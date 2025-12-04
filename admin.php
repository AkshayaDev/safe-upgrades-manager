<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

final class SAFEUPMA_Admin {
    public static function init() {
        add_action('load-update.php', array(__CLASS__, 'safeupma_set_hooks'));
        add_action('admin_menu', array(__CLASS__, 'safeupma_add_backup_menu'));
        add_action('wp_ajax_safeupma_restore_backup', array(__CLASS__, 'safeupma_restore_backup'));
        add_action('wp_ajax_safeupma_delete_backup', array(__CLASS__, 'safeupma_delete_backup'));
        
        self::ensure_backup_directory();
    }
    
    public static function ensure_backup_directory() {
        if (!file_exists(SAFEUPMA_BACKUP_DIR)) {
            wp_mkdir_p(SAFEUPMA_BACKUP_DIR);
            file_put_contents(SAFEUPMA_BACKUP_DIR . '.htaccess', 'deny from all');
        }
    }

    public static function safeupma_set_hooks() {
        include_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');

        add_action('admin_action_upload-theme', array(__CLASS__, 'update_theme'));
        add_action('admin_action_upload-plugin', array(__CLASS__, 'update_plugin'));
    }

    public static function update_theme() {
        if (!current_user_can('upload_themes')) {
            wp_die(esc_html__('Sorry, you are not allowed to install themes on this site.', 'safe-upgrades-manager'));
        }

        check_admin_referer('theme-upload');

        $file_upload = new File_Upload_Upgrader('themezip', 'package');

        wp_enqueue_script('customize-loader');

        $title = __('Upload Theme', 'safe-upgrades-manager');
        $parent_file = 'themes.php';
        $submenu_file = 'theme-install.php';

        require_once(ABSPATH . 'wp-admin/admin-header.php');

        /* translators: %s: filename of the uploaded theme */
        $title = sprintf(__('Installing Theme from uploaded file: %s', 'safe-upgrades-manager'), esc_html(basename($file_upload->filename)));
        $nonce = 'theme-upload';
        $url = add_query_arg(array('package' => $file_upload->id), 'update.php?action=upload-theme');
        $type = 'upload'; // Install plugin type, From Web or an Upload.

        require_once(dirname(__FILE__) . '/custom-theme-upgrader.php');

        $upgrader = new SAFEUPMA_Theme_Upgrader(new Theme_Installer_Skin(compact('type', 'title', 'nonce', 'url')));
        $result = $upgrader->install($file_upload->package);

        if ($result || is_wp_error($result)) {
            $file_upload->cleanup();
        }

        include(ABSPATH . 'wp-admin/admin-footer.php');

        exit();
    }

    public static function update_plugin() {
        if (!current_user_can('upload_plugins')) {
            wp_die(esc_html__('Sorry, you are not allowed to install plugins on this site.', 'safe-upgrades-manager'));
        }

        check_admin_referer('plugin-upload');

        $file_upload = new File_Upload_Upgrader('pluginzip', 'package');

        $title = __('Upload Plugin', 'safe-upgrades-manager');
        $parent_file = 'plugins.php';
        $submenu_file = 'plugin-install.php';
        require_once(ABSPATH . 'wp-admin/admin-header.php');

        /* translators: %s: filename of the uploaded plugin */
        $title = sprintf(__('Installing Plugin from uploaded file: %s', 'safe-upgrades-manager'), esc_html(basename($file_upload->filename)));
        $nonce = 'plugin-upload';
        $url = add_query_arg(array('package' => $file_upload->id), 'update.php?action=upload-plugin');
        $type = 'upload'; // Install plugin type, From Web or an Upload.

        require_once(dirname(__FILE__) . '/custom-plugin-upgrader.php');

        $upgrader = new SAFEUPMA_Plugin_Upgrader(new Plugin_Installer_Skin(compact('type', 'title', 'nonce', 'url')));
        $result = $upgrader->install($file_upload->package);

        if ($result || is_wp_error($result)) {
            $file_upload->cleanup();
        }

        include(ABSPATH . 'wp-admin/admin-footer.php');

        exit();
    }

    public static function safeupma_add_backup_menu() {
        add_management_page(
            __('Theme & Plugin Backups', 'safe-upgrades-manager'),
            __('Upgrade Backups', 'safe-upgrades-manager'),
            'manage_options',
            'safeupma-backups',
            array(__CLASS__, 'backup_page')
        );
    }

