<?php
/**
 * Template Name: Cancel Membership
 *
 * @package FlexPress
 */

get_header();

// Check if user is logged in
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login?redirect_to=' . urlencode(home_url('/cancel-membership'))));
    exit;
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get user's membership status
$membership_status = get_user_meta($user_id, 'membership_status', true);
$subscription_type = get_user_meta($user_id, 'subscription_type', true);
$next_rebill_date = get_user_meta($user_id, 'next_rebill_date', true);
$membership_expires = get_user_meta($user_id, 'membership_expires', true);
$subscription_amount = get_user_meta($user_id, 'subscription_amount', true);
$subscription_currency = get_user_meta($user_id, 'subscription_currency', true);

// Check if user has an active subscription to cancel
$can_cancel = ($membership_status === 'active' || $membership_status === 'cancelled');

// Handle cancellation request
$cancellation_success = false;
$cancellation_error = '';

if (isset($_POST['cancel_membership']) && wp_verify_nonce($_POST['cancel_nonce'], 'cancel_membership_nonce')) {
    // Process cancellation
    $result = flexpress_cancel_user_membership($user_id);
    
    if ($result['success']) {
        $cancellation_success = true;
        // Update local variables
        $membership_status = 'cancelled';
        $can_cancel = false;
    } else {
        $cancellation_error = $result['message'];
    }
}
?>

