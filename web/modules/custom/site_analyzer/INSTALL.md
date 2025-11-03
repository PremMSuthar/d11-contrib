# Site Analyzer Module - Installation Guide

## Prerequisites

- Drupal 10.x or 11.x
- PHP 8.1+ (8.2+ recommended)
- MySQL 5.7+ or PostgreSQL 10+
- Minimum 256MB PHP memory limit
- Write permissions for files directory

## Installation Steps

### 1. Enable the Module

#### Via Drupal Admin Interface:
1. Navigate to **Extend** (`/admin/modules`)
2. Find "Site Analyzer" in the Custom section
3. Check the box next to "Site Analyzer"
4. Click "Install"

#### Via Drush (if available):
```bash
drush en site_analyzer
```

### 2. Configure Permissions

1. Navigate to **People > Permissions** (`/admin/people/permissions`)
2. Configure the following permissions as needed:

   **Site Analyzer Permissions:**
   - **Access Site Analyzer**: View analysis reports and dashboard
   - **Export Site Analyzer Reports**: Export reports in various formats  
   - **Administer Site Analyzer**: Configure module settings

   **Recommended Role Assignments:**
   - **Site Administrator**: All permissions
   - **Developer**: Access + Export permissions
   - **Content Manager**: Access permission only

### 3. Configure Module Settings

1. Navigate to **Configuration > Development > Site Analyzer** (`/admin/config/development/site-analyzer`)
2. Configure the following settings:

   **General Settings:**
   - ✅ Check "Module is configured"
   - Set "Default Analysis Depth" (Quick/Standard/Deep)

   **Automated Monitoring:**
   - Enable if you want automatic analysis during cron
   - Set monitoring interval (hourly to monthly)

   **Alert Settings:**
   - Configure email alerts for critical issues
   - Set alert thresholds for memory/disk usage

   **Performance Settings:**
   - Adjust execution time and memory limits
   - Configure batch sizes for large sites

   **Security Settings:**
   - Enable code scanning for custom modules/themes
   - Configure scan scope

### 4. Access the Dashboard

1. Navigate to **Reports > Site Analyzer** (`/admin/reports/site-analyzer`)
2. The dashboard will display:
   - System health overview
   - Module analysis summary
   - Security status
   - Performance metrics
   - Upgrade readiness

## Quick Start Guide

### 1. Initial Analysis
- Visit the dashboard to see overall site health
- Click on any card to view detailed analysis
- Review recommendations for immediate actions

### 2. Module Analysis
- Go to **Module Analysis** for Drupal 11 readiness
- Use "Scan Module" buttons for detailed code analysis
- Export results for planning purposes

### 3. Security Review
- Check **Security Analysis** for vulnerabilities
- Review permission audit results
- Address any critical security issues

### 4. Performance Optimization
- Review **Performance Analysis** recommendations
- Enable suggested caching and optimization
- Monitor memory and disk usage

### 5. Upgrade Planning
- Use **Upgrade Report** for Drupal 11 migration
- Review compatibility matrix
- Plan upgrade timeline based on effort estimation

## Troubleshooting

### Common Issues

1. **Memory Limit Errors**
   - Increase PHP memory limit to 512M or higher
   - Use "Quick Scan" mode for large sites
   - Adjust batch sizes in settings

2. **Timeout Issues**
   - Increase max execution time
   - Use automated monitoring instead of manual scans
   - Process analysis in smaller batches

3. **Permission Errors**
   - Ensure proper file permissions for analysis
   - Check that required modules are enabled
   - Verify user has appropriate permissions

4. **Missing Data**
   - Clear all caches after installation
   - Run cron to populate initial data
   - Check that dependencies are met

### Performance Tips

- Use "Quick Scan" for regular monitoring
- Enable automated monitoring during low-traffic periods
- Export large reports rather than viewing in browser
- Clear old reports regularly based on retention settings

## Support

For issues, feature requests, or contributions:
1. Check the module's documentation
2. Review troubleshooting guides
3. Contact the module maintainers

## Security Notes

- Limit access to authorized personnel only
- Be cautious with "Include sensitive data" option
- Regularly review and update alert settings
- Monitor for security updates and apply promptly