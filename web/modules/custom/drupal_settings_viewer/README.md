# Drupal Settings Viewer

A Drupal 10/11 contrib module that provides a toolbar item for developers to view the `drupalSettings` object for the current page.

## Description

The Drupal Settings Viewer module adds a "Settings" item to the Drupal toolbar that allows authorized users to view the current page's `drupalSettings` object in a formatted, read-only display. This is particularly useful for:

- Debugging JavaScript behaviors
- Understanding what data is available to frontend scripts
- Inspecting configuration passed from PHP to JavaScript
- Development and troubleshooting

## Features

- **Secure Access Control**: Only users with the "Access Drupal Settings Viewer" permission can view settings
- **Toolbar Integration**: Seamlessly integrates with Drupal's admin toolbar
- **Pretty-Printed JSON**: Displays settings in a readable, formatted JSON structure
- **Copy to Clipboard**: One-click copying of the entire settings object
- **Responsive Design**: Works well on desktop and mobile devices
- **Real-time Data**: Shows the actual `drupalSettings` object for the current page

## Installation

### Via Composer (Recommended)

```bash
composer require drupal/drupal_settings_viewer
```

### Manual Installation

1. Download the module and place it in your `modules/contrib` directory
2. Enable the module via Drush or the admin interface:

```bash
drush en drupal_settings_viewer
```

Or navigate to **Extend** (`/admin/modules`) in your Drupal admin interface and enable "Drupal Settings Viewer".

## Configuration

### Permissions

After installation, you need to grant the appropriate permission:

1. Navigate to **People > Permissions** (`/admin/people/permissions`)
2. Find the "Drupal Settings Viewer" section
3. Grant the "Access Drupal Settings Viewer" permission to appropriate roles

**⚠️ Security Warning**: Only grant this permission to trusted developers and administrators. The `drupalSettings` object may contain sensitive configuration data.

### Recommended Roles

- **Developer**: Full access for debugging and development
- **Site Administrator**: Access for troubleshooting
- **Anonymous/Authenticated User**: ❌ **NOT RECOMMENDED**

## Usage

1. **Access the Viewer**: Once enabled and permissions are set, you'll see a "Settings" item in the toolbar
2. **View Settings**: Click the toolbar item to open a dropdown tray showing the current page's `drupalSettings`
3. **Inspect Data**: Browse the expandable tree view of settings organized by key
4. **Interact with Data**: 
   - Click arrows to expand/collapse sections
   - Use "Expand All" / "Collapse All" to toggle all sections
   - Use "Refresh" to reload current settings
   - Use "Copy All" to copy the entire settings object to clipboard
5. **Alternative View**: Visit `/admin/development/settings-viewer` for a full-page JSON view

### Example Screenshot

```
[Toolbar] Home | Content | Structure | Appearance | Extend | Configuration | People | Reports | Help | Settings
                                                                                                              ^^^^^^^^
```

*The "Settings" item appears at the end of the toolbar for authorized users.*

## Security Considerations

### Important Security Notes

- **Development Only**: This module is intended for development and debugging purposes
- **Sensitive Data**: The `drupalSettings` object may contain:
  - API keys and tokens
  - Database connection details
  - Internal configuration
  - User-specific data
- **Access Control**: Always restrict access to trusted users only
- **Production Use**: Consider disabling this module on production sites

### Best Practices

1. **Restrict Permissions**: Only grant access to developers and administrators
2. **Environment-Specific**: Consider enabling only in development/staging environments
3. **Regular Audits**: Periodically review who has access to this functionality
4. **Data Sanitization**: Be aware of what sensitive data might be exposed

## Technical Details

### System Requirements

- **Drupal**: 10.x or 11.x
- **PHP**: 8.1+ (as required by Drupal 10/11)
- **Dependencies**: 
  - `drupal:toolbar` (core)
  - `drupal:user` (core)

### Architecture

- **Controller**: `SettingsViewerController` handles the main page display
- **Toolbar Plugin**: Integrates with Drupal's toolbar system
- **JavaScript**: Client-side formatting and clipboard functionality
- **Permissions**: Custom permission system for access control
- **Theming**: Twig template with responsive CSS

### File Structure

```
drupal_settings_viewer/
├── css/
│   └── toolbar-icon.css
├── js/
│   └── settings-viewer.js
├── src/
│   ├── Controller/
│   │   └── SettingsViewerController.php
│   └── Plugin/
│       └── Toolbar/
│           └── SettingsViewerToolbar.php
├── templates/
│   └── drupal-settings-viewer.html.twig
├── drupal_settings_viewer.info.yml
├── drupal_settings_viewer.libraries.yml
├── drupal_settings_viewer.module
├── drupal_settings_viewer.permissions.yml
├── drupal_settings_viewer.routing.yml
└── README.md
```

## Development

### Contributing

This module follows Drupal coding standards and best practices:

- **PSR-4 Autoloading**: Proper namespace structure
- **Dependency Injection**: Services injected via container
- **Security**: XSS protection and access control
- **Documentation**: Comprehensive docblocks
- **Testing**: Ready for automated testing

### Extending the Module

The module is designed to be extensible:

- **Custom Themes**: Override the `drupal-settings-viewer.html.twig` template
- **Additional Data**: Extend the controller to show additional debugging information
- **Custom Permissions**: Add role-specific access controls
- **API Integration**: Add endpoints for programmatic access

## Troubleshooting

### Common Issues

**Q: The toolbar item doesn't appear**
A: Check that the user has the "Access Drupal Settings Viewer" permission and that the toolbar module is enabled.

**Q: The page shows "Loading..." but never loads**
A: Check browser console for JavaScript errors. Ensure the module's JavaScript library is loading correctly.

**Q: Settings appear empty or minimal**
A: This is normal for pages with minimal JavaScript. Try viewing a more complex page like a node edit form.

**Q: Copy to clipboard doesn't work**
A: This feature requires a modern browser with clipboard API support or HTTPS. Older browsers may not support this functionality.

### Debug Steps

1. **Check Permissions**: Verify user has correct permissions
2. **Clear Cache**: Clear Drupal caches after installation
3. **Browser Console**: Check for JavaScript errors
4. **Module Status**: Ensure module is properly enabled

## License

This project is licensed under the GPL-2.0+ license - see the [LICENSE](LICENSE) file for details.

## Support

- **Issue Queue**: [drupal.org/project/drupal_settings_viewer/issues](https://drupal.org/project/drupal_settings_viewer/issues)
- **Documentation**: [drupal.org/docs/contributed-modules/drupal-settings-viewer](https://drupal.org/docs/contributed-modules/drupal-settings-viewer)

## Changelog

### 1.0.0
- Initial release
- Toolbar integration
- Basic settings viewer
- Permission-based access control
- Copy to clipboard functionality