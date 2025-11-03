/**
 * @file
 * Site Analyzer module JavaScript.
 */

(function ($, Drupal, once) {
  'use strict';

  /**
   * Site Analyzer dashboard behaviors.
   */
  Drupal.behaviors.siteAnalyzerDashboard = {
    attach: function (context, settings) {
      once('site-analyzer-dashboard', '.site-analyzer-dashboard', context).forEach(function (element) {
        // Initialize dashboard
        Drupal.siteAnalyzer.initDashboard(element);
      });
    }
  };

  /**
   * Site Analyzer namespace.
   */
  Drupal.siteAnalyzer = Drupal.siteAnalyzer || {};

  /**
   * Initialize dashboard functionality.
   */
  Drupal.siteAnalyzer.initDashboard = function (dashboard) {
    // Add click handlers for metric cards
    $(dashboard).find('.dashboard-card').on('click', function () {
      var card = $(this);
      var cardType = card.attr('class').match(/(\w+)-card/);
      
      if (cardType && cardType[1]) {
        var analysisType = cardType[1];
        var url = '/admin/reports/site-analyzer/' + analysisType;
        
        // Navigate to detailed analysis page
        if (analysisType === 'system') {
          window.location.href = url.replace('system', 'system');
        } else if (analysisType === 'modules') {
          window.location.href = url.replace('modules', 'modules');
        } else if (analysisType === 'themes') {
          window.location.href = url.replace('themes', 'themes');
        } else if (analysisType === 'content') {
          window.location.href = url.replace('content', 'content');
        } else if (analysisType === 'security') {
          window.location.href = url.replace('security', 'security');
        } else if (analysisType === 'performance') {
          window.location.href = url.replace('performance', 'performance');
        } else if (analysisType === 'upgrade') {
          window.location.href = url.replace('upgrade', 'upgrade');
        }
      }
    });

    // Add hover effects
    $(dashboard).find('.dashboard-card').hover(
      function () {
        $(this).css('cursor', 'pointer');
      },
      function () {
        $(this).css('cursor', 'default');
      }
    );

    // Initialize progress bars
    Drupal.siteAnalyzer.initProgressBars(dashboard);

    // Auto-refresh dashboard data every 5 minutes
    if (settings.siteAnalyzer && settings.siteAnalyzer.autoRefresh) {
      setInterval(function () {
        Drupal.siteAnalyzer.refreshDashboard();
      }, 300000); // 5 minutes
    }
  };

  /**
   * Initialize progress bars with animation.
   */
  Drupal.siteAnalyzer.initProgressBars = function (container) {
    $(container).find('.progress-fill').each(function () {
      var progressBar = $(this);
      var targetWidth = progressBar.css('width');
      
      // Start from 0 and animate to target width
      progressBar.css('width', '0%');
      
      setTimeout(function () {
        progressBar.css('width', targetWidth);
      }, 500);
    });
  };

  /**
   * Refresh dashboard data via AJAX.
   */
  Drupal.siteAnalyzer.refreshDashboard = function () {
    $.ajax({
      url: '/admin/reports/site-analyzer/ajax-refresh',
      type: 'GET',
      dataType: 'json',
      success: function (data) {
        if (data.success) {
          Drupal.siteAnalyzer.updateDashboardData(data.data);
        }
      },
      error: function () {
        console.log('Failed to refresh dashboard data');
      }
    });
  };

  /**
   * Update dashboard with new data.
   */
  Drupal.siteAnalyzer.updateDashboardData = function (data) {
    // Update metric values
    $('.metric-value').each(function () {
      var metric = $(this);
      var metricName = metric.data('metric');
      
      if (data[metricName]) {
        metric.text(data[metricName]);
      }
    });

    // Update status indicators
    $('.status-indicator').each(function () {
      var indicator = $(this);
      var statusType = indicator.data('status-type');
      
      if (data[statusType + '_status']) {
        indicator.removeClass().addClass('status-indicator status-' + data[statusType + '_status']);
        indicator.text(data[statusType + '_status'].replace('_', ' ').toUpperCase());
      }
    });

    // Update progress bars
    $('.progress-fill').each(function () {
      var progressBar = $(this);
      var progressType = progressBar.data('progress-type');
      
      if (data[progressType]) {
        progressBar.css('width', data[progressType] + '%');
      }
    });
  };

  /**
   * Export functionality.
   */
  Drupal.siteAnalyzer.exportReport = function (format) {
    var exportUrl = '/admin/reports/site-analyzer/export/' + format;
    
    // Show loading indicator
    var button = $('[data-export="' + format + '"]');
    var originalText = button.text();
    button.text('Exporting...').prop('disabled', true);
    
    // Create hidden form to trigger download
    var form = $('<form>', {
      'method': 'GET',
      'action': exportUrl
    }).appendTo('body');
    
    form.submit();
    form.remove();
    
    // Reset button after delay
    setTimeout(function () {
      button.text(originalText).prop('disabled', false);
    }, 2000);
  };

  /**
   * Show/hide detailed information.
   */
  Drupal.siteAnalyzer.toggleDetails = function (element) {
    var details = $(element).next('.details-content');
    var icon = $(element).find('.toggle-icon');
    
    if (details.is(':visible')) {
      details.slideUp();
      icon.text('+');
    } else {
      details.slideDown();
      icon.text('-');
    }
  };

  /**
   * Filter table rows.
   */
  Drupal.siteAnalyzer.filterTable = function (input, tableId) {
    var filter = $(input).val().toLowerCase();
    var table = $('#' + tableId);
    
    table.find('tbody tr').each(function () {
      var row = $(this);
      var text = row.text().toLowerCase();
      
      if (text.indexOf(filter) > -1) {
        row.show();
      } else {
        row.hide();
      }
    });
  };

  /**
   * Sort table by column.
   */
  Drupal.siteAnalyzer.sortTable = function (columnIndex, tableId) {
    var table = $('#' + tableId);
    var tbody = table.find('tbody');
    var rows = tbody.find('tr').toArray();
    
    rows.sort(function (a, b) {
      var aText = $(a).find('td').eq(columnIndex).text();
      var bText = $(b).find('td').eq(columnIndex).text();
      
      // Try to parse as numbers
      var aNum = parseFloat(aText);
      var bNum = parseFloat(bText);
      
      if (!isNaN(aNum) && !isNaN(bNum)) {
        return aNum - bNum;
      } else {
        return aText.localeCompare(bText);
      }
    });
    
    tbody.empty().append(rows);
  };

  /**
   * Show notification message.
   */
  Drupal.siteAnalyzer.showNotification = function (message, type) {
    type = type || 'info';
    
    var notification = $('<div>', {
      'class': 'site-analyzer-notification notification-' + type,
      'text': message
    });
    
    $('body').append(notification);
    
    notification.fadeIn().delay(3000).fadeOut(function () {
      $(this).remove();
    });
  };

  /**
   * Initialize tooltips.
   */
  Drupal.siteAnalyzer.initTooltips = function (context) {
    $(context).find('[data-tooltip]').each(function () {
      var element = $(this);
      var tooltip = element.data('tooltip');
      
      element.hover(
        function () {
          var tooltipElement = $('<div>', {
            'class': 'site-analyzer-tooltip',
            'text': tooltip
          });
          
          $('body').append(tooltipElement);
          
          var offset = element.offset();
          tooltipElement.css({
            'top': offset.top - tooltipElement.outerHeight() - 5,
            'left': offset.left + (element.outerWidth() / 2) - (tooltipElement.outerWidth() / 2)
          }).fadeIn();
        },
        function () {
          $('.site-analyzer-tooltip').remove();
        }
      );
    });
  };

  // Initialize tooltips on page load
  $(document).ready(function () {
    Drupal.siteAnalyzer.initTooltips(document);
  });

})(jQuery, Drupal, once);