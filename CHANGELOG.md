# Changelog

All notable changes to Safe Upgrades Manager will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-11-26

### Added
- Initial release of Safe Upgrades Manager
- Automatic backup creation during theme and plugin upgrades via upload
- ZIP-based backup storage system with organized directory structure
- Admin interface under Tools > Upgrade Backups for backup management
- Tabbed interface separating plugin and theme backups
- One-click restore functionality to rollback to previous versions
- One-click delete functionality for backup cleanup
- Automatic cleanup of backups older than 30 days (configurable via filter)
- Security features including:
  - Nonce verification for all AJAX requests
  - Proper capability checks (`manage_options`, `upload_themes`, `upload_plugins`)
  - Secure file handling and directory protection
- Memory optimization for large theme/plugin files
- Intelligent backup directory creation with `.htaccess` protection
- Error logging for troubleshooting backup operations
- Fallback backup method (directory renaming) when ZIP creation fails
- Pagination support for large numbers of backups
- Real-time backup file size calculation and display
- Custom upgrader classes extending WordPress core functionality
- Support for both themes and plugins with unified backup management

### Technical Features
- Custom `SAFEUPMA_Plugin_Upgrader` class extending `Plugin_Upgrader`
- Custom `SAFEUPMA_Theme_Upgrader` class extending `Theme_Upgrader`
- PclZip integration for reliable ZIP archive creation
- RecursiveDirectoryIterator for efficient directory operations
- WordPress-standard AJAX handling with proper security
- Transient caching for improved performance
- Filter hook `safeupma_max_backup_age` for customizing backup retention
- Comprehensive error handling and user feedback

### Security
- All file operations performed with proper WordPress filesystem API
- Directory traversal protection
- Secure backup storage outside web-accessible directory
- Input sanitization and validation for all user inputs
- Proper escaping of all output data

### Requirements
- WordPress 5.0 or higher
- PHP 7.4 or higher
- Sufficient disk space for backup storage
- Write permissions for wp-content directory

---

## Future Releases

### Planned Features
- Support for WordPress automatic updates
- Backup compression options
- Email notifications for backup operations
- Backup export/import functionality
- Integration with cloud storage providers
- Scheduled backup cleanup options
- Backup verification and integrity checks
- Multi-site network support enhancements

### Known Issues
- Currently only supports manual theme/plugin uploads, not automatic updates
- Large themes/plugins may require increased memory limits
- Backup operations may timeout on very large files (handled gracefully with fallback)