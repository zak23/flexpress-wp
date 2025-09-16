<?php
/**
 * Template Name: Lost Password
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
                            <h1 class="h3 mb-3"><?php esc_html_e('Reset Password', 'flexpress'); ?></h1>
                            <p class="text-muted"><?php esc_html_e('Enter your email address and we\'ll send you instructions to reset your password.', 'flexpress'); ?></p>
                        </div>

                        <?php
                        // Show any error messages
                        if (isset($_GET['reset']) && $_GET['reset'] === 'failed') {
                            echo '<div class="alert alert-danger">';
                            echo esc_html__('Password reset failed. Please try again.', 'flexpress');
                            echo '</div>';
                        }

                        // Show success message
                        if (isset($_GET['reset']) && $_GET['reset'] === 'sent') {
                            echo '<div class="alert alert-success">';
                            echo esc_html__('Password reset instructions have been sent to your email address.', 'flexpress');
                            echo '</div>';
                        }
                        ?>

                        <form method="post" action="<?php echo esc_url(wp_lostpassword_url()); ?>" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="user_login" class="form-label"><?php esc_html_e('Email Address', 'flexpress'); ?></label>
                                <input type="email" class="form-control" id="user_login" name="user_login" required>
                                <div class="invalid-feedback">
                                    <?php esc_html_e('Please enter your email address.', 'flexpress'); ?>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <?php esc_html_e('Send Reset Instructions', 'flexpress'); ?>
                            </button>

                            <div class="text-center">
                                <a href="<?php echo esc_url(home_url('/login')); ?>" class="text-decoration-none">
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
            form.classList.add('was-validated')
        }, false)
    })
})()
</script>

<?php
get_footer(); 