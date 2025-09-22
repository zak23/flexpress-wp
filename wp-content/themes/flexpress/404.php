<?php
/**
 * The template for displaying 404 pages (not found)
 * 
 * Enhanced 404 page with FlexPress dark theme styling
 * 
 * @package FlexPress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<div class="site-main error-404-page">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6 text-center">
                <!-- Main Error Section -->
                <div class="error-404-main mb-5">
                    <div class="error-number">
                        <span class="error-404-text">404</span>
                    </div>
                    <h1 class="error-title mb-4">Sorry!</h1>
                    <h2 class="error-subtitle mb-4">Page not found</h2>
                </div>

                <!-- Action Buttons -->
                <!-- If user is not logged in show login and join options -->
                <!-- If user is logged in show dashboard and browse episodes options -->
                <!-- if user is logged in and does not have an active or canelled membership show memberhip link -->
                <div class="error-actions">
                    <?php if (!is_user_logged_in()): ?>
                        <!-- Not logged in - show join/login options -->
                        <a href="<?php echo esc_url(home_url('/join')); ?>" class="btn btn-primary btn-lg me-3 mb-3">
                            <?php esc_html_e('Join Now', 'flexpress'); ?>
                        </a>
                        <a href="<?php echo esc_url(home_url('/login')); ?>" class="btn btn-outline-primary btn-lg mb-3">
                            <?php esc_html_e('Login', 'flexpress'); ?>
                        </a>
                    <?php else: ?>
                        <?php 
                        // Check membership status for logged-in users
                        $current_user_id = get_current_user_id();
                        $membership_status = flexpress_get_membership_status($current_user_id);
                        $has_active_membership = in_array($membership_status, ['active', 'cancelled']);
                        ?>
                        
                        <?php if (!$has_active_membership): ?>
                            <!-- Logged in but no active membership - show membership options -->
                            <a href="<?php echo esc_url(home_url('/membership')); ?>" class="btn btn-primary btn-lg me-3 mb-3">
                                <i class="bi bi-star me-2"></i>
                                <?php esc_html_e('Get Membership', 'flexpress'); ?>
                            </a>
                            <a href="<?php echo esc_url(home_url('/episodes')); ?>" class="btn btn-outline-primary btn-lg mb-3">
                                <i class="bi bi-collection-play me-2"></i>
                                <?php esc_html_e('Browse Episodes', 'flexpress'); ?>
                            </a>
                        <?php else: ?>
                            <!-- Logged in with active membership - show member options -->
                            <a href="<?php echo esc_url(home_url('/dashboard')); ?>" class="btn btn-primary btn-lg me-3 mb-3">
                                <i class="bi bi-speedometer2 me-2"></i>
                                <?php esc_html_e('Dashboard', 'flexpress'); ?>
                            </a>
                            <a href="<?php echo esc_url(home_url('/episodes')); ?>" class="btn btn-outline-primary btn-lg mb-3">
                                <i class="bi bi-collection-play me-2"></i>
                                <?php esc_html_e('Browse Episodes', 'flexpress'); ?>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <div class="mt-4">
                        <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-house me-2"></i>
                            <?php esc_html_e('Go Home', 'flexpress'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
get_footer(); 