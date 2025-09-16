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
            ? 'Your affiliate application has been approved! You can start promoting immediately.'
            : 'Your affiliate application has been submitted and is pending review. You will receive an email once approved.';
    }
}

// Get affiliate settings
$affiliate_settings = get_option('flexpress_affiliate_settings', array());
$default_signup_commission = floatval($affiliate_settings['commission_rate'] ?? 25);
$default_rebill_commission = floatval($affiliate_settings['rebill_commission_rate'] ?? 10);
$auto_approve = !empty($affiliate_settings['auto_approve_affiliates']);

?>

<div class="affiliate-signup-page">
    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h1 class="h3 mb-0"><i class="fas fa-handshake me-2"></i>Join Our Affiliate Program</h1>
                    </div>
                    <div class="card-body">
                        
                        <?php if ($existing_affiliate): ?>
                            <!-- Already an affiliate -->
                            <div class="alert alert-info">
                                <h4><i class="fas fa-info-circle me-2"></i>You're Already an Affiliate!</h4>
                                <p><strong>Affiliate Code:</strong> <code><?php echo esc_html($existing_affiliate->affiliate_code); ?></code></p>
                                <p><strong>Status:</strong> 
                                    <span class="badge bg-<?php echo $existing_affiliate->status === 'active' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($existing_affiliate->status); ?>
                                    </span>
                                </p>
                                <p class="mb-0">
                                    <a href="/affiliate-dashboard" class="btn btn-primary">
                                        <i class="fas fa-chart-line me-2"></i>View Dashboard
                                    </a>
                                </p>
                            </div>
                            
                        <?php elseif ($registration_success): ?>
                            <!-- Registration successful -->
                            <div class="alert alert-success">
                                <h4><i class="fas fa-check-circle me-2"></i>Application Submitted!</h4>
                                <p><?php echo esc_html($registration_message); ?></p>
                                <?php if ($auto_approve): ?>
                                    <p class="mb-0">
                                        <a href="/affiliate-dashboard" class="btn btn-success">
                                            <i class="fas fa-chart-line me-2"></i>Go to Your Dashboard
                                        </a>
                                    </p>
                                <?php endif; ?>
                            </div>
                            
                        <?php else: ?>
                            <!-- Registration form -->
                            
                            <?php if ($registration_message): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo esc_html($registration_message); ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Program Benefits -->
                            <div class="alert alert-info mb-4">
                                <h5><i class="fas fa-gift me-2"></i>Affiliate Program Benefits</h5>
                                <ul class="mb-0">
                                    <li><strong><?php echo $default_signup_commission; ?>%</strong> commission on signup sales</li>
                                    <li><strong><?php echo $default_rebill_commission; ?>%</strong> commission on recurring payments</li>
                                    <li>Real-time tracking and reporting</li>
                                    <li>Monthly payouts (minimum $<?php echo floatval($affiliate_settings['minimum_payout'] ?? 50); ?>)</li>
                                    <li>Custom promotional materials</li>
                                    <li>Dedicated affiliate support</li>
                                </ul>
                            </div>
                            
                            <form method="post" class="needs-validation" novalidate>
                                <?php wp_nonce_field('affiliate_signup', 'affiliate_nonce'); ?>
                                
                                <div class="mb-3">
                                    <label for="display_name" class="form-label">
                                        <i class="fas fa-user me-2"></i>Display Name *
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="display_name" 
                                           name="display_name" 
                                           value="<?php echo esc_attr($_POST['display_name'] ?? (is_user_logged_in() ? wp_get_current_user()->display_name : '')); ?>"
                                           required>
                                    <div class="form-text">This will be shown in your affiliate profile</div>
                                    <div class="invalid-feedback">Please enter your display name.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope me-2"></i>Email Address *
                                    </label>
                                    <input type="email" 
                                           class="form-control" 
                                           id="email" 
                                           name="email" 
                                           value="<?php echo esc_attr($_POST['email'] ?? (is_user_logged_in() ? wp_get_current_user()->user_email : '')); ?>"
                                           required>
                                    <div class="form-text">We'll use this for important affiliate communications</div>
                                    <div class="invalid-feedback">Please enter a valid email address.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="affiliate_code" class="form-label">
                                        <i class="fas fa-tag me-2"></i>Preferred Affiliate Code *
                                    </label>
                                    <div class="input-group">
                                        <input type="text" 
                                               class="form-control" 
                                               id="affiliate_code" 
                                               name="affiliate_code" 
                                               value="<?php echo esc_attr($_POST['affiliate_code'] ?? ''); ?>"
                                               pattern="[a-zA-Z0-9-]{3,20}"
                                               required>
                                        <button type="button" class="btn btn-outline-secondary" id="suggest-code">
                                            <i class="fas fa-magic"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">3-20 characters, letters, numbers, and hyphens only</div>
                                    <div class="invalid-feedback">Code must be 3-20 characters with only letters, numbers, and hyphens.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="referral_url" class="form-label">
                                        <i class="fas fa-link me-2"></i>Website/Social Media URL
                                    </label>
                                    <input type="url" 
                                           class="form-control" 
                                           id="referral_url" 
                                           name="referral_url" 
                                           value="<?php echo esc_attr($_POST['referral_url'] ?? ''); ?>"
                                           placeholder="https://your-website.com">
                                    <div class="form-text">Where will you be promoting our content?</div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="notes" class="form-label">
                                        <i class="fas fa-comment me-2"></i>Additional Information
                                    </label>
                                    <textarea class="form-control" 
                                              id="notes" 
                                              name="notes" 
                                              rows="3" 
                                              placeholder="Tell us about your promotional strategy, audience, or any questions..."><?php echo esc_textarea($_POST['notes'] ?? ''); ?></textarea>
                                    <div class="form-text">Optional: Help us understand how you plan to promote</div>
                                </div>
                                
                                <!-- Terms Agreement -->
                                <div class="mb-4">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="agree_terms" name="agree_terms" required>
                                        <label class="form-check-label" for="agree_terms">
                                            I agree to the 
                                            <a href="/affiliate-terms" target="_blank">Affiliate Terms & Conditions</a> 
                                            and understand the commission structure
                                        </label>
                                        <div class="invalid-feedback">You must agree to the terms and conditions.</div>
                                    </div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" 
                                            name="submit_affiliate_application" 
                                            class="btn btn-primary btn-lg">
                                        <i class="fas fa-paper-plane me-2"></i>Submit Application
                                    </button>
                                </div>
                                
                            </form>
                            
                        <?php endif; ?>
                        
                    </div>
                    
                    <?php if (!$existing_affiliate && !$registration_success): ?>
                    <div class="card-footer text-center text-muted">
                        <small>
                            <i class="fas fa-question-circle me-1"></i>
                            Have questions? <a href="/contact">Contact our affiliate team</a>
                        </small>
                    </div>
                    <?php endif; ?>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Auto-suggest affiliate code based on display name
    $('#suggest-code').click(function() {
        const displayName = $('#display_name').val().trim();
        if (displayName) {
            const suggestedCode = displayName.toLowerCase()
                .replace(/[^a-z0-9]/g, '')
                .substring(0, 15);
            $('#affiliate_code').val(suggestedCode);
        }
    });
    
    // Auto-suggest when display name changes
    $('#display_name').on('blur', function() {
        const codeField = $('#affiliate_code');
        if (!codeField.val().trim()) {
            $('#suggest-code').click();
        }
    });
    
    // Form validation
    (function() {
        'use strict';
        window.addEventListener('load', function() {
            var forms = document.getElementsByClassName('needs-validation');
            var validation = Array.prototype.filter.call(forms, function(form) {
                form.addEventListener('submit', function(event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }, false);
    })();
});
</script>

<style>
.affiliate-signup-page {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 2rem 0;
}

.card {
    border: none;
    border-radius: 15px;
}

.card-header {
    border-radius: 15px 15px 0 0 !important;
}

.form-control {
    border-radius: 8px;
}

.btn {
    border-radius: 8px;
}

.alert {
    border-radius: 8px;
}

code {
    font-size: 1.1em;
    font-weight: bold;
}
</style>

<?php get_footer(); ?> 