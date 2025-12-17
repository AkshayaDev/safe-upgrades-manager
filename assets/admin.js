/**
 * Safe Upgrades Manager - Admin JavaScript
 * @package SafeUpgradesManager
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Restore backup functionality
    $('.safeupma-restore-backup').on('click', function(e) {
        e.preventDefault();
        
        if (confirm(safeupma_admin.restore_confirm)) {
            var $button = $(this);
            var index = $button.data('index');
            
            // Disable button and change text
            $button.prop('disabled', true).text(safeupma_admin.restoring_text);
            
            // Make AJAX request
            $.post(ajaxurl, {
                action: 'safeupma_restore_backup',
                index: index,
                nonce: safeupma_admin.nonce
            })
            .done(function(response) {
                if (response.success) {
                    alert(safeupma_admin.restore_success);
                    location.reload();
                } else {
                    var errorMsg = safeupma_admin.restore_error + ' ' + (response.data || safeupma_admin.unknown_error);
                    alert(errorMsg);
                }
            })
            .fail(function() {
                alert(safeupma_admin.restore_error + ' ' + safeupma_admin.unknown_error);
            })
            .always(function() {
                // Re-enable button if still on page
                $button.prop('disabled', false).text(safeupma_admin.restore_text);
            });
        }
    });
    
    // Delete backup functionality  
    $('.safeupma-delete-backup').on('click', function(e) {
        e.preventDefault();
        
        if (confirm(safeupma_admin.delete_confirm)) {
            var $button = $(this);
            var index = $button.data('index');
            
            // Disable button and change text
            $button.prop('disabled', true).text(safeupma_admin.deleting_text);
            
            // Make AJAX request
            $.post(ajaxurl, {
                action: 'safeupma_delete_backup',
                index: index,
                nonce: safeupma_admin.nonce
            })
            .done(function(response) {
                if (response.success) {
                    alert(safeupma_admin.delete_success);
                    location.reload();
                } else {
                    var errorMsg = safeupma_admin.delete_error + ' ' + (response.data || safeupma_admin.unknown_error);
                    alert(errorMsg);
                }
            })
            .fail(function() {
                alert(safeupma_admin.delete_error + ' ' + safeupma_admin.unknown_error);
            })
            .always(function() {
                // Re-enable button if still on page
                $button.prop('disabled', false).text(safeupma_admin.delete_text);
            });
        }
    });
});