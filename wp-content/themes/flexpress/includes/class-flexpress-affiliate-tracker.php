<?php
/**
 * FlexPress Affiliate Tracker
 * 
 * Handles affiliate click tracking, cookie management, and conversion attribution.
 * 
 * @package FlexPress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * FlexPress Affiliate Tracker Class
 */
class FlexPress_Affiliate_Tracker {
    
    /**
     * Instance of the class
     * 
     * @var FlexPress_Affiliate_Tracker
     */
    private static $instance = null;
    
    /**
     * Cookie name for tracking
     * 
     * @var string
     */
    private $cookie_name = 'flexpress_affiliate_tracking';
    
    /**
     * Cookie duration in days
     * 
     * @var int
     */
    private $cookie_duration = 30;
    
    /**
     * Get instance of the class
     * 
     * @return FlexPress_Affiliate_Tracker
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
        add_action('init', array($this, 'init_tracking'));
        add_action('wp_head', array($this, 'add_tracking_script'));
        add_action('wp_footer', array($this, 'add_tracking_pixel'));
    }
    
    /**
     * Initialize tracking on page load
     */
    public function init_tracking() {
        // Check if affiliate system is enabled
        $settings = get_option('flexpress_affiliate_settings', array());
        if (empty($settings['module_enabled'])) {
            return;
        }
        
        // Check for affiliate referral parameters (support both ?aff and legacy ?ref)
        $affiliate_code = '';
        if (isset($_GET['aff']) && $_GET['aff'] !== '') {
            $affiliate_code = sanitize_text_field($_GET['aff']);
        } elseif (isset($_GET['ref']) && $_GET['ref'] !== '') {
            $affiliate_code = sanitize_text_field($_GET['ref']);
        }
        $promo_code = sanitize_text_field($_GET['promo'] ?? '');
        
        if (!empty($affiliate_code)) {
            $this->process_affiliate_referral($affiliate_code, $promo_code);
        }
    }
    
