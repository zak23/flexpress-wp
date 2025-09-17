# FlexPress Affiliate & Promo-Code Management System
## Comprehensive Implementation Guide

### 🎯 **Objective**
Build a self-contained affiliate and promo-code management system integrated into the FlexPress WordPress theme. The system handles sign-ups, tracking, reporting, commissions, and payouts for affiliates while supporting in-house promo codes. It can be toggled on/off from theme settings and automatically sets up its database schema on theme activation.

---

## 🏗️ **System Architecture**

### **Integration Points**
- **FlexPress Settings System**: Extends existing admin panel structure
- **Flowguard Payment Integration**: Hooks into existing webhook system
- **Database Schema**: Extends current table structure with new affiliate tables
- **Theme Activation**: Integrates with existing `flexpress_theme_activation()` hook
- **User Management**: Leverages WordPress user system with custom roles

### **File Structure**
```
wp-content/themes/flexpress/
├── includes/
│   ├── affiliate-system/
│   │   ├── class-flexpress-affiliate-manager.php
│   │   ├── class-flexpress-affiliate-tracker.php
│   │   ├── class-flexpress-affiliate-dashboard.php
│   │   ├── affiliate-database.php
│   │   ├── affiliate-helpers.php
│   │   └── affiliate-shortcodes.php
│   ├── admin/
│   │   └── class-flexpress-affiliate-settings.php (enhanced)
│   └── flowguard-integration.php (enhanced)
├── assets/
│   ├── css/
│   │   └── affiliate-system.css
│   └── js/
│       ├── affiliate-admin.js (enhanced)
│       ├── affiliate-dashboard.js
│       └── affiliate-tracking.js
├── page-templates/
│   ├── affiliate-application.php
│   ├── affiliate-dashboard.php
│   └── affiliate-terms.php
└── template-parts/
    └── affiliate/
        ├── application-form.php
        ├── dashboard-stats.php
        ├── referral-links.php
        └── payout-history.php
```

---

## 📊 **Database Schema**

### **Core Tables**

#### **wp_flexpress_affiliates**
```sql
CREATE TABLE wp_flexpress_affiliates (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) NOT NULL,
    affiliate_code varchar(50) NOT NULL UNIQUE,
    display_name varchar(100) NOT NULL,
    email varchar(100) NOT NULL,
    website varchar(255),
    social_media text,
    payout_method enum('paypal', 'bank_transfer', 'check', 'crypto') NOT NULL DEFAULT 'paypal',
    payout_details text,
    tax_info text,
    commission_initial decimal(5,2) NOT NULL DEFAULT 25.00,
    commission_rebill decimal(5,2) NOT NULL DEFAULT 10.00,
    commission_unlock decimal(5,2) NOT NULL DEFAULT 15.00,
    commission_type enum('percentage', 'flat') NOT NULL DEFAULT 'percentage',
    status enum('pending', 'active', 'suspended', 'rejected') NOT NULL DEFAULT 'pending',
    payout_threshold decimal(10,2) NOT NULL DEFAULT 100.00,
    total_clicks bigint(20) NOT NULL DEFAULT 0,
    total_signups bigint(20) NOT NULL DEFAULT 0,
    total_rebills bigint(20) NOT NULL DEFAULT 0,
    total_unlocks bigint(20) NOT NULL DEFAULT 0,
    total_revenue decimal(10,2) NOT NULL DEFAULT 0.00,
    total_commission decimal(10,2) NOT NULL DEFAULT 0.00,
    pending_commission decimal(10,2) NOT NULL DEFAULT 0.00,
    approved_commission decimal(10,2) NOT NULL DEFAULT 0.00,
    paid_commission decimal(10,2) NOT NULL DEFAULT 0.00,
    referral_url varchar(255) NOT NULL DEFAULT '',
    application_data text,
    notes text,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY affiliate_code (affiliate_code),
    KEY user_id (user_id),
    KEY status (status),
    KEY email (email),
    KEY created_at (created_at)
);
```

