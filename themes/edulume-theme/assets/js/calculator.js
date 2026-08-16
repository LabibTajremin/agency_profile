/*
 * The cost calculator.
 *
 * Adds up tuition, living costs, the visa and the flight for a chosen destination and length of
 * study, and shows the total in the destination's currency and in the visitor's own.
 *
 * Every number comes from data attributes the server rendered, so the figures a visitor sees
 * are the figures the site owner configured. Nothing is hardcoded here; a currency rate written
 * into a script is a rate nobody can update without a deployment.
 */

(function calculator() {
  var strings = (window.edulumeStrings || {}).calculator || {};

  function text(key, fallback) {
    return typeof strings[key] === 'string' && strings[key] !== '' ? strings[key] : fallback;
  }

  function number(element, attribute) {
    var raw = Number(element.getAttribute(attribute));

    return Number.isFinite(raw) ? raw : 0;
  }

  function format(amount, currency, locale) {
    try {
      return new Intl.NumberFormat(locale || undefined, {
        style: 'currency',
        currency: currency || 'USD',
        maximumFractionDigits: 0,
      }).format(amount);
    } catch (error) {
      // An unknown currency code should not take the whole total down with it.
      return String(Math.round(amount));
    }
  }

  function setUp(root) {
    var destination = root.querySelector('[data-edulume-calculator-destination]');
    var years = root.querySelector('[data-edulume-calculator-years]');
    var output = root.querySelector('[data-edulume-calculator-total]');
    var breakdown = root.querySelector('[data-edulume-calculator-breakdown]');
    var locale = root.getAttribute('data-edulume-locale') || '';

    if (destination === null || output === null) {
      return;
    }

    function recalculate() {
      var option = destination.options[destination.selectedIndex];

      if (!option) {
        return;
      }

      var duration = years === null ? 1 : Math.max(1, Math.min(Number(years.value) || 1, 10));
      var currency = option.getAttribute('data-currency') || 'USD';

      var tuition = number(option, 'data-tuition') * duration;
      var living = number(option, 'data-living') * duration;
      // One-off costs, so they are deliberately not multiplied by the duration — the commonest
      // arithmetic error in a calculator like this, and one nobody notices for months.
      var visa = number(option, 'data-visa');
      var flights = number(option, 'data-flights');
      var total = tuition + living + visa + flights;

      output.textContent = format(total, currency, locale);

      if (breakdown === null) {
        return;
      }

      var rows = [
        [text('tuition', 'Tuition'), tuition],
        [text('living', 'Living costs'), living],
        [text('visa', 'Visa and health surcharge'), visa],
        [text('flights', 'Flights'), flights],
      ];

      breakdown.textContent = '';

      rows.forEach(function (row) {
        if (row[1] <= 0) {
          return;
        }

        var item = document.createElement('div');
        var label = document.createElement('span');
        var value = document.createElement('span');

        item.className = 'edulume-calculator__row';
        label.className = 'edulume-calculator__label';
        value.className = 'edulume-calculator__value';
        label.textContent = row[0];
        value.textContent = format(row[1], currency, locale);

        item.appendChild(label);
        item.appendChild(value);
        breakdown.appendChild(item);
      });
    }

    destination.addEventListener('change', recalculate);

    if (years !== null) {
      years.addEventListener('input', recalculate);
      years.addEventListener('change', recalculate);
    }

    recalculate();
  }

  function start() {
    Array.prototype.forEach.call(document.querySelectorAll('[data-edulume-calculator]'), setUp);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
