<?php
/**
 * Plugin Name: Safe Upgrades Manager
 * Plugin URI: https://github.com/AkshayaDev/safe-upgrades-manager
 * Description: Creates backup copies of themes and plugins before upgrading them, allowing rollback to previous versions if needed.
 * Version: 1.0.0
 * Author: Akshaya Swaroop
 * License: GPL v2 or later
 * Text Domain: safe-upgrades-manager
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SAFEUPMA_PLUGIN_FILE', __FILE__);
define('SAFEUPMA_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SAFEUPMA_WP_PLUGINS_PATH', plugin_dir_path(__DIR__));
define('SAFEUPMA_PLUGIN_URL', plugin_dir_url(__FILE__));

// Use uploads directory instead of wp-content for backup storage
$safeupma_upload_dir = wp_upload_dir();
define('SAFEUPMA_BACKUP_DIR', trailingslashit($safeupma_upload_dir['basedir']) . 'safe-upgrades-manager/');

if (is_admin()) {
    require(dirname(__FILE__) . '/admin.php');
    
    // Add plugin action links
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'safeupma_add_action_links');
}

function safeupma_add_action_links($links) {
    $backup_link = '<a href="' . admin_url('tools.php?page=safeupma-backups') . '">' . __('Upgrade Backups', 'safe-upgrades-manager') . '</a>';
    array_unshift($links, $backup_link);
    return $links;
}