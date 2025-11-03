# Site Audit Integration with Site Analyzer

This document outlines the integration of Site Audit functionality into the Site Analyzer module, providing comprehensive analysis capabilities while maintaining the Site Analyzer's architecture and frontend design.

## Overview

The integration adds the following Site Audit features to Site Analyzer:

1. **Best Practices Analysis** - Structural recommendations
2. **Enhanced Cache Analysis** - Optimal Drupal caching settings  
3. **Enhanced Database Analysis** - Detailed database insights
4. **Enhanced Security Analysis** - Security exploit detection
5. **Watchdog Analysis** - 404 errors, PHP errors, log monitoring
6. **Enhanced Content Analysis** - Complete entity type analysis

## New Services Added

### 1. WatchdogAnalyzer Service
**File:** `src/Service/WatchdogAnalyzer.php`
**Route:** `/admin/reports/site-analyzer/watchdog`

**Features:**
- 404 error analysis with top URLs and recent errors
- PHP error analysis with error types breakdown
- Watchdog statistics and log age analysis
- Error trends and top error sources
- Comprehensive recommendations

**Key Methods:**
- `analyze404Errors()` - Analyzes 404 page not found errors
- `analyzePHPErrors()` - Analyzes PHP errors and warnings
- `getWatchdogStatistics()` - Gets overall watchdog statistics
- `analyzeErrorTrends()` - Analyzes error trends over time

### 2. BestPracticesAnalyzer Service
**File:** `src/Service/BestPracticesAnalyzer.php`
**Route:** `/admin/reports/site-analyzer/best-practices`

**Features:**
- Settings.php file analysis
- Folder structure validation
- Multisite setup analysis
- Services configuration review
- Fast 404 configuration
- Development modules detection
- File permissions analysis

**Key Methods:**
- `analyzeSettingsFile()` - Checks settings.php configuration
- `analyzeFolderStructure()` - Validates Drupal directory structure
- `analyzeMultisiteSetup()` - Analyzes multisite configuration
- `analyzeFilePermissions()` - Checks file and directory permissions

## Enhanced Existing Services

### 1. Enhanced DatabaseAnalyzer
**New Features Added:**
- **Collation Analysis** - Database and table collation consistency
- **Engine Analysis** - MyISAM vs InnoDB analysis
- **Fragmentation Analysis** - Database fragmentation detection
- **Row Count Analysis** - Large table identification

**New Methods:**
- `analyzeCollation()` - Checks database collation consistency
- `analyzeEngines()` - Analyzes database engines used
- `analyzeFragmentation()` - Detects table fragmentation
- `analyzeRowCounts()` - Analyzes table row counts and sizes

### 2. Enhanced SecurityAnalyzer
**New Features Added:**
- **Menu Router Security** - Malicious route detection
- **Malicious Code Scanning** - Suspicious code pattern detection
- **Security Exploit Checks** - Known vulnerability detection
- **File Upload Security** - Dangerous extension detection

**New Methods:**
- `analyzeMenuRouterSecurity()` - Scans for malicious menu routes
- `scanForMaliciousCode()` - Scans custom code for suspicious patterns
- `checkForSecurityExploits()` - Checks for known security issues
- `checkFileUploadExploits()` - Analyzes file upload security

### 3. Enhanced PerformanceAnalyzer
**New Features Added:**
- **Detailed Cache Analysis** - Comprehensive cache configuration
- **Cache Bins Analysis** - Individual cache bin analysis
- **Cache Page Expiration** - Page cache timing analysis
- **Cache Preprocessing** - CSS/JS preprocessing analysis

**New Methods:**
- `analyzeCacheDetailed()` - Detailed cache configuration analysis
- `analyzeCacheBinsDetailed()` - Individual cache bin analysis
- `analyzeCachePageExpire()` - Page cache expiration analysis
- `analyzeCachePreprocessing()` - Asset preprocessing analysis

### 4. Enhanced ContentAnalyzer
**New Features Added:**
- **All Entity Types** - Complete entity type listing
- **Detailed Entity Analysis** - Content entity types with counts
- **Unused Content Types** - Identification of unused node types
- **Unused Vocabularies** - Identification of unused taxonomies
- **Entity Bundles Analysis** - Comprehensive bundle analysis

**New Methods:**
- `analyzeAllEntityTypes()` - Lists all content and config entities
- `analyzeContentEntityTypesDetailed()` - Detailed content entity analysis
- `findUnusedContentTypes()` - Finds unused node types
- `findUnusedVocabularies()` - Finds unused taxonomy vocabularies
- `analyzeEntityBundles()` - Comprehensive bundle analysis

