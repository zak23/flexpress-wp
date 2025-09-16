<?php
/**
 * Template Name: My Account
 * Description: Account management page
 */

// Detect subscription status query param to allow cancellation notice display
$subscription_status = isset($_GET['subscription']) ? sanitize_text_field($_GET['subscription']) : '';

// If user is logged in and there is no cancellation notice to show, redirect to dashboard
if (is_user_logged_in() && $subscription_status !== 'cancelled' && $subscription_status !== 'expired') {
    wp_redirect(home_url('/dashboard/'));
    exit;
}

// If not logged in, show login form
get_header();
?>

<div class="container mt-5 pt-5">
    <?php if ($subscription_status === 'cancelled') : ?>
        <div class="alert alert-success text-center mb-4">
            <?php esc_html_e('Your subscription has been cancelled. We are sorry to see you go.', 'flexpress'); ?>
        </div>
    <?php elseif ($subscription_status === 'expired') : ?>
        <div class="alert alert-warning text-center mb-4">
            <?php esc_html_e('Your subscription has expired. Please renew your membership to regain access to premium content.', 'flexpress'); ?>
            <a href="<?php echo esc_url(home_url('/join')); ?>" class="alert-link"><?php esc_html_e('Renew now', 'flexpress'); ?></a>.
        </div>
    <?php endif; ?>
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="text-center mb-5">
                <h1 class="h2 mb-3"><?php esc_html_e('Welcome Back', 'flexpress'); ?></h1>
                <p class="text-muted"><?php esc_html_e('Sign in to your account to continue', 'flexpress'); ?></p>
            </div>

            <!-- Login Form Container -->
            <div id="login-form-container" class="card shadow-sm">
                <div class="card-body p-4">
                    <div class="alert alert-danger d-none" id="login-error"></div>
                    <div class="alert alert-success d-none" id="login-success"></div>

                    <form id="flexpress-login-form" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="user_login" class="form-label"><?php esc_html_e('Email Address', 'flexpress'); ?></label>
                            <input type="email" class="form-control" id="user_login" name="log" required>
                            <div class="invalid-feedback"><?php esc_html_e('Please enter your email address.', 'flexpress'); ?></div>
                        </div>

                        <div class="mb-3">
                            <label for="user_pass" class="form-label"><?php esc_html_e('Password', 'flexpress'); ?></label>
                            <input type="password" class="form-control" id="user_pass" name="pwd" required>
                            <div class="invalid-feedback"><?php esc_html_e('Please enter your password.', 'flexpress'); ?></div>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="rememberme" id="rememberme">
                            <label class="form-check-label" for="rememberme">
                                <?php esc_html_e('Remember me', 'flexpress'); ?>
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <?php esc_html_e('Sign In', 'flexpress'); ?>
                        </button>

                        <div class="text-center">
                            <a href="#" id="show-reset-form" class="text-decoration-none">
                                <?php esc_html_e('Forgot your password?', 'flexpress'); ?>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="text-center mt-4">
                <hr class="my-4">
                <p class="text-muted mb-3"><?php esc_html_e("Don't have an account?", 'flexpress'); ?></p>
                <a href="<?php echo esc_url(home_url('/join')); ?>" class="btn btn-outline-primary">
                    <?php esc_html_e('Create Account', 'flexpress'); ?>
                </a>
            </div>

            <!-- Reset Password Form (Hidden by default) -->
            <div id="reset-form-container" class="card shadow-sm mt-4" style="display: none;">
                <div class="card-body p-4">
                    <h2 class="h4 mb-3"><?php esc_html_e('Reset Password', 'flexpress'); ?></h2>
                    <p class="text-muted mb-4"><?php esc_html_e("Enter your email address and we'll send you instructions to reset your password.", 'flexpress'); ?></p>
                    
                    <div class="alert alert-danger d-none" id="reset-error"></div>
                    <div class="alert alert-success d-none" id="reset-success"></div>

                    <form id="flexpress-reset-form" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="reset_email" class="form-label"><?php esc_html_e('Email Address', 'flexpress'); ?></label>
                            <input type="email" class="form-control" id="reset_email" name="user_login" required>
                            <div class="invalid-feedback"><?php esc_html_e('Please enter your email address.', 'flexpress'); ?></div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <?php esc_html_e('Send Reset Instructions', 'flexpress'); ?>
                        </button>

                        <div class="text-center">
                            <a href="#" id="show-login-form" class="text-decoration-none">
                                <?php esc_html_e('Back to Login', 'flexpress'); ?>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Toggle between login and reset forms
    $('#show-reset-form').on('click', function(e) {
        e.preventDefault();
        $('#login-form-container').hide();
        $('#reset-form-container').show();
    });

    $('#show-login-form').on('click', function(e) {
        e.preventDefault();
        $('#reset-form-container').hide();
        $('#login-form-container').show();
    });

    // Login form submission
    $('#flexpress-login-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        var originalText = $submitBtn.text();
        
        // Clear previous messages
        $('.alert').addClass('d-none');
        
        // Show loading state
        $submitBtn.prop('disabled', true).text('Signing In...');
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'flexpress_ajax_login',
                username: $('#user_login').val(),
                password: $('#user_pass').val(),
                remember: $('#rememberme').is(':checked') ? 1 : 0,
                security: '<?php echo wp_create_nonce('ajax-login-nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $('#login-success').removeClass('d-none').text(response.message);
                    // Redirect to dashboard after successful login
                    window.location.href = '<?php echo home_url('/dashboard/'); ?>';
                } else {
                    $('#login-error').removeClass('d-none').text(response.message);
                }
            },
            error: function() {
                $('#login-error').removeClass('d-none').text('An error occurred. Please try again.');
            },
            complete: function() {
                $submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });

    // Reset form submission
    $('#flexpress-reset-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        var originalText = $submitBtn.text();
        
        // Clear previous messages
        $('.alert').addClass('d-none');
        
        // Show loading state
        $submitBtn.prop('disabled', true).text('Sending...');
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'flexpress_ajax_password_reset',
                email: $('#reset_email').val(),
                security: '<?php echo wp_create_nonce('ajax-forgot-nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $('#reset-success').removeClass('d-none').text(response.message);
                    $('#reset_email').val('');
                } else {
                    $('#reset-error').removeClass('d-none').text(response.message);
                }
            },
            error: function() {
                $('#reset-error').removeClass('d-none').text('An error occurred. Please try again.');
            },
            complete: function() {
                $submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });
});
</script>

<?php
get_footer();
?> 