<?php
/**
 * FlexPress Contact & Social Media Helper Functions
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Get business information by type
 *
 * @param string $type Business info type (parent_company, business_number, business_address)
 * @return string Business information or empty string if not set
 */
function flexpress_get_business_info($type) {
    $options = get_option('flexpress_contact_settings', array());
    
    return isset($options[$type]) ? $options[$type] : '';
}

/**
 * Get parent company name
 *
 * @return string Parent company name
 */
function flexpress_get_parent_company() {
    return flexpress_get_business_info('parent_company');
}

/**
 * Get business number (ABN, TIN, EIN, etc.)
 *
 * @return string Business number
 */
function flexpress_get_business_number() {
    return flexpress_get_business_info('business_number');
}

/**
 * Get business address
 *
 * @return string Business address
 */
function flexpress_get_business_address() {
    return flexpress_get_business_info('business_address');
}

/**
 * Get formatted business information line
 *
 * @param string $separator Separator between elements (default: ' - ')
 * @return string Formatted business info or empty string if no info available
 */
function flexpress_get_formatted_business_info($separator = ' - ') {
    $company = flexpress_get_parent_company();
    $business_number = flexpress_get_business_number();
    $address = flexpress_get_business_address();
    
    $parts = array();
    
    if (!empty($company)) {
        $parts[] = $company;
    }
    
    if (!empty($business_number)) {
        $parts[] = $business_number;
    }
    
    if (!empty($address)) {
        $parts[] = $address;
    }
    
    return !empty($parts) ? implode($separator, $parts) : '';
}

/**
 * Display formatted business information
 *
 * @param array $args Display options
 */
function flexpress_display_business_info($args = array()) {
    $defaults = array(
        'separator' => ' - ',
        'wrapper' => 'p',
        'class' => 'business-info',
        'show_company' => true,
        'show_number' => true,
        'show_address' => true
    );
    
    $args = wp_parse_args($args, $defaults);
    
    $parts = array();
    
    if ($args['show_company']) {
        $company = flexpress_get_parent_company();
        if (!empty($company)) {
            $parts[] = $company;
        }
    }
    
    if ($args['show_number']) {
        $business_number = flexpress_get_business_number();
        if (!empty($business_number)) {
            $parts[] = $business_number;
        }
    }
    
    if ($args['show_address']) {
        $address = flexpress_get_business_address();
        if (!empty($address)) {
            $parts[] = $address;
        }
    }
    
    if (empty($parts)) {
        return;
    }
    
    $business_info = implode($args['separator'], $parts);
    
    if ($args['wrapper']) {
        echo '<' . esc_attr($args['wrapper']) . ' class="' . esc_attr($args['class']) . '">';
    }
    
    echo esc_html($business_info);
    
    if ($args['wrapper']) {
        echo '</' . esc_attr($args['wrapper']) . '>';
    }
}

/**
 * Check if any business information is configured
 *
 * @return bool True if at least one business info field is set
 */