    public static function backup_page() {
        $backups = get_option('safeupma_backups', array());
        $tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'plugins';
        $current_tab = in_array($tab, array('plugins', 'themes')) ? $tab : 'plugins';
        $per_page = 10;
        $paged = isset($_GET['paged']) ? intval(wp_unslash($_GET['paged'])) : 1;
        $current_page = max(1, $paged);
        
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Theme & Plugin Upgrade Backups', 'safe-upgrades-manager') . '</h1>';
        
        if (empty($backups)) {
            echo '<p>' . esc_html__('No backups found.', 'safe-upgrades-manager') . '</p>';
            echo '<p>' . esc_html__('Backups are automatically created when you upgrade themes or plugins using the upload method.', 'safe-upgrades-manager') . '</p>';
        } else {
            // Group backups by type
            $plugins = array();
            $themes = array();
            
            foreach ($backups as $index => $backup) {
                $backup['index'] = $index;
                if ($backup['type'] === 'plugin') {
                    $plugins[] = $backup;
                } else {
                    $themes[] = $backup;
                }
            }
            
            // Display tabs
            self::display_tabs($current_tab, count($plugins), count($themes));
            
            // Display current tab content
            if ($current_tab === 'plugins') {
                self::display_paginated_backups($plugins, 'plugin', $current_page, $per_page);
            } else {
                self::display_paginated_backups($themes, 'theme', $current_page, $per_page);
            }
        }
        
        self::cleanup_old_backups();
        echo '</div>';
        self::admin_scripts();
    }
    
    private static function display_tabs($current_tab, $plugin_count, $theme_count) {
        $base_url = admin_url('tools.php?page=safeupma-backups');
        
        echo '<nav class="nav-tab-wrapper">';
        
        $plugin_class = ($current_tab === 'plugins') ? 'nav-tab nav-tab-active' : 'nav-tab';
        $theme_class = ($current_tab === 'themes') ? 'nav-tab nav-tab-active' : 'nav-tab';
        
        echo '<a href="' . esc_url($base_url . '&tab=plugins') . '" class="' . esc_attr($plugin_class) . '">';
        echo esc_html__('Plugin Backups', 'safe-upgrades-manager') . ' <span class="count">(' . esc_html($plugin_count) . ')</span>';
        echo '</a>';
        
        echo '<a href="' . esc_url($base_url . '&tab=themes') . '" class="' . esc_attr($theme_class) . '">';
        echo esc_html__('Theme Backups', 'safe-upgrades-manager') . ' <span class="count">(' . esc_html($theme_count) . ')</span>';
        echo '</a>';
        
        echo '</nav>';
    }
    
    private static function display_paginated_backups($backups, $type, $current_page, $per_page) {
        $total_items = count($backups);
        $total_pages = ceil($total_items / $per_page);
        $offset = ($current_page - 1) * $per_page;
        
        // Reverse to show newest first, then slice for pagination
        $backups = array_reverse($backups);
        $paginated_backups = array_slice($backups, $offset, $per_page);
        
        if (empty($paginated_backups)) {
            /* translators: %s: backup type (plugin or theme) */
            echo '<p>' . sprintf(esc_html__('No %s backups found.', 'safe-upgrades-manager'), esc_html($type)) . '</p>';
            return;
        }
        
        // Display the table
        self::display_backup_table($paginated_backups, $type);
        
        // Display pagination
        if ($total_pages > 1) {
            self::display_pagination($current_page, $total_pages, $type, $total_items);
        }
    }
    
