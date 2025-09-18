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

<div class="membership-page">
    <div class="container py-5">
        <div class="row justify-content-center mb-5">
            <div class="col-md-10 text-center">
                <h1 class="display-4 mb-4"><?php esc_html_e('Welcome Back', 'flexpress'); ?></h1>
                <p class="lead mb-4"><?php esc_html_e('Sign in to your account to continue', 'flexpress'); ?></p>
            </div>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-6">
                
                <!-- Login Form Container -->
                <div id="login-form-container" class="card bg-dark">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <h2 class="h3 mb-3"><?php esc_html_e('Sign In', 'flexpress'); ?></h2>
                            <p class="text-muted"><?php esc_html_e('Enter your credentials to access your account', 'flexpress'); ?></p>
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

                        <form id="flexpress-login-form" class="needs-validation" novalidate>
                            <?php wp_nonce_field('ajax-login-nonce', 'security'); ?>
                            
                            <div class="mb-3">
                                <label for="user_login" class="form-label"><?php esc_html_e('Email Address', 'flexpress'); ?></label>
                                <input type="email" class="form-control" id="user_login" name="log" required>
                                <div class="invalid-feedback">
                                    <?php esc_html_e('Please enter your email address.', 'flexpress'); ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="user_pass" class="form-label"><?php esc_html_e('Password', 'flexpress'); ?></label>
                                <input type="password" class="form-control" id="user_pass" name="pwd" required>
                                <div class="invalid-feedback">
                                    <?php esc_html_e('Please enter your password.', 'flexpress'); ?>
                                </div>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="rememberme" name="rememberme" value="forever">
                                <label class="form-check-label" for="rememberme"><?php esc_html_e('Remember me', 'flexpress'); ?></label>
                            </div>

                            <button type="submit" id="flexpress-login-submit" class="btn btn-primary btn-lg w-100 mb-3">
                                <?php esc_html_e('Sign In', 'flexpress'); ?>
                            </button>

                            <div class="text-center">
                                <a href="#" id="show-forgot-form" class="legal-link">
                                    <?php esc_html_e('Forgot your password?', 'flexpress'); ?>
                                </a>
                            </div>
                        </form>

                        <hr class="my-4">

                        <div class="text-center">
                            <p class="mb-3"><?php esc_html_e('Don\'t have an account?', 'flexpress'); ?></p>
                            <a href="<?php echo esc_url(home_url('/join')); ?>" class="btn btn-outline-primary btn-lg">
                                <?php esc_html_e('Create Account', 'flexpress'); ?>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Forgot Password Form Container (Hidden by default) -->
                <div id="forgot-form-container" class="card bg-dark" style="display: none;">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <h2 class="h3 mb-3"><?php esc_html_e('Reset Password', 'flexpress'); ?></h2>
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

                        <form id="flexpress-forgot-form" class="needs-validation" novalidate>
                            <?php wp_nonce_field('ajax-forgot-nonce', 'forgot_security'); ?>
                            
                            <div class="mb-3">
                                <label for="user_email" class="form-label"><?php esc_html_e('Email Address', 'flexpress'); ?></label>
                                <input type="email" class="form-control" id="user_email" name="user_email" required>
                                <div class="invalid-feedback">
                                    <?php esc_html_e('Please enter your email address.', 'flexpress'); ?>
                                </div>
                            </div>

                            <button type="submit" id="flexpress-forgot-submit" class="btn btn-primary btn-lg w-100 mb-3">
                                <?php esc_html_e('Send Reset Instructions', 'flexpress'); ?>
                            </button>

                            <div class="text-center">
                                <a href="#" id="show-login-form" class="legal-link">
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

<style>
/* Login Page Styling to Match Join/Membership Pages */
.membership-page {
    background-color: var(--color-background);
    color: var(--color-text);
    min-height: 100vh;
}

.membership-page .card.bg-dark {
    background-color: var(--color-surface) !important;
    border: 1px solid var(--color-border);
    border-radius: 12px;
    box-shadow: var(--shadow-lg);
}

.membership-page .form-control {
    background-color: var(--color-background);
    border: 1px solid var(--color-border);
    color: var(--color-text);
    border-radius: 8px;
}

.membership-page .form-control:focus {
    background-color: var(--color-background);
    border-color: var(--color-accent);
    color: var(--color-text);
    box-shadow: 0 0 0 0.25rem rgba(var(--color-accent-rgb), 0.25);
}

.membership-page .form-control::placeholder {
    color: var(--color-text-secondary);
}

.membership-page .form-label {
    color: var(--color-text);
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.membership-page .form-check-label {
    color: var(--color-text-secondary);
}

.membership-page .btn-primary {
    background-color: var(--color-accent);
    border-color: var(--color-accent);
    color: var(--color-accent-text);
    border-radius: 8px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: var(--transition-fast);
}

.membership-page .btn-primary:hover {
    background-color: var(--color-accent-hover);
    border-color: var(--color-accent-hover);
    color: var(--color-accent-text);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(var(--color-accent-rgb), 0.3);
}

.membership-page .btn-outline-primary {
    border-color: var(--color-accent);
    color: var(--color-accent);
    background: transparent;
    border-radius: 8px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: var(--transition-fast);
}

.membership-page .btn-outline-primary:hover {
    background-color: var(--color-accent);
    border-color: var(--color-accent);
    color: var(--color-accent-text);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(var(--color-accent-rgb), 0.3);
}

.membership-page .legal-link {
    color: var(--color-accent);
    text-decoration: none;
    font-weight: 500;
    transition: var(--transition-fast);
}

.membership-page .legal-link:hover {
    color: var(--color-accent-hover);
    text-decoration: underline;
}

.membership-page .text-muted {
    color: var(--color-text-secondary) !important;
}

.membership-page .alert {
    border-radius: 8px;
    border: none;
}

.membership-page .alert-success {
    background-color: rgba(40, 167, 69, 0.1);
    border-color: rgba(40, 167, 69, 0.3);
    color: #28a745;
}

.membership-page .alert-danger {
    background-color: rgba(220, 53, 69, 0.1);
    border-color: rgba(220, 53, 69, 0.3);
    color: #dc3545;
}

.membership-page hr {
    border-color: var(--color-border);
}

/* Responsive Design */
@media (max-width: 768px) {
    .membership-page .display-4 {
        font-size: 2rem;
    }
    
    .membership-page .card-body {
        padding: 2rem !important;
    }
}
</style>

<?php
get_footer(); 