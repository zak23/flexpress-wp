<?php
/**
 * Template Name: Affiliate Signup
 * Description: Registration page for new affiliates
 */

get_header();

// Check if already an affiliate
$current_user_id = get_current_user_id();
$existing_affiliate = null;
if ($current_user_id) {
    global $wpdb;
    $existing_affiliate = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}flexpress_affiliates WHERE user_id = %d OR email = %s",
        $current_user_id,
        wp_get_current_user()->user_email
    ));
}

// Handle form submission
$registration_message = '';
$registration_success = false;

if (isset($_POST['submit_affiliate_application']) && wp_verify_nonce($_POST['affiliate_nonce'], 'affiliate_signup')) {
    if (empty($_POST['agree_terms'])) {
        $registration_message = __('You must agree to the affiliate terms and conditions.', 'flexpress');
    } else {
        $affiliate_data = array(
            'user_id' => $current_user_id,
            'display_name' => sanitize_text_field($_POST['display_name'] ?? ''),
            'email' => sanitize_email($_POST['email'] ?? ''),
            'affiliate_code' => sanitize_text_field($_POST['affiliate_code'] ?? ''),
            'referral_url' => esc_url_raw($_POST['referral_url'] ?? ''),
            'notes' => sanitize_textarea_field($_POST['notes'] ?? '')
        );

        $result = flexpress_register_affiliate($affiliate_data);

        if (is_wp_error($result)) {
            $registration_message = $result->get_error_message();
        } else {
            $registration_success = true;
            $registration_message = ($result['status'] === 'active')
                ? __('Your affiliate application has been approved! You can start promoting immediately.', 'flexpress')
                : __('Your affiliate application has been submitted and is pending review. You will receive an email once approved.', 'flexpress');
        }
    }
}

// Get affiliate settings
$affiliate_settings = get_option('flexpress_affiliate_settings', array());
$default_signup_commission = floatval($affiliate_settings['commission_rate'] ?? 25);
$default_rebill_commission = floatval($affiliate_settings['rebill_commission_rate'] ?? 10);
$auto_approve = !empty($affiliate_settings['auto_approve_affiliates']);
$minimum_payout = floatval($affiliate_settings['minimum_payout'] ?? 50);

$dashboard_url = esc_url(home_url('/affiliate-dashboard/'));
$terms_url = esc_url(home_url('/affiliate-terms/'));
$contact_url = esc_url(home_url('/contact/'));
?>

