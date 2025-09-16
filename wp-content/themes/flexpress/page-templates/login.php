<?php
/**
 * Template Name: Login
 */

// Redirect if already logged in
if (is_user_logged_in()) {
    wp_redirect(home_url('/my-account'));
    exit;
}

get_header();
?>

<div class="site-main">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                
                <!-- Login Form Container -->
                <div id="login-form-container" class="card shadow-sm">
                    <div class="card-body p-4 text-dark">
                        <div class="text-center mb-4">
                            <h1 class="h3 mb-3 text-dark"><?php esc_html_e('Welcome Back', 'flexpress'); ?></h1>
                            <p class="text-muted"><?php esc_html_e('Sign in to your account to continue', 'flexpress'); ?></p>
                        </div>

                        <div id="login-status">
                            <?php
                            // Show success message after password change
                            if (isset($_GET['password']) && $_GET['password'] === 'changed') {
                                echo '<div class="alert alert-success">';
                                echo esc_html__('Your password has been changed successfully. You can now log in with your new password.', 'flexpress');
                                echo '</div>';
                            }
                            
                            // Show any error messages
                            if (isset($_GET['login']) && $_GET['login'] === 'failed') {
                                echo '<div class="alert alert-danger">';
                                echo esc_html__('Invalid username or password.', 'flexpress');
                                echo '</div>';
                            }
                            ?>
                        </div>

                        <form id="flexpress-login-form" class="needs-validation text-dark" novalidate>
                            <?php wp_nonce_field('ajax-login-nonce', 'security'); ?>
                            
                            <div class="mb-3">
                                <label for="user_login" class="form-label text-dark"><?php esc_html_e('Email Address', 'flexpress'); ?></label>
                                <input type="email" class="form-control" id="user_login" name="log" required>
                                <div class="invalid-feedback text-dark">
                                    <?php esc_html_e('Please enter your email address.', 'flexpress'); ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="user_pass" class="form-label text-dark"><?php esc_html_e('Password', 'flexpress'); ?></label>
                                <input type="password" class="form-control" id="user_pass" name="pwd" required>
                                <div class="invalid-feedback text-dark">
                                    <?php esc_html_e('Please enter your password.', 'flexpress'); ?>
                                </div>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="rememberme" name="rememberme" value="forever">
                                <label class="form-check-label text-dark" for="rememberme"><?php esc_html_e('Remember me', 'flexpress'); ?></label>
                            </div>

                            <button type="submit" id="flexpress-login-submit" class="btn btn-primary w-100 mb-3">
                                <?php esc_html_e('Sign In', 'flexpress'); ?>
                            </button>

                            <div class="text-center">
                                <a href="#" id="show-forgot-form" class="text-decoration-none text-primary">
                                    <?php esc_html_e('Forgot your password?', 'flexpress'); ?>
                                </a>
                            </div>
                        </form>

                        <hr class="my-4">

                        <div class="text-center">
                            <p class="mb-3 text-dark"><?php esc_html_e('Don\'t have an account?', 'flexpress'); ?></p>
                            <a href="<?php echo esc_url(home_url('/join')); ?>" class="btn btn-outline-primary">
                                <?php esc_html_e('Create Account', 'flexpress'); ?>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Forgot Password Form Container (Hidden by default) -->
                <div id="forgot-form-container" class="card shadow-sm" style="display: none;">
                    <div class="card-body p-4 text-dark">
                        <div class="text-center mb-4">
                            <h1 class="h3 mb-3 text-dark"><?php esc_html_e('Reset Password', 'flexpress'); ?></h1>
                            <p class="text-muted"><?php esc_html_e('Enter your email address and we\'ll send you instructions to reset your password.', 'flexpress'); ?></p>
                        </div>

                        <div id="forgot-status">
                            <?php
                            // Show error messages
                            if (isset($_GET['error'])) {
                                if ($_GET['error'] === 'invalidkey') {
                                    echo '<div class="alert alert-danger">';
                                    echo esc_html__('Invalid password reset key. Please request a new password reset link.', 'flexpress');
                                    echo '</div>';
                                } elseif ($_GET['error'] === 'expiredkey') {
                                    echo '<div class="alert alert-danger">';
                                    echo esc_html__('Password reset key has expired. Please request a new password reset link.', 'flexpress');
                                    echo '</div>';
                                }
                            }
                            ?>
                        </div>

                        <form id="flexpress-forgot-form" class="needs-validation text-dark" novalidate>
                            <?php wp_nonce_field('ajax-forgot-nonce', 'forgot_security'); ?>
                            
                            <div class="mb-3">
                                <label for="user_email" class="form-label text-dark"><?php esc_html_e('Email Address', 'flexpress'); ?></label>
                                <input type="email" class="form-control" id="user_email" name="user_email" required>
                                <div class="invalid-feedback text-dark">
                                    <?php esc_html_e('Please enter your email address.', 'flexpress'); ?>
                                </div>
                            </div>

                            <button type="submit" id="flexpress-forgot-submit" class="btn btn-primary w-100 mb-3">
                                <?php esc_html_e('Send Reset Instructions', 'flexpress'); ?>
                            </button>

                            <div class="text-center">
                                <a href="#" id="show-login-form" class="text-decoration-none text-primary">
                                    <?php esc_html_e('Back to Login', 'flexpress'); ?>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>

<?php
get_footer(); 