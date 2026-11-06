<?php
/**
 * Template Name: Payment Success
 * 
 * Payment success page template for completed transactions.
 * 
 * @package FlexPress
 * @since 1.0.0
 */

// Browser query parameters never authenticate a customer or confirm a payment.
nocache_headers();
if (!is_user_logged_in()) {
    flexpress_flowguard_redirect_payment_return();
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$transaction_id = isset($_GET['transaction_id']) && is_string($_GET['transaction_id']) ? sanitize_text_field(wp_unslash($_GET['transaction_id'])) : '';
$ref = isset($_GET['ref']) && is_string($_GET['ref']) ? sanitize_text_field(wp_unslash($_GET['ref'])) : '';
$session_id = isset($_GET['session_id']) && is_string($_GET['session_id']) ? sanitize_text_field(wp_unslash($_GET['session_id'])) : '';
if ($ref === '' && $session_id !== '') {
    $session = flexpress_flowguard_get_session($session_id);
    if ($session && (int) $session['user_id'] === (int) $user_id) {
        $ref = $session['reference_id'] ?? '';
    }
}
$transaction = flexpress_flowguard_get_return_transaction($user_id, $transaction_id, $ref);
$payment_confirmed = $transaction && $transaction['status'] === 'approved';
$transaction_id = $payment_confirmed ? $transaction['transaction_id'] : '';
$sale_id = $payment_confirmed ? ($transaction['sale_id'] ?? '') : '';
$reference_data = $payment_confirmed ? flexpress_flowguard_parse_enhanced_reference($transaction['reference_id'] ?? '') : [];
$episode_id = $reference_data['episode_id'] ?? 0;
$is_ppv_purchase = !empty($reference_data['is_ppv']);
$episode = $episode_id ? get_post($episode_id) : null;
$membership_status = get_user_meta($user_id, 'membership_status', true);
$subscription_amount = $payment_confirmed ? $transaction['amount'] : '';
$subscription_currency = $payment_confirmed ? $transaction['currency'] : '';
$next_rebill_date = get_user_meta($user_id, 'next_rebill_date', true);
$membership_expires = get_user_meta($user_id, 'membership_expires', true);
get_header();
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
                        <h1 class="success-title"><?php echo $payment_confirmed ? esc_html__('Payment confirmed', 'flexpress') : esc_html__('Payment confirmation pending', 'flexpress'); ?></h1>
                        <?php if (!$payment_confirmed): ?>
                            <p class="success-subtitle">We are waiting for confirmation from the payment provider. Access updates after confirmation. Refresh this page shortly or check your account.</p>
                            <button type="button" class="btn btn-primary" onclick="window.location.reload()">Check again</button>
                            <a class="btn btn-outline-primary" href="<?php echo esc_url(home_url('/my-account/')); ?>">My account</a>
                        <?php elseif ($is_ppv_purchase && $episode): ?>
                            <p class="success-subtitle">Purchase confirmed for "<?php echo esc_html($episode->post_title); ?>".</p>
                        <?php else: ?>
                            <p class="success-subtitle">Thank you for your purchase. Your account has been updated.</p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($payment_confirmed): ?>
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
                                        <?php if (!empty($transaction_id)): ?>
                                        <div class="detail-item">
                                            <label>Transaction ID:</label>
                                            <span><?php echo esc_html($transaction_id); ?></span>
                                        </div>
                                        <?php endif; ?>
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
                                                <h6>View Your Purchase</h6>
                                                <p>Your purchase of "<?php echo esc_html($episode->post_title); ?>" is confirmed.</p>
                                                <a href="<?php echo get_permalink($episode_id); ?>?ppv=unlocked&transaction_id=<?php echo esc_attr($transaction_id); ?>" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-play me-1"></i>
                                                    View Content
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
                    
                    <?php endif; ?>
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
    background-color: var(--color-background);
    min-height: 100vh;
    padding: 2rem 0;
}

.success-container {
    background-color: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: 16px;
    padding: 2rem;
    box-shadow: var(--shadow-lg);
}

.success-icon {
    font-size: 4rem;
    color: var(--color-accent);
    margin-bottom: 1rem;
}

.success-title {
    color: var(--color-text);
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.success-subtitle {
    color: var(--color-text-secondary);
    font-size: 1.2rem;
    margin-bottom: 0;
}

.card {
    background-color: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: 12px;
    margin-bottom: 1rem;
}

.card-header {
    background-color: var(--color-surface);
    border-bottom: 1px solid var(--color-border);
    color: var(--color-text);
    font-weight: 600;
}

.card-body {
    color: var(--color-text-secondary);
}

.detail-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.75rem;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--color-border);
}

.detail-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.detail-item label {
    font-weight: 600;
    color: var(--color-text);
}

.status-success {
    color: var(--color-accent);
    font-weight: 600;
}

.membership-active .status-indicator {
    display: flex;
    align-items: center;
    color: var(--color-accent);
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
    background: var(--color-accent);
    color: var(--color-accent-text);
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
    color: var(--color-text);
    margin-bottom: 0.5rem;
}

.step-content p {
    margin-bottom: 0.75rem;
    color: var(--color-text-secondary);
}


.text-muted {
    color: var(--color-text-secondary) !important;
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
    if (!<?php echo wp_json_encode((bool) $payment_confirmed); ?>) return;
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

// Only offer dashboard navigation after a confirmed payment.
setTimeout(function() {
    if (!<?php echo wp_json_encode((bool) $payment_confirmed); ?>) return;
    if (confirm('Would you like to go to your dashboard now?')) {
        window.location.href = '<?php echo home_url('/dashboard'); ?>';
    }
}, 30000);
</script>

<?php get_footer(); ?>
