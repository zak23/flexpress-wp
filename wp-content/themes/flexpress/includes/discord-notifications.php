<?php
/**
 * Discord Notifications System
 * 
 * Provides Discord webhook integration for real-time notifications
 * of Flowguard payment events, talent applications, and other critical events.
 * 
 * @package FlexPress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Discord Notifications Service
 */
class FlexPress_Discord_Notifications {
    
    /**
     * Discord webhook URL
     * 
     * @var string
     */
    private $webhook_url;
    
    /**
     * Site name for notifications
     * 
     * @var string
     */
    private $site_name;
    
    /**
     * Constructor
     */
    public function __construct() {
        $discord_settings = get_option('flexpress_discord_settings', []);
        $this->webhook_url = $discord_settings['webhook_url'] ?? '';
        $this->site_name = get_bloginfo('name');
    }
    
    /**
     * Send Discord notification
     * 
     * @param array $embed Discord embed data
     * @param string $content Optional message content
     * @return bool Success status
     */
    public function send_notification($embed, $content = '') {
        if (empty($this->webhook_url)) {
            error_log('Discord: No webhook URL configured');
            return false;
        }
        
        $payload = [
            'content' => $content,
            'embeds' => [$embed]
        ];
        
        $response = wp_remote_post($this->webhook_url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'User-Agent' => 'FlexPress-Discord-Notifications/1.0'
            ],
            'body' => json_encode($payload),
            'timeout' => 10
        ]);
        
        if (is_wp_error($response)) {
            error_log('Discord notification failed: ' . $response->get_error_message());
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code >= 200 && $status_code < 300) {
            return true;
        }
        
        error_log('Discord notification failed with status: ' . $status_code);
        return false;
    }
    
    /**
     * Create subscription approved embed
     * 
     * @param array $payload Flowguard webhook payload
     * @param int $user_id User ID
     * @return array Discord embed
     */
    public function create_subscription_approved_embed($payload, $user_id) {
        $user = get_userdata($user_id);
        $user_display_name = flexpress_get_user_display_name($user_id);
        
        $embed = [
            'title' => '🎉 New Member Signup!',
            'color' => 0x00ff00, // Green
            'fields' => [
                [
                    'name' => 'Username',
                    'value' => $user_display_name,
                    'inline' => true
                ],
                [
                    'name' => 'User ID',
                    'value' => '`' . $user_id . '`',
                    'inline' => true
                ],
                [
                    'name' => 'Email',
                    'value' => $user->user_email,
                    'inline' => true
                ],
                [
                    'name' => 'Amount',
                    'value' => $payload['priceCurrency'] . ' ' . $payload['priceAmount'],
                    'inline' => true
                ],
                [
                    'name' => 'Subscription Type',
                    'value' => ucfirst($payload['subscriptionType'] ?? 'unknown'),
                    'inline' => true
                ],
                [
                    'name' => 'Transaction ID',
                    'value' => '`' . $payload['transactionId'] . '`',
                    'inline' => true
                ],
                [
                    'name' => 'Sale ID',
                    'value' => '`' . $payload['saleId'] . '`',
                    'inline' => true
                ]
            ],
            'footer' => [
                'text' => $this->site_name . ' • Flowguard',
                'icon_url' => get_site_icon_url()
            ],
            'timestamp' => date('c')
        ];
        
        // Add next charge date for recurring subscriptions
        if ($payload['subscriptionType'] ?? 'unknown' === 'recurring' && !empty($payload['nextChargeOn'])) {
            $embed['fields'][] = [
                'name' => 'Next Charge',
                'value' => date('M j, Y', strtotime($payload['nextChargeOn'])),
                'inline' => true
            ];
        }
        
        // Add expiration date for one-time subscriptions only
        if (($payload['subscriptionType'] ?? 'unknown') === 'one-time' && isset($payload['expiresOn']) && !empty($payload['expiresOn'])) {
            $embed['fields'][] = [
                'name' => 'Expires',
                'value' => date('M j, Y', strtotime($payload['expiresOn'])),
                'inline' => true
            ];
        }
        
        return $embed;
    }
    
    /**
     * Create subscription rebill embed
     * 
     * @param array $payload Flowguard webhook payload
     * @param int $user_id User ID
     * @return array Discord embed
     */
    public function create_subscription_rebill_embed($payload, $user_id) {
        $user_display_name = flexpress_get_user_display_name($user_id);
        
        $embed = [
            'title' => '💰 Subscription Rebill Success',
            'color' => 0x00ff00, // Green
            'fields' => [
                [
                    'name' => 'Username',
                    'value' => $user_display_name,
                    'inline' => true
                ],
                [
                    'name' => 'User ID',
                    'value' => '`' . $user_id . '`',
                    'inline' => true
                ],
                [
                    'name' => 'Amount',
                    'value' => $payload['priceCurrency'] . ' ' . $payload['priceAmount'],
                    'inline' => true
                ],
                [
                    'name' => 'Transaction ID',
                    'value' => '`' . $payload['transactionId'] . '`',
                    'inline' => true
                ],
                [
                    'name' => 'Sale ID',
                    'value' => '`' . $payload['saleId'] . '`',
                    'inline' => true
                ],
                [
                    'name' => 'Next Charge',
                    'value' => date('M j, Y', strtotime($payload['nextChargeOn'])),
                    'inline' => true
                ]
            ],
            'footer' => [
                'text' => $this->site_name . ' • Flowguard',
                'icon_url' => get_site_icon_url()
            ],
            'timestamp' => date('c')
        ];
        
        return $embed;
    }
    
    /**
     * Create subscription cancel embed
     * 
     * @param array $payload Flowguard webhook payload
     * @param int $user_id User ID
     * @return array Discord embed
     */
    public function create_subscription_cancel_embed($payload, $user_id) {
        $user_display_name = flexpress_get_user_display_name($user_id);
        
        $embed = [
            'title' => '❌ Subscription Cancelled',
            'color' => 0xff6b35, // Orange
            'fields' => [
                [
                    'name' => 'Username',
                    'value' => $user_display_name,
                    'inline' => true
                ],
                [
                    'name' => 'User ID',
                    'value' => '`' . $user_id . '`',
                    'inline' => true
                ],
                [
                    'name' => 'Cancelled By',
                    'value' => ucfirst($payload['cancelledBy'] ?? 'unknown'),
                    'inline' => true
                ],
                [
                    'name' => 'Sale ID',
                    'value' => '`' . $payload['saleId'] . '`',
                    'inline' => true
                ]
            ],
            'footer' => [
                'text' => $this->site_name . ' • Flowguard',
                'icon_url' => get_site_icon_url()
            ],
            'timestamp' => date('c')
        ];
        
        // Add expiration date for one-time subscriptions only
        if (($payload['subscriptionType'] ?? 'unknown') === 'one-time' && isset($payload['expiresOn']) && !empty($payload['expiresOn'])) {
            $embed['fields'][] = [
                'name' => 'Access Expires',
                'value' => date('M j, Y', strtotime($payload['expiresOn'])),
                'inline' => true
            ];
        }
        
        return $embed;
    }
    
    /**
     * Create subscription expiry embed
     * 
     * @param array $payload Flowguard webhook payload
     * @param int $user_id User ID
     * @return array Discord embed
     */
    public function create_subscription_expiry_embed($payload, $user_id) {
        $user_display_name = flexpress_get_user_display_name($user_id);
        
        $embed = [
            'title' => '⏰ Subscription Expired',
            'color' => 0xff0000, // Red
            'fields' => [
                [
                    'name' => 'Username',
                    'value' => $user_display_name,
                    'inline' => true
                ],
                [
                    'name' => 'User ID',
                    'value' => '`' . $user_id . '`',
                    'inline' => true
                ],
                [
                    'name' => 'Sale ID',
                    'value' => '`' . $payload['saleId'] . '`',
                    'inline' => true
                ],
                [
                    'name' => 'Subscription Type',
                    'value' => ucfirst($payload['subscriptionType'] ?? 'unknown'),
                    'inline' => true
                ]
            ],
            'footer' => [
                'text' => $this->site_name . ' • Flowguard',
                'icon_url' => get_site_icon_url()
            ],
            'timestamp' => date('c')
        ];
        
        return $embed;
    }
    
    /**
     * Create subscription extend embed
     * 
     * @param array $payload Flowguard webhook payload
     * @param int $user_id User ID
     * @return array Discord embed
     */
    public function create_subscription_extend_embed($payload, $user_id) {
        $user_display_name = flexpress_get_user_display_name($user_id);
        
        $embed = [
            'title' => '🔄 Subscription Extended',
            'color' => 0x00bfff, // Deep Sky Blue
            'fields' => [
                [
                    'name' => 'Username',
                    'value' => $user_display_name,
                    'inline' => true
                ],
                [
                    'name' => 'User ID',
                    'value' => '`' . $user_id . '`',
                    'inline' => true
                ],
                [
                    'name' => 'Amount',
                    'value' => $payload['priceCurrency'] . ' ' . $payload['priceAmount'],
                    'inline' => true
                ],
                [
                    'name' => 'Subscription Type',
                    'value' => ucfirst($payload['subscriptionType'] ?? 'unknown'),
                    'inline' => true
                ],
                [
                    'name' => 'Transaction ID',
                    'value' => '`' . $payload['transactionId'] . '`',
                    'inline' => true
                ],
                [
                    'name' => 'Sale ID',
                    'value' => '`' . $payload['saleId'] . '`',
                    'inline' => true
                ]
            ],
            'footer' => [
                'text' => $this->site_name . ' • Flowguard',
                'icon_url' => get_site_icon_url()
            ],
            'timestamp' => date('c')
        ];
        
        // Add next charge date for recurring subscriptions
        if ($payload['subscriptionType'] ?? 'unknown' === 'recurring' && !empty($payload['nextChargeOn'])) {
            $embed['fields'][] = [
                'name' => 'Next Charge',
                'value' => date('M j, Y', strtotime($payload['nextChargeOn'])),
                'inline' => true
            ];
        }
        
        // Add expiration date for one-time subscriptions
        if (($payload['subscriptionType'] ?? 'unknown') === 'one-time' && isset($payload['expiresOn']) && !empty($payload['expiresOn'])) {
            $embed['fields'][] = [
                'name' => 'New Expiration',
                'value' => date('M j, Y', strtotime($payload['expiresOn'])),
                'inline' => true
            ];
        }
        
        return $embed;
    }
    
    /**
     * Create purchase approved embed (PPV)
     * 
     * @param array $payload Flowguard webhook payload
     * @param int $user_id User ID
     * @param int $episode_id Episode ID
     * @return array Discord embed
     */
    public function create_purchase_approved_embed($payload, $user_id, $episode_id = 0) {
        $user_display_name = flexpress_get_user_display_name($user_id);
        
        $embed = [
            'title' => '🎬 PPV Purchase Approved',
            'color' => 0x0099ff, // Blue
            'fields' => [
                [
                    'name' => 'Username',
                    'value' => $user_display_name,
                    'inline' => true
                ],
                [
                    'name' => 'User ID',
                    'value' => '`' . $user_id . '`',
                    'inline' => true
                ],
                [
                    'name' => 'Amount',
                    'value' => $payload['priceCurrency'] . ' ' . $payload['priceAmount'],
                    'inline' => true
                ],
                [
                    'name' => 'Transaction ID',
                    'value' => '`' . $payload['transactionId'] . '`',
                    'inline' => true
                ]
            ],
            'footer' => [
                'text' => $this->site_name . ' • Flowguard',
                'icon_url' => get_site_icon_url()
            ],
            'timestamp' => date('c')
        ];
        
        // Add episode information if available
        if ($episode_id > 0) {
            $episode = get_post($episode_id);
            if ($episode) {
                $embed['fields'][] = [
                    'name' => 'Episode',
                    'value' => $episode->post_title,
                    'inline' => true
                ];
                
                $embed['fields'][] = [
                    'name' => 'Episode Link',
                    'value' => '[View Episode](' . get_permalink($episode_id) . ')',
                    'inline' => true
                ];
            }
        }
        
        return $embed;
    }
    
    /**
     * Create refund embed
     * 
     * @param array $payload Flowguard webhook payload
     * @param int $user_id User ID
     * @return array Discord embed
     */
    public function create_refund_embed($payload, $user_id) {
        $user_display_name = flexpress_get_user_display_name($user_id);
        $refund_type = $payload['postbackType']; // chargeback or credit
        
        $embed = [
            'title' => '⚠️ ' . ucfirst($refund_type) . ' Processed',
            'color' => 0xff0000, // Red
            'fields' => [
                [
                    'name' => 'Username',
                    'value' => $user_display_name,
                    'inline' => true
                ],
                [
                    'name' => 'User ID',
                    'value' => '`' . $user_id . '`',
                    'inline' => true
                ],
                [
                    'name' => 'Amount',
                    'value' => $payload['priceCurrency'] . ' ' . $payload['priceAmount'],
                    'inline' => true
                ],
                [
                    'name' => 'Transaction ID',
                    'value' => '`' . $payload['transactionId'] . '`',
                    'inline' => true
                ],
                [
                    'name' => 'Order Type',
                    'value' => ucfirst($payload['orderType']),
                    'inline' => true
                ]
            ],
            'footer' => [
                'text' => $this->site_name . ' • Flowguard',
                'icon_url' => get_site_icon_url()
            ],
            'timestamp' => date('c')
        ];
        
        return $embed;
    }
    
    /**
     * Create talent application embed
     * 
     * @param array $form_data Form submission data
     * @return array Discord embed
     */
    public function create_talent_application_embed($form_data) {
        $embed = [
            'title' => '🌟 New Talent Application',
            'color' => 0xffd700, // Gold
            'fields' => [
                [
                    'name' => 'Name',
                    'value' => $form_data['name'] ?? 'Not provided',
                    'inline' => true
                ],
                [
                    'name' => 'Email',
                    'value' => $form_data['email'] ?? 'Not provided',
                    'inline' => true
                ],
                [
                    'name' => 'Phone',
                    'value' => $form_data['phone'] ?? 'Not provided',
                    'inline' => true
                ],
                [
                    'name' => 'Age',
                    'value' => $form_data['age'] ?? 'Not provided',
                    'inline' => true
                ],
                [
                    'name' => 'Location',
                    'value' => $form_data['location'] ?? 'Not provided',
                    'inline' => true
                ],
                [
                    'name' => 'Experience',
                    'value' => $form_data['experience'] ?? 'Not provided',
                    'inline' => true
                ]
            ],
            'footer' => [
                'text' => $this->site_name . ' • Talent Applications',
                'icon_url' => get_site_icon_url()
            ],
            'timestamp' => date('c')
        ];
        
        // Add bio if provided
        if (!empty($form_data['bio'])) {
            $embed['fields'][] = [
                'name' => 'Bio',
                'value' => substr($form_data['bio'], 0, 1000) . (strlen($form_data['bio']) > 1000 ? '...' : ''),
                'inline' => false
            ];
        }
        
        return $embed;
    }
    
    /**
     * Create general notification embed
     * 
     * @param string $title Notification title
     * @param string $description Notification description
     * @param int $color Embed color (hex)
     * @param array $fields Additional fields
     * @return array Discord embed
     */
    public function create_general_embed($title, $description, $color = 0x0099ff, $fields = []) {
        $embed = [
            'title' => $title,
            'description' => $description,
            'color' => $color,
            'fields' => $fields,
            'footer' => [
                'text' => $this->site_name,
                'icon_url' => get_site_icon_url()
            ],
            'timestamp' => date('c')
        ];
        
        return $embed;
    }
}

