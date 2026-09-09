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
      nonce: eskAdmin.nce,
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

})(jQuery);