    private static function display_pagination($current_page, $total_pages, $type, $total_items) {
        $base_url = admin_url('tools.php?page=safeupma-backups&tab=' . $type . 's');
        
        echo '<div class="tablenav bottom">';
        echo '<div class="alignleft actions bulkactions"></div>';
        echo '<div class="tablenav-pages">';
        
        if ($total_pages > 1) {
            /* translators: %s: number of items */
            echo '<span class="displaying-num">' . esc_html(sprintf(_n('%s item', '%s items', $total_items, 'safe-upgrades-manager'), number_format_i18n($total_items))) . '</span>';
            
            echo '<span class="pagination-links">';
            
            // First page
            if ($current_page > 1) {
                echo '<a class="first-page button" href="' . esc_url($base_url . '&paged=1') . '">';
                echo '<span class="screen-reader-text">' . esc_html__('First page', 'safe-upgrades-manager') . '</span>';
                echo '<span aria-hidden="true">«</span>';
                echo '</a>';
                
                echo '<a class="prev-page button" href="' . esc_url($base_url . '&paged=' . ($current_page - 1)) . '">';
                echo '<span class="screen-reader-text">' . esc_html__('Previous page', 'safe-upgrades-manager') . '</span>';
                echo '<span aria-hidden="true">‹</span>';
                echo '</a>';
            } else {
                echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>';
                echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>';
            }
            
            // Current page indicator
            echo '<span class="paging-input">';
            echo '<span class="tablenav-paging-text">';
            /* translators: %1$s: current page number, %2$s: total pages */
            echo sprintf(esc_html__('%1$s of %2$s', 'safe-upgrades-manager'), '<span class="current-page" id="current-page-selector">' . esc_html($current_page) . '</span>', '<span class="total-pages">' . esc_html($total_pages) . '</span>');
            echo '</span>';
            echo '</span>';
            
            // Next/Last pages
            if ($current_page < $total_pages) {
                echo '<a class="next-page button" href="' . esc_url($base_url . '&paged=' . ($current_page + 1)) . '">';
                echo '<span class="screen-reader-text">' . esc_html__('Next page', 'safe-upgrades-manager') . '</span>';
                echo '<span aria-hidden="true">›</span>';
                echo '</a>';
                
                echo '<a class="last-page button" href="' . esc_url($base_url . '&paged=' . $total_pages) . '">';
                echo '<span class="screen-reader-text">' . esc_html__('Last page', 'safe-upgrades-manager') . '</span>';
                echo '<span aria-hidden="true">»</span>';
                echo '</a>';
            } else {
                echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>';
                echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span>';
            }
            
            echo '</span>';
        }
        
        echo '</div>';
        echo '</div>';
    }
    
