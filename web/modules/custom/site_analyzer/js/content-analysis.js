/**
 * @file
 * Content analysis specific JavaScript.
 */

(function ($, Drupal, once) {
  'use strict';

  /**
   * Content analysis behaviors.
   */
  Drupal.behaviors.siteAnalyzerContentAnalysis = {
    attach: function (context, settings) {
      once('content-analysis', '.site-analyzer-content-report', context).forEach(function (element) {
        Drupal.siteAnalyzer.initContentAnalysis(element);
      });
    }
  };

  /**
   * Initialize content analysis functionality.
   */
  Drupal.siteAnalyzer.initContentAnalysis = function (container) {
    var $container = $(container);

    // Initialize tab functionality
    Drupal.siteAnalyzer.initContentTabs($container);

    // Initialize content type visualization
    Drupal.siteAnalyzer.initContentTypeVisualization($container);

    // Initialize field analysis
    Drupal.siteAnalyzer.initFieldAnalysis($container);

    // Initialize relationship diagram
    Drupal.siteAnalyzer.initRelationshipDiagram($container);

    // Initialize content filters
    Drupal.siteAnalyzer.initContentFilters($container);

    // Initialize export functionality
    Drupal.siteAnalyzer.initContentExport($container);

    // Initialize migration plan functionality
    Drupal.siteAnalyzer.initMigrationPlan($container);
  };

  /**
   * Initialize tab functionality.
   */
  Drupal.siteAnalyzer.initContentTabs = function ($container) {
    // Tab navigation
    $container.on('click', '.tab-button', function (e) {
      e.preventDefault();
      var $button = $(this);
      var targetTab = $button.data('tab');
      
      // Remove active class from all tabs and buttons
      $container.find('.tab-button').removeClass('active');
      $container.find('.tab-content').removeClass('active');
      
      // Add active class to clicked button and corresponding content
      $button.addClass('active');
      $container.find('#' + targetTab).addClass('active');
      
      // Trigger any tab-specific initialization
      Drupal.siteAnalyzer.onTabChange(targetTab, $container);
    });
  };

  /**
   * Handle tab change events.
   */
  Drupal.siteAnalyzer.onTabChange = function (tabId, $container) {
    switch (tabId) {
      case 'relationships':
        // Re-initialize mermaid diagrams if needed
        if (typeof mermaid !== 'undefined') {
          mermaid.init();
        }
        break;
      case 'migration':
        // Initialize migration checklist functionality
        Drupal.siteAnalyzer.initMigrationChecklist($container);
        break;
    }
  };

  /**
   * Initialize content type visualization.
   */
  Drupal.siteAnalyzer.initContentTypeVisualization = function ($container) {
    var $chartContainer = $container.find('.content-type-chart');
    
    if ($chartContainer.length && $chartContainer.data('chart-data')) {
      var chartData = $chartContainer.data('chart-data');
      Drupal.siteAnalyzer.renderContentTypeChart($chartContainer[0], chartData);
    }
  };

  /**
   * Render content type chart.
   */
  Drupal.siteAnalyzer.renderContentTypeChart = function (container, data) {
    // Simple bar chart implementation
    var $container = $(container);
    var maxCount = Math.max.apply(Math, data.map(function(item) { return item.count; }));
    
    var html = '<div class="chart-bars">';
    
    data.forEach(function (item) {
      var percentage = (item.count / maxCount) * 100;
      html += '<div class="chart-bar">';
      html += '<div class="bar-label">' + item.label + '</div>';
      html += '<div class="bar-container">';
      html += '<div class="bar-fill" style="width: ' + percentage + '%"></div>';
      html += '<div class="bar-value">' + item.count + '</div>';
      html += '</div>';
      html += '</div>';
    });
    
    html += '</div>';
    
    $container.html(html);
  };

  /**
   * Initialize field analysis.
   */
  Drupal.siteAnalyzer.initFieldAnalysis = function ($container) {
    // Field type breakdown
    $container.on('click', '.field-type-toggle', function () {
      var $toggle = $(this);
      var $details = $toggle.next('.field-type-details');
      
      if ($details.is(':visible')) {
        $details.slideUp();
        $toggle.find('.toggle-icon').text('+');
      } else {
        $details.slideDown();
        $toggle.find('.toggle-icon').text('-');
      }
    });

    // Deprecated field warnings
    $container.find('.deprecated-field').each(function () {
      var $field = $(this);
      $field.prepend('<span class="warning-icon">⚠</span>');
    });
  };

  /**
   * Initialize relationship diagram.
   */
  Drupal.siteAnalyzer.initRelationshipDiagram = function ($container) {
    var $diagramContainer = $container.find('.relationship-diagram');
    
    if ($diagramContainer.length && $diagramContainer.data('relationships')) {
      var relationships = $diagramContainer.data('relationships');
      Drupal.siteAnalyzer.renderRelationshipDiagram($diagramContainer[0], relationships);
    }
  };

  /**
   * Render relationship diagram.
   */
  Drupal.siteAnalyzer.renderRelationshipDiagram = function (container, relationships) {
    var $container = $(container);
    
    // Simple node-link diagram
    var html = '<div class="diagram-nodes">';
    
    // Create nodes for content types
    var nodes = {};
    relationships.content_types.forEach(function (type) {
      nodes[type] = {
        id: type,
        type: 'content_type',
        label: type.replace('_', ' ').toUpperCase()
      };
    });
    
    // Create nodes for vocabularies
    relationships.vocabularies.forEach(function (vocab) {
      nodes[vocab] = {
        id: vocab,
        type: 'vocabulary',
        label: vocab.replace('_', ' ').toUpperCase()
      };
    });
    
    // Render nodes
    for (var nodeId in nodes) {
      var node = nodes[nodeId];
      html += '<div class="diagram-node ' + node.type + '" data-node-id="' + nodeId + '">';
      html += '<div class="node-label">' + node.label + '</div>';
      html += '</div>';
    }
    
    html += '</div>';
    
    // Add relationships info
    html += '<div class="relationships-info">';
    html += '<h4>Relationships:</h4>';
    html += '<ul>';
    
    relationships.relationships.forEach(function (rel) {
      html += '<li>' + rel.source + ' → ' + rel.target + ' (' + rel.type + ')</li>';
    });
    
    html += '</ul>';
    html += '</div>';
    
    $container.html(html);
  };

  /**
   * Initialize content filters.
   */
  Drupal.siteAnalyzer.initContentFilters = function ($container) {
    // Content type filter
    $container.on('change', '.content-type-filter', function () {
      var filterValue = $(this).val();
      Drupal.siteAnalyzer.filterContentByType(filterValue, $container);
    });

    // Field type filter
    $container.on('change', '.field-type-filter', function () {
      var filterValue = $(this).val();
      Drupal.siteAnalyzer.filterContentByFieldType(filterValue, $container);
    });

    // Usage filter
    $container.on('change', '.usage-filter', function () {
      var filterValue = $(this).val();
      Drupal.siteAnalyzer.filterContentByUsage(filterValue, $container);
    });
  };

  /**
   * Filter content by type.
   */
  Drupal.siteAnalyzer.filterContentByType = function (type, $container) {
    var $items = $container.find('.content-item');
    
    if (type === 'all') {
      $items.show();
    } else {
      $items.each(function () {
        var $item = $(this);
        var itemType = $item.data('content-type');
        
        if (itemType === type) {
          $item.show();
        } else {
          $item.hide();
        }
      });
    }
  };

  /**
   * Filter content by field type.
   */
  Drupal.siteAnalyzer.filterContentByFieldType = function (fieldType, $container) {
    var $items = $container.find('.field-item');
    
    if (fieldType === 'all') {
      $items.show();
    } else {
      $items.each(function () {
        var $item = $(this);
        var itemFieldType = $item.data('field-type');
        
        if (itemFieldType === fieldType) {
          $item.show();
        } else {
          $item.hide();
        }
      });
    }
  };

  /**
   * Filter content by usage.
   */
  Drupal.siteAnalyzer.filterContentByUsage = function (usage, $container) {
    var $items = $container.find('.content-item');
    
    if (usage === 'all') {
      $items.show();
    } else if (usage === 'high') {
      $items.each(function () {
        var $item = $(this);
        var itemUsage = parseInt($item.data('usage-count'), 10);
        
        if (itemUsage > 100) {
          $item.show();
        } else {
          $item.hide();
        }
      });
    } else if (usage === 'medium') {
      $items.each(function () {
        var $item = $(this);
        var itemUsage = parseInt($item.data('usage-count'), 10);
        
        if (itemUsage > 10 && itemUsage <= 100) {
          $item.show();
        } else {
          $item.hide();
        }
      });
    } else if (usage === 'low') {
      $items.each(function () {
        var $item = $(this);
        var itemUsage = parseInt($item.data('usage-count'), 10);
        
        if (itemUsage <= 10) {
          $item.show();
        } else {
          $item.hide();
        }
      });
    }
  };

  /**
   * Toggle content details.
   */
  Drupal.siteAnalyzer.toggleContentDetails = function (element) {
    var $element = $(element);
    var $details = $element.next('.content-details');
    var $icon = $element.find('.toggle-icon');
    
    if ($details.is(':visible')) {
      $details.slideUp();
      $icon.text('+');
    } else {
      $details.slideDown();
      $icon.text('-');
    }
  };

  /**
   * Export content analysis.
   */
  Drupal.siteAnalyzer.exportContentAnalysis = function (format) {
    var exportUrl = '/admin/reports/site-analyzer/export/' + format + '?section=content';
    
    // Create hidden form to trigger download
    var $form = $('<form>', {
      'method': 'GET',
      'action': exportUrl
    }).appendTo('body');
    
    $form.submit();
    $form.remove();
    
    Drupal.siteAnalyzer.showNotification('Content analysis export started', 'info');
  };

  /**
   * Initialize export functionality.
   */
  Drupal.siteAnalyzer.initContentExport = function ($container) {
    $container.on('click', '.export-content-report', function (e) {
      e.preventDefault();
      var format = $(this).data('format') || 'json';
      Drupal.siteAnalyzer.exportContentAnalysis(format);
    });
  };

  /**
   * Initialize migration plan functionality.
   */
  Drupal.siteAnalyzer.initMigrationPlan = function ($container) {
    $container.on('click', '.generate-migration-plan', function (e) {
      e.preventDefault();
      Drupal.siteAnalyzer.generateMigrationPlan($container);
    });
  };

  /**
   * Initialize migration checklist.
   */
  Drupal.siteAnalyzer.initMigrationChecklist = function ($container) {
    $container.on('change', '.checklist-item input[type="checkbox"]', function () {
      var $checkbox = $(this);
      var $item = $checkbox.closest('.checklist-item');
      
      if ($checkbox.is(':checked')) {
        $item.addClass('completed');
      } else {
        $item.removeClass('completed');
      }
      
      // Update progress
      Drupal.siteAnalyzer.updateChecklistProgress($container);
    });
  };

  /**
   * Update checklist progress.
   */
  Drupal.siteAnalyzer.updateChecklistProgress = function ($container) {
    var $checkboxes = $container.find('.checklist-item input[type="checkbox"]');
    var total = $checkboxes.length;
    var completed = $checkboxes.filter(':checked').length;
    var percentage = total > 0 ? Math.round((completed / total) * 100) : 0;
    
    var $progressBar = $container.find('.checklist-progress');
    if ($progressBar.length === 0) {
      $progressBar = $('<div class="checklist-progress"><div class="progress-bar"><div class="progress-fill"></div></div><div class="progress-text"></div></div>');
      $container.find('.migration-section .checklist').after($progressBar);
    }
    
    $progressBar.find('.progress-fill').css('width', percentage + '%');
    $progressBar.find('.progress-text').text(completed + ' of ' + total + ' completed (' + percentage + '%)');
  };

  /**
   * Generate migration plan.
   */
  Drupal.siteAnalyzer.generateMigrationPlan = function ($container) {
    var $button = $container.find('.generate-migration-plan');
    var originalText = $button.text();
    
    $button.prop('disabled', true).text('Generating...');
    
    // Simulate API call to generate migration plan
    setTimeout(function () {
      var recommendations = Drupal.siteAnalyzer.generateMigrationRecommendations($container);
      Drupal.siteAnalyzer.showMigrationPlan(recommendations, $container);
      $button.prop('disabled', false).text(originalText);
    }, 2000);
  };

  /**
   * Show migration plan modal.
   */
  Drupal.siteAnalyzer.showMigrationPlan = function (recommendations, $container) {
    var html = '<div class="migration-plan-modal">';
    html += '<div class="modal-overlay"></div>';
    html += '<div class="modal-content">';
    html += '<div class="modal-header">';
    html += '<h3>Migration Plan</h3>';
    html += '<button class="modal-close">&times;</button>';
    html += '</div>';
    html += '<div class="modal-body">';
    html += '<div class="migration-recommendations">';
    
    if (recommendations.length > 0) {
      recommendations.forEach(function (rec) {
        html += '<div class="recommendation-item ' + rec.type + '">';
        html += '<div class="rec-icon">' + (rec.type === 'warning' ? '⚠️' : 'ℹ️') + '</div>';
        html += '<div class="rec-content">';
        html += '<div class="rec-message">' + rec.message + '</div>';
        html += '<div class="rec-action">' + rec.action + '</div>';
        html += '</div>';
        html += '</div>';
      });
    } else {
      html += '<div class="no-recommendations">No specific recommendations at this time. Your content structure looks good for migration!</div>';
    }
    
    html += '</div>';
    html += '</div>';
    html += '<div class="modal-footer">';
    html += '<button class="btn btn-primary export-plan">Export Plan</button>';
    html += '<button class="btn btn-secondary modal-close">Close</button>';
    html += '</div>';
    html += '</div>';
    html += '</div>';
    
    var $modal = $(html);
    $('body').append($modal);
    
    // Modal close functionality
    $modal.on('click', '.modal-close, .modal-overlay', function () {
      $modal.remove();
    });
    
    // Export plan functionality
    $modal.on('click', '.export-plan', function () {
      Drupal.siteAnalyzer.exportMigrationPlan(recommendations);
    });
  };

  /**
   * Export migration plan.
   */
  Drupal.siteAnalyzer.exportMigrationPlan = function (recommendations) {
    var planData = {
      generated: new Date().toISOString(),
      recommendations: recommendations,
      checklist: []
    };
    
    // Add checklist items
    $('.checklist-item').each(function () {
      var $item = $(this);
      var $checkbox = $item.find('input[type="checkbox"]');
      var $label = $item.find('label');
      
      planData.checklist.push({
        task: $label.text(),
        completed: $checkbox.is(':checked'),
        id: $checkbox.attr('id')
      });
    });
    
    var dataStr = JSON.stringify(planData, null, 2);
    var dataBlob = new Blob([dataStr], {type: 'application/json'});
    var url = URL.createObjectURL(dataBlob);
    
    var link = document.createElement('a');
    link.href = url;
    link.download = 'migration-plan-' + new Date().toISOString().split('T')[0] + '.json';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
    
    Drupal.siteAnalyzer.showNotification('Migration plan exported successfully', 'success');
  };

  /**
   * Generate content migration recommendations.
   */
  Drupal.siteAnalyzer.generateMigrationRecommendations = function ($container) {
    var recommendations = [];
    
    // Check for deprecated fields
    $container.find('.deprecated-field').each(function () {
      var fieldName = $(this).data('field-name');
      var fieldType = $(this).data('field-type');
      
      recommendations.push({
        type: 'warning',
        message: 'Field "' + fieldName + '" uses deprecated type "' + fieldType + '"',
        action: 'Consider migrating to a supported field type'
      });
    });
    
    // Check for high-usage content types
    $container.find('.content-item').each(function () {
      var $item = $(this);
      var usage = parseInt($item.data('usage-count'), 10);
      var contentType = $item.data('content-type');
      
      if (usage > 1000) {
        recommendations.push({
          type: 'info',
          message: 'Content type "' + contentType + '" has high usage (' + usage + ' items)',
          action: 'Plan for extended migration time'
        });
      }
    });
    
    return recommendations;
  };

})(jQuery, Drupal, once);