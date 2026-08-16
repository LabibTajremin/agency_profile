/*
 * The eligibility check.
 *
 * A short quiz — destination, level, grade, English score — that answers with a verdict rather
 * than a score. "Likely", "borderline", "unlikely", and what would change it.
 *
 * The thresholds are attributes on the destination options, not constants here, because they
 * differ per destination and change every intake. The verdict is deliberately cautious: this
 * tells somebody whether it is worth a conversation, and the conversation is where a
 * counsellor looks at the whole picture.
 */

(function eligibility() {
  var strings = (window.edulumeStrings || {}).eligibility || {};

  function text(key, fallback) {
    return typeof strings[key] === 'string' && strings[key] !== '' ? strings[key] : fallback;
  }

  function number(source, attribute) {
    var raw = Number(source.getAttribute(attribute));

    return Number.isFinite(raw) ? raw : 0;
  }

  function setUp(root) {
    var form = root.querySelector('form') || root;
    var destination = root.querySelector('[data-edulume-eligibility-destination]');
    var grade = root.querySelector('[data-edulume-eligibility-grade]');
    var english = root.querySelector('[data-edulume-eligibility-english]');
    var verdict = root.querySelector('[data-edulume-eligibility-verdict]');
    var advice = root.querySelector('[data-edulume-eligibility-advice]');

    if (destination === null || verdict === null) {
      return;
    }

    function assess() {
      var option = destination.options[destination.selectedIndex];

      if (!option) {
        return;
      }

      var requiredGrade = number(option, 'data-minimum-grade');
      var requiredEnglish = number(option, 'data-minimum-english');
      var actualGrade = grade === null ? 0 : Number(grade.value) || 0;
      var actualEnglish = english === null ? 0 : Number(english.value) || 0;

      var gradeShortfall = requiredGrade - actualGrade;
      var englishShortfall = requiredEnglish - actualEnglish;
      var gaps = [];

      if (gradeShortfall > 0) {
        gaps.push(text('gradeGap', 'a higher grade average'));
      }

      if (englishShortfall > 0) {
        gaps.push(text('englishGap', 'a higher English score'));
      }

      /*
       * Three outcomes, not a percentage. A number invites somebody to treat a guess as a
       * decision; a word with a reason attached invites them to ask.
       */
      if (gaps.length === 0) {
        verdict.textContent = text('likely', 'Likely — your profile meets the published minimums.');
        verdict.setAttribute('data-verdict', 'likely');
      } else if (gradeShortfall <= 0.3 && englishShortfall <= 0.5) {
        verdict.textContent = text('borderline', 'Borderline — worth a conversation.');
        verdict.setAttribute('data-verdict', 'borderline');
      } else {
        verdict.textContent = text('unlikely', 'Unlikely as things stand.');
        verdict.setAttribute('data-verdict', 'unlikely');
      }

      if (advice !== null) {
        advice.textContent =
          gaps.length === 0
            ? text('noGaps', 'Bring your transcript and test report to your first session.')
            : text('gaps', 'What would change this: ') + gaps.join(text('and', ' and '));
      }
    }

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      assess();
    });

    root.addEventListener('change', assess);
    root.addEventListener('input', assess);

    assess();
  }

  function start() {
    Array.prototype.forEach.call(document.querySelectorAll('[data-edulume-eligibility]'), setUp);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
