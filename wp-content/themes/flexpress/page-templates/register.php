<?php
/**
 * Template Name: Register
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
                <h1 class="display-4 mb-4"><?php esc_html_e('Join Our Community', 'flexpress'); ?></h1>
                <p class="lead mb-4"><?php esc_html_e('Create your account to access exclusive content', 'flexpress'); ?></p>
            </div>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-6">
                <div class="card bg-dark">
                    <div class="card-body p-5">
                        <h2 class="h3 mb-3"><?php esc_html_e('Create Your Account', 'flexpress'); ?></h2>
                        <p class="text-muted"><?php esc_html_e('Join our community and get access to exclusive content', 'flexpress'); ?></p>

                        <?php
                        // Show any error messages
                        if (isset($_GET['register']) && $_GET['register'] === 'failed') {
                            $error_message = '';
                            $error_type = $_GET['error'] ?? '';
                            
                            switch ($error_type) {
                                case 'security':
                                    $error_message = __('Security check failed. Please try again.', 'flexpress');
                                    break;
                                case 'fields':
                                    $error_message = __('Please fill in all required fields.', 'flexpress');
                                    break;
                                case 'password':
                                    $error_message = __('Passwords do not match.', 'flexpress');
                                    break;
                                case 'terms':
                                    $error_message = __('You must agree to the terms and conditions.', 'flexpress');
                                    break;
                                case 'email':
                                    $error_message = __('Please enter a valid email address.', 'flexpress');
                                    break;
                                case 'password_length':
                                    $error_message = __('Password must be at least 8 characters long.', 'flexpress');
                                    break;
                                case 'exists':
                                    $error_message = __('This email address is already registered.', 'flexpress');
                                    break;
                                case 'create':
                                    $error_message = __('Registration failed. Please try again.', 'flexpress');
                                    break;
                                default:
                                    $error_message = __('Registration failed. Please try again.', 'flexpress');
                            }
                            
                            echo '<div class="alert alert-danger">';
                            echo esc_html($error_message);
                            echo '</div>';
                        }
                        
                        // Show success message
                        if (isset($_GET['registered']) && $_GET['registered'] === 'success') {
                            echo '<div class="alert alert-success">';
                            echo esc_html__('Account created successfully! Welcome to our community.', 'flexpress');
                            echo '</div>';
                        }
                        ?>

                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="needs-validation" novalidate>
                            <?php wp_nonce_field('register_user', 'register_nonce'); ?>
                            <input type="hidden" name="action" value="register_user">

                            <div class="mb-3">
                                <label for="user_email" class="form-label"><?php esc_html_e('Email Address', 'flexpress'); ?></label>
                                <input type="email" class="form-control" id="user_email" name="user_email" required>
                                <div class="invalid-feedback">
                                    <?php esc_html_e('Please enter a valid email address.', 'flexpress'); ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="user_pass" class="form-label"><?php esc_html_e('Password', 'flexpress'); ?></label>
                                <input type="password" class="form-control" id="user_pass" name="user_pass" required minlength="8">
                                <div class="form-text"><?php esc_html_e('Password must be at least 8 characters long.', 'flexpress'); ?></div>
                                <div class="invalid-feedback">
                                    <?php esc_html_e('Please enter a password with at least 8 characters.', 'flexpress'); ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="user_pass_confirm" class="form-label"><?php esc_html_e('Confirm Password', 'flexpress'); ?></label>
                                <input type="password" class="form-control" id="user_pass_confirm" name="user_pass_confirm" required>
                                <div class="invalid-feedback">
                                    <?php esc_html_e('Please confirm your password.', 'flexpress'); ?>
                                </div>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                                <label class="form-check-label" for="terms">
                                    <?php
                                    printf(
                                        esc_html__('I agree to the %s and %s', 'flexpress'),
                                        '<a href="' . esc_url(home_url('/terms')) . '" class="text-decoration-none">' . esc_html__('Terms of Service', 'flexpress') . '</a>',
                                        '<a href="' . esc_url(home_url('/privacy')) . '" class="text-decoration-none">' . esc_html__('Privacy Policy', 'flexpress') . '</a>'
                                    );
                                    ?>
                                </label>
                                <div class="invalid-feedback">
                                    <?php esc_html_e('You must agree to the terms and conditions.', 'flexpress'); ?>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mb-3" id="register-submit">
                                <span class="btn-text"><?php esc_html_e('Create Account', 'flexpress'); ?></span>
                                <span class="btn-loading d-none">
                                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                    <?php esc_html_e('Creating Account...', 'flexpress'); ?>
                                </span>
                            </button>
                        </form>

                        <hr class="my-4">

                        <div class="text-center">
                            <p class="mb-3"><?php esc_html_e('Already have an account?', 'flexpress'); ?></p>
                            <a href="<?php echo esc_url(home_url('/login')); ?>" class="btn btn-outline-primary">
                                <?php esc_html_e('Sign In', 'flexpress'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation and submission
(function () {
    'use strict'
    
    const form = document.querySelector('.needs-validation');
    const submitBtn = document.getElementById('register-submit');
    const btnText = submitBtn.querySelector('.btn-text');
    const btnLoading = submitBtn.querySelector('.btn-loading');
    
    // Real-time password confirmation validation
    const password = document.getElementById('user_pass');
    const confirmPassword = document.getElementById('user_pass_confirm');
    
    function validatePasswords() {
        if (confirmPassword.value && password.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Passwords do not match');
            confirmPassword.classList.add('is-invalid');
        } else {
            confirmPassword.setCustomValidity('');
            confirmPassword.classList.remove('is-invalid');
        }
    }
    
    password.addEventListener('input', validatePasswords);
    confirmPassword.addEventListener('input', validatePasswords);
    
    form.addEventListener('submit', function (event) {
        // Validate passwords before form submission
        validatePasswords();
        
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            form.classList.add('was-validated');
            return;
        }
        
        // Show loading state
        submitBtn.disabled = true;
        btnText.classList.add('d-none');
        btnLoading.classList.remove('d-none');
        
        // Form will submit normally to admin-post.php
        // The loading state will be cleared on page reload/redirect
    });
    
    // Clear any existing validation on input
    const inputs = form.querySelectorAll('input');
    inputs.forEach(function(input) {
        input.addEventListener('input', function() {
            if (input.classList.contains('is-invalid')) {
                input.classList.remove('is-invalid');
            }
        });
    });
})()
</script>

<style>
/* Register Page Styling to Match Login/Membership Pages */
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
    border-color: var(--color-primary);
    color: var(--color-text);
    box-shadow: 0 0 0 0.2rem rgba(var(--color-primary-rgb), 0.25);
}

