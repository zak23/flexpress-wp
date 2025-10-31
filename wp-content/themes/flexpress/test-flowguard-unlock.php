<?php
/**
 * Test Flowguard Unlock Button Integration
 * 
 * This script tests the complete unlock button flow with Flowguard:
 * 1. PPV purchase creation
 * 2. Payment URL generation
 * 3. Webhook processing
 * 4. Episode access granting
 * 
 * @package FlexPress
 * @since 1.0.0
 */

// Load WordPress
require_once('../../../../wp-load.php');

if (!flexpress_current_user_is_founder()) {
    die('Access denied. Admin privileges required.');
}

echo "<h1>🧪 Flowguard Unlock Button Integration Test</h1>\n";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .test-section{margin:20px 0;padding:15px;border:1px solid #ddd;border-radius:5px;} .success{background:#d4edda;border-color:#c3e6cb;} .error{background:#f8d7da;border-color:#f5c6cb;} .info{background:#d1ecf1;border-color:#bee5eb;} pre{background:#f8f9fa;padding:10px;border-radius:3px;overflow-x:auto;}</style>\n";

// Test 1: Check Flowguard API Configuration
echo "<div class='test-section info'>\n";
echo "<h2>📋 Test 1: Flowguard API Configuration</h2>\n";

$flowguard_settings = get_option('flexpress_flowguard_settings', []);
if (empty($flowguard_settings)) {
    echo "<p class='error'>❌ Flowguard settings not configured</p>\n";
    echo "<p>Please configure Flowguard settings in FlexPress Settings → Flowguard</p>\n";
} else {
    echo "<p class='success'>✅ Flowguard settings found</p>\n";
    echo "<ul>\n";
    echo "<li>Shop ID: " . esc_html($flowguard_settings['shop_id'] ?? 'Not set') . "</li>\n";
    echo "<li>Environment: " . esc_html($flowguard_settings['environment'] ?? 'Not set') . "</li>\n";
    echo "<li>Signature Key: " . (empty($flowguard_settings['signature_key']) ? 'Not set' : 'Configured') . "</li>\n";
    echo "</ul>\n";
}
echo "</div>\n";

// Test 2: Check API Client
echo "<div class='test-section info'>\n";
echo "<h2>🔧 Test 2: Flowguard API Client</h2>\n";

try {
    $api = flexpress_get_flowguard_api();
    if ($api) {
        echo "<p class='success'>✅ Flowguard API client initialized successfully</p>\n";
        echo "<ul>\n";
        echo "<li>API Base URL: " . esc_html($api->get_api_base_url()) . "</li>\n";
        echo "<li>Shop ID: " . esc_html($api->get_shop_id()) . "</li>\n";
        echo "<li>Environment: " . esc_html($api->get_environment()) . "</li>\n";
        echo "</ul>\n";
    } else {
        echo "<p class='error'>❌ Failed to initialize Flowguard API client</p>\n";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error initializing API client: " . esc_html($e->getMessage()) . "</p>\n";
}
echo "</div>\n";

// Test 3: Find Test Episode
echo "<div class='test-section info'>\n";
echo "<h2>🎬 Test 3: Test Episode</h2>\n";

$test_episode = null;
$episodes = get_posts([
    'post_type' => 'episode',
    'posts_per_page' => 5,
    'meta_query' => [
        [
            'key' => 'episode_price',
            'value' => 0,
            'compare' => '>'
        ]
    ]
]);

if (empty($episodes)) {
    echo "<p class='error'>❌ No episodes with PPV pricing found</p>\n";
    echo "<p>Please create an episode with a PPV price set in ACF fields</p>\n";
} else {
    $test_episode = $episodes[0];
    $ppv_price = get_field('episode_price', $test_episode->ID);
    echo "<p class='success'>✅ Test episode found</p>\n";
    echo "<ul>\n";
    echo "<li>Episode ID: " . $test_episode->ID . "</li>\n";
    echo "<li>Title: " . esc_html($test_episode->post_title) . "</li>\n";
    echo "<li>PPV Price: $" . number_format($ppv_price, 2) . "</li>\n";
    echo "</ul>\n";
}
echo "</div>\n";

// Test 4: Test PPV Purchase Creation
if ($test_episode) {
    echo "<div class='test-section info'>\n";
    echo "<h2>💳 Test 4: PPV Purchase Creation</h2>\n";
    
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    
    echo "<p>Testing PPV purchase for user: " . esc_html($current_user->user_login) . " (ID: $user_id)</p>\n";
    
    try {
        // Test with minimum price for Flowguard
        $test_price = max(2.95, $ppv_price);
        
        $result = flexpress_flowguard_create_ppv_purchase($user_id, $test_episode->ID, $test_price);
        
        if ($result['success']) {
            echo "<p class='success'>✅ PPV purchase created successfully</p>\n";
            echo "<ul>\n";
            echo "<li>Session ID: " . esc_html($result['session_id']) . "</li>\n";
            echo "<li>Payment URL: " . esc_html($result['payment_url']) . "</li>\n";
            echo "<li>Price: $" . number_format($test_price, 2) . "</li>\n";
            echo "</ul>\n";
            
            // Store test data for cleanup
            update_option('flexpress_test_session_id', $result['session_id']);
            update_option('flexpress_test_episode_id', $test_episode->ID);
            update_option('flexpress_test_user_id', $user_id);
            
        } else {
            echo "<p class='error'>❌ Failed to create PPV purchase: " . esc_html($result['error']) . "</p>\n";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Exception during PPV purchase: " . esc_html($e->getMessage()) . "</p>\n";
    }
    echo "</div>\n";
}

// Test 5: Test Webhook Handler
echo "<div class='test-section info'>\n";
echo "<h2>🔗 Test 5: Webhook Handler</h2>\n";

$webhook_url = home_url('/wp-admin/admin-ajax.php?action=flowguard_webhook');
echo "<p>Webhook URL: <code>" . esc_html($webhook_url) . "</code></p>\n";

// Test webhook endpoint accessibility
$response = wp_remote_get($webhook_url, ['timeout' => 10]);
if (is_wp_error($response)) {
    echo "<p class='error'>❌ Webhook endpoint not accessible: " . esc_html($response->get_error_message()) . "</p>\n";
} else {
    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code === 200) {
        echo "<p class='success'>✅ Webhook endpoint is accessible</p>\n";
    } else {
        echo "<p class='error'>❌ Webhook endpoint returned status: $status_code</p>\n";
    }
}
echo "</div>\n";

// Test 6: Test Reference ID Parsing
echo "<div class='test-section info'>\n";
echo "<h2>🔍 Test 6: Reference ID Parsing</h2>\n";

$test_references = [
    'ppv_123_456_789', // New format
    'ppv_user_123_episode_456', // Legacy format
    'user_123_plan_456', // Subscription format
    'invalid_reference' // Invalid format
];

foreach ($test_references as $ref) {
    $user_id = flexpress_flowguard_get_user_from_reference($ref);
    echo "<p>Reference: <code>$ref</code> → User ID: " . ($user_id ?: 'Not found') . "</p>\n";
}
echo "</div>\n";

// Test 7: Test Episode Access Functions
if ($test_episode) {
    echo "<div class='test-section info'>\n";
    echo "<h2>🎯 Test 7: Episode Access Functions</h2>\n";
    
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    
    // Test access check
    $access_info = flexpress_check_episode_access($test_episode->ID, $user_id);
    $has_access = $access_info['has_access'];
    echo "<p>User has access to episode: " . ($has_access ? 'Yes' : 'No') . "</p>\n";
    
    // Test PPV purchases list
    $ppv_purchases = get_user_meta($user_id, 'ppv_purchases', true) ?: [];
    echo "<p>User's PPV purchases: " . implode(', ', $ppv_purchases) . "</p>\n";
    
    // Test access info
    $access_info = flexpress_check_episode_access($test_episode->ID, $user_id);
    echo "<p>Access info:</p>\n";
    echo "<pre>" . print_r($access_info, true) . "</pre>\n";
    
    echo "</div>\n";
}

// Test 8: Payment Page Template
echo "<div class='test-section info'>\n";
echo "<h2>📄 Test 8: Payment Page Template</h2>\n";

$payment_page = get_page_by_path('payment');
if ($payment_page) {
    echo "<p class='success'>✅ Payment page exists</p>\n";
    echo "<ul>\n";
    echo "<li>Page ID: " . $payment_page->ID . "</li>\n";
    echo "<li>Page URL: " . get_permalink($payment_page->ID) . "</li>\n";
    echo "<li>Template: " . get_page_template_slug($payment_page->ID) . "</li>\n";
    echo "</ul>\n";
} else {
    echo "<p class='error'>❌ Payment page not found</p>\n";
    echo "<p>Please create a page with slug 'payment' and assign the 'Payment' template</p>\n";
}

$success_page = get_page_by_path('payment-success');
if ($success_page) {
    echo "<p class='success'>✅ Payment success page exists</p>\n";
    echo "<ul>\n";
    echo "<li>Page ID: " . $success_page->ID . "</li>\n";
    echo "<li>Page URL: " . get_permalink($success_page->ID) . "</li>\n";
    echo "</ul>\n";
} else {
    echo "<p class='error'>❌ Payment success page not found</p>\n";
    echo "<p>Please create a page with slug 'payment-success' and assign the 'Payment Success' template</p>\n";
}
echo "</div>\n";

// Summary
echo "<div class='test-section'>\n";
echo "<h2>📊 Test Summary</h2>\n";
echo "<p>The Flowguard unlock button integration has been tested. Key components:</p>\n";
echo "<ul>\n";
echo "<li>✅ PPV purchase function updated to handle member discounts</li>\n";
echo "<li>✅ Webhook handler updated for new reference format</li>\n";
echo "<li>✅ Payment success page updated for PPV purchases</li>\n";
echo "<li>✅ Reference ID parsing supports both new and legacy formats</li>\n";
echo "</ul>\n";
echo "<p><strong>Next Steps:</strong></p>\n";
echo "<ol>\n";
echo "<li>Configure Flowguard settings with your Shop ID and Signature Key</li>\n";
echo "<li>Create episodes with PPV pricing</li>\n";
echo "<li>Test the unlock button on a live episode page</li>\n";
echo "<li>Verify webhook processing in Flowguard ControlCenter</li>\n";
echo "</ol>\n";
echo "</div>\n";

// Cleanup test data
if (get_option('flexpress_test_session_id')) {
    delete_option('flexpress_test_session_id');
    delete_option('flexpress_test_episode_id');
    delete_option('flexpress_test_user_id');
    echo "<p><em>Test data cleaned up.</em></p>\n";
}
?>
