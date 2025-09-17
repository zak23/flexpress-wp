<?php
/**
 * FlexPress Affiliate Dashboard
 * 
 * Handles affiliate dashboard display and data management.
 * 
 * @package FlexPress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * FlexPress Affiliate Dashboard Class
 */
class FlexPress_Affiliate_Dashboard {
    
    /**
     * Instance of the class
     * 
     * @var FlexPress_Affiliate_Dashboard
     */
    private static $instance = null;
    
    /**
     * Get instance of the class
     * 
     * @return FlexPress_Affiliate_Dashboard
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('wp_ajax_get_affiliate_dashboard_data', array($this, 'get_dashboard_data'));
        add_action('wp_ajax_get_affiliate_stats', array($this, 'get_affiliate_stats'));
        add_action('wp_ajax_get_affiliate_payouts', array($this, 'get_affiliate_payouts'));
    }
    
    /**
     * Get current affiliate for logged-in user
     * 
     * @return object|null Affiliate object or null
     */
    public function get_current_affiliate() {
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return null;
        }
        
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}flexpress_affiliates WHERE user_id = %d AND status = 'active'",
            $user_id
        ));
    }
    
    /**
     * Render affiliate dashboard
     */
    public function render_dashboard() {
        $affiliate = $this->get_current_affiliate();
        
        if (!$affiliate) {
            echo '<div class="affiliate-dashboard-error">';
            echo '<h2>' . esc_html__('Access Denied', 'flexpress') . '</h2>';
            echo '<p>' . esc_html__('You are not an approved affiliate or you are not logged in.', 'flexpress') . '</p>';
            echo '<p><a href="' . esc_url(home_url('/affiliate-application')) . '" class="button">' . esc_html__('Apply to Become an Affiliate', 'flexpress') . '</a></p>';
            echo '</div>';
            return;
        }
        
        ?>
        <div class="affiliate-dashboard">
            <div class="dashboard-header">
                <h2><?php esc_html_e('Affiliate Dashboard', 'flexpress'); ?></h2>
                <p class="welcome-message">
                    <?php printf(esc_html__('Welcome back, %s!', 'flexpress'), esc_html($affiliate->display_name)); ?>
                </p>
            </div>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-icon">👆</div>
                    <h3><?php esc_html_e('Total Clicks', 'flexpress'); ?></h3>
                    <div class="stat-number"><?php echo number_format($affiliate->total_clicks); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <h3><?php esc_html_e('Total Signups', 'flexpress'); ?></h3>
                    <div class="stat-number"><?php echo number_format($affiliate->total_signups); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <h3><?php esc_html_e('Total Revenue', 'flexpress'); ?></h3>
                    <div class="stat-number">$<?php echo number_format($affiliate->total_revenue, 2); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <h3><?php esc_html_e('Pending Commission', 'flexpress'); ?></h3>
                    <div class="stat-number">$<?php echo number_format($affiliate->pending_commission, 2); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <h3><?php esc_html_e('Approved Commission', 'flexpress'); ?></h3>
                    <div class="stat-number">$<?php echo number_format($affiliate->approved_commission, 2); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">💳</div>
                    <h3><?php esc_html_e('Paid Commission', 'flexpress'); ?></h3>
                    <div class="stat-number">$<?php echo number_format($affiliate->paid_commission, 2); ?></div>
                </div>
            </div>
            
            <div class="dashboard-sections">
                <div class="section referral-section">
                    <h3><?php esc_html_e('Your Referral Link', 'flexpress'); ?></h3>
                    <div class="referral-link-container">
                        <input type="text" id="referral-url" value="<?php echo esc_attr($affiliate->referral_url); ?>" readonly>
                        <button class="copy-link-button" data-copy-target="referral-url">
                            <?php esc_html_e('Copy', 'flexpress'); ?>
                        </button>
                    </div>
                    <p class="referral-description">
                        <?php esc_html_e('Share this link to start earning commissions!', 'flexpress'); ?>
                    </p>
                </div>
                
                <div class="section promo-codes-section">
                    <h3><?php esc_html_e('Your Promo Codes', 'flexpress'); ?></h3>
                    <div class="promo-codes-list">
                        <?php $this->render_assigned_promo_codes($affiliate->id); ?>
                    </div>
                </div>
                
                <div class="section commission-rates-section">
                    <h3><?php esc_html_e('Your Commission Rates', 'flexpress'); ?></h3>
                    <div class="commission-rates">
                        <div class="rate-item">
                            <span class="rate-label"><?php esc_html_e('Initial Sales:', 'flexpress'); ?></span>
                            <span class="rate-value"><?php echo esc_html($affiliate->commission_initial); ?>%</span>
                        </div>
                        <div class="rate-item">
                            <span class="rate-label"><?php esc_html_e('Recurring Payments:', 'flexpress'); ?></span>
                            <span class="rate-value"><?php echo esc_html($affiliate->commission_rebill); ?>%</span>
                        </div>
                        <div class="rate-item">
                            <span class="rate-label"><?php esc_html_e('Unlock Purchases:', 'flexpress'); ?></span>
                            <span class="rate-value"><?php echo esc_html($affiliate->commission_unlock); ?>%</span>
                        </div>
                        <div class="rate-item">
                            <span class="rate-label"><?php esc_html_e('Payout Threshold:', 'flexpress'); ?></span>
                            <span class="rate-value">$<?php echo esc_html($affiliate->payout_threshold); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="section recent-activity-section">
                    <h3><?php esc_html_e('Recent Activity', 'flexpress'); ?></h3>
                    <div class="activity-list">
                        <?php $this->render_recent_activity($affiliate->id); ?>
                    </div>
                </div>
                
                <div class="section payout-history-section">
                    <h3><?php esc_html_e('Payout History', 'flexpress'); ?></h3>
                    <div class="payout-history">
                        <?php $this->render_payout_history($affiliate->id); ?>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-actions">
                <a href="<?php echo esc_url(home_url('/affiliate-terms')); ?>" class="button">
                    <?php esc_html_e('Terms & Conditions', 'flexpress'); ?>
                </a>
                <a href="<?php echo esc_url(home_url('/contact')); ?>" class="button">
                    <?php esc_html_e('Contact Support', 'flexpress'); ?>
                </a>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Copy link functionality
            $('.copy-link-button').on('click', function() {
                var target = $(this).data('copy-target');
                var input = $('#' + target);
                
                input.select();
                document.execCommand('copy');
                
                $(this).text('<?php esc_html_e('Copied!', 'flexpress'); ?>');
                setTimeout(() => {
                    $(this).text('<?php esc_html_e('Copy', 'flexpress'); ?>');
                }, 2000);
            });
            
            // Load additional data via AJAX
            loadAffiliateStats();
        });
        
        function loadAffiliateStats() {
            jQuery.post(ajaxurl, {
                action: 'get_affiliate_stats',
                nonce: '<?php echo wp_create_nonce('flexpress_affiliate_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    // Update stats with real-time data
                    console.log('Affiliate stats loaded:', response.data);
                }
            });
        }
        </script>
        <?php
    }
    
    /**
     * Render assigned promo codes
     * 
     * @param int $affiliate_id Affiliate ID
     */
    private function render_assigned_promo_codes($affiliate_id) {
        global $wpdb;
        
        $promo_codes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}flexpress_affiliate_promo_codes 
             WHERE affiliate_id = %d AND status = 'active' 
             ORDER BY created_at DESC",
            $affiliate_id
        ));
        
        if (empty($promo_codes)) {
            echo '<p class="no-promo-codes">' . esc_html__('No promo codes assigned yet.', 'flexpress') . '</p>';
            return;
        }
        
        foreach ($promo_codes as $promo_code) {
            ?>
            <div class="promo-code-item">
                <div class="promo-code-header">
                    <span class="promo-code-name"><?php echo esc_html($promo_code->code); ?></span>
                    <span class="promo-code-status status-<?php echo esc_attr($promo_code->status); ?>">
                        <?php echo esc_html(ucfirst($promo_code->status)); ?>
                    </span>
                </div>
                <div class="promo-code-stats">
                    <span class="usage-count"><?php printf(esc_html__('Used %d times', 'flexpress'), $promo_code->usage_count); ?></span>
                    <span class="revenue"><?php printf(esc_html__('Generated $%s', 'flexpress'), number_format($promo_code->revenue_generated, 2)); ?></span>
                </div>
                <?php if (!empty($promo_code->custom_pricing_json)): ?>
                    <div class="custom-pricing">
                        <small><?php esc_html_e('Custom pricing applied', 'flexpress'); ?></small>
                    </div>
                <?php endif; ?>
            </div>
            <?php
        }
    }
    
    /**
     * Render recent activity
     * 
     * @param int $affiliate_id Affiliate ID
     */
    private function render_recent_activity($affiliate_id) {
        global $wpdb;
        
        $activities = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                'click' as type,
                created_at,
                landing_page as description,
                NULL as amount
            FROM {$wpdb->prefix}flexpress_affiliate_clicks 
            WHERE affiliate_id = %d 
            ORDER BY created_at DESC 
            LIMIT 5",
            $affiliate_id
        ));
        
        if (empty($activities)) {
            echo '<p class="no-activity">' . esc_html__('No recent activity.', 'flexpress') . '</p>';
            return;
        }
        
        foreach ($activities as $activity) {
            ?>
            <div class="activity-item">
                <div class="activity-type activity-<?php echo esc_attr($activity->type); ?>">
                    <?php echo esc_html(ucfirst($activity->type)); ?>
                </div>
                <div class="activity-description">
                    <?php echo esc_html($activity->description); ?>
                </div>
                <div class="activity-date">
                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($activity->created_at))); ?>
                </div>
            </div>
            <?php
        }
    }
    
    /**
     * Render payout history
     * 
     * @param int $affiliate_id Affiliate ID
     */
    private function render_payout_history($affiliate_id) {
        global $wpdb;
        
        $payouts = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}flexpress_affiliate_payouts 
             WHERE affiliate_id = %d 
             ORDER BY created_at DESC 
             LIMIT 10",
            $affiliate_id
        ));
        
        if (empty($payouts)) {
            echo '<p class="no-payouts">' . esc_html__('No payouts yet.', 'flexpress') . '</p>';
            return;
        }
        
        ?>
        <div class="payouts-table">
            <table>
                <thead>
                    <tr>
                        <th><?php esc_html_e('Period', 'flexpress'); ?></th>
                        <th><?php esc_html_e('Amount', 'flexpress'); ?></th>
                        <th><?php esc_html_e('Status', 'flexpress'); ?></th>
                        <th><?php esc_html_e('Date', 'flexpress'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payouts as $payout): ?>
                    <tr>
                        <td>
                            <?php 
                            printf(
                                esc_html__('%s to %s', 'flexpress'),
                                esc_html(date_i18n(get_option('date_format'), strtotime($payout->period_start))),
                                esc_html(date_i18n(get_option('date_format'), strtotime($payout->period_end)))
                            );
                            ?>
                        </td>
                        <td>$<?php echo number_format($payout->payout_amount, 2); ?></td>
                        <td>
                            <span class="payout-status status-<?php echo esc_attr($payout->status); ?>">
                                <?php echo esc_html(ucfirst($payout->status)); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($payout->created_at))); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Get dashboard data via AJAX
     */
    public function get_dashboard_data() {
        check_ajax_referer('flexpress_affiliate_nonce', 'nonce');
        
        $affiliate = $this->get_current_affiliate();
        
        if (!$affiliate) {
            wp_send_json_error(['message' => __('Access denied.', 'flexpress')]);
        }
        
        $tracker = FlexPress_Affiliate_Tracker::get_instance();
        $stats = $tracker->get_affiliate_stats($affiliate->id, '30d');
        
        wp_send_json_success([
            'affiliate' => $affiliate,
            'stats' => $stats,
            'timeline' => $tracker->get_conversion_timeline($affiliate->id, 30)
        ]);
    }
    
    /**
     * Get affiliate stats via AJAX
     */
    public function get_affiliate_stats() {
        check_ajax_referer('flexpress_affiliate_nonce', 'nonce');
        
        $affiliate = $this->get_current_affiliate();
        
        if (!$affiliate) {
            wp_send_json_error(['message' => __('Access denied.', 'flexpress')]);
        }
        
        $period = sanitize_text_field($_POST['period'] ?? '30d');
        $tracker = FlexPress_Affiliate_Tracker::get_instance();
        
        wp_send_json_success($tracker->get_affiliate_stats($affiliate->id, $period));
    }
    
    /**
     * Get affiliate payouts via AJAX
     */
    public function get_affiliate_payouts() {
        check_ajax_referer('flexpress_affiliate_nonce', 'nonce');
        
        $affiliate = $this->get_current_affiliate();
        
        if (!$affiliate) {
            wp_send_json_error(['message' => __('Access denied.', 'flexpress')]);
        }
        
        global $wpdb;
        
        $payouts = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}flexpress_affiliate_payouts 
             WHERE affiliate_id = %d 
             ORDER BY created_at DESC",
            $affiliate->id
        ));
        
        wp_send_json_success($payouts);
    }
}

// Initialize the affiliate dashboard
FlexPress_Affiliate_Dashboard::get_instance();
