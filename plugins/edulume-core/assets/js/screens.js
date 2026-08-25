/*
 * The two Edulume admin screens that render server-side.
 *
 * Both are enhancements over markup that already works without them: the import form posts and
 * imports, the section list has a number box beside every row. Nothing here is required for the
 * screen to function, which is deliberate — these are the first two screens a new owner opens,
 * often on a connection that drops a script.
 */

(function screens() {
  var config = window.edulumeScreens || {};
  var strings = config.strings || {};

  function text(key, fallback) {
    return typeof strings[key] === 'string' ? strings[key] : fallback;
  }

  /*
   * The demo import, ten items at a time.
   *
   * Driven from the browser rather than by a loop on the server: shared hosting kills the
   * request at thirty seconds, and a thousand-item pack does not finish in thirty seconds. Each
   * round trip is its own short request, so no single one can be killed mid-write.
   */
  function setUpImport(form) {
    var slug = form.getAttribute('data-edulume-import');

    if (!slug || typeof window.fetch !== 'function') {
      return;
    }

    var button = form.querySelector('button[type="submit"]');
    var status = form.querySelector('[data-edulume-import-status]');
    var bar = form.querySelector('[data-edulume-import-bar]');
    var running = false;

    function report(created, total) {
      var percent = total > 0 ? Math.min(100, Math.round((created / total) * 100)) : 0;

      if (bar) {
        bar.style.inlineSize = percent + '%';
        bar.parentNode.setAttribute('aria-valuenow', String(percent));
      }

      if (status) {
        status.textContent = text('importing', 'Importing…') + ' ' + created + ' / ' + total;
      }
    }

    function batch(restart) {
      var body = new window.FormData();

      body.append('action', config.importAction);
      body.append('_ajax_nonce', config.importNonce);
      body.append('demo', slug);
      body.append('restart', restart ? '1' : '0');

      return window
        .fetch(config.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body })
        .then(function (response) {
          return response.json();
        })
        .then(function (payload) {
          if (!payload || !payload.success) {
            throw new Error('batch failed');
          }

          report(payload.data.created, payload.data.total);

          if (!payload.data.complete) {
            return batch(false);
          }

          if (status) {
            status.textContent = text('imported', 'Done. Reloading…');
          }

          window.location.reload();

          return null;
        });
    }

    form.addEventListener('submit', function (event) {
      if (running) {
        event.preventDefault();

        return;
      }

      event.preventDefault();
      running = true;

      if (button) {
        button.disabled = true;
      }

      batch(true)['catch'](function () {
        running = false;

        if (button) {
          button.disabled = false;
        }

        if (status) {
          status.textContent = text('importFailed', 'That did not finish. Try again.');
        }
      });
    });
  }

  /*
   * Dragging a section row.
   *
   * The number inputs are the state; dragging rewrites them. Doing it the other way round —
   * DOM order as state — means a keyboard user who types a number sees nothing move and a
   * mouse user who drags submits stale numbers.
   */
  function setUpSortable(list) {
    var dragged = null;

    function renumber() {
      Array.prototype.forEach.call(
        list.querySelectorAll('[data-edulume-position]'),
        function (input, index) {
          input.value = String(index + 1);
        }
      );
    }

    Array.prototype.forEach.call(
      list.querySelectorAll('[data-edulume-section-row]'),
      function (row) {
        var handle = row.querySelector('[data-edulume-drag-handle]');

        if (!handle) {
          return;
        }

        handle.setAttribute('draggable', 'true');

        handle.addEventListener('dragstart', function (event) {
          dragged = row;
          event.dataTransfer.effectAllowed = 'move';
          // Firefox refuses to start a drag unless something is written to the transfer.
          event.dataTransfer.setData('text/plain', '');
        });

        row.addEventListener('dragover', function (event) {
          if (!dragged || dragged === row) {
            return;
          }

          event.preventDefault();

          var box = row.getBoundingClientRect();
          var isBelow = event.clientY > box.top + box.height / 2;

          row.parentNode.insertBefore(dragged, isBelow ? row.nextSibling : row);
        });

        row.addEventListener('drop', function (event) {
          event.preventDefault();
          dragged = null;
          renumber();
        });
      }
    );

    list.addEventListener('dragend', function () {
      dragged = null;
      renumber();
    });
  }

  /*
   * The confirmation gate on the login shield.
   *
   * Only ever *adds* a condition: the form is submittable without this script, because a
   * settings page that cannot be saved when a script fails is worse than one that can be saved
   * carelessly. The acknowledgement is only demanded when the shield is actually being switched
   * on — nobody should have to re-read a URL to change how many failed attempts are allowed.
   */
  function setUpShield(form) {
    var enable = form.querySelector('input[name="enabled"]');
    var confirm = form.querySelector('[data-edulume-shield-confirm]');
    var slug = form.querySelector('[data-edulume-shield-slug]');
    var preview = form.querySelector('.edulume-shield__confirm code');
    var wasEnabled = enable ? enable.checked : false;
    var originalSlug = slug ? slug.value : '';

    if (!enable || !confirm) {
      return;
    }

    if (slug && preview) {
      slug.addEventListener('input', function () {
        preview.textContent = preview.textContent.replace(/[^/]*\/?$/, slug.value + '/');
      });
    }

    form.addEventListener('submit', function (event) {
      var turningOn = enable.checked && !wasEnabled;
      var moving = enable.checked && slug && slug.value !== originalSlug;

      if ((turningOn || moving) && !confirm.checked) {
        event.preventDefault();
        confirm.focus();
        window.alert(text('confirmShield', 'Please confirm you have saved your sign-in address.'));
      }
    });
  }

  /*
   * The video repeater.
   *
   * Rows are cloned from the last one and renumbered, rather than built from a template string:
   * the markup is rendered by PHP with the translations and the source list already in it, and a
   * second copy of that markup in JavaScript is a second copy to keep in step.
   *
   * Without this script the page still saves whatever rows the server rendered, which is why
   * there is always at least one — an owner with no JavaScript can still add their first video.
   */
  function setUpVideoRows(list) {
    function renumber() {
      Array.prototype.forEach.call(
        list.querySelectorAll('[data-edulume-video-row]'),
        function (row, index) {
          Array.prototype.forEach.call(row.querySelectorAll('[name]'), function (field) {
            field.setAttribute(
              'name',
              field.getAttribute('name').replace(/items\[\d+\]/, 'items[' + index + ']')
            );
          });
        }
      );
    }

    function bindRemove(row) {
      var remove = row.querySelector('[data-edulume-video-remove]');

      if (!remove) {
        return;
      }

      remove.addEventListener('click', function () {
        // Never below one row: an empty list has nothing to clone the next row from.
        if (list.querySelectorAll('[data-edulume-video-row]').length < 2) {
          Array.prototype.forEach.call(row.querySelectorAll('input'), function (field) {
            field.value = '';
          });

          return;
        }

        row.parentNode.removeChild(row);
        renumber();
      });
    }

    Array.prototype.forEach.call(list.querySelectorAll('[data-edulume-video-row]'), bindRemove);

    var add = document.querySelector('[data-edulume-video-add]');

    if (!add) {
      return;
    }

    add.addEventListener('click', function () {
      var rows = list.querySelectorAll('[data-edulume-video-row]');
      var clone = rows[rows.length - 1].cloneNode(true);

      Array.prototype.forEach.call(clone.querySelectorAll('input'), function (field) {
        field.value = field.getAttribute('type') === 'number' ? '0' : '';
      });

      list.appendChild(clone);
      bindRemove(clone);
      renumber();

      var first = clone.querySelector('input');

      if (first) {
        first.focus();
      }
    });
  }

  /*
   * A range with no read-out is a slider whose value you can only discover by saving. The
   * number is rendered server-side too, so this only keeps it in step while dragging.
   */
  function setUpRange(input) {
    var output = document.querySelector('output[for="' + input.id + '"]');

    if (!output) {
      return;
    }

    input.addEventListener('input', function () {
      output.textContent = input.value;
    });
  }

  /*
   * The picker writes into the text box rather than submitting on its own. The text box is what
   * posts, so a typed hex still works with this file blocked and there is one source of truth.
   */
  function setUpColour(picker) {
    var target = document.getElementById(picker.getAttribute('data-edulume-color-for') || '');

    if (!target) {
      return;
    }

    picker.addEventListener('input', function () {
      target.value = picker.value;
    });

    target.addEventListener('change', function () {
      if (/^#?[0-9a-f]{6}$/i.test(target.value)) {
        picker.value = target.value.charAt(0) === '#' ? target.value : '#' + target.value;
      }
    });
  }

  function setUpCopyReport(button) {
    var report = document.querySelector('[data-edulume-report]');

    if (!report) {
      return;
    }

    button.addEventListener('click', function () {
      report.select();

      // `execCommand` as the fallback: the clipboard API needs a secure context, and plenty of
      // these sites are still served over plain HTTP when somebody first opens this screen.
      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(report.value);
      } else {
        document.execCommand('copy');
      }
    });
  }

  function start() {
    Array.prototype.forEach.call(document.querySelectorAll('[data-edulume-range]'), setUpRange);
    Array.prototype.forEach.call(
      document.querySelectorAll('[data-edulume-color-for]'),
      setUpColour
    );
    Array.prototype.forEach.call(
      document.querySelectorAll('[data-edulume-copy-report]'),
      setUpCopyReport
    );
    Array.prototype.forEach.call(document.querySelectorAll('[data-edulume-import]'), setUpImport);
    Array.prototype.forEach.call(
      document.querySelectorAll('[data-edulume-video-rows]'),
      setUpVideoRows
    );
    Array.prototype.forEach.call(
      document.querySelectorAll('[data-edulume-sortable]'),
      setUpSortable
    );
    Array.prototype.forEach.call(document.querySelectorAll('[data-edulume-shield]'), setUpShield);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
