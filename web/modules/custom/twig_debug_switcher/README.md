# Twig Debug Switcher

A Drupal module that allows developers to easily toggle Twig debug mode on and off from the admin interface.

## Features

- **Easy Toggle**: Enable/disable Twig debug mode with a simple checkbox or button click
- **Admin Interface**: Accessible through the admin menu at Administration » Configuration » Development » Twig Debug Switcher
- **Current Status Display**: Shows the current state of Twig debug mode
- **Safety Warnings**: Includes warnings about using only in development environments
- **Permissions**: Restricted access with dedicated permission
- **Environment Detection**: Warns if used in what appears to be a production environment

## What Twig Debug Mode Does

When enabled, Twig debug mode:
- Adds HTML comments to templates showing which template files are being used
- Enables auto-reload, so template changes are immediately visible without clearing cache
- Helps developers identify which template files to modify when theming
- Shows template suggestions and the template hierarchy

## Installation

1. The module is already in your `web/modules/custom/` directory
2. Enable the module: `drush en twig_debug_switcher`
3. Clear cache: `drush cr`
4. Grant the "Administer Twig Debug Switcher" permission to appropriate roles
5. Navigate to Administration » Configuration » Development » Twig Debug Switcher

### Quick Start
```bash
# Enable the module
drush en twig_debug_switcher

# Clear cache
drush cr

# Enable debug mode via Drush
drush twig-debug:enable

# Check status
drush twig-debug:status
```

## Usage

### Via Admin Interface
1. Go to Administration » Configuration » Development » Twig Debug Switcher
2. Check/uncheck the "Enable Twig Debug Mode" checkbox, or
3. Use the "Enable Debug" or "Disable Debug" buttons for quick toggling

### Permissions
- Grant the "Administer Twig Debug Switcher" permission to developers who need access
- This permission is restricted by default for security

## Security Considerations

⚠️ **WARNING**: This module should ONLY be used in development environments. Never enable this in production!

The module includes several safety features:
- Environment detection that warns if preprocessing is enabled (indicating possible production use)
- Clear warnings in the admin interface
- Restricted permissions
- Runtime requirements check

## Requirements

- Drupal 10 or 11
- Development environment (not recommended for production)

## Technical Details

The module works by:
1. Storing the debug state in configuration (`twig_debug_switcher.settings`)
2. Using a service (`TwigDebugManager`) to manage Twig environment settings
3. Providing a form interface for easy toggling
4. Integrating with Drupal's Twig service to enable/disable debug features

## Troubleshooting

- **Changes not visible**: Clear the cache after toggling debug mode
- **Permission denied**: Ensure the user has the "Administer Twig Debug Switcher" permission
- **Module not working**: Check that you're in a development environment and not production
- **"getEnvironment() method not found" error**: This has been fixed in the latest version - the module now correctly targets the Drupal Twig service directly

### Recent Fixes
- Fixed `Call to undefined method getEnvironment()` error by correctly targeting the Drupal Twig service
- Improved reflection-based property modification for better compatibility
- Enhanced error handling to prevent site breakage

## Development

This module follows Drupal coding standards and includes:
- Proper dependency injection
- Configuration schema
- Permissions system
- Menu integration
- Help documentation
- Environment safety checks

## License

This module is released under the same license as Drupal core.