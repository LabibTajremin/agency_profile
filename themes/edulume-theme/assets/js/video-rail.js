/*
 * The video rail: facade activation, one player at a time, and a hard cap on live iframes.
 *
 * Three rules the whole file exists to keep.
 *
 *   1. **Never more than two live iframes.** A Facebook embed is somebody else's application
 *      running in your page; ten of them on a long scroll leaks memory badly enough that the tab
 *      becomes unusable. When a card falls more than two positions out of the centre, its iframe
 *      src is set to about:blank and the node is removed — src alone is not enough, because the
 *      element keeps its listeners.
 *   2. **Autoplay is muted or it does not happen.** Every browser blocks autoplay with sound. An
 *      unmuted request is a video that quietly never starts, which looks exactly like a broken
 *      player, so the mute is in the embed URL rather than left to a flag somebody might change.
 *   3. **A rejected play() falls back to the poster.** The promise rejects on iOS low-power
 *      mode, with Data Saver on, and whenever the browser has simply decided not to. Leaving the
 *      element in place gives a black rectangle; tearing it down gives the poster and a button.
 *
 * Autoplay is abandoned entirely on a metered or slow connection, at phone widths, under
 * prefers-reduced-motion, and whenever consent is required — the point of a consent gate is that
 * nothing loads until somebody says so.
 */