.membership-page .form-control::placeholder {
    color: var(--color-text-muted);
}

.membership-page .btn-primary {
    background-color: var(--color-primary);
    border-color: var(--color-primary);
    border-radius: 8px;
    font-weight: 600;
    padding: 12px 24px;
}

.membership-page .btn-primary:hover {
    background-color: var(--color-primary-hover);
    border-color: var(--color-primary-hover);
}

.membership-page .btn-outline-primary {
    color: var(--color-primary);
    border-color: var(--color-primary);
    border-radius: 8px;
    font-weight: 600;
    padding: 12px 24px;
}

.membership-page .btn-outline-primary:hover {
    background-color: var(--color-primary);
    border-color: var(--color-primary);
}

.membership-page .form-check-input:checked {
    background-color: var(--color-primary);
    border-color: var(--color-primary);
}

.membership-page .form-check-input:focus {
    border-color: var(--color-primary);
    box-shadow: 0 0 0 0.2rem rgba(var(--color-primary-rgb), 0.25);
}

.membership-page .text-muted {
    color: var(--color-text-muted) !important;
}

.membership-page .alert {
    border-radius: 8px;
    border: none;
}

.membership-page .alert-danger {
    background-color: rgba(var(--color-danger-rgb), 0.1);
    color: var(--color-danger);
}

.membership-page .alert-success {
    background-color: rgba(var(--color-success-rgb), 0.1);
    color: var(--color-success);
}

.membership-page .form-text {
    color: var(--color-text-muted);
    font-size: 0.875rem;
}

.membership-page .invalid-feedback {
    color: var(--color-danger);
}

.membership-page .is-invalid {
    border-color: var(--color-danger);
}

.membership-page .legal-link {
    color: var(--color-primary);
    text-decoration: none;
}

.membership-page .legal-link:hover {
    color: var(--color-primary-hover);
    text-decoration: underline;
}

.membership-page .spinner-border-sm {
    width: 1rem;
    height: 1rem;
}
</style>

<?php
get_footer(); 