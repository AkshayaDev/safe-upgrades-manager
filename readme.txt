=== Safe Upgrades Manager ===
Contributors: akshayaswaroop
Donate link: https://paypal.me/swaroopakshaya
Tags: backup, theme, upgrade, restore, rollback
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Creates backup copies of themes and plugins before upgrading them, allowing rollback to previous versions if needed.

== Description ==

Safe Upgrades Manager is a safety-first plugin that automatically creates backup copies of your themes and plugins before upgrading them. This ensures you can quickly restore to a previous working version if something goes wrong during an upgrade.

**Key Features:**

* **Automatic Backups**: Creates ZIP backups automatically when upgrading themes or plugins via upload
* **Smart Backup Management**: Organized backup storage with automatic cleanup of old backups (30 days by default)
* **Easy Restore**: One-click restore functionality to rollback to previous versions
* **Admin Interface**: Clean, tabbed interface to manage plugin and theme backups separately
* **Security First**: Proper nonce verification, capability checks, and secure file handling
* **Memory Optimization**: Intelligent memory management for large theme/plugin files
* **Detailed Logging**: Error logging for troubleshooting backup operations

**How It Works:**

1. When you upload a new version of a theme or plugin, the plugin detects the upgrade
2. It automatically creates a ZIP backup of the current version before replacement
3. Backups are stored in a secure directory (`wp-content/upgrades-backup/`)
4. Access the backup management interface under Tools > Upgrade Backups
5. Restore or delete backups with a single click

**Perfect For:**

* Developers testing theme/plugin updates
* Agencies managing client websites
* Anyone who wants peace of mind during upgrades
* Sites with custom modifications that might be lost during updates

The plugin integrates seamlessly with WordPress's existing upgrade process and adds an extra layer of protection without changing your workflow.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/safe-upgrades-manager` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. That's it! The plugin will automatically start creating backups during theme/plugin upgrades
4. Manage your backups by going to Tools > Upgrade Backups

== Frequently Asked Questions ==

= Does this plugin work with automatic updates? =

Currently, the plugin only creates backups for manual uploads of themes and plugins. It doesn't intercept WordPress automatic updates. This is by design to avoid interference with core WordPress functionality.

= Where are backups stored? =

Backups are stored in `/wp-content/upgrades-backup/` directory. This directory is protected with an .htaccess file to prevent direct access.

= How long are backups kept? =

By default, backups older than 30 days are automatically deleted. You can modify this with the `safeupma_max_backup_age` filter hook.

= Can I restore a backup manually? =

Yes! The plugin provides a user-friendly interface under Tools > Upgrade Backups where you can restore or delete any backup with one click.

= What happens if backup creation fails? =

If ZIP backup creation fails, the plugin will attempt to move the old version to a renamed directory as a fallback backup method.

= Does this work with multisite? =

The plugin can be activated on multisite installations, but backups are managed per site, not network-wide.

= Will this slow down my site? =

No, backups are only created during upgrade operations, not during normal site operation. The plugin has minimal impact on performance.

== Screenshots ==

1. Backup management interface showing plugin backups with restore/delete options
2. Theme backups tab with organized backup listings
3. Backup creation process during plugin upgrade
4. Backup restore confirmation dialog

== Changelog ==

= 1.0.0 =
* Initial release
* Automatic backup creation for theme and plugin upgrades
* ZIP-based backup storage system
* Admin interface for backup management
* One-click restore and delete functionality
* Security features with nonce verification
* Automatic cleanup of old backups
* Memory optimization for large files
* Error logging and fallback backup methods

== Upgrade Notice ==

= 1.0.0 =
Initial release of Safe Upgrades Manager. Provides automatic backup functionality for theme and plugin upgrades with easy restore options.

== Developer Notes ==

**Hooks and Filters:**

* `safeupma_max_backup_age` - Filter to modify the maximum age of backups before cleanup (default: 30 days)

**Directory Structure:**
```
wp-uploads/safe-upgrades-manager/
├── plugins/
│   └── [plugin-slug]/
│       ├── [plugin-name]_v[version]_[timestamp].zip
│       └── [plugin-name]_v[version]_[timestamp].zip
└── themes/
    └── [theme-slug]/
        ├── [theme-name]_v[version]_[timestamp].zip
        └── [theme-name]_v[version]_[timestamp].zip
```

**Requirements:**
* PHP 7.4 or higher
* WordPress 5.0 or higher
* Sufficient disk space for backup storage
* Write permissions for wp-content directory