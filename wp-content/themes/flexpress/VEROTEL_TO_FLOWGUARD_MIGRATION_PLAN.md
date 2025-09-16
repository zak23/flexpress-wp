# 🔄 Verotel to Flowguard Migration Plan

## 📋 **Migration Overview**

This document outlines the complete migration from Verotel FlexPay to Flowguard payment processing system for the FlexPress WordPress theme. The migration will replace all Verotel functionality with Flowguard's modern API and enhanced security features.

## 🎯 **Migration Goals**

- **Replace Verotel FlexPay** with Flowguard payment processing
- **Maintain all existing functionality** (subscriptions, PPV, webhooks, admin)
- **Improve security** with JWT-based authentication and better webhook validation
- **Enhance user experience** with embedded payment forms (no redirects)
- **Preserve user data** and transaction history
- **Zero downtime** migration with fallback capabilities

## 📊 **Current Verotel Implementation Analysis**

### **Core Files to Replace**
```
wp-content/themes/flexpress/
├── includes/
│   ├── verotel-integration.php                    # Main integration functions
│   ├── class-flexpress-verotel.php               # Verotel client class
│   ├── admin/
│   │   ├── class-flexpress-verotel-settings.php  # Admin settings
│   │   ├── class-flexpress-verotel-diagnostics.php # Diagnostics
│   │   └── class-flexpress-verotel-orphaned-webhooks.php # Orphaned webhooks
│   └── verotel/                                   # Verotel SDK (entire directory)
├── page-templates/
│   ├── join.php                                   # Signup page (uses Verotel)
│   └── dashboard.php                              # Dashboard (Verotel references)
├── assets/js/
│   └── verotel.js                                 # Frontend JavaScript
└── functions.php                                  # Verotel hooks and functions
```

### **Database Tables**
- `wp_flexpress_verotel_webhooks` - Webhook logs
- `wp_flexpress_verotel_transactions` - Transaction records
- User meta fields: `verotel_*`, `membership_status`, `subscription_*`

### **Current Verotel Features**
- ✅ Subscription management (recurring/one-time)
- ✅ PPV (Pay-Per-View) purchases
- ✅ Webhook processing (initial, rebill, cancel, chargeback)
- ✅ Admin settings and diagnostics
- ✅ User dashboard integration
- ✅ Affiliate/promo code tracking
- ✅ Activity logging

## 🚀 **Flowguard Implementation Status**

### **Already Implemented** ✅
- ✅ Flowguard API client (`class-flexpress-flowguard-api.php`)
- ✅ Integration functions (`flowguard-integration.php`)
- ✅ Webhook handler (`flowguard-webhook-handler.php`)
- ✅ Database management (`flowguard-database.php`)
- ✅ Admin settings (`class-flexpress-flowguard-settings.php`)
- ✅ Payment page templates (`flowguard-payment.php`, `payment-success.php`, `payment-declined.php`)
- ✅ Frontend JavaScript (`flowguard.js`)
- ✅ Registration page (`register-flowguard.php`)
- ✅ Join page (`join-flowguard.php`)

### **Configuration**
- **Shop ID**: 134837 (from ControlCenter)
- **Signature Key**: QdqSpfTHzKKQChBB26xDcEAh3wkQtZ (from ControlCenter)
- **Environment**: Sandbox for testing, Production for live
- **Webhook URL**: `/wp-admin/admin-ajax.php?action=flowguard_webhook`

## 📋 **Migration Steps**

### **Phase 1: Preparation & Testing** 🧪

#### **1.1 Backup Current System**
```bash
# Backup Verotel settings
wp option get flexpress_verotel_settings > verotel_settings_backup.json

# Backup user meta data
wp db export --tables=wp_usermeta --where="meta_key LIKE 'verotel_%' OR meta_key LIKE 'membership_%'" verotel_usermeta_backup.sql

# Backup webhook logs
wp db export --tables=wp_flexpress_verotel_webhooks verotel_webhooks_backup.sql
```

#### **1.2 Test Flowguard Integration**
- [ ] Verify API connectivity
- [ ] Test subscription creation
- [ ] Test PPV purchase flow
- [ ] Validate webhook processing
- [ ] Test admin interface

#### **1.3 Create Migration Scripts**
- [ ] User data migration script
- [ ] Transaction history migration
- [ ] Settings migration script

### **Phase 2: Core System Replacement** 🔄

#### **2.1 Update Functions.php**
```php
// Replace Verotel hooks with Flowguard
// OLD: add_action('wp_ajax_verotel_webhook', 'flexpress_handle_verotel_webhook');
// NEW: add_action('wp_ajax_flowguard_webhook', 'flexpress_flowguard_webhook_handler');

// Update payment creation functions
// OLD: flexpress_create_verotel_subscription()
// NEW: flexpress_flowguard_create_subscription()

// Update PPV functions
// OLD: flexpress_create_verotel_ppv_purchase()
// NEW: flexpress_flowguard_create_ppv_purchase()
```

