# Changelog

All notable changes to the Twig Debug Switcher module will be documented in this file.

## [1.0.1] - 2024-01-XX

### Fixed
- Fixed `Call to undefined method Drupal\Core\Template\TwigEnvironment::getEnvironment()` error
- Corrected Twig service interaction - the Drupal Twig service IS the environment, not a wrapper
- Improved reflection-based property modification for better compatibility
- Enhanced error handling in all components

### Changed
- Updated all components to directly target the Twig service instead of calling getEnvironment()
- Improved error messages and logging

## [1.0.0] - 2024-01-XX

### Added
- Initial release of Twig Debug Switcher module
- Admin interface for toggling Twig debug mode on/off
- TwigDebugManager service for managing debug settings
- Configuration form with current status display
- Quick action buttons for enable/disable
- Safety warnings for production environments
- Environment detection and warnings
- Permissions system with restricted access
- Menu integration in Development section
- CSS styling for admin interface
- Drush commands for command-line usage:
  - `drush twig-debug:enable` (alias: `tde`)
  - `drush twig-debug:disable` (alias: `tdd`)
  - `drush twig-debug:status` (alias: `tds`)
- Comprehensive help documentation
- Functional tests for core functionality
- Install/uninstall hooks with cleanup
- Configuration schema
- README documentation

### Features
- Toggle Twig debug mode from admin UI
- Real-time status display
- Environment safety checks
- Permission-based access control
- Command-line interface via Drush
- Automatic cleanup on uninstall
- Development-focused design

### Security
- Restricted permissions by default
- Production environment warnings
- Runtime requirements checking
- Safe uninstall process