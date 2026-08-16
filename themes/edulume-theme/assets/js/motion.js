/*
 * Scroll-triggered motion.
 *
 * Elements are revealed by adding a class when they enter the viewport; the transitions
 * themselves live in CSS, where the compiled duration and easing tokens already are. Animating
 * from JavaScript would mean the configurator's motion settings had to be duplicated here and
 * kept in step, which they would not be.
 *
 * Two rules the rest of the file exists to honour:
 *
 *   1. `prefers-reduced-motion` wins outright. Not "shorter", not "less" — nothing observes,
 *      nothing animates, and every element is left in its final state immediately. A visitor
 *      who has asked their operating system for less motion has told you something about how
 *      their body reacts, not about their taste.
 *   2. Content is visible before this runs. The reveal class removes an opacity that CSS only
 *      applies when the document says motion is on, so a blocked or failed script leaves the
 *      page readable rather than blank.
 */

(function motion() {
  var REVEAL_ATTRIBUTE = 'data-edulume-motion';
  var VISIBLE_CLASS = 'is-in-view';

  function reduced() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  }

  function revealAll(elements) {
    Array.prototype.forEach.call(elements, function (element) {
      element.classList.add(VISIBLE_CLASS);
    });
  }

  function start() {
    var elements = document.querySelectorAll('[' + REVEAL_ATTRIBUTE + ']');

    if (elements.length === 0) {
      return;
    }

    // Marks the document so CSS knows the reveal is being driven, and only then hides anything.
    document.documentElement.setAttribute('data-edulume-motion-active', 'true');

    if (reduced() || typeof window.IntersectionObserver !== 'function') {
      revealAll(elements);

      return;
    }

    var observer = new window.IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) {
            return;
          }

          var element = entry.target;
          // Stagger is per-container, read from the element, so a grid can cascade without the
          // script knowing anything about the grid.
          var delay = Number(element.getAttribute('data-edulume-motion-delay') || '0');

          window.setTimeout(
            function () {
              element.classList.add(VISIBLE_CLASS);
            },
            Number.isFinite(delay) ? Math.max(0, Math.min(delay, 1000)) : 0
          );

          // Once revealed, stop watching. Re-animating on every scroll past is the thing that
          // makes a long page feel restless.
          observer.unobserve(element);
        });
      },
      { rootMargin: '0px 0px -10% 0px', threshold: 0.01 }
    );

    Array.prototype.forEach.call(elements, function (element) {
      observer.observe(element);
    });

    /*
     * A visitor can turn reduced motion on while the page is open. Watching for it means the
     * setting takes effect immediately rather than at the next navigation.
     */
    var query = window.matchMedia('(prefers-reduced-motion: reduce)');
    var onChange = function () {
      if (query.matches) {
        observer.disconnect();
        revealAll(document.querySelectorAll('[' + REVEAL_ATTRIBUTE + ']'));
      }
    };

    if (typeof query.addEventListener === 'function') {
      query.addEventListener('change', onChange);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
