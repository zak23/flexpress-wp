jQuery(document).ready(function($) {
    'use strict';

    // Handle delete user button clicks
    $('.delete-user-btn').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var userId = $button.data('user-id');
        var userName = $button.data('user-name');
        
        // Show confirmation dialog
        if (!confirm(flexpressMembershipAdmin.confirmDeleteMessage + '\n\nUser: ' + userName)) {
            return;
        }
        
        // Disable button and show loading state
        $button.prop('disabled', true).text(flexpressMembershipAdmin.deletingMessage);
        
        // Send AJAX request
        $.ajax({
            url: flexpressMembershipAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_member_user',
                user_id: userId,
                nonce: flexpressMembershipAdmin.deleteUserNonce
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    alert(response.data.message);
                    
                    // Remove the user row from the table
                    $button.closest('tr').fadeOut(300, function() {
                        $(this).remove();
                        
                        // Check if table is empty
                        var $tbody = $('table.users tbody');
                        if ($tbody.find('tr').length === 0) {
                            $tbody.html('<tr><td colspan="7">No users found matching the filter criteria.</td></tr>');
                        }
                    });
                } else {
                    // Show error message
                    alert(response.data.message || flexpressMembershipAdmin.deleteErrorMessage);
                    
                    // Re-enable button
                    $button.prop('disabled', false).text('Delete');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                alert(flexpressMembershipAdmin.deleteErrorMessage);
                
                // Re-enable button
                $button.prop('disabled', false).text('Delete');
            }
        });
    });
    
    // Add some styling for the delete button
    $('.delete-user-btn').css({
        'color': '#a00',
        'border-color': '#a00'
    }).hover(
        function() {
            $(this).css({
                'color': '#fff',
                'background-color': '#a00'
            });
        },
        function() {
            $(this).css({
                'color': '#a00',
                'background-color': ''
            });
        }
    );
}); 