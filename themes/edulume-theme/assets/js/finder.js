/*
 * The finder: faceted filtering without a page reload, with the filter state in the URL.
 *
 * The form works on its own. This module intercepts the submit, fetches the same URL, and swaps
 * the results — so a failed request, a blocked script or a slow connection degrades to the
 * ordinary form submission rather than to a dead filter.
 */

(function finder() {
  var form = document.querySelector('[data-edulume-finder]');
  var results = document.getElementById('edulume-results');

  if (form === null || results === null) {
    return;
  }

  var status = form.querySelector('.edulume-finder__status');
  var inFlight = null;

  function announce(message) {
    if (status !== null) {
      status.textContent = message;
    }
  }

  function urlFor() {
    var parameters = new URLSearchParams(new FormData(form));
    var cleaned = new URLSearchParams();

    // Empty parameters are dropped so the shared URL carries the filter and nothing else —
    // two people who chose the same filters get byte-identical links.
    parameters.forEach(function (value, key) {
      if (value !== '') {
        cleaned.append(key, value);
      }
    });

    var query = cleaned.toString();

    return window.location.pathname + (query === '' ? '' : '?' + query);
  }

  function swap(html) {
    var parsed = new DOMParser().parseFromString(html, 'text/html');
    var replacement = parsed.getElementById('edulume-results');

    if (replacement === null) {
      return false;
    }

    results.replaceWith(replacement);
    results = replacement;

    var count = replacement.querySelectorAll('.edulume-card').length;

    announce(count === 1 ? '1 result' : String(count) + ' results');

    return true;
  }

  function apply(pushState) {
    var url = urlFor();

    if (inFlight !== null) {
      inFlight.abort();
    }

    inFlight = new AbortController();
    announce('Filtering…');

    window
      .fetch(url, { signal: inFlight.signal, headers: { 'X-Requested-With': 'fetch' } })
      .then(function (response) {
        return response.ok ? response.text() : Promise.reject(new Error(String(response.status)));
      })
      .then(function (html) {
        if (!swap(html)) {
          window.location.assign(url);
          return;
        }

        if (pushState) {
          window.history.pushState({ edulume: true }, '', url);
        }
      })
      .catch(function (error) {
        if (error.name === 'AbortError') {
          return;
        }

        // The form still points at the same URL, so falling back to a full navigation gives
        // the visitor exactly the results they asked for.
        window.location.assign(url);
      });
  }

  form.addEventListener('submit', function (event) {
    event.preventDefault();
    apply(true);
  });

  form.addEventListener('change', function (event) {
    if (event.target.matches('input, select')) {
      apply(true);
    }
  });

  // The back button has to return to the previous filter, not to the previous page.
  window.addEventListener('popstate', function () {
    window.location.reload();
  });
})();
