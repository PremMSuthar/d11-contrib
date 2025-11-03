/**
 * @file
 * Site Analyzer Navigation JavaScript
 */

(function ($, Drupal, once) {
  'use strict';

  /**
   * Site Analyzer Navigation behavior.
   */
  Drupal.behaviors.siteAnalyzerNavigation = {
    attach: function (context, settings) {
      once('site-analyzer-navigation', '.site-analyzer-navigation', context).forEach(function (element) {
        const $navigation = $(element);
        const $navLinks = $navigation.find('.nav-link');

        // Add keyboard navigation
        $navLinks.on('keydown', function(e) {
          handleKeyboardNavigation(e, $navLinks);
        });

        // Add active state management
        updateActiveStates($navLinks);

        // Add smooth scrolling for anchor links
        $navLinks.filter('[href*="#"]').on('click', function(e) {
          handleAnchorClick(e, $(this));
        });

        // Initialize navigation state
        initializeNavigation($navigation);
      });
    }
  };



  /**
   * Handle keyboard navigation.
   */
  function handleKeyboardNavigation(e, $navLinks) {
    const currentIndex = $navLinks.index(e.target);
    let targetIndex;

    switch (e.key) {
      case 'ArrowRight':
      case 'ArrowDown':
        e.preventDefault();
        targetIndex = (currentIndex + 1) % $navLinks.length;
        $navLinks.eq(targetIndex).focus();
        break;
        
      case 'ArrowLeft':
      case 'ArrowUp':
        e.preventDefault();
        targetIndex = currentIndex === 0 ? $navLinks.length - 1 : currentIndex - 1;
        $navLinks.eq(targetIndex).focus();
        break;
        
      case 'Home':
        e.preventDefault();
        $navLinks.first().focus();
        break;
        
      case 'End':
        e.preventDefault();
        $navLinks.last().focus();
        break;
    }
  }

  /**
   * Update active states based on current page.
   */
  function updateActiveStates($navLinks) {
    const currentPath = window.location.pathname;
    
    $navLinks.each(function() {
      const $link = $(this);
      const linkPath = $link.attr('href');
      
      if (linkPath && currentPath.includes(linkPath)) {
        $link.addClass('nav-link--active');
        $link.closest('.nav-item').addClass('nav-item--active');
      }
    });
  }

  /**
   * Handle anchor link clicks.
   */
  function handleAnchorClick(e, $link) {
    const href = $link.attr('href');
    const target = href.substring(href.indexOf('#'));
    
    if (target.length > 1) {
      const $target = $(target);
      
      if ($target.length) {
        e.preventDefault();
        
        $('html, body').animate({
          scrollTop: $target.offset().top - 100
        }, 500);
        
        // Update URL without triggering page reload
        if (history.pushState) {
          history.pushState(null, null, href);
        }
      }
    }
  }

  /**
   * Initialize navigation state.
   */
  function initializeNavigation($navigation) {
    // Add navigation context from drupalSettings
    if (Drupal.settings && Drupal.settings.siteAnalyzer && Drupal.settings.siteAnalyzer.navigation) {
      const context = Drupal.settings.siteAnalyzer.navigation;
      
      // Highlight current page
      if (context.current_key) {
        const $currentLink = $navigation.find('.nav-link[data-key="' + context.current_key + '"]');
        $currentLink.addClass('nav-link--active');
        $currentLink.closest('.nav-item').addClass('nav-item--active');
      }
    }

    // Set up responsive behavior
    setupResponsiveBehavior($navigation);
  }

  /**
   * Setup responsive behavior.
   */
  function setupResponsiveBehavior($navigation) {
    // Responsive behavior for single-line navigation
    // No mobile toggle needed
  }

  /**
   * Navigation utilities.
   */
  Drupal.siteAnalyzerNavigation = {
    /**
     * Programmatically navigate to a page.
     */
    navigateTo: function(route) {
      if (Drupal.settings && Drupal.settings.siteAnalyzer && Drupal.settings.siteAnalyzer.navigation) {
        const navigation = Drupal.settings.siteAnalyzer.navigation.navigation;
        
        for (const key in navigation) {
          if (navigation[key].route === route) {
            window.location.href = navigation[key].url;
            break;
          }
        }
      }
    },

    /**
     * Get current navigation context.
     */
    getCurrentContext: function() {
      if (Drupal.settings && Drupal.settings.siteAnalyzer && Drupal.settings.siteAnalyzer.navigation) {
        return Drupal.settings.siteAnalyzer.navigation;
      }
      return null;
    },

    /**
     * Update navigation highlight.
     */
    updateActiveState: function(key) {
      $('.site-analyzer-navigation .nav-link').removeClass('nav-link--active');
      $('.site-analyzer-navigation .nav-item').removeClass('nav-item--active');
      
      const $targetLink = $('.site-analyzer-navigation .nav-link[data-key="' + key + '"]');
      $targetLink.addClass('nav-link--active');
      $targetLink.closest('.nav-item').addClass('nav-item--active');
    }
  };

})(jQuery, Drupal, once);