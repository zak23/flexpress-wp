/**
 * ACF Admin JavaScript
 * Handles custom functionality for ACF fields in the admin area
 */

jQuery(document).ready(function($) {
    
    // Track the previous access type value
    let previousAccessType = '';
    
    // Initialize previous value on page load
    const accessTypeField = $('select[name*="access_type"]');
    if (accessTypeField.length) {
        previousAccessType = accessTypeField.val();
    }
    
    // Handle access type changes
    $(document).on('change', 'select[name*="access_type"]', function() {
        const currentValue = $(this).val();
        const $this = $(this);
        
        // If user selected "free" and it wasn't already free
        if (currentValue === 'free' && previousAccessType !== 'free') {
            
            // Show confirmation dialog
            const confirmed = confirm(
                '⚠️ FREE EPISODE CONFIRMATION\n\n' +
                'Are you sure you want to make this episode FREE for everyone?\n\n' +
                '• This episode will be accessible to all visitors\n' +
                '• No payment or membership required\n' +
                '• This cannot be easily undone once published\n\n' +
                'Click OK to confirm, or Cancel to choose a different access type.'
            );
            
            if (!confirmed) {
                // User cancelled, revert to previous value
                $this.val(previousAccessType);
                
                // Trigger ACF to update conditional logic
                $this.trigger('change');
                
                // Show helpful message
                setTimeout(function() {
                    alert('💡 TIP: Consider using "Membership Access + PPV Option" for most episodes.\n\nThis gives members free access while allowing non-members to purchase individually.');
                }, 100);
                
                return false;
            } else {
                // User confirmed, show success message
                setTimeout(function() {
                    alert('✅ Episode set to FREE for everyone!\n\nThis episode will be accessible to all visitors without any payment or membership requirement.');
                }, 100);
            }
        }
        
        // Update previous value
        previousAccessType = currentValue;
        
        // Show contextual help based on selection
        showAccessTypeHelp(currentValue);
    });
    
    // Show help text based on access type
    function showAccessTypeHelp(accessType) {
        // Remove any existing help notices
        $('.access-type-help').remove();
        
        let helpText = '';
        let helpClass = '';
        
        switch(accessType) {
            case 'free':
                helpText = '🟢 <strong>Free for Everyone:</strong> This episode will be accessible to all visitors without any payment or membership requirement.';
                helpClass = 'notice-success';
                break;
                
            case 'ppv_only':
                helpText = '🔵 <strong>Pay-Per-View Only:</strong> This episode must be purchased individually. Membership does not provide access - perfect for exclusive premium content.';
                helpClass = 'notice-info';
                break;
                
            case 'membership':
                helpText = '🟡 <strong>Membership Access + PPV:</strong> Active members get free access, non-members can purchase individually. Great for encouraging membership signups.';
                helpClass = 'notice-warning';
                break;
                
            case 'mixed':
                helpText = '🔵 <strong>Members Get Discount:</strong> Active members receive a discount, non-members pay full price. Perfect balance of member benefits and revenue.';
                helpClass = 'notice-info';
                break;
        }
        
        if (helpText) {
            const $helpNotice = $('<div class="notice ' + helpClass + ' access-type-help" style="margin: 10px 0; padding: 10px;"><p>' + helpText + '</p></div>');
            $('.access-type-field').append($helpNotice);
        }
    }
    
    // Show initial help on page load
    if (accessTypeField.length) {
        showAccessTypeHelp(accessTypeField.val());
    }
    
    // Handle price field visibility and validation
    $(document).on('change', 'select[name*="access_type"], input[name*="episode_price"]', function() {
        validatePriceField();
    });
    
    function validatePriceField() {
        const accessType = $('select[name*="access_type"]').val();
        const priceField = $('input[name*="episode_price"]');
        const priceValue = parseFloat(priceField.val()) || 0;
        
        // Remove existing price warnings
        $('.price-field-warning').remove();
        
        let warningText = '';
        
        if (accessType === 'free' && priceValue > 0) {
            warningText = '⚠️ <strong>Warning:</strong> Episode is set to FREE but has a price set. The price will be ignored.';
        } else if ((accessType === 'ppv_only' || accessType === 'membership' || accessType === 'mixed') && priceValue <= 0) {
            warningText = '⚠️ <strong>Warning:</strong> This access type requires a price to be set for purchases to work properly.';
        }
        
        if (warningText) {
            const $warning = $('<div class="notice notice-warning price-field-warning" style="margin: 10px 0; padding: 10px;"><p>' + warningText + '</p></div>');
            priceField.closest('.acf-field').append($warning);
        }
    }
    
    // Initial price validation
    setTimeout(validatePriceField, 500);
    
    // Add helpful tooltips to field labels
    $(document).on('mouseenter', '.acf-field-access-type .acf-label label', function() {
        $(this).attr('title', 'Choose how users can access this episode. This controls pricing, membership benefits, and video access.');
    });
    
    $(document).on('mouseenter', '.acf-field-episode-price .acf-label label', function() {
        $(this).attr('title', 'Set the base price for non-members. Members may get discounts based on the access type.');
    });
    
    $(document).on('mouseenter', '.acf-field-member-discount .acf-label label', function() {
        $(this).attr('title', 'Percentage discount for active members (only applies to "Mixed" access type).');
    });
    
}); 