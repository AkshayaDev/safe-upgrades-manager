<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class SAFEUPMA_Plugin_Upgrader extends Plugin_Upgrader {
    public function install_package($args = array()) {
        global $wp_filesystem;

        if (empty($args['source']) || empty($args['destination'])) {
            // Only run if the arguments we need are present.
            return parent::install_package($args);
        }

        $source_files = array_keys($wp_filesystem->dirlist($args['source']));
        $remote_destination = SAFEUPMA_WP_PLUGINS_PATH;

        // Locate which directory to copy to the new folder, This is based on the actual folder holding the files.
        if (1 === count($source_files) && $wp_filesystem->is_dir(trailingslashit($args['source']) . $source_files[0] . '/')) { // Only one folder? Then we want its contents.
            $destination = trailingslashit($remote_destination) . trailingslashit($source_files[0]);
        } elseif (0 === count($source_files)) {
            // Looks like an empty zip, we'll let the default code handle this.
            return parent::install_package($args);
        } else { // It's only a single file, the upgrader will use the folder name of this file as the destination folder. Folder name is based on zip filename.
            $destination = trailingslashit($remote_destination) . trailingslashit(basename($args['source']));
        }

        if (is_dir($destination)) {
            // This is an upgrade, clear the destination.
            $args['clear_destination'] = true;

            // Switch template strings to use upgrade terminology rather than install terminology.
            $this->upgrade_strings();

            // Replace default remove_old string to make the messages more meaningful.
            $this->strings['installing_package'] = __('Upgrading the plugin&#8230;', 'safe-upgrades-manager');
            $this->strings['remove_old'] = __('Backing up the old version of the plugin&#8230;', 'safe-upgrades-manager');
        }

        return parent::install_package($args);
    }

    private function safeupma_get_plugin_data($directory) {
        $files = glob($directory . '*.php');

        if ($files) {
            foreach ($files as $file) {
                $info = get_plugin_data($file, false, false);

                if (!empty($info['Name'])) {
                    $data = array(
                        'name'    => $info['Name'],
                        'version' => $info['Version'],
                    );

                    return $data;
                }
            }
        }

        return false;
    }

    public function clear_destination($destination) {
        global $wp_filesystem;

        if (!is_dir($destination)) {
            // This is an installation not an upgrade.
            return parent::clear_destination($destination);
        }

        $data = $this->safeupma_get_plugin_data($destination);

        if (false === $data) {
            // The existing directory is not a valid plugin, skip backup.
            return parent::clear_destination($destination);
        }

        $backup_url = $this->safeupma_create_backup($destination);

        if (!is_wp_error($backup_url)) {
            /* translators: 1: plugin zip URL */
            $this->skin->feedback(sprintf(__('A backup zip file of the old plugin version can be downloaded <a href="%1$s">here</a>.', 'safe-upgrades-manager'), $backup_url));

            // Restore default strings and display the original remove_old message.
            $this->upgrade_strings();
            $this->skin->feedback('remove_old');

            return parent::clear_destination($destination);
        }

        $this->skin->error($backup_url);
        $this->skin->feedback(__('Moving the old version of the plugin to a new directory&#8230;', 'safe-upgrades-manager'));

        $new_name = basename($destination) . "-{$data['version']}";
        $directory = dirname($destination);

        for ($x = 0; $x < 20; $x++) {
            $test_name = $new_name . '-' . $this->get_random_characters(10, 20);

            if (!is_dir("$directory/$test_name")) {
                $new_name = $test_name;
                break;
            }
        }

        if (is_dir("$directory/$new_name")) {
            // We gave it our best effort. Time to give up on the idea of having a backup.
            $this->skin->error(__('Unable to find a new directory name to move the old version of the plugin to. No backup will be created.', 'safe-upgrades-manager'));
        } else {
            $result = $wp_filesystem->move($destination, "$directory/$new_name");

            if ($result) {
                /* translators: 1: new plugin directory name */
                $this->skin->feedback(sprintf(__('Moved the old version of the plugin to a new plugin directory named %1$s. This directory should be backed up and removed from the site.', 'safe-upgrades-manager'), "<code>$new_name</code>"));
            } else {
                $this->skin->error(__('Unable to move the old version of the plugin to a new directory. No backup will be created.', 'safe-upgrades-manager'));
            }
        }

        // Restore default strings and display the original remove_old message.
        $this->upgrade_strings();
        $this->skin->feedback('remove_old');

        return parent::clear_destination($destination);
    }

    private function safeupma_create_backup($directory) {
        $backup_dir = SAFEUPMA_BACKUP_DIR . 'plugins/';

        if (!is_dir($backup_dir)) {
            wp_mkdir_p($backup_dir);
        }

        $data = $this->safeupma_get_plugin_data($directory);
        
        if (!$data) {
            return new WP_Error('safeupma-cannot-backup-no-plugin-data', __('Could not read plugin information.', 'safe-upgrades-manager'));
        }

        $backup_name = basename($directory) . '_' . gmdate('Y-m-d_H-i-s') . '_backup.zip';
        $backup_path = $backup_dir . $backup_name;

        // Create ZIP archive
        if (!$this->safeupma_create_zip_backup($directory, $backup_path)) {
            return new WP_Error('safeupma-cannot-backup-zip-failed', __('Failed to create plugin ZIP backup.', 'safe-upgrades-manager'));
        }
        
        // Save backup info to database
        $this->safeupma_save_backup_info('plugin', basename($directory), $backup_name, basename($directory), $data['version']);

        return admin_url('tools.php?page=safeupma-backups');
    }

    private function get_random_characters($min_length, $max_length) {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $rand_string = '';
        $length = wp_rand($min_length, $max_length);

        for ($count = 0; $count < $length; $count++) {
            $rand_string .= $characters[wp_rand(0, strlen($characters) - 1)];
        }

        return $rand_string;
    }

    
    private function safeupma_create_zip_backup($source_dir, $zip_path) {
        // Try PclZip first (WordPress standard)
        if (class_exists('PclZip')) {
            $archive = new PclZip($zip_path);

            $zip_result = $archive->create($source_dir, PCLZIP_OPT_REMOVE_PATH, dirname($source_dir));

            if (0 !== $zip_result) {
                return true;
            }
            // error_log('WPSU: PclZip creation failed - ' . $archive->errorInfo(true));
        }
        
        // Fallback to ZipArchive if PclZip fails or is not available
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            $result = $zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            
            if ($result !== TRUE) {
                // error_log('WPSU: ZipArchive open failed with code: ' . $result);
                return false;
            }
            
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($source_dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($iterator as $file) {
                $file_path = $file->getPathname();
                $relative_path = substr($file_path, strlen(dirname($source_dir)) + 1);
                
                if ($file->isDir()) {
                    $zip->addEmptyDir($relative_path);
                } elseif ($file->isFile()) {
                    $zip->addFile($file_path, $relative_path);
                }
            }
            
            $close_result = $zip->close();
            if ($close_result) {
                return true;
            }
            // error_log('WPSU: ZipArchive close failed');
        }

        return false;
    }
    
    private function safeupma_save_backup_info($type, $name, $backup_name, $original_file, $version) {
        $backups = get_option('safeupma_backups', array());
        
        $backups[] = array(
            'type' => $type,
            'name' => $name,
            'version' => $version,
            'backup_name' => $backup_name,
            'original_file' => $original_file,
            'date' => current_time('mysql'),
            'timestamp' => time()
        );
        
        update_option('safeupma_backups', $backups);
    }
}