/**
 * Send Discord notification for Flowguard subscription approved
 * 
 * @param array $payload Flowguard webhook payload
 * @param int $user_id User ID
 */
function flexpress_discord_notify_subscription_approved($payload, $user_id) {
    $discord_settings = get_option('flexpress_discord_settings', []);
    if (empty($discord_settings['webhook_url']) || !$discord_settings['notify_subscriptions']) {
        return;
    }
    
    $discord = new FlexPress_Discord_Notifications();
    $embed = $discord->create_subscription_approved_embed($payload, $user_id);
    
    $discord->send_notification($embed, '🎉 **New member signed up!**');
}

/**
 * Send Discord notification for Flowguard subscription rebill
 * 
 * @param array $payload Flowguard webhook payload
 * @param int $user_id User ID
 */
function flexpress_discord_notify_subscription_rebill($payload, $user_id) {
    $discord_settings = get_option('flexpress_discord_settings', []);
    if (empty($discord_settings['webhook_url']) || !$discord_settings['notify_rebills']) {
        return;
    }
    
    $discord = new FlexPress_Discord_Notifications();
    $embed = $discord->create_subscription_rebill_embed($payload, $user_id);
    
    $discord->send_notification($embed, '💰 **Subscription rebill successful!**');
}

/**
 * Send Discord notification for Flowguard subscription cancel
 * 
 * @param array $payload Flowguard webhook payload
 * @param int $user_id User ID
 */