#### **wp_flexpress_affiliate_promo_codes**
```sql
CREATE TABLE wp_flexpress_affiliate_promo_codes (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    code varchar(50) NOT NULL UNIQUE,
    affiliate_id bigint(20) NULL,
    display_name varchar(100) NOT NULL,
    custom_pricing_json text,
    status enum('active', 'inactive', 'expired') NOT NULL DEFAULT 'active',
    usage_limit int(11) NULL,
    usage_count int(11) NOT NULL DEFAULT 0,
    revenue_generated decimal(10,2) NOT NULL DEFAULT 0.00,
    commission_earned decimal(10,2) NOT NULL DEFAULT 0.00,
    valid_from datetime NULL,
    valid_until datetime NULL,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY code (code),
    KEY affiliate_id (affiliate_id),
    KEY status (status),
    KEY valid_from (valid_from),
    KEY valid_until (valid_until),
    FOREIGN KEY (affiliate_id) REFERENCES wp_flexpress_affiliates(id) ON DELETE SET NULL
);
```

#### **wp_flexpress_affiliate_clicks**
```sql
CREATE TABLE wp_flexpress_affiliate_clicks (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    affiliate_id bigint(20) NOT NULL,
    promo_code_id bigint(20) NULL,
    ip_address varchar(45) NOT NULL,
    user_agent text,
    referrer varchar(255),
    landing_page varchar(255),
    cookie_id varchar(255) NOT NULL,
    converted tinyint(1) NOT NULL DEFAULT 0,
    conversion_type enum('signup', 'rebill', 'unlock') NULL,
    conversion_value decimal(10,2) NULL,
    conversion_date datetime NULL,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY affiliate_id (affiliate_id),
    KEY promo_code_id (promo_code_id),
    KEY ip_address (ip_address),
    KEY cookie_id (cookie_id),
    KEY converted (converted),
    KEY created_at (created_at),
    FOREIGN KEY (affiliate_id) REFERENCES wp_flexpress_affiliates(id) ON DELETE CASCADE,
    FOREIGN KEY (promo_code_id) REFERENCES wp_flexpress_affiliate_promo_codes(id) ON DELETE SET NULL
);
```

#### **wp_flexpress_affiliate_transactions**
```sql
CREATE TABLE wp_flexpress_affiliate_transactions (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    affiliate_id bigint(20) NOT NULL,
    promo_code_id bigint(20) NULL,
    user_id bigint(20) NOT NULL,
    transaction_type enum('initial', 'rebill', 'unlock', 'refund') NOT NULL,
    transaction_id varchar(255) NOT NULL,
    flowguard_transaction_id varchar(255),
    plan_id varchar(50) NOT NULL,
    revenue_amount decimal(10,2) NOT NULL,
    commission_rate decimal(5,2) NOT NULL,
    commission_amount decimal(10,2) NOT NULL,
    commission_type enum('percentage', 'flat') NOT NULL DEFAULT 'percentage',
    status enum('pending', 'approved', 'paid', 'cancelled') NOT NULL DEFAULT 'pending',
    click_id bigint(20) NULL,
    notes text,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    approved_at datetime NULL,
    paid_at datetime NULL,
    PRIMARY KEY (id),
    KEY affiliate_id (affiliate_id),
    KEY promo_code_id (promo_code_id),
    KEY user_id (user_id),
    KEY transaction_type (transaction_type),
    KEY status (status),
    KEY transaction_id (transaction_id),
    KEY created_at (created_at),
    FOREIGN KEY (affiliate_id) REFERENCES wp_flexpress_affiliates(id) ON DELETE CASCADE,
    FOREIGN KEY (promo_code_id) REFERENCES wp_flexpress_affiliate_promo_codes(id) ON DELETE SET NULL,
    FOREIGN KEY (click_id) REFERENCES wp_flexpress_affiliate_clicks(id) ON DELETE SET NULL
);
```

#### **wp_flexpress_affiliate_payouts**
```sql
CREATE TABLE wp_flexpress_affiliate_payouts (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    affiliate_id bigint(20) NOT NULL,
    period_start date NOT NULL,
    period_end date NOT NULL,
    total_commissions decimal(10,2) NOT NULL,
    payout_amount decimal(10,2) NOT NULL,
    payout_method enum('paypal', 'bank_transfer', 'check', 'crypto') NOT NULL,
    payout_details text,
    status enum('pending', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    reference_id varchar(255),
    notes text,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed_at datetime NULL,
    PRIMARY KEY (id),
    KEY affiliate_id (affiliate_id),
    KEY period_end (period_end),
    KEY status (status),
    KEY created_at (created_at),
    FOREIGN KEY (affiliate_id) REFERENCES wp_flexpress_affiliates(id) ON DELETE CASCADE
);
```

---

