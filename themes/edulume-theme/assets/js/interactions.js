/*
 * The small interactions several blocks declare and nothing implemented: tabs, counters,
 * before-and-after, and click-to-play video.
 *
 * Each of these was named in the block catalogue as a feature a block requires, and each
 * resolved to no module — the enqueue loop skips a module whose file is missing, silently, so
 * the blocks rendered and did nothing. Grouped into one file because they are twenty lines
 * apiece and four extra requests on a page that uses all four is worse than one.
 */

(function interactions() {
  var strings = (window.edulumeStrings || {}).interactions || {};

  function text(key, fallback) {
    return typeof strings[key] === 'string' && strings[key] !== '' ? strings[key] : fallback;
  }

  function reduced() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  }

  /*
   * Tabs, following the WAI-ARIA pattern: arrow keys move between tabs, Home and End jump to
   * the ends, and only the selected tab is in the tab order — so Tab moves out of the tablist
   * to the panel rather than through every tab in it.
   */
  function setUpTabs(root) {
    var tabs = Array.prototype.slice.call(root.querySelectorAll('[role="tab"]'));

    if (tabs.length === 0) {
      return;
    }

    function select(index) {
      tabs.forEach(function (tab, position) {
        var selected = position === index;
        var panel = document.getElementById(tab.getAttribute('aria-controls') || '');

        tab.setAttribute('aria-selected', selected ? 'true' : 'false');
        tab.setAttribute('tabindex', selected ? '0' : '-1');

        if (panel !== null) {
          panel.hidden = !selected;
        }
      });
    }

    tabs.forEach(function (tab, index) {
      tab.addEventListener('click', function () {
        select(index);
        tab.focus();
      });

      tab.addEventListener('keydown', function (event) {
        var next = null;

        if (event.key === 'ArrowRight') {
          next = (index + 1) % tabs.length;
        } else if (event.key === 'ArrowLeft') {
          next = (index - 1 + tabs.length) % tabs.length;
        } else if (event.key === 'Home') {
          next = 0;
        } else if (event.key === 'End') {
          next = tabs.length - 1;
        }

        if (next !== null) {
          event.preventDefault();
          select(next);
          tabs[next].focus();
        }
      });
    });

    select(0);
  }

  /*
   * Counters. The final value is in the markup, so a blocked script leaves the real number on
   * the page rather than a zero that never counts up.
   */
  function setUpCounter(element) {
    var target = Number(element.getAttribute('data-edulume-counter'));

    if (
      !Number.isFinite(target) ||
      reduced() ||
      typeof window.IntersectionObserver !== 'function'
    ) {
      return;
    }

    var observer = new window.IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) {
          return;
        }

        observer.unobserve(element);

        var started = null;
        var duration = 900;

        function step(timestamp) {
          started = started === null ? timestamp : started;

          var progress = Math.min((timestamp - started) / duration, 1);
          // Eased out, so it decelerates into the real figure instead of stopping dead.
          var eased = 1 - Math.pow(1 - progress, 3);

          element.textContent = String(Math.round(target * eased));

          if (progress < 1) {
            window.requestAnimationFrame(step);
          } else {
            element.textContent = String(target);
          }
        }

        window.requestAnimationFrame(step);
      });
    });

    observer.observe(element);
  }

  /*
   * Before and after. A range input rather than a drag handler: it is keyboard operable, it
   * announces a value, and it works on touch without a single pointer event.
   */
  function setUpBeforeAfter(root) {
    var slider = root.querySelector('input[type="range"]');

    if (slider === null) {
      return;
    }

    slider.setAttribute('aria-label', text('reveal', 'Reveal the after image'));

    function apply() {
      root.style.setProperty('--edulume-reveal', String(slider.value) + '%');
    }

    slider.addEventListener('input', apply);
    apply();
  }

  /*
   * Click-to-play video. The embed is only inserted once somebody asks for it, so a page with
   * three videos costs three thumbnails rather than three third-party players — and sets no
   * third-party cookie until there is consent in the form of a deliberate click.
   */
  function setUpVideo(root) {
    var button = root.querySelector('[data-edulume-video-play]');
    var source = root.getAttribute('data-edulume-video');

    if (button === null || source === null) {
      return;
    }

    button.addEventListener('click', function () {
      var frame = document.createElement('iframe');

      frame.src = source;
      frame.title = root.getAttribute('data-edulume-video-title') || text('video', 'Video');
      frame.loading = 'lazy';
      frame.allow = 'accelerometer; encrypted-media; picture-in-picture';
      frame.setAttribute('allowfullscreen', 'true');
      frame.className = 'edulume-video__frame';

      root.textContent = '';
      root.appendChild(frame);
      frame.focus();
    });
  }

  function start() {
    Array.prototype.forEach.call(document.querySelectorAll('[data-edulume-tabs]'), setUpTabs);
    Array.prototype.forEach.call(document.querySelectorAll('[data-edulume-counter]'), setUpCounter);
    Array.prototype.forEach.call(
      document.querySelectorAll('[data-edulume-before-after]'),
      setUpBeforeAfter
    );
    Array.prototype.forEach.call(document.querySelectorAll('[data-edulume-video]'), setUpVideo);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