<div class="cancel-membership-page">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="cancel-container">
                    <!-- Page Header -->
                    <div class="cancel-header text-center mb-5">
                        <div class="cancel-icon">
                            <i class="fas fa-user-times"></i>
                        </div>
                        <h1 class="cancel-title">Cancel Membership</h1>
                        <p class="cancel-subtitle">We're sorry to see you go. Here's how to cancel your membership.</p>
                    </div>

                    <?php if ($cancellation_success): ?>
                        <!-- Success Message -->
                        <div class="alert alert-success text-center mb-4">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Membership Cancelled Successfully!</strong><br>
                            Your membership has been cancelled. You will retain access until <?php echo esc_html(date('F j, Y', strtotime($membership_expires))); ?>.
                        </div>
                    <?php elseif ($cancellation_error): ?>
                        <!-- Error Message -->
                        <div class="alert alert-danger text-center mb-4">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <strong>Error:</strong> <?php echo esc_html($cancellation_error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($can_cancel): ?>
                        <!-- Current Membership Status -->
                        <div class="membership-status-card mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-crown me-2"></i>
                                        Current Membership Status
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="status-item">
                                                <label>Status:</label>
                                                <span class="status-active">
                                                    <i class="fas fa-check-circle me-1"></i>
                                                    <?php echo ucfirst($membership_status); ?>
                                                </span>
                                            </div>
                                            <?php if ($subscription_type): ?>
                                            <div class="status-item">
                                                <label>Plan:</label>
                                                <span><?php echo esc_html($subscription_type); ?></span>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-6">
                                            <?php if ($subscription_amount): ?>
                                            <div class="status-item">
                                                <label>Amount:</label>
                                                <span><?php echo esc_html($subscription_currency . ' ' . $subscription_amount); ?></span>
                                            </div>
                                            <?php endif; ?>
                                            <?php if ($next_rebill_date): ?>
                                            <div class="status-item">
                                                <label>Next Billing:</label>
                                                <span><?php echo esc_html(date('F j, Y', strtotime($next_rebill_date))); ?></span>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Cancellation Form -->
                        <div class="cancellation-form mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-times-circle me-2"></i>
                                        Cancel Your Membership
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="cancellation-warning mb-4">
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            <strong>Important:</strong> Cancelling your membership will:
                                            <ul class="mb-0 mt-2">
                                                <li>Stop all future billing</li>
                                                <li>Allow you to keep access until <?php echo esc_html(date('F j, Y', strtotime($membership_expires))); ?></li>
                                                <li>Remove access to premium content after the expiration date</li>
                                            </ul>
                                        </div>
                                    </div>

                                    <form method="post" id="cancel-membership-form">
                                        <?php wp_nonce_field('cancel_membership_nonce', 'cancel_nonce'); ?>
                                        
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="confirm_cancellation" name="confirm_cancellation" required>
                                            <label class="form-check-label" for="confirm_cancellation">
                                                I understand that cancelling my membership will stop future billing and remove access to premium content after <?php echo esc_html(date('F j, Y', strtotime($membership_expires))); ?>.
                                            </label>
                                        </div>

                                        <div class="form-check mb-4">
                                            <input class="form-check-input" type="checkbox" id="confirm_no_refund" name="confirm_no_refund" required>
                                            <label class="form-check-label" for="confirm_no_refund">
                                                I understand that I will not receive a refund for the current billing period and will retain access until the expiration date.
                                            </label>
                                        </div>

                                        <div class="text-center">
                                            <button type="submit" name="cancel_membership" class="btn btn-danger btn-lg" id="cancel-btn">
                                                <i class="fas fa-times me-2"></i>
                                                Cancel My Membership
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- No Active Membership -->
                        <div class="no-membership-card mb-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="no-membership-icon mb-3">
                                        <i class="fas fa-info-circle"></i>
                                    </div>
                                    <h5>No Active Membership</h5>
                                    <p class="text-muted mb-4">
                                        <?php if ($membership_status === 'cancelled'): ?>
                                            Your membership has already been cancelled. You retain access until <?php echo esc_html(date('F j, Y', strtotime($membership_expires))); ?>.
                                        <?php elseif ($membership_status === 'expired'): ?>
                                            Your membership has expired. You can renew your membership to regain access to premium content.
                                        <?php else: ?>
                                            You don't have an active membership to cancel.
                                        <?php endif; ?>
                                    </p>
                                    
                                    <?php if ($membership_status === 'expired' || $membership_status === 'none'): ?>
                                        <a href="<?php echo esc_url(home_url('/membership')); ?>" class="btn btn-primary">
                                            <i class="fas fa-crown me-2"></i>
                                            Renew Membership
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Cancellation Information -->
                    <div class="cancellation-info mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-info-circle me-2"></i>
                                    How Cancellation Works
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-item">
                                            <div class="info-icon">
                                                <i class="fas fa-calendar-check"></i>
                                            </div>
                                            <div class="info-content">
                                                <h6>Immediate Effect</h6>
                                                <p>Your membership is cancelled immediately and no future charges will be made.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-item">
                                            <div class="info-icon">
                                                <i class="fas fa-clock"></i>
                                            </div>
                                            <div class="info-content">
                                                <h6>Access Until Expiration</h6>
                                                <p>You keep full access to premium content until your current billing period ends.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-item">
                                            <div class="info-icon">
                                                <i class="fas fa-redo"></i>
                                            </div>
                                            <div class="info-content">
                                                <h6>Easy Reactivation</h6>
                                                <p>You can reactivate your membership anytime by visiting our membership page.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-item">
                                            <div class="info-icon">
                                                <i class="fas fa-shield-alt"></i>
                                            </div>
                                            <div class="info-content">
                                                <h6>Secure Process</h6>
                                                <p>All cancellations are processed securely and you'll receive email confirmation.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Alternative Options -->
                    <div class="alternative-options mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-lightbulb me-2"></i>
                                    Before You Go - Consider These Options
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="option-item text-center">
                                            <div class="option-icon">
                                                <i class="fas fa-pause"></i>
                                            </div>
                                            <h6>Pause Membership</h6>
                                            <p>Contact support to temporarily pause your membership instead of cancelling.</p>
                                            <a href="<?php echo esc_url(home_url('/contact')); ?>" class="btn btn-outline-primary btn-sm">
                                                Contact Support
                                            </a>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="option-item text-center">
                                            <div class="option-icon">
                                                <i class="fas fa-exchange-alt"></i>
                                            </div>
                                            <h6>Change Plan</h6>
                                            <p>Switch to a different membership plan that better fits your needs.</p>
                                            <a href="<?php echo esc_url(home_url('/membership')); ?>" class="btn btn-outline-primary btn-sm">
                                                View Plans
                                            </a>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="option-item text-center">
                                            <div class="option-icon">
                                                <i class="fas fa-gift"></i>
                                            </div>
                                            <h6>Special Offers</h6>
                                            <p>Check out our current promotions and special offers before cancelling.</p>
                                            <a href="<?php echo esc_url(home_url('/membership')); ?>" class="btn btn-outline-primary btn-sm">
                                                View Offers
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- FAQ Section -->
                    <div class="cancellation-faq mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-question-circle me-2"></i>
                                    Frequently Asked Questions
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="accordion" id="cancellationFAQ">
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="faq1">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1" aria-expanded="false" aria-controls="collapse1">
                                                <i class="fas fa-question-circle me-3"></i>
                                                Will I get a refund if I cancel?
                                            </button>
                                        </h2>
                                        <div id="collapse1" class="accordion-collapse collapse" aria-labelledby="faq1" data-bs-parent="#cancellationFAQ">
                                            <div class="accordion-body">
                                                No refunds are provided for cancellations. However, you will retain access to all premium content until your current billing period expires.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="faq2">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2" aria-expanded="false" aria-controls="collapse2">
                                                <i class="fas fa-redo me-3"></i>
                                                Can I reactivate my membership later?
                                            </button>
                                        </h2>
                                        <div id="collapse2" class="accordion-collapse collapse" aria-labelledby="faq2" data-bs-parent="#cancellationFAQ">
                                            <div class="accordion-body">
                                                Yes! You can reactivate your membership at any time by visiting our membership page and selecting a plan. Your account and preferences will be preserved.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="faq3">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3" aria-expanded="false" aria-controls="collapse3">
                                                <i class="fas fa-calendar-alt me-3"></i>
                                                When will my access end?
                                            </button>
                                        </h2>
                                        <div id="collapse3" class="accordion-collapse collapse" aria-labelledby="faq3" data-bs-parent="#cancellationFAQ">
                                            <div class="accordion-body">
                                                Your access will continue until <?php echo esc_html(date('F j, Y', strtotime($membership_expires))); ?>, which is the end of your current billing period. After this date, you'll lose access to premium content.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="faq4">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse4" aria-expanded="false" aria-controls="collapse4">
                                                <i class="fas fa-envelope me-3"></i>
                                                Will I receive confirmation of my cancellation?
                                            </button>
                                        </h2>
                                        <div id="collapse4" class="accordion-collapse collapse" aria-labelledby="faq4" data-bs-parent="#cancellationFAQ">
                                            <div class="accordion-body">
                                                Yes, you will receive an email confirmation at <?php echo esc_html($current_user->user_email); ?> confirming your cancellation and the date your access will end.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Support Contact -->
                    <div class="support-contact text-center">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="mb-3">Need Help?</h5>
                                <p class="text-muted mb-4">
                                    If you have any questions about cancelling your membership or need assistance, our support team is here to help.
                                </p>
                                <a href="<?php echo esc_url(home_url('/contact')); ?>" class="btn btn-outline-primary me-3">
                                    <i class="fas fa-envelope me-2"></i>
                                    Contact Support
                                </a>
                                <a href="<?php echo esc_url(home_url('/dashboard')); ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-tachometer-alt me-2"></i>
                                    Back to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.cancel-membership-page {
    background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
    min-height: 100vh;
    padding: 2rem 0;
}

.cancel-container {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 16px;
    padding: 2rem;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

.cancel-icon {
    font-size: 4rem;
    color: #dc3545;
    margin-bottom: 1rem;
}

.cancel-title {
    color: #ffffff;
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.cancel-subtitle {
    color: #b0b0b0;
    font-size: 1.2rem;
    margin-bottom: 0;
}

.card {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    margin-bottom: 1rem;
}

.card-header {
    background: rgba(255, 255, 255, 0.05);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    color: #ffffff;
    font-weight: 600;
}

.card-body {
    color: #b0b0b0;
}

.status-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.75rem;
    padding: 0.5rem 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.status-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.status-item label {
    font-weight: 600;
    color: #ffffff;
}

.status-active {
    color: #28a745;
    font-weight: 600;
}

.no-membership-icon {
    font-size: 3rem;
    color: #6c757d;
}

.info-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 1.5rem;
}

.info-icon {
    background: #ff6b6b;
    color: #ffffff;
    width: 3rem;
    height: 3rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    flex-shrink: 0;
}

.info-content h6 {
    color: #ffffff;
    margin-bottom: 0.5rem;
}

.info-content p {
    margin-bottom: 0;
    color: #b0b0b0;
}

.option-item {
    padding: 1rem;
}

.option-icon {
    background: #ff6b6b;
    color: #ffffff;
    width: 4rem;
    height: 4rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-size: 1.5rem;
}

.option-item h6 {
    color: #ffffff;
    margin-bottom: 0.75rem;
}

.option-item p {
    color: #b0b0b0;
    margin-bottom: 1rem;
}

.btn {
    border-radius: 8px;
    font-weight: 500;
    padding: 0.5rem 1rem;
}

.btn-danger {
    background: #dc3545;
    border-color: #dc3545;
}

.btn-danger:hover {
    background: #c82333;
    border-color: #bd2130;
}

.btn-primary {
    background: #ff6b6b;
    border-color: #ff6b6b;
}

.btn-primary:hover {
    background: #ff5252;
    border-color: #ff5252;
}

.btn-outline-primary {
    border-color: #ff6b6b;
    color: #ff6b6b;
}

.btn-outline-primary:hover {
    background-color: #ff6b6b;
    border-color: #ff6b6b;
    color: #ffffff;
}

.btn-outline-secondary {
    border-color: #6c757d;
    color: #6c757d;
}

.btn-outline-secondary:hover {
    background-color: #6c757d;
    border-color: #6c757d;
    color: #ffffff;
}

.accordion-item {
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.accordion-button {
    background: rgba(255, 255, 255, 0.03);
    color: #ffffff;
    border: none;
}

.accordion-button:not(.collapsed) {
    background: rgba(255, 255, 255, 0.08);
    color: #ffffff;
}

.accordion-button:focus {
    box-shadow: 0 0 0 0.25rem rgba(255, 107, 107, 0.25);
}

.accordion-body {
    background: rgba(255, 255, 255, 0.02);
    color: #b0b0b0;
}

@media (max-width: 768px) {
    .cancel-container {
        padding: 1.5rem;
        margin: 1rem;
    }
    
    .cancel-title {
        font-size: 2rem;
    }
    
    .info-item {
        flex-direction: column;
        text-align: center;
    }
    
    .info-icon {
        margin: 0 auto 1rem;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Confirmation dialog for cancellation
    $('#cancel-membership-form').on('submit', function(e) {
        const confirmCancellation = $('#confirm_cancellation').is(':checked');
        const confirmNoRefund = $('#confirm_no_refund').is(':checked');
        
        if (!confirmCancellation || !confirmNoRefund) {
            e.preventDefault();
            alert('Please confirm both statements before proceeding with cancellation.');
            return false;
        }
        
        if (!confirm('Are you sure you want to cancel your membership? This action cannot be undone.')) {
            e.preventDefault();
            return false;
        }
        
        // Show loading state
        $('#cancel-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Cancelling...');
    });
    
    // Auto-check confirmation boxes when clicked
    $('#confirm_cancellation, #confirm_no_refund').on('change', function() {
        const bothChecked = $('#confirm_cancellation').is(':checked') && $('#confirm_no_refund').is(':checked');
        $('#cancel-btn').prop('disabled', !bothChecked);
    });
    
    // Initialize button state
    $('#cancel-btn').prop('disabled', true);
});
</script>

<?php get_footer(); ?>