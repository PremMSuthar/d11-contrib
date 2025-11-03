# Site Analyzer Module

A comprehensive site analysis tool for Drupal upgrade planning and development insights.

## Overview

The Site Analyzer module provides detailed analysis of your Drupal site's health, performance, security, and upgrade readiness. It's designed to help developers, site administrators, and agencies make informed decisions about site maintenance and Drupal upgrades.

## Features

### Core Analysis Areas

1. **System Information**
   - Drupal Core Version (exact version, release date)
   - PHP Version (current, minimum required for target version)
   - Database Type & Version (MySQL, PostgreSQL, etc.)
   - Web Server (Apache, Nginx version)
   - Operating System details
   - Memory Usage (current, peak, available)
   - Disk Space (used, available, database size)

2. **Module Analysis**
   - Installed Modules List (enabled/disabled status)
   - Custom vs Contributed module identification
   - Module Versions (current vs latest available)
   - Compatibility Status for target Drupal version
   - Security Updates needed
   - Deprecated Modules identification
   - Module Dependencies mapping
   - Custom Module Code Analysis (hooks used, deprecated functions)

3. **Theme Analysis**
   - Active Themes (admin + frontend)
   - Theme Type (custom/contributed/core)
   - Theme Compatibility with target version
   - Template Overrides inventory
   - CSS/JS Assets analysis
   - Theme Dependencies
   - Responsive Framework used

4. **Content Analysis**
   - Content Types (count, fields, usage)
   - Field Types inventory (deprecated fields detection)
   - Content Volume (nodes, users, comments, files)
   - Media Usage (file types, storage locations)
   - Taxonomy Structure (vocabularies, terms count)
   - Menu Structure analysis
   - Block Usage (custom blocks, views blocks)

5. **Database Analysis**
   - Table Structure comparison
   - Database Size breakdown by table
   - Custom Tables identification
   - Data Integrity checks
   - Index Analysis
   - Query Performance insights
   - Migration Requirements assessment

6. **Code Quality Analysis**
   - Custom Code Review (modules, themes)
   - Deprecated Functions usage
   - Security Vulnerabilities scan
   - Coding Standards compliance
   - Performance Bottlenecks identification
   - API Changes impact assessment

7. **Performance Metrics**
   - Page Load Times analysis
   - Database Query performance
   - Memory Usage patterns
   - Cache Hit Rates
   - File System performance
   - Third-party Integration response times

8. **Security Assessment**
   - Security Updates status
   - Permission Audit
   - File Permissions check
   - SSL Configuration
   - Input Validation analysis
   - Access Control review

9. **Upgrade Readiness Report**
   - Compatibility Matrix (modules, themes, custom code)
   - Breaking Changes identification
   - Migration Path recommendations
   - Effort Estimation (hours, complexity level)
   - Risk Assessment (high/medium/low risk items)
   - Timeline Suggestions

## Installation

1. Download or clone the module to your `modules/custom` directory
2. Enable the module: `drush en site_analyzer`
3. Configure the module at `/admin/config/development/site-analyzer`
4. Set appropriate permissions for user roles

## Usage

### Dashboard

Navigate to **Administration » Reports » Site Analyzer** to access the main dashboard. The dashboard provides:

- Quick health overview of all system areas
- Status indicators with color-coded alerts
- Key metrics and statistics
- Direct links to detailed analysis reports

### Detailed Reports

Access specific analysis reports through the dashboard or direct URLs:

- **System Analysis**: `/admin/reports/site-analyzer/system`
- **Module Analysis**: `/admin/reports/site-analyzer/modules`
- **Theme Analysis**: `/admin/reports/site-analyzer/themes`
- **Content Analysis**: `/admin/reports/site-analyzer/content`
- **Database Analysis**: `/admin/reports/site-analyzer/database`
- **Security Analysis**: `/admin/reports/site-analyzer/security`
- **Performance Analysis**: `/admin/reports/site-analyzer/performance`
- **Upgrade Report**: `/admin/reports/site-analyzer/upgrade`

### Export Options

Reports can be exported in multiple formats:
- **JSON**: Machine-readable format for API integration
- **CSV**: Spreadsheet format for data analysis
- **PDF**: Professional reports for clients (requires additional setup)