<div class="affiliate-signup-page">
    <div class="container py-5">
        <div class="row justify-content-center mb-5">
            <div class="col-md-10 text-center">
                <h1 class="display-4 mb-4">
                    <i class="fas fa-handshake me-2" aria-hidden="true"></i>
                    <?php esc_html_e('Join Our Affiliate Program', 'flexpress'); ?>
                </h1>
                <p class="lead mb-0">
                    <?php esc_html_e('Earn commissions by promoting our content and grow with our community.', 'flexpress'); ?>
                </p>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-6">
                <div class="card affiliate-signup-form bg-dark">
                    <div class="card-body p-4 p-md-5">

                        <?php if ($existing_affiliate) : ?>
                            <div class="alert alert-info" role="alert">
                                <h2 class="h4 mb-3">
                                    <i class="fas fa-info-circle me-2" aria-hidden="true"></i>
                                    <?php esc_html_e("You're Already an Affiliate!", 'flexpress'); ?>
                                </h2>
                                <p class="mb-2">
                                    <strong><?php esc_html_e('Affiliate Code:', 'flexpress'); ?></strong>
                                    <code class="affiliate-code-display"><?php echo esc_html($existing_affiliate->affiliate_code); ?></code>
                                </p>
                                <p class="mb-3">
                                    <strong><?php esc_html_e('Status:', 'flexpress'); ?></strong>
                                    <span class="badge <?php echo $existing_affiliate->status === 'active' ? 'bg-success' : 'bg-warning'; ?>">
                                        <?php echo esc_html(ucfirst($existing_affiliate->status)); ?>
                                    </span>
                                </p>
                                <a href="<?php echo $dashboard_url; ?>" class="btn btn-primary">
                                    <i class="fas fa-chart-line me-2" aria-hidden="true"></i>
                                    <?php esc_html_e('View Dashboard', 'flexpress'); ?>
                                </a>
                            </div>

                        <?php elseif ($registration_success) : ?>
                            <div class="alert alert-success" role="alert">
                                <h2 class="h4 mb-3">
                                    <i class="fas fa-check-circle me-2" aria-hidden="true"></i>
                                    <?php esc_html_e('Application Submitted!', 'flexpress'); ?>
                                </h2>
                                <p class="mb-3"><?php echo esc_html($registration_message); ?></p>
                                <?php if ($auto_approve) : ?>
                                    <a href="<?php echo $dashboard_url; ?>" class="btn btn-primary">
                                        <i class="fas fa-chart-line me-2" aria-hidden="true"></i>
                                        <?php esc_html_e('Go to Your Dashboard', 'flexpress'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>

                        <?php else : ?>
                            <?php if ($registration_message) : ?>
                                <div class="alert alert-danger" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2" aria-hidden="true"></i>
                                    <?php echo esc_html($registration_message); ?>
                                </div>
                            <?php endif; ?>

                            <div class="commission-rates mb-4">
                                <h2 class="h5 mb-3">
                                    <i class="fas fa-gift me-2" aria-hidden="true"></i>
                                    <?php esc_html_e('Affiliate Program Benefits', 'flexpress'); ?>
                                </h2>
                                <ul class="commission-benefits mb-0">
                                    <li class="commission-rate-item">
                                        <strong><?php echo esc_html($default_signup_commission); ?>%</strong>
                                        <?php esc_html_e('commission on signup sales', 'flexpress'); ?>
                                    </li>
                                    <li class="commission-rate-item">
                                        <strong><?php echo esc_html($default_rebill_commission); ?>%</strong>
                                        <?php esc_html_e('commission on recurring payments', 'flexpress'); ?>
                                    </li>
                                    <li><?php esc_html_e('Real-time tracking and reporting', 'flexpress'); ?></li>
                                    <li>
                                        <?php
                                        printf(
                                            /* translators: %s: minimum payout amount */
                                            esc_html__('Monthly payouts (minimum $%s)', 'flexpress'),
                                            esc_html(number_format($minimum_payout, 0))
                                        );
                                        ?>
                                    </li>
                                    <li><?php esc_html_e('Custom promotional materials', 'flexpress'); ?></li>
                                    <li><?php esc_html_e('Dedicated affiliate support', 'flexpress'); ?></li>
                                </ul>
                            </div>

                            <form method="post" id="affiliate-signup-form" class="needs-validation" novalidate>
                                <?php wp_nonce_field('affiliate_signup', 'affiliate_nonce'); ?>

                                <div class="mb-3">
                                    <label for="display_name" class="form-label">
                                        <i class="fas fa-user me-2" aria-hidden="true"></i>
                                        <?php esc_html_e('Display Name', 'flexpress'); ?> *
                                    </label>
                                    <input type="text"
                                           class="form-control"
                                           id="display_name"
                                           name="display_name"
                                           value="<?php echo esc_attr($_POST['display_name'] ?? (is_user_logged_in() ? wp_get_current_user()->display_name : '')); ?>"
                                           required>
                                    <div class="form-text"><?php esc_html_e('This will be shown in your affiliate profile', 'flexpress'); ?></div>
                                    <div class="invalid-feedback"><?php esc_html_e('Please enter your display name.', 'flexpress'); ?></div>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope me-2" aria-hidden="true"></i>
                                        <?php esc_html_e('Email Address', 'flexpress'); ?> *
                                    </label>
                                    <input type="email"
                                           class="form-control"
                                           id="email"
                                           name="email"
                                           value="<?php echo esc_attr($_POST['email'] ?? (is_user_logged_in() ? wp_get_current_user()->user_email : '')); ?>"
                                           required>
                                    <div class="form-text"><?php esc_html_e("We'll use this for important affiliate communications", 'flexpress'); ?></div>
                                    <div class="invalid-feedback"><?php esc_html_e('Please enter a valid email address.', 'flexpress'); ?></div>
                                </div>

                                <div class="mb-3">
                                    <label for="affiliate_code" class="form-label">
                                        <i class="fas fa-tag me-2" aria-hidden="true"></i>
                                        <?php esc_html_e('Preferred Affiliate Code', 'flexpress'); ?> *
                                    </label>
                                    <div class="affiliate-code-generator">
                                        <input type="text"
                                               class="form-control"
                                               id="affiliate_code"
                                               name="affiliate_code"
                                               value="<?php echo esc_attr($_POST['affiliate_code'] ?? ''); ?>"
                                               pattern="[a-zA-Z0-9-]{3,20}"
                                               required>
                                        <button type="button" class="btn btn-outline-primary" id="generate-code-btn">
                                            <i class="fas fa-magic me-1" aria-hidden="true"></i>
                                            <?php esc_html_e('Suggest', 'flexpress'); ?>
                                        </button>
                                    </div>
                                    <div class="form-text"><?php esc_html_e('3-20 characters, letters, numbers, and hyphens only', 'flexpress'); ?></div>
                                    <div class="invalid-feedback"><?php esc_html_e('Code must be 3-20 characters with only letters, numbers, and hyphens.', 'flexpress'); ?></div>
                                </div>

                                <div class="mb-3">
                                    <label for="referral_url" class="form-label">
                                        <i class="fas fa-link me-2" aria-hidden="true"></i>
                                        <?php esc_html_e('Website/Social Media URL', 'flexpress'); ?>
                                    </label>
                                    <input type="url"
                                           class="form-control"
                                           id="referral_url"
                                           name="referral_url"
                                           value="<?php echo esc_attr($_POST['referral_url'] ?? ''); ?>"
                                           placeholder="https://your-website.com">
                                    <div class="form-text"><?php esc_html_e('Where will you be promoting our content?', 'flexpress'); ?></div>
                                </div>

                                <div class="mb-4">
                                    <label for="notes" class="form-label">
                                        <i class="fas fa-comment me-2" aria-hidden="true"></i>
                                        <?php esc_html_e('Additional Information', 'flexpress'); ?>
                                    </label>
                                    <textarea class="form-control"
                                              id="notes"
                                              name="notes"
                                              rows="3"
                                              placeholder="<?php esc_attr_e('Tell us about your promotional strategy, audience, or any questions...', 'flexpress'); ?>"><?php echo esc_textarea($_POST['notes'] ?? ''); ?></textarea>
                                    <div class="form-text"><?php esc_html_e('Optional: Help us understand how you plan to promote', 'flexpress'); ?></div>
                                </div>

                                <div class="mb-4">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="agree_terms" name="agree_terms" required>
                                        <label class="form-check-label" for="agree_terms">
                                            <?php esc_html_e('I agree to the', 'flexpress'); ?>
                                            <a href="<?php echo $terms_url; ?>" target="_blank" rel="noopener noreferrer">
                                                <?php esc_html_e('Affiliate Terms & Conditions', 'flexpress'); ?>
                                            </a>
                                            <?php esc_html_e('and understand the commission structure', 'flexpress'); ?>
                                        </label>
                                        <div class="invalid-feedback"><?php esc_html_e('You must agree to the terms and conditions.', 'flexpress'); ?></div>
                                    </div>
                                </div>

                                <div class="d-grid">
                                    <button type="submit"
                                            name="submit_affiliate_application"
                                            class="btn btn-primary btn-lg">
                                        <i class="fas fa-paper-plane me-2" aria-hidden="true"></i>
                                        <?php esc_html_e('Submit Application', 'flexpress'); ?>
                                    </button>
                                </div>
                            </form>

                        <?php endif; ?>

                    </div>

                    <?php if (!$existing_affiliate && !$registration_success) : ?>
                    <div class="card-footer text-center">
                        <small class="text-muted">
                            <i class="fas fa-question-circle me-1" aria-hidden="true"></i>
                            <?php esc_html_e('Have questions?', 'flexpress'); ?>
                            <a href="<?php echo $contact_url; ?>"><?php esc_html_e('Contact our affiliate team', 'flexpress'); ?></a>
                        </small>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>
