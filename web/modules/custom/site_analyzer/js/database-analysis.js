/**
 * Database Analysis JavaScript
 */

(function ($, Drupal, once) {
  'use strict';

  /**
   * Database Analysis behavior.
   */
  Drupal.behaviors.databaseAnalysis = {
    attach: function (context, settings) {
      once('database-analysis', '.site-analyzer-database-report', context).forEach(function (element) {
        new DatabaseAnalysis(element, settings);
      });
    }
  };

  /**
   * Database Analysis class.
   */
  function DatabaseAnalysis(element, settings) {
    this.element = element;
    this.settings = settings.siteAnalyzer?.databaseAnalysis || {};
    this.init();
  }

  DatabaseAnalysis.prototype = {
    init: function () {
      this.initializeDropdowns();
      this.initializeFilters();
      this.initializeTableToggles();
      this.initializeExportControls();
      this.initializeRefreshControls();
      this.initializeStatistics();
    },

    initializeDropdowns: function () {
      var self = this;
      
      // Handle dropdown toggles
      var dropdownToggles = this.element.querySelectorAll('.dropdown-toggle');
      dropdownToggles.forEach(function (toggle) {
        toggle.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();
          
          var dropdown = toggle.closest('.dropdown');
          var isOpen = dropdown.classList.contains('open');
          
          // Close all other dropdowns
          self.element.querySelectorAll('.dropdown.open').forEach(function (openDropdown) {
            openDropdown.classList.remove('open');
          });
          
          // Toggle current dropdown
          if (!isOpen) {
            dropdown.classList.add('open');
          }
        });
      });
      
      // Close dropdowns when clicking outside
      document.addEventListener('click', function (e) {
        if (!e.target.closest('.dropdown')) {
          self.element.querySelectorAll('.dropdown.open').forEach(function (dropdown) {
            dropdown.classList.remove('open');
          });
        }
      });
    },

    initializeFilters: function () {
      var self = this;
      
      // Search functionality
      var searchInput = this.element.querySelector('#table-search');
      if (searchInput) {
        searchInput.addEventListener('input', function () {
          self.filterTables();
        });
      }

      // Filter controls
      var filters = this.element.querySelectorAll('.filter-select');
      filters.forEach(function (filter) {
        filter.addEventListener('change', function () {
          self.filterTables();
        });
      });

      // Clear filters
      var clearBtn = this.element.querySelector('.clear-filters-btn');
      if (clearBtn) {
        clearBtn.addEventListener('click', function () {
          self.clearAllFilters();
        });
      }

      // Toggle all details
      var toggleBtn = this.element.querySelector('.toggle-details-btn');
      if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
          self.toggleAllDetails();
        });
      }
    },

    initializeTableToggles: function () {
      var self = this;
      
      this.element.addEventListener('click', function (e) {
        var btn = e.target.closest('.table-toggle');
        if (btn) {
          e.preventDefault();
          var target = btn.dataset.target;
          var details = self.element.querySelector('#details-' + target);
          var icon = btn.querySelector('.toggle-icon');

          if (details) {
            if (details.style.display === 'none' || !details.style.display) {
              details.style.display = 'block';
              icon.textContent = '▲';
              btn.setAttribute('aria-expanded', 'true');
            } else {
              details.style.display = 'none';
              icon.textContent = '▼';
              btn.setAttribute('aria-expanded', 'false');
            }
          }
        }
      });
    },

    initializeExportControls: function () {
      var self = this;
      
      this.element.addEventListener('click', function (e) {
        var btn = e.target.closest('.export-btn');
        if (btn) {
          e.preventDefault();
          var format = btn.dataset.format;
          self.exportDatabaseReport(format);
          
          // Close dropdown after selection
          var dropdown = btn.closest('.dropdown');
          if (dropdown) {
            dropdown.classList.remove('open');
          }
        }
      });
    },

    initializeRefreshControls: function () {
      var self = this;
      
      this.element.addEventListener('click', function (e) {
        var btn = e.target.closest('.refresh-btn');
        if (btn) {
          e.preventDefault();
          self.refreshDatabaseAnalysis();
        }
      });
    },

    initializeStatistics: function () {
      this.updateFilterStatistics();
      this.animateProgressBars();
    },

    filterTables: function () {
      var searchTerm = this.getSearchTerm();
      var categoryFilter = this.getCategoryFilter();
      var sizeFilter = this.getSizeFilter();
      var rowsFilter = this.getRowsFilter();

      var tables = this.element.querySelectorAll('.table-card');
      var visibleCount = 0;

      tables.forEach(function (table) {
        var show = true;

        // Search filter
        if (searchTerm) {
          var name = table.dataset.name.toLowerCase();
          var description = table.querySelector('.table-description');
          var descText = description ? description.textContent.toLowerCase() : '';
          
          if (!name.includes(searchTerm) && !descText.includes(searchTerm)) {
            show = false;
          }
        }

        // Category filter
        if (categoryFilter !== 'all' && table.dataset.category !== categoryFilter) {
          show = false;
        }

        // Size filter
        if (sizeFilter !== 'all') {
          var size = parseFloat(table.dataset.size) || 0;
          switch (sizeFilter) {
            case 'large':
              if (size <= 10) show = false;
              break;
            case 'medium':
              if (size <= 1 || size > 10) show = false;
              break;
            case 'small':
              if (size >= 1) show = false;
              break;
            case 'empty':
              if (size > 0) show = false;
              break;
          }
        }

        // Rows filter
        if (rowsFilter !== 'all') {
          var rows = parseInt(table.dataset.rows) || 0;
          switch (rowsFilter) {
            case 'high':
              if (rows <= 10000) show = false;
              break;
            case 'medium':
              if (rows <= 1000 || rows > 10000) show = false;
              break;
            case 'low':
              if (rows >= 1000) show = false;
              break;
            case 'empty':
              if (rows > 0) show = false;
              break;
          }
        }

        table.style.display = show ? 'block' : 'none';
        if (show) visibleCount++;
      });

      this.updateFilterStatistics(visibleCount, tables.length);
    },

    clearAllFilters: function () {
      var searchInput = this.element.querySelector('#table-search');
      var categoryFilter = this.element.querySelector('#category-filter');
      var sizeFilter = this.element.querySelector('#size-filter');
      var rowsFilter = this.element.querySelector('#rows-filter');

      if (searchInput) searchInput.value = '';
      if (categoryFilter) categoryFilter.value = 'all';
      if (sizeFilter) sizeFilter.value = 'all';
      if (rowsFilter) rowsFilter.value = 'all';

      this.filterTables();
    },

    toggleAllDetails: function () {
      var details = this.element.querySelectorAll('.table-details');
      var icons = this.element.querySelectorAll('.toggle-icon');
      var toggleBtns = this.element.querySelectorAll('.table-toggle');
      
      var anyVisible = Array.from(details).some(function (detail) {
        return detail.style.display === 'block';
      });

      details.forEach(function (detail) {
        detail.style.display = anyVisible ? 'none' : 'block';
      });

      icons.forEach(function (icon) {
        icon.textContent = anyVisible ? '▼' : '▲';
      });
      
      toggleBtns.forEach(function (btn) {
        btn.setAttribute('aria-expanded', anyVisible ? 'false' : 'true');
      });
    },

    exportDatabaseReport: function (format) {
      var self = this;
      
      this.showLoadingOverlay('Preparing export...');
      
      // Collect visible table data
      var visibleTables = this.getVisibleTablesData();
      var exportData = {
        summary: this.settings,
        tables: visibleTables,
        timestamp: new Date().toISOString(),
        format: format
      };

      setTimeout(function () {
        try {
          switch (format) {
            case 'json':
              self.downloadJSON(exportData);
              break;
            case 'csv':
              self.downloadCSV(exportData);
              break;
            case 'excel':
              self.downloadExcel(exportData);
              break;
            default:
              console.warn('Unsupported export format:', format);
          }
        } catch (error) {
          console.error('Export failed:', error);
          alert('Export failed. Please try again.');
        }
        
        self.hideLoadingOverlay();
      }, 500);
    },

    refreshDatabaseAnalysis: function () {
      this.showLoadingOverlay('Refreshing database analysis...');
      
      // In a real implementation, this would make an AJAX call
      setTimeout(function () {
        window.location.reload();
      }, 1000);
    },

    // Helper methods
    getSearchTerm: function () {
      var searchInput = this.element.querySelector('#table-search');
      return searchInput ? searchInput.value.toLowerCase().trim() : '';
    },

    getCategoryFilter: function () {
      var categoryFilter = this.element.querySelector('#category-filter');
      return categoryFilter ? categoryFilter.value : 'all';
    },

    getSizeFilter: function () {
      var sizeFilter = this.element.querySelector('#size-filter');
      return sizeFilter ? sizeFilter.value : 'all';
    },

    getRowsFilter: function () {
      var rowsFilter = this.element.querySelector('#rows-filter');
      return rowsFilter ? rowsFilter.value : 'all';
    },

    updateFilterStatistics: function (visible, total) {
      // Update any filter statistics display
      var statsElement = this.element.querySelector('.filter-stats');
      if (statsElement && visible !== undefined && total !== undefined) {
        statsElement.textContent = 'Showing ' + visible + ' of ' + total + ' tables';
      }
    },

    animateProgressBars: function () {
      var progressBars = this.element.querySelectorAll('.bar-fill');
      
      progressBars.forEach(function (bar) {
        var width = bar.style.width;
        bar.style.width = '0%';
        
        setTimeout(function () {
          bar.style.width = width;
        }, 100);
      });
    },

    getVisibleTablesData: function () {
      var visibleTables = [];
      var tableCards = this.element.querySelectorAll('.table-card');
      
      tableCards.forEach(function (card) {
        if (card.style.display !== 'none') {
          var tableData = {
            name: card.dataset.name,
            category: card.dataset.category,
            size_mb: parseFloat(card.dataset.size) || 0,
            row_count: parseInt(card.dataset.rows) || 0
          };
          
          // Extract field information if details are visible
          var details = card.querySelector('.table-details');
          if (details && details.style.display === 'block') {
            var fieldsTable = details.querySelector('.fields-list tbody');
            if (fieldsTable) {
              tableData.fields = [];
              var rows = fieldsTable.querySelectorAll('tr');
              rows.forEach(function (row) {
                var cells = row.querySelectorAll('td');
                if (cells.length >= 5) {
                  tableData.fields.push({
                    name: cells[0].textContent.trim(),
                    type: cells[1].textContent.trim(),
                    nullable: cells[2].textContent.trim(),
                    default_value: cells[3].textContent.trim(),
                    key_type: cells[4].textContent.trim()
                  });
                }
              });
            }
          }
          
          visibleTables.push(tableData);
        }
      });
      
      return visibleTables;
    },

    downloadJSON: function (data) {
      var jsonString = JSON.stringify(data, null, 2);
      var blob = new Blob([jsonString], { type: 'application/json' });
      var url = URL.createObjectURL(blob);
      
      var link = document.createElement('a');
      link.href = url;
      link.download = 'database-analysis-' + this.formatDate() + '.json';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      URL.revokeObjectURL(url);
    },

    downloadCSV: function (data) {
      var csvContent = this.convertToCSV(data.tables);
      var blob = new Blob([csvContent], { type: 'text/csv' });
      var url = URL.createObjectURL(blob);
      
      var link = document.createElement('a');
      link.href = url;
      link.download = 'database-tables-' + this.formatDate() + '.csv';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      URL.revokeObjectURL(url);
    },

    downloadExcel: function (data) {
      // For Excel export, we'll use CSV format with Excel-friendly headers
      var csvContent = this.convertToCSV(data.tables, true);
      var blob = new Blob([csvContent], { type: 'application/vnd.ms-excel' });
      var url = URL.createObjectURL(blob);
      
      var link = document.createElement('a');
      link.href = url;
      link.download = 'database-analysis-' + this.formatDate() + '.xls';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      URL.revokeObjectURL(url);
    },

    convertToCSV: function (tables, excelFormat) {
      var headers = ['Table Name', 'Category', 'Size (MB)', 'Row Count', 'Field Count'];
      var csvContent = headers.join(',') + '\n';
      
      tables.forEach(function (table) {
        var row = [
          '"' + table.name + '"',
          '"' + table.category + '"',
          table.size_mb,
          table.row_count,
          table.fields ? table.fields.length : 0
        ];
        csvContent += row.join(',') + '\n';
      });
      
      return excelFormat ? '\uFEFF' + csvContent : csvContent; // Add BOM for Excel
    },

    formatDate: function () {
      var now = new Date();
      return now.getFullYear() + '-' +
        String(now.getMonth() + 1).padStart(2, '0') + '-' +
        String(now.getDate()).padStart(2, '0');
    },

    showLoadingOverlay: function (message) {
      var overlay = this.element.querySelector('.loading-overlay');
      if (overlay) {
        var loadingText = overlay.querySelector('.loading-text');
        if (loadingText && message) {
          loadingText.textContent = message;
        }
        overlay.style.display = 'flex';
      }
    },

    hideLoadingOverlay: function () {
      var overlay = this.element.querySelector('.loading-overlay');
      if (overlay) {
        overlay.style.display = 'none';
      }
    }
  };

})(jQuery, Drupal, once);