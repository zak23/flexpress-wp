<?php
/**
 * FlexPress Affiliate Database Schema
 * 
 * Creates and manages database tables for the affiliate system.
 * 
 * @package FlexPress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create affiliate system database tables
 */
function flexpress_affiliate_create_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Create affiliates table
    $affiliates_table = $wpdb->prefix . 'flexpress_affiliates';
    $sql_affiliates = "CREATE TABLE $affiliates_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        affiliate_code varchar(50) NOT NULL UNIQUE,
        display_name varchar(100) NOT NULL,
        email varchar(100) NOT NULL,
        website varchar(255),
        social_media text,
        payout_method enum('paypal', 'crypto', 'aus_bank_transfer', 'yoursafe', 'ach', 'swift') NOT NULL DEFAULT 'paypal',
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
    ) $charset_collate;";
    
    // Create affiliate promo codes table
    $promo_codes_table = $wpdb->prefix . 'flexpress_affiliate_promo_codes';
    $sql_promo_codes = "CREATE TABLE $promo_codes_table (
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
        FOREIGN KEY (affiliate_id) REFERENCES {$affiliates_table}(id) ON DELETE SET NULL
    ) $charset_collate;";
    
    // Create affiliate clicks table
    $clicks_table = $wpdb->prefix . 'flexpress_affiliate_clicks';
    $sql_clicks = "CREATE TABLE $clicks_table (
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
        FOREIGN KEY (affiliate_id) REFERENCES {$affiliates_table}(id) ON DELETE CASCADE,
        FOREIGN KEY (promo_code_id) REFERENCES {$promo_codes_table}(id) ON DELETE SET NULL
    ) $charset_collate;";
    
    // Create affiliate transactions table
    $transactions_table = $wpdb->prefix . 'flexpress_affiliate_transactions';
    $sql_transactions = "CREATE TABLE $transactions_table (
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
        FOREIGN KEY (affiliate_id) REFERENCES {$affiliates_table}(id) ON DELETE CASCADE,
        FOREIGN KEY (promo_code_id) REFERENCES {$promo_codes_table}(id) ON DELETE SET NULL,
        FOREIGN KEY (click_id) REFERENCES {$clicks_table}(id) ON DELETE SET NULL
    ) $charset_collate;";
    
    // Create affiliate payouts table
    $payouts_table = $wpdb->prefix . 'flexpress_affiliate_payouts';
    $sql_payouts = "CREATE TABLE $payouts_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        affiliate_id bigint(20) NOT NULL,
        period_start date NOT NULL,
        period_end date NOT NULL,
        total_commissions decimal(10,2) NOT NULL,
        payout_amount decimal(10,2) NOT NULL,
        payout_method enum('paypal', 'crypto', 'aus_bank_transfer', 'yoursafe', 'ach', 'swift') NOT NULL,
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
        FOREIGN KEY (affiliate_id) REFERENCES {$affiliates_table}(id) ON DELETE CASCADE
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    dbDelta($sql_affiliates);
    dbDelta($sql_promo_codes);
    dbDelta($sql_clicks);
    dbDelta($sql_transactions);
    dbDelta($sql_payouts);
    
    // Store database version
    update_option('flexpress_affiliate_db_version', '1.0.0');
    
    return true;
}

/**
 * Drop affiliate system database tables
 */
function flexpress_affiliate_drop_tables() {
    global $wpdb;
    
    $tables = [
        $wpdb->prefix . 'flexpress_affiliate_payouts',
        $wpdb->prefix . 'flexpress_affiliate_transactions',
        $wpdb->prefix . 'flexpress_affiliate_clicks',
        $wpdb->prefix . 'flexpress_affiliate_promo_codes',
        $wpdb->prefix . 'flexpress_affiliates'
    ];
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }
    
    delete_option('flexpress_affiliate_db_version');
    
    return true;
}

/**
 * Check if affiliate tables exist
 * 
 * @return bool True if tables exist
 */
function flexpress_affiliate_tables_exist() {
    global $wpdb;
    
    $tables = [
        $wpdb->prefix . 'flexpress_affiliates',
        $wpdb->prefix . 'flexpress_affiliate_promo_codes',
        $wpdb->prefix . 'flexpress_affiliate_clicks',
        $wpdb->prefix . 'flexpress_affiliate_transactions',
        $wpdb->prefix . 'flexpress_affiliate_payouts'
    ];
    
    foreach ($tables as $table) {
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        if (!$exists) {
            return false;
        }
    }
    
    return true;
}

/**
 * Get affiliate database version
 * 
 * @return string Database version
 */
function flexpress_affiliate_get_db_version() {
    return get_option('flexpress_affiliate_db_version', '0.0.0');
}

/**
 * Update affiliate database if needed
 */
function flexpress_affiliate_update_database() {
    $current_version = flexpress_affiliate_get_db_version();
    $target_version = '1.0.0';
    
    if (version_compare($current_version, $target_version, '<')) {
        flexpress_affiliate_create_tables();
    }
}

/**
 * Initialize affiliate database on theme activation
 */
function flexpress_affiliate_init_database() {
    if (!flexpress_affiliate_tables_exist()) {
        flexpress_affiliate_create_tables();
    } else {
        flexpress_affiliate_update_database();
    }
}