#### **2.2 Update Page Templates**

**Join Page (`join.php`)**
```php
// Replace Verotel client initialization
// OLD: $verotel = new FlexPress_Verotel();
// NEW: $flowguard_api = flexpress_get_flowguard_api();

// Update payment URL generation
// OLD: $payment_url = $verotel->get_subscription_url(...)
// NEW: $result = flexpress_flowguard_create_subscription($user_id, $plan_id);
//      $payment_url = $result['payment_url'];
```

**Dashboard (`dashboard.php`)**
```php
// Update subscription status checks
// OLD: flexpress_get_subscription_details() (Verotel-based)
// NEW: flexpress_flowguard_get_subscription_status()

// Update cancellation handling
// OLD: flexpress_cancel_verotel_subscription()
// NEW: flexpress_flowguard_cancel_subscription()
```

#### **2.3 Update Admin Interface**
- [ ] Replace Verotel settings page with Flowguard settings
- [ ] Update diagnostics to show Flowguard status
- [ ] Migrate webhook monitoring to Flowguard webhooks

#### **2.4 Update Frontend JavaScript**
```javascript
// Replace verotel.js with flowguard.js
// Update AJAX calls to use Flowguard endpoints
// Update payment form initialization
```

### **Phase 3: Data Migration** 📊

#### **3.1 User Meta Migration**
```sql
-- Migrate Verotel user meta to Flowguard format
UPDATE wp_usermeta 
SET meta_key = 'flowguard_sale_id' 
WHERE meta_key = 'verotel_sale_id';

UPDATE wp_usermeta 
SET meta_key = 'flowguard_transaction_id' 
WHERE meta_key = 'verotel_transaction_id';

-- Keep membership_status and subscription_* fields (compatible)
```

#### **3.2 Transaction History Migration**
```sql
-- Create Flowguard transactions table
CREATE TABLE wp_flexpress_flowguard_transactions (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) NOT NULL,
    transaction_id varchar(255) NOT NULL,
    session_id varchar(255) NOT NULL,
    sale_id varchar(255),
    amount decimal(10,2) NOT NULL,
    currency varchar(3) NOT NULL,
    status varchar(50) NOT NULL,
    order_type varchar(50) NOT NULL,
    reference_id varchar(255),
    created_at datetime NOT NULL,
    updated_at datetime NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY transaction_id (transaction_id),
    KEY user_id (user_id),
    KEY status (status)
);

-- Migrate existing transaction data
INSERT INTO wp_flexpress_flowguard_transactions 
SELECT 
    user_id,
    transaction_id,
    CONCAT('migrated_', transaction_id) as session_id,
    sale_id,
    amount,
    currency,
    status,
    'subscription' as order_type,
    CONCAT('user_', user_id, '_migrated') as reference_id,
    created_at,
    updated_at
FROM wp_flexpress_verotel_transactions;
```

#### **3.3 Webhook Log Migration**
```sql
-- Migrate webhook logs for reference
INSERT INTO wp_flexpress_flowguard_webhooks 
SELECT 
    webhook_id,
    event_type,
    transaction_id,
    user_id,
    payload,
    processed,
    created_at
FROM wp_flexpress_verotel_webhooks
WHERE processed = 1;
```

### **Phase 4: Testing & Validation** ✅

#### **4.1 Functional Testing**
- [ ] Test new user registration with Flowguard
- [ ] Test subscription creation and management
- [ ] Test PPV purchase flow
- [ ] Test webhook processing
- [ ] Test admin interface functionality
- [ ] Test user dashboard features

#### **4.2 Data Integrity Testing**
- [ ] Verify user membership statuses are preserved
- [ ] Verify transaction history is accessible
- [ ] Verify affiliate tracking continues to work
- [ ] Verify activity logs are maintained

#### **4.3 Performance Testing**
- [ ] Test payment processing speed
- [ ] Test webhook response times
- [ ] Test admin interface responsiveness

### **Phase 5: Deployment & Cleanup** 🚀

#### **5.1 Production Deployment**
- [ ] Deploy Flowguard configuration
- [ ] Update webhook URLs in ControlCenter
- [ ] Test live payment processing
- [ ] Monitor webhook delivery

#### **5.2 Verotel Cleanup**
- [ ] Remove Verotel files (after confirming Flowguard works)
- [ ] Clean up Verotel database tables (optional - keep for reference)
- [ ] Remove Verotel settings from admin
- [ ] Update documentation

## 🔧 **Migration Scripts**

