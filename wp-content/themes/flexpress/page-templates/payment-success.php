<?php
/**
 * Template Name: Payment Success
 * 
 * Payment success page template for completed transactions.
 * 
 * @package FlexPress
 * @since 1.0.0
 */

// Check if user is logged in
if (!is_user_logged_in()) {
    // Check if user_id is provided in URL parameters (from payment flow)
    $user_id_from_url = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    
    if ($user_id_from_url && get_userdata($user_id_from_url)) {
        // Auto-login the user if valid user_id is provided
        wp_set_current_user($user_id_from_url);
        wp_set_auth_cookie($user_id_from_url);
    } else {
        // No valid user_id, redirect to login with return URL
        $current_url = home_url($_SERVER['REQUEST_URI']);
        wp_redirect(home_url('/login?redirect_to=' . urlencode($current_url)));
        exit;
    }
}

get_header();

$transaction_id = isset($_GET['transaction_id']) ? sanitize_text_field($_GET['transaction_id']) : '';
$sale_id = isset($_GET['sale_id']) ? sanitize_text_field($_GET['sale_id']) : '';
$episode_id = isset($_GET['episode_id']) ? intval($_GET['episode_id']) : 0;
$ref = isset($_GET['ref']) ? sanitize_text_field($_GET['ref']) : '';

// Get current user (now guaranteed to be logged in)
$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Check if this is a PPV purchase
$is_ppv_purchase = $episode_id > 0;
$episode = null;
if ($is_ppv_purchase) {
    $episode = get_post($episode_id);
}

// Get transaction details if available
$transaction = null;
if ($transaction_id) {
    $transaction = flexpress_flowguard_get_transaction($transaction_id);
}

// Get user's membership status
$membership_status = get_user_meta($user_id, 'membership_status', true);
$subscription_amount = get_user_meta($user_id, 'subscription_amount', true);
$subscription_currency = get_user_meta($user_id, 'subscription_currency', true);
$next_rebill_date = get_user_meta($user_id, 'next_rebill_date', true);
$membership_expires = get_user_meta($user_id, 'membership_expires', true);
?>

<main id="primary" class="site-main payment-success-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="success-container">
                    <!-- Success Header -->
                    <div class="success-header text-center mb-5">
                        <div class="success-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h1 class="success-title">Payment Successful!</h1>
                        <?php if ($is_ppv_purchase && $episode): ?>
                            <p class="success-subtitle">Episode unlocked! You now have access to "<?php echo esc_html($episode->post_title); ?>".</p>
                        <?php else: ?>
                            <p class="success-subtitle">Thank you for your purchase. Your account has been updated.</p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Transaction Details -->
                    <div class="transaction-details">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-receipt me-2"></i>
                                    Transaction Details
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="detail-item">
                                            <label>Transaction ID:</label>
                                            <span><?php echo esc_html($transaction_id ?: 'N/A'); ?></span>
                                        </div>
                                        <?php if ($sale_id): ?>
                                        <div class="detail-item">
                                            <label>Sale ID:</label>
                                            <span><?php echo esc_html($sale_id); ?></span>
                                        </div>
                                        <?php endif; ?>
                                        <div class="detail-item">
                                            <label>Date:</label>
                                            <span><?php echo current_time('F j, Y \a\t g:i A'); ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <?php if ($subscription_amount): ?>
                                        <div class="detail-item">
                                            <label>Amount:</label>
                                            <span><?php echo esc_html($subscription_currency . ' ' . $subscription_amount); ?></span>
                                        </div>
                                        <?php endif; ?>
                                        <div class="detail-item">
                                            <label>Status:</label>
                                            <span class="status-success">Completed</span>
                                        </div>
                                        <?php if ($next_rebill_date): ?>
                                        <div class="detail-item">
                                            <label>Next Billing:</label>
                                            <span><?php echo esc_html(date('F j, Y', strtotime($next_rebill_date))); ?></span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Membership Status -->
                    <?php if ($membership_status === 'active'): ?>
                    <div class="membership-status mt-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-crown me-2"></i>
                                    Membership Status
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="membership-active">
                                    <div class="status-indicator">
                                        <i class="fas fa-check-circle"></i>
                                        <span>Active Membership</span>
                                    </div>
                                    <p class="mt-3 mb-0">
                                        Welcome to your premium membership! You now have access to all exclusive content.
                                    </p>
                                    <?php if ($membership_expires): ?>
                                    <p class="mt-2 mb-0 text-muted">
                                        <small>Access expires: <?php echo esc_html(date('F j, Y', strtotime($membership_expires))); ?></small>
                                    </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Next Steps -->
                    <div class="next-steps mt-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-arrow-right me-2"></i>
                                    What's Next?
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="steps-list">
                                    <?php if ($is_ppv_purchase && $episode): ?>
                                        <div class="step-item">
                                            <div class="step-number">1</div>
                                            <div class="step-content">
                                                <h6>Watch Your Episode</h6>
                                                <p>You now have full access to "<?php echo esc_html($episode->post_title); ?>".</p>
                                                <a href="<?php echo get_permalink($episode_id); ?>" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-play me-1"></i>
                                                    Watch Episode
                                                </a>
                                            </div>
                                        </div>
                                        <div class="step-item">
                                            <div class="step-number">2</div>
                                            <div class="step-content">
                                                <h6>Access Your Dashboard</h6>
                                                <p>View your purchased episodes and manage your account.</p>
                                                <a href="<?php echo home_url('/dashboard'); ?>" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-tachometer-alt me-1"></i>
                                                    Go to Dashboard
                                                </a>
                                            </div>
                                        </div>
                                        <div class="step-item">
                                            <div class="step-number">3</div>
                                            <div class="step-content">
                                                <h6>Browse More Content</h6>
                                                <p>Discover other exclusive episodes and premium content.</p>
                                                <a href="<?php echo home_url('/episodes'); ?>" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-search me-1"></i>
                                                    Browse Episodes
                                                </a>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="step-item">
                                            <div class="step-number">1</div>
                                            <div class="step-content">
                                                <h6>Access Your Dashboard</h6>
                                                <p>Manage your account, view billing history, and update payment methods.</p>
                                                <a href="<?php echo home_url('/dashboard'); ?>" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-tachometer-alt me-1"></i>
                                                    Go to Dashboard
                                                </a>
                                            </div>
                                        </div>
                                        <div class="step-item">
                                            <div class="step-number">2</div>
                                            <div class="step-content">
                                                <h6>Explore Premium Content</h6>
                                                <p>Browse our exclusive videos, galleries, and member-only features.</p>
                                                <a href="<?php echo home_url('/episodes'); ?>" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-play me-1"></i>
                                                    Browse Content
                                                </a>
                                            </div>
                                        </div>
                                        <div class="step-item">
                                            <div class="step-number">3</div>
                                            <div class="step-content">
                                                <h6>Download Receipt</h6>
                                                <p>Save your transaction receipt for your records.</p>
                                                <button onclick="downloadReceipt()" class="btn btn-outline-secondary btn-sm">
                                                    <i class="fas fa-download me-1"></i>
                                                    Download Receipt
                                                </button>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Support Information -->
                    <div class="support-info mt-4 text-center">
                        <p class="text-muted">
                            Need help or have questions about your purchase?
                        </p>
                        <a href="<?php echo home_url('/contact'); ?>" class="btn btn-outline-primary">
                            <i class="fas fa-envelope me-1"></i>
                            Contact Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.payment-success-page {
    background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
    min-height: 100vh;
    padding: 2rem 0;
}

