/*
 * Narrowing a country's university list.
 *
 * The whole list is already in the page — this hides rows, it does not fetch them. That is what
 * makes the filter instant and what makes the page work with the script blocked: without it the
 * form is a plain GET and the unfiltered list is still the page.
 *
 * The current filter is mirrored into the query string with replaceState, so a narrowed view can
 * be shared and the back button behaves. `replaceState` rather than `pushState`: typing four
 * characters into a search box should not put four entries in somebody's history.
 */

(function universityFilter() {
  var strings = (window.edulumeStrings || {})['university-filter'] || {};

  function text(key, fallback) {
    return typeof strings[key] === 'string' ? strings[key] : fallback;
  }

  function setUp(form) {
    var grid = document.querySelector('[data-edulume-university]');

    if (!grid) {
      return;
    }

    var cards = Array.prototype.slice.call(document.querySelectorAll('[data-edulume-university]'));
    var search = form.querySelector('[data-edulume-filter-search]');
    var intake = form.querySelector('[data-edulume-filter-intake]');
    var count = document.querySelector('[data-edulume-filter-count]');

    // The submit button only exists inside <noscript>, but a stray Enter keypress would still
    // reload the page and throw away the filter that is already applied.
    form.addEventListener('submit', function (event) {
      event.preventDefault();
    });

    function apply() {
      var term = search ? search.value.trim().toLowerCase() : '';
      var month = intake ? intake.value.trim().toLowerCase() : '';
      var shown = 0;

      cards.forEach(function (card) {
        var name = card.getAttribute('data-name') || '';
        var intakes = card.getAttribute('data-intakes') || '';
        var matches =
          (term === '' || name.indexOf(term) !== -1) &&
          (month === '' || intakes.split('|').indexOf(month) !== -1);

        card.hidden = !matches;

        if (matches) {
          shown += 1;
        }
      });

      if (count) {
        count.textContent =
          shown === 1
            ? text('one', '1 university')
            : text('many', '%s universities').replace('%s', String(shown));
      }

      remember(term, month);
    }

    function remember(term, month) {
      if (!window.history || typeof window.history.replaceState !== 'function') {
        return;
      }

      var url = new window.URL(window.location.href);

      if (term === '') {
        url.searchParams.delete('q');
      } else {
        url.searchParams.set('q', term);
      }

      if (month === '') {
        url.searchParams.delete('intake');
      } else {
        url.searchParams.set('intake', month);
      }

      window.history.replaceState({}, '', url.toString());
    }

    function restore() {
      var url = new window.URL(window.location.href);
      var term = url.searchParams.get('q');
      var month = url.searchParams.get('intake');

      if (search && term) {
        search.value = term;
      }

      if (intake && month) {
        intake.value = month;
      }

      if (term || month) {
        apply();
      }
    }

    if (search) {
      search.addEventListener('input', apply);
    }

    if (intake) {
      intake.addEventListener('change', apply);
    }

    restore();
  }

  function start() {
    Array.prototype.forEach.call(
      document.querySelectorAll('[data-edulume-university-filter]'),
      setUp
    );
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
