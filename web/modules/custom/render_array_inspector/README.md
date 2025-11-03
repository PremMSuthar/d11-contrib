# Render Array Inspector

A Drupal module that provides a toolbar panel showing the render array structure behind the current page's output.

## Features

- **Toolbar Integration**: Adds a "Render Array" tab to the Drupal toolbar
- **Interactive Tree View**: Displays render arrays in a collapsible tree structure
- **Real-time Inspection**: View the render array of any page you're currently viewing
- **Developer-Friendly**: Designed specifically for developers debugging render arrays
- **Configurable**: Settings to control display depth and auto-expansion
- **Permission-Based**: Access controlled by user permissions

## Installation

1. Place this module in your `web/modules/custom/` directory
2. Enable the module: `drush en render_array_inspector`
3. Grant the "Access Render Array Inspector" permission to appropriate user roles
4. The "Render Array" tab will appear in the toolbar for users with permission

## Usage

1. Navigate to any page on your Drupal site
2. Click the "Render Array" tab in the toolbar
3. The tray will open showing the render array structure for the current page
4. Click on array keys to expand/collapse sections
5. Use the "Refresh" button to reload the render array data

## Configuration

Visit `/admin/config/development/render-array-inspector` to configure:

- **Maximum depth**: Control how deep the tree traversal goes
- **Auto-expand first level**: Automatically expand the first level of the tree
- **Show cache information**: Include cache-related data in the display

## Permissions

- **Access Render Array Inspector**: Required to see and use the toolbar panel

## Technical Details

The module works by:

1. Adding a toolbar item with an AJAX-powered tray
2. Capturing the current page's render array through the controller system
3. Formatting the array into a hierarchical tree structure
4. Providing interactive JavaScript for expanding/collapsing tree nodes

## Requirements

- Drupal 10 or 11
- Toolbar module (core)
- System module (core)

## Development

This module is intended for development and debugging purposes. It should not be enabled on production sites as it can expose internal application structure.

## Support

This is a custom development module. For issues or feature requests, contact your development team.