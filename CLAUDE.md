# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Safe Upgrades Manager is a WordPress plugin that creates backup copies of themes and plugins before upgrading them, allowing rollback to previous versions if needed. The plugin provides a safety-first approach to WordPress theme and plugin upgrades through automated backup creation and one-click restore functionality.

## Plugin Architecture

### Core Components

The plugin follows WordPress plugin standards with a modular class-based structure:

- **Main Plugin File**: `safe-upgrades-manager.php` - Plugin header, constants, and initialization
- **Admin Interface**: `admin.php` - Main admin class with backup management UI and AJAX handlers  
- **Custom Upgraders**: Two separate classes extending WordPress core upgraders:
  - `custom-plugin-upgrader.php` - Extends `Plugin_Upgrader` for plugin backup creation
  - `custom-theme-upgrader.php` - Extends `Theme_Upgrader` for theme backup creation
- **Assets**: CSS and JavaScript for admin interface styling and functionality

### Key Classes

- **SAFEUPMA_Admin**: Main admin functionality including menu creation, backup display, and AJAX handlers
- **SAFEUPMA_Plugin_Upgrader**: Handles plugin upgrade backup creation with ZIP archive support
- **SAFEUPMA_Theme_Upgrader**: Handles theme upgrade backup creation with ZIP archive support

### Backup System Architecture

The plugin uses a two-tier backup approach:
1. **Primary**: ZIP archive creation using WordPress PclZip class
2. **Secondary**: ZipArchive class fallback if PclZip fails  
3. **Final Fallback**: Directory renaming if both ZIP methods fail

Backup storage structure:
```
wp-uploads/safe-upgrades-manager/
├── plugins/
│   └── [plugin-slug]_[timestamp]_backup.zip
└── themes/
    └── [theme-slug]_[timestamp]_backup.zip
```

**Memory Management**: ZIP operations temporarily increase memory limit to 256M and execution time to 600 seconds.

### Database Schema

Backup metadata is stored in WordPress options table:
- **Option Name**: `safeupma_backups`
- **Structure**: Array of backup records with type, name, version, backup_name, original_file, date, and timestamp

## Development Workflow

### Testing Backup Creation
1. Upload a theme or plugin via WordPress admin (Appearance > Add New Theme > Upload or Plugins > Add New > Upload)
2. The plugin automatically intercepts the upgrade process
3. Check the backup management interface at Tools > Upgrade Backups
4. Verify ZIP files are created in the uploads directory

### Testing Restore Functionality
1. Navigate to Tools > Upgrade Backups
2. Use the tabbed interface to switch between plugin and theme backups
3. Click "Restore" to test backup restoration
4. Verify the restoration process completes successfully

### Memory and Performance Considerations
- ZIP creation operations include memory limit increases (256M)
- Time limits are temporarily extended (600 seconds) during backup operations
- Large themes/plugins may require server optimization

## Security Implementation

The plugin implements multiple security layers:
- Nonce verification for all AJAX requests
- Capability checks (`manage_options`, `upload_themes`, `upload_plugins`)
- Input sanitization and output escaping throughout
- .htaccess protection for backup directory
- WordPress filesystem API usage for all file operations

## Filter Hooks

- `safeupma_max_backup_age`: Modify backup retention period (default: 30 days)
  ```php
  // Example: Change retention to 60 days
  add_filter('safeupma_max_backup_age', function($age) {
      return 60 * DAY_IN_SECONDS;
  });
  ```

## Known Limitations

- Only works with manual theme/plugin uploads, not WordPress automatic updates
- Requires sufficient disk space for backup storage
- Large files may require increased PHP memory and time limits
- Multisite installations manage backups per-site, not network-wide

## Development Commands

No build commands required - this is a standard WordPress plugin using PHP, JavaScript, and CSS without compilation steps.

### Testing and Validation
```bash
# WordPress.org compliance validation (if plugin-check tool is available)
wp plugin-check safe-upgrades-manager

# Manual testing workflow:
# 1. Upload test theme/plugin via WordPress admin
# 2. Navigate to Tools > Upgrade Backups to verify backup creation
# 3. Test restore functionality with created backups
# 4. Test deletion of backups
# 5. Verify .htaccess protection in backup directory
```

### Development Testing
- Test with various theme and plugin sizes to verify memory handling
- Test backup creation with both PclZip and ZipArchive methods
- Verify nonce security and capability checks in AJAX handlers
- Test automatic cleanup of backups older than 30 days

## Key Code Locations

### Main Hook Integration
- Plugin upgrade interception: `admin.php:31-32` (admin_action hooks)
- Theme/plugin upgrader replacement: `admin.php:58,92` (custom upgrader classes)

### Backup Creation Logic
- Plugin backup creation: `custom-plugin-upgrader.php:129-154`
- Theme backup creation: `custom-theme-upgrader.php:106-135`
- ZIP creation methods: Both upgraders have identical `safeupma_create_zip_backup()` methods

### Restore & Management
- AJAX restore handler: `admin.php:382-432`
- AJAX delete handler: `admin.php:434-459`
- Backup listing/pagination: `admin.php:180-202`

## File Structure

```
safe-upgrades-manager/
├── safe-upgrades-manager.php    # Main plugin file with constants and initialization
├── admin.php                    # Admin interface and AJAX handlers
├── custom-plugin-upgrader.php   # Plugin backup creation class
├── custom-theme-upgrader.php    # Theme backup creation class
├── assets/
│   ├── admin.css               # Admin interface styles
│   └── admin.js                # AJAX functionality for restore/delete
├── screenshots/                # Plugin screenshots for WordPress.org
├── readme.txt                  # WordPress.org plugin readme
└── CHANGELOG.md               # Version history and feature documentation
```

## Important Constants

- `SAFEUPMA_PLUGIN_FILE`: Main plugin file path
- `SAFEUPMA_PLUGIN_PATH`: Plugin directory path  
- `SAFEUPMA_PLUGIN_URL`: Plugin directory URL
- `SAFEUPMA_BACKUP_DIR`: Backup storage directory in uploads folder