## ⚙️ **Core Features Implementation**

### **1. Module Toggle System**

#### **Settings Integration**
```php
// Enhanced FlexPress_Affiliate_Settings class
class FlexPress_Affiliate_Settings {
    public function register_affiliate_settings() {
        register_setting('flexpress_affiliate_settings', 'flexpress_affiliate_settings', array(
            'sanitize_callback' => array($this, 'sanitize_affiliate_settings')
        ));

        // Module Toggle Section
        add_settings_section(
            'flexpress_affiliate_module_section',
            __('Module Control', 'flexpress'),
            array($this, 'render_module_section'),
            'flexpress_affiliate_settings'
        );

        add_settings_field(
            'affiliate_module_enabled',
            __('Enable Affiliate System', 'flexpress'),
            array($this, 'render_module_toggle_field'),
            'flexpress_affiliate_settings',
            'flexpress_affiliate_module_section'
        );
    }

    public function render_module_toggle_field() {
        $settings = get_option('flexpress_affiliate_settings', array());
        $enabled = !empty($settings['module_enabled']);
        ?>
        <label>
            <input type="checkbox" name="flexpress_affiliate_settings[module_enabled]" value="1" <?php checked($enabled); ?> />
            <?php esc_html_e('Enable the complete affiliate and promo-code management system', 'flexpress'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('When disabled, all affiliate hooks stop firing and promo codes fall back to normal pricing rules.', 'flexpress'); ?>
        </p>
        <?php
    }
}
```

#### **Hook Management**
```php
// Conditional hook registration
function flexpress_affiliate_init_hooks() {
    $settings = get_option('flexpress_affiliate_settings', array());
    
    if (!empty($settings['module_enabled'])) {
        // Register all affiliate hooks
        add_action('wp_ajax_nopriv_affiliate_application', 'flexpress_handle_affiliate_application');
        add_action('wp_ajax_affiliate_application', 'flexpress_handle_affiliate_application');
        add_action('wp_ajax_affiliate_dashboard_data', 'flexpress_get_affiliate_dashboard_data');
        
        // Flowguard webhook integration
        add_action('flexpress_flowguard_payment_completed', 'flexpress_affiliate_process_payment');
        add_action('flexpress_flowguard_payment_rebill', 'flexpress_affiliate_process_rebill');
        add_action('flexpress_flowguard_payment_refund', 'flexpress_affiliate_process_refund');
        
        // Tracking hooks
        add_action('wp_head', 'flexpress_affiliate_tracking_script');
        add_action('wp_footer', 'flexpress_affiliate_tracking_pixel');
    }
}
add_action('init', 'flexpress_affiliate_init_hooks');
```

### **2. Affiliate Sign-Up Flow**

#### **Application Form**
```php
// includes/affiliate-system/class-flexpress-affiliate-manager.php
class FlexPress_Affiliate_Manager {
    public function render_application_form() {
        ?>
        <form id="affiliate-application-form" class="affiliate-form">
            <div class="form-group">
                <label for="affiliate_name"><?php esc_html_e('Full Name', 'flexpress'); ?> *</label>
                <input type="text" id="affiliate_name" name="affiliate_name" required>
            </div>
            
            <div class="form-group">
                <label for="affiliate_email"><?php esc_html_e('Email Address', 'flexpress'); ?> *</label>
                <input type="email" id="affiliate_email" name="affiliate_email" required>
            </div>
            
            <div class="form-group">
                <label for="affiliate_website"><?php esc_html_e('Website/Social Media', 'flexpress'); ?></label>
                <input type="url" id="affiliate_website" name="affiliate_website" placeholder="https://yourwebsite.com">
            </div>
            
            <div class="form-group">
                <label for="payout_method"><?php esc_html_e('Preferred Payout Method', 'flexpress'); ?> *</label>
                <select id="payout_method" name="payout_method" required>
                    <option value="paypal"><?php esc_html_e('PayPal', 'flexpress'); ?></option>
                    <option value="bank_transfer"><?php esc_html_e('Bank Transfer', 'flexpress'); ?></option>
                    <option value="check"><?php esc_html_e('Check', 'flexpress'); ?></option>
                    <option value="crypto"><?php esc_html_e('Cryptocurrency', 'flexpress'); ?></option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="payout_details"><?php esc_html_e('Payout Details', 'flexpress'); ?></label>
                <textarea id="payout_details" name="payout_details" placeholder="PayPal email, bank details, etc."></textarea>
            </div>
            
            <div class="form-group">
                <label for="tax_info"><?php esc_html_e('Tax Information (Optional)', 'flexpress'); ?></label>
                <textarea id="tax_info" name="tax_info" placeholder="Tax ID, business registration, etc."></textarea>
            </div>
            
            <div class="form-group">
                <label for="marketing_experience"><?php esc_html_e('Marketing Experience', 'flexpress'); ?></label>
                <textarea id="marketing_experience" name="marketing_experience" placeholder="Describe your marketing experience and strategies"></textarea>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="terms_accepted" required>
                    <?php esc_html_e('I agree to the Affiliate Terms and Conditions', 'flexpress'); ?>
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <?php esc_html_e('Submit Application', 'flexpress'); ?>
            </button>
        </form>
        <?php
    }
}
```

