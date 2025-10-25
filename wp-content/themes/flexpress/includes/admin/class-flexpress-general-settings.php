<?php

/**
 * FlexPress General Settings
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * FlexPress General Settings Class
 */
class FlexPress_General_Settings
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_media_scripts'));
    }

    /**
     * Register general settings
     */
    public function register_settings()
    {
        // Register the settings option with validation
        register_setting(
            'flexpress_general_settings',
            'flexpress_general_settings',
            array(
                'sanitize_callback' => 'flexpress_sanitize_general_settings'
            )
        );

        add_settings_section(
            'flexpress_general_section',
            __('General Settings', 'flexpress'),
            array($this, 'render_section_description'),
            'flexpress_general_settings'
        );

        // Add settings fields here
        add_settings_field(
            'flexpress_site_title',
            __('Site Title', 'flexpress'),
            array($this, 'render_site_title_field'),
            'flexpress_general_settings',
            'flexpress_general_section'
        );

        add_settings_field(
            'flexpress_site_description',
            __('Site Description', 'flexpress'),
            array($this, 'render_site_description_field'),
            'flexpress_general_settings',
            'flexpress_general_section'
        );

        // Add a new section for the logo
        add_settings_section(
            'flexpress_logo_section',
            __('Custom Logo', 'flexpress'),
            array($this, 'render_logo_section_description'),
            'flexpress_general_settings'
        );

        // Add custom logo field
        add_settings_field(
            'flexpress_custom_logo',
            __('Upload Logo', 'flexpress'),
            array($this, 'render_custom_logo_field'),
            'flexpress_general_settings',
            'flexpress_logo_section'
        );

        // Add secondary logo field for different color conditions
        add_settings_field(
            'flexpress_secondary_logo',
            __('Upload Secondary Logo', 'flexpress'),
            array($this, 'render_secondary_logo_field'),
            'flexpress_general_settings',
            'flexpress_logo_section'
        );

        // Add Color Settings section
        add_settings_section(
            'flexpress_color_section',
            __('Color Settings', 'flexpress'),
            array($this, 'render_color_section_description'),
            'flexpress_general_settings'
        );

        // Add accent color field
        add_settings_field(
            'flexpress_accent_color',
            __('Accent Color', 'flexpress'),
            array($this, 'render_accent_color_field'),
            'flexpress_general_settings',
            'flexpress_color_section'
        );

        // Add age verification exit URL field
        add_settings_field(
            'flexpress_age_verification_exit_url',
            __('Age Verification Exit URL', 'flexpress'),
            array($this, 'render_age_verification_exit_url_field'),
            'flexpress_general_settings',
            'flexpress_general_section'
        );

        // Add OnlyFans referral code field
        add_settings_field(
            'flexpress_onlyfans_referral_code',
            __('OnlyFans Referral Code', 'flexpress'),
            array($this, 'render_onlyfans_referral_code_field'),
            'flexpress_general_settings',
            'flexpress_general_section'
        );

        // Add Dolls Downunder Network field
        add_settings_field(
            'flexpress_dolls_downunder_network',
            __('Show Dolls Downunder Network', 'flexpress'),
            array($this, 'render_dolls_downunder_network_field'),
            'flexpress_general_settings',
            'flexpress_general_section'
        );

        // Add Awards Section
        add_settings_section(
            'flexpress_awards_section',
            __('Awards & Recognition', 'flexpress'),
            array($this, 'render_awards_section_description'),
            'flexpress_general_settings'
        );

        // Add awards section enable/disable field
        add_settings_field(
            'flexpress_awards_enabled',
            __('Enable Awards Section', 'flexpress'),
            array($this, 'render_awards_enabled_field'),
            'flexpress_general_settings',
            'flexpress_awards_section'
        );

        // Add awards title field
        add_settings_field(
            'flexpress_awards_title',
            __('Awards Section Title', 'flexpress'),
            array($this, 'render_awards_title_field'),
            'flexpress_general_settings',
            'flexpress_awards_section'
        );

        // Add awards repeater field
        add_settings_field(
            'flexpress_awards_list',
            __('Awards & Recognitions', 'flexpress'),
            array($this, 'render_awards_list_field'),
            'flexpress_general_settings',
            'flexpress_awards_section'
        );

        // Add Featured On section
        add_settings_section(
            'flexpress_featured_on_section',
            __('Featured On Section', 'flexpress'),
            array($this, 'render_featured_on_section_description'),
            'flexpress_general_settings'
        );

        // Add Featured On enable field
        add_settings_field(
            'flexpress_featured_on_enabled',
            __('Enable Featured On Section', 'flexpress'),
            array($this, 'render_featured_on_enabled_field'),
            'flexpress_general_settings',
            'flexpress_featured_on_section'
        );

        // Add Featured On media outlets field
        add_settings_field(
            'flexpress_featured_on_media',
            __('Media Outlets', 'flexpress'),
            array($this, 'render_featured_on_media_field'),
            'flexpress_general_settings',
            'flexpress_featured_on_section'
        );

        // Add Extras Section
        add_settings_section(
            'flexpress_extras_section',
            __('Extras Content', 'flexpress'),
            array($this, 'render_extras_section_description'),
            'flexpress_general_settings'
        );

        // Add Extras enable field
        add_settings_field(
            'flexpress_extras_enabled',
            __('Enable Extras', 'flexpress'),
            array($this, 'render_extras_enabled_field'),
            'flexpress_general_settings',
            'flexpress_extras_section'
        );

        // Add Casting Section
        add_settings_section(
            'flexpress_casting_section',
            __('Casting Section', 'flexpress'),
            array($this, 'render_casting_section_description'),
            'flexpress_general_settings'
        );

        // Add casting image field
        add_settings_field(
            'flexpress_casting_image',
            __('Casting Section Image', 'flexpress'),
            array($this, 'render_casting_image_field'),
            'flexpress_general_settings',
            'flexpress_casting_section'
        );

        // Add Join CTA Section
        add_settings_section(
            'flexpress_join_cta_section',
            __('Join CTA Section', 'flexpress'),
            array($this, 'render_join_cta_section_description'),
            'flexpress_general_settings'
        );

        // Add join CTA image field
        add_settings_field(
            'flexpress_join_cta_image',
            __('Join CTA Image', 'flexpress'),
            array($this, 'render_join_cta_image_field'),
            'flexpress_general_settings',
            'flexpress_join_cta_section'
        );

        // Add Coming Soon Section
        add_settings_section(
            'flexpress_coming_soon_section',
            __('Coming Soon Mode', 'flexpress'),
            array($this, 'render_coming_soon_section_description'),
            'flexpress_general_settings'
        );

        // Add coming soon enabled field
        add_settings_field(
            'flexpress_coming_soon_enabled',
            __('Enable Coming Soon Mode', 'flexpress'),
            array($this, 'render_coming_soon_enabled_field'),
            'flexpress_general_settings',
            'flexpress_coming_soon_section'
        );

        // Add coming soon logo field
        add_settings_field(
            'flexpress_coming_soon_logo',
            __('Coming Soon Logo', 'flexpress'),
            array($this, 'render_coming_soon_logo_field'),
            'flexpress_general_settings',
            'flexpress_coming_soon_section'
        );

        // Add coming soon video URL field
        add_settings_field(
            'flexpress_coming_soon_video_url',
            __('Video ID (Bunny CDN)', 'flexpress'),
            array($this, 'render_coming_soon_video_url_field'),
            'flexpress_general_settings',
            'flexpress_coming_soon_section'
        );

        // Add coming soon fallback image field
        add_settings_field(
            'flexpress_coming_soon_fallback_image',
            __('Thumbnail Image', 'flexpress'),
            array($this, 'render_coming_soon_fallback_image_field'),
            'flexpress_general_settings',
            'flexpress_coming_soon_section'
        );

        // Add coming soon text field
        add_settings_field(
            'flexpress_coming_soon_text',
            __('Coming Soon Text', 'flexpress'),
            array($this, 'render_coming_soon_text_field'),
            'flexpress_general_settings',
            'flexpress_coming_soon_section'
        );

        // Add coming soon links field
        add_settings_field(
            'flexpress_coming_soon_links',
            __('Custom Links', 'flexpress'),
            array($this, 'render_coming_soon_links_field'),
            'flexpress_general_settings',
            'flexpress_coming_soon_section'
        );

        // Add coming soon whitelist field
        add_settings_field(
            'flexpress_coming_soon_whitelist',
            __('Whitelisted Pages', 'flexpress'),
            array($this, 'render_coming_soon_whitelist_field'),
            'flexpress_general_settings',
            'flexpress_coming_soon_section'
        );

        // Add Featured Banner Section
        add_settings_section(
            'flexpress_featured_banner_section',
            __('Featured Banner', 'flexpress'),
            array($this, 'render_featured_banner_section_description'),
            'flexpress_general_settings'
        );

        // Add featured banner enabled field
        add_settings_field(
            'flexpress_featured_banner_enabled',
            __('Enable Featured Banner', 'flexpress'),
            array($this, 'render_featured_banner_enabled_field'),
            'flexpress_general_settings',
            'flexpress_featured_banner_section'
        );

        // Add featured banner image field
        add_settings_field(
            'flexpress_featured_banner_image',
            __('Banner Image', 'flexpress'),
            array($this, 'render_featured_banner_image_field'),
            'flexpress_general_settings',
            'flexpress_featured_banner_section'
        );

        // Add featured banner URL field
        add_settings_field(
            'flexpress_featured_banner_url',
            __('Banner URL', 'flexpress'),
            array($this, 'render_featured_banner_url_field'),
            'flexpress_general_settings',
            'flexpress_featured_banner_section'
        );
    }

    /**
     * Render section description
     */
    public function render_section_description()
    {
        echo '<p>' . esc_html__('Configure general settings for your FlexPress site.', 'flexpress') . '</p>';
    }

    /**
     * Render site title field
     */
    public function render_site_title_field()
    {
        $options = get_option('flexpress_general_settings');
        $value = isset($options['site_title']) ? $options['site_title'] : '';
?>
        <input type="text"
            name="flexpress_general_settings[site_title]"
            value="<?php echo esc_attr($value); ?>"
            class="regular-text">
    <?php
    }

    /**
     * Render site description field
     */
    public function render_site_description_field()
    {
        $options = get_option('flexpress_general_settings');
        $value = isset($options['site_description']) ? $options['site_description'] : '';
    ?>
        <textarea name="flexpress_general_settings[site_description]"
            rows="3"
            class="large-text"><?php echo esc_textarea($value); ?></textarea>
    <?php
    }

    /**
     * Render logo section description
     */
    public function render_logo_section_description()
    {
        echo '<p>' . esc_html__('Upload custom logos for your FlexPress site. You can upload a primary logo and an optional secondary logo for different color conditions (e.g., light logo for dark backgrounds).', 'flexpress') . '</p>';
    }

    /**
     * Render color section description
     */
    public function render_color_section_description()
    {
        echo '<p>' . esc_html__('Customize the color scheme for your FlexPress site. The accent color will be used for buttons, links, and highlights throughout the theme.', 'flexpress') . '</p>';
    }

    /**
     * Render accent color field
     */
    public function render_accent_color_field()
    {
        $options = get_option('flexpress_general_settings');
        $value = isset($options['accent_color']) ? $options['accent_color'] : '#ff69b4';
    ?>
        <input type="color"
            name="flexpress_general_settings[accent_color]"
            value="<?php echo esc_attr($value); ?>"
            class="color-picker">
        <p class="description">
            <?php esc_html_e('Choose an accent color for buttons, links, and important elements. Default is hot pink (#ff69b4).', 'flexpress'); ?>
        </p>
        <div class="accent-color-preview" style="margin-top: 10px;">
            <div style="background-color: <?php echo esc_attr($value); ?>; padding: 10px 20px; border-radius: 4px; display: inline-block; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                Sample Button
            </div>
            <div style="margin-top: 8px; color: <?php echo esc_attr($value); ?>; font-weight: 600;">
                Sample Link Text
            </div>
        </div>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Function to determine if a color is light or dark
                function isLightColor(hex) {
                    // Remove # if present
                    hex = hex.replace('#', '');

                    // Convert to RGB
                    var r = parseInt(hex.substr(0, 2), 16);
                    var g = parseInt(hex.substr(2, 2), 16);
                    var b = parseInt(hex.substr(4, 2), 16);

                    // Calculate luminance
                    var luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;

                    // Return true if light (luminance > 0.5)
                    return luminance > 0.5;
                }

                // Function to update preview colors
                function updatePreview(color) {
                    var textColor = isLightColor(color) ? '#000000' : '#ffffff';
                    $('.accent-color-preview div:first-child').css({
                        'background-color': color,
                        'color': textColor
                    });
                    $('.accent-color-preview div:last-child').css('color', color);
                }

                // Update preview on color change
                $('input[name="flexpress_general_settings[accent_color]"]').on('change', function() {
                    updatePreview($(this).val());
                });

                // Initialize preview with current color
                updatePreview('<?php echo esc_js($value); ?>');
            });
        </script>
    <?php
    }

    /**
     * Render age verification exit URL field
     */
    public function render_age_verification_exit_url_field()
    {
        $options = get_option('flexpress_general_settings');
        $value = isset($options['age_verification_exit_url']) ? $options['age_verification_exit_url'] : 'https://duckduckgo.com';
    ?>
        <input type="url"
            name="flexpress_general_settings[age_verification_exit_url]"
            value="<?php echo esc_attr($value); ?>"
            class="regular-text"
            placeholder="https://example.com">
        <p class="description">
            <?php esc_html_e('URL to redirect users when they click "Exit Site" in the age verification modal. Default is DuckDuckGo.', 'flexpress'); ?>
        </p>
    <?php
    }

    /**
     * Render OnlyFans referral code field
     */
    public function render_onlyfans_referral_code_field()
    {
        $options = get_option('flexpress_general_settings');
        $value = isset($options['onlyfans_referral_code']) ? $options['onlyfans_referral_code'] : '';
    ?>
        <input type="text"
            name="flexpress_general_settings[onlyfans_referral_code]"
            value="<?php echo esc_attr($value); ?>"
            class="regular-text"
            placeholder="17503922">
        <p class="description">
            <?php esc_html_e('Enter your OnlyFans referral code. When set, all OnlyFans links on your site will automatically include ?ref={code} to track referrals.', 'flexpress'); ?>
        </p>
    <?php
    }

    /**
     * Render Dolls Downunder Network field
     */
    public function render_dolls_downunder_network_field()
    {
        $options = get_option('flexpress_general_settings');
        $value = isset($options['dolls_downunder_network']) ? $options['dolls_downunder_network'] : 0;
    ?>
        <label>
            <input type="checkbox"
                name="flexpress_general_settings[dolls_downunder_network]"
                value="1"
                <?php checked($value, 1); ?>>
            <?php esc_html_e('Display "Part of the Dolls Downunder Network" text in footer', 'flexpress'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('When enabled, displays network affiliation text in the footer.', 'flexpress'); ?>
        </p>
    <?php
    }

    /**
     * Render custom logo field
     */
    public function render_custom_logo_field()
    {
        $options = get_option('flexpress_general_settings');
        $logo_id = isset($options['custom_logo']) ? $options['custom_logo'] : '';

        // Display the current logo if it exists
        if (!empty($logo_id)) {
            $logo_url = wp_get_attachment_image_url($logo_id, 'medium');
            if ($logo_url) {
                echo '<div class="flexpress-logo-preview">';
                echo '<img src="' . esc_url($logo_url) . '" style="max-width: 300px; height: auto; margin-bottom: 10px;" />';
                echo '</div>';
            }
        }
    ?>
        <input type="hidden" name="flexpress_general_settings[custom_logo]" id="flexpress_custom_logo" value="<?php echo esc_attr($logo_id); ?>" />
        <input type="button" class="button button-secondary" id="flexpress_upload_logo_button" value="<?php esc_attr_e('Upload Logo', 'flexpress'); ?>" />
        <?php if (!empty($logo_id)) : ?>
            <input type="button" class="button button-secondary" id="flexpress_remove_logo_button" value="<?php esc_attr_e('Remove Logo', 'flexpress'); ?>" />
        <?php endif; ?>
        <p class="description"><?php esc_html_e('Upload a logo to be used instead of the site title. Recommended size: 300x80px.', 'flexpress'); ?></p>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Media uploader
                var mediaUploader;

                $('#flexpress_upload_logo_button').on('click', function(e) {
                    e.preventDefault();

                    // If the media uploader already exists, open it
                    if (mediaUploader) {
                        mediaUploader.open();
                        return;
                    }

                    // Create the media uploader
                    mediaUploader = wp.media({
                        title: '<?php esc_html_e('Select or Upload Logo', 'flexpress'); ?>',
                        button: {
                            text: '<?php esc_html_e('Use this image as logo', 'flexpress'); ?>'
                        },
                        multiple: false
                    });

                    // When an image is selected, run a callback
                    mediaUploader.on('select', function() {
                        var attachment = mediaUploader.state().get('selection').first().toJSON();
                        $('#flexpress_custom_logo').val(attachment.id);

                        // Update preview
                        if (attachment.url) {
                            if ($('.flexpress-logo-preview').length === 0) {
                                $('<div class="flexpress-logo-preview"><img style="max-width: 300px; height: auto; margin-bottom: 10px;" /></div>').insertBefore('#flexpress_upload_logo_button');
                            }
                            $('.flexpress-logo-preview img').attr('src', attachment.url);

                            // Show remove button if not already visible
                            if ($('#flexpress_remove_logo_button').length === 0) {
                                $('<input type="button" class="button button-secondary" id="flexpress_remove_logo_button" value="<?php esc_attr_e('Remove Logo', 'flexpress'); ?>" />').insertAfter('#flexpress_upload_logo_button');
                            }
                        }
                    });

                    // Open the media uploader
                    mediaUploader.open();
                });

                // Handle remove button
                $(document).on('click', '#flexpress_remove_logo_button', function(e) {
                    e.preventDefault();
                    $('#flexpress_custom_logo').val('');
                    $('.flexpress-logo-preview').remove();
                    $(this).remove();
                });
            });
        </script>
    <?php
    }

    /**
     * Render secondary logo field for different color conditions
     */
    public function render_secondary_logo_field()
    {
        $options = get_option('flexpress_general_settings');
        $secondary_logo_id = isset($options['secondary_logo']) ? $options['secondary_logo'] : '';

        // Display the current secondary logo if it exists
        if (!empty($secondary_logo_id)) {
            $logo_url = wp_get_attachment_image_url($secondary_logo_id, 'medium');
            if ($logo_url) {
                echo '<div class="flexpress-secondary-logo-preview">';
                echo '<img src="' . esc_url($logo_url) . '" style="max-width: 300px; height: auto; margin-bottom: 10px;" />';
                echo '</div>';
            }
        }
    ?>
        <input type="hidden" name="flexpress_general_settings[secondary_logo]" id="flexpress_secondary_logo" value="<?php echo esc_attr($secondary_logo_id); ?>" />
        <input type="button" class="button button-secondary" id="flexpress_upload_secondary_logo_button" value="<?php esc_attr_e('Upload Secondary Logo', 'flexpress'); ?>" />
        <?php if (!empty($secondary_logo_id)) : ?>
            <input type="button" class="button button-secondary" id="flexpress_remove_secondary_logo_button" value="<?php esc_attr_e('Remove Secondary Logo', 'flexpress'); ?>" />
        <?php endif; ?>
        <p class="description"><?php esc_html_e('Upload a secondary logo for different color conditions (e.g., light logo for dark backgrounds, dark logo for light backgrounds). This logo will be used when the system detects different color scheme preferences. Recommended size: 300x80px.', 'flexpress'); ?></p>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Media uploader for secondary logo
                var secondaryMediaUploader;

                $('#flexpress_upload_secondary_logo_button').on('click', function(e) {
                    e.preventDefault();

                    // If the media uploader already exists, open it
                    if (secondaryMediaUploader) {
                        secondaryMediaUploader.open();
                        return;
                    }

                    // Create the media uploader
                    secondaryMediaUploader = wp.media({
                        title: '<?php esc_html_e('Select or Upload Secondary Logo', 'flexpress'); ?>',
                        button: {
                            text: '<?php esc_html_e('Use this image as secondary logo', 'flexpress'); ?>'
                        },
                        multiple: false
                    });

                    // When an image is selected, run a callback
                    secondaryMediaUploader.on('select', function() {
                        var attachment = secondaryMediaUploader.state().get('selection').first().toJSON();
                        $('#flexpress_secondary_logo').val(attachment.id);

                        // Update preview
                        if (attachment.url) {
                            if ($('.flexpress-secondary-logo-preview').length === 0) {
                                $('<div class="flexpress-secondary-logo-preview"><img style="max-width: 300px; height: auto; margin-bottom: 10px;" /></div>').insertBefore('#flexpress_upload_secondary_logo_button');
                            }
                            $('.flexpress-secondary-logo-preview img').attr('src', attachment.url);

                            // Show remove button if not already visible
                            if ($('#flexpress_remove_secondary_logo_button').length === 0) {
                                $('<input type="button" class="button button-secondary" id="flexpress_remove_secondary_logo_button" value="<?php esc_attr_e('Remove Secondary Logo', 'flexpress'); ?>" />').insertAfter('#flexpress_upload_secondary_logo_button');
                            }
                        }
                    });

                    // Open the media uploader
                    secondaryMediaUploader.open();
                });

                // Handle remove button for secondary logo
                $(document).on('click', '#flexpress_remove_secondary_logo_button', function(e) {
                    e.preventDefault();
                    $('#flexpress_secondary_logo').val('');
                    $('.flexpress-secondary-logo-preview').remove();
                    $(this).remove();
                });
            });
        </script>
    <?php
    }

    /**
     * Render awards section description
     */
    public function render_awards_section_description()
    {
        echo '<p>' . esc_html__('Configure the Awards & Recognition section that appears on your homepage.', 'flexpress') . '</p>';
    }

    /**
     * Render awards enabled field
     */
    public function render_awards_enabled_field()
    {
        $options = get_option('flexpress_general_settings');
        $value = isset($options['awards_enabled']) ? $options['awards_enabled'] : 0;
    ?>
        <label>
            <input type="checkbox"
                name="flexpress_general_settings[awards_enabled]"
                value="1"
                <?php checked($value, 1); ?>>
            <?php esc_html_e('Show Awards & Recognition section on homepage', 'flexpress'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('Enable or disable the awards section that appears above the footer on your homepage.', 'flexpress'); ?>
        </p>
    <?php
    }

    /**
     * Render awards title field
     */
    public function render_awards_title_field()
    {
        $options = get_option('flexpress_general_settings');
        $value = isset($options['awards_title']) ? $options['awards_title'] : 'Awards & Recognition';
    ?>
        <input type="text"
            name="flexpress_general_settings[awards_title]"
            value="<?php echo esc_attr($value); ?>"
            class="regular-text">
        <p class="description">
            <?php esc_html_e('The title displayed in the awards section. Default: "Awards & Recognition"', 'flexpress'); ?>
        </p>
    <?php
    }

    /**
     * Render awards list field
     */
    public function render_awards_list_field()
    {
        $options = get_option('flexpress_general_settings');
        $awards_list = isset($options['awards_list']) ? $options['awards_list'] : array();

        // Default awards if none exist
        if (empty($awards_list)) {
            $awards_list = array(
                array(
                    'title' => 'AAIA 2024 Winner',
                    'logo_id' => '',
                    'logo_url' => 'https://cdn.dollsdownunder.com/wp-content/themes/dolls_downunder/images/AAIA-2024-Winner-light.png',
                    'link' => 'https://adultawards.com.au/aaia2024-winners/',
                    'alt' => 'AAIA 2024 Winner'
                )
            );
        }
    ?>
        <div id="awards-list-container">
            <p class="description">
                <?php esc_html_e('Add multiple awards and recognitions. Each award can have its own logo and link.', 'flexpress'); ?>
            </p>

            <div id="awards-list">
                <?php foreach ($awards_list as $index => $award): ?>
                    <div class="award-item" data-index="<?php echo $index; ?>">
                        <div class="award-item-header">
                            <h4><?php printf(esc_html__('Award %d', 'flexpress') ?: 'Award %d', $index + 1); ?></h4>
                            <button type="button" class="button button-secondary remove-award" data-index="<?php echo $index; ?>">
                                <?php esc_html_e('Remove', 'flexpress'); ?>
                            </button>
                        </div>

                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="award_title_<?php echo $index; ?>"><?php esc_html_e('Award Title', 'flexpress'); ?></label>
                                </th>
                                <td>
                                    <input type="text"
                                        id="award_title_<?php echo $index; ?>"
                                        name="flexpress_general_settings[awards_list][<?php echo $index; ?>][title]"
                                        value="<?php echo esc_attr($award['title'] ?? ''); ?>"
                                        class="regular-text">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="award_logo_<?php echo $index; ?>"><?php esc_html_e('Award Logo', 'flexpress'); ?></label>
                                </th>
                                <td>
                                    <input type="hidden"
                                        id="award_logo_id_<?php echo $index; ?>"
                                        name="flexpress_general_settings[awards_list][<?php echo $index; ?>][logo_id]"
                                        value="<?php echo esc_attr($award['logo_id'] ?? ''); ?>">

                                    <?php
                                    $logo_url = '';
                                    if (!empty($award['logo_id'])) {
                                        $logo_url = wp_get_attachment_url($award['logo_id']);
                                    } elseif (!empty($award['logo_url'])) {
                                        $logo_url = $award['logo_url'];
                                    }
                                    ?>

                                    <?php if ($logo_url): ?>
                                        <div class="award-logo-preview">
                                            <img src="<?php echo esc_url($logo_url); ?>"
                                                style="max-width: 150px; height: auto; margin-bottom: 10px; border: 1px solid #ddd; padding: 10px;">
                                        </div>
                                    <?php endif; ?>

                                    <input type="button"
                                        class="button upload-award-logo"
                                        data-index="<?php echo $index; ?>"
                                        value="<?php esc_attr_e('Select Logo', 'flexpress'); ?>">

                                    <?php if ($logo_url): ?>
                                        <input type="button"
                                            class="button button-secondary remove-award-logo"
                                            data-index="<?php echo $index; ?>"
                                            value="<?php esc_attr_e('Remove Logo', 'flexpress'); ?>">
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="award_link_<?php echo $index; ?>"><?php esc_html_e('Award Link', 'flexpress'); ?></label>
                                </th>
                                <td>
                                    <input type="url"
                                        id="award_link_<?php echo $index; ?>"
                                        name="flexpress_general_settings[awards_list][<?php echo $index; ?>][link]"
                                        value="<?php echo esc_attr($award['link'] ?? ''); ?>"
                                        class="regular-text"
                                        placeholder="https://example.com/award">
                                    <p class="description">
                                        <?php esc_html_e('Optional: URL to link to when the award logo is clicked.', 'flexpress'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="award_alt_<?php echo $index; ?>"><?php esc_html_e('Alt Text', 'flexpress'); ?></label>
                                </th>
                                <td>
                                    <input type="text"
                                        id="award_alt_<?php echo $index; ?>"
                                        name="flexpress_general_settings[awards_list][<?php echo $index; ?>][alt]"
                                        value="<?php echo esc_attr($award['alt'] ?? ''); ?>"
                                        class="regular-text"
                                        placeholder="<?php echo esc_attr($award['title'] ?? 'Award'); ?>">
                                </td>
                            </tr>
                        </table>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="button" class="button button-primary" id="add-award">
                <?php esc_html_e('Add New Award', 'flexpress'); ?>
            </button>
        </div>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                var awardIndex = <?php echo count($awards_list); ?>;
                var mediaUploaders = {};

                // Add new award
                $('#add-award').on('click', function() {
                    var newAwardHtml = `
                        <div class="award-item" data-index="${awardIndex}">
                            <div class="award-item-header">
                                <h4>Award ${awardIndex + 1}</h4>
                                <button type="button" class="button button-secondary remove-award" data-index="${awardIndex}">
                                    Remove
                                </button>
                            </div>
                            
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="award_title_${awardIndex}">Award Title</label>
                                    </th>
                                    <td>
                                        <input type="text" 
                                               id="award_title_${awardIndex}"
                                               name="flexpress_general_settings[awards_list][${awardIndex}][title]" 
                                               value="" 
                                               class="regular-text">
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="award_logo_${awardIndex}">Award Logo</label>
                                    </th>
                                    <td>
                                        <input type="hidden" 
                                               id="award_logo_id_${awardIndex}"
                                               name="flexpress_general_settings[awards_list][${awardIndex}][logo_id]" 
                                               value="">
                                        
                                        <input type="button" 
                                               class="button upload-award-logo" 
                                               data-index="${awardIndex}"
                                               value="Select Logo">
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="award_link_${awardIndex}">Award Link</label>
                                    </th>
                                    <td>
                                        <input type="url" 
                                               id="award_link_${awardIndex}"
                                               name="flexpress_general_settings[awards_list][${awardIndex}][link]" 
                                               value="" 
                                               class="regular-text"
                                               placeholder="https://example.com/award">
                                        <p class="description">
                                            Optional: URL to link to when the award logo is clicked.
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="award_alt_${awardIndex}">Alt Text</label>
                                    </th>
                                    <td>
                                        <input type="text" 
                                               id="award_alt_${awardIndex}"
                                               name="flexpress_general_settings[awards_list][${awardIndex}][alt]" 
                                               value="" 
                                               class="regular-text"
                                               placeholder="Award">
                                    </td>
                                </tr>
                            </table>
                        </div>
                    `;

                    $('#awards-list').append(newAwardHtml);
                    awardIndex++;
                });

                // Remove award
                $(document).on('click', '.remove-award', function() {
                    $(this).closest('.award-item').remove();
                    updateAwardNumbers();
                });

                // Upload award logo
                $(document).on('click', '.upload-award-logo', function() {
                    var index = $(this).data('index');

                    if (mediaUploaders[index]) {
                        mediaUploaders[index].open();
                        return;
                    }

                    mediaUploaders[index] = wp.media({
                        title: 'Select Award Logo',
                        button: {
                            text: 'Use this image'
                        },
                        multiple: false
                    });

                    mediaUploaders[index].on('select', function() {
                        var attachment = mediaUploaders[index].state().get('selection').first().toJSON();
                        $('#award_logo_id_' + index).val(attachment.id);

                        var previewHtml = `
                            <div class="award-logo-preview">
                                <img src="${attachment.url}" style="max-width: 150px; height: auto; margin-bottom: 10px; border: 1px solid #ddd; padding: 10px;">
                            </div>
                        `;

                        var removeButtonHtml = `
                            <input type="button" 
                                   class="button button-secondary remove-award-logo" 
                                   data-index="${index}"
                                   value="Remove Logo">
                        `;

                        $(this).closest('td').find('.award-logo-preview').remove();
                        $(this).closest('td').find('.upload-award-logo').after(previewHtml);
                        $(this).closest('td').find('.remove-award-logo').remove();
                        $(this).closest('td').find('.award-logo-preview').after(removeButtonHtml);
                    });

                    mediaUploaders[index].open();
                });

                // Remove award logo
                $(document).on('click', '.remove-award-logo', function() {
                    var index = $(this).data('index');
                    $('#award_logo_id_' + index).val('');
                    $(this).closest('td').find('.award-logo-preview').remove();
                    $(this).remove();
                });

                // Update award numbers
                function updateAwardNumbers() {
                    $('#awards-list .award-item').each(function(newIndex) {
                        $(this).attr('data-index', newIndex);
                        $(this).find('h4').text('Award ' + (newIndex + 1));
                        $(this).find('input, button').each(function() {
                            var name = $(this).attr('name');
                            var id = $(this).attr('id');
                            if (name) {
                                $(this).attr('name', name.replace(/\[\d+\]/, '[' + newIndex + ']'));
                            }
                            if (id) {
                                $(this).attr('id', id.replace(/\d+/, newIndex));
                            }
                        });
                    });
                }
            });
        </script>

        <style>
            .award-item {
                border: 1px solid #ddd;
                margin-bottom: 20px;
                padding: 15px;
                background: #f9f9f9;
            }

            .award-item-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 15px;
            }

            .award-item-header h4 {
                margin: 0;
            }
        </style>
    <?php
    }

    /**
     * Render Featured On section description
     */
    public function render_featured_on_section_description()
    {
    ?>
        <p>
            <?php esc_html_e('Configure the "Featured On" section that displays media outlets and publications that have featured your site.', 'flexpress'); ?>
        </p>
    <?php
    }

    /**
     * Render Featured On enabled field
     */
    public function render_featured_on_enabled_field()
    {
        $options = get_option('flexpress_general_settings');
        $value = isset($options['featured_on_enabled']) ? $options['featured_on_enabled'] : '0';
    ?>
        <label>
            <input type="checkbox"
                name="flexpress_general_settings[featured_on_enabled]"
                value="1"
                <?php checked($value, '1'); ?>>
            <?php esc_html_e('Show the Featured On section on the homepage', 'flexpress'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('When enabled, the Featured On section will appear just above the footer on the homepage.', 'flexpress'); ?>
        </p>
    <?php
    }

    /**
     * Render Featured On media outlets field
     */
    public function render_featured_on_media_field()
    {
        $options = get_option('flexpress_general_settings');
        $media_outlets = isset($options['featured_on_media']) ? $options['featured_on_media'] : array();

        // Default media outlets if none are set
        if (empty($media_outlets)) {
            $media_outlets = array(
                array(
                    'name' => 'Aus Adult News',
                    'url' => 'https://ausadultnews.com/',
                    'logo' => 'https://ausadultnews.com/wp-content/uploads/2024/05/Aus-Adult-News-header.png',
                    'alt' => 'Aus Adult News'
                )
            );
        }
    ?>
        <div id="featured-on-media-container">
            <?php foreach ($media_outlets as $index => $outlet): ?>
                <div class="featured-on-media-item" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; background: #f9f9f9;">
                    <h4><?php echo sprintf(__('Media Outlet %d', 'flexpress') ?: 'Media Outlet %d', $index + 1); ?></h4>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="featured_on_media_<?php echo $index; ?>_name"><?php esc_html_e('Name', 'flexpress'); ?></label>
                            </th>
                            <td>
                                <input type="text"
                                    id="featured_on_media_<?php echo $index; ?>_name"
                                    name="flexpress_general_settings[featured_on_media][<?php echo $index; ?>][name]"
                                    value="<?php echo esc_attr($outlet['name']); ?>"
                                    class="regular-text"
                                    placeholder="<?php esc_attr_e('Media Outlet Name', 'flexpress'); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="featured_on_media_<?php echo $index; ?>_url"><?php esc_html_e('URL', 'flexpress'); ?></label>
                            </th>
                            <td>
                                <input type="url"
                                    id="featured_on_media_<?php echo $index; ?>_url"
                                    name="flexpress_general_settings[featured_on_media][<?php echo $index; ?>][url]"
                                    value="<?php echo esc_attr($outlet['url']); ?>"
                                    class="regular-text"
                                    placeholder="https://example.com">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="featured_on_media_<?php echo $index; ?>_logo"><?php esc_html_e('Logo', 'flexpress'); ?></label>
                            </th>
                            <td>
                                <div class="featured-on-logo-upload">
                                    <?php if (!empty($outlet['logo_id'])):
                                        $logo_url = wp_get_attachment_url($outlet['logo_id']);
                                        $logo_alt = get_post_meta($outlet['logo_id'], '_wp_attachment_image_alt', true);
                                    ?>
                                        <div class="logo-preview" style="margin-bottom: 10px;">
                                            <img src="<?php echo esc_url($logo_url); ?>"
                                                style="max-height: 60px; max-width: 200px; object-fit: contain;"
                                                alt="<?php echo esc_attr($logo_alt); ?>">
                                        </div>
                                        <input type="hidden"
                                            name="flexpress_general_settings[featured_on_media][<?php echo $index; ?>][logo_id]"
                                            value="<?php echo esc_attr($outlet['logo_id']); ?>">
                                        <button type="button" class="button remove-logo" data-index="<?php echo $index; ?>">
                                            <?php esc_html_e('Remove Logo', 'flexpress'); ?>
                                        </button>
                                    <?php else: ?>
                                        <input type="hidden"
                                            name="flexpress_general_settings[featured_on_media][<?php echo $index; ?>][logo_id]"
                                            value="">
                                        <button type="button" class="button upload-logo" data-index="<?php echo $index; ?>">
                                            <?php esc_html_e('Upload Logo', 'flexpress'); ?>
                                        </button>
                                    <?php endif; ?>

                                    <!-- Fallback URL field for external logos -->
                                    <div style="margin-top: 10px;">
                                        <label for="featured_on_media_<?php echo $index; ?>_logo_url">
                                            <?php esc_html_e('Or enter logo URL:', 'flexpress'); ?>
                                        </label>
                                        <input type="url"
                                            id="featured_on_media_<?php echo $index; ?>_logo_url"
                                            name="flexpress_general_settings[featured_on_media][<?php echo $index; ?>][logo]"
                                            value="<?php echo esc_attr($outlet['logo']); ?>"
                                            class="regular-text"
                                            placeholder="https://example.com/logo.png">
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="featured_on_media_<?php echo $index; ?>_alt"><?php esc_html_e('Alt Text', 'flexpress'); ?></label>
                            </th>
                            <td>
                                <input type="text"
                                    id="featured_on_media_<?php echo $index; ?>_alt"
                                    name="flexpress_general_settings[featured_on_media][<?php echo $index; ?>][alt]"
                                    value="<?php echo esc_attr($outlet['alt']); ?>"
                                    class="regular-text"
                                    placeholder="<?php esc_attr_e('Alt text for logo', 'flexpress'); ?>">
                            </td>
                        </tr>
                    </table>

                    <button type="button" class="button remove-featured-on-media" style="color: #a00;">
                        <?php esc_html_e('Remove This Media Outlet', 'flexpress'); ?>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>

        <button type="button" id="add-featured-on-media" class="button button-secondary">
            <?php esc_html_e('Add Media Outlet', 'flexpress'); ?>
        </button>

        <p class="description">
            <?php esc_html_e('Add media outlets and publications that have featured your site. Each outlet will appear as a slide in the Featured On carousel.', 'flexpress'); ?>
        </p>

        <script>
            jQuery(document).ready(function($) {
                var mediaIndex = <?php echo count($media_outlets); ?>;
                var mediaUploader;

                $('#add-featured-on-media').on('click', function() {
                    var newItem = '<div class="featured-on-media-item" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; background: #f9f9f9;">' +
                        '<h4><?php esc_html_e('Media Outlet', 'flexpress'); ?> ' + (mediaIndex + 1) + '</h4>' +
                        '<table class="form-table">' +
                        '<tr>' +
                        '<th scope="row"><label><?php esc_html_e('Name', 'flexpress'); ?></label></th>' +
                        '<td><input type="text" name="flexpress_general_settings[featured_on_media][' + mediaIndex + '][name]" class="regular-text" placeholder="<?php esc_attr_e('Media Outlet Name', 'flexpress'); ?>"></td>' +
                        '</tr>' +
                        '<tr>' +
                        '<th scope="row"><label><?php esc_html_e('URL', 'flexpress'); ?></label></th>' +
                        '<td><input type="url" name="flexpress_general_settings[featured_on_media][' + mediaIndex + '][url]" class="regular-text" placeholder="https://example.com"></td>' +
                        '</tr>' +
                        '<tr>' +
                        '<th scope="row"><label><?php esc_html_e('Logo', 'flexpress'); ?></label></th>' +
                        '<td>' +
                        '<div class="featured-on-logo-upload">' +
                        '<input type="hidden" name="flexpress_general_settings[featured_on_media][' + mediaIndex + '][logo_id]" value="">' +
                        '<button type="button" class="button upload-logo" data-index="' + mediaIndex + '"><?php esc_html_e('Upload Logo', 'flexpress'); ?></button>' +
                        '<div style="margin-top: 10px;">' +
                        '<label><?php esc_html_e('Or enter logo URL:', 'flexpress'); ?></label>' +
                        '<input type="url" name="flexpress_general_settings[featured_on_media][' + mediaIndex + '][logo]" class="regular-text" placeholder="https://example.com/logo.png">' +
                        '</div>' +
                        '</div>' +
                        '</td>' +
                        '</tr>' +
                        '<tr>' +
                        '<th scope="row"><label><?php esc_html_e('Alt Text', 'flexpress'); ?></label></th>' +
                        '<td><input type="text" name="flexpress_general_settings[featured_on_media][' + mediaIndex + '][alt]" class="regular-text" placeholder="<?php esc_attr_e('Alt text for logo', 'flexpress'); ?>"></td>' +
                        '</tr>' +
                        '</table>' +
                        '<button type="button" class="button remove-featured-on-media" style="color: #a00;"><?php esc_html_e('Remove This Media Outlet', 'flexpress'); ?></button>' +
                        '</div>';

                    $('#featured-on-media-container').append(newItem);
                    mediaIndex++;
                });

                $(document).on('click', '.remove-featured-on-media', function() {
                    $(this).closest('.featured-on-media-item').remove();
                });

                // Handle logo upload
                $(document).on('click', '.upload-logo', function(e) {
                    e.preventDefault();

                    var button = $(this);
                    var index = button.data('index');

                    if (mediaUploader) {
                        mediaUploader.open();
                        return;
                    }

                    mediaUploader = wp.media({
                        title: '<?php esc_html_e('Choose Logo', 'flexpress'); ?>',
                        button: {
                            text: '<?php esc_html_e('Use This Logo', 'flexpress'); ?>'
                        },
                        multiple: false,
                        library: {
                            type: 'image'
                        }
                    });

                    mediaUploader.on('select', function() {
                        var attachment = mediaUploader.state().get('selection').first().toJSON();

                        var logoPreview = '<div class="logo-preview" style="margin-bottom: 10px;">' +
                            '<img src="' + attachment.url + '" style="max-height: 60px; max-width: 200px; object-fit: contain;" alt="' + attachment.alt + '">' +
                            '</div>';

                        button.closest('.featured-on-logo-upload').html(
                            logoPreview +
                            '<input type="hidden" name="flexpress_general_settings[featured_on_media][' + index + '][logo_id]" value="' + attachment.id + '">' +
                            '<button type="button" class="button remove-logo" data-index="' + index + '"><?php esc_html_e('Remove Logo', 'flexpress'); ?></button>' +
                            '<div style="margin-top: 10px;">' +
                            '<label><?php esc_html_e('Or enter logo URL:', 'flexpress'); ?></label>' +
                            '<input type="url" name="flexpress_general_settings[featured_on_media][' + index + '][logo]" class="regular-text" placeholder="https://example.com/logo.png">' +
                            '</div>'
                        );
                    });

                    mediaUploader.open();
                });

                // Handle logo removal
                $(document).on('click', '.remove-logo', function(e) {
                    e.preventDefault();

                    var index = $(this).data('index');
                    var container = $(this).closest('.featured-on-logo-upload');

                    container.html(
                        '<input type="hidden" name="flexpress_general_settings[featured_on_media][' + index + '][logo_id]" value="">' +
                        '<button type="button" class="button upload-logo" data-index="' + index + '"><?php esc_html_e('Upload Logo', 'flexpress'); ?></button>' +
                        '<div style="margin-top: 10px;">' +
                        '<label><?php esc_html_e('Or enter logo URL:', 'flexpress'); ?></label>' +
                        '<input type="url" name="flexpress_general_settings[featured_on_media][' + index + '][logo]" class="regular-text" placeholder="https://example.com/logo.png">' +
                        '</div>'
                    );
                });
            });
        </script>
    <?php
    }

    /**
     * Render Extras section description
     */
    public function render_extras_section_description()
    {
    ?>
        <p>
            <?php esc_html_e('Configure the Extras/BTS (Behind the Scenes) content system. Extras can include galleries, behind-the-scenes videos, bloopers, interviews, and other bonus content.', 'flexpress'); ?>
        </p>
    <?php
    }

    /**
     * Render Extras enabled field
     */
    public function render_extras_enabled_field()
    {
        $options = get_option('flexpress_general_settings');
        $value = isset($options['extras_enabled']) ? $options['extras_enabled'] : '0';
    ?>
        <label>
            <input type="checkbox"
                name="flexpress_general_settings[extras_enabled]"
                value="1"
                <?php checked($value, '1'); ?>>
            <?php esc_html_e('Enable Extras/BTS content system', 'flexpress'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('When enabled, you can create and manage Extras content including galleries, behind-the-scenes videos, bloopers, interviews, and other bonus content. The Extras post type will be available in the admin menu.', 'flexpress'); ?>
        </p>
    <?php
    }

    /**
     * Render casting section description
     */
    public function render_casting_section_description()
    {
    ?>
        <p>
            <?php esc_html_e('Configure the casting section image that appears on the homepage and casting page.', 'flexpress'); ?>
        </p>
    <?php
    }

    /**
     * Render casting image field
     */
    public function render_casting_image_field()
    {
        $options = get_option('flexpress_general_settings');
        $casting_image_id = isset($options['casting_image']) ? $options['casting_image'] : '';


        // Display the current image if it exists
        if (!empty($casting_image_id)) {
            $image_url = wp_get_attachment_image_url($casting_image_id, 'medium');
            if ($image_url) {
                echo '<div class="flexpress-casting-image-preview">';
                echo '<img src="' . esc_url($image_url) . '" style="max-width: 300px; height: auto; margin-bottom: 10px;" />';
                echo '</div>';
            }
        } else {
        }
    ?>
        <input type="hidden" name="flexpress_general_settings[casting_image]" id="flexpress_casting_image" value="<?php echo esc_attr($casting_image_id); ?>" />
        <input type="button" class="button button-secondary" id="flexpress_upload_casting_image_button" value="<?php esc_attr_e('Upload Casting Image', 'flexpress'); ?>" />
        <?php if (!empty($casting_image_id)) : ?>
            <input type="button" class="button button-secondary" id="flexpress_remove_casting_image_button" value="<?php esc_attr_e('Remove Image', 'flexpress'); ?>" />
        <?php endif; ?>
        <p class="description"><?php esc_html_e('Upload an image for the casting section. This image will be displayed on both the homepage and casting page. Recommended size: 600x400px.', 'flexpress'); ?></p>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Media uploader for casting image
                var castingMediaUploader;

                $('#flexpress_upload_casting_image_button').on('click', function(e) {
                    e.preventDefault();

                    // If the media uploader already exists, open it
                    if (castingMediaUploader) {
                        castingMediaUploader.open();
                        return;
                    }

                    // Create the media uploader
                    castingMediaUploader = wp.media({
                        title: '<?php esc_html_e('Select or Upload Casting Image', 'flexpress'); ?>',
                        button: {
                            text: '<?php esc_html_e('Use this image', 'flexpress'); ?>'
                        },
                        multiple: false
                    });

                    // When an image is selected, run a callback
                    castingMediaUploader.on('select', function() {
                        var attachment = castingMediaUploader.state().get('selection').first().toJSON();
                        $('#flexpress_casting_image').val(attachment.id);

                        // Update preview
                        if (attachment.url) {
                            if ($('.flexpress-casting-image-preview').length === 0) {
                                $('<div class="flexpress-casting-image-preview"><img style="max-width: 300px; height: auto; margin-bottom: 10px;" /></div>').insertBefore('#flexpress_upload_casting_image_button');
                            }
                            $('.flexpress-casting-image-preview img').attr('src', attachment.url);

                            // Show remove button if not already visible
                            if ($('#flexpress_remove_casting_image_button').length === 0) {
                                $('<input type="button" class="button button-secondary" id="flexpress_remove_casting_image_button" value="<?php esc_attr_e('Remove Image', 'flexpress'); ?>" />').insertAfter('#flexpress_upload_casting_image_button');
                            }
                        }
                    });

                    // Open the media uploader
                    castingMediaUploader.open();
                });

                // Handle remove button
                $(document).on('click', '#flexpress_remove_casting_image_button', function(e) {
                    e.preventDefault();
                    $('#flexpress_casting_image').val('');
                    $('.flexpress-casting-image-preview').remove();
                    $(this).remove();
                });
            });
        </script>
    <?php
    }

    /**
     * Render join CTA section description
     */
    public function render_join_cta_section_description()
    {
    ?>
        <p>
            <?php esc_html_e('Configure the image for the Join Now call-to-action section that appears on the homepage.', 'flexpress'); ?>
        </p>
    <?php
    }

    /**
     * Render join CTA image field
     */
    public function render_join_cta_image_field()
    {
        $options = get_option('flexpress_general_settings');
        $join_cta_image_id = isset($options['join_cta_image']) ? $options['join_cta_image'] : '';


        // Display the current image if it exists
        if (!empty($join_cta_image_id)) {
            $image_url = wp_get_attachment_image_url($join_cta_image_id, 'medium');
            if ($image_url) {
                echo '<div class="flexpress-join-cta-image-preview">';
                echo '<img src="' . esc_url($image_url) . '" style="max-width: 300px; height: auto; margin-bottom: 10px;" />';
                echo '</div>';
            }
        } else {
        }
    ?>
        <input type="hidden" name="flexpress_general_settings[join_cta_image]" id="flexpress_join_cta_image" value="<?php echo esc_attr($join_cta_image_id); ?>" />
        <input type="button" class="button button-secondary" id="flexpress_upload_join_cta_image_button" value="<?php esc_attr_e('Upload Join CTA Image', 'flexpress'); ?>" />
        <?php if (!empty($join_cta_image_id)) : ?>
            <input type="button" class="button button-secondary" id="flexpress_remove_join_cta_image_button" value="<?php esc_attr_e('Remove Image', 'flexpress'); ?>" />
        <?php endif; ?>
        <p class="description"><?php esc_html_e('Upload an image for the Join Now call-to-action section. This image will be displayed on the homepage. Recommended size: 800x1200px (portrait orientation).', 'flexpress'); ?></p>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Media uploader for join CTA image
                var joinCtaMediaUploader;

                $('#flexpress_upload_join_cta_image_button').on('click', function(e) {
                    e.preventDefault();

                    // If the media uploader already exists, open it
                    if (joinCtaMediaUploader) {
                        joinCtaMediaUploader.open();
                        return;
                    }

                    // Create the media uploader
                    joinCtaMediaUploader = wp.media({
                        title: '<?php esc_html_e('Select or Upload Join CTA Image', 'flexpress'); ?>',
                        button: {
                            text: '<?php esc_html_e('Use this image', 'flexpress'); ?>'
                        },
                        multiple: false
                    });

                    // When an image is selected, run a callback
                    joinCtaMediaUploader.on('select', function() {
                        var attachment = joinCtaMediaUploader.state().get('selection').first().toJSON();
                        $('#flexpress_join_cta_image').val(attachment.id);

                        // Update preview
                        if (attachment.url) {
                            if ($('.flexpress-join-cta-image-preview').length === 0) {
                                $('<div class="flexpress-join-cta-image-preview"><img style="max-width: 300px; height: auto; margin-bottom: 10px;" /></div>').insertBefore('#flexpress_upload_join_cta_image_button');
                            }
                            $('.flexpress-join-cta-image-preview img').attr('src', attachment.url);

                            // Show remove button if not already visible
                            if ($('#flexpress_remove_join_cta_image_button').length === 0) {
                                $('<input type="button" class="button button-secondary" id="flexpress_remove_join_cta_image_button" value="<?php esc_attr_e('Remove Image', 'flexpress'); ?>" />').insertAfter('#flexpress_upload_join_cta_image_button');
                            }
                        }
                    });

                    // Open the media uploader
                    joinCtaMediaUploader.open();
                });

                // Handle remove button
                $(document).on('click', '#flexpress_remove_join_cta_image_button', function(e) {
                    e.preventDefault();
                    $('#flexpress_join_cta_image').val('');
                    $('.flexpress-join-cta-image-preview').remove();
                    $(this).remove();
                });
            });
        </script>
    <?php
    }

    /**
     * Enqueue media scripts on settings page
     */
    public function enqueue_media_scripts($hook)
    {
        if (strpos($hook, 'flexpress-settings') !== false) {
            wp_enqueue_media();
        }
    }

    /**
     * Render Coming Soon section description
     */
    public function render_coming_soon_section_description()
    {
    ?>
        <p>
            <?php esc_html_e('Configure the Coming Soon mode that displays a landing page with logo, video, and custom links. Administrators will automatically bypass this and see the normal site.', 'flexpress'); ?>
        </p>
    <?php
    }

    /**
     * Render Coming Soon enabled field
     */
    public function render_coming_soon_enabled_field()
    {
        $options = get_option('flexpress_general_settings');
        $value = isset($options['coming_soon_enabled']) ? $options['coming_soon_enabled'] : 0;
    ?>
        <label>
            <input type="checkbox"
                name="flexpress_general_settings[coming_soon_enabled]"
                value="1"
                <?php checked($value, 1); ?>>
            <?php esc_html_e('Enable Coming Soon Mode', 'flexpress'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('When enabled, visitors will see the coming soon page. Administrators will automatically bypass this.', 'flexpress'); ?>
        </p>
    <?php
    }

    /**
     * Render Coming Soon logo field
     */
    public function render_coming_soon_logo_field()
    {
        $options = get_option('flexpress_general_settings');
        $logo_id = isset($options['coming_soon_logo']) ? $options['coming_soon_logo'] : '';

        // Display the current logo if it exists
        if (!empty($logo_id)) {
            $logo_url = wp_get_attachment_image_url($logo_id, 'medium');
            if ($logo_url) {
                echo '<div class="flexpress-coming-soon-logo-preview">';
                echo '<img src="' . esc_url($logo_url) . '" style="max-width: 300px; height: auto; margin-bottom: 10px;" />';
                echo '</div>';
            }
        }
    ?>
        <input type="hidden" name="flexpress_general_settings[coming_soon_logo]" id="flexpress_coming_soon_logo" value="<?php echo esc_attr($logo_id); ?>" />
        <input type="button" class="button button-secondary" id="flexpress_upload_coming_soon_logo_button" value="<?php esc_attr_e('Upload Logo', 'flexpress'); ?>" />
        <?php if (!empty($logo_id)) : ?>
            <input type="button" class="button button-secondary" id="flexpress_remove_coming_soon_logo_button" value="<?php esc_attr_e('Remove Logo', 'flexpress'); ?>" />
        <?php endif; ?>
        <p class="description"><?php esc_html_e('Upload a logo for the coming soon page. If not set, will use the site logo. Recommended size: 300x100px.', 'flexpress'); ?></p>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Media uploader for coming soon logo
                var comingSoonMediaUploader;

                $('#flexpress_upload_coming_soon_logo_button').on('click', function(e) {
                    e.preventDefault();

                    // If the media uploader already exists, open it
                    if (comingSoonMediaUploader) {
                        comingSoonMediaUploader.open();
                        return;
                    }

                    // Create the media uploader
                    comingSoonMediaUploader = wp.media({
                        title: '<?php esc_html_e('Select or Upload Coming Soon Logo', 'flexpress'); ?>',
                        button: {
                            text: '<?php esc_html_e('Use this image as logo', 'flexpress'); ?>'
                        },
                        multiple: false
                    });

                    // When an image is selected, run a callback
                    comingSoonMediaUploader.on('select', function() {
                        var attachment = comingSoonMediaUploader.state().get('selection').first().toJSON();
                        $('#flexpress_coming_soon_logo').val(attachment.id);

                        // Update preview
                        if (attachment.url) {
                            if ($('.flexpress-coming-soon-logo-preview').length === 0) {
                                $('<div class="flexpress-coming-soon-logo-preview"><img style="max-width: 300px; height: auto; margin-bottom: 10px;" /></div>').insertBefore('#flexpress_upload_coming_soon_logo_button');
                            }
                            $('.flexpress-coming-soon-logo-preview img').attr('src', attachment.url);

                            // Show remove button if not already visible
                            if ($('#flexpress_remove_coming_soon_logo_button').length === 0) {
                                $('<input type="button" class="button button-secondary" id="flexpress_remove_coming_soon_logo_button" value="<?php esc_attr_e('Remove Logo', 'flexpress'); ?>" />').insertAfter('#flexpress_upload_coming_soon_logo_button');
                            }
                        }
                    });

                    // Open the media uploader
                    comingSoonMediaUploader.open();
                });

                // Handle remove button
                $(document).on('click', '#flexpress_remove_coming_soon_logo_button', function(e) {
                    e.preventDefault();
                    $('#flexpress_coming_soon_logo').val('');
                    $('.flexpress-coming-soon-logo-preview').remove();
                    $(this).remove();
                });
            });
        </script>
    <?php
    }

    /**
     * Render Coming Soon video URL field
     */
    public function render_coming_soon_video_url_field()
    {
        $options = get_option('flexpress_general_settings');
        $value = isset($options['coming_soon_video_url']) ? $options['coming_soon_video_url'] : '';
    ?>
        <input type="text"
            name="flexpress_general_settings[coming_soon_video_url]"
            value="<?php echo esc_attr($value); ?>"
            class="regular-text"
            placeholder="123456">
        <p class="description">
            <?php esc_html_e('Enter the Bunny CDN video ID (just the number). The video will autoplay on page load with thumbnail fallback.', 'flexpress'); ?>
        </p>
    <?php
    }

    /**
     * Render Coming Soon fallback image field
     */
    public function render_coming_soon_fallback_image_field()
    {
        $options = get_option('flexpress_general_settings');
        $image_id = isset($options['coming_soon_fallback_image']) ? $options['coming_soon_fallback_image'] : '';

        // Display the current image if it exists
        if (!empty($image_id)) {
            $image_url = wp_get_attachment_image_url($image_id, 'medium');
            if ($image_url) {
                echo '<div class="flexpress-coming-soon-fallback-preview">';
                echo '<img src="' . esc_url($image_url) . '" style="max-width: 300px; height: auto; margin-bottom: 10px;" />';
                echo '</div>';
            }
        }
    ?>
        <input type="hidden" name="flexpress_general_settings[coming_soon_fallback_image]" id="flexpress_coming_soon_fallback_image" value="<?php echo esc_attr($image_id); ?>" />
        <input type="button" class="button button-secondary" id="flexpress_upload_coming_soon_fallback_button" value="<?php esc_attr_e('Upload Thumbnail Image', 'flexpress'); ?>" />
        <?php if (!empty($image_id)) : ?>
            <input type="button" class="button button-secondary" id="flexpress_remove_coming_soon_fallback_button" value="<?php esc_attr_e('Remove Image', 'flexpress'); ?>" />
        <?php endif; ?>
        <p class="description"><?php esc_html_e('Upload a thumbnail image that shows while the video loads. If video fails to load, this image will remain visible. Recommended size: 1920x1080px.', 'flexpress'); ?></p>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Media uploader for fallback image
                var fallbackMediaUploader;

                $('#flexpress_upload_coming_soon_fallback_button').on('click', function(e) {
                    e.preventDefault();

                    // If the media uploader already exists, open it
                    if (fallbackMediaUploader) {
                        fallbackMediaUploader.open();
                        return;
                    }

                    // Create the media uploader
                    fallbackMediaUploader = wp.media({
                        title: '<?php esc_html_e('Select or Upload Fallback Image', 'flexpress'); ?>',
                        button: {
                            text: '<?php esc_html_e('Use this image', 'flexpress'); ?>'
                        },
                        multiple: false
                    });

                    // When an image is selected, run a callback
                    fallbackMediaUploader.on('select', function() {
                        var attachment = fallbackMediaUploader.state().get('selection').first().toJSON();
                        $('#flexpress_coming_soon_fallback_image').val(attachment.id);

                        // Update preview
                        if (attachment.url) {
                            if ($('.flexpress-coming-soon-fallback-preview').length === 0) {
                                $('<div class="flexpress-coming-soon-fallback-preview"><img style="max-width: 300px; height: auto; margin-bottom: 10px;" /></div>').insertBefore('#flexpress_upload_coming_soon_fallback_button');
                            }
                            $('.flexpress-coming-soon-fallback-preview img').attr('src', attachment.url);

                            // Show remove button if not already visible
                            if ($('#flexpress_remove_coming_soon_fallback_button').length === 0) {
                                $('<input type="button" class="button button-secondary" id="flexpress_remove_coming_soon_fallback_button" value="<?php esc_attr_e('Remove Image', 'flexpress'); ?>" />').insertAfter('#flexpress_upload_coming_soon_fallback_button');
                            }
                        }
                    });

                    // Open the media uploader
                    fallbackMediaUploader.open();
                });

                // Handle remove button
                $(document).on('click', '#flexpress_remove_coming_soon_fallback_button', function(e) {
                    e.preventDefault();
                    $('#flexpress_coming_soon_fallback_image').val('');
                    $('.flexpress-coming-soon-fallback-preview').remove();
                    $(this).remove();
                });
            });
        </script>
    <?php
    }

    /**
     * Render Coming Soon text field
     */
    public function render_coming_soon_text_field()
    {
        $options = get_option('flexpress_general_settings');
        $value = isset($options['coming_soon_text']) ? $options['coming_soon_text'] : 'Coming Soon';
    ?>
        <input type="text"
            name="flexpress_general_settings[coming_soon_text]"
            value="<?php echo esc_attr($value); ?>"
            class="regular-text">
        <p class="description">
            <?php esc_html_e('The main text displayed on the coming soon page. Default: "Coming Soon"', 'flexpress'); ?>
        </p>
    <?php
    }

    /**
     * Render Coming Soon links field
     */
    public function render_coming_soon_links_field()
    {
        $options = get_option('flexpress_general_settings');
        $links_list = isset($options['coming_soon_links']) ? $options['coming_soon_links'] : array();

        // Default links if none exist
        if (empty($links_list)) {
            $links_list = array(
                array(
                    'title' => 'Get Notified',
                    'url' => '#newsletter-modal',
                    'new_tab' => 0
                ),
                array(
                    'title' => 'Work with Us',
                    'url' => 'contact',
                    'new_tab' => 0
                )
            );
        }
    ?>
        <div id="coming-soon-links-container">
            <p class="description">
                <?php esc_html_e('Add custom links that will appear on the coming soon page.', 'flexpress'); ?>
            </p>

            <div id="coming-soon-links">
                <?php foreach ($links_list as $index => $link): ?>
                    <div class="coming-soon-link-item" data-index="<?php echo $index; ?>">
                        <div class="coming-soon-link-header">
                            <h4><?php printf(esc_html__('Link %d', 'flexpress') ?: 'Link %d', $index + 1); ?></h4>
                            <button type="button" class="button button-secondary remove-coming-soon-link" data-index="<?php echo $index; ?>">
                                <?php esc_html_e('Remove', 'flexpress'); ?>
                            </button>
                        </div>

                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="coming_soon_link_title_<?php echo $index; ?>"><?php esc_html_e('Link Title', 'flexpress'); ?></label>
                                </th>
                                <td>
                                    <input type="text"
                                        id="coming_soon_link_title_<?php echo $index; ?>"
                                        name="flexpress_general_settings[coming_soon_links][<?php echo $index; ?>][title]"
                                        value="<?php echo esc_attr($link['title'] ?? ''); ?>"
                                        class="regular-text">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="coming_soon_link_url_<?php echo $index; ?>"><?php esc_html_e('Link URL', 'flexpress'); ?></label>
                                </th>
                                <td>
                                    <input type="text"
                                        id="coming_soon_link_url_<?php echo $index; ?>"
                                        name="flexpress_general_settings[coming_soon_links][<?php echo $index; ?>][url]"
                                        value="<?php echo esc_attr($link['url'] ?? ''); ?>"
                                        class="regular-text"
                                        placeholder="Enter link (e.g., /page, #modal, https://example.com)">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="coming_soon_link_new_tab_<?php echo $index; ?>"><?php esc_html_e('Open in New Tab', 'flexpress'); ?></label>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox"
                                            id="coming_soon_link_new_tab_<?php echo $index; ?>"
                                            name="flexpress_general_settings[coming_soon_links][<?php echo $index; ?>][new_tab]"
                                            value="1"
                                            <?php checked($link['new_tab'] ?? 0, 1); ?>>
                                        <?php esc_html_e('Open link in new tab', 'flexpress'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="button" class="button button-primary" id="add-coming-soon-link">
                <?php esc_html_e('Add New Link', 'flexpress'); ?>
            </button>
        </div>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                var linkIndex = <?php echo count($links_list); ?>;

                // Add new link
                $('#add-coming-soon-link').on('click', function() {
                    var newLinkHtml = `
                        <div class="coming-soon-link-item" data-index="${linkIndex}">
                            <div class="coming-soon-link-header">
                                <h4>Link ${linkIndex + 1}</h4>
                                <button type="button" class="button button-secondary remove-coming-soon-link" data-index="${linkIndex}">
                                    Remove
                                </button>
                            </div>
                            
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="coming_soon_link_title_${linkIndex}">Link Title</label>
                                    </th>
                                    <td>
                                        <input type="text" 
                                               id="coming_soon_link_title_${linkIndex}"
                                               name="flexpress_general_settings[coming_soon_links][${linkIndex}][title]" 
                                               value="" 
                                               class="regular-text">
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="coming_soon_link_url_${linkIndex}">Link URL</label>
                                    </th>
                                    <td>
                                        <input type="text" 
                                               id="coming_soon_link_url_${linkIndex}"
                                               name="flexpress_general_settings[coming_soon_links][${linkIndex}][url]" 
                                               value="" 
                                               class="regular-text"
                                               placeholder="Enter link (e.g., /page, #modal, https://example.com)">
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="coming_soon_link_new_tab_${linkIndex}">Open in New Tab</label>
                                    </th>
                                    <td>
                                        <label>
                                            <input type="checkbox" 
                                                   id="coming_soon_link_new_tab_${linkIndex}"
                                                   name="flexpress_general_settings[coming_soon_links][${linkIndex}][new_tab]" 
                                                   value="1">
                                            Open link in new tab
                                        </label>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    `;

                    $('#coming-soon-links').append(newLinkHtml);
                    linkIndex++;
                });

                // Remove link
                $(document).on('click', '.remove-coming-soon-link', function() {
                    $(this).closest('.coming-soon-link-item').remove();
                    updateLinkNumbers();
                });

                // Update link numbers
                function updateLinkNumbers() {
                    $('#coming-soon-links .coming-soon-link-item').each(function(newIndex) {
                        $(this).attr('data-index', newIndex);
                        $(this).find('h4').text('Link ' + (newIndex + 1));
                        $(this).find('input, button').each(function() {
                            var name = $(this).attr('name');
                            var id = $(this).attr('id');
                            if (name) {
                                $(this).attr('name', name.replace(/\[\d+\]/, '[' + newIndex + ']'));
                            }
                            if (id) {
                                $(this).attr('id', id.replace(/\d+/, newIndex));
                            }
                        });
                    });
                }
            });
        </script>

        <style>
            .coming-soon-link-item {
                border: 1px solid #ddd;
                margin-bottom: 20px;
                padding: 15px;
                background: #f9f9f9;
            }

            .coming-soon-link-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 15px;
            }

            .coming-soon-link-header h4 {
                margin: 0;
            }
        </style>
    <?php
    }

    /**
     * Render Coming Soon whitelist field
     */
    public function render_coming_soon_whitelist_field()
    {
        $options = get_option('flexpress_general_settings');
        $whitelist = isset($options['coming_soon_whitelist']) ? $options['coming_soon_whitelist'] : array();

        // Get all published pages
        $pages = get_pages(array(
            'post_status' => 'publish',
            'sort_column' => 'post_title',
            'sort_order' => 'ASC'
        ));
    ?>
        <p class="description">
            <?php esc_html_e('Select pages that should remain accessible when Coming Soon mode is enabled. These pages will bypass the coming soon redirect.', 'flexpress'); ?>
        </p>

        <?php if (!empty($pages)) : ?>
            <select name="flexpress_general_settings[coming_soon_whitelist][]"
                id="flexpress_coming_soon_whitelist"
                multiple="multiple"
                size="8"
                style="width: 100%; max-width: 500px;">
                <?php foreach ($pages as $page) : ?>
                    <option value="<?php echo esc_attr($page->ID); ?>"
                        <?php selected(in_array($page->ID, $whitelist), true); ?>>
                        <?php echo esc_html($page->post_title); ?>
                        <?php if ($page->post_parent) : ?>
                            <?php
                            $parent = get_post($page->post_parent);
                            if ($parent) {
                                echo ' (' . esc_html($parent->post_title) . ')';
                            }
                            ?>
                        <?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <p class="description">
                <strong><?php esc_html_e('Tip:', 'flexpress'); ?></strong>
                <?php esc_html_e('Hold Ctrl/Cmd to select multiple pages. Common pages to whitelist include Contact, About, or specific landing pages.', 'flexpress'); ?>
            </p>
        <?php else : ?>
            <p class="description">
                <?php esc_html_e('No published pages found. Create some pages first to use this feature.', 'flexpress'); ?>
            </p>
        <?php endif; ?>

        <style>
            #flexpress_coming_soon_whitelist {
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 8px;
                font-size: 14px;
            }

            #flexpress_coming_soon_whitelist option {
                padding: 4px 8px;
            }

            #flexpress_coming_soon_whitelist option:checked {
                background-color: #0073aa;
                color: white;
            }
        </style>
    <?php
    }

    /**
     * Render Featured Banner section description
     */
    public function render_featured_banner_section_description()
    {
    ?>
        <p>
            <?php esc_html_e('Configure a featured banner that appears on the homepage below the featured episodes section. Perfect for highlighting special episodes, models, or promotional content.', 'flexpress'); ?>
        </p>
    <?php
    }

    /**
     * Render Featured Banner enabled field
     */
    public function render_featured_banner_enabled_field()
    {
        $options = get_option('flexpress_general_settings');
        $value = isset($options['featured_banner_enabled']) ? $options['featured_banner_enabled'] : 0;
    ?>
        <label>
            <input type="checkbox"
                name="flexpress_general_settings[featured_banner_enabled]"
                value="1"
                <?php checked($value, 1); ?>>
            <?php esc_html_e('Show Featured Banner on Homepage', 'flexpress'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('When enabled, the featured banner will appear below the featured episodes section on the homepage.', 'flexpress'); ?>
        </p>
    <?php
    }

    /**
     * Render Featured Banner image field
     */
    public function render_featured_banner_image_field()
    {
        $options = get_option('flexpress_general_settings');
        $banner_image_id = isset($options['featured_banner_image']) ? $options['featured_banner_image'] : '';

        // Display the current image if it exists
        if (!empty($banner_image_id)) {
            $image_url = wp_get_attachment_image_url($banner_image_id, 'medium');
            if ($image_url) {
                echo '<div class="flexpress-featured-banner-preview">';
                echo '<img src="' . esc_url($image_url) . '" style="max-width: 300px; height: auto; margin-bottom: 10px;" />';
                echo '</div>';
            }
        }
    ?>
        <input type="hidden" name="flexpress_general_settings[featured_banner_image]" id="flexpress_featured_banner_image" value="<?php echo esc_attr($banner_image_id); ?>" />
        <input type="button" class="button button-secondary" id="flexpress_upload_featured_banner_button" value="<?php esc_attr_e('Upload Banner Image', 'flexpress'); ?>" />
        <?php if (!empty($banner_image_id)) : ?>
            <input type="button" class="button button-secondary" id="flexpress_remove_featured_banner_button" value="<?php esc_attr_e('Remove Image', 'flexpress'); ?>" />
        <?php endif; ?>
        <p class="description"><?php esc_html_e('Upload a banner image for the featured banner. This image will be displayed on the homepage below the featured episodes section. Recommended size: 1200x400px.', 'flexpress'); ?></p>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Media uploader for featured banner image
                var featuredBannerMediaUploader;

                $('#flexpress_upload_featured_banner_button').on('click', function(e) {
                    e.preventDefault();

                    // If the media uploader already exists, open it
                    if (featuredBannerMediaUploader) {
                        featuredBannerMediaUploader.open();
                        return;
                    }

                    // Create the media uploader
                    featuredBannerMediaUploader = wp.media({
                        title: '<?php esc_html_e('Select or Upload Featured Banner Image', 'flexpress'); ?>',
                        button: {
                            text: '<?php esc_html_e('Use this image', 'flexpress'); ?>'
                        },
                        multiple: false
                    });

                    // When an image is selected, run a callback
                    featuredBannerMediaUploader.on('select', function() {
                        var attachment = featuredBannerMediaUploader.state().get('selection').first().toJSON();
                        $('#flexpress_featured_banner_image').val(attachment.id);

                        // Update preview
                        if (attachment.url) {
                            if ($('.flexpress-featured-banner-preview').length === 0) {
                                $('<div class="flexpress-featured-banner-preview"><img style="max-width: 300px; height: auto; margin-bottom: 10px;" /></div>').insertBefore('#flexpress_upload_featured_banner_button');
                            }
                            $('.flexpress-featured-banner-preview img').attr('src', attachment.url);

                            // Show remove button if not already visible
                            if ($('#flexpress_remove_featured_banner_button').length === 0) {
                                $('<input type="button" class="button button-secondary" id="flexpress_remove_featured_banner_button" value="<?php esc_attr_e('Remove Image', 'flexpress'); ?>" />').insertAfter('#flexpress_upload_featured_banner_button');
                            }
                        }
                    });

                    // Open the media uploader
                    featuredBannerMediaUploader.open();
                });

                // Handle remove button
                $(document).on('click', '#flexpress_remove_featured_banner_button', function(e) {
                    e.preventDefault();
                    $('#flexpress_featured_banner_image').val('');
                    $('.flexpress-featured-banner-preview').remove();
                    $(this).remove();
                });
            });
        </script>
    <?php
    }

    /**
     * Render Featured Banner URL field
     */
    public function render_featured_banner_url_field()
    {
        $options = get_option('flexpress_general_settings');
        $value = isset($options['featured_banner_url']) ? $options['featured_banner_url'] : '';
    ?>
        <input type="url"
            name="flexpress_general_settings[featured_banner_url]"
            value="<?php echo esc_attr($value); ?>"
            class="regular-text"
            placeholder="https://example.com/episode">
        <p class="description">
            <?php esc_html_e('Enter the URL where users will be directed when they click the banner. Can be an episode, model page, or any other URL.', 'flexpress'); ?>
        </p>
<?php
    }
}

// General settings initialization moved to functions.php 