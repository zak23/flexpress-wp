<?php
/**
 * Template Name: Banned User Page
 * 
 * Displays a message for banned users
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if user is logged in
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login'));
    exit;
}

$user_id = get_current_user_id();
$membership_status = get_user_meta($user_id, 'membership_status', true);

// Get ban details
$ban_reason = get_user_meta($user_id, 'ban_reason', true);
$ban_date = get_user_meta($user_id, 'ban_date', true);
$user = get_userdata($user_id);

get_header();
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white text-center">
                    <h1 class="card-title mb-0">
                        <i class="fas fa-ban me-2"></i>
                        <?php esc_html_e('Account Suspended', 'flexpress'); ?>
                    </h1>
                </div>
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-exclamation-triangle text-danger" style="font-size: 4rem;"></i>
                    </div>
                    
                    <h2 class="text-danger mb-4">
                        <?php esc_html_e('Your account has been suspended', 'flexpress'); ?>
                    </h2>
                    
                    <p class="lead mb-4">
                        <?php esc_html_e('We\'re sorry, but your account has been suspended and you no longer have access to our content.', 'flexpress'); ?>
                    </p>
                    
                    <?php if ($ban_reason): ?>
                    <div class="alert alert-warning mb-4">
                        <h5 class="alert-heading">
                            <i class="fas fa-info-circle me-2"></i>
                            <?php esc_html_e('Reason for Suspension', 'flexpress'); ?>
                        </h5>
                        <p class="mb-0"><?php echo esc_html($ban_reason); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($ban_date): ?>
                    <div class="alert alert-info mb-4">
                        <h5 class="alert-heading">
                            <i class="fas fa-calendar me-2"></i>
                            <?php esc_html_e('Suspension Date', 'flexpress'); ?>
                        </h5>
                        <p class="mb-0">
                            <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($ban_date))); ?>
                        </p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="alert alert-light border mb-4">
                        <h5 class="alert-heading">
                            <i class="fas fa-question-circle me-2"></i>
                            <?php esc_html_e('Need Help?', 'flexpress'); ?>
                        </h5>
                        <p class="mb-0">
                            <?php esc_html_e('If you believe this suspension is in error, please contact our support team for assistance.', 'flexpress'); ?>
                        </p>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="<?php echo esc_url(home_url('/support')); ?>" class="btn btn-primary btn-lg">
                            <i class="fas fa-envelope me-2"></i>
                            <?php esc_html_e('Contact Support', 'flexpress'); ?>
                        </a>
                        <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-sign-out-alt me-2"></i>
                            <?php esc_html_e('Logout', 'flexpress'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.banned-page {
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.card {
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    border-radius: 15px;
}

.card-header {
    border-radius: 15px 15px 0 0 !important;
}

.alert {
    border-radius: 10px;
}

.btn-lg {
    padding: 12px 30px;
    font-size: 1.1rem;
    border-radius: 8px;
}

.text-danger {
    color: #dc3545 !important;
}

.bg-danger {
    background-color: #dc3545 !important;
}

.border-danger {
    border-color: #dc3545 !important;
}
</style>

<?php get_footer(); ?>
