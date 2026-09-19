/**
 * Eskoofy Admin JS.
 *
 * @package Eskoofy
 */

/* global jQuery, eskAdmin */
(function ($) {
  'use strict';

  // Live search students
  var searchTimer;
  $(document).on('keyup', '#esk-student-search', function () {
    var query = $(this).val();
    var $results = $('#esk-student-results');
    clearTimeout(searchTimer);
    if (query.length < 2) {
      $results.empty();
      return;
    }
    searchTimer = setTimeout(function () {
      $.get(eskAdmin.ajaxUrl, {
        action: 'esk_search_students',
        nonce: eskAdmin.nonce,
        q: query
      }, function (res) {
        if (res.success && res.data.students.length) {
          var html = '<ul class="esk-live-results">';
          res.data.students.forEach(function (s) {
            html += '<li><a href="#">' + (s.display_name || '') +
              ' — ' + s.admission_number + '</a></li>';
          });
          html += '</ul>';
          $results.html(html);
        } else {
          $results.html('<p>No students found.</p>');
        }
      });
    }, 300);
  });

  // Load students by class (attendance mark page)
  $(document).on('change', '#esk-class-select', function () {
    var classId = $(this).val();
    if (!classId) return;
    $.get(eskAdmin.ajaxUrl, {
      action: 'esk_get_students_by_class',
      nonce: eskAdmin.nonce,
      class_id: classId
    }, function (res) {
      if (res.success) {
        // Populate student checkboxes if needed by admin.js consumer
        $(document).trigger('esk:students-loaded', res.data.students);
      }
    });
  });

  // Confirm actions
  $(document).on('click', '.esk-confirm', function (e) {
    if (!confirm($(this).data('confirm') || 'Are you sure?')) {
      e.preventDefault();
    }
  });

  // Modal open/close
  $(document).on('click', '.esk-modal-trigger', function (e) {
    e.preventDefault();
    var target = $(this).data('modal');
    $(target).addClass('esk-modal-active');
  });

  $(document).on('click', '.esk-modal-close', function () {
    $(this).closest('.esk-modal').removeClass('esk-modal-active');
  });

  // App-style confirm modal: replaces native `confirm()` used by the
  // views' inline `onclick="return confirm('...')"` handlers.
  (function () {
    var modalRoot = document.getElementById('esk-confirm-modal');
    if (!modalRoot) { return; }
    var confirmBtn = document.getElementById('esk-confirm-ok');
    var cancelBtn = document.getElementById('esk-confirm-cancel');
    var msgEl = document.getElementById('esk-confirm-message');
    var pending = null;

    function closeModal() {
      modalRoot.classList.remove('is-open');
      pending = null;
    }

    document.addEventListener('click', function (e) {
      var target = e.target;
      if (!(target instanceof Element)) { return; }
      var el = target.closest('a, button, input[type="submit"], input[type="button"]');
      if (!el) { return; }
      var oc = el.getAttribute && (el.getAttribute('onclick') || '');
      if (oc.indexOf('confirm(') === -1) { return; }

      e.preventDefault();
      e.stopPropagation();

      var m = oc.match(/confirm\(\s*['"]([^'"]*)['"]\s*\)/);
      msgEl.textContent = m && m[1] ? m[1] : 'Are you sure?';
      modalRoot.classList.add('is-open');
      pending = el;
    }, true);

    document.addEventListener('submit', function (e) {
      var form = e.target;
      if (!(form instanceof HTMLFormElement)) { return; }
      var os = form.getAttribute && (form.getAttribute('onsubmit') || '');
      if (os.indexOf('confirm(') === -1) { return; }

      e.preventDefault();
      e.stopPropagation();

      var m = os.match(/confirm\(\s*['"]([^'"]*)['"]\s*\)/);
      msgEl.textContent = m && m[1] ? m[1] : 'Are you sure?';
      modalRoot.classList.add('is-open');
      pending = form;
    }, true);

    if (confirmBtn) {
      confirmBtn.addEventListener('click', function () {
        var action = pending;
        closeModal();
        if (action instanceof HTMLFormElement) {
          action.submit();
          return;
        }
        if (!action) { return; }
        var tag = action.tagName.toLowerCase();
        if (tag === 'a') {
          var href = action.getAttribute('href');
          if (href) { window.location.href = href; }
        } else {
          var form = action.form;
          if (form) { form.submit(); }
        }
      });
    }
    if (cancelBtn) { cancelBtn.addEventListener('click', closeModal); }
    modalRoot.addEventListener('click', function (e) {
      if (e.target === modalRoot) { closeModal(); }
    });
  })();

})(jQuery);
