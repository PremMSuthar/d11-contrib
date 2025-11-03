/**
 * @file
 * JavaScript for the Render Array Inspector module.
 */

(function ($, Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Render Array Inspector behavior.
   */
  Drupal.behaviors.renderArrayInspector = {
    attach: function (context, settings) {
      console.log('Render Array Inspector: Attaching behavior');
      
      // Initialize when toolbar item is present
      once('render-array-inspector', '.toolbar-icon-render-array', context).forEach(function (element) {
        console.log('Render Array Inspector: Found toolbar icon');
        
        var $toolbarItem = $(element).closest('.toolbar-item');
        var isInitialized = false;

        // Setup when toolbar is clicked
        $toolbarItem.on('click', function () {
          console.log('Render Array Inspector: Toolbar clicked');
          setTimeout(function() {
            if (!isInitialized) {
              console.log('Render Array Inspector: Initializing');
              setupEventHandlers();
              isInitialized = true;
            }
          }, 200);
        });
      });

      // Also try to setup handlers immediately if the content is already visible
      if ($('#refresh-data').length > 0) {
        console.log('Render Array Inspector: Content already visible, setting up handlers');
        setupEventHandlers();
      }

      /**
       * Setup event handlers
       */
      function setupEventHandlers() {
        console.log('Render Array Inspector: Setting up event handlers');
        
        // Remove any existing handlers first
        $(document).off('click.rai');
        
        // Refresh button
        $(document).on('click.rai', '#refresh-data', function (e) {
          console.log('Refresh button clicked');
          e.preventDefault();
          loadData();
        });

        // Expand all button
        $(document).on('click.rai', '#expand-all', function (e) {
          console.log('Expand all clicked');
          e.preventDefault();
          $('.tree-children.collapsed').removeClass('collapsed');
          $('.tree-toggle').text('−');
        });

        // Collapse all button
        $(document).on('click.rai', '#collapse-all', function (e) {
          console.log('Collapse all clicked');
          e.preventDefault();
          $('.tree-children').addClass('collapsed');
          $('.tree-toggle').text('+');
        });

        // Tree node clicks
        $(document).on('click.rai', '.tree-node', function (e) {
          e.preventDefault();
          var $node = $(this);
          var $children = $node.parent().find('> .tree-children');
          var $toggle = $node.find('.tree-toggle');

          if ($children.length > 0) {
            if ($children.hasClass('collapsed')) {
              $children.removeClass('collapsed');
              $toggle.text('−');
            } else {
              $children.addClass('collapsed');
              $toggle.text('+');
            }
          }
        });
        
        console.log('Render Array Inspector: Event handlers set up');
      }

      /**
       * Load render array data
       */
      function loadData() {
        console.log('Loading render array data...');
        
        // Show loading state
        $('#loading-message').show();
        $('#empty-message').hide();
        $('#data-display').hide();
        
        // Disable refresh button
        $('#refresh-data').prop('disabled', true).text('Loading...');

        var ajaxUrl = drupalSettings.path.baseUrl + 'admin/render-array-inspector/get-render-array';
        console.log('AJAX URL:', ajaxUrl);

        $.ajax({
          url: ajaxUrl,
          type: 'GET',
          dataType: 'json',
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          },
          success: function (response) {
            console.log('AJAX Success - Data loaded:', response);
            handleSuccess(response);
          },
          error: function (xhr, status, error) {
            console.error('AJAX Error:', {xhr: xhr, status: status, error: error});
            handleError(xhr, status, error);
          },
          complete: function() {
            console.log('AJAX Complete');
            // Hide loading and re-enable button
            $('#loading-message').hide();
            $('#refresh-data').prop('disabled', false).text('Refresh');
          }
        });
      }

      /**
       * Handle successful data load
       */
      function handleSuccess(response) {
        console.log('Handling success response');
        
        if (response && response.length > 0) {
          // Process AJAX commands
          for (var i = 0; i < response.length; i++) {
            if (response[i].command === 'insert' && response[i].method === 'html') {
              console.log('Processing AJAX command:', response[i]);
              $(response[i].selector).html(response[i].data);
            }
          }
          
          $('#data-display').show();
          $('#empty-message').hide();
          console.log('Data display updated');
        } else {
          console.log('No valid response data');
          showError('No data received from server');
        }
      }

      /**
       * Handle error
       */
      function handleError(xhr, status, error) {
        console.error('Handling error:', error);
        
        var errorMsg = 'Error loading data: ' + error;
        if (xhr.responseText) {
          console.log('Response text:', xhr.responseText);
          errorMsg += ' (Response: ' + xhr.responseText.substring(0, 200) + ')';
        }
        showError(errorMsg);
      }

      /**
       * Show error message
       */
      function showError(message) {
        console.log('Showing error:', message);
        
        var errorHtml = '<div class="message message-error">';
        errorHtml += '<strong>Error:</strong> ' + message;
        errorHtml += '<br><small>Check the browser console for more details.</small>';
        errorHtml += '</div>';
        
        $('#data-display').html(errorHtml).show();
        $('#empty-message').hide();
      }
    }
  };

  /**
   * Toolbar integration - auto-load when tray opens
   */
  Drupal.behaviors.renderArrayInspectorToolbar = {
    attach: function (context, settings) {
      console.log('Render Array Inspector Toolbar: Attaching');
      
      // Listen for toolbar tray changes
      $(document).on('drupalToolbarTrayChange', function (event, tray) {
        console.log('Toolbar tray changed:', tray);
        
        if (tray.id === 'toolbar-item-render-array-inspector') {
          console.log('Our tray was opened');
          
          // Auto-load data after a short delay
          setTimeout(function() {
            if ($('#refresh-data').length > 0 && $('#data-display').is(':hidden')) {
              console.log('Auto-loading data');
              $('#refresh-data').trigger('click');
            }
          }, 500);
        }
      });
    }
  };

})(jQuery, Drupal, drupalSettings, once);