#### **Application Processing**
```php
function flexpress_handle_affiliate_application() {
    check_ajax_referer('flexpress_affiliate_nonce', 'nonce');
    
    $data = array(
        'affiliate_name' => sanitize_text_field($_POST['affiliate_name'] ?? ''),
        'affiliate_email' => sanitize_email($_POST['affiliate_email'] ?? ''),
        'affiliate_website' => esc_url_raw($_POST['affiliate_website'] ?? ''),
        'payout_method' => sanitize_text_field($_POST['payout_method'] ?? 'paypal'),
        'payout_details' => sanitize_textarea_field($_POST['payout_details'] ?? ''),
        'tax_info' => sanitize_textarea_field($_POST['tax_info'] ?? ''),
        'marketing_experience' => sanitize_textarea_field($_POST['marketing_experience'] ?? ''),
        'terms_accepted' => !empty($_POST['terms_accepted']),
        'ip_address' => flexpress_get_client_ip(),
        'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
        'submitted_at' => current_time('mysql')
    );
    
    // Validation
    if (empty($data['affiliate_name']) || empty($data['affiliate_email'])) {
        wp_send_json_error(['message' => __('Name and email are required.', 'flexpress')]);
    }
    
    if (!is_email($data['affiliate_email'])) {
        wp_send_json_error(['message' => __('Please enter a valid email address.', 'flexpress')]);
    }
    
    if (!$data['terms_accepted']) {
        wp_send_json_error(['message' => __('You must accept the terms and conditions.', 'flexpress')]);
    }
    
    // Check for existing application
    global $wpdb;
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}flexpress_affiliates WHERE email = %s",
        $data['affiliate_email']
    ));
    
    if ($existing) {
        wp_send_json_error(['message' => __('An application with this email already exists.', 'flexpress')]);
    }
    
    // Generate unique affiliate code
    $affiliate_code = flexpress_generate_affiliate_code();
    
    // Insert application
    $result = $wpdb->insert(
        $wpdb->prefix . 'flexpress_affiliates',
        array(
            'affiliate_code' => $affiliate_code,
            'display_name' => $data['affiliate_name'],
            'email' => $data['affiliate_email'],
            'website' => $data['affiliate_website'],
            'payout_method' => $data['payout_method'],
            'payout_details' => $data['payout_details'],
            'tax_info' => $data['tax_info'],
            'status' => 'pending',
            'application_data' => json_encode($data),
            'created_at' => current_time('mysql')
        ),
        array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
    );
    
    if ($result) {
        // Send notification email to admin
        flexpress_send_affiliate_application_notification($data);
        
        wp_send_json_success(['message' => __('Application submitted successfully! We will review it and get back to you.', 'flexpress')]);
    } else {
        wp_send_json_error(['message' => __('Failed to submit application. Please try again.', 'flexpress')]);
    }
}
```

### **3. Affiliate Dashboard**

