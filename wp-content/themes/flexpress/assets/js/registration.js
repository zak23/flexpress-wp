jQuery(document).ready(function($) {
    // Check if flexpressRegistration is available
    if (typeof flexpressRegistration === 'undefined') {
        console.log('FlexPress Registration: flexpressRegistration object not found - skipping initialization');
        return;
    }

    // Skip processing on join page to avoid conflicts
    if (flexpressRegistration.isJoinPage) {
        console.log('FlexPress Registration: Join page detected - skipping shortcode form processing');
        return;
    }

    const form = $('#flexpress-register-form');
    const errorDiv = $('#registration-error');

    // Only proceed if this is the shortcode form, not the join page form
    if (form.length && !form.closest('.join-page-form').length) {
        
    // Form validation
    form.on('submit', function(e) {
        e.preventDefault();

        // Reset previous error messages
        errorDiv.addClass('d-none');
        form.find('.is-invalid').removeClass('is-invalid');

        // Validate form
        if (!this.checkValidity()) {
            e.stopPropagation();
            form.addClass('was-validated');
            return;
        }

        // Check if passwords match
        const password = $('#password').val();
        const passwordConfirm = $('#password_confirm').val();

        if (password !== passwordConfirm) {
            $('#password_confirm').addClass('is-invalid');
            return;
        }

        // Get form data
        const formData = new FormData(this);
        formData.append('action', 'flexpress_register');
        formData.append('nonce', flexpressRegistration.nonce);

        // Disable submit button
        const submitButton = form.find('button[type="submit"]');
        const originalText = submitButton.text();
        submitButton.prop('disabled', true).text('Creating Account...');

        // Submit form
        $.ajax({
            url: flexpressRegistration.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Redirect to success page
                    window.location.href = response.data.redirect_url;
                } else {
                    // Show error message
                    errorDiv.removeClass('d-none').text(response.data.message);
                    submitButton.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                // Show generic error message
                errorDiv.removeClass('d-none').text('An error occurred. Please try again.');
                submitButton.prop('disabled', false).text(originalText);
            }
        });
    });

    // Password strength indicator
    $('#password').on('input', function() {
        const password = $(this).val();
        let strength = 0;

        // Length check
        if (password.length >= 8) strength += 1;
        
        // Contains number
        if (/\d/.test(password)) strength += 1;
        
        // Contains lowercase
        if (/[a-z]/.test(password)) strength += 1;
        
        // Contains uppercase
        if (/[A-Z]/.test(password)) strength += 1;
        
        // Contains special character
        if (/[^A-Za-z0-9]/.test(password)) strength += 1;

        // Update strength indicator
        const strengthIndicator = $('<div class="password-strength mt-2"></div>');
        const strengthText = ['Very Weak', 'Weak', 'Medium', 'Strong', 'Very Strong'];
        const strengthClass = ['danger', 'warning', 'info', 'primary', 'success'];

        if (password.length > 0) {
            strengthIndicator.html(`
                <div class="progress" style="height: 5px;">
                    <div class="progress-bar bg-${strengthClass[strength - 1]}" 
                         role="progressbar" 
                         style="width: ${(strength / 5) * 100}%">
                    </div>
                </div>
                <small class="text-${strengthClass[strength - 1]} mt-1 d-block">
                    ${strengthText[strength - 1]}
                </small>
            `);
        }

        $(this).next('.password-strength').remove();
        $(this).after(strengthIndicator);
    });

    // Real-time password match validation
    $('#password_confirm').on('input', function() {
        const password = $('#password').val();
        const confirmPassword = $(this).val();

        if (confirmPassword && password !== confirmPassword) {
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
    });
    
    } // Close the if statement for form checking
}); 