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

  function start() {
    Array.prototype.forEach.call(document.querySelectorAll('[data-edulume-import]'), setUpImport);
    Array.prototype.forEach.call(
      document.querySelectorAll('[data-edulume-sortable]'),
      setUpSortable
    );
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
