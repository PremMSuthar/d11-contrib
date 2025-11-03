/**
 * @file
 * JavaScript for the Drupal Settings Viewer module.
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Behavior for the Drupal Settings Viewer.
   */
  Drupal.behaviors.drupalSettingsViewer = {
    attach: function (context, settings) {
      // Handle both full page and tray containers
      var settingsContainer = document.getElementById('drupal-settings-viewer-container');
      var trayContainer = document.getElementById('drupal-settings-tray-container');
      
      if (settingsContainer && context === document) {
        // Full page view
        this.displaySettings(settingsContainer);
      }
      
      if (trayContainer) {
        // Tray view - initialize when tray is opened
        once('settings-viewer-tray', '.drupal-settings-viewer-tray', context).forEach((element) => {
          this.initializeTray(trayContainer);
        });
      }
    },

    /**
     * Initialize the toolbar tray.
     */
    initializeTray: function (container) {
      // Display settings immediately
      this.displaySettingsInTray(container);
      
      // Set up control buttons
      this.setupTrayControls(container);
      
      // Listen for toolbar tray events
      document.addEventListener('drupalToolbarTrayChange', (event) => {
        if (event.detail.activeTray === 'toolbar-item-drupal-settings-viewer') {
          // Refresh settings when tray is opened
          setTimeout(() => {
            this.displaySettingsInTray(container);
          }, 100);
        }
      });
    },

    /**
     * Set up control buttons for the tray.
     */
    setupTrayControls: function (container) {
      var refreshBtn = document.getElementById('refresh-settings');
      var copyBtn = document.getElementById('copy-settings');
      var expandBtn = document.getElementById('toggle-expand');
      
      if (refreshBtn) {
        refreshBtn.addEventListener('click', () => {
          this.displaySettingsInTray(container);
        });
      }
      
      if (copyBtn) {
        copyBtn.addEventListener('click', () => {
          var jsonContent = container.querySelector('.settings-viewer-json');
          if (jsonContent) {
            this.copyToClipboard(jsonContent.textContent, copyBtn);
          }
        });
      }
      
      if (expandBtn) {
        expandBtn.addEventListener('click', () => {
          this.toggleExpansion(container, expandBtn);
        });
      }
    },

    /**
     * Display settings in the toolbar tray.
     */
    displaySettingsInTray: function (container) {
      // Clear existing content
      container.innerHTML = '';
      
      // Create a more compact display for the tray
      var settingsDisplay = document.createElement('div');
      settingsDisplay.className = 'settings-tray-content';
      
      try {
        // Create expandable sections for major settings groups
        var settingsGroups = this.organizeSettings(drupalSettings);
        
        if (Object.keys(settingsGroups).length === 0) {
          settingsDisplay.innerHTML = '<p class="no-settings">No drupalSettings found on this page.</p>';
        } else {
          settingsDisplay.appendChild(this.createSettingsTree(settingsGroups));
        }
      } catch (error) {
        settingsDisplay.innerHTML = '<p class="error">Error loading settings: ' + error.message + '</p>';
      }
      
      container.appendChild(settingsDisplay);
    },

    /**
     * Organize settings into logical groups.
     */
    organizeSettings: function (settings) {
      var groups = {};
      
      for (var key in settings) {
        if (settings.hasOwnProperty(key)) {
          groups[key] = settings[key];
        }
      }
      
      return groups;
    },

    /**
     * Create an expandable tree view of settings.
     */
    createSettingsTree: function (settings) {
      var tree = document.createElement('div');
      tree.className = 'settings-tree';
      
      for (var key in settings) {
        if (settings.hasOwnProperty(key)) {
          var item = this.createTreeItem(key, settings[key]);
          tree.appendChild(item);
        }
      }
      
      return tree;
    },

    /**
     * Create a tree item (expandable).
     */
    createTreeItem: function (key, value) {
      var item = document.createElement('div');
      item.className = 'tree-item';
      
      var header = document.createElement('div');
      header.className = 'tree-header';
      
      var isObject = typeof value === 'object' && value !== null;
      var isArray = Array.isArray(value);
      
      if (isObject || isArray) {
        var toggle = document.createElement('button');
        toggle.className = 'tree-toggle';
        toggle.textContent = '▶';
        toggle.setAttribute('aria-expanded', 'false');
        
        var label = document.createElement('span');
        label.className = 'tree-label';
        label.textContent = key;
        
        var count = document.createElement('span');
        count.className = 'tree-count';
        if (isArray) {
          count.textContent = ' [' + value.length + ']';
        } else {
          count.textContent = ' {' + Object.keys(value).length + '}';
        }
        
        header.appendChild(toggle);
        header.appendChild(label);
        header.appendChild(count);
        
        var content = document.createElement('div');
        content.className = 'tree-content';
        content.style.display = 'none';
        
        if (isArray) {
          value.forEach((item, index) => {
            content.appendChild(this.createTreeItem('[' + index + ']', item));
          });
        } else {
          for (var subKey in value) {
            if (value.hasOwnProperty(subKey)) {
              content.appendChild(this.createTreeItem(subKey, value[subKey]));
            }
          }
        }
        
        toggle.addEventListener('click', () => {
          var isExpanded = toggle.getAttribute('aria-expanded') === 'true';
          toggle.setAttribute('aria-expanded', !isExpanded);
          toggle.textContent = isExpanded ? '▶' : '▼';
          content.style.display = isExpanded ? 'none' : 'block';
        });
        
        item.appendChild(header);
        item.appendChild(content);
      } else {
        var label = document.createElement('span');
        label.className = 'tree-label';
        label.textContent = key + ': ';
        
        var valueSpan = document.createElement('span');
        valueSpan.className = 'tree-value';
        valueSpan.textContent = JSON.stringify(value);
        
        header.appendChild(label);
        header.appendChild(valueSpan);
        item.appendChild(header);
      }
      
      return item;
    },

    /**
     * Toggle expansion of all items.
     */
    toggleExpansion: function (container, button) {
      var toggles = container.querySelectorAll('.tree-toggle');
      var shouldExpand = button.textContent === 'Expand All';
      
      toggles.forEach(toggle => {
        var content = toggle.parentNode.nextElementSibling;
        if (content && content.classList.contains('tree-content')) {
          toggle.setAttribute('aria-expanded', shouldExpand);
          toggle.textContent = shouldExpand ? '▼' : '▶';
          content.style.display = shouldExpand ? 'block' : 'none';
        }
      });
      
      button.textContent = shouldExpand ? 'Collapse All' : 'Expand All';
    },

    /**
     * Display the drupalSettings object in a formatted way.
     *
     * @param {Element} container
     *   The container element to display the settings in.
     */
    displaySettings: function (container) {
      // Clear any existing content.
      container.innerHTML = '';

      // Create header.
      var header = document.createElement('h2');
      header.textContent = 'Current Page drupalSettings';
      header.className = 'settings-viewer-header';
      container.appendChild(header);

      // Create info paragraph.
      var info = document.createElement('p');
      info.innerHTML = 'This is a read-only view of the <code>drupalSettings</code> object for the current page. ' +
        'This data is used by JavaScript on the frontend to configure Drupal behaviors and modules.';
      info.className = 'settings-viewer-info';
      container.appendChild(info);

      // Create the settings display area.
      var settingsDisplay = document.createElement('pre');
      settingsDisplay.className = 'settings-viewer-json';
      settingsDisplay.style.cssText = 'background: #f5f5f5; padding: 1rem; border: 1px solid #ddd; ' +
        'border-radius: 4px; overflow: auto; max-height: 70vh; font-family: monospace; font-size: 14px;';

      try {
        // Format the drupalSettings object as pretty JSON.
        var formattedSettings = JSON.stringify(drupalSettings, null, 2);
        settingsDisplay.textContent = formattedSettings;
      } catch (error) {
        settingsDisplay.textContent = 'Error: Unable to serialize drupalSettings object.\n' + error.message;
        settingsDisplay.style.color = '#d32f2f';
      }

      container.appendChild(settingsDisplay);

      // Add copy button.
      var copyButton = document.createElement('button');
      copyButton.textContent = 'Copy to Clipboard';
      copyButton.className = 'button button--primary';
      copyButton.style.marginTop = '1rem';
      copyButton.addEventListener('click', function () {
        this.copyToClipboard(settingsDisplay.textContent, copyButton);
      }.bind(this));

      container.appendChild(copyButton);

      // Add timestamp.
      var timestamp = document.createElement('p');
      timestamp.textContent = 'Generated at: ' + new Date().toLocaleString();
      timestamp.className = 'settings-viewer-timestamp';
      timestamp.style.cssText = 'margin-top: 1rem; font-size: 0.9em; color: #666;';
      container.appendChild(timestamp);
    },

    /**
     * Copy text to clipboard.
     *
     * @param {string} text
     *   The text to copy.
     * @param {Element} button
     *   The button element to provide feedback on.
     */
    copyToClipboard: function (text, button) {
      if (navigator.clipboard && window.isSecureContext) {
        // Use the modern clipboard API.
        navigator.clipboard.writeText(text).then(function () {
          this.showCopyFeedback(button, 'Copied!');
        }.bind(this)).catch(function () {
          this.showCopyFeedback(button, 'Copy failed');
        }.bind(this));
      } else {
        // Fallback for older browsers.
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
          this.showCopyFeedback(button, 'Copied!');
        } catch (error) {
          this.showCopyFeedback(button, 'Copy failed');
        }

        document.body.removeChild(textArea);
      }
    },

    /**
     * Show feedback on the copy button.
     *
     * @param {Element} button
     *   The button element.
     * @param {string} message
     *   The feedback message.
     */
    showCopyFeedback: function (button, message) {
      var originalText = button.textContent;
      button.textContent = message;
      button.disabled = true;

      setTimeout(function () {
        button.textContent = originalText;
        button.disabled = false;
      }, 2000);
    }
  };

})(Drupal, drupalSettings, once);