/**
 * @file
 * Unified Extensions Analysis JavaScript.
 */

(function ($, Drupal, once) {
  'use strict';

  /**
   * Unified Extensions behaviors.
   */
  Drupal.behaviors.siteAnalyzerUnifiedExtensions = {
    attach: function (context, settings) {
      once('unified-extensions', '.site-analyzer-unified-extensions', context).forEach(function (element) {
        Drupal.siteAnalyzer.initUnifiedExtensions(element, settings);
      });
    }
  };

  /**
   * Site Analyzer namespace.
   */
  Drupal.siteAnalyzer = Drupal.siteAnalyzer || {};

  /**
   * Initialize unified extensions functionality.
   */
  Drupal.siteAnalyzer.initUnifiedExtensions = function (container, settings) {
    var $container = $(container);
    var unifiedData = settings.siteAnalyzer?.unifiedData || [];

    // Initialize components
    Drupal.siteAnalyzer.initSearch($container);
    Drupal.siteAnalyzer.initFilters($container);
    Drupal.siteAnalyzer.initSorting($container);
    Drupal.siteAnalyzer.initBulkActions($container);
    Drupal.siteAnalyzer.initRowActions($container);
    Drupal.siteAnalyzer.initModals($container);
    Drupal.siteAnalyzer.initDropdowns($container);

    // Store data for filtering and sorting
    $container.data('unified-data', unifiedData);
  };

  /**
   * Initialize search functionality.
   */
  Drupal.siteAnalyzer.initSearch = function ($container) {
    var $searchInput = $container.find('#extension-search');
    var searchTimeout;

    $searchInput.on('input', function () {
      clearTimeout(searchTimeout);
      var searchTerm = $(this).val().toLowerCase();

      searchTimeout = setTimeout(function () {
        Drupal.siteAnalyzer.filterTable($container, {
          search: searchTerm
        });
      }, 300);
    });
  };

  /**
   * Initialize filter functionality.
   */
  Drupal.siteAnalyzer.initFilters = function ($container) {
    var $filters = $container.find('.filter-select');

    $filters.on('change', function () {
      Drupal.siteAnalyzer.applyAllFilters($container);
    });

    // Clear filters button
    $container.find('.clear-filters-btn').on('click', function () {
      $filters.val('all');
      $container.find('#extension-search').val('');
      Drupal.siteAnalyzer.applyAllFilters($container);
    });
  };

  /**
   * Apply all active filters.
   */
  Drupal.siteAnalyzer.applyAllFilters = function ($container) {
    var filters = {
      search: $container.find('#extension-search').val().toLowerCase(),
      type: $container.find('#type-filter').val(),
      status: $container.find('#status-filter').val(),
      category: $container.find('#category-filter').val(),
      readiness: $container.find('#readiness-filter').val()
    };

    Drupal.siteAnalyzer.filterTable($container, filters);
  };

  /**
   * Filter table rows based on criteria.
   */
  Drupal.siteAnalyzer.filterTable = function ($container, filters) {
    var $rows = $container.find('.extension-row');
    var visibleCount = 0;

    $rows.each(function () {
      var $row = $(this);
      var show = true;

      // Search filter
      if (filters.search && filters.search.length > 0) {
        var text = $row.text().toLowerCase();
        if (text.indexOf(filters.search) === -1) {
          show = false;
        }
      }

      // Type filter
      if (filters.type && filters.type !== 'all') {
        if ($row.data('type') !== filters.type) {
          show = false;
        }
      }

      // Status filter
      if (filters.status && filters.status !== 'all') {
        if ($row.data('status') !== filters.status) {
          show = false;
        }
      }

      // Category filter
      if (filters.category && filters.category !== 'all') {
        if ($row.data('category') !== filters.category) {
          show = false;
        }
      }

      // Readiness filter
      if (filters.readiness && filters.readiness !== 'all') {
        if ($row.data('readiness') !== filters.readiness) {
          show = false;
        }
      }

      if (show) {
        $row.show();
        visibleCount++;
      } else {
        $row.hide();
      }
    });

    // Update visible count
    Drupal.siteAnalyzer.updateVisibleCount($container, visibleCount);
  };

  /**
   * Update visible count display.
   */
  Drupal.siteAnalyzer.updateVisibleCount = function ($container, count) {
    var $table = $container.find('.extensions-table');
    var $existingCount = $table.find('.visible-count');

    if ($existingCount.length === 0) {
      $existingCount = $('<div class="visible-count"></div>');
      $table.before($existingCount);
    }

    var totalCount = $container.find('.extension-row').length;
    $existingCount.text('Showing ' + count + ' of ' + totalCount + ' extensions');
  };

  /**
   * Initialize table sorting.
   */
  Drupal.siteAnalyzer.initSorting = function ($container) {
    var $sortableHeaders = $container.find('th.sortable');

    $sortableHeaders.on('click', function () {
      var $header = $(this);
      var sortField = $header.data('sort');
      var currentSort = $header.hasClass('sorted-asc') ? 'asc' : 
                       $header.hasClass('sorted-desc') ? 'desc' : 'none';
      var newSort = currentSort === 'asc' ? 'desc' : 'asc';

      // Clear all sort indicators
      $sortableHeaders.removeClass('sorted-asc sorted-desc');

      // Set new sort
      $header.addClass('sorted-' + newSort);

      // Sort the table
      Drupal.siteAnalyzer.sortTable($container, sortField, newSort);
    });
  };

  /**
   * Sort table by field and direction.
   */
  Drupal.siteAnalyzer.sortTable = function ($container, field, direction) {
    var $tbody = $container.find('.extensions-table tbody');
    var $rows = $tbody.find('.extension-row').toArray();

    $rows.sort(function (a, b) {
      var aVal = Drupal.siteAnalyzer.getSortValue($(a), field);
      var bVal = Drupal.siteAnalyzer.getSortValue($(b), field);

      if (typeof aVal === 'number' && typeof bVal === 'number') {
        return direction === 'asc' ? aVal - bVal : bVal - aVal;
      } else {
        aVal = String(aVal).toLowerCase();
        bVal = String(bVal).toLowerCase();
        if (direction === 'asc') {
          return aVal.localeCompare(bVal);
        } else {
          return bVal.localeCompare(aVal);
        }
      }
    });

    $tbody.empty().append($rows);
  };

  /**
   * Get sort value from row.
   */
  Drupal.siteAnalyzer.getSortValue = function ($row, field) {
    switch (field) {
      case 'display_name':
        return $row.find('.extension-name strong').text();
      case 'type':
        return $row.data('type');
      case 'status':
        return $row.data('status');
      case 'version':
        return $row.find('.version-text').text();
      case 'readiness_score':
        var scoreText = $row.find('.score-circle').text();
        return parseInt(scoreText.replace('%', ''), 10) || 0;
      case 'issues_count':
        var issuesText = $row.find('.issues-count').text();
        return parseInt(issuesText, 10) || 0;
      default:
        return '';
    }
  };

  /**
   * Initialize bulk actions.
   */
  Drupal.siteAnalyzer.initBulkActions = function ($container) {
    var $selectAll = $container.find('#select-all');
    var $rowCheckboxes = $container.find('.row-checkbox');
    var $bulkBar = $container.find('.bulk-actions-bar');

    // Select all functionality
    $selectAll.on('change', function () {
      var isChecked = $(this).is(':checked');
      $rowCheckboxes.prop('checked', isChecked);
      Drupal.siteAnalyzer.updateBulkActions($container);
    });

    // Individual checkbox functionality
    $rowCheckboxes.on('change', function () {
      var totalCheckboxes = $rowCheckboxes.length;
      var checkedCheckboxes = $rowCheckboxes.filter(':checked').length;

      $selectAll.prop('checked', checkedCheckboxes === totalCheckboxes);
      $selectAll.prop('indeterminate', checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes);

      Drupal.siteAnalyzer.updateBulkActions($container);
    });

    // Bulk action buttons
    $container.find('.bulk-scan-btn').on('click', function () {
      var selectedItems = Drupal.siteAnalyzer.getSelectedItems($container);
      Drupal.siteAnalyzer.bulkScan($container, selectedItems);
    });

    $container.find('.bulk-export-btn').on('click', function () {
      var selectedItems = Drupal.siteAnalyzer.getSelectedItems($container);
      Drupal.siteAnalyzer.bulkExport($container, selectedItems);
    });

    $container.find('.bulk-clear-btn').on('click', function () {
      $rowCheckboxes.prop('checked', false);
      $selectAll.prop('checked', false);
      Drupal.siteAnalyzer.updateBulkActions($container);
    });
  };

  /**
   * Update bulk actions bar visibility and count.
   */
  Drupal.siteAnalyzer.updateBulkActions = function ($container) {
    var selectedCount = $container.find('.row-checkbox:checked').length;
    var $bulkBar = $container.find('.bulk-actions-bar');
    var $selectedCount = $bulkBar.find('.selected-count');

    if (selectedCount > 0) {
      $bulkBar.show();
      $selectedCount.text(selectedCount);
    } else {
      $bulkBar.hide();
    }
  };

  /**
   * Get selected items data.
   */
  Drupal.siteAnalyzer.getSelectedItems = function ($container) {
    var selectedItems = [];
    $container.find('.row-checkbox:checked').each(function () {
      var $row = $(this).closest('.extension-row');
      selectedItems.push({
        name: $(this).val(),
        type: $row.data('type'),
        display_name: $row.find('.extension-name strong').text()
      });
    });
    return selectedItems;
  };

  /**
   * Initialize row actions.
   */
  Drupal.siteAnalyzer.initRowActions = function ($container) {
    // Scan button
    $container.on('click', '.scan-btn', function (e) {
      e.preventDefault();
      var $btn = $(this);
      var name = $btn.data('name');
      var type = $btn.data('type');

      Drupal.siteAnalyzer.scanExtension($container, name, type, $btn);
    });

    // Details button
    $container.on('click', '.details-btn', function (e) {
      e.preventDefault();
      var $btn = $(this);
      var name = $btn.data('name');
      var type = $btn.data('type');

      Drupal.siteAnalyzer.showExtensionDetails($container, name, type);
    });

    // Scan all button
    $container.find('.scan-all-btn').on('click', function (e) {
      e.preventDefault();
      var scanType = $(this).data('scan-type') || 'all';
      Drupal.siteAnalyzer.scanAllExtensions($container, scanType);
    });

    // Export buttons
    $container.on('click', '.export-btn', function (e) {
      e.preventDefault();
      var format = $(this).data('format');
      Drupal.siteAnalyzer.exportExtensions($container, format);
    });
  };

  /**
   * Scan individual extension.
   */
  Drupal.siteAnalyzer.scanExtension = function ($container, name, type, $btn) {
    var originalText = $btn.text();
    $btn.prop('disabled', true).html('<span class="spinner"></span> Scanning...');

    $.ajax({
      url: '/admin/reports/site-analyzer/scan/' + encodeURIComponent(name),
      type: 'POST',
      data: { type: type },
      dataType: 'json',
      success: function (data) {
        if (data.success) {
          Drupal.siteAnalyzer.showScanResults($container, name, data.results);
          Drupal.siteAnalyzer.showNotification('Scan completed successfully', 'success');
        } else {
          Drupal.siteAnalyzer.showNotification('Scan failed: ' + (data.error || 'Unknown error'), 'error');
        }
      },
      error: function (xhr, status, error) {
        Drupal.siteAnalyzer.showNotification('Scan failed: ' + error, 'error');
      },
      complete: function () {
        $btn.prop('disabled', false).text(originalText);
      }
    });
  };

  /**
   * Scan all extensions.
   */
  Drupal.siteAnalyzer.scanAllExtensions = function ($container, scanType) {
    var $btn = $container.find('.scan-all-btn');
    var originalText = $btn.text();

    $btn.prop('disabled', true).html('<span class="spinner"></span> Scanning...');
    Drupal.siteAnalyzer.showLoadingOverlay('Scanning all extensions...');

    $.ajax({
      url: '/admin/reports/site-analyzer/scan-all',
      type: 'POST',
      data: { scan_type: scanType },
      dataType: 'json',
      success: function (data) {
        if (data.success) {
          Drupal.siteAnalyzer.showBulkScanResults($container, data);
          Drupal.siteAnalyzer.showNotification('Bulk scan completed successfully', 'success');
        } else {
          Drupal.siteAnalyzer.showNotification('Bulk scan failed: ' + (data.error || 'Unknown error'), 'error');
        }
      },
      error: function (xhr, status, error) {
        Drupal.siteAnalyzer.showNotification('Bulk scan failed: ' + error, 'error');
      },
      complete: function () {
        $btn.prop('disabled', false).text(originalText);
        Drupal.siteAnalyzer.hideLoadingOverlay();
      }
    });
  };

  /**
   * Bulk scan selected items.
   */
  Drupal.siteAnalyzer.bulkScan = function ($container, selectedItems) {
    if (selectedItems.length === 0) {
      Drupal.siteAnalyzer.showNotification('No items selected', 'warning');
      return;
    }

    Drupal.siteAnalyzer.showLoadingOverlay('Scanning ' + selectedItems.length + ' extensions...');

    var promises = selectedItems.map(function (item) {
      return $.ajax({
        url: '/admin/reports/site-analyzer/scan/' + encodeURIComponent(item.name),
        type: 'POST',
        data: { type: item.type },
        dataType: 'json'
      });
    });

    Promise.allSettled(promises).then(function (results) {
      var successful = results.filter(function (result) {
        return result.status === 'fulfilled' && result.value.success;
      }).length;

      var message = successful + ' of ' + selectedItems.length + ' extensions scanned successfully';
      Drupal.siteAnalyzer.showNotification(message, successful === selectedItems.length ? 'success' : 'warning');
      Drupal.siteAnalyzer.hideLoadingOverlay();
    });
  };

  /**
   * Bulk export selected items.
   */
  Drupal.siteAnalyzer.bulkExport = function ($container, selectedItems) {
    if (selectedItems.length === 0) {
      Drupal.siteAnalyzer.showNotification('No items selected', 'warning');
      return;
    }

    var itemNames = selectedItems.map(function (item) { return item.name; });
    var exportUrl = '/admin/reports/site-analyzer/export/json?items=' + encodeURIComponent(itemNames.join(','));

    // Create hidden form to trigger download
    var $form = $('<form>', {
      'method': 'GET',
      'action': exportUrl
    }).appendTo('body');

    $form.submit();
    $form.remove();

    Drupal.siteAnalyzer.showNotification('Export started for ' + selectedItems.length + ' items', 'info');
  };

  /**
   * Export all extensions.
   */
  Drupal.siteAnalyzer.exportExtensions = function ($container, format) {
    var exportUrl = '/admin/reports/site-analyzer/export/' + format + '?section=extensions';

    // Create hidden form to trigger download
    var $form = $('<form>', {
      'method': 'GET',
      'action': exportUrl
    }).appendTo('body');

    $form.submit();
    $form.remove();

    Drupal.siteAnalyzer.showNotification('Export started', 'info');
  };

  /**
   * Show extension details.
   */
  Drupal.siteAnalyzer.showExtensionDetails = function ($container, name, type) {
    var $modal = $container.find('#results-modal');
    var $modalTitle = $modal.find('.modal-title');
    var $modalBody = $modal.find('#modal-results');

    $modalTitle.text('Extension Details: ' + name);
    $modalBody.html('<div class="loading-content"><div class="spinner"></div><div class="loading-text">Loading details...</div></div>');

    $modal.show();

    // Load extension details
    $.ajax({
      url: '/admin/reports/site-analyzer/details/' + encodeURIComponent(name),
      type: 'GET',
      data: { type: type },
      dataType: 'json',
      success: function (data) {
        if (data.success) {
          Drupal.siteAnalyzer.renderExtensionDetails($modalBody, data.details);
        } else {
          $modalBody.html('<div class="error">Failed to load details: ' + (data.error || 'Unknown error') + '</div>');
        }
      },
      error: function (xhr, status, error) {
        $modalBody.html('<div class="error">Failed to load details: ' + error + '</div>');
      }
    });
  };

  /**
   * Render extension details in modal.
   */
  Drupal.siteAnalyzer.renderExtensionDetails = function ($container, details) {
    var html = '<div class="extension-details">';

    // Basic info
    html += '<div class="detail-section">';
    html += '<h4>Basic Information</h4>';
    html += '<div class="detail-grid">';
    html += '<div class="detail-item"><strong>Name:</strong> ' + (details.display_name || details.name) + '</div>';
    html += '<div class="detail-item"><strong>Type:</strong> ' + details.type + '</div>';
    html += '<div class="detail-item"><strong>Version:</strong> ' + (details.version || 'Unknown') + '</div>';
    html += '<div class="detail-item"><strong>Status:</strong> ' + (details.status || 'Unknown') + '</div>';
    html += '</div>';
    html += '</div>';

    // Description
    if (details.description) {
      html += '<div class="detail-section">';
      html += '<h4>Description</h4>';
      html += '<p>' + details.description + '</p>';
      html += '</div>';
    }

    // Dependencies
    if (details.dependencies && details.dependencies.length > 0) {
      html += '<div class="detail-section">';
      html += '<h4>Dependencies</h4>';
      html += '<ul>';
      details.dependencies.forEach(function (dep) {
        html += '<li>' + dep + '</li>';
      });
      html += '</ul>';
      html += '</div>';
    }

    // Compatibility issues
    if (details.compatibility_issues && details.compatibility_issues.length > 0) {
      html += '<div class="detail-section">';
      html += '<h4>Compatibility Issues</h4>';
      html += '<div class="issues-list">';
      details.compatibility_issues.forEach(function (issue) {
        html += '<div class="issue-item issue-' + (issue.severity || 'medium') + '">';
        html += '<strong>' + (issue.type || 'Issue') + ':</strong> ' + issue.message;
        html += '</div>';
      });
      html += '</div>';
      html += '</div>';
    }

    html += '</div>';

    $container.html(html);
  };

  /**
   * Initialize modals.
   */
  Drupal.siteAnalyzer.initModals = function ($container) {
    // Close modal functionality
    $container.on('click', '.modal-close', function () {
      $(this).closest('.modal').hide();
    });

    // Close modal on background click
    $container.on('click', '.modal', function (e) {
      if (e.target === this) {
        $(this).hide();
      }
    });

    // Close modal on escape key
    $(document).on('keydown', function (e) {
      if (e.key === 'Escape') {
        $container.find('.modal:visible').hide();
      }
    });
  };

  /**
   * Initialize dropdowns.
   */
  Drupal.siteAnalyzer.initDropdowns = function ($container) {
    $container.on('click', '.dropdown-toggle', function (e) {
      e.preventDefault();
      var $dropdown = $(this).closest('.dropdown');
      var $menu = $dropdown.find('.dropdown-menu');

      // Close other dropdowns
      $('.dropdown-menu').not($menu).hide();

      // Toggle current dropdown
      $menu.toggle();
    });

    // Close dropdowns when clicking outside
    $(document).on('click', function (e) {
      if (!$(e.target).closest('.dropdown').length) {
        $('.dropdown-menu').hide();
      }
    });
  };

  /**
   * Show loading overlay.
   */
  Drupal.siteAnalyzer.showLoadingOverlay = function (message) {
    var $overlay = $('.loading-overlay');
    if ($overlay.length === 0) {
      $overlay = $('<div class="loading-overlay"><div class="loading-content"><div class="spinner"></div><div class="loading-text"></div></div></div>');
      $('body').append($overlay);
    }
    $overlay.find('.loading-text').text(message || 'Loading...');
    $overlay.show();
  };

  /**
   * Hide loading overlay.
   */
  Drupal.siteAnalyzer.hideLoadingOverlay = function () {
    $('.loading-overlay').hide();
  };

  /**
   * Show notification.
   */
  Drupal.siteAnalyzer.showNotification = function (message, type) {
    type = type || 'info';

    var $notification = $('<div class="site-analyzer-notification notification-' + type + '">' + message + '</div>');
    $('body').append($notification);

    $notification.fadeIn().delay(3000).fadeOut(function () {
      $(this).remove();
    });
  };

  /**
   * Show scan results.
   */
  Drupal.siteAnalyzer.showScanResults = function ($container, name, results) {
    var $modal = $container.find('#results-modal');
    var $modalTitle = $modal.find('.modal-title');
    var $modalBody = $modal.find('#modal-results');

    $modalTitle.text('Scan Results: ' + name);

    var html = '<div class="scan-results">';
    html += '<div class="results-summary">';
    html += '<h4>Summary</h4>';
    html += '<div class="summary-stats">';
    html += '<div class="stat-item">Issues Found: <strong>' + (results.issues_count || 0) + '</strong></div>';
    html += '<div class="stat-item">Readiness Score: <strong>' + (results.readiness_score || 0) + '%</strong></div>';
    html += '</div>';
    html += '</div>';

    if (results.issues && results.issues.length > 0) {
      html += '<div class="results-issues">';
      html += '<h4>Issues</h4>';
      results.issues.forEach(function (issue) {
        html += '<div class="issue-item issue-' + (issue.severity || 'medium') + '">';
        html += '<strong>' + (issue.type || 'Issue') + ':</strong> ' + issue.message;
        if (issue.file) {
          html += '<div class="issue-file">File: ' + issue.file + (issue.line ? ':' + issue.line : '') + '</div>';
        }
        html += '</div>';
      });
      html += '</div>';
    }

    html += '</div>';

    $modalBody.html(html);
    $modal.show();
  };

  /**
   * Show bulk scan results.
   */
  Drupal.siteAnalyzer.showBulkScanResults = function ($container, data) {
    var $modal = $container.find('#results-modal');
    var $modalTitle = $modal.find('.modal-title');
    var $modalBody = $modal.find('#modal-results');

    $modalTitle.text('Bulk Scan Results');

    var html = '<div class="bulk-scan-results">';
    html += '<div class="results-summary">';
    html += '<h4>Summary</h4>';
    html += '<div class="summary-stats">';
    html += '<div class="stat-item">Extensions Scanned: <strong>' + (data.scanned_count || 0) + '</strong></div>';
    html += '<div class="stat-item">Total Issues: <strong>' + (data.total_issues || 0) + '</strong></div>';
    html += '<div class="stat-item">Extensions with Issues: <strong>' + (data.extensions_with_issues || 0) + '</strong></div>';
    html += '</div>';
    html += '</div>';

    if (data.results) {
      html += '<div class="results-details">';
      html += '<h4>Details</h4>';
      Object.keys(data.results).forEach(function (extensionName) {
        var result = data.results[extensionName];
        html += '<div class="extension-result">';
        html += '<h5>' + extensionName + '</h5>';
        if (result.issues_count > 0) {
          html += '<div class="result-issues">Found ' + result.issues_count + ' issues</div>';
        } else {
          html += '<div class="result-clean">No issues found</div>';
        }
        html += '</div>';
      });
      html += '</div>';
    }

    html += '</div>';

    $modalBody.html(html);
    $modal.show();
  };

})(jQuery, Drupal, once);