## Configuration

### General Settings

- **Analysis Depth**: Choose between Quick Scan, Standard Analysis, or Deep Dive
- **Module Configuration**: Mark as configured to remove setup warnings

### Automated Monitoring

- **Enable Monitoring**: Run analysis automatically during cron
- **Monitoring Interval**: How often to run automated analysis
- **Report Retention**: How long to keep automated reports

### Alert Settings

- **Email Alerts**: Send notifications when critical issues are detected
- **Alert Thresholds**: Configure when to trigger alerts (memory, disk, security)
- **Alert Recipients**: Email addresses to receive notifications

### Performance Settings

- **Execution Limits**: Control analysis execution time and memory usage
- **Batch Processing**: Configure batch sizes for large datasets

### Security Settings

- **Code Scanning**: Enable/disable security vulnerability scanning
- **Scan Scope**: Choose which custom code to analyze

### Integration Settings

- **Drupal.org API**: Check for module updates and security advisories
- **API Timeouts**: Configure external API request timeouts

### Report Branding

- **Custom Logo**: Upload logo for branded reports
- **Company Information**: Add company details to reports
- **Custom Footer**: Add custom footer text to reports

## Permissions

The module defines three permission levels:

1. **Access Site Analyzer**: View analysis reports and dashboard
2. **Export Site Analyzer Reports**: Export reports in various formats
3. **Administer Site Analyzer**: Configure module settings and advanced options

## API Integration

The module provides RESTful API endpoints for external integration:

- `GET /admin/reports/site-analyzer/export/json`: Full analysis in JSON format
- `GET /admin/reports/site-analyzer/ajax-refresh`: Dashboard data refresh

## Automated Monitoring

When enabled, the module will:

1. Run analysis during cron operations
2. Store results for historical comparison
3. Send email alerts when thresholds are exceeded
4. Maintain report history based on retention settings

## Upgrade Planning

The upgrade readiness report provides:

- **Compatibility Matrix**: Detailed compatibility status for all modules and themes
- **Breaking Changes**: Specific code changes required for upgrade
- **Effort Estimation**: Time estimates broken down by category
- **Risk Assessment**: Identified risks and mitigation strategies
- **Timeline Suggestions**: Phased approach to upgrade planning
- **Preparation Checklist**: Step-by-step tasks for upgrade preparation

## Troubleshooting

### Common Issues

1. **Memory Limit Errors**: Increase PHP memory limit or adjust batch sizes
2. **Timeout Issues**: Increase max execution time or use Quick Scan mode
3. **Permission Errors**: Ensure proper file permissions for analysis
4. **Missing Data**: Check that required modules are enabled

### Performance Optimization

- Use Quick Scan mode for regular monitoring
- Adjust batch sizes for large sites
- Enable caching for better performance
- Schedule automated analysis during low-traffic periods

## Development

### Extending the Module

The module is designed to be extensible:

1. **Custom Analyzers**: Create new analyzer services implementing the analysis interface
2. **Additional Reports**: Add new report types and templates
3. **Export Formats**: Implement additional export formats
4. **Integration Hooks**: Use provided hooks for custom functionality

### Code Structure

```
site_analyzer/
├── src/
│   ├── Controller/          # Page controllers
│   ├── Service/            # Analysis services
│   └── Form/               # Configuration forms
├── templates/              # Twig templates
├── css/                   # Stylesheets
├── js/                    # JavaScript
└── config/install/        # Default configuration
```

## Requirements

- **Drupal**: 10.x or 11.x
- **PHP**: 8.1+ (8.2+ recommended)
- **Database**: MySQL 5.7+ or PostgreSQL 10+
- **Memory**: 256MB+ recommended for analysis
- **Disk Space**: Varies based on site size and report retention

## Support

For issues, feature requests, or contributions:

1. Check the module's issue queue
2. Review documentation and troubleshooting guides
3. Contact the module maintainers

## License

This module is licensed under the GNU General Public License v2.0 or later.

## Credits

Developed by the Drupal community for comprehensive site analysis and upgrade planning.