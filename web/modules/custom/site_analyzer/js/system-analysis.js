/**
 * @file
 * Enhanced System Analysis JavaScript functionality.
 */

(function ($, Drupal, once) {
  'use strict';

  /**
   * Enhanced System Analysis behaviors.
   */
  Drupal.behaviors.siteAnalyzerSystemEnhanced = {
    attach: function (context, settings) {
      // Initialize enhanced system analysis features
      once('system-analysis-init', 'body', context).forEach(function () {
        initializeSystemAnalysis();
      });
    }
  };

  /**
   * Initialize system analysis features.
   */
  function initializeSystemAnalysis() {
    // Add smooth scrolling to section links
    addSmoothScrolling();
    
    // Initialize tooltips for technical terms
    initializeTooltips();
    
    // Add copy functionality for code snippets
    addCopyFunctionality();
    
    // Initialize collapsible sections
    initializeCollapsibleSections();
    
    // Add keyboard navigation
    addKeyboardNavigation();
  }

  /**
   * Add smooth scrolling to internal links.
   */
  function addSmoothScrolling() {
    $('a[href^="#"]').on('click', function(e) {
      e.preventDefault();
      var target = $(this.getAttribute('href'));
      if (target.length) {
        $('html, body').animate({
          scrollTop: target.offset().top - 100
        }, 500);
      }
    });
  }

  /**
   * Initialize tooltips for technical terms.
   */
  function initializeTooltips() {
    // Add tooltips to status badges
    $('.status-badge').each(function() {
      var $this = $(this);
      var tooltipText = getStatusTooltip($this.text().trim());
      if (tooltipText) {
        $this.attr('title', tooltipText);
      }
    });
    
    // Add tooltips to tool names
    $('.tool-name').each(function() {
      var $this = $(this);
      var toolName = $this.text().trim().toLowerCase();
      var tooltipText = getToolTooltip(toolName);
      if (tooltipText) {
        $this.attr('title', tooltipText);
      }
    });
  }

  /**
   * Get tooltip text for status badges.
   */
  function getStatusTooltip(status) {
    var tooltips = {
      'Available': 'This tool is installed and ready to use',
      'Not Available': 'This tool is not installed or not in PATH',
      'Enabled': 'This feature is currently active',
      'Disabled': 'This feature is currently inactive',
      'Missing': 'This component is not found',
      'Exists': 'This file or directory is present',
      'Good': 'This setting is optimal',
      'Warning': 'This setting may need attention',
      'Critical': 'This setting requires immediate attention'
    };
    return tooltips[status] || null;
  }

  /**
   * Get tooltip text for tools.
   */
  function getToolTooltip(toolName) {
    var tooltips = {
      'phpcs': 'PHP CodeSniffer - Detects violations of coding standards',
      'phpstan': 'Static analysis tool for PHP - Finds bugs without running code',
      'psalm': 'Static analysis tool focused on type safety',
      'phpmd': 'PHP Mess Detector - Finds potential problems in code',
      'rector': 'Automated tool for upgrading and refactoring PHP code',
      'eslint': 'JavaScript linting utility for identifying problematic patterns',
      'stylelint': 'CSS linter that helps avoid errors and enforce conventions',
      'phpunit': 'Testing framework for PHP applications',
      'behat': 'Behavior-driven development framework for PHP',
      'codeception': 'Full-stack testing framework for PHP',
      'drush': 'Command-line shell and scripting interface for Drupal',
      'composer': 'Dependency manager for PHP'
    };
    return tooltips[toolName] || null;
  }

  /**
   * Add copy functionality for code snippets.
   */
  function addCopyFunctionality() {
    $('code').each(function() {
      var $code = $(this);
      var text = $code.text().trim();
      
      // Only add copy button for longer code snippets
      if (text.length > 10 && (text.includes('composer') || text.includes('drush') || text.includes('vendor'))) {
        var $copyBtn = $('<button class="copy-btn" title="Copy to clipboard">📋</button>');
        $code.css('position', 'relative').append($copyBtn);
        
        $copyBtn.on('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          copyToClipboard(text, $copyBtn);
        });
      }
    });
  }

  /**
   * Copy text to clipboard.
   */
  function copyToClipboard(text, $button) {
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(text).then(function() {
        showCopySuccess($button);
      }).catch(function() {
        fallbackCopyToClipboard(text, $button);
      });
    } else {
      fallbackCopyToClipboard(text, $button);
    }
  }

  /**
   * Fallback copy method for older browsers.
   */
  function fallbackCopyToClipboard(text, $button) {
    var textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
      document.execCommand('copy');
      showCopySuccess($button);
    } catch (err) {
      console.error('Failed to copy text: ', err);
    }
    
    document.body.removeChild(textArea);
  }

  /**
   * Show copy success feedback.
   */
  function showCopySuccess($button) {
    var originalText = $button.text();
    $button.text('✓').addClass('copy-success');
    
    setTimeout(function() {
      $button.text(originalText).removeClass('copy-success');
    }, 2000);
  }

  /**
   * Initialize collapsible sections.
   */
  function initializeCollapsibleSections() {
    // Make recommendation sections collapsible
    $('.version-recommendations, .tool-recommendations').each(function() {
      var $section = $(this);
      var $header = $section.find('h4').first();
      
      if ($header.length) {
        $header.css('cursor', 'pointer')
               .prepend('<span class="collapse-indicator">▼ </span>')
               .on('click', function() {
                 var $content = $section.find('.recommendation');
                 var $indicator = $header.find('.collapse-indicator');
                 
                 $content.slideToggle(300);
                 $indicator.text($indicator.text() === '▼ ' ? '▶ ' : '▼ ');
               });
      }
    });
  }

  /**
   * Add keyboard navigation support.
   */
  function addKeyboardNavigation() {
    // Add keyboard support for tool cards
    $('.tool-card').attr('tabindex', '0').on('keydown', function(e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        $(this).trigger('click');
      }
    });
    
    // Add keyboard support for recommendation sections
    $('.recommendation').attr('tabindex', '0').on('keydown', function(e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        var $action = $(this).find('.recommendation-action code');
        if ($action.length) {
          copyToClipboard($action.text(), $action);
        }
      }
    });
  }

  /**
   * Global refresh function for system analysis.
   */
  window.refreshSystemAnalysis = function() {
    // Show loading state
    var $button = $('.refresh-btn');
    var originalText = $button.html();
    $button.html('<span class="btn-icon">⏳</span>' + Drupal.t('Refreshing...'))
           .prop('disabled', true);
    
    // Add loading overlay
    var $overlay = $('<div class="loading-overlay"><div class="loading-spinner">🔄</div><div class="loading-text">' + Drupal.t('Analyzing system...') + '</div></div>');
    $('body').append($overlay);
    
    // Simulate analysis time (in real implementation, this would be an AJAX call)
    setTimeout(function() {
      window.location.reload();
    }, 1000);
  };

})(jQuery, Drupal, once);