    private static function display_backup_table($backups, $type) {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . esc_html__('Name', 'safe-upgrades-manager') . '</th>';
        echo '<th>' . esc_html__('Version', 'safe-upgrades-manager') . '</th>';
        echo '<th>' . esc_html__('Backup Date', 'safe-upgrades-manager') . '</th>';
        echo '<th>' . esc_html__('Size', 'safe-upgrades-manager') . '</th>';
        echo '<th>' . esc_html__('Actions', 'safe-upgrades-manager') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($backups as $backup) {
            $backup_path = SAFEUPMA_BACKUP_DIR . $backup['type'] . 's/' . $backup['backup_name'];
            $size = file_exists($backup_path) ? size_format(self::get_folder_size($backup_path)) : __('N/A', 'safe-upgrades-manager');
            
            echo '<tr>';
            echo '<td>' . esc_html($backup['name']) . '</td>';
            echo '<td>' . esc_html($backup['version']) . '</td>';
            echo '<td>' . esc_html($backup['date']) . '</td>';
            echo '<td>' . esc_html($size) . '</td>';
            echo '<td>';
            if (file_exists($backup_path)) {
                echo '<button class="button restore-backup" data-index="' . esc_attr($backup['index']) . '">' . esc_html__('Restore', 'safe-upgrades-manager') . '</button> ';
            }
            echo '<button class="button delete-backup" data-index="' . esc_attr($backup['index']) . '">' . esc_html__('Delete', 'safe-upgrades-manager') . '</button>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '<br>';
    }
    
    private static function get_folder_size($path) {
        if (is_file($path)) {
            return filesize($path);
        } elseif (is_dir($path)) {
            $size = 0;
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
            return $size;
        }
        return 0;
    }
    
    private static function admin_scripts() {
        // Enqueue admin styles
        wp_add_inline_style('wp-admin', '
            .nav-tab-wrapper {
                margin-bottom: 20px;
            }
            .nav-tab .count {
                color: #72777c;
                font-weight: normal;
            }
            .nav-tab-active .count {
                color: #0073aa;
            }
            .wp-list-table {
                margin-top: 0;
            }
            .tablenav.bottom {
                margin-top: 20px;
                padding-top: 10px;
                border-top: 1px solid #ddd;
            }
        ');
        
        // Enqueue jQuery if not already loaded
        wp_enqueue_script('jquery');
        
        // Add inline script for backup functionality
        $script = "
        jQuery(document).ready(function($) {
            $('.restore-backup').click(function() {
                if (confirm('" . esc_js(__('Are you sure you want to restore this backup? This will overwrite the current version.', 'safe-upgrades-manager')) . "')) {
                    var index = $(this).data('index');
                    var button = $(this);
                    button.prop('disabled', true).text('" . esc_js(__('Restoring...', 'safe-upgrades-manager')) . "');
                    
                    $.post(ajaxurl, {
                        action: 'safeupma_restore_backup',
                        index: index,
                        nonce: '" . wp_create_nonce('safeupma_nonce') . "'
                    }, function(response) {
                        if (response.success) {
                            alert('" . esc_js(__('Backup restored successfully!', 'safe-upgrades-manager')) . "');
                            location.reload();
                        } else {
                            alert('" . esc_js(__('Error restoring backup: ', 'safe-upgrades-manager')) . "' + (response.data || '" . esc_js(__('Unknown error', 'safe-upgrades-manager')) . "'));
                            button.prop('disabled', false).text('" . esc_js(__('Restore', 'safe-upgrades-manager')) . "');
                        }
                    });
                }
            });
            
            $('.delete-backup').click(function() {
                if (confirm('" . esc_js(__('Are you sure you want to delete this backup?', 'safe-upgrades-manager')) . "')) {
                    var index = $(this).data('index');
                    var button = $(this);
                    button.prop('disabled', true).text('" . esc_js(__('Deleting...', 'safe-upgrades-manager')) . "');
                    
                    $.post(ajaxurl, {
                        action: 'safeupma_delete_backup',
                        index: index,
                        nonce: '" . wp_create_nonce('safeupma_nonce') . "'
                    }, function(response) {
                        if (response.success) {
                            alert('" . esc_js(__('Backup deleted successfully!', 'safe-upgrades-manager')) . "');
                            location.reload();
                        } else {
                            alert('" . esc_js(__('Error deleting backup: ', 'safe-upgrades-manager')) . "' + (response.data || '" . esc_js(__('Unknown error', 'safe-upgrades-manager')) . "'));
                            button.prop('disabled', false).text('" . esc_js(__('Delete', 'safe-upgrades-manager')) . "');
                        }
                    });
                }
            });
        });
        ";
        
        wp_add_inline_script('jquery', $script);
    }
    
    private static function cleanup_old_backups() {
        $backups = get_option('safeupma_backups', array());
        $max_age = apply_filters('safeupma_max_backup_age', 30 * DAY_IN_SECONDS);
        $current_time = time();
        $changed = false;
        
        foreach ($backups as $index => $backup) {
            if (isset($backup['timestamp']) && ($current_time - $backup['timestamp']) > $max_age) {
                $backup_path = SAFEUPMA_BACKUP_DIR . $backup['type'] . 's/' . $backup['backup_name'];
                if (file_exists($backup_path)) {
                    if (is_file($backup_path)) {
                        wp_delete_file($backup_path);
                    } elseif (is_dir($backup_path)) {
                        self::remove_directory($backup_path);
                    }
                }
                unset($backups[$index]);
                $changed = true;
            }
        }
        
        if ($changed) {
            update_option('safeupma_backups', array_values($backups));
        }
    }
    
    public static function safeupma_restore_backup() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'safeupma_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error(__('Security check failed.', 'safe-upgrades-manager'));
        }
        
        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            require_once(ABSPATH . '/wp-admin/includes/file.php');
            WP_Filesystem();
        }
        
        $backups = get_option('safeupma_backups', array());
        $index = isset($_POST['index']) ? intval($_POST['index']) : -1;
        
        if ($index < 0 || !isset($backups[$index])) {
            wp_send_json_error(__('Invalid backup index.', 'safe-upgrades-manager'));
        }
        
        $backup = $backups[$index];
        $backup_path = SAFEUPMA_BACKUP_DIR . $backup['type'] . 's/' . $backup['backup_name'];
        
        if (!file_exists($backup_path)) {
            wp_send_json_error(__('Backup file not found.', 'safe-upgrades-manager'));
        }
        
        // FIXED: Correct target path calculation
        if ($backup['type'] === 'plugin') {
            $target_path = WP_PLUGIN_DIR . '/' . $backup['original_file'];
        } else {
            $target_path = get_theme_root() . '/' . $backup['original_file'];
        }
        
        if (!is_dir($target_path)) {
            /* translators: %s: target directory path */
            wp_send_json_error(sprintf(__('Target directory not found: %s', 'safe-upgrades-manager'), $target_path));
        }
        
