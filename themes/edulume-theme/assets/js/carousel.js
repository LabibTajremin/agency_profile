/*
 * Carousels and marquees.
 *
 * Built on scroll snapping rather than on transforms. A snapping track is a scrollable region
 * the browser already knows how to drive: it flicks on a touchscreen, it responds to a
 * trackpad, it exposes a scrollbar, and arrow keys work inside it because it is focusable
 * content rather than a stack of absolutely-positioned slides. The buttons below only call
 * `scrollBy`, so nothing here has to reimplement momentum or bounds.
 *
 * The markup is whatever the editor put inside the block. This attaches to the wrapper the
 * block renderer emits, adds controls, and leaves the slides alone — so a carousel with the
 * script blocked is a horizontally scrollable row, which is still usable.
 */

(function carousel() {
  var strings = (window.edulumeStrings || {}).carousel || {};

  function text(key, fallback) {
    return typeof strings[key] === 'string' && strings[key] !== '' ? strings[key] : fallback;
  }

  function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  }

  function build(block) {
    var track = block.querySelector('.edulume-carousel__track') || block.firstElementChild;

    if (track === null || track.children.length < 2) {
      return;
    }

    track.classList.add('edulume-carousel__track');
    track.setAttribute('tabindex', '0');
    track.setAttribute('role', 'group');
    track.setAttribute('aria-label', text('track', 'Carousel'));

    var controls = document.createElement('div');
    controls.className = 'edulume-carousel__controls';

    function button(direction, label) {
      var element = document.createElement('button');

      element.type = 'button';
      element.className = 'edulume-carousel__button edulume-carousel__button--' + direction;
      element.innerHTML = '<span class="screen-reader-text"></span>';
      element.querySelector('span').textContent = label;

      element.addEventListener('click', function () {
        // One viewport-width of travel, so the step matches whatever the layout is showing
        // rather than assuming a fixed slide size.
        track.scrollBy({
          left: direction === 'next' ? track.clientWidth : -track.clientWidth,
          behavior: prefersReducedMotion() ? 'auto' : 'smooth',
        });
      });

      return element;
    }

    var previous = button('previous', text('previous', 'Previous'));
    var next = button('next', text('next', 'Next'));

    controls.appendChild(previous);
    controls.appendChild(next);
    block.appendChild(controls);

    /*
     * The buttons disable at the ends rather than wrapping. A carousel that silently loops
     * gives a keyboard or screen-reader user no way to know they have seen everything.
     */
    function reflect() {
      var maximum = track.scrollWidth - track.clientWidth;

      previous.disabled = track.scrollLeft <= 1;
      next.disabled = track.scrollLeft >= maximum - 1;
    }

    track.addEventListener('scroll', reflect, { passive: true });
    window.addEventListener('resize', reflect);
    reflect();
  }

  function start() {
    var blocks = document.querySelectorAll(
      '[data-edulume-block="carousel"], [data-edulume-block="logo-wall"].is-style-marquee'
    );

    Array.prototype.forEach.call(blocks, build);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
