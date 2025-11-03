# Bootstrap Purge Module Structure

## Core Files
```
bootstrap_purge/
├── bootstrap_purge.info.yml          # Module definition
├── bootstrap_purge.module            # Main module hooks
├── bootstrap_purge.routing.yml       # Route definitions
├── bootstrap_purge.permissions.yml   # Permission definitions
├── bootstrap_purge.services.yml      # Service definitions
├── bootstrap_purge.libraries.yml     # Asset libraries
├── bootstrap_purge.install           # Install/uninstall hooks
├── bootstrap_purge.links.menu.yml    # Menu links
├── bootstrap_purge.links.task.yml    # Local task links
├── drush.services.yml                # Drush command services
└── README.md                         # Documentation
```

## Source Code
```
src/
├── Controller/
│   ├── BootstrapPurgeController.php  # Main admin controller
│   └── RuntimeDataController.php     # Runtime data collection
├── Form/
│   ├── BootstrapPurgeSettingsForm.php # Settings form
│   └── WhitelistForm.php             # Whitelist management
├── Service/
│   ├── AssetCollector.php            # Asset discovery
│   ├── AssetAnalyzer.php             # Analysis engine
│   ├── AssetPurger.php               # Purging logic
│   ├── AssetManager.php              # Coordination service
│   ├── RuntimeDataCollector.php      # Runtime data handling
│   └── WhitelistManager.php          # Whitelist management
└── Commands/
    └── BootstrapPurgeCommands.php     # Drush commands
```

## Configuration
```
config/
├── install/
│   ├── bootstrap_purge.settings.yml  # Default settings
│   └── bootstrap_purge.whitelist.yml # Default whitelist
└── schema/
    └── bootstrap_purge.schema.yml     # Configuration schema
```

## Frontend Assets
```
js/
├── runtime-collector.js              # Client-side data collection
└── admin-ui.js                       # Admin interface enhancements

css/
└── admin-ui.css                      # Admin interface styling

templates/
├── bootstrap-purge-dashboard.html.twig    # Dashboard template
└── bootstrap-purge-asset-diff.html.twig   # Diff viewer template
```

## Optional Node.js Integration
```
package.json                          # Node.js dependencies
scripts/
└── purge-runner.js                   # PurgeCSS integration script

examples/
└── purgecss.config.js               # Example PurgeCSS config
```

## Key Features Implemented

### 1. Asset Management
- **AssetCollector**: Scans themes/modules for CSS/JS files
- **Library Integration**: Works with Drupal's library system
- **Bootstrap Detection**: Identifies Bootstrap-related assets

### 2. Analysis Engine
- **Static Analysis**: Server-side CSS selector analysis
- **Runtime Collection**: Client-side usage tracking
- **Combined Analysis**: Merges both approaches
- **Confidence Scoring**: Rates purge safety

### 3. Purging System
- **PurgeCSS Integration**: Optional Node.js tool integration
- **PHP Fallback**: Pure PHP implementation
- **Safe Defaults**: Bootstrap-aware whitelist patterns
- **Rollback Support**: Revert problematic purges

### 4. Admin Interface
- **Dashboard**: Statistics and overview
- **Asset Management**: Review and approve purges
- **Diff Viewer**: Compare original vs purged files
- **Whitelist Management**: Pattern testing and management

### 5. Automation
- **Drush Commands**: Full CLI support
- **Cron Integration**: Automated analysis and application
- **CI/CD Ready**: Deployment pipeline integration

### 6. Safety & Privacy
- **Whitelist Patterns**: Comprehensive Bootstrap protection
- **Data Anonymization**: Privacy-compliant collection
- **Audit Logging**: Track all operations
- **Automatic Rollback**: Error detection and recovery

## Installation & Usage

1. **Install**: Place in `modules/custom` and enable
2. **Configure**: Visit `/admin/config/development/bootstrap-purge`
3. **Analyze**: Run analysis via UI or Drush
4. **Review**: Approve/reject purge candidates
5. **Monitor**: Track performance improvements

## Drush Commands
- `drush bootstrap-purge:analyze` - Run analysis
- `drush bootstrap-purge:apply` - Apply purges
- `drush bootstrap-purge:list` - List assets
- `drush bootstrap-purge:stats` - Show statistics
- `drush bootstrap-purge:revert` - Revert purges

The module is now complete and ready for use!