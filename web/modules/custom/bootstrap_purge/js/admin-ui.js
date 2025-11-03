/**
 * @file
 * Admin UI enhancements for Bootstrap Purge.
 */

(function ($, Drupal) {
  'use strict';

  /**
   * Bootstrap Purge admin UI behaviors.
   */
  Drupal.behaviors.bootstrapPurgeAdminUI = {
    attach: function (context, settings) {
      // Confirmation dialogs
      $('[data-confirm]', context).once('bootstrap-purge-confirm').on('click', function (e) {
        var message = $(this).data('confirm');
        if (!confirm(message)) {
          e.preventDefault();
          return false;
        }
      });

      // Diff viewer enhancements
      $('.bootstrap-purge-diff', context).once('bootstrap-purge-diff').each(function () {
        var $container = $(this);
        
        // Add line numbers
        $container.find('pre').each(function () {
          var $pre = $(this);
          var lines = $pre.text().split('\n');
          var numberedLines = lines.map(function (line, index) {
            return '<span class="line-number">' + (index + 1) + '</span>' + line;
          });
          $pre.html(numberedLines.join('\n'));
        });
      });

      // Asset table enhancements
      $('.bootstrap-purge-assets-table', context).once('bootstrap-purge-table').each(function () {
        var $table = $(this);
        
        // Add sorting
        $table.find('th').on('click', function () {
          var $th = $(this);
          var column = $th.index();
          var $tbody = $table.find('tbody');
          var rows = $tbody.find('tr').toArray();
          
          var isAscending = $th.hasClass('sort-asc');
          
          // Remove existing sort classes
          $table.find('th').removeClass('sort-asc sort-desc');
          
          // Add new sort class
          $th.addClass(isAscending ? 'sort-desc' : 'sort-asc');
          
          // Sort rows
          rows.sort(function (a, b) {
            var aText = $(a).find('td').eq(column).text();
            var bText = $(b).find('td').eq(column).text();
            
            // Try to parse as numbers for size columns
            if (aText.match(/\d+(\.\d+)?\s*(B|KB|MB|GB)/)) {
              aText = parseSize(aText);
              bText = parseSize(bText);
            }
            
            if (aText < bText) return isAscending ? 1 : -1;
            if (aText > bText) return isAscending ? -1 : 1;
            return 0;
          });
          
          // Reorder table
          $tbody.empty().append(rows);
        });
      });

      // Progress indicators for long operations
      $('.bootstrap-purge-action-button', context).once('bootstrap-purge-progress').on('click', function () {
        var $button = $(this);
        var originalText = $button.text();
        
        $button.prop('disabled', true).text('Processing...');
        
        // Re-enable after a delay (in case of redirect)
        setTimeout(function () {
          $button.prop('disabled', false).text(originalText);
        }, 5000);
      });

      // Auto-refresh for dashboard stats
      if ($('.bootstrap-purge-dashboard', context).length) {
        setInterval(function () {
          // Only refresh if page is visible
          if (!document.hidden) {
            $('.bootstrap-purge-stats').load(window.location.href + ' .bootstrap-purge-stats > *');
          }
        }, 30000); // Every 30 seconds
      }
    }
  };

  /**
   * Parse size string to bytes for sorting.
   */
  function parseSize(sizeStr) {
    var match = sizeStr.match(/(\d+(?:\.\d+)?)\s*(B|KB|MB|GB)/);
    if (!match) return 0;
    
    var size = parseFloat(match[1]);
    var unit = match[2];
    
    switch (unit) {
      case 'GB': return size * 1024 * 1024 * 1024;
      case 'MB': return size * 1024 * 1024;
      case 'KB': return size * 1024;
      default: return size;
    }
  }

  /**
   * Bootstrap Purge diff viewer.
   */
  Drupal.behaviors.bootstrapPurgeDiffViewer = {
    attach: function (context, settings) {
      $('.bootstrap-purge-diff-viewer', context).once('diff-viewer').each(function () {
        var $viewer = $(this);
        var $original = $viewer.find('.original-content');
        var $purged = $viewer.find('.purged-content');
        
        // Add toggle between side-by-side and unified view
        var $toggle = $('<button type="button" class="diff-view-toggle">Toggle View</button>');
        $viewer.prepend($toggle);
        
        $toggle.on('click', function () {
          $viewer.toggleClass('unified-view');
        });
        
        // Highlight differences
        if (typeof diff_match_patch !== 'undefined') {
          var dmp = new diff_match_patch();
          var originalText = $original.text();
          var purgedText = $purged.text();
          var diffs = dmp.diff_main(originalText, purgedText);
          
          dmp.diff_cleanupSemantic(diffs);
          
          var originalHtml = '';
          var purgedHtml = '';
          
          diffs.forEach(function (diff) {
            var operation = diff[0];
            var text = diff[1];
            
            switch (operation) {
              case 0: // Equal
                originalHtml += text;
                purgedHtml += text;
                break;
              case -1: // Deletion
                originalHtml += '<span class="diff-removed">' + text + '</span>';
                break;
              case 1: // Insertion
                purgedHtml += '<span class="diff-added">' + text + '</span>';
                break;
            }
          });
          
          $original.html('<pre>' + originalHtml + '</pre>');
          $purged.html('<pre>' + purgedHtml + '</pre>');
        }
      });
    }
  };

})(jQuery, Drupal);