.success-container {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 16px;
    padding: 2rem;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

.success-icon {
    font-size: 4rem;
    color: #28a745;
    margin-bottom: 1rem;
}

.success-title {
    color: #ffffff;
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.success-subtitle {
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

.detail-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.75rem;
    padding: 0.5rem 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.detail-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.detail-item label {
    font-weight: 600;
    color: #ffffff;
}

.status-success {
    color: #28a745;
    font-weight: 600;
}

.membership-active .status-indicator {
    display: flex;
    align-items: center;
    color: #28a745;
    font-weight: 600;
    font-size: 1.1rem;
}

.membership-active .status-indicator i {
    margin-right: 0.5rem;
    font-size: 1.2rem;
}

.steps-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.step-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.step-number {
    background: #ff6b6b;
    color: #ffffff;
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    flex-shrink: 0;
}

.step-content h6 {
    color: #ffffff;
    margin-bottom: 0.5rem;
}

.step-content p {
    margin-bottom: 0.75rem;
    color: #b0b0b0;
}

.btn {
    border-radius: 8px;
    font-weight: 500;
    padding: 0.5rem 1rem;
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

@media (max-width: 768px) {
    .success-container {
        padding: 1.5rem;
        margin: 1rem;
    }
    
    .success-title {
        font-size: 2rem;
    }
    
    .step-item {
        flex-direction: column;
        text-align: center;
    }
    
    .step-number {
        align-self: center;
    }
}
</style>

<script>
function downloadReceipt() {
    // Create receipt data
    const receiptData = {
        transactionId: '<?php echo esc_js($transaction_id); ?>',
        saleId: '<?php echo esc_js($sale_id); ?>',
        date: '<?php echo current_time('F j, Y \a\t g:i A'); ?>',
        amount: '<?php echo esc_js($subscription_currency . ' ' . $subscription_amount); ?>',
        status: 'Completed',
        customer: '<?php echo esc_js($current_user->display_name); ?>',
        email: '<?php echo esc_js($current_user->user_email); ?>'
    };
    
    // Create receipt HTML
    const receiptHTML = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Payment Receipt</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .receipt { max-width: 600px; margin: 0 auto; }
                .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 20px; }
                .detail { margin: 10px 0; }
                .label { font-weight: bold; }
                .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ccc; }
            </style>
        </head>
        <body>
            <div class="receipt">
                <div class="header">
                    <h1>Payment Receipt</h1>
                    <p>Thank you for your purchase!</p>
                </div>
                <div class="detail"><span class="label">Transaction ID:</span> ${receiptData.transactionId}</div>
                <div class="detail"><span class="label">Sale ID:</span> ${receiptData.saleId}</div>
                <div class="detail"><span class="label">Date:</span> ${receiptData.date}</div>
                <div class="detail"><span class="label">Amount:</span> ${receiptData.amount}</div>
                <div class="detail"><span class="label">Status:</span> ${receiptData.status}</div>
                <div class="detail"><span class="label">Customer:</span> ${receiptData.customer}</div>
                <div class="detail"><span class="label">Email:</span> ${receiptData.email}</div>
                <div class="footer">
                    <p>This receipt was generated on ${new Date().toLocaleString()}</p>
                </div>
            </div>
        </body>
        </html>
    `;
    
    // Create and download file
    const blob = new Blob([receiptHTML], { type: 'text/html' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `payment-receipt-${receiptData.transactionId}.html`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

// Auto-redirect to dashboard after 30 seconds
setTimeout(function() {
    if (confirm('Would you like to go to your dashboard now?')) {
        window.location.href = '<?php echo home_url('/dashboard'); ?>';
    }
}, 30000);
</script>

<?php get_footer(); ?>
