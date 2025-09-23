<?php
/**
 * Template Name: Reset Password
 */

// Redirect if already logged in
if (is_user_logged_in()) {
    wp_redirect(home_url('/my-account'));
    exit;
}

// Check for required parameters
if (empty($_GET['key']) || empty($_GET['login'])) {
    wp_redirect(home_url('/lost-password?error=invalidkey'));
    exit;
}

$rp_key = $_GET['key'];
$rp_login = $_GET['login'];

// Verify the reset key
$user = check_password_reset_key($rp_key, $rp_login);
if (is_wp_error($user)) {
    if ($user->get_error_code() === 'expired_key') {
        wp_redirect(home_url('/lost-password?error=expiredkey'));
    } else {
        wp_redirect(home_url('/lost-password?error=invalidkey'));
    }
    exit;
}

get_header();
?>

<div class="membership-page">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card bg-dark shadow-sm">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <h1 class="h3 mb-3"><?php esc_html_e('Create New Password', 'flexpress'); ?></h1>
                            <p class="text-muted"><?php esc_html_e('Enter your new password below.', 'flexpress'); ?></p>
                        </div>

                        <?php
                        // Show any error messages
                        if (isset($_GET['error'])) {
                            if ($_GET['error'] === 'password_mismatch') {
                                echo '<div class="alert alert-danger">';
                                echo esc_html__('Passwords do not match. Please try again.', 'flexpress');
                                echo '</div>';
                            } elseif ($_GET['error'] === 'password_empty') {
                                echo '<div class="alert alert-danger">';
                                echo esc_html__('Password cannot be empty. Please enter a password.', 'flexpress');
                                echo '</div>';
                            }
                        }
                        ?>

                        <form id="reset-password-form" method="post" action="<?php echo esc_url(site_url('wp-login.php?action=resetpass')); ?>" class="needs-validation" novalidate>
                            <input type="hidden" name="rp_key" value="<?php echo esc_attr($rp_key); ?>">
                            <input type="hidden" name="rp_login" value="<?php echo esc_attr($rp_login); ?>">
                            
                            <div class="mb-3">
                                <label for="pass1" class="form-label"><?php esc_html_e('New Password', 'flexpress'); ?></label>
                                <input type="password" class="form-control" id="pass1" name="pass1" required>
                                <small id="password-strength" class="form-text"></small>
                                <div class="invalid-feedback">
                                    <?php esc_html_e('Please enter a new password.', 'flexpress'); ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="pass2" class="form-label"><?php esc_html_e('Confirm New Password', 'flexpress'); ?></label>
                                <input type="password" class="form-control" id="pass2" name="pass2" required>
                                <small id="password-match" class="form-text"></small>
                                <div class="invalid-feedback">
                                    <?php esc_html_e('Please confirm your new password.', 'flexpress'); ?>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="password-guidelines">
                                    <p class="text-muted small">
                                        <?php esc_html_e('Your password should:', 'flexpress'); ?>
                                    </p>
                                    <ul class="text-muted small">
                                        <li><?php esc_html_e('Be at least 8 characters long', 'flexpress'); ?></li>
                                        <li><?php esc_html_e('Include at least one uppercase and lowercase letter', 'flexpress'); ?></li>
                                        <li><?php esc_html_e('Include at least one number', 'flexpress'); ?></li>
                                        <li><?php esc_html_e('Include at least one special character (e.g., !@#$%^&*)', 'flexpress'); ?></li>
                                    </ul>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <?php esc_html_e('Reset Password', 'flexpress'); ?>
                            </button>
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
            // Check if passwords match
            var pass1 = document.getElementById('pass1').value;
            var pass2 = document.getElementById('pass2').value;
            
            if (pass1 !== pass2) {
                event.preventDefault();
                event.stopPropagation();
                document.getElementById('password-match').classList.add('text-danger');
                document.getElementById('password-match').classList.remove('text-success');
                document.getElementById('password-match').textContent = '<?php esc_html_e('Passwords do not match', 'flexpress'); ?>';
                return;
            }
            
            // Check if form is valid
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false)
    })
})()
</script>

<?php
get_footer(); 