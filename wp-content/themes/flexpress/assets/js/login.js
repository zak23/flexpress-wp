jQuery(document).ready(function($) {
    
    // Handle login form submission
    $('#flexpress-login-form').on('submit', function(e) {
        e.preventDefault();
        
        // Clear previous messages
        $('.form-message').remove();
        
        // Show loading message
        $('#flexpress-login-submit').prop('disabled', true);
        $('#login-status').html('<div class="alert alert-info">' + ajax_login_object.loadingmessage + '</div>');
        
        // Collect data from form
        var formData = {
            'action': 'flexpress_ajax_login',
            'username': $('#user_login').val(),
            'password': $('#user_pass').val(),
            'remember': $('#rememberme').is(':checked'),
            'security': $('#security').val()
        };
        
        // Send AJAX request
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: ajax_login_object.ajaxurl,
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#login-status').html('<div class="alert alert-success">' + response.message + '</div>');
                    
                    // Check for redirect_to parameter in URL or use default
                    const urlParams = new URLSearchParams(window.location.search);
                    const redirectTo = urlParams.get('redirect_to');
                    const redirectUrl = redirectTo ? decodeURIComponent(redirectTo) : ajax_login_object.redirecturl;
                    
                    window.location.href = redirectUrl;
                } else {
                    $('#login-status').html('<div class="alert alert-danger">' + response.message + '</div>');
                    $('#flexpress-login-submit').prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                $('#login-status').html('<div class="alert alert-danger">An error occurred. Please try again.</div>');
                $('#flexpress-login-submit').prop('disabled', false);
            }
        });
    });
    
    // Handle forgot password form submission
    $('#flexpress-forgot-form').on('submit', function(e) {
        e.preventDefault();
        
        // Clear previous messages
        $('.form-message').remove();
        
        // Show loading message
        $('#flexpress-forgot-submit').prop('disabled', true);
        $('#forgot-status').html('<div class="alert alert-info">Processing your request...</div>');
        
        // Collect data from form
        var formData = {
            'action': 'flexpress_ajax_password_reset',
            'email': $('#user_email').val(),
            'security': $('#forgot_security').val()
        };
        
        // Send AJAX request
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: ajax_login_object.ajaxurl,
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#forgot-status').html('<div class="alert alert-success">' + response.message + '</div>');
                    // Clear the form
                    $('#user_email').val('');
                } else {
                    $('#forgot-status').html('<div class="alert alert-danger">' + response.message + '</div>');
                }
                $('#flexpress-forgot-submit').prop('disabled', false);
            },
            error: function(xhr, status, error) {
                $('#forgot-status').html('<div class="alert alert-danger">An error occurred. Please try again.</div>');
                $('#flexpress-forgot-submit').prop('disabled', false);
            }
        });
    });
    
    // Toggle between login and forgot password forms
    $('#show-forgot-form').on('click', function(e) {
        e.preventDefault();
        $('#login-form-container').hide();
        $('#forgot-form-container').show();
    });
    
    $('#show-login-form').on('click', function(e) {
        e.preventDefault();
        $('#forgot-form-container').hide();
        $('#login-form-container').show();
    });
    
    // Password strength meter for reset password form
    if ($('#reset-password-form').length) {
        var passwordStrength = {
            0: 'Very weak',
            1: 'Weak',
            2: 'Medium',
            3: 'Strong',
            4: 'Very strong'
        };
        
        $('#pass1').on('keyup', function() {
            var password = $(this).val();
            var strength = 0;
            
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/\d+/)) strength++;
            if (password.match(/.[!,@,#,$,%,^,&,*,?,_,~,-,(,)]/)) strength++;
            
            $('#password-strength').removeClass('text-danger text-warning text-info text-success');
            
            if (strength <= 1) {
                $('#password-strength')
                    .addClass('text-danger')
                    .text(passwordStrength[strength]);
            } else if (strength == 2) {
                $('#password-strength')
                    .addClass('text-warning')
                    .text(passwordStrength[strength]);
            } else if (strength == 3) {
                $('#password-strength')
                    .addClass('text-info')
                    .text(passwordStrength[strength]);
            } else {
                $('#password-strength')
                    .addClass('text-success')
                    .text(passwordStrength[strength]);
            }
        });
        
        // Check if passwords match
        $('#pass2').on('keyup', function() {
            if ($('#pass1').val() === $(this).val()) {
                $('#password-match').addClass('text-success').removeClass('text-danger').text('Passwords match');
            } else {
                $('#password-match').addClass('text-danger').removeClass('text-success').text('Passwords do not match');
            }
        });
    }
}); 