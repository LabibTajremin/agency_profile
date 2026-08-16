/*
 * The chrome module: mobile drawer, mode toggle, announcement dismissal, back-to-top and the
 * preloader.
 *
 * Everything here enhances markup that already works. The drawer's links are real links, the
 * mode toggle only swaps an attribute the CSS reads, and the announcement is visible before
 * this file loads — so a failed or blocked script costs polish, never access.
 */

(function chrome() {
  var FOCUSABLE =
    'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

  function focusableWithin(container) {
    return Array.prototype.slice
      .call(container.querySelectorAll(FOCUSABLE))
      .filter(function (element) {
        return element.offsetParent !== null;
      });
  }

  /*
   * The focus trap.
   *
   * Without it a keyboard user tabs straight out of an open drawer and into the page behind it,
   * which is still covered — they are then navigating something they cannot see.
   */
  function trapFocus(container, event) {
    var focusable = focusableWithin(container);

    if (focusable.length === 0) {
      return;
    }

    var first = focusable[0];
    var last = focusable[focusable.length - 1];

    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
      return;
    }

    if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  }

  function setUpDrawer() {
    var toggle = document.querySelector('.edulume-drawer-toggle');
    var drawer = document.getElementById('edulume-drawer');

    if (toggle === null || drawer === null) {
      return;
    }

    var closer = drawer.querySelector('.edulume-drawer__close');
    var lastFocused = null;

    function open() {
      lastFocused = document.activeElement;
      drawer.hidden = false;
      toggle.setAttribute('aria-expanded', 'true');

      var focusable = focusableWithin(drawer);

      if (focusable.length > 0) {
        focusable[0].focus();
      }
    }

    function close() {
      drawer.hidden = true;
      toggle.setAttribute('aria-expanded', 'false');

      // Returning focus to what opened the drawer, rather than to the top of the document,
      // is the difference between closing a menu and losing your place on the page.
      if (lastFocused !== null && typeof lastFocused.focus === 'function') {
        lastFocused.focus();
      }
    }

    toggle.addEventListener('click', function () {
      if (drawer.hidden) {
        open();
      } else {
        close();
      }
    });

    if (closer !== null) {
      closer.addEventListener('click', close);
    }

    document.addEventListener('keydown', function (event) {
      if (drawer.hidden) {
        return;
      }

      if (event.key === 'Escape') {
        close();
        return;
      }

      if (event.key === 'Tab') {
        trapFocus(drawer, event);
      }
    });
  }

  function setUpModeToggle() {
    var buttons = document.querySelectorAll('[data-edulume-mode-toggle]');

    if (buttons.length === 0) {
      return;
    }

    function current() {
      return document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
    }

    /*
     * The button is driven from the document attribute rather than the other way round.
     *
     * The no-flash script in the head has already set `data-theme` before first paint, so on
     * load the attribute is the truth and the control has to catch up to it. Rendering the
     * state into the markup instead would mean the server guessing a mode it cannot know.
     *
     * Both the accessible name and the tooltip name the *destination*, because that is what
     * pressing the button does. A control labelled with the state you are already in is the
     * single most common defect in these toggles.
     */
    function reflect() {
      var goingDark = current() === 'light';

      Array.prototype.forEach.call(buttons, function (button) {
        var label = goingDark
          ? button.getAttribute('data-label-to-dark')
          : button.getAttribute('data-label-to-light');

        button.setAttribute('aria-pressed', goingDark ? 'false' : 'true');

        if (label) {
          button.setAttribute('aria-label', label);
          button.setAttribute('title', label);
        }
      });
    }

    function apply(next) {
      document.documentElement.setAttribute('data-theme', next);

      try {
        window.localStorage.setItem('edulume-theme', next);
      } catch (error) {
        // Private browsing refuses storage. The choice still applies for this page view.
      }

      // Mirrored into a cookie so the server can pick the right logo file on the next
      // request instead of shipping the light one and swapping it after paint.
      document.cookie = 'edulume-theme=' + next + ';path=/;max-age=31536000;samesite=lax';
      reflect();
    }

    Array.prototype.forEach.call(buttons, function (button) {
      button.addEventListener('click', function () {
        apply(current() === 'dark' ? 'light' : 'dark');
      });
    });

    reflect();
  }

  function setUpAnnouncement() {
    var bar = document.querySelector('.edulume-announcement[data-dismissible="true"]');

    if (bar === null) {
      return;
    }

    var key = 'edulume-announcement-' + bar.getAttribute('data-dismissal-key');
    var dismiss = bar.querySelector('.edulume-announcement__dismiss');

    try {
      if (window.localStorage.getItem(key) === 'dismissed') {
        bar.remove();
        return;
      }
    } catch (error) {
      // No storage: the bar simply reappears next visit, which is the safe direction to fail.
    }

    if (dismiss === null) {
      return;
    }

    dismiss.addEventListener('click', function () {
      bar.remove();

      try {
        window.localStorage.setItem(key, 'dismissed');
      } catch (error) {
        // As above.
      }
    });
  }

  function setUpBackToTop() {
    var button = document.querySelector('.edulume-fab__action--back-to-top');

    if (button === null) {
      return;
    }

    button.addEventListener('click', function (event) {
      event.preventDefault();

      var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

      window.scrollTo({ top: 0, behavior: reduced ? 'auto' : 'smooth' });
    });
  }

  function setUpPreloader() {
    var preloader = document.querySelector('[data-edulume-preloader]');

    if (preloader === null) {
      return;
    }

    // Marked done on load, and again on a timer, so a stalled asset can never leave the page
    // covered by a full-screen overlay.
    function done() {
      preloader.setAttribute('data-done', 'true');
    }

    window.addEventListener('load', done);
    window.setTimeout(done, 4000);
  }

  function start() {
    setUpDrawer();
    setUpModeToggle();
    setUpAnnouncement();
    setUpBackToTop();
    setUpPreloader();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
