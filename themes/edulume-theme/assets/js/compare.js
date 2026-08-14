/*
 * Compare and shortlist.
 *
 * Both live in `localStorage` with no login, because asking someone to register before they can
 * save a course loses the visitor who was about to become a lead. The bounds mirror the PHP
 * side exactly — two to four for a comparison, fifty for a shortlist — so what the browser
 * stores is always something the server will accept.
 */

(function compare() {
  var COMPARE_KEY = 'edulume-compare';
  var SHORTLIST_KEY = 'edulume-shortlist';
  var COMPARE_MAXIMUM = 4;
  var SHORTLIST_MAXIMUM = 50;

  function read(key) {
    try {
      var stored = JSON.parse(window.localStorage.getItem(key) || '[]');

      return Array.isArray(stored)
        ? stored.filter(function (id) {
            return Number.isInteger(id) && id > 0;
          })
        : [];
    } catch (error) {
      return [];
    }
  }

  function write(key, ids) {
    try {
      window.localStorage.setItem(key, JSON.stringify(ids));
    } catch (error) {
      // Storage refused — private browsing, or a full quota. The page still works; the
      // selection simply does not survive the next navigation.
    }
  }

  function toggle(key, maximum, id) {
    var ids = read(key);
    var index = ids.indexOf(id);

    if (index !== -1) {
      ids.splice(index, 1);
      write(key, ids);

      return false;
    }

    if (ids.length >= maximum) {
      return null;
    }

    ids.push(id);
    write(key, ids);

    return true;
  }

  function reflect() {
    var compared = read(COMPARE_KEY);
    var shortlisted = read(SHORTLIST_KEY);

    document.querySelectorAll('[data-edulume-compare]').forEach(function (button) {
      var id = Number(button.getAttribute('data-edulume-compare'));
      var active = compared.indexOf(id) !== -1;

      button.setAttribute('aria-pressed', active ? 'true' : 'false');
      button.disabled = !active && compared.length >= COMPARE_MAXIMUM;
    });

    document.querySelectorAll('[data-edulume-shortlist]').forEach(function (button) {
      var id = Number(button.getAttribute('data-edulume-shortlist'));

      button.setAttribute('aria-pressed', shortlisted.indexOf(id) !== -1 ? 'true' : 'false');
    });

    document.querySelectorAll('[data-edulume-compare-count]').forEach(function (element) {
      element.textContent = String(compared.length);
    });

    document.querySelectorAll('[data-edulume-compare-link]').forEach(function (link) {
      link.hidden = compared.length < 2;
      link.setAttribute(
        'href',
        link.getAttribute('data-edulume-compare-link') + '?items=' + compared.join(',')
      );
    });
  }

  document.addEventListener('click', function (event) {
    var compareButton = event.target.closest('[data-edulume-compare]');

    if (compareButton !== null) {
      event.preventDefault();
      toggle(
        COMPARE_KEY,
        COMPARE_MAXIMUM,
        Number(compareButton.getAttribute('data-edulume-compare'))
      );
      reflect();

      return;
    }

    var shortlistButton = event.target.closest('[data-edulume-shortlist]');

    if (shortlistButton !== null) {
      event.preventDefault();
      toggle(
        SHORTLIST_KEY,
        SHORTLIST_MAXIMUM,
        Number(shortlistButton.getAttribute('data-edulume-shortlist'))
      );
      reflect();
    }
  });

  // Two tabs open on the same site should agree about what is shortlisted.
  window.addEventListener('storage', reflect);

  reflect();
})();