function flexpress_discord_notify_subscription_cancel($payload, $user_id) {
    $discord_settings = get_option('flexpress_discord_settings', []);
    if (empty($discord_settings['webhook_url']) || !$discord_settings['notify_cancellations']) {
        return;
    }
    
    $discord = new FlexPress_Discord_Notifications();
    $embed = $discord->create_subscription_cancel_embed($payload, $user_id);
    
    $discord->send_notification($embed, '❌ **Subscription cancelled!**');
}

/**
 * Send Discord notification for Flowguard subscription expiry
 * 
 * @param array $payload Flowguard webhook payload
 * @param int $user_id User ID
 */
function flexpress_discord_notify_subscription_expiry($payload, $user_id) {
    $discord_settings = get_option('flexpress_discord_settings', []);
    if (empty($discord_settings['webhook_url']) || !$discord_settings['notify_expirations']) {
        return;
    }
    
    $discord = new FlexPress_Discord_Notifications();
    $embed = $discord->create_subscription_expiry_embed($payload, $user_id);
    
    $discord->send_notification($embed, '⏰ **Subscription expired!**');
}

/**
 * Send Discord notification for Flowguard subscription extend
 * 
 * @param array $payload Flowguard webhook payload
 * @param int $user_id User ID
 */
function flexpress_discord_notify_subscription_extend($payload, $user_id) {
    $discord_settings = get_option('flexpress_discord_settings', []);
    
    // Debug logging
    error_log('Discord Extend: Checking settings - webhook_url: ' . (empty($discord_settings['webhook_url']) ? 'empty' : 'set') . ', notify_extensions: ' . ($discord_settings['notify_extensions'] ?? 'not set'));
    
    if (empty($discord_settings['webhook_url']) || !($discord_settings['notify_extensions'] ?? true)) {
        error_log('Discord Extend: Notification skipped - webhook_url empty or notify_extensions disabled');
        return;
    }
    
    error_log('Discord Extend: Sending notification for user ' . $user_id);
    
    $discord = new FlexPress_Discord_Notifications();
    $embed = $discord->create_subscription_extend_embed($payload, $user_id);
    
    $result = $discord->send_notification($embed, '🔄 **Subscription extended!**');
    error_log('Discord Extend: Notification result: ' . ($result ? 'success' : 'failed'));
}