#### **Dashboard Interface**
```php
// includes/affiliate-system/class-flexpress-affiliate-dashboard.php
class FlexPress_Affiliate_Dashboard {
    public function render_dashboard() {
        $affiliate = $this->get_current_affiliate();
        
        if (!$affiliate) {
            echo '<p>' . esc_html__('You are not an approved affiliate.', 'flexpress') . '</p>';
            return;
        }
        
        ?>
        <div class="affiliate-dashboard">
            <div class="dashboard-header">
                <h2><?php esc_html_e('Affiliate Dashboard', 'flexpress'); ?></h2>
                <p><?php printf(esc_html__('Welcome, %s', 'flexpress'), esc_html($affiliate->display_name)); ?></p>
            </div>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <h3><?php esc_html_e('Total Clicks', 'flexpress'); ?></h3>
                    <div class="stat-number"><?php echo number_format($affiliate->total_clicks); ?></div>
                </div>
                
                <div class="stat-card">
                    <h3><?php esc_html_e('Total Signups', 'flexpress'); ?></h3>
                    <div class="stat-number"><?php echo number_format($affiliate->total_signups); ?></div>
                </div>
                
                <div class="stat-card">
                    <h3><?php esc_html_e('Total Revenue', 'flexpress'); ?></h3>
                    <div class="stat-number">$<?php echo number_format($affiliate->total_revenue, 2); ?></div>
                </div>
                
                <div class="stat-card">
                    <h3><?php esc_html_e('Pending Commission', 'flexpress'); ?></h3>
                    <div class="stat-number">$<?php echo number_format($affiliate->pending_commission, 2); ?></div>
                </div>
            </div>
            
            <div class="dashboard-sections">
                <div class="section">
                    <h3><?php esc_html_e('Your Referral Link', 'flexpress'); ?></h3>
                    <div class="referral-link">
                        <input type="text" value="<?php echo esc_attr($affiliate->referral_url); ?>" readonly>
                        <button class="copy-link"><?php esc_html_e('Copy', 'flexpress'); ?></button>
                    </div>
                </div>
                
                <div class="section">
                    <h3><?php esc_html_e('Assigned Promo Codes', 'flexpress'); ?></h3>
                    <div class="promo-codes-list">
                        <?php $this->render_assigned_promo_codes($affiliate->id); ?>
                    </div>
                </div>
                
                <div class="section">
                    <h3><?php esc_html_e('Recent Activity', 'flexpress'); ?></h3>
                    <div class="activity-list">
                        <?php $this->render_recent_activity($affiliate->id); ?>
                    </div>
                </div>
                
                <div class="section">
                    <h3><?php esc_html_e('Payout History', 'flexpress'); ?></h3>
                    <div class="payout-history">
                        <?php $this->render_payout_history($affiliate->id); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
```

### **4. Promo Code Management**

#### **Admin Promo Code Creation**
```php
function flexpress_create_promo_code() {
    check_ajax_referer('flexpress_affiliate_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Permission denied.', 'flexpress')]);
    }
    
    $data = array(
        'code' => sanitize_text_field($_POST['code'] ?? ''),
        'affiliate_id' => intval($_POST['affiliate_id'] ?? 0),
        'display_name' => sanitize_text_field($_POST['display_name'] ?? ''),
        'custom_pricing_json' => sanitize_textarea_field($_POST['custom_pricing_json'] ?? ''),
        'status' => sanitize_text_field($_POST['status'] ?? 'active'),
        'usage_limit' => intval($_POST['usage_limit'] ?? 0),
        'valid_from' => sanitize_text_field($_POST['valid_from'] ?? ''),
        'valid_until' => sanitize_text_field($_POST['valid_until'] ?? '')
    );
    
    // Validation
    if (empty($data['code']) || empty($data['display_name'])) {
        wp_send_json_error(['message' => __('Code and display name are required.', 'flexpress')]);
    }
    
    // Check if code already exists
    global $wpdb;
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}flexpress_affiliate_promo_codes WHERE code = %s",
        $data['code']
    ));
    
    if ($existing) {
        wp_send_json_error(['message' => __('This promo code already exists.', 'flexpress')]);
    }
    
    // Insert promo code
    $result = $wpdb->insert(
        $wpdb->prefix . 'flexpress_affiliate_promo_codes',
        $data,
        array('%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s')
    );
    
    if ($result) {
        wp_send_json_success(['message' => __('Promo code created successfully.', 'flexpress')]);
    } else {
        wp_send_json_error(['message' => __('Failed to create promo code.', 'flexpress')]);
    }
}
```

### **5. Tracking System**

