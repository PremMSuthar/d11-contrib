# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2024-01-01

### Added
- Initial release of Drupal Settings Viewer module
- Toolbar integration with "Settings" item
- Settings viewer page at `/admin/development/settings-viewer`
- Permission-based access control with "access drupal settings viewer" permission
- Pretty-printed JSON display of drupalSettings object
- Copy to clipboard functionality
- Responsive design with clean UI
- Security warnings and best practices documentation
- Comprehensive README with installation and usage instructions
- Functional tests for access control and toolbar integration
- Support for Drupal 10 and 11

### Security
- Restricted access through custom permission system
- XSS protection for rendered content
- Security warnings in installation and documentation

### Technical
- PSR-4 compliant code structure
- Dependency injection for services
- Proper Drupal coding standards
- Comprehensive docblocks
- Twig templating system
- CSS and JavaScript libraries
- Composer package configuration