function flexpress_has_business_info() {
    $info = array(
        flexpress_get_parent_company(),
        flexpress_get_business_number(),
        flexpress_get_business_address()
    );
    
    foreach ($info as $field) {
        if (!empty($field)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Get contact email by type
 *
 * @param string $type Email type (support, contact, billing)
 * @return string Email address or empty string if not set
 */
function flexpress_get_contact_email($type) {
    $options = get_option('flexpress_contact_settings', array());
    $field_name = $type . '_email';
    
    return isset($options[$field_name]) ? $options[$field_name] : '';
}

/**
 * Get support email address
 *
 * @return string Support email address
 */
function flexpress_get_support_email() {
    return flexpress_get_contact_email('support');
}

/**
 * Get general contact email address
 *
 * @return string Contact email address
 */
function flexpress_get_contact_email_address() {
    return flexpress_get_contact_email('contact');
}

/**
 * Get billing email address
 *
 * @return string Billing email address
 */
function flexpress_get_billing_email() {
    return flexpress_get_contact_email('billing');
}

/**
 * Get social media URL by platform
 *
 * @param string $platform Social media platform name
 * @return string Social media URL or empty string if not set
 */
function flexpress_get_social_media_url($platform) {
    $options = get_option('flexpress_contact_settings', array());
    $field_name = 'social_' . $platform;
    
    return isset($options[$field_name]) ? $options[$field_name] : '';
}

/**
 * Get all social media links
 *
 * @return array Array of social media platforms and their URLs
 */
function flexpress_get_all_social_media_links() {
    $options = get_option('flexpress_contact_settings', array());
    $social_links = array();
    
    $platforms = array(
        'facebook', 'instagram', 'twitter', 'youtube', 'tiktok', 'linkedin',
        'pinterest', 'snapchat', 'onlyfans', 'fansly', 'manyvideos', 'chaturbate',
        'reddit', 'tumblr', 'twitch', 'patreon', 'discord', 'telegram', 'whatsapp'
    );
    
    foreach ($platforms as $platform) {
        $field_name = 'social_' . $platform;
        if (isset($options[$field_name]) && !empty($options[$field_name])) {
            $social_links[$platform] = $options[$field_name];
        }
    }
    
    return $social_links;
}

/**
 * Get social media platform configuration
 *
 * @return array Social media platform configuration with icons and labels
 */
function flexpress_get_social_media_config() {
    return array(
        'facebook' => array(
            'label' => __('Facebook', 'flexpress'),
            'icon' => 'fab fa-facebook-f'
        ),
        'instagram' => array(
            'label' => __('Instagram', 'flexpress'),
            'icon' => 'fab fa-instagram'
        ),
        'twitter' => array(
            'label' => __('Twitter / X', 'flexpress'),
            'icon' => 'fab fa-twitter'
        ),
        'youtube' => array(
            'label' => __('YouTube', 'flexpress'),
            'icon' => 'fab fa-youtube'
        ),
        'tiktok' => array(
            'label' => __('TikTok', 'flexpress'),
            'icon' => 'fab fa-tiktok'
        ),
        'linkedin' => array(
            'label' => __('LinkedIn', 'flexpress'),
            'icon' => 'fab fa-linkedin-in'
        ),
        'pinterest' => array(
            'label' => __('Pinterest', 'flexpress'),
            'icon' => 'fab fa-pinterest'
        ),
        'snapchat' => array(
            'label' => __('Snapchat', 'flexpress'),
            'icon' => 'fab fa-snapchat-ghost'
        ),
        'onlyfans' => array(
            'label' => __('OnlyFans', 'flexpress'),
            'icon' => 'fas fa-user-circle'
        ),
        'fansly' => array(
            'label' => __('Fansly', 'flexpress'),
            'icon' => 'fas fa-heart'
        ),
        'manyvideos' => array(
            'label' => __('ManyVids', 'flexpress'),
            'icon' => 'fas fa-video'
        ),
        'chaturbate' => array(
            'label' => __('Chaturbate', 'flexpress'),
            'icon' => 'fas fa-video'
        ),
        'reddit' => array(
            'label' => __('Reddit', 'flexpress'),
            'icon' => 'fab fa-reddit-alien'
        ),
        'tumblr' => array(
            'label' => __('Tumblr', 'flexpress'),
            'icon' => 'fab fa-tumblr'
        ),
        'twitch' => array(
            'label' => __('Twitch', 'flexpress'),
            'icon' => 'fab fa-twitch'
        ),
        'patreon' => array(
            'label' => __('Patreon', 'flexpress'),
            'icon' => 'fab fa-patreon'
        ),
        'discord' => array(
            'label' => __('Discord', 'flexpress'),
            'icon' => 'fab fa-discord'
        ),
        'telegram' => array(
            'label' => __('Telegram', 'flexpress'),
            'icon' => 'fab fa-telegram-plane'
        ),
        'whatsapp' => array(
            'label' => __('WhatsApp', 'flexpress'),
            'icon' => 'fab fa-whatsapp'
        )
    );
}

/**
 * Display social media links with icons
 *
 * @param array $args Display options
 */
function flexpress_display_social_media_links($args = array()) {
    $defaults = array(
        'show_icons' => true,
        'show_labels' => false,
        'target' => '_blank',
        'class' => 'social-media-links',
        'item_class' => 'social-link',
        'icon_class' => 'social-icon',
        'platforms' => array(), // Empty array means show all available
        'wrapper' => 'ul',
        'item_wrapper' => 'li'
    );
    
    $args = wp_parse_args($args, $defaults);
    
    $social_links = flexpress_get_all_social_media_links();
    $social_config = flexpress_get_social_media_config();
    
    if (empty($social_links)) {
        return;
    }
    
    // Filter platforms if specified
    if (!empty($args['platforms'])) {
        $social_links = array_intersect_key($social_links, array_flip($args['platforms']));
    }
    
    if (empty($social_links)) {
        return;
    }
    
    // Start wrapper
    if ($args['wrapper']) {
        echo '<' . esc_attr($args['wrapper']) . ' class="' . esc_attr($args['class']) . '">';
    }
    
    foreach ($social_links as $platform => $url) {
        if (!isset($social_config[$platform])) {
            continue;
        }
        
        $config = $social_config[$platform];
        
        // Start item wrapper
        if ($args['item_wrapper']) {
            echo '<' . esc_attr($args['item_wrapper']) . ' class="' . esc_attr($args['item_class']) . ' ' . esc_attr($platform) . '">';
        }
        
        $link_class = isset($args['link_class']) ? ' class="' . esc_attr($args['link_class']) . '"' : '';
        echo '<a href="' . esc_url($url) . '" target="' . esc_attr($args['target']) . '" rel="noopener noreferrer"' . $link_class . '>';
        
        if ($args['show_icons']) {
            echo '<i class="' . esc_attr($config['icon']) . ' ' . esc_attr($args['icon_class']) . '"></i>';
        }
        
        if ($args['show_labels']) {
            echo '<span class="social-label">' . esc_html($config['label']) . '</span>';
        }
        
        echo '</a>';
        
        // End item wrapper
        if ($args['item_wrapper']) {
            echo '</' . esc_attr($args['item_wrapper']) . '>';
        }
    }
    
    // End wrapper
    if ($args['wrapper']) {
        echo '</' . esc_attr($args['wrapper']) . '>';
    }
}

/**
 * Get contact email with mailto link
 *
 * @param string $type Email type (support, contact, billing)
 * @param string $subject Optional email subject
 * @return string Mailto link or empty string if email not set
 */
function flexpress_get_contact_email_link($type, $subject = '') {
    $email = flexpress_get_contact_email($type);
    
    if (empty($email)) {
        return '';
    }
    
    $mailto = 'mailto:' . $email;
    
    if (!empty($subject)) {
        $mailto .= '?subject=' . urlencode($subject);
    }
    
    return $mailto;
}

/**
 * Display contact email link
 *
 * @param string $type Email type (support, contact, billing)
 * @param string $text Link text (defaults to email address)
 * @param string $subject Optional email subject
 * @param array $args Additional link attributes
 */
function flexpress_display_contact_email_link($type, $text = '', $subject = '', $args = array()) {
    $email = flexpress_get_contact_email($type);
    
    if (empty($email)) {
        return;
    }
    
    $mailto = flexpress_get_contact_email_link($type, $subject);
    
    if (empty($text)) {
        $text = $email;
    }
    
    $defaults = array(
        'class' => 'contact-email-link'
    );
    
    $args = wp_parse_args($args, $defaults);
    
    $attributes = '';
    foreach ($args as $attr => $value) {
        $attributes .= ' ' . esc_attr($attr) . '="' . esc_attr($value) . '"';
    }
    
    echo '<a href="' . esc_attr($mailto) . '"' . $attributes . '>' . esc_html($text) . '</a>';
}

/**
 * Check if any contact emails are configured
 *
 * @return bool True if at least one contact email is set
 */
function flexpress_has_contact_emails() {
    $emails = array(
        flexpress_get_support_email(),
        flexpress_get_contact_email_address(),
        flexpress_get_billing_email()
    );
    
    foreach ($emails as $email) {
        if (!empty($email)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Check if any social media links are configured
 *
 * @return bool True if at least one social media link is set
 */
function flexpress_has_social_media_links() {
    $social_links = flexpress_get_all_social_media_links();
    return !empty($social_links);
} 