#### **Click Tracking**
```php
// includes/affiliate-system/class-flexpress-affiliate-tracker.php
class FlexPress_Affiliate_Tracker {
    public function track_click($affiliate_id, $promo_code_id = null) {
        $cookie_id = $this->get_or_create_cookie_id();
        $ip_address = flexpress_get_client_ip();
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
            // Update affiliate click count
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}flexpress_affiliates SET total_clicks = total_clicks + 1 WHERE id = %d",
                $affiliate_id
            ));
            
            // Set tracking cookie
            $this->set_tracking_cookie($affiliate_id, $promo_code_id, $cookie_id);
        }
        
        return $result;
    }
    
    private function get_or_create_cookie_id() {
        if (isset($_COOKIE['flexpress_affiliate_tracking'])) {
            return sanitize_text_field($_COOKIE['flexpress_affiliate_tracking']);
        }
        
        return 'aff_' . wp_generate_uuid4();
    }
    
    private function set_tracking_cookie($affiliate_id, $promo_code_id, $cookie_id) {
        $cookie_data = array(
            'affiliate_id' => $affiliate_id,
            'promo_code_id' => $promo_code_id,
            'cookie_id' => $cookie_id,
            'timestamp' => time()
        );
        
        setcookie(
            'flexpress_affiliate_tracking',
            base64_encode(json_encode($cookie_data)),
            time() + (30 * DAY_IN_SECONDS), // 30 days
            COOKIEPATH,
            COOKIE_DOMAIN,
            is_ssl(),
            true
        );
    }
}
```

### **6. Flowguard Integration**

#### **Payment Event Processing**
```php
// Enhanced flowguard-webhook-handler.php
function flexpress_affiliate_process_payment($payload) {
    $settings = get_option('flexpress_affiliate_settings', array());
    
    if (empty($settings['module_enabled'])) {
        return; // Module disabled
    }
    
    $transaction_id = $payload['transactionId'] ?? '';
    $user_id = flexpress_flowguard_get_user_from_reference($payload['referenceId'] ?? '');
    $amount = floatval($payload['amount'] ?? 0);
    $order_type = $payload['orderType'] ?? '';
    
    // Get tracking data from cookie
    $tracking_data = flexpress_get_tracking_data_from_cookie();
    
    if (!$tracking_data) {
        return; // No affiliate tracking
    }
    
    $affiliate_id = $tracking_data['affiliate_id'];
    $promo_code_id = $tracking_data['promo_code_id'];
    
    // Get affiliate data
    global $wpdb;
    $affiliate = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}flexpress_affiliates WHERE id = %d",
        $affiliate_id
    ));
    
    if (!$affiliate) {
        return; // Invalid affiliate
    }
    
    // Determine transaction type and commission rate
    $transaction_type = 'initial';
    $commission_rate = $affiliate->commission_initial;
    
    if ($order_type === 'subscription') {
        // Check if this is a rebill
        $existing_transaction = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}flexpress_affiliate_transactions 
             WHERE user_id = %d AND transaction_type = 'initial'",
            $user_id
        ));
        
        if ($existing_transaction) {
            $transaction_type = 'rebill';
            $commission_rate = $affiliate->commission_rebill;
        }
    }
    
    // Calculate commission
    $commission_amount = ($amount * $commission_rate) / 100;
    
    // Insert transaction record
    $wpdb->insert(
        $wpdb->prefix . 'flexpress_affiliate_transactions',
        array(
            'affiliate_id' => $affiliate_id,
            'promo_code_id' => $promo_code_id,
            'user_id' => $user_id,
            'transaction_type' => $transaction_type,
            'transaction_id' => $transaction_id,
            'flowguard_transaction_id' => $transaction_id,
            'plan_id' => $payload['planId'] ?? '',
            'revenue_amount' => $amount,
            'commission_rate' => $commission_rate,
            'commission_amount' => $commission_amount,
            'status' => 'pending',
            'created_at' => current_time('mysql')
        ),
        array('%d', '%d', '%d', '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%s', '%s')
    );
    
    // Update affiliate stats
    $wpdb->query($wpdb->prepare(
        "UPDATE {$wpdb->prefix}flexpress_affiliates SET 
         total_revenue = total_revenue + %f,
         pending_commission = pending_commission + %f,
         total_signups = total_signups + 1
         WHERE id = %d",
        $amount,
        $commission_amount,
        $affiliate_id
    ));
    
    // Mark click as converted
    if ($tracking_data['click_id']) {
        $wpdb->update(
            $wpdb->prefix . 'flexpress_affiliate_clicks',
            array(
                'converted' => 1,
                'conversion_type' => $transaction_type,
                'conversion_value' => $amount,
                'conversion_date' => current_time('mysql')
            ),
            array('id' => $tracking_data['click_id']),
            array('%d', '%s', '%f', '%s'),
            array('%d')
        );
    }
}
```

