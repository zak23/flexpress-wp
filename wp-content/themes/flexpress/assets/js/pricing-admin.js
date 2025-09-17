jQuery(document).ready(function($) {
    'use strict';

    console.log('Pricing admin script loaded');

    // Utility functions
    function showError($field, message) {
        // Remove existing error
        $field.removeClass('error').next('.error-message').remove();
        
        // Add error class and message
        $field.addClass('error');
        $field.after('<div class="error-message" style="color: red; font-size: 12px; margin-top: 5px;">' + message + '</div>');
    }

    function clearError($field) {
        $field.removeClass('error').next('.error-message').remove();
    }

    function validateField($field) {
        const value = $field.val();
        const fieldName = $field.attr('name');
        
        // Clear previous error
        clearError($field);
        
        // Required field validation
        if ($field.prop('required') && (!value || value.trim() === '')) {
            showError($field, 'This field is required');
            return false;
        }
        
        // Specific field validations
        if (fieldName === 'price' && value) {
            const price = parseFloat(value);
            if (isNaN(price) || price < 0) {
                showError($field, 'Please enter a valid price');
                return false;
            }
        }
        
        if (fieldName === 'duration' && value) {
            const duration = parseInt(value);
            if (isNaN(duration) || duration < 1) {
                showError($field, 'Please enter a valid duration');
                return false;
            }
        }
        
        if (fieldName === 'trial_price' && value) {
            const trialPrice = parseFloat(value);
            if (isNaN(trialPrice) || trialPrice < 0) {
                showError($field, 'Please enter a valid trial price');
                return false;
            }
        }
        
        if (fieldName === 'trial_duration' && value) {
            const trialDuration = parseInt(value);
            if (isNaN(trialDuration) || trialDuration < 1) {
                showError($field, 'Please enter a valid trial duration');
                return false;
            }
        }
        
        return true;
    }

    function showNotice(type, message) {
        // Remove existing notices
        $('.notice').remove();
        
        // Create new notice
        const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        const notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
        
        // Insert after the page title
        $('.wrap h1').after(notice);
        
        // Auto-remove after 5 seconds
        setTimeout(function() {
            notice.fadeOut();
        }, 5000);
    }

    // Cache DOM elements
    const $modal = $('#plan-edit-modal');
    const $modalTitle = $('#modal-title');
    const $form = $('#pricing-plan-form');
    const $trialEnabled = $('#trial-enabled');
    const $trialSettings = $('.trial-settings');

    console.log('Modal elements found:', {
        modal: $modal.length,
        modalTitle: $modalTitle.length,
        form: $form.length,
        trialEnabled: $trialEnabled.length,
        trialSettings: $trialSettings.length
    });

    // Open modal for new plan
    $(document).on('click', '#add-new-plan', function() {
        console.log('Add new plan clicked');
        openModal('add');
    });

    // Open modal for editing plan
    $(document).on('click', '.edit-plan', function() {
        const planId = $(this).data('plan-id');
        console.log('Edit plan clicked for plan ID:', planId);
        console.log('Button element:', this);
        console.log('jQuery object:', $(this));
        console.log('Data attribute value:', $(this).attr('data-plan-id'));
        
        if (!planId) {
            console.error('No plan ID found!');
            alert('Error: No plan ID found. Please check the button configuration.');
            return;
        }
        
        openModal('edit', planId);
    });

    // Close modal
    $(document).on('click', '.pricing-modal-close, #cancel-plan-edit', function() {
        console.log('Close modal clicked');
        closeModal();
    });

    // Close modal when clicking outside
    $(document).on('click', '.pricing-modal', function(e) {
        if (e.target === this) {
            console.log('Modal backdrop clicked');
            closeModal();
        }
    });

    // Handle trial enabled checkbox
    $trialEnabled.change(function() {
        handleTrialSettings();
    });

    // Handle plan type change
    $('#plan-type').change(function() {
        const planType = $(this).val();
        const $durationSection = $('#duration-section');
        const $durationInputs = $durationSection.find('input, select');
        const $durationNote = $('.duration-note');
        
        if (planType === 'one_time') {
            // Disable trial settings for one-time payments
            $trialEnabled.prop('checked', false).prop('disabled', true);
            $trialSettings.slideUp();
            $trialSettings.find('input').prop('required', false);
            
            // Enable duration settings - one-time payments should have configurable duration
            $('#plan-duration').prop('disabled', false);
            $('#plan-duration-unit').prop('disabled', false);
            
            // Set default values if empty (but don't override existing values)
            if (!$('#plan-duration').val()) {
                $('#plan-duration').val('30');
            }
            if (!$('#plan-duration-unit').val()) {
                $('#plan-duration-unit').val('days');
            }
            
            // Update labels for clarity
            $durationSection.find('h3').text('Access Duration');
            $('#plan-duration-label').text('Access Duration');
            $durationNote.slideUp(); // Hide the incorrect lifetime note
            
        } else if (planType === 'lifetime') {
            // Disable trial settings for lifetime access
            $trialEnabled.prop('checked', false).prop('disabled', true);
            $trialSettings.slideUp();
            $trialSettings.find('input').prop('required', false);
            
            // Set duration to 'lifetime' (999 years) and disable fields
            $('#plan-duration').val('999').prop('disabled', true);
            $('#plan-duration-unit').val('years').prop('disabled', true);
            
            // Update duration label and show note
            $durationSection.find('h3').text('Access Duration');
            $('#plan-duration-label').text('Lifetime Access');
            $durationNote.slideDown();
            
        } else {
            // Recurring subscription - enable trial settings
            $trialEnabled.prop('disabled', false);
            handleTrialSettings();
            
            // Enable duration settings
            $('#plan-duration').prop('disabled', false);
            $('#plan-duration-unit').prop('disabled', false);
            
            // Set default values if empty
            if (!$('#plan-duration').val()) {
                $('#plan-duration').val('1');
            }
            if (!$('#plan-duration-unit').val()) {
                $('#plan-duration-unit').val('months');
            }
            
            // Restore duration label and hide note
            $durationSection.find('h3').text('Duration Settings');
            $('#plan-duration-label').text('Duration');
            $durationNote.slideUp();
        }
    });

    function handleTrialSettings() {
        const planType = $('#plan-type').val();
        if ($trialEnabled.is(':checked') && planType !== 'one_time' && planType !== 'lifetime') {
            $trialSettings.slideDown();
            $trialSettings.find('input').prop('required', true);
        } else {
            $trialSettings.slideUp();
            $trialSettings.find('input').prop('required', false);
            // Clear trial values when disabled
            $('#trial-price').val('');
            $('#trial-duration').val('');
            $('#trial-duration-unit').val('days');
        }
    }

    // Form submission
    $form.submit(function(e) {
        e.preventDefault();
        console.log('Form submitted');
        
        // Validate form
        let isValid = true;
        const planType = $('#plan-type').val();
        
        // Basic validation
        $form.find('input[required], select[required]').each(function() {
            if (!validateField($(this))) {
                isValid = false;
            }
        });
        
        // Plan type specific validation
        if (planType === 'one_time' || planType === 'lifetime') {
            // No trial period allowed for one-time payments or lifetime access
            if ($trialEnabled.is(':checked')) {
                const planTypeText = planType === 'lifetime' ? 'lifetime access' : 'one-time payments';
                showError($trialEnabled, 'Trial periods are not allowed for ' + planTypeText);
                isValid = false;
            }
        } else {
            // Validate trial settings if enabled (only for recurring subscriptions)
            if ($trialEnabled.is(':checked')) {
                $trialSettings.find('input[required]').each(function() {
                    if (!validateField($(this))) {
                        isValid = false;
                    }
                });
            }
        }
        
        console.log('Form validation result:', isValid);
        if (isValid) {
            console.log('Validation passed, calling savePlan()');
            savePlan();
        } else {
            console.log('Validation failed, not saving plan');
            showNotice('error', 'Please fix the validation errors before saving.');
        }
    });

    // Delete plan
    $(document).on('click', '.delete-plan', function() {
        const planId = $(this).data('plan-id');
        console.log('Delete plan clicked for plan ID:', planId);
        if (confirm(flexpressPricing.strings.confirmDelete)) {
            deletePlan(planId);
        }
    });

    // Toggle plan status
    $(document).on('click', '.toggle-plan-status', function() {
        const planId = $(this).data('plan-id');
        console.log('Toggle plan status clicked for plan ID:', planId);
        togglePlanStatus(planId);
    });

    // Test Flowguard connection
    $(document).on('click', '#test-flowguard-connection', function() {
        console.log('Test Flowguard connection clicked');
        testFlowguardConnection();
    });

    // Validate pricing plans
    $(document).on('click', '#validate-pricing-plans', function() {
        console.log('Validate pricing plans clicked');
        validatePricingPlans();
    });

    /**
     * Open modal for add/edit
     */
    function openModal(mode, planId = null) {
        console.log('Opening modal in mode:', mode, 'with plan ID:', planId);
        
        if (mode === 'add') {
            $modalTitle.text('Add New Pricing Plan');
            $form[0].reset();
            $('#plan-id').val('');
            $trialSettings.hide();
            $trialSettings.find('input').prop('required', false);
            
            // Set default values for new plans
            $('#plan-active').prop('checked', true);
            $('#plan-currency').val('$');
            $('#plan-duration').val('30');
            $('#plan-duration-unit').val('days');
            $('#plan-type').val('recurring');
            $('#plan-sort-order').val('0');
            $('#promo-codes-container').hide();
            $('#plan-promo-only').prop('checked', false);
            
            console.log('New plan defaults set - active:', $('#plan-active').prop('checked'));
        } else if (mode === 'edit') {
            $modalTitle.text('Edit Pricing Plan');
            loadPlanData(planId);
        }
        
        console.log('About to show modal');
        $modal.fadeIn();
        $('body').addClass('modal-open');
        console.log('Modal shown');
    }

    /**
     * Close modal
     */
    function closeModal() {
        console.log('Closing modal');
        $modal.fadeOut();
        $('body').removeClass('modal-open');
        $form[0].reset();
        $trialSettings.hide();
        $trialSettings.find('input').prop('required', false);
        // Clear any error messages
        $form.find('.error-message').remove();
        $form.find('.error').removeClass('error');
    }

    /**
     * Load plan data for editing
     */
    function loadPlanData(planId) {
        console.log('Loading plan data for plan ID:', planId);
        
        // Check if flexpressPricing object exists
        if (typeof flexpressPricing === 'undefined') {
            console.error('flexpressPricing object not found!');
            alert('Error: AJAX configuration not found. Please refresh the page.');
            return;
        }
        
        console.log('AJAX URL:', flexpressPricing.ajaxurl);
        console.log('Nonce:', flexpressPricing.nonce);
        
        // Show loading state
        $form.find('input, select, textarea').prop('disabled', true);
        $modalTitle.text('Loading Plan Data...');
        
        $.ajax({
            url: flexpressPricing.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_pricing_plan',
                plan_id: planId,
                nonce: flexpressPricing.nonce
            },
            beforeSend: function() {
                console.log('AJAX request starting...');
            },
            success: function(response) {
                console.log('AJAX response received:', response);
                
                if (response.success) {
                    const plan = response.data;
                    console.log('Plan data:', plan);
                    
                    // Update modal title
                    $modalTitle.text('Edit Pricing Plan');
                    
                    // Populate basic information
                    $('#plan-id').val(planId);
                    $('#plan-name').val(plan.name || '');
                    $('#plan-description').val(plan.description || '');
                    $('#plan-price').val(plan.price || '');
                    $('#plan-currency').val(plan.currency || '$');
                    $('#plan-type').val(plan.plan_type || 'recurring');
                    
                    // Handle plan type specific settings
                    if (plan.plan_type === 'one_time') {
                        $trialEnabled.prop('checked', false).prop('disabled', true);
                        $trialSettings.hide();
                        $trialSettings.find('input').prop('required', false);
                        
                        // Enable duration fields for one-time payments
                        $('#plan-duration').prop('disabled', false);
                        $('#plan-duration-unit').prop('disabled', false);
                    } else if (plan.plan_type === 'lifetime') {
                        $trialEnabled.prop('checked', false).prop('disabled', true);
                        $trialSettings.hide();
                        $trialSettings.find('input').prop('required', false);
                        
                        // Disable duration fields for lifetime access
                        $('#plan-duration').prop('disabled', true);
                        $('#plan-duration-unit').prop('disabled', true);
                    } else {
                        $trialEnabled.prop('disabled', false);
                        handleTrialSettings();
                        
                        // Enable duration fields for recurring plans
                        $('#plan-duration').prop('disabled', false);
                        $('#plan-duration-unit').prop('disabled', false);
                    }
                    
                    // Populate duration settings
                    $('#plan-duration').val(plan.duration || '');
                    $('#plan-duration-unit').val(plan.duration_unit || 'days');
                    $('#plan-sort-order').val(plan.sort_order || 0);
                    
                    // Populate trial settings
                    const trialEnabled = plan.trial_enabled == 1;
                    $('#trial-enabled').prop('checked', trialEnabled);
                    
                    if (trialEnabled) {
                        $('#trial-price').val(plan.trial_price || '');
                        $('#trial-duration').val(plan.trial_duration || '');
                        $('#trial-duration-unit').val(plan.trial_duration_unit || 'days');
                        $trialSettings.show();
                        $trialSettings.find('input').prop('required', true);
                    } else {
                        $trialSettings.hide();
                        $trialSettings.find('input').prop('required', false);
                    }
                    
                    // Populate Flowguard settings
                    $('#flowguard-shop-id').val(plan.flowguard_shop_id || '');
                    $('#flowguard-product-id').val(plan.flowguard_product_id || '');
                    
                    // Populate display options
                    $('#plan-featured').prop('checked', plan.featured == 1);
                    $('#plan-active').prop('checked', plan.active == 1);
                    
                    // Populate promotional options
                    $('#plan-promo-only').prop('checked', plan.promo_only == 1);
                    $('#plan-promo-codes').val(plan.promo_codes || '');
                    
                    // Show/hide promo codes field based on promo_only setting
                    if (plan.promo_only == 1) {
                        $('#promo-codes-container').show();
                    } else {
                        $('#promo-codes-container').hide();
                    }
                    
                    console.log('Form populated successfully');
                    
                } else {
                    console.error('AJAX request failed:', response.data);
                    $modalTitle.text('Edit Pricing Plan');
                    showNotice('error', response.data || flexpressPricing.strings.error);
                    closeModal();
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText,
                    statusCode: xhr.status
                });
                $modalTitle.text('Edit Pricing Plan');
                showNotice('error', 'Failed to load plan data. Please try again.');
                closeModal();
            },
            complete: function() {
                console.log('AJAX request completed');
                // Re-enable form fields
                $form.find('input, select, textarea').prop('disabled', false);
            }
        });
    }

    /**
     * Save pricing plan
     */
    function savePlan() {
        console.log('savePlan called');
        
        // Debug: Check if flexpressPricing object exists
        if (typeof flexpressPricing === 'undefined') {
            console.error('flexpressPricing object not found!');
            showNotice('error', 'Configuration error: AJAX settings not found. Please refresh the page.');
            return;
        }
        
        console.log('AJAX URL:', flexpressPricing.ajaxurl);
        console.log('Nonce:', flexpressPricing.nonce);
        
        // Build form data and explicitly handle unchecked checkboxes
        const formData = $form.serialize();
        console.log('Serialized form data:', formData);
        
        // Add unchecked checkbox values explicitly
        const checkboxes = ['trial_enabled', 'featured', 'active', 'promo_only'];
        let additionalData = '';
        checkboxes.forEach(function(name) {
            const $checkbox = $form.find(`[name="${name}"]`);
            if ($checkbox.length && !$checkbox.is(':checked')) {
                additionalData += `&${name}=0`;
            }
        });
        
        const finalData = formData + additionalData + '&action=save_pricing_plan&nonce=' + flexpressPricing.nonce;
        console.log('Final data being sent:', finalData);
        
        $.ajax({
            url: flexpressPricing.ajaxurl,
            type: 'POST',
            data: finalData,
            beforeSend: function() {
                $form.find('button[type="submit"]').prop('disabled', true).text('Saving...');
            },
            success: function(response) {
                console.log('AJAX response:', response);
                if (response.success) {
                    showNotice('success', response.data.message);
                    closeModal();
                    // Reload the page to show updated plans
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showNotice('error', response.data || flexpressPricing.strings.error);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error, xhr.responseText);
                showNotice('error', flexpressPricing.strings.error);
            },
            complete: function() {
                $form.find('button[type="submit"]').prop('disabled', false).text('Save Plan');
            }
        });
    }

    /**
     * Delete pricing plan
     */
    function deletePlan(planId) {
        $.ajax({
            url: flexpressPricing.ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_pricing_plan',
                plan_id: planId,
                nonce: flexpressPricing.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotice('success', response.data);
                    $(`.pricing-plan-card[data-plan-id="${planId}"]`).fadeOut(function() {
                        $(this).remove();
                        
                        // Show no plans message if all plans are deleted
                        if ($('.pricing-plan-card').length === 0) {
                            $('#pricing-plans-list').html(
                                '<div class="no-plans-message">' +
                                '<p>No pricing plans configured yet. Click "Add New Plan" to create your first plan.</p>' +
                                '</div>'
                            );
                        }
                    });
                } else {
                    showNotice('error', response.data || flexpressPricing.strings.error);
                }
            },
            error: function() {
                showNotice('error', flexpressPricing.strings.error);
            }
        });
    }

    /**
     * Toggle plan status
     */
    function togglePlanStatus(planId) {
        const $button = $(`.toggle-plan-status[data-plan-id="${planId}"]`);
        const $card = $(`.pricing-plan-card[data-plan-id="${planId}"]`);
        
        $.ajax({
            url: flexpressPricing.ajaxurl,
            type: 'POST',
            data: {
                action: 'toggle_plan_status',
                plan_id: planId,
                nonce: flexpressPricing.nonce
            },
            beforeSend: function() {
                $button.prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    showNotice('success', response.data);
                    
                    // Toggle visual state
                    if ($card.hasClass('inactive')) {
                        $card.removeClass('inactive');
                        $button.text('Deactivate');
                        $card.find('.plan-badge.inactive').remove();
                    } else {
                        $card.addClass('inactive');
                        $button.text('Activate');
                        $card.find('.plan-title').append('<span class="plan-badge inactive">Inactive</span>');
                    }
                } else {
                    showNotice('error', response.data || flexpressPricing.strings.error);
                }
            },
            error: function() {
                showNotice('error', flexpressPricing.strings.error);
            },
            complete: function() {
                $button.prop('disabled', false);
            }
        });
    }

    /**
     * Show admin notice
     */
    function showNotice(type, message) {
        const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        const $notice = $(`<div class="notice ${noticeClass} is-dismissible"><p>${message}</p></div>`);
        
        $('.wrap h1').after($notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
        
        // Add dismiss functionality
        $notice.on('click', '.notice-dismiss', function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        });
        
        // Add the dismiss button
        $notice.append('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>');
    }

    // Form validation
    $form.find('input[required], select[required]').on('blur', function() {
        validateField($(this));
    });

    /**
     * Validate individual field
     */
    function validateField($field) {
        const value = $field.val().trim();
        const fieldName = $field.attr('name');
        let isValid = true;
        let errorMessage = '';

        // Remove existing error styling
        $field.removeClass('error');
        $field.next('.error-message').remove();

        // Required field validation
        if ($field.prop('required') && !value) {
            isValid = false;
            errorMessage = 'This field is required.';
        }

        // Specific field validations
        switch (fieldName) {
            case 'price':
            case 'trial_price':
                if (value && (isNaN(value) || parseFloat(value) < 0)) {
                    isValid = false;
                    errorMessage = 'Please enter a valid price.';
                }
                break;
            
            case 'duration':
            case 'trial_duration':
                if (value && (isNaN(value) || parseInt(value) < 1)) {
                    isValid = false;
                    errorMessage = 'Duration must be at least 1.';
                }
                break;
        }

        // Show error if validation fails
        if (!isValid) {
            $field.addClass('error');
            $field.after(`<span class="error-message" style="color: #d63638; font-size: 12px; display: block; margin-top: 2px;">${errorMessage}</span>`);
        }

        return isValid;
    }

    // Add CSS for error states
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .modal-open { overflow: hidden; }
            .pricing-plan-card .button:disabled { opacity: 0.6; cursor: not-allowed; }
            .form-row input.error, .form-row select.error, .form-row textarea.error {
                border-color: #d63638;
                box-shadow: 0 0 0 1px #d63638;
            }
            .notice { margin: 15px 0; }
            .notice-dismiss { 
                float: right; 
                padding: 9px; 
                text-decoration: none;
                background: none;
                border: none;
                cursor: pointer;
            }
        `)
        .appendTo('head');

    // Toggle promo codes field visibility
    $('#plan-promo-only').on('change', function() {
        if ($(this).is(':checked')) {
            $('#promo-codes-container').slideDown();
        } else {
            $('#promo-codes-container').slideUp();
        }
    });

    /**
     * Test Flowguard connection
     */
    function testFlowguardConnection() {
        const $button = $('#test-flowguard-connection');
        
        $button.prop('disabled', true).text('Testing...');
        
        $.ajax({
            url: flexpressPricing.ajaxurl,
            type: 'POST',
            data: {
                action: 'test_flowguard_connection',
                nonce: flexpressPricing.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotice('success', 'Flowguard connection successful! ' + response.data);
                } else {
                    showNotice('error', 'Flowguard connection failed: ' + response.data);
                }
            },
            error: function() {
                showNotice('error', 'Failed to test Flowguard connection. Please try again.');
            },
            complete: function() {
                $button.prop('disabled', false).text('Test Flowguard Connection');
            }
        });
    }

    /**
     * Validate pricing plans
     */
    function validatePricingPlans() {
        const $button = $('#validate-pricing-plans');
        
        $button.prop('disabled', true).text('Validating...');
        
        $.ajax({
            url: flexpressPricing.ajaxurl,
            type: 'POST',
            data: {
                action: 'validate_pricing_plans',
                nonce: flexpressPricing.nonce
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    let message = `Validation completed successfully! `;
                    message += `Total plans: ${data.summary.total_plans}, `;
                    message += `Plans with warnings: ${data.summary.plans_with_warnings}`;
                    
                    if (data.total_warnings > 0) {
                        message += `\n\nWarnings found: ${data.total_warnings}`;
                    }
                    
                    showNotice('success', message);
                } else {
                    const data = response.data;
                    let message = `Validation found issues:\n`;
                    message += `Total errors: ${data.total_errors}, `;
                    message += `Total warnings: ${data.total_warnings}\n\n`;
                    
                    // Show first few errors
                    let errorCount = 0;
                    for (const planId in data.validation_results) {
                        const result = data.validation_results[planId];
                        if (result.errors.length > 0 && errorCount < 3) {
                            message += `${result.name}: ${result.errors.join(', ')}\n`;
                            errorCount++;
                        }
                    }
                    
                    showNotice('error', message);
                }
            },
            error: function() {
                showNotice('error', 'Failed to validate pricing plans. Please try again.');
            },
            complete: function() {
                $button.prop('disabled', false).text('Validate Plans');
            }
        });
    }
});