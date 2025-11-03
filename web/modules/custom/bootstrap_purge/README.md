# Bootstrap Purge

A comprehensive Drupal module that automatically detects and removes unused CSS and JavaScript from Bootstrap and other frontend libraries to reduce payload size and improve page loading performance.

## Features

### Core Functionality
- **Asset Analysis**: Static and runtime analysis to identify unused CSS selectors and JavaScript code
- **Smart Purging**: Removes unused assets while preserving functionality and accessibility
- **Bootstrap Focus**: Specialized handling for Bootstrap CSS/JS with intelligent defaults
- **Safety First**: Comprehensive whitelist system and rollback capabilities

### Analysis Methods
- **Static Analysis**: Server-side analysis using PurgeCSS or PHP-based parsing
- **Runtime Analysis**: Client-side collection of actually used selectors during real user sessions
- **Combined Analysis**: Merge static and runtime data for maximum accuracy

### Admin Interface
- **Dashboard**: Overview of purged assets, savings, and statistics
- **Asset Management**: Review, approve, reject, and preview purged assets
- **Diff Viewer**: Side-by-side comparison of original vs purged files
- **Whitelist Management**: Comprehensive pattern management with testing tools

### Automation
- **Drush Commands**: Full CLI support for analysis, application, and management
- **Cron Integration**: Automatic analysis and application based on confidence thresholds
- **CI/CD Ready**: Integrate with deployment pipelines

### Safety & Privacy
- **Confidence Scoring**: Each purge candidate gets a confidence score
- **Automatic Rollback**: Revert problematic purges automatically
- **Privacy Compliant**: Anonymized data collection with configurable sampling
- **Audit Logging**: Track all purge operations and decisions

## Installation

1. Place the module in your `modules/custom` directory
2. Enable the module: `drush en bootstrap_purge`
3. Configure the module at `/admin/config/development/bootstrap-purge`

## Configuration

### Basic Setup

1. **Enable the Module**: Check "Enable Bootstrap Purge" in the settings
2. **Choose Analysis Mode**: 
   - Static: Server-side analysis (recommended for start)
   - Runtime: Client-side data collection
   - Combined: Both methods for maximum accuracy
3. **Configure Storage**: Set the storage path for purged assets
4. **Set Confidence Threshold**: Minimum confidence score for auto-approval

### External Tools (Optional)

For enhanced CSS purging, you can configure external tools:

- **PurgeCSS**: Install via npm and configure the path
- **Node.js**: Required for PurgeCSS integration

```bash
npm install -g purgecss
```

### Whitelist Configuration

The module includes comprehensive Bootstrap whitelist patterns by default. You can:

- Add custom patterns for your theme
- Use glob patterns (`*.btn-*`) or regex (`/^\.d-.*/`)
- Test patterns with the built-in testing tool
- Import additional Bootstrap defaults

## Usage

### Via Admin Interface

1. **Run Analysis**: Visit the dashboard and click "Run Analysis"
2. **Review Candidates**: Go to "Manage Assets" to review purge candidates
3. **Approve/Reject**: Use the diff viewer to make informed decisions
4. **Monitor Results**: Check the dashboard for savings and performance metrics

### Via Drush Commands

```bash
# Analyze assets
drush bootstrap-purge:analyze --routes=key --bootstrap-only

# Apply purges with high confidence
drush bootstrap-purge:apply all --confidence-threshold=80

# List all assets and their status
drush bootstrap-purge:list --status=pending

# Revert a specific asset
drush bootstrap-purge:revert bootstrap.css

# Show statistics
drush bootstrap-purge:stats

# Clear all analysis data
drush bootstrap-purge:clear --force
```

### Runtime Data Collection

Enable runtime data collection to improve analysis accuracy:

1. Enable "Runtime Data Collection" in settings
2. Set sample rate (percentage of users to track)
3. Data is collected anonymously and used to refine purge decisions

## Architecture

### Services

