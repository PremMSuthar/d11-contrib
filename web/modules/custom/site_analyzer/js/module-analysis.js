/**
 * @file
 * Module analysis specific JavaScript.
 */

(function ($, Drupal, once) {
  'use strict';

  /**
   * Module analysis behaviors.
   */
  Drupal.behaviors.siteAnalyzerModuleAnalysis = {
    attach: function (context, settings) {
      once('module-analysis', '.site-analyzer-module-report', context).forEach(function (element) {
        Drupal.siteAnalyzer.initModuleAnalysis(element);
      });
    }
  };

  /**
   * Initialize module analysis functionality.
   */
  Drupal.siteAnalyzer.initModuleAnalysis = function (container) {
    var $container = $(container);

    // Initialize scan controls
    Drupal.siteAnalyzer.initScanControls($container);

    // Initialize module filters
    Drupal.siteAnalyzer.initModuleFilters($container);

    // Initialize module table sorting
    Drupal.siteAnalyzer.initModuleTableSorting($container);
  };

  /**
   * Initialize scan controls.
   */
  Drupal.siteAnalyzer.initScanControls = function ($container) {
    // Scan individual module button
    $container.on('click', '.scan-module-btn', function (e) {
      e.preventDefault();
      var $button = $(this);
      var moduleName = $button.data('module');
      
      Drupal.siteAnalyzer.scanModule(moduleName, $button);
    });

    // Scan all modules button
    $container.on('click', '.scan-all-modules-btn', function (e) {
      e.preventDefault();
      var $button = $(this);
      var scanType = $button.data('scan-type') || 'all';
      
      Drupal.siteAnalyzer.scanAllModules(scanType, $button);
    });

    // Export results button
    $container.on('click', '.export-results-btn', function (e) {
      e.preventDefault();
      var format = $(this).data('format') || 'json';
      Drupal.siteAnalyzer.exportModuleResults(format);
    });
  };

  /**
   * Scan individual module.
   */
  Drupal.siteAnalyzer.scanModule = function (moduleName, $button) {
    var originalText = $button.text();
    $button.text('Scanning...').prop('disabled', true);

    // Show progress indicator
    var $progress = $button.siblings('.scan-progress');
    if ($progress.length === 0) {
      $progress = $('<div class="scan-progress"><div class="spinner"></div> Scanning module...</div>');
      $button.after($progress);
    }
    $progress.show();

    $.ajax({
      url: '/admin/reports/site-analyzer/scan/' + encodeURIComponent(moduleName),
      type: 'POST',
      dataType: 'json',
      success: function (data) {
        if (data.success) {
          Drupal.siteAnalyzer.displayScanResults(moduleName, data, $button);
          Drupal.siteAnalyzer.showNotification('Module scan completed successfully', 'success');
        } else {
          Drupal.siteAnalyzer.showNotification('Scan failed: ' + (data.error || 'Unknown error'), 'error');
        }
      },
      error: function (xhr, status, error) {
        Drupal.siteAnalyzer.showNotification('Scan failed: ' + error, 'error');
      },
      complete: function () {
        $button.text(originalText).prop('disabled', false);
        $progress.hide();
      }
    });
  };

  /**
   * Scan all modules.
   */
  Drupal.siteAnalyzer.scanAllModules = function (scanType, $button) {
    var originalText = $button.text();
    $button.text('Scanning...').prop('disabled', true);

    // Show progress indicator
    var $progress = $button.siblings('.scan-progress');
    if ($progress.length === 0) {
      $progress = $('<div class="scan-progress"><div class="spinner"></div> Scanning modules...</div>');
      $button.after($progress);
    }
    $progress.show();

    $.ajax({
      url: '/admin/reports/site-analyzer/scan-all',
      type: 'POST',
      data: { scan_type: scanType },
      dataType: 'json',
      success: function (data) {
        if (data.success) {
          Drupal.siteAnalyzer.displayBulkScanResults(data, $button);
          Drupal.siteAnalyzer.showNotification('Bulk scan completed successfully', 'success');
        } else {
          Drupal.siteAnalyzer.showNotification('Bulk scan failed: ' + (data.error || 'Unknown error'), 'error');
        }
      },
      error: function (xhr, status, error) {
        Drupal.siteAnalyzer.showNotification('Bulk scan failed: ' + error, 'error');
      },
      complete: function () {
        $button.text(originalText).prop('disabled', false);
        $progress.hide();
      }
    });
  };

  /**
   * Display scan results for individual module.
   */
  Drupal.siteAnalyzer.displayScanResults = function (moduleName, data, $button) {
    var $resultsContainer = $button.closest('.module-row').find('.scan-results');
    
    if ($resultsContainer.length === 0) {
      $resultsContainer = $('<div class="scan-results"></div>');
      $button.closest('.module-row').append($resultsContainer);
    }

    var results = data.results;
    var summary = data.summary;

    var html = '<div class="scan-result-item">';
    html += '<h4>Scan Results for ' + moduleName + '</h4>';
    
    if (summary.total_issues === 0) {
      html += '<div class="scan-result-item clean">';
      html += '<strong>✓ No issues found</strong>';
      html += '</div>';
    } else {
      html += '<div class="scan-result-item issues-found">';
      html += '<strong>⚠ ' + summary.total_issues + ' issues found</strong>';
      html += '<ul>';
      
      if (summary.deprecated_functions > 0) {
        html += '<li>' + summary.deprecated_functions + ' deprecated functions</li>';
      }
      if (summary.deprecated_hooks > 0) {
        html += '<li>' + summary.deprecated_hooks + ' deprecated hooks</li>';
      }
      if (summary.api_changes > 0) {
        html += '<li>' + summary.api_changes + ' API changes</li>';
      }
      if (summary.security_issues > 0) {
        html += '<li>' + summary.security_issues + ' security issues</li>';
      }
      
      html += '</ul>';
      html += '</div>';
    }

    html += '</div>';

    $resultsContainer.html(html).slideDown();
  };

  /**
   * Display bulk scan results.
   */
  Drupal.siteAnalyzer.displayBulkScanResults = function (data, $button) {
    var $resultsContainer = $('.bulk-scan-results');
    
    if ($resultsContainer.length === 0) {
      $resultsContainer = $('<div class="bulk-scan-results"></div>');
      $button.closest('.module-scan-controls').after($resultsContainer);
    }

    var summary = data.summary;
    var results = data.results;

    var html = '<div class="bulk-scan-summary">';
    html += '<h3>Bulk Scan Results</h3>';
    html += '<div class="summary-stats">';
    html += '<div class="stat-item"><span class="stat-label">Modules Scanned:</span> <span class="stat-value">' + data.modules_scanned + '</span></div>';
    html += '<div class="stat-item"><span class="stat-label">Total Issues:</span> <span class="stat-value">' + data.total_issues + '</span></div>';
    html += '<div class="stat-item"><span class="stat-label">Modules with Issues:</span> <span class="stat-value">' + summary.modules_with_issues + '</span></div>';
    html += '<div class="stat-item"><span class="stat-label">Clean Modules:</span> <span class="stat-value">' + summary.modules_clean + '</span></div>';
    html += '</div>';

    if (summary.modules_with_issues > 0) {
      html += '<div class="detailed-results">';
      html += '<h4>Modules with Issues:</h4>';
      
      for (var moduleName in results) {
        var moduleResult = results[moduleName];
        if (moduleResult.status === 'issues_found') {
          html += '<div class="module-result">';
          html += '<strong>' + moduleName + '</strong>: ' + moduleResult.issues_count + ' issues found';
          html += '</div>';
        }
      }
      
      html += '</div>';
    }

    html += '</div>';

    $resultsContainer.html(html).slideDown();
  };

  /**
   * Initialize module filters.
   */
  Drupal.siteAnalyzer.initModuleFilters = function ($container) {
    // Module type filter
    $container.on('change', '.module-type-filter', function () {
      var filterValue = $(this).val();
      Drupal.siteAnalyzer.filterModulesByType(filterValue, $container);
    });

    // Module status filter
    $container.on('change', '.module-status-filter', function () {
      var filterValue = $(this).val();
      Drupal.siteAnalyzer.filterModulesByStatus(filterValue, $container);
    });

    // Search filter
    $container.on('input', '.module-search-filter', function () {
      var searchTerm = $(this).val().toLowerCase();
      Drupal.siteAnalyzer.filterModulesBySearch(searchTerm, $container);
    });
  };

  /**
   * Filter modules by type.
   */
  Drupal.siteAnalyzer.filterModulesByType = function (type, $container) {
    var $rows = $container.find('.module-table tbody tr');
    
    if (type === 'all') {
      $rows.show();
    } else {
      $rows.each(function () {
        var $row = $(this);
        var moduleType = $row.data('module-type');
        
        if (moduleType === type) {
          $row.show();
        } else {
          $row.hide();
        }
      });
    }
  };

  /**
   * Filter modules by status.
   */
  Drupal.siteAnalyzer.filterModulesByStatus = function (status, $container) {
    var $rows = $container.find('.module-table tbody tr');
    
    if (status === 'all') {
      $rows.show();
    } else {
      $rows.each(function () {
        var $row = $(this);
        var moduleStatus = $row.data('module-status');
        
        if (moduleStatus === status) {
          $row.show();
        } else {
          $row.hide();
        }
      });
    }
  };

  /**
   * Filter modules by search term.
   */
  Drupal.siteAnalyzer.filterModulesBySearch = function (searchTerm, $container) {
    var $rows = $container.find('.module-table tbody tr');
    
    if (searchTerm === '') {
      $rows.show();
    } else {
      $rows.each(function () {
        var $row = $(this);
        var text = $row.text().toLowerCase();
        
        if (text.indexOf(searchTerm) > -1) {
          $row.show();
        } else {
          $row.hide();
        }
      });
    }
  };

  /**
   * Initialize module table sorting.
   */
  Drupal.siteAnalyzer.initModuleTableSorting = function ($container) {
    $container.on('click', '.sortable-header', function () {
      var $header = $(this);
      var columnIndex = $header.index();
      var $table = $header.closest('table');
      
      Drupal.siteAnalyzer.sortModuleTable(columnIndex, $table);
    });
  };

  /**
   * Sort module table by column.
   */
  Drupal.siteAnalyzer.sortModuleTable = function (columnIndex, $table) {
    var $tbody = $table.find('tbody');
    var $rows = $tbody.find('tr').toArray();
    
    $rows.sort(function (a, b) {
      var aText = $(a).find('td').eq(columnIndex).text().trim();
      var bText = $(b).find('td').eq(columnIndex).text().trim();
      
      // Try to parse as numbers
      var aNum = parseFloat(aText);
      var bNum = parseFloat(bText);
      
      if (!isNaN(aNum) && !isNaN(bNum)) {
        return aNum - bNum;
      } else {
        return aText.localeCompare(bText);
      }
    });
    
    $tbody.empty().append($rows);
  };

  /**
   * Export module analysis results.
   */
  Drupal.siteAnalyzer.exportModuleResults = function (format) {
    var exportUrl = '/admin/reports/site-analyzer/export/' + format;
    
    // Create hidden form to trigger download
    var $form = $('<form>', {
      'method': 'GET',
      'action': exportUrl
    }).appendTo('body');
    
    $form.submit();
    $form.remove();
    
    Drupal.siteAnalyzer.showNotification('Export started', 'info');
  };

})(jQuery, Drupal, once);