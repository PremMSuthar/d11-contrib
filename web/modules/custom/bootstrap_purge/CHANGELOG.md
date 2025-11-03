# Changelog

All notable changes to the Bootstrap Purge module will be documented in this file.

## [1.0.0] - 2024-01-01

### Added
- Initial release of Bootstrap Purge module
- Static CSS analysis using PurgeCSS or PHP-based parsing
- Runtime data collection for improved accuracy
- Comprehensive admin interface with dashboard and asset management
- Drush commands for CLI automation
- Whitelist management with pattern testing
- Bootstrap-specific optimizations and safe defaults
- Confidence scoring and automatic rollback capabilities
- Privacy-compliant data collection with anonymization
- Support for both Node.js/PurgeCSS and PHP-only environments

### Features
- Asset collection from themes and modules
- Multiple analysis modes (static, runtime, combined)
- Diff viewer for reviewing changes
- Cron integration for automated processing
- Extensive logging and audit trails
- Performance monitoring and statistics
- Theme-specific whitelist patterns
- CI/CD integration support

### Security
- Anonymized IP address collection
- Configurable data retention periods
- Safe whitelist patterns for Bootstrap components
- Automatic reversion on detected issues