    /**
     * Process affiliate referral
     * 
     * @param string $affiliate_code Affiliate code
     * @param string $promo_code Optional promo code
     */
    private function process_affiliate_referral($affiliate_code, $promo_code = '') {
        global $wpdb;
        
        // Get affiliate data
        $affiliate = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}flexpress_affiliates WHERE affiliate_code = %s AND status = 'active'",
            $affiliate_code
        ));
        
        if (!$affiliate) {
            return; // Invalid or inactive affiliate
        }
        
        // Get promo code data if provided
        $promo_code_id = null;
        if (!empty($promo_code)) {
            $promo_data = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}flexpress_affiliate_promo_codes WHERE code = %s AND status = 'active'",
                $promo_code
            ));
            
            if ($promo_data) {
                $promo_code_id = $promo_data->id;
            }
        }
        
        // Track the click
        $click_id = $this->track_click($affiliate->id, $promo_code_id);
        
        // Set tracking cookie
        $this->set_tracking_cookie($affiliate->id, $promo_code_id, $click_id);
        
        // Update affiliate click count
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}flexpress_affiliates SET total_clicks = total_clicks + 1 WHERE id = %d",
            $affiliate->id
        ));
    }
    
    /**
     * Track affiliate click
     * 
     * @param int $affiliate_id Affiliate ID
     * @param int|null $promo_code_id Promo code ID
     * @return int Click ID
     */
    public function track_click($affiliate_id, $promo_code_id = null) {
        $cookie_id = $this->get_or_create_cookie_id();
        $ip_address = $this->get_client_ip();
        $user_agent = sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? '');
        $referrer = sanitize_text_field($_SERVER['HTTP_REFERER'] ?? '');
        $landing_page = esc_url_raw($_SERVER['REQUEST_URI'] ?? '');
        
        global $wpdb;
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'flexpress_affiliate_clicks',
            array(
                'affiliate_id' => $affiliate_id,
                'promo_code_id' => $promo_code_id,
                'ip_address' => $ip_address,
                'user_agent' => $user_agent,
                'referrer' => $referrer,
                'landing_page' => $landing_page,
                'cookie_id' => $cookie_id,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return 0;
    }
    
    /**
     * Set tracking cookie
     * 
     * @param int $affiliate_id Affiliate ID
     * @param int|null $promo_code_id Promo code ID
     * @param int $click_id Click ID
     */
    private function set_tracking_cookie($affiliate_id, $promo_code_id, $click_id) {
        $cookie_data = array(
            'affiliate_id' => $affiliate_id,
            'promo_code_id' => $promo_code_id,
            'click_id' => $click_id,
            'timestamp' => time()
        );
        
        $cookie_value = base64_encode(json_encode($cookie_data));
        
        setcookie(
            $this->cookie_name,
            $cookie_value,
            time() + ($this->cookie_duration * DAY_IN_SECONDS),
            COOKIEPATH,
            COOKIE_DOMAIN,
            is_ssl(),
            true
        );
    }
    
    /**
     * Get tracking data from cookie
     * 
     * @return array|null Tracking data or null
     */
    public function get_tracking_data_from_cookie() {
        if (!isset($_COOKIE[$this->cookie_name])) {
            return null;
        }
        
        $cookie_data = json_decode(base64_decode($_COOKIE[$this->cookie_name]), true);
        
        if (!$cookie_data || !isset($cookie_data['affiliate_id'])) {
            return null;
        }
        
        // Check if cookie is still valid (not expired)
        $cookie_age = time() - $cookie_data['timestamp'];
        if ($cookie_age > ($this->cookie_duration * DAY_IN_SECONDS)) {
            $this->clear_tracking_cookie();
            return null;
        }
        
        return $cookie_data;
    }
    
    /**
     * Clear tracking cookie
     */
    public function clear_tracking_cookie() {
        setcookie(
            $this->cookie_name,
            '',
            time() - 3600,
            COOKIEPATH,
            COOKIE_DOMAIN,
            is_ssl(),
            true
        );
    }
    
    /**
     * Get or create cookie ID
     * 
     * @return string Cookie ID
     */
    private function get_or_create_cookie_id() {
        $tracking_data = $this->get_tracking_data_from_cookie();
        
        if ($tracking_data && isset($tracking_data['click_id'])) {
            return 'aff_' . $tracking_data['click_id'];
        }
        
        return 'aff_' . wp_generate_uuid4();
    }
    
    /**
     * Get client IP address
     * 
     * @return string IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Mark click as converted
     * 
     * @param int $click_id Click ID
     * @param string $conversion_type Conversion type
     * @param float $conversion_value Conversion value
     */
    public function mark_click_converted($click_id, $conversion_type, $conversion_value) {
        global $wpdb;
        
        $wpdb->update(
            $wpdb->prefix . 'flexpress_affiliate_clicks',
            array(
                'converted' => 1,
                'conversion_type' => $conversion_type,
                'conversion_value' => $conversion_value,
                'conversion_date' => current_time('mysql')
            ),
            array('id' => $click_id),
            array('%d', '%s', '%f', '%s'),
            array('%d')
        );
    }
    
    /**
     * Add tracking script to head
     */
    public function add_tracking_script() {
        if (!$this->is_tracking_enabled()) {
            return;
        }
        
        $tracking_data = $this->get_tracking_data_from_cookie();
        
        if (!$tracking_data) {
            return;
        }
        
        ?>
        <script type="text/javascript">
        window.flexpressAffiliateTracking = {
            affiliateId: <?php echo intval($tracking_data['affiliate_id']); ?>,
            promoCodeId: <?php echo intval($tracking_data['promo_code_id'] ?? 0); ?>,
            clickId: <?php echo intval($tracking_data['click_id']); ?>,
            timestamp: <?php echo intval($tracking_data['timestamp']); ?>
        };
        </script>
        <?php
    }
    
    /**
     * Add tracking pixel to footer
     */
    public function add_tracking_pixel() {
        if (!$this->is_tracking_enabled()) {
            return;
        }
        
        $tracking_data = $this->get_tracking_data_from_cookie();
        
        if (!$tracking_data) {
            return;
        }
        
        ?>
        <script type="text/javascript">
        (function() {
            // Track page view
            if (typeof flexpressAffiliateTracking !== 'undefined') {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.send('action=track_affiliate_pageview&affiliate_id=' + flexpressAffiliateTracking.affiliateId + '&click_id=' + flexpressAffiliateTracking.clickId + '&nonce=<?php echo wp_create_nonce('flexpress_affiliate_tracking'); ?>');
            }
        })();
        </script>
        <?php
    }
    
    /**
     * Check if tracking is enabled
     * 
     * @return bool True if enabled
     */
    private function is_tracking_enabled() {
        $settings = get_option('flexpress_affiliate_settings', array());
        return !empty($settings['module_enabled']);
    }
    
    /**
     * Get affiliate statistics
     * 
     * @param int $affiliate_id Affiliate ID
     * @param string $period Period (7d, 30d, 90d, 1y)
     * @return array Statistics
     */
    public function get_affiliate_stats($affiliate_id, $period = '30d') {
        global $wpdb;
        
        $days = 30;
        switch ($period) {
            case '7d':
                $days = 7;
                break;
            case '90d':
                $days = 90;
                break;
            case '1y':
                $days = 365;
                break;
        }
        
        $date_from = date('Y-m-d', strtotime("-{$days} days"));
        
        // Get click statistics
        $clicks = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_clicks,
                COUNT(CASE WHEN converted = 1 THEN 1 END) as conversions,
                SUM(CASE WHEN converted = 1 THEN conversion_value ELSE 0 END) as conversion_value
            FROM {$wpdb->prefix}flexpress_affiliate_clicks 
            WHERE affiliate_id = %d AND created_at >= %s",
            $affiliate_id,
            $date_from
        ));
        
        // Get transaction statistics
        $transactions = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_transactions,
                SUM(revenue_amount) as total_revenue,
                SUM(commission_amount) as total_commission
            FROM {$wpdb->prefix}flexpress_affiliate_transactions 
            WHERE affiliate_id = %d AND created_at >= %s",
            $affiliate_id,
            $date_from
        ));
        
        return array(
            'clicks' => intval($clicks->total_clicks ?? 0),
            'conversions' => intval($clicks->conversions ?? 0),
            'conversion_rate' => $clicks->total_clicks > 0 ? round(($clicks->conversions / $clicks->total_clicks) * 100, 2) : 0,
            'conversion_value' => floatval($clicks->conversion_value ?? 0),
            'transactions' => intval($transactions->total_transactions ?? 0),
            'revenue' => floatval($transactions->total_revenue ?? 0),
            'commission' => floatval($transactions->total_commission ?? 0)
        );
    }
    
    /**
     * Get conversion timeline data
     * 
     * @param int $affiliate_id Affiliate ID
     * @param int $days Number of days
     * @return array Timeline data
     */
    public function get_conversion_timeline($affiliate_id, $days = 30) {
        global $wpdb;
        
        $date_from = date('Y-m-d', strtotime("-{$days} days"));
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE(created_at) as date,
                COUNT(*) as clicks,
                COUNT(CASE WHEN converted = 1 THEN 1 END) as conversions,
                SUM(CASE WHEN converted = 1 THEN conversion_value ELSE 0 END) as revenue
            FROM {$wpdb->prefix}flexpress_affiliate_clicks 
            WHERE affiliate_id = %d AND created_at >= %s
            GROUP BY DATE(created_at)
            ORDER BY date ASC",
            $affiliate_id,
            $date_from
        ));
        
        return $results;
    }
}

// Initialize the affiliate tracker
FlexPress_Affiliate_Tracker::get_instance();

/**
 * Handle affiliate pageview tracking
 */
function flexpress_track_affiliate_pageview() {
    check_ajax_referer('flexpress_affiliate_tracking', 'nonce');
    
    $affiliate_id = intval($_POST['affiliate_id'] ?? 0);
    $click_id = intval($_POST['click_id'] ?? 0);
    
    if ($affiliate_id && $click_id) {
        // Log pageview for analytics
        error_log("Affiliate pageview: Affiliate {$affiliate_id}, Click {$click_id}");
    }
    
    wp_die();
}
add_action('wp_ajax_track_affiliate_pageview', 'flexpress_track_affiliate_pageview');
add_action('wp_ajax_nopriv_track_affiliate_pageview', 'flexpress_track_affiliate_pageview');
