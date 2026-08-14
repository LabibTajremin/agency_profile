/*
 * The consent banner's behaviour.
 *
 * Writes a cookie of granted categories and nothing else — no timestamp, no identifier. A
 * consent cookie that identifies the visitor is itself the thing being consented to.
 *
 * The page is reloaded after a choice because the tracking scripts are decided server-side. That
 * is deliberate: deciding client-side would mean the scripts are on the page already, waiting to
 * be told whether they are allowed, which is not consent.
 */

(function consent() {
  var banner = document.querySelector('[data-edulume-consent]');

  if (banner === null) {
    return;
  }

  function store(value) {
    document.cookie =
      'edulume-consent=' + encodeURIComponent(value) + ';path=/;max-age=15552000;samesite=lax';
    window.location.reload();
  }

  var accept = banner.querySelector('[data-edulume-consent-accept]');
  var reject = banner.querySelector('[data-edulume-consent-reject]');

  if (accept !== null) {
    accept.addEventListener('click', function () {
      var granted = Array.prototype.slice
        .call(banner.querySelectorAll('input[type="checkbox"]:checked'))
        .map(function (input) {
          return input.value;
        });

      store(granted.length === 0 ? 'none' : granted.join(','));
    });
  }

  if (reject !== null) {
    reject.addEventListener('click', function () {
      store('none');
    });
  }
})();
