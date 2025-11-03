/**
 * @file
 * JavaScript for simplified Site Analyzer extensions display.
 */

(function ($, Drupal, once) {
  'use strict';

  /**
   * Simplified Extensions Table functionality.
   */
  Drupal.behaviors.siteAnalyzerSimple = {
    attach: function (context, settings) {
      once('site-analyzer-simple', '.site-analyzer-unified-extensions-simple', context).forEach(function (element) {
        const $container = $(element);
        const $table = $container.find('.extensions-table');
        const $searchInput = $container.find('#extension-search');
        const $typeFilter = $container.find('#type-filter');
        const $statusFilter = $container.find('#status-filter');
        const $categoryFilter = $container.find('#category-filter');
        const $clearFilters = $container.find('.clear-filters-btn');

        // Initialize sorting
        initSorting($table);
        
        // Initialize filtering
        initFiltering($searchInput, $typeFilter, $statusFilter, $categoryFilter, $clearFilters, $table);
      });
    }
  };

  /**
   * Initialize table sorting functionality.
   */
  function initSorting($table) {
    const $headers = $table.find('th.sortable');
    
    $headers.on('click', function() {
      const $header = $(this);
      const sortField = $header.data('sort');
      const currentSort = $header.hasClass('sorted-asc') ? 'asc' : 
                         $header.hasClass('sorted-desc') ? 'desc' : 'none';
      
      // Remove sorting from all headers
      $headers.removeClass('sorted-asc sorted-desc');
      
      // Determine new sort direction
      let newSort = 'asc';
      if (currentSort === 'asc') {
        newSort = 'desc';
      }
      
      // Apply new sort
      $header.addClass('sorted-' + newSort);
      
      // Sort the table
      sortTable($table, sortField, newSort);
    });
  }

  /**
   * Sort table by field and direction.
   */
  function sortTable($table, field, direction) {
    const $tbody = $table.find('tbody');
    const $rows = $tbody.find('tr').get();
    
    $rows.sort(function(a, b) {
      let aVal = getFieldValue($(a), field);
      let bVal = getFieldValue($(b), field);
      
      // Handle numeric values
      if (!isNaN(aVal) && !isNaN(bVal)) {
        aVal = parseFloat(aVal);
        bVal = parseFloat(bVal);
      } else {
        // Convert to lowercase for string comparison
        aVal = aVal.toString().toLowerCase();
        bVal = bVal.toString().toLowerCase();
      }
      
      if (direction === 'asc') {
        return aVal < bVal ? -1 : aVal > bVal ? 1 : 0;
      } else {
        return aVal > bVal ? -1 : aVal < bVal ? 1 : 0;
      }
    });
    
    // Re-append sorted rows
    $.each($rows, function(index, row) {
      $tbody.append(row);
    });
  }

  /**
   * Get field value from table row.
   */
  function getFieldValue($row, field) {
    switch (field) {
      case 'display_name':
        return $row.find('.extension-name strong').text().trim();
      case 'type':
        return $row.data('type') || '';
      case 'status':
        return $row.data('status') || '';
      case 'category':
        return $row.data('category') || '';
      case 'version':
        return $row.find('.version-text').text().trim();
      case 'package':
        return $row.find('.package-text').text().trim();
      default:
        return '';
    }
  }

  /**
   * Initialize filtering functionality.
   */
  function initFiltering($searchInput, $typeFilter, $statusFilter, $categoryFilter, $clearFilters, $table) {
    // Search input
    $searchInput.on('input', function() {
      applyFilters($table, $searchInput, $typeFilter, $statusFilter, $categoryFilter);
    });
    
    // Filter dropdowns
    $typeFilter.on('change', function() {
      applyFilters($table, $searchInput, $typeFilter, $statusFilter, $categoryFilter);
    });
    
    $statusFilter.on('change', function() {
      applyFilters($table, $searchInput, $typeFilter, $statusFilter, $categoryFilter);
    });
    
    $categoryFilter.on('change', function() {
      applyFilters($table, $searchInput, $typeFilter, $statusFilter, $categoryFilter);
    });
    
    // Clear filters button
    $clearFilters.on('click', function() {
      $searchInput.val('');
      $typeFilter.val('all');
      $statusFilter.val('all');
      $categoryFilter.val('all');
      applyFilters($table, $searchInput, $typeFilter, $statusFilter, $categoryFilter);
    });
  }

  /**
   * Apply all filters to the table.
   */
  function applyFilters($table, $searchInput, $typeFilter, $statusFilter, $categoryFilter) {
    const searchTerm = $searchInput.val().toLowerCase();
    const typeFilter = $typeFilter.val();
    const statusFilter = $statusFilter.val();
    const categoryFilter = $categoryFilter.val();
    
    const $rows = $table.find('tbody tr');
    let visibleCount = 0;
    
    $rows.each(function() {
      const $row = $(this);
      let visible = true;
      
      // Search filter
      if (searchTerm) {
        const extensionName = $row.find('.extension-name strong').text().toLowerCase();
        const extensionDescription = $row.find('.extension-description').text().toLowerCase();
        const extensionPackage = $row.find('.package-text').text().toLowerCase();
        
        if (extensionName.indexOf(searchTerm) === -1 && 
            extensionDescription.indexOf(searchTerm) === -1 &&
            extensionPackage.indexOf(searchTerm) === -1) {
          visible = false;
        }
      }
      
      // Type filter
      if (typeFilter !== 'all' && $row.data('type') !== typeFilter) {
        visible = false;
      }
      
      // Status filter
      if (statusFilter !== 'all' && $row.data('status') !== statusFilter) {
        visible = false;
      }
      
      // Category filter
      if (categoryFilter !== 'all' && $row.data('category') !== categoryFilter) {
        visible = false;
      }
      
      // Show/hide row
      if (visible) {
        $row.show();
        visibleCount++;
      } else {
        $row.hide();
      }
    });
    
    // Update empty state
    updateEmptyState($table, visibleCount);
  }

  /**
   * Update empty state display.
   */
  function updateEmptyState($table, visibleCount) {
    const $tbody = $table.find('tbody');
    let $emptyRow = $tbody.find('.empty-state-row');
    
    if (visibleCount === 0) {
      if ($emptyRow.length === 0) {
        $emptyRow = $('<tr class="empty-state-row"><td colspan="6" class="text-center"><div class="empty-state"><div class="empty-icon">🔍</div><div class="empty-message">No extensions match your filters.</div></div></td></tr>');
        $tbody.append($emptyRow);
      }
      $emptyRow.show();
    } else {
      $emptyRow.hide();
    }
  }

})(jQuery, Drupal, once);