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
     * Discord webhook URLs for different notification types
     * 
     * @var array
     */
    private $webhook_urls;
    
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
        
        // Support both legacy single webhook and new multi-webhook system
        $default_webhook = $discord_settings['webhook_url'] ?? '';
        
        $this->webhook_urls = [
            'default' => $default_webhook,
            'financial' => $discord_settings['webhook_url_financial'] ?? $default_webhook,
            'contact' => $discord_settings['webhook_url_contact'] ?? $default_webhook
        ];
        
        $this->site_name = get_bloginfo('name');
    }
    
    /**
     * Send Discord notification
     * 
     * @param array $embed Discord embed data
     * @param string $content Optional message content
     * @param string $channel_type Channel type (subscriptions, payments, talent, general, default)
     * @return bool Success status
     */
    public function send_notification($embed, $content = '', $channel_type = 'default') {
        $webhook_url = $this->webhook_urls[$channel_type] ?? $this->webhook_urls['default'];
        
        if (empty($webhook_url)) {
            error_log('Discord: No webhook URL configured for channel type: ' . $channel_type);
            return false;
        }
        
        // Validate embed data before sending
        $embed = $this->validate_embed_data($embed);
        
        $payload = [
            'content' => $content,
            'embeds' => [$embed]
        ];
        
        $response = wp_remote_post($webhook_url, [
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
        $response_body = wp_remote_retrieve_body($response);
        
        if ($status_code >= 200 && $status_code < 300) {
            return true;
        }
        
        // Enhanced error logging for debugging
        error_log('Discord notification failed with status: ' . $status_code);
        error_log('Discord response body: ' . $response_body);
        error_log('Discord payload: ' . json_encode($payload));
        
        return false;
    }
    
    /**
     * Validate embed data to prevent Discord 400 errors
     * 
     * @param array $embed Discord embed data
     * @return array Validated embed data
     */
    private function validate_embed_data($embed) {
        // Ensure title is not too long (256 char limit)
        if (isset($embed['title']) && strlen($embed['title']) > 256) {
            $embed['title'] = substr($embed['title'], 0, 253) . '...';
        }
        
        // Ensure description is not too long (4096 char limit)
        if (isset($embed['description']) && strlen($embed['description']) > 4096) {
            $embed['description'] = substr($embed['description'], 0, 4093) . '...';
        }
        
        // Validate fields
        if (isset($embed['fields']) && is_array($embed['fields'])) {
            $validated_fields = [];
            foreach ($embed['fields'] as $field) {
                // Ensure field name is not too long (256 char limit)
                if (isset($field['name']) && strlen($field['name']) > 256) {
                    $field['name'] = substr($field['name'], 0, 253) . '...';
                }
                
                // Ensure field value is not too long (1024 char limit)
                if (isset($field['value']) && strlen($field['value']) > 1024) {
                    $field['value'] = substr($field['value'], 0, 1021) . '...';
                }
                
                $validated_fields[] = $field;
            }
            
            // Limit to 25 fields max
            if (count($validated_fields) > 25) {
                $validated_fields = array_slice($validated_fields, 0, 25);
            }
            
            $embed['fields'] = $validated_fields;
        }
        
        // Ensure footer text is not too long (2048 char limit)
        if (isset($embed['footer']['text']) && strlen($embed['footer']['text']) > 2048) {
            $embed['footer']['text'] = substr($embed['footer']['text'], 0, 2045) . '...';
        }
        
        return $embed;
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
        
        // Add promo code if used during signup
        $applied_promo_code = get_user_meta($user_id, 'applied_promo_code', true);
        if (!empty($applied_promo_code)) {
            $embed['fields'][] = [
                'name' => 'Promo Code',
                'value' => '`' . $applied_promo_code . '`',
                'inline' => true
            ];
        }
        
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
        
        // Add promo code if used during original signup
        $applied_promo_code = get_user_meta($user_id, 'applied_promo_code', true);
        if (!empty($applied_promo_code)) {
            $embed['fields'][] = [
                'name' => 'Original Promo Code',
                'value' => '`' . $applied_promo_code . '`',
                'inline' => true
            ];
        }
        
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
        
        // Add promo code if used during original signup
        $applied_promo_code = get_user_meta($user_id, 'applied_promo_code', true);
        if (!empty($applied_promo_code)) {
            $embed['fields'][] = [
                'name' => 'Original Promo Code',
                'value' => '`' . $applied_promo_code . '`',
                'inline' => true
            ];
        }
        
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
        
        // Differentiate between refund and chargeback
        if ($refund_type === 'chargeback') {
            $title = '🚨 Chargeback Dispute';
            $color = 0xff0000; // Red - more urgent
        } else {
            $title = '💰 Refund Processed';
            $color = 0xffa500; // Orange - less urgent
        }
        
        $embed = [
            'title' => $title,
            'color' => $color,
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
    
    $discord->send_notification($embed, '🎉 **New member signed up!**', 'financial');
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
    
    $discord->send_notification($embed, '💰 **Subscription rebill successful!**', 'financial');
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
    
    $discord->send_notification($embed, '❌ **Subscription cancelled!**', 'financial');
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
    
    $discord->send_notification($embed, '⏰ **Subscription expired!**', 'financial');
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
    
    $result = $discord->send_notification($embed, '🔄 **Subscription extended!**', 'financial');
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
    
    $discord->send_notification($embed, '🎬 **PPV purchase successful!**', 'financial');
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
    if ($refund_type === 'chargeback') {
        $message = '🚨 **Chargeback dispute filed!** Immediate attention required.';
    } else {
        $message = '💰 **Refund processed!** Customer service action completed.';
    }
    $discord->send_notification($embed, $message, 'financial');
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
    
    $discord->send_notification($embed, '🌟 **New talent application received!**', 'contact');
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
function flexpress_discord_send_custom_notification($title, $description, $color = 0x0099ff, $fields = [], $content = '', $channel_type = 'default') {
    $discord_settings = get_option('flexpress_discord_settings', []);
    if (empty($discord_settings['webhook_url'])) {
        return false;
    }
    
    $discord = new FlexPress_Discord_Notifications();
    $embed = $discord->create_general_embed($title, $description, $color, $fields);
    
    return $discord->send_notification($embed, $content, $channel_type);
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
    
    return $discord->send_notification($embed, '🧪 **Discord webhook test successful!**', 'default');
}