### **7. Payout System**

#### **Payout Processing**
```php
function flexpress_process_affiliate_payout($affiliate_id, $payout_data) {
    global $wpdb;
    
    // Get affiliate data
    $affiliate = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}flexpress_affiliates WHERE id = %d",
        $affiliate_id
    ));
    
    if (!$affiliate || $affiliate->approved_commission < $affiliate->payout_threshold) {
        return false; // Not eligible for payout
    }
    
    // Create payout record
    $result = $wpdb->insert(
        $wpdb->prefix . 'flexpress_affiliate_payouts',
        array(
            'affiliate_id' => $affiliate_id,
            'period_start' => $payout_data['period_start'],
            'period_end' => $payout_data['period_end'],
            'total_commissions' => $affiliate->approved_commission,
            'payout_amount' => $affiliate->approved_commission,
            'payout_method' => $affiliate->payout_method,
            'payout_details' => $affiliate->payout_details,
            'status' => 'pending',
            'created_at' => current_time('mysql')
        ),
        array('%d', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%s')
    );
    
    if ($result) {
        // Update affiliate balances
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}flexpress_affiliates SET 
             paid_commission = paid_commission + %f,
             approved_commission = 0
             WHERE id = %d",
            $affiliate->approved_commission,
            $affiliate_id
        ));
        
        // Mark transactions as paid
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}flexpress_affiliate_transactions SET 
             status = 'paid',
             paid_at = %s
             WHERE affiliate_id = %d AND status = 'approved'",
            current_time('mysql'),
            $affiliate_id
        ));
        
        return true;
    }
    
    return false;
}
```

---

## 🔧 **Implementation Steps**

### **Phase 1: Database Setup**
1. Create database schema functions
2. Integrate with theme activation hook
3. Add database versioning system

### **Phase 2: Core Classes**
1. Implement `FlexPress_Affiliate_Manager`
2. Implement `FlexPress_Affiliate_Tracker`
3. Implement `FlexPress_Affiliate_Dashboard`

### **Phase 3: Admin Interface**
1. Enhance existing affiliate settings page
2. Add affiliate management interface
3. Add promo code management interface
4. Add transaction and payout management

### **Phase 4: Frontend Integration**
1. Create affiliate application page template
2. Create affiliate dashboard page template
3. Implement shortcodes for forms and dashboards
4. Add tracking scripts and cookies

### **Phase 5: Flowguard Integration**
1. Enhance webhook handler for affiliate processing
2. Add commission calculation logic
3. Implement transaction tracking
4. Add refund/chargeback handling

### **Phase 6: Testing & Optimization**
1. Test all affiliate flows
2. Test promo code functionality
3. Test payout processing
4. Performance optimization
5. Security audit

---

## 🛡️ **Security Considerations**

### **Data Protection**
- Sanitize all user input
- Hash sensitive data
- Use prepared statements for database queries
- Implement CSRF protection
- Validate file uploads

### **Access Control**
- Role-based permissions
- Capability checks for admin functions
- Secure cookie handling
- IP address logging
- Rate limiting for applications

### **Privacy Compliance**
- GDPR cookie notices
- Data retention policies
- User data export/deletion
- Consent management
- Privacy policy updates

---

## 📈 **Future Enhancements**

### **Advanced Features**
- REST API endpoints for affiliate stats
- CSV export functionality
- Automated payout processing
- Tiered commission levels
- A/B testing for promo codes
- Advanced analytics dashboard
- Mobile app integration
- Multi-language support

### **Integration Opportunities**
- PayPal MassPay API
- Cryptocurrency payment processing
- Email marketing automation
- Social media integration
- Advanced reporting tools
- Machine learning for optimization

---

## 📋 **Deliverables Checklist**

- [ ] Database schema implementation
- [ ] Core affiliate management classes
- [ ] Admin interface enhancements
- [ ] Frontend page templates
- [ ] Shortcode implementations
- [ ] Flowguard webhook integration
- [ ] Tracking and analytics system
- [ ] Payout processing system
- [ ] Security implementations
- [ ] Documentation and testing
- [ ] Performance optimization
- [ ] User acceptance testing

This comprehensive implementation plan provides a complete roadmap for building a professional-grade affiliate and promo-code management system that integrates seamlessly with the existing FlexPress theme architecture.