(function videoRail() {
  var CONSENT_KEY = 'edulume-video-consent';
  var MAX_LIVE_PLAYERS = 2;
  var strings = (window.edulumeStrings || {})['video-rail'] || {};

  function text(key, fallback) {
    return typeof strings[key] === 'string' ? strings[key] : fallback;
  }

  function saidYesAlready() {
    try {
      return window.localStorage.getItem(CONSENT_KEY) === 'granted';
    } catch (error) {
      return false;
    }
  }

  function rememberConsent() {
    try {
      window.localStorage.setItem(CONSENT_KEY, 'granted');
    } catch (error) {
      // Private browsing refuses storage. The choice still holds for this page view.
    }
  }

  /*
   * Conditions under which nothing should start on its own.
   *
   * saveData and a 2g effectiveType are the visitor telling the browser they are paying for
   * bytes; a narrow viewport is very often the same person. Honouring that is not a nicety in a
   * product sold to consultancies whose visitors are on mobile data.
   */
  function autoplayIsUnwelcome() {
    var connection = window.navigator.connection || {};

    if (connection.saveData === true) {
      return true;
    }

    if (/^(slow-)?2g$/.test(String(connection.effectiveType || ''))) {
      return true;
    }

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      return true;
    }

    return window.innerWidth < 480;
  }

  function initVideoRail(root, options) {
    var settings = options || {};
    var track = root.querySelector('[data-edulume-video-track]');

    if (!track) {
      return;
    }

    var cards = Array.prototype.slice.call(root.querySelectorAll('[data-edulume-video-card]'));

    if (cards.length === 0) {
      return;
    }

    var consentRequired = root.getAttribute('data-edulume-video-consent') === 'true';
    var wantsAutoplay =
      settings.autoplay !== false &&
      root.getAttribute('data-edulume-video-autoplay') === 'true' &&
      !consentRequired &&
      !autoplayIsUnwelcome();

    var live = [];

    function teardown(card) {
      var frame = card.querySelector('[data-edulume-video-frame]');
      var player = card.querySelector('iframe, video');

      if (!player || !frame) {
        return;
      }

      if (player.tagName === 'IFRAME') {
        // Blanked before removal. Removing the node alone leaves the embed's own timers
        // running in some browsers, which is the leak this whole cap exists to prevent.
        player.setAttribute('src', 'about:blank');
      } else if (typeof player.pause === 'function') {
        player.pause();
        player.removeAttribute('src');
        player.load();
      }

      frame.removeChild(player);
      card.removeAttribute('data-edulume-video-live');
      live = live.filter(function (entry) {
        return entry !== card;
      });
    }

    function enforceBudget(keep) {
      while (live.length > MAX_LIVE_PLAYERS) {
        var oldest = live[0] === keep && live.length > 1 ? live[1] : live[0];

        teardown(oldest);
      }
    }

    function build(card, autoplay) {
      var embed = card.getAttribute('data-edulume-video-embed');
      var source = card.getAttribute('data-edulume-video-source');
      var frame = card.querySelector('[data-edulume-video-frame]');

      if (!embed || !frame) {
        return null;
      }

      if (source === 'mp4') {
        var video = document.createElement('video');

        video.className = 'edulume-video-card__player';
        video.setAttribute('src', embed);
        video.setAttribute('playsinline', '');
        video.setAttribute('preload', 'none');
        video.muted = true;
        video.controls = true;

        frame.appendChild(video);

        if (autoplay) {
          var attempt = video.play();

          if (attempt && typeof attempt['catch'] === 'function') {
            attempt['catch'](function () {
              // The browser said no. Poster and a button beats a black rectangle.
              teardown(card);
            });
          }
        }

        return video;
      }

      var iframe = document.createElement('iframe');

      iframe.className = 'edulume-video-card__player';
      iframe.setAttribute('src', embed);
      iframe.setAttribute('loading', 'lazy');
      iframe.setAttribute('allow', 'autoplay; encrypted-media; picture-in-picture');
      iframe.setAttribute('allowfullscreen', '');
      iframe.setAttribute(
        'title',
        card.getAttribute('data-edulume-video-title') || text('video', 'Video')
      );

      frame.appendChild(iframe);

      return iframe;
    }

    function activate(card, autoplay) {
      if (card.hasAttribute('data-edulume-video-live')) {
        return;
      }

      if (card.getAttribute('data-edulume-video-consent') === 'required' && !saidYesAlready()) {
        card.setAttribute('data-edulume-video-asking', 'true');

        return;
      }

      if (!build(card, autoplay)) {
        return;
      }

      card.setAttribute('data-edulume-video-live', 'true');
      live.push(card);
      enforceBudget(card);
    }

    /*
     * Which card is nearest the middle of the visible track.
     *
     * Measured against the track's own box rather than the viewport's, so the answer is the same
     * whether the rail is at the top of the page or halfway down it.
     */
    function centreCard() {
      var box = track.getBoundingClientRect();
      var middle = box.left + box.width / 2;
      var closest = null;
      var shortest = Infinity;

      cards.forEach(function (card) {
        var cardBox = card.getBoundingClientRect();
        var distance = Math.abs(cardBox.left + cardBox.width / 2 - middle);

        if (distance < shortest) {
          shortest = distance;
          closest = card;
        }
      });

      return closest;
    }

    function playCentre() {
      if (!wantsAutoplay) {
        return;
      }

      var card = centreCard();

      if (!card) {
        return;
      }

      live.slice().forEach(function (other) {
        if (other !== card) {
          teardown(other);
        }
      });

      activate(card, true);
    }

    cards.forEach(function (card) {
      var play = card.querySelector('[data-edulume-video-play]');
      var accept = card.querySelector('[data-edulume-video-consent-accept]');
      var all = card.querySelector('[data-edulume-video-consent-all]');

      if (play) {
        play.addEventListener('click', function () {
          if (card.getAttribute('data-edulume-video-consent') === 'required' && !saidYesAlready()) {
            card.setAttribute('data-edulume-video-asking', 'true');

            return;
          }

          activate(card, true);
        });
      }

      if (accept) {
        accept.addEventListener('click', function () {
          if (all && all.checked) {
            rememberConsent();
          }

          card.removeAttribute('data-edulume-video-consent');
          card.removeAttribute('data-edulume-video-asking');
          activate(card, true);
        });
      }
    });

    if (typeof window.IntersectionObserver === 'function') {
      var observer = new window.IntersectionObserver(
        function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              playCentre();

              return;
            }

            // The rail has left the screen. Nothing playing off-screen, ever — that is bandwidth
            // and battery spent on something nobody is looking at.
            live.slice().forEach(teardown);
          });
        },
        { threshold: 0.6 }
      );

      observer.observe(root);
    }

    var pending = false;

    track.addEventListener(
      'scroll',
      function () {
        if (pending) {
          return;
        }

        pending = true;
        window.requestAnimationFrame(function () {
          pending = false;
          playCentre();
        });
      },
      { passive: true }
    );

    function scrollBy(direction) {
      track.scrollBy({ left: direction * track.clientWidth * 0.8, behavior: 'smooth' });
    }

    var previous = root.querySelector('[data-edulume-video-previous]');
    var next = root.querySelector('[data-edulume-video-next]');

    if (previous) {
      previous.addEventListener('click', function () {
        scrollBy(-1);
      });
    }

    if (next) {
      next.addEventListener('click', function () {
        scrollBy(1);
      });
    }

    track.addEventListener('keydown', function (event) {
      if (event.key === 'ArrowRight') {
        event.preventDefault();
        scrollBy(1);
      } else if (event.key === 'ArrowLeft') {
        event.preventDefault();
        scrollBy(-1);
      }
    });
  }

  // Exposed so the destination page's single intake video reuses this rather than growing a
  // second player nobody keeps in step with this one.
  window.edulumeInitVideoRail = initVideoRail;

  function start() {
    Array.prototype.forEach.call(
      document.querySelectorAll('[data-edulume-video-rail]'),
      function (root) {
        initVideoRail(root, {
          single: root.getAttribute('data-edulume-video-single') === 'true',
        });
      }
    );
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