/**
 * Send Discord notification for Flowguard purchase approved (PPV)
 * 
 * @param array $payload Flowguard webhook payload
 * @param int $user_id User ID
 * @param int $episode_id Episode ID
 */
function flexpress_discord_notify_purchase_approved($payload, $user_id, $episode_id = 0) {
    $discord_settings = get_option('flexpress_discord_settings', []);
    if (empty($discord_settings['webhook_url']) || !$discord_settings['notify_ppv']) {
        return;
    }
    
    $discord = new FlexPress_Discord_Notifications();
    $embed = $discord->create_purchase_approved_embed($payload, $user_id, $episode_id);
    
    $discord->send_notification($embed, '🎬 **PPV purchase successful!**');
}

/**
 * Send Discord notification for Flowguard refund
 * 
 * @param array $payload Flowguard webhook payload
 * @param int $user_id User ID
 */
function flexpress_discord_notify_refund($payload, $user_id) {
    $discord_settings = get_option('flexpress_discord_settings', []);
    if (empty($discord_settings['webhook_url']) || !$discord_settings['notify_refunds']) {
        return;
    }
    
    $discord = new FlexPress_Discord_Notifications();
    $embed = $discord->create_refund_embed($payload, $user_id);
    
    $refund_type = $payload['postbackType'];
    $discord->send_notification($embed, '⚠️ **' . ucfirst($refund_type) . ' processed!**');
}

