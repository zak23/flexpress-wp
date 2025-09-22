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

<div class="site-main">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <h1 class="h3 mb-3"><?php esc_html_e('Create Your Account', 'flexpress'); ?></h1>
                            <p class="text-muted"><?php esc_html_e('Join our community and get access to exclusive content', 'flexpress'); ?></p>
                        </div>

                        <?php
                        // Show any error messages
                        if (isset($_GET['register']) && $_GET['register'] === 'failed') {
                            echo '<div class="alert alert-danger">';
                            echo esc_html__('Registration failed. Please try again.', 'flexpress');
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
                                <input type="password" class="form-control" id="user_pass" name="user_pass" required>
                                <div class="invalid-feedback">
                                    <?php esc_html_e('Please enter a password.', 'flexpress'); ?>
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

                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <?php esc_html_e('Create Account', 'flexpress'); ?>
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
// Form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }

            // Check if passwords match
            var password = document.getElementById('user_pass')
            var confirmPassword = document.getElementById('user_pass_confirm')
            if (password.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Passwords do not match')
            } else {
                confirmPassword.setCustomValidity('')
            }

            form.classList.add('was-validated')
        }, false)
    })
})()
</script>

<?php
get_footer(); 