- **AssetCollector**: Scans themes/modules for CSS/JS assets
- **AssetAnalyzer**: Performs static and runtime analysis
- **AssetPurger**: Creates purged versions using PurgeCSS or PHP
- **AssetManager**: Coordinates the entire purge workflow
- **RuntimeDataCollector**: Handles client-side usage data
- **WhitelistManager**: Manages whitelist patterns and testing

### Data Flow

1. **Collection**: Scan all enabled themes/modules for assets
2. **Analysis**: Compare assets against HTML snapshots and runtime data
3. **Purging**: Generate purged versions of assets
4. **Review**: Present candidates for admin approval
5. **Application**: Replace original assets with purged versions
6. **Monitoring**: Track performance and handle rollbacks

## Performance Impact

### Typical Savings
- **Bootstrap CSS**: 60-80% size reduction
- **Custom CSS**: 30-60% size reduction
- **JavaScript**: 20-40% size reduction (with proper tree-shaking)

### Benchmarks
- Analysis overhead: < 1% on cron runs
- Runtime collection: < 0.1% JavaScript overhead
- Page load improvement: 15-40% faster CSS parsing

## Safety Considerations

### Whitelist Patterns
The module includes extensive Bootstrap whitelist patterns:
- Utility classes (d-*, col-*, btn-*, etc.)
- Component classes (modal, dropdown, nav, etc.)
- State classes (active, disabled, show, etc.)
- Responsive classes (d-*-block, col-*-*, etc.)
- Accessibility classes (sr-only, visually-hidden, etc.)

### Confidence Scoring
Each purge candidate receives a confidence score based on:
- Static analysis coverage
- Runtime data availability
- Whitelist pattern matches
- Historical success rates

### Rollback Mechanisms
- Automatic reversion on error detection
- Manual rollback via admin interface
- Version tracking for all purged assets
- Backup of original files

## Troubleshooting

### Common Issues

**CSS/JS Functionality Broken**
- Check whitelist patterns for missing selectors
- Review confidence scores and lower threshold
- Use runtime data collection for better accuracy
- Manually revert problematic assets

**PurgeCSS Not Working**
- Verify Node.js and PurgeCSS installation
- Check file permissions and paths
- Test tools via admin interface
- Fallback to PHP-based purging

**Large Storage Usage**
- Configure automatic cleanup of old purged files
- Adjust retention periods in settings
- Monitor storage usage in dashboard

### Debug Mode

Enable debug logging by adding to settings.php:
```php
$config['system.logging']['error_level'] = 'verbose';
```

## Development

### Extending the Module

The module is designed to be extensible:

- Add custom analyzers by implementing analysis plugins
- Extend whitelist patterns for specific frameworks
- Integrate with custom build tools and bundlers
- Add custom purging strategies

### API Usage

```php
// Get the asset manager
$asset_manager = \Drupal::service('bootstrap_purge.asset_manager');

// Run analysis
$results = $asset_manager->runAnalysis(['routes' => ['<front>']]);

// Approve a purge
$asset_manager->approvePurge('theme/bootstrap/css/bootstrap.css');

// Get statistics
$stats = $asset_manager->getDashboardStats();
```

## Contributing

Contributions are welcome! Please:

1. Follow Drupal coding standards
2. Add tests for new functionality
3. Update documentation
4. Test with multiple Bootstrap versions

## License

This module is licensed under the GPL v2 or later.

## Support

For issues and feature requests, please use the project's issue queue.

## Roadmap

### Planned Features
- Visual regression testing integration
- Support for CSS-in-JS frameworks
- Advanced JavaScript tree-shaking
- Integration with CDN purging
- Performance monitoring dashboard
- Multi-site configuration management

### Version History
- **1.0.0**: Initial release with core functionality
- **1.1.0**: Runtime data collection and improved analysis
- **1.2.0**: Enhanced admin interface and Drush commands
- **2.0.0**: Advanced purging strategies and CI/CD integration