/**
 * Send Discord notification for talent application
 * 
 * @param array $form_data Form submission data
 */
function flexpress_discord_notify_talent_application($form_data) {
    $discord_settings = get_option('flexpress_discord_settings', []);
    if (empty($discord_settings['webhook_url']) || !$discord_settings['notify_talent_applications']) {
        return;
    }
    
    $discord = new FlexPress_Discord_Notifications();
    $embed = $discord->create_talent_application_embed($form_data);
    
    $discord->send_notification($embed, '🌟 **New talent application received!**');
}

/**
 * Send custom Discord notification
 * 
 * @param string $title Notification title
 * @param string $description Notification description
 * @param int $color Embed color (hex)
 * @param array $fields Additional fields
 * @param string $content Optional message content
 */
function flexpress_discord_send_custom_notification($title, $description, $color = 0x0099ff, $fields = [], $content = '') {
    $discord_settings = get_option('flexpress_discord_settings', []);
    if (empty($discord_settings['webhook_url'])) {
        return false;
    }
    
    $discord = new FlexPress_Discord_Notifications();
    $embed = $discord->create_general_embed($title, $description, $color, $fields);
    
    return $discord->send_notification($embed, $content);
}

/**
 * Test Discord webhook connection
 * 
 * @return bool Success status
 */
function flexpress_discord_test_connection() {
    $discord_settings = get_option('flexpress_discord_settings', []);
    if (empty($discord_settings['webhook_url'])) {
        return false;
    }
    
    $discord = new FlexPress_Discord_Notifications();
    $embed = $discord->create_general_embed(
        '🧪 Discord Test Notification',
        'This is a test notification from FlexPress. If you can see this, your Discord webhook is working correctly!',
        0x00ff00,
        [
            [
                'name' => 'Test Time',
                'value' => date('Y-m-d H:i:s'),
                'inline' => true
            ],
            [
                'name' => 'Site',
                'value' => get_bloginfo('name'),
                'inline' => true
            ]
        ]
    );
    
    return $discord->send_notification($embed, '🧪 **Discord webhook test successful!**');
}
