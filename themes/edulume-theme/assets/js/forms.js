/*
 * Enquiry forms.
 *
 * The form posts on its own. This adds three things and degrades to that plain post if any of
 * them fail: inline validation before a round trip, conditional fields that appear only when
 * they apply, and submission without a page reload.
 *
 * Client-side validation is a courtesy, never a control. Everything checked here is checked
 * again on the server, because anything a browser enforces is something a request can skip.
 */

(function forms() {
  var strings = (window.edulumeStrings || {}).forms || {};

  function text(key, fallback) {
    return typeof strings[key] === 'string' && strings[key] !== '' ? strings[key] : fallback;
  }

  function fieldsOf(form) {
    return Array.prototype.slice.call(form.querySelectorAll('[name]')).filter(function (field) {
      return field.type !== 'hidden' || field.hasAttribute('data-edulume-validate');
    });
  }

  function errorFor(field) {
    var id = field.getAttribute('name');

    return field.form.querySelector('[data-edulume-error="' + id + '"]');
  }

  function showError(field, message) {
    var target = errorFor(field);

    field.setAttribute('aria-invalid', 'true');

    if (target !== null) {
      target.textContent = message;
    }
  }

  function clearError(field) {
    var target = errorFor(field);

    field.removeAttribute('aria-invalid');

    if (target !== null) {
      target.textContent = '';
    }
  }

  function validate(field) {
    var value = (field.value || '').trim();

    if (field.required && value === '') {
      showError(field, text('required', 'This field is required.'));

      return false;
    }

    if (field.type === 'email' && value !== '' && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(value)) {
      showError(field, text('email', 'Enter a valid email address.'));

      return false;
    }

    if (field.type === 'checkbox' && field.required && !field.checked) {
      showError(field, text('consent', 'Please tick this to continue.'));

      return false;
    }

    clearError(field);

    return true;
  }

  /*
   * Conditional fields.
   *
   * A field declares what it depends on; nothing declares what depends on it. That direction
   * matters — a form builder can add a field without having to find and edit whatever it
   * follows.
   */
  function applyConditions(form) {
    Array.prototype.forEach.call(form.querySelectorAll('[data-edulume-when]'), function (wrapper) {
      var name = wrapper.getAttribute('data-edulume-when');
      var expected = wrapper.getAttribute('data-edulume-equals');
      var source = form.querySelector('[name="' + name + '"]');

      if (source === null) {
        return;
      }

      var actual = source.type === 'checkbox' ? String(source.checked) : source.value || '';
      var matches = expected === null || expected === actual;

      wrapper.hidden = !matches;

      // A hidden required field would block submission with an error nobody can see.
      Array.prototype.forEach.call(wrapper.querySelectorAll('[name]'), function (field) {
        field.disabled = !matches;
      });
    });
  }

  function setUp(form) {
    var status = form.querySelector('[data-edulume-form-status]');
    var submitting = false;

    applyConditions(form);

    form.addEventListener('change', function () {
      applyConditions(form);
    });

    form.addEventListener(
      'blur',
      function (event) {
        if (event.target.hasAttribute('name')) {
          validate(event.target);
        }
      },
      true
    );

    form.addEventListener('submit', function (event) {
      var fields = fieldsOf(form).filter(function (field) {
        return !field.disabled;
      });
      var invalid = fields.filter(function (field) {
        return !validate(field);
      });

      if (invalid.length > 0) {
        event.preventDefault();
        // Focus the first problem. A summary at the top that nobody scrolls back to is the
        // most common way an accessible error message goes unread.
        invalid[0].focus();

        return;
      }

      var endpoint = form.getAttribute('data-edulume-endpoint');

      if (endpoint === null || typeof window.fetch !== 'function' || submitting) {
        return;
      }

      event.preventDefault();
      submitting = true;

      if (status !== null) {
        status.textContent = text('sending', 'Sending…');
      }

      window
        .fetch(endpoint, {
          method: 'POST',
          body: new FormData(form),
          headers: { 'X-WP-Nonce': form.getAttribute('data-edulume-nonce') || '' },
        })
        .then(function (response) {
          return response.ok ? response.json() : Promise.reject(new Error(String(response.status)));
        })
        .then(function () {
          form.reset();
          applyConditions(form);

          if (status !== null) {
            status.textContent = text('sent', 'Thank you. We will be in touch shortly.');
          }
        })
        .catch(function () {
          // Back to the plain post rather than stranding the person with a failed request.
          submitting = false;

          if (status !== null) {
            status.textContent = '';
          }

          form.submit();
        });
    });
  }

  function start() {
    Array.prototype.forEach.call(document.querySelectorAll('[data-edulume-form]'), setUp);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