### **Migration Script: User Data**
```php
<?php
/**
 * Migrate user data from Verotel to Flowguard
 */
function flexpress_migrate_verotel_to_flowguard() {
    global $wpdb;
    
    // Get all users with Verotel data
    $users = $wpdb->get_results("
        SELECT user_id, meta_key, meta_value 
        FROM {$wpdb->usermeta} 
        WHERE meta_key LIKE 'verotel_%' 
        OR meta_key IN ('membership_status', 'subscription_type', 'subscription_start_date', 'next_rebill_date')
    ");
    
    $migration_log = [];
    
    foreach ($users as $user_meta) {
        $user_id = $user_meta->user_id;
        $meta_key = $user_meta->meta_key;
        $meta_value = $user_meta->meta_value;
        
        // Map Verotel meta keys to Flowguard equivalents
        $mapping = [
            'verotel_sale_id' => 'flowguard_sale_id',
            'verotel_transaction_id' => 'flowguard_transaction_id',
            'verotel_shop_id' => 'flowguard_shop_id',
            'verotel_customer_id' => 'flowguard_customer_id'
        ];
        
        if (isset($mapping[$meta_key])) {
            $new_key = $mapping[$meta_key];
            update_user_meta($user_id, $new_key, $meta_value);
            $migration_log[] = "Migrated {$meta_key} to {$new_key} for user {$user_id}";
        }
    }
    
    return $migration_log;
}
```

### **Migration Script: Settings**
```php
<?php
/**
 * Migrate Verotel settings to Flowguard
 */
function flexpress_migrate_verotel_settings() {
    $verotel_settings = get_option('flexpress_verotel_settings', []);
    
    if (empty($verotel_settings)) {
        return false;
    }
    
    // Map Verotel settings to Flowguard format
    $flowguard_settings = [
        'shop_id' => $verotel_settings['verotel_shop_id'] ?? '134837',
        'signature_key' => $verotel_settings['verotel_signature_key'] ?? 'QdqSpfTHzKKQChBB26xDcEAh3wkQtZ',
        'environment' => 'production', // Switch to production for live site
        'webhook_url' => home_url('/wp-admin/admin-ajax.php?action=flowguard_webhook')
    ];
    
    update_option('flexpress_flowguard_settings', $flowguard_settings);
    
    return $flowguard_settings;
}
```

## 🚨 **Risk Mitigation**

### **Rollback Plan**
1. **Keep Verotel files** until Flowguard is fully tested
2. **Maintain Verotel settings** as backup
3. **Preserve original user meta** (don't delete, just add new)
4. **Test rollback procedure** before migration

### **Monitoring**
- [ ] Monitor webhook delivery rates
- [ ] Monitor payment success rates
- [ ] Monitor user complaints
- [ ] Monitor admin interface functionality

### **Fallback Procedures**
- [ ] Quick rollback to Verotel if critical issues
- [ ] Manual payment processing if API fails
- [ ] Customer support escalation procedures

## 📈 **Expected Benefits**

### **Security Improvements**
- ✅ JWT-based authentication (more secure than signature validation)
- ✅ Better webhook validation
- ✅ PCI DSS compliance
- ✅ 3D Secure support

### **User Experience Improvements**
- ✅ Embedded payment forms (no redirects)
- ✅ Faster payment processing
- ✅ Better error handling
- ✅ Mobile-optimized payment forms

### **Admin Improvements**
- ✅ Better webhook monitoring
- ✅ Enhanced transaction tracking
- ✅ Improved diagnostics
- ✅ Real-time payment status updates

## 📝 **Post-Migration Checklist**

### **Immediate (Day 1)**
- [ ] Verify all payments are processing correctly
- [ ] Check webhook delivery rates
- [ ] Monitor error logs
- [ ] Test admin interface functionality

### **Short-term (Week 1)**
- [ ] Monitor user feedback
- [ ] Check transaction reconciliation
- [ ] Verify affiliate tracking
- [ ] Test subscription renewals

### **Long-term (Month 1)**
- [ ] Analyze payment success rates
- [ ] Review webhook reliability
- [ ] Optimize payment flows
- [ ] Plan Verotel cleanup

## 🔗 **Related Documentation**

- [Flowguard API Implementation Guide](FLOWGUARD_API_IMPLEMENTATION.md)
- [Flowguard Integration Success](FLOWGUARD_INTEGRATION_SUCCESS.md)
- [Flowguard Implementation Plan](FLOWGUARD_IMPLEMENTATION_PLAN.md)

## 📞 **Support Contacts**

- **Flowguard Support**: Available through ControlCenter
- **Technical Issues**: Check webhook logs and error logs
- **Payment Issues**: Verify API credentials and webhook URLs

---

**Migration Status**: Ready to Begin
**Estimated Duration**: 2-3 days (including testing)
**Risk Level**: Medium (with proper rollback plan)
**Dependencies**: Flowguard ControlCenter access, API credentials
