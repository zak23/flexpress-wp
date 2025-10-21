<?php

/**
 * FlexPress Contact & Social Media Settings
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * FlexPress Contact & Social Media Settings Class
 */
class FlexPress_Contact_Settings
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_contact_settings_page'));
        add_action('admin_init', array($this, 'register_contact_settings'));
    }

    /**
     * Add the contact settings page to admin menu
     */
    public function add_contact_settings_page()
    {
        add_submenu_page(
            'flexpress-settings',
            __('Contact & Social', 'flexpress'),
            __('Contact & Social', 'flexpress'),
            'manage_options',
            'flexpress-contact-settings',
            array($this, 'render_contact_settings_page')
        );
    }

    /**
     * Register settings
     */
    public function register_contact_settings()
    {
        register_setting('flexpress_contact_settings', 'flexpress_contact_settings', array(
            'sanitize_callback' => array($this, 'sanitize_contact_settings')
        ));

        // Business Information Section
        add_settings_section(
            'flexpress_business_info_section',
            __('Business Information', 'flexpress'),
            array($this, 'render_business_info_section'),
            'flexpress_contact_settings'
        );

        // Parent Company
        add_settings_field(
            'parent_company',
            __('Parent Company', 'flexpress'),
            array($this, 'render_parent_company_field'),
            'flexpress_contact_settings',
            'flexpress_business_info_section'
        );

        // Business Number
        add_settings_field(
            'business_number',
            __('Business Number', 'flexpress'),
            array($this, 'render_business_number_field'),
            'flexpress_contact_settings',
            'flexpress_business_info_section'
        );

        // Business Address
        add_settings_field(
            'business_address',
            __('Business Address', 'flexpress'),
            array($this, 'render_business_address_field'),
            'flexpress_contact_settings',
            'flexpress_business_info_section'
        );

        // Contact Email Settings Section
        add_settings_section(
            'flexpress_contact_emails_section',
            __('Contact Email Addresses', 'flexpress'),
            array($this, 'render_contact_emails_section'),
            'flexpress_contact_settings'
        );

        // Support Email
        add_settings_field(
            'support_email',
            __('Support Email', 'flexpress'),
            array($this, 'render_support_email_field'),
            'flexpress_contact_settings',
            'flexpress_contact_emails_section'
        );

        // Contact Email
        add_settings_field(
            'contact_email',
            __('General Contact Email', 'flexpress'),
            array($this, 'render_contact_email_field'),
            'flexpress_contact_settings',
            'flexpress_contact_emails_section'
        );

        // Billing Email
        add_settings_field(
            'billing_email',
            __('Billing Email', 'flexpress'),
            array($this, 'render_billing_email_field'),
            'flexpress_contact_settings',
            'flexpress_contact_emails_section'
        );

        // Social Media Settings Section
        add_settings_section(
            'flexpress_social_media_section',
            __('Social Media Links', 'flexpress'),
            array($this, 'render_social_media_section'),
            'flexpress_contact_settings'
        );

        // Major Social Platforms
        $social_platforms = $this->get_social_platforms();
        foreach ($social_platforms as $platform => $data) {
            add_settings_field(
                'social_' . $platform,
                $data['label'],
                array($this, 'render_social_field'),
                'flexpress_contact_settings',
                'flexpress_social_media_section',
                array('platform' => $platform, 'data' => $data)
            );
        }
    }

    /**
     * Get social media platforms configuration
     */
    private function get_social_platforms()
    {
        return array(
            'facebook' => array(
                'label' => __('Facebook', 'flexpress'),
                'placeholder' => 'https://facebook.com/username',
                'icon' => 'fab fa-facebook-f'
            ),
            'instagram' => array(
                'label' => __('Instagram', 'flexpress'),
                'placeholder' => 'https://instagram.com/username',
                'icon' => 'fab fa-instagram'
            ),
            'twitter' => array(
                'label' => __('Twitter / X', 'flexpress'),
                'placeholder' => 'https://twitter.com/username',
                'icon' => 'fab fa-x-twitter'
            ),
            'youtube' => array(
                'label' => __('YouTube', 'flexpress'),
                'placeholder' => 'https://youtube.com/c/channelname',
                'icon' => 'fab fa-youtube'
            ),
            'tiktok' => array(
                'label' => __('TikTok', 'flexpress'),
                'placeholder' => 'https://tiktok.com/@username',
                'icon' => 'fab fa-tiktok'
            ),
            'linkedin' => array(
                'label' => __('LinkedIn', 'flexpress'),
                'placeholder' => 'https://linkedin.com/company/companyname',
                'icon' => 'fab fa-linkedin-in'
            ),
            'pinterest' => array(
                'label' => __('Pinterest', 'flexpress'),
                'placeholder' => 'https://pinterest.com/username',
                'icon' => 'fab fa-pinterest'
            ),
            'snapchat' => array(
                'label' => __('Snapchat', 'flexpress'),
                'placeholder' => 'https://snapchat.com/add/username',
                'icon' => 'fab fa-snapchat-ghost'
            ),
            'onlyfans' => array(
                'label' => __('OnlyFans', 'flexpress'),
                'placeholder' => 'https://onlyfans.com/username',
                'icon' => 'fas fa-user-circle'
            ),
            'fansly' => array(
                'label' => __('Fansly', 'flexpress'),
                'placeholder' => 'https://fansly.com/username',
                'icon' => 'fas fa-heart'
            ),
            'manyvideos' => array(
                'label' => __('ManyVids', 'flexpress'),
                'placeholder' => 'https://manyvids.com/Profile/username',
                'icon' => 'fas fa-video'
            ),
            'chaturbate' => array(
                'label' => __('Chaturbate', 'flexpress'),
                'placeholder' => 'https://chaturbate.com/username',
                'icon' => 'fas fa-video'
            ),
            'reddit' => array(
                'label' => __('Reddit', 'flexpress'),
                'placeholder' => 'https://reddit.com/r/subreddit',
                'icon' => 'fab fa-reddit-alien'
            ),
            'tumblr' => array(
                'label' => __('Tumblr', 'flexpress'),
                'placeholder' => 'https://username.tumblr.com',
                'icon' => 'fab fa-tumblr'
            ),
            'twitch' => array(
                'label' => __('Twitch', 'flexpress'),
                'placeholder' => 'https://twitch.tv/username',
                'icon' => 'fab fa-twitch'
            ),
            'patreon' => array(
                'label' => __('Patreon', 'flexpress'),
                'placeholder' => 'https://patreon.com/username',
                'icon' => 'fab fa-patreon'
            ),
            'discord' => array(
                'label' => __('Discord', 'flexpress'),
                'placeholder' => 'https://discord.gg/serverinvite',
                'icon' => 'fab fa-discord'
            ),
            'telegram' => array(
                'label' => __('Telegram', 'flexpress'),
                'placeholder' => 'https://t.me/username',
                'icon' => 'fab fa-telegram-plane'
            ),
            'whatsapp' => array(
                'label' => __('WhatsApp', 'flexpress'),
                'placeholder' => 'https://wa.me/1234567890',
                'icon' => 'fab fa-whatsapp'
            )
        );
    }

    /**
     * Sanitize contact settings
     */
    public function sanitize_contact_settings($input)
    {
        $sanitized = array();

        // Sanitize business information
        $business_fields = array('parent_company', 'business_number', 'business_address');
        foreach ($business_fields as $field) {
            if (isset($input[$field])) {
                $sanitized[$field] = sanitize_text_field($input[$field]);
            }
        }

        // Sanitize email addresses
        $email_fields = array('support_email', 'contact_email', 'billing_email');
        foreach ($email_fields as $field) {
            if (isset($input[$field])) {
                $sanitized[$field] = sanitize_email($input[$field]);
            }
        }

        // Sanitize social media URLs
        $social_platforms = array_keys($this->get_social_platforms());
        foreach ($social_platforms as $platform) {
            $field = 'social_' . $platform;
            if (isset($input[$field])) {
                $sanitized[$field] = esc_url_raw($input[$field]);
            }
        }

        return $sanitized;
    }

    /**
     * Render business info section description
     */
    public function render_business_info_section()
    {
        echo '<p>' . esc_html__('Configure your business information including company name, registration number, and address.', 'flexpress') . '</p>';
    }

    /**
     * Render parent company field
     */
    public function render_parent_company_field()
    {
        $options = get_option('flexpress_contact_settings', array());
        $value = isset($options['parent_company']) ? $options['parent_company'] : '';
?>
        <input type="text"
            name="flexpress_contact_settings[parent_company]"
            value="<?php echo esc_attr($value); ?>"
            class="regular-text"
            placeholder="Company Name Pty Ltd">
        <p class="description"><?php esc_html_e('Your parent company or business name.', 'flexpress'); ?></p>
    <?php
    }

    /**
     * Render business number field
     */
    public function render_business_number_field()
    {
        $options = get_option('flexpress_contact_settings', array());
        $value = isset($options['business_number']) ? $options['business_number'] : '';
    ?>
        <input type="text"
            name="flexpress_contact_settings[business_number]"
            value="<?php echo esc_attr($value); ?>"
            class="regular-text"
            placeholder="ABN 12 345 678 901">
        <p class="description"><?php esc_html_e('Your business registration number (ABN, TIN, EIN, etc.).', 'flexpress'); ?></p>
    <?php
    }

    /**
     * Render business address field
     */
    public function render_business_address_field()
    {
        $options = get_option('flexpress_contact_settings', array());
        $value = isset($options['business_address']) ? $options['business_address'] : '';
    ?>
        <textarea name="flexpress_contact_settings[business_address]"
            rows="3"
            class="large-text"
            placeholder="Suite 123, 45 Business Street, City, State, 12345, Country"><?php echo esc_textarea($value); ?></textarea>
        <p class="description"><?php esc_html_e('Your complete business address.', 'flexpress'); ?></p>
    <?php
    }

    /**
     * Render contact emails section description
     */
    public function render_contact_emails_section()
    {
        echo '<p>' . esc_html__('Configure contact email addresses that will be used throughout your site templates.', 'flexpress') . '</p>';
    }

    /**
     * Render social media section description
     */
    public function render_social_media_section()
    {
        echo '<p>' . esc_html__('Add your social media profile links. Leave blank to hide specific platforms.', 'flexpress') . '</p>';
    }

    /**
     * Render support email field
     */
    public function render_support_email_field()
    {
        $options = get_option('flexpress_contact_settings', array());
        $site_domain = parse_url(home_url(), PHP_URL_HOST);
        $default_value = 'support@' . $site_domain;
        $value = isset($options['support_email']) && !empty($options['support_email']) ? $options['support_email'] : $default_value;
    ?>
        <input type="email"
            name="flexpress_contact_settings[support_email]"
            value="<?php echo esc_attr($value); ?>"
            class="regular-text">
        <p class="description"><?php esc_html_e('Email address for customer support inquiries.', 'flexpress'); ?></p>
    <?php
    }

    /**
     * Render contact email field
     */
    public function render_contact_email_field()
    {
        $options = get_option('flexpress_contact_settings', array());
        $site_domain = parse_url(home_url(), PHP_URL_HOST);
        $default_value = 'contact@' . $site_domain;
        $value = isset($options['contact_email']) && !empty($options['contact_email']) ? $options['contact_email'] : $default_value;
    ?>
        <input type="email"
            name="flexpress_contact_settings[contact_email]"
            value="<?php echo esc_attr($value); ?>"
            class="regular-text">
        <p class="description"><?php esc_html_e('General contact email address for inquiries.', 'flexpress'); ?></p>
    <?php
    }

    /**
     * Render billing email field
     */
    public function render_billing_email_field()
    {
        $options = get_option('flexpress_contact_settings', array());
        $site_domain = parse_url(home_url(), PHP_URL_HOST);
        $default_value = 'billing@' . $site_domain;
        $value = isset($options['billing_email']) && !empty($options['billing_email']) ? $options['billing_email'] : $default_value;
    ?>
        <input type="email"
            name="flexpress_contact_settings[billing_email]"
            value="<?php echo esc_attr($value); ?>"
            class="regular-text">
        <p class="description"><?php esc_html_e('Email address for billing and payment related inquiries.', 'flexpress'); ?></p>
    <?php
    }

    /**
     * Render social media field
     */
    public function render_social_field($args)
    {
        $platform = $args['platform'];
        $data = $args['data'];
        $options = get_option('flexpress_contact_settings', array());
        $field_name = 'social_' . $platform;
        $value = isset($options[$field_name]) ? $options[$field_name] : '';
    ?>
        <div style="display: flex; align-items: center; gap: 10px;">
            <i class="<?php echo esc_attr($data['icon']); ?>" style="width: 20px; text-align: center; color: #666;"></i>
            <input type="url"
                name="flexpress_contact_settings[<?php echo esc_attr($field_name); ?>]"
                value="<?php echo esc_attr($value); ?>"
                class="regular-text"
                placeholder="<?php echo esc_attr($data['placeholder']); ?>">
        </div>
    <?php
    }

    /**
     * Render the contact settings page
     */
    public function render_contact_settings_page()
    {
    ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Contact & Social Media Settings', 'flexpress'); ?></h1>

            <form method="post" action="options.php">
                <?php
                settings_fields('flexpress_contact_settings');
                do_settings_sections('flexpress_contact_settings');
                submit_button();
                ?>
            </form>

            <?php if (isset($_GET['regenerated'])): ?>
                <div class="notice notice-<?php echo $_GET['regenerated'] === 'success' ? 'success' : 'error'; ?> is-dismissible">
                    <p>
                        <?php
                        if ($_GET['regenerated'] === 'success') {
                            esc_html_e('Legal page content has been regenerated successfully with your updated contact information.', 'flexpress');
                        } else {
                            esc_html_e('Failed to regenerate legal page content. Please check the error logs.', 'flexpress');
                        }
                        ?>
                    </p>
                </div>
            <?php endif; ?>

            <div class="card" style="max-width: 800px; margin-top: 30px;">
                <h2><?php esc_html_e('Legal Page Content Update', 'flexpress'); ?></h2>
                <p><?php esc_html_e('After updating your contact information, you can regenerate your Privacy Policy and Terms & Conditions pages to include the updated details:', 'flexpress'); ?></p>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-bottom: 20px;">
                    <?php wp_nonce_field('regenerate_legal_content', 'regenerate_legal_nonce'); ?>
                    <input type="hidden" name="action" value="regenerate_legal_content">
                    <input type="hidden" name="page_type" value="both">

                    <input type="submit" class="button button-primary" value="<?php esc_attr_e('Regenerate Legal Pages with Updated Info', 'flexpress'); ?>">
                </form>

                <div class="notice notice-info inline">
                    <p><strong><?php esc_html_e('Note:', 'flexpress'); ?></strong> <?php esc_html_e('This will update the content of your Privacy Policy and Terms & Conditions pages with your current contact information and business details. Any custom edits to these pages will be overwritten.', 'flexpress'); ?></p>
                </div>
            </div>

            <div class="card" style="max-width: 800px; margin-top: 30px;">
                <h2><?php esc_html_e('Usage in Templates', 'flexpress'); ?></h2>
                <p><?php esc_html_e('Use these helper functions in your templates to access the contact and social media settings:', 'flexpress'); ?></p>

                <h3><?php esc_html_e('Business Information', 'flexpress'); ?></h3>
                <code style="display: block; margin: 10px 0; padding: 10px; background: #f5f5f5;">
                    // Get parent company<br>
                    $company = flexpress_get_business_info('parent_company');<br><br>

                    // Get business number<br>
                    $business_number = flexpress_get_business_info('business_number');<br><br>

                    // Get business address<br>
                    $address = flexpress_get_business_info('business_address');<br><br>

                    // Get formatted business info line<br>
                    $business_line = flexpress_get_formatted_business_info();
                </code>

                <h3><?php esc_html_e('Contact Emails', 'flexpress'); ?></h3>
                <code style="display: block; margin: 10px 0; padding: 10px; background: #f5f5f5;">
                    // Get support email<br>
                    $support_email = flexpress_get_contact_email('support');<br><br>

                    // Get general contact email<br>
                    $contact_email = flexpress_get_contact_email('contact');<br><br>

                    // Get billing email<br>
                    $billing_email = flexpress_get_contact_email('billing');
                </code>

                <h3><?php esc_html_e('Social Media Links', 'flexpress'); ?></h3>
                <code style="display: block; margin: 10px 0; padding: 10px; background: #f5f5f5;">
                    // Get specific social media URL<br>
                    $facebook_url = flexpress_get_social_media_url('facebook');<br><br>

                    // Get all social media links<br>
                    $all_social = flexpress_get_all_social_media_links();<br><br>

                    // Display social media links with icons<br>
                    flexpress_display_social_media_links();
                </code>
            </div>
        </div>

        <style>
            .form-table th {
                width: 200px;
            }

            .form-table input[type="email"],
            .form-table input[type="url"] {
                width: 400px;
            }
        </style>
<?php
    }
}

// Initialize the contact settings
new FlexPress_Contact_Settings();