## Service Registration

**File:** `site_analyzer.services.yml`

Added new services:
```yaml
site_analyzer.watchdog_analyzer:
  class: Drupal\site_analyzer\Service\WatchdogAnalyzer
  arguments: ['@database', '@module_handler']

site_analyzer.best_practices_analyzer:
  class: Drupal\site_analyzer\Service\BestPracticesAnalyzer
  arguments: ['@config.factory', '@module_handler', '@file_system']
```

## Routing

**File:** `site_analyzer.routing.yml`

Added new routes:
- `site_analyzer.watchdog_analysis` - `/admin/reports/site-analyzer/watchdog`
- `site_analyzer.best_practices_analysis` - `/admin/reports/site-analyzer/best-practices`

## Controller Updates

**File:** `src/Controller/AnalyzerController.php`

Added new controller methods:
- `watchdog()` - Displays watchdog analysis
- `bestPractices()` - Displays best practices analysis
- Updated `security()` method to use enhanced security analysis

## Key Features from Site Audit

### Best Practices Checks
- ✅ Settings.php file validation
- ✅ Folder structure analysis
- ✅ Multisite configuration
- ✅ File permissions
- ✅ Development modules detection
- ✅ Fast 404 configuration

### Cache Analysis
- ✅ Page cache expiration (15+ minutes recommended)
- ✅ CSS/JS preprocessing
- ✅ Cache bins analysis
- ✅ Dynamic page cache
- ✅ Render cache analysis

### Database Enhancements
- ✅ Collation consistency
- ✅ Engine analysis (MyISAM vs InnoDB)
- ✅ Fragmentation detection
- ✅ Row count analysis
- ✅ Size analysis with recommendations

### Security Enhancements
- ✅ Menu router security (malicious callback detection)
- ✅ Malicious code pattern scanning
- ✅ File upload security
- ✅ Vulnerable module detection
- ✅ Security exploit checks

### Watchdog Analysis
- ✅ 404 error analysis with top URLs
- ✅ PHP error analysis with severity breakdown
- ✅ Log statistics and trends
- ✅ Error source identification
- ✅ Log age analysis

### Content Analysis
- ✅ All entity types listing
- ✅ Unused content types detection
- ✅ Unused vocabularies detection
- ✅ Entity bundle analysis
- ✅ Content volume analysis

## Recommendations System

Each enhanced service provides structured recommendations:

```php
[
  'type' => 'error|warning|info',
  'category' => 'specific_category',
  'message' => 'Detailed recommendation message',
  'priority' => 'critical|high|medium|low',
]
```

## Frontend Integration

The integration maintains the Site Analyzer's existing frontend design and templates. New analysis data is passed to templates using the same pattern:

```php
return [
  '#theme' => 'site_analyzer_[analysis_type]',
  '#[analysis_type]_data' => $analysis_data,
  '#attached' => [
    'library' => ['site_analyzer/analyzer'],
  ],
];
```

## Usage

1. **Access Watchdog Analysis:**
   - Navigate to `/admin/reports/site-analyzer/watchdog`
   - View 404 errors, PHP errors, and log statistics

2. **Access Best Practices Analysis:**
   - Navigate to `/admin/reports/site-analyzer/best-practices`
   - Review structural recommendations and configuration

3. **Enhanced Existing Pages:**
   - Database page now includes collation, engine, and fragmentation analysis
   - Security page includes malicious code scanning and exploit detection
   - Performance page includes detailed cache analysis
   - Content page includes comprehensive entity analysis

## Benefits

1. **Comprehensive Analysis** - Combines Site Analyzer's architecture with Site Audit's detailed checks
2. **Unified Interface** - All analysis in one module with consistent UI
3. **Enhanced Recommendations** - Structured recommendations for each analysis area
4. **Maintainable Code** - Clean service-based architecture
5. **Extensible Design** - Easy to add more Site Audit features in the future

## Future Enhancements

The integration provides a foundation for adding more Site Audit features:
- Cron analysis
- Block caching analysis
- Views caching analysis
- Extension analysis (duplicates, missing modules)
- Google PageSpeed Insights integration
- Status report integration
- User analysis enhancements

This integration successfully brings Site Audit's comprehensive checking capabilities into the Site Analyzer module while maintaining the existing architecture and frontend design.