        $temp_backup_path = $target_path . '_temp_' . time();
        
        if ($wp_filesystem->move($target_path, $temp_backup_path)) {
            if (self::extract_zip_backup($backup_path, $target_path)) {
                self::remove_directory($temp_backup_path);
                wp_send_json_success(__('Backup restored successfully.', 'safe-upgrades-manager'));
            } else {
                $wp_filesystem->move($temp_backup_path, $target_path);
                wp_send_json_error(__('Failed to restore backup.', 'safe-upgrades-manager'));
            }
        } else {
            wp_send_json_error(__('Failed to create temporary backup.', 'safe-upgrades-manager'));
        }
    }
    
    public static function safeupma_delete_backup() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'safeupma_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error(__('Security check failed.', 'safe-upgrades-manager'));
        }
        
        $backups = get_option('safeupma_backups', array());
        $index = isset($_POST['index']) ? intval($_POST['index']) : -1;
        
        if ($index < 0 || !isset($backups[$index])) {
            wp_send_json_error(__('Invalid backup index.', 'safe-upgrades-manager'));
        }
        
        $backup = $backups[$index];
        $backup_path = SAFEUPMA_BACKUP_DIR . $backup['type'] . 's/' . $backup['backup_name'];
        
        if (file_exists($backup_path)) {
            if (!wp_delete_file($backup_path)) {
                wp_send_json_error(__('Failed to delete backup file.', 'safe-upgrades-manager'));
            }
        }
        
        unset($backups[$index]);
        update_option('safeupma_backups', array_values($backups));
        
        wp_send_json_success(__('Backup deleted successfully.', 'safe-upgrades-manager'));
    }
    
    private static function copy_directory($source, $destination) {
        if (!is_dir($source)) {
            return false;
        }
        
        if (!wp_mkdir_p($destination)) {
            return false;
        }
        
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($iterator as $item) {
                $source_path = $item->getPathname();
                $relative_path = substr($source_path, strlen($source) + 1);
                $target = $destination . DIRECTORY_SEPARATOR . $relative_path;
                
                if ($item->isDir()) {
                    if (!wp_mkdir_p($target)) {
                        return false;
                    }
                } else {
                    if (!copy($item->getPathname(), $target)) {
                        return false;
                    }
                }
            }
        } catch (Exception $e) {
            // error_log('WPSU: Copy directory error - ' . $e->getMessage());
            return false;
        }
        
        return true;
    }
    
    private static function extract_zip_backup($zip_path, $extract_to) {
        require_once(ABSPATH . 'wp-admin/includes/class-pclzip.php');
        
        $archive = new PclZip($zip_path);
        
        // Extract to temporary location first
        $temp_extract_dir = dirname($extract_to) . '/temp_extract_' . time();
        
        if (!wp_mkdir_p($temp_extract_dir)) {
            return false;
        }
        
        $extract_result = $archive->extract(PCLZIP_OPT_PATH, $temp_extract_dir);
        
        if (0 === $extract_result) {
            // error_log('WPSU: ZIP extraction failed - ' . $archive->errorInfo(true));
            self::remove_directory($temp_extract_dir);
            return false;
        }
        
        // Find the actual plugin/theme directory inside the extracted content
        $extracted_items = scandir($temp_extract_dir);
        $source_dir = null;
        
        foreach ($extracted_items as $item) {
            if ($item !== '.' && $item !== '..' && is_dir($temp_extract_dir . '/' . $item)) {
                $source_dir = $temp_extract_dir . '/' . $item;
                break;
            }
        }
        
        if (!$source_dir) {
            // error_log('WPSU: No directory found in extracted ZIP');
            self::remove_directory($temp_extract_dir);
            return false;
        }
        
        // Move the contents from the nested directory to the target location
        if (!self::copy_directory($source_dir, $extract_to)) {
            // error_log('WPSU: Failed to copy extracted files to target location');
            self::remove_directory($temp_extract_dir);
            return false;
        }
        
        // Clean up temporary extraction directory
        self::remove_directory($temp_extract_dir);
        
        return true;
    }
    
    private static function remove_directory($dir) {
        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            require_once(ABSPATH . '/wp-admin/includes/file.php');
            WP_Filesystem();
        }
        
        if (!$wp_filesystem->is_dir($dir)) {
            return false;
        }
        
        return $wp_filesystem->rmdir($dir, true);
    }
    
}

SAFEUPMA_Admin::init();