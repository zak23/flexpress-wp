# Plunk Email Marketing Integration Documentation

## Table of Contents
1. [Overview](#overview)
2. [Plugin Architecture](#plugin-architecture)
3. [Installation & Configuration](#installation--configuration)
4. [API Integration](#api-integration)
5. [WordPress Integration](#wordpress-integration)
6. [Frontend Components](#frontend-components)
7. [User Management](#user-management)
8. [Security Features](#security-features)
9. [Code Examples](#code-examples)
10. [Improvement Suggestions](#improvement-suggestions)
11. [Troubleshooting](#troubleshooting)

## Overview

This documentation covers the complete Plunk email marketing integration for the Dolls Down Under WordPress platform. The implementation provides automated email marketing capabilities with user segmentation, subscription management, and comprehensive tracking.

### Key Features
- **Automated User Registration**: New users are automatically added to Plunk
- **Newsletter Subscription Management**: Frontend and backend subscription controls
- **Security Integration**: Cloudflare Turnstile and honeypot protection
- **User Segmentation**: Automatic tagging based on user behavior
- **Event Tracking**: Comprehensive activity tracking
- **Admin Management**: WordPress admin interface for contact management

## Plugin Architecture

### File Structure
```
wp-content/plugins/plunk-wordpress/
├── plunk-wordpress.php          # Main plugin file
├── includes/
│   ├── class-plunk-admin.php    # Admin interface
│   ├── class-plunk-api.php      # API communication
│   └── class-plunk-subscriber.php # User management
```

### Class Hierarchy
- **Plunk\Admin**: Handles WordPress admin interface and settings
- **Plunk\API**: Manages all Plunk API communications
- **Plunk\Subscriber**: Handles user subscriptions and WordPress hooks

## Installation & Configuration

### 1. Plugin Installation

```php
// Main plugin file: plunk-wordpress.php
<?php
/**
 * Plugin Name: Plunk Email Marketing
 * Description: Email marketing integration with Plunk API
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PLUNK_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('PLUNK_PLUGIN_URL', plugin_dir_url(__FILE__));
```

### 2. Configuration Setup

Navigate to **WordPress Admin > Plunk > Settings** and configure:

```php
// Required settings
$api_key = get_option('plunk_api_key');        // Your Plunk API key
$install_url = get_option('plunk_install_url'); // Your Plunk install URL
```

### 3. Activation Hook

```php
register_activation_hook(__FILE__, 'plunk_activate');

function plunk_activate() {
    // Create default options
    add_option('plunk_api_key', '');
    add_option('plunk_install_url', '');
}
```

## API Integration

### Core API Class

The `Plunk\API` class handles all communication with the Plunk API:

```php
namespace Plunk;

class API {
    private $api_key;
    private $install_url;

    public function __construct() {
        $this->api_key = get_option('plunk_api_key');
        $this->install_url = get_option('plunk_install_url');
    }

    private function make_request($endpoint, $args = []) {
        $default_args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'timeout' => 30,
            'sslverify' => true
        ];

        $args = wp_parse_args($args, $default_args);
        $url = rtrim($this->install_url, '/') . '/' . ltrim($endpoint, '/');
        
        return wp_remote_request($url, $args);
    }
}
```

### API Methods

#### 1. Contact Management

```php
// Add new contact
public function add_contact($data) {
    $contact_data = [
        'email' => $data['email'],
        'subscribed' => false,
        'data' => $data['data'] ?? []
    ];
    
    return $this->make_request('/api/v1/contacts', [
        'method' => 'POST',
        'body' => json_encode($contact_data)
    ]);
}

// Get contact by email
public function get_contact_by_email($email) {
    $result = $this->make_request('/api/v1/contacts?' . http_build_query(['email' => $email]), [
        'method' => 'GET'
    ]);
    
    if (is_wp_error($result) || empty($result)) {
        return new \WP_Error('contact_not_found', 'Contact not found');
    }
    
    foreach ($result as $contact) {
        if ($contact['email'] === $email) {
            return $contact;
        }
    }
    
    return new \WP_Error('contact_not_found', 'Contact not found');
}
```

#### 2. Subscription Management

```php
// Subscribe contact
public function subscribe_contact($contact_id, $email) {
    return $this->make_request('/api/v1/contacts/subscribe', [
        'method' => 'POST',
        'body' => json_encode(['id' => $contact_id])
    ]);
}

// Unsubscribe contact
public function unsubscribe_contact($contact_id, $email) {
    return $this->make_request('/api/v1/contacts/unsubscribe', [
        'method' => 'POST',
        'body' => json_encode(['id' => $contact_id])
    ]);
}
```

#### 3. Event Tracking

```php
// Track user events
public function track_event($contact_id, $event_name, $email) {
    return $this->make_request('/api/v1/track', [
        'method' => 'POST',
        'body' => json_encode([
            'contactId' => $contact_id,
            'event' => $event_name,
            'email' => $email
        ])
    ]);
}
```

## WordPress Integration

### 1. User Registration Hook

Automatically subscribe new users when they register:

```php
// In class-plunk-subscriber.php
public function init() {
    add_action('user_register', [$this, 'subscribe_new_user'], 10, 1);
}

public function subscribe_new_user($user_id) {
    $user = get_userdata($user_id);
    
    // Check if contact exists
    $existing_contact = $this->api->get_contact_by_email($user->user_email);
    
    $contact_data = [
        'email' => $user->user_email,
        'subscribed' => true,
        'data' => [
            'name' => $user->display_name,
            'signupDate' => date('c'),
            'source' => 'Membership Registration'
        ]
    ];
    
    if (!is_wp_error($existing_contact) && isset($existing_contact['id'])) {
        // Update existing contact
        $result = $this->api->update_contact($existing_contact['id'], $contact_data);
        update_user_meta($user_id, 'plunk_contact_id', $existing_contact['id']);
    } else {
        // Create new contact
        $result = $this->api->add_contact($contact_data);
        if (isset($result['id'])) {
            update_user_meta($user_id, 'plunk_contact_id', $result['id']);
        }
    }
}
```

### 2. User Deletion Hook

Clean up Plunk contacts when users are deleted:

```php
public function init() {
    add_action('delete_user', [$this, 'delete_plunk_contact'], 10, 1);
}

public function delete_plunk_contact($user_id) {
    $contact_id = get_user_meta($user_id, 'plunk_contact_id', true);
    
    if (!$contact_id) {
        // Try to find by email
        $user = get_userdata($user_id);
        if ($user && $user->user_email) {
            $contact = $this->api->get_contact_by_email($user->user_email);
            if (!is_wp_error($contact) && isset($contact['id'])) {
                $contact_id = $contact['id'];
            }
        }
    }
    
    if ($contact_id) {
        $this->api->delete_contact($contact_id);
    }
}
```

### 3. AJAX Handlers

#### Newsletter Signup Handler

```php
public function handle_newsletter_signup() {
    // Verify Turnstile token
    $token = $_POST['cf-turnstile-response'] ?? '';
    $secret = '0x4AAAAAAAkLNePhvurDG5F0roXP1gkLIO0';
    
    $verify = wp_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
        'body' => [
            'secret' => $secret,
            'response' => $token,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        ]
    ]);
    
    $verify_data = json_decode(wp_remote_retrieve_body($verify), true);
    
    if (!isset($verify_data['success']) || !$verify_data['success']) {
        wp_send_json_error('Security verification failed');
        return;
    }
    
    // Check honeypot
    if (!empty($_POST['website'])) {
        wp_send_json_error('Bot detected');
        return;
    }
    
    $email = sanitize_email($_POST['email']);
    $existing_contact = $this->api->get_contact_by_email($email);
    
    if (is_wp_error($existing_contact)) {
        // Create new contact
        $contact_data = [
            'email' => $email,
            'subscribed' => false,
            'data' => [
                'signupDate' => date('c'),
                'source' => 'Newsletter Modal'
            ]
        ];
        
        $result = $this->api->add_contact($contact_data);
        
        if (!is_wp_error($result) && isset($result['id'])) {
            $this->api->track_event($result['id'], 'newsletter-signup', $email);
            wp_send_json_success([
                'message' => 'Thank you for signing up!',
                'new_subscriber' => true
            ]);
        }
    } else {
        // Track event for existing contact
        $this->api->track_event($existing_contact['id'], 'newsletter-signup', $email);
        wp_send_json_success([
            'message' => 'Thanks for signing up!',
            'already_exists' => true
        ]);
    }
}
```

## Frontend Components

### 1. Newsletter Modal

The newsletter modal provides a secure signup form with Cloudflare Turnstile protection:

```html
<!-- Newsletter Modal -->
<div class="modal fade" id="newsletterModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content newsletter-modal">
            <div class="modal-body text-center">
                <h3 class="modal-title mb-3">Never Miss an Episode!</h3>
                <p class="mb-4">Subscribe to our newsletter and be the first to know when new content drops!</p>

                <form class="newsletter-form" id="newsletterForm">
                    <!-- Honeypot field -->
                    <div class="d-none">
                        <input type="text" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="mb-3">
                        <input type="email" class="form-control" id="newsletterEmail" name="email" placeholder="Enter your email" required>
                    </div>

                    <!-- Turnstile widget -->
                    <div class="cf-turnstile" data-sitekey="0x4AAAAAAAkLNXJHj9xjJMqF"></div>

                    <button type="submit" class="btn btn-light">Subscribe Now</button>
                </form>
            </div>
        </div>
    </div>
</div>
```

### 2. JavaScript Integration

```javascript
jQuery(document).ready(function($) {
    $('#newsletterForm').on('submit', function(e) {
        e.preventDefault();

        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const token = turnstile.getResponse();

        if (!token) {
            return;
        }

        $submitBtn.prop('disabled', true).text('Subscribing...');

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'plunk_newsletter_signup',
                email: $('#newsletterEmail').val(),
                website: $form.find('input[name="website"]').val(),
                'cf-turnstile-response': token
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    const successHtml = `
                        <div class="text-center newsletter-success">
                            <div class="bg-white rounded p-4">
                                <h4 class="text-pink mb-3">${response.data.message}</h4>
                                <p class="text-dark mb-0">Please check your email to confirm your subscription.</p>
                            </div>
                        </div>`;
                    
                    $form.html(successHtml);
                    
                    // Close modal after delay
                    setTimeout(function() {
                        $('#newsletterModal').modal('hide');
                    }, 3000);
                } else {
                    $submitBtn.prop('disabled', false).text('Subscribe Now');
                    turnstile.reset();
                    $form.prepend(`<div class="alert alert-danger">${response.data}</div>`);
                }
            },
            error: function() {
                $submitBtn.prop('disabled', false).text('Subscribe Now');
                turnstile.reset();
            }
        });
    });
});
```

### 3. Newsletter Status Shortcode

Display subscription status and toggle for logged-in users:

```php
public function render_newsletter_status() {
    if (!is_user_logged_in()) {
        return '<p>Please log in to manage your newsletter preferences.</p>';
    }

    $user = wp_get_current_user();
    $api = new API();
    
    $contact_id = get_user_meta($user->ID, 'plunk_contact_id', true);
    
    if ($contact_id) {
        $contact = $api->get_contact_by_id($contact_id);
    } else {
        $contact = $api->get_contact_by_email($user->user_email);
        if (!is_wp_error($contact) && isset($contact['id'])) {
            update_user_meta($user->ID, 'plunk_contact_id', $contact['id']);
        }
    }

    ob_start();
    ?>
    <div class="newsletter-toggle d-flex justify-content-center align-items-center">
        <label class="toggle-switch">
            <input type="checkbox" 
                   id="newsletter-toggle" 
                   <?php echo (!empty($contact) && isset($contact['subscribed']) && $contact['subscribed']) ? 'checked' : ''; ?>>
            <span class="toggle-slider round"></span>
        </label>
        <span class="status-text">
            <?php echo (!empty($contact) && isset($contact['subscribed']) && $contact['subscribed']) ? 'Subscribed' : 'Not Subscribed'; ?>
        </span>
    </div>
    <?php
    return ob_get_clean();
}
```

## User Management

### 1. Automatic User Segmentation

The system automatically segments users based on their behavior:

```php
// User registration segmentation
$contact_data = [
    'email' => $user->user_email,
    'subscribed' => true,
    'data' => [
        'name' => $user->display_name,
        'signupDate' => date('c'),
        'source' => 'Membership Registration',
        'userType' => 'member',
        'membershipStatus' => 'active'
    ]
];

// Newsletter signup segmentation
$contact_data = [
    'email' => $email,
    'subscribed' => false,
    'data' => [
        'signupDate' => date('c'),
        'source' => 'Newsletter Modal',
        'userType' => 'newsletter_subscriber'
    ]
];
```

### 2. Contact ID Storage

Store Plunk contact IDs in WordPress user meta for efficient lookups:

```php
// Store contact ID when user registers
update_user_meta($user_id, 'plunk_contact_id', $contact_id);

// Retrieve contact ID for operations
$contact_id = get_user_meta($user_id, 'plunk_contact_id', true);
```

### 3. Membership Status Tracking

Track membership status changes:

```php
// When membership status changes
add_action('update_user_meta', function($meta_id, $user_id, $meta_key, $meta_value) {
    if ($meta_key === 'membership_status') {
        $contact_id = get_user_meta($user_id, 'plunk_contact_id', true);
        if ($contact_id) {
            $api = new API();
            $api->update_contact($contact_id, [
                'data' => [
                    'membershipStatus' => $meta_value,
                    'lastUpdated' => date('c')
                ]
            ]);
        }
    }
}, 10, 4);
```

## Security Features

### 1. Cloudflare Turnstile Integration

Protect forms from bots using Cloudflare Turnstile:

```php
// Verify Turnstile token
$token = $_POST['cf-turnstile-response'] ?? '';
$secret = 'YOUR_TURNSTILE_SECRET';

$verify = wp_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
    'body' => [
        'secret' => $secret,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ]
]);

$verify_data = json_decode(wp_remote_retrieve_body($verify), true);

if (!isset($verify_data['success']) || !$verify_data['success']) {
    wp_send_json_error('Security verification failed');
    return;
}
```

### 2. Honeypot Protection

```html
<!-- Hidden honeypot field -->
<div class="d-none">
    <input type="text" name="website" tabindex="-1" autocomplete="off">
</div>
```

```php
// Check honeypot
if (!empty($_POST['website'])) {
    wp_send_json_error('Bot detected');
    return;
}
```

### 3. Input Sanitization

```php
// Sanitize all inputs
$email = sanitize_email($_POST['email']);
$name = sanitize_text_field($_POST['name'] ?? '');
$contact_id = sanitize_text_field($_POST['contact_id'] ?? '');
```

### 4. Nonce Verification

```php
// Verify nonces for AJAX requests
if (!wp_verify_nonce($_POST['nonce'], 'plunk_subscribe')) {
    wp_send_json_error('Invalid nonce');
}
```

## Code Examples

### 1. Custom Event Tracking

Track custom events for user behavior:

```php
// Track video view
function track_video_view($user_id, $video_id) {
    $contact_id = get_user_meta($user_id, 'plunk_contact_id', true);
    if ($contact_id) {
        $api = new API();
        $api->track_event($contact_id, 'video-view', [
            'videoId' => $video_id,
            'timestamp' => date('c')
        ]);
    }
}

// Track purchase
function track_purchase($user_id, $amount, $product_id) {
    $contact_id = get_user_meta($user_id, 'plunk_contact_id', true);
    if ($contact_id) {
        $api = new API();
        $api->track_event($contact_id, 'purchase', [
            'amount' => $amount,
            'productId' => $product_id,
            'timestamp' => date('c')
        ]);
    }
}
```

### 2. Bulk Operations

```php
// Bulk subscribe users
function bulk_subscribe_users($user_ids) {
    $api = new API();
    $results = [];
    
    foreach ($user_ids as $user_id) {
        $user = get_userdata($user_id);
        $contact_id = get_user_meta($user_id, 'plunk_contact_id', true);
        
        if ($contact_id) {
            $result = $api->subscribe_contact($contact_id, $user->user_email);
            $results[$user_id] = $result;
        }
    }
    
    return $results;
}
```

### 3. Custom Segmentation

```php
// Segment users by membership type
function segment_users_by_membership() {
    $api = new API();
    
    // Get all users with active memberships
    $users = get_users([
        'meta_key' => 'membership_status',
        'meta_value' => 'active'
    ]);
    
    foreach ($users as $user) {
        $contact_id = get_user_meta($user->ID, 'plunk_contact_id', true);
        if ($contact_id) {
            $api->update_contact($contact_id, [
                'data' => [
                    'segment' => 'active_members',
                    'lastSegmentUpdate' => date('c')
                ]
            ]);
        }
    }
}
```

## Improvement Suggestions

### 1. Advanced User Segmentation

Implement more sophisticated segmentation based on user behavior:

```php
class PlunkSegmentation {
    
    public function segment_by_engagement($user_id) {
        $contact_id = get_user_meta($user_id, 'plunk_contact_id', true);
        if (!$contact_id) return;
        
        $api = new API();
        
        // Calculate engagement score
        $engagement_score = $this->calculate_engagement_score($user_id);
        
        $segment = 'low_engagement';
        if ($engagement_score > 80) {
            $segment = 'high_engagement';
        } elseif ($engagement_score > 50) {
            $segment = 'medium_engagement';
        }
        
        $api->update_contact($contact_id, [
            'data' => [
                'engagementScore' => $engagement_score,
                'engagementSegment' => $segment,
                'lastEngagementUpdate' => date('c')
            ]
        ]);
    }
    
    private function calculate_engagement_score($user_id) {
        $score = 0;
        
        // Login frequency (40% weight)
        $login_count = get_user_meta($user_id, 'login_count_30d', true) ?: 0;
        $score += min($login_count * 2, 40);
        
        // Video views (30% weight)
        $video_views = get_user_meta($user_id, 'video_views_30d', true) ?: 0;
        $score += min($video_views, 30);
        
        // Purchase activity (20% weight)
        $purchases = get_user_meta($user_id, 'purchases_30d', true) ?: 0;
        $score += min($purchases * 10, 20);
        
        // Email engagement (10% weight)
        $email_opens = get_user_meta($user_id, 'email_opens_30d', true) ?: 0;
        $score += min($email_opens, 10);
        
        return $score;
    }
}
```

### 2. Automated Email Campaigns

Create automated email sequences based on user behavior:

```php
class PlunkAutomation {
    
    public function trigger_welcome_sequence($user_id) {
        $contact_id = get_user_meta($user_id, 'plunk_contact_id', true);
        if (!$contact_id) return;
        
        $api = new API();
        
        // Send welcome email
        $api->track_event($contact_id, 'welcome-sequence-started', [
            'sequence' => 'new_member_welcome',
            'step' => 1,
            'timestamp' => date('c')
        ]);
        
        // Schedule follow-up emails
        $this->schedule_follow_up($contact_id, 'welcome', 1, '+1 day');
        $this->schedule_follow_up($contact_id, 'welcome', 2, '+3 days');
        $this->schedule_follow_up($contact_id, 'welcome', 3, '+7 days');
    }
    
    public function trigger_retention_sequence($user_id) {
        $last_login = get_user_meta($user_id, 'last_login', true);
        $days_since_login = (time() - strtotime($last_login)) / DAY_IN_SECONDS;
        
        if ($days_since_login > 7) {
            $contact_id = get_user_meta($user_id, 'plunk_contact_id', true);
            if ($contact_id) {
                $api = new API();
                $api->track_event($contact_id, 'retention-sequence-started', [
                    'daysSinceLogin' => $days_since_login,
                    'timestamp' => date('c')
                ]);
            }
        }
    }
}
```

### 3. Advanced Analytics

Track detailed user behavior and create reports:

```php
class PlunkAnalytics {
    
    public function get_user_journey($user_id) {
        $contact_id = get_user_meta($user_id, 'plunk_contact_id', true);
        if (!$contact_id) return [];
        
        $api = new API();
        
        // Get all events for this user
        $events = $api->get_contact_events($contact_id);
        
        $journey = [];
        foreach ($events as $event) {
            $journey[] = [
                'event' => $event['event'],
                'timestamp' => $event['timestamp'],
                'data' => $event['data']
            ];
        }
        
        return $journey;
    }
    
    public function get_conversion_funnel() {
        $api = new API();
        
        // Get all contacts
        $contacts = $api->get_contacts();
        
        $funnel = [
            'newsletter_signups' => 0,
            'membership_registrations' => 0,
            'active_members' => 0,
            'purchases' => 0
        ];
        
        foreach ($contacts as $contact) {
            $data = json_decode($contact['data'], true);
            
            if (isset($data['source'])) {
                switch ($data['source']) {
                    case 'Newsletter Modal':
                        $funnel['newsletter_signups']++;
                        break;
                    case 'Membership Registration':
                        $funnel['membership_registrations']++;
                        break;
                }
            }
            
            if (isset($data['membershipStatus']) && $data['membershipStatus'] === 'active') {
                $funnel['active_members']++;
            }
            
            // Check for purchase events
            $events = $api->get_contact_events($contact['id']);
            foreach ($events as $event) {
                if ($event['event'] === 'purchase') {
                    $funnel['purchases']++;
                    break;
                }
            }
        }
        
        return $funnel;
    }
}
```

### 4. A/B Testing Integration

Implement A/B testing for email campaigns:

```php
class PlunkABTesting {
    
    public function assign_test_group($user_id, $test_name) {
        $contact_id = get_user_meta($user_id, 'plunk_contact_id', true);
        if (!$contact_id) return;
        
        // Assign user to test group (A or B)
        $test_group = (crc32($user_id . $test_name) % 2) ? 'A' : 'B';
        
        $api = new API();
        $api->update_contact($contact_id, [
            'data' => [
                'abTest' => $test_name,
                'abGroup' => $test_group,
                'abAssignedAt' => date('c')
            ]
        ]);
        
        return $test_group;
    }
    
    public function track_test_conversion($user_id, $test_name, $conversion_type) {
        $contact_id = get_user_meta($user_id, 'plunk_contact_id', true);
        if (!$contact_id) return;
        
        $api = new API();
        $api->track_event($contact_id, 'ab-test-conversion', [
            'testName' => $test_name,
            'conversionType' => $conversion_type,
            'timestamp' => date('c')
        ]);
    }
}
```

### 5. Dynamic Content Personalization

Personalize content based on user data:

```php
class PlunkPersonalization {
    
    public function get_personalized_content($user_id, $content_type) {
        $contact_id = get_user_meta($user_id, 'plunk_contact_id', true);
        if (!$contact_id) return null;
        
        $api = new API();
        $contact = $api->get_contact_by_id($contact_id);
        
        if (is_wp_error($contact)) return null;
        
        $data = json_decode($contact['data'], true);
        
        // Personalize based on user data
        $personalized_content = [
            'greeting' => $this->get_personalized_greeting($data),
            'recommendations' => $this->get_recommendations($data),
            'offers' => $this->get_personalized_offers($data)
        ];
        
        return $personalized_content;
    }
    
    private function get_personalized_greeting($data) {
        $name = $data['name'] ?? 'there';
        $membership_status = $data['membershipStatus'] ?? 'none';
        
        switch ($membership_status) {
            case 'active':
                return "Hi {$name}! As a valued member, here's what's new...";
            case 'expired':
                return "Hi {$name}! We miss you! Here's a special offer...";
            default:
                return "Hi {$name}! Discover what you're missing...";
        }
    }
}
```

### 6. Performance Optimization

Optimize API calls and implement caching:

```php
class PlunkOptimized {
    
    private $cache_group = 'plunk_contacts';
    private $cache_expiration = 300; // 5 minutes
    
    public function get_contact_cached($user_id) {
        $cache_key = "contact_{$user_id}";
        $contact = wp_cache_get($cache_key, $this->cache_group);
        
        if ($contact === false) {
            $contact_id = get_user_meta($user_id, 'plunk_contact_id', true);
            if ($contact_id) {
                $api = new API();
                $contact = $api->get_contact_by_id($contact_id);
                wp_cache_set($cache_key, $contact, $this->cache_group, $this->cache_expiration);
            }
        }
        
        return $contact;
    }
    
    public function batch_update_contacts($updates) {
        $api = new API();
        
        // Process updates in batches of 10
        $batches = array_chunk($updates, 10);
        
        foreach ($batches as $batch) {
            $this->process_batch($api, $batch);
        }
    }
    
    private function process_batch($api, $batch) {
        foreach ($batch as $update) {
            $api->update_contact($update['contact_id'], $update['data']);
        }
    }
}
```

## Troubleshooting

### Common Issues

#### 1. API Connection Errors

```php
// Check API configuration
function debug_plunk_config() {
    $api_key = get_option('plunk_api_key');
    $install_url = get_option('plunk_install_url');
    
    if (empty($api_key) || empty($install_url)) {
        error_log('Plunk: Missing API configuration');
        return false;
    }
    
    // Test API connection
    $api = new API();
    $test = $api->get_contacts();
    
    if (is_wp_error($test)) {
        error_log('Plunk API Error: ' . $test->get_error_message());
        return false;
    }
    
    return true;
}
```

#### 2. Contact Sync Issues

```php
// Sync existing users with Plunk
function sync_existing_users() {
    $users = get_users(['number' => -1]);
    $api = new API();
    
    foreach ($users as $user) {
        $contact_id = get_user_meta($user->ID, 'plunk_contact_id', true);
        
        if (!$contact_id) {
            // Try to find contact by email
            $contact = $api->get_contact_by_email($user->user_email);
            
            if (!is_wp_error($contact) && isset($contact['id'])) {
                update_user_meta($user->ID, 'plunk_contact_id', $contact['id']);
            } else {
                // Create new contact
                $contact_data = [
                    'email' => $user->user_email,
                    'subscribed' => true,
                    'data' => [
                        'name' => $user->display_name,
                        'source' => 'Manual Sync',
                        'syncDate' => date('c')
                    ]
                ];
                
                $result = $api->add_contact($contact_data);
                if (!is_wp_error($result) && isset($result['id'])) {
                    update_user_meta($user->ID, 'plunk_contact_id', $result['id']);
                }
            }
        }
    }
}
```

#### 3. Debug Logging

```php
// Enhanced logging
function plunk_log($message, $level = 'info') {
    if (is_array($message) || is_object($message)) {
        $message = print_r($message, true);
    }
    
    $log_entry = sprintf(
        '[%s] Plunk %s: %s',
        date('Y-m-d H:i:s'),
        strtoupper($level),
        $message
    );
    
    error_log($log_entry);
    
    // Also log to custom log file if needed
    if (defined('PLUNK_LOG_FILE')) {
        file_put_contents(PLUNK_LOG_FILE, $log_entry . "\n", FILE_APPEND);
    }
}
```

### Performance Monitoring

```php
// Monitor API performance
class PlunkPerformanceMonitor {
    
    private $start_time;
    
    public function start_timer() {
        $this->start_time = microtime(true);
    }
    
    public function end_timer($operation) {
        $duration = microtime(true) - $this->start_time;
        
        if ($duration > 1.0) { // Log slow operations
            plunk_log("Slow operation: {$operation} took {$duration} seconds", 'warning');
        }
        
        return $duration;
    }
    
    public function monitor_api_call($endpoint, $method) {
        $this->start_timer();
        
        // Make API call
        $api = new API();
        $result = $api->make_request($endpoint, ['method' => $method]);
        
        $duration = $this->end_timer("API {$method} {$endpoint}");
        
        // Log performance metrics
        plunk_log([
            'endpoint' => $endpoint,
            'method' => $method,
            'duration' => $duration,
            'success' => !is_wp_error($result)
        ]);
        
        return $result;
    }
}
```

This comprehensive documentation provides everything needed to understand, implement, and extend the Plunk email marketing integration. The system is designed to be scalable, secure, and feature-rich while maintaining WordPress best practices.

## Key Takeaways

1. **Automated Integration**: Users are automatically added to Plunk when they register
2. **Security First**: Cloudflare Turnstile and honeypot protection prevent spam
3. **User Segmentation**: Automatic tagging based on user behavior and membership status
4. **Event Tracking**: Comprehensive tracking of user actions and conversions
5. **Admin Management**: Full WordPress admin interface for contact management
6. **Extensible**: Easy to add new features like A/B testing and personalization

The implementation provides a solid foundation for email marketing that can be extended with advanced features as your business grows.