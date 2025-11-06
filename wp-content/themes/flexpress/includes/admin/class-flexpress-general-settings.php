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
        $value = isset($options['accent_color']) ? $options['accent_color'] : '#ff5093';
    ?>
        <input type="color"
            name="flexpress_general_settings[accent_color]"
            value="<?php echo esc_attr($value); ?>"
            class="color-picker">
        <p class="description">
            <?php esc_html_e('Choose an accent color for buttons, links, and important elements. Default is pink (#ff5093).', 'flexpress'); ?>
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
                var $picker = $('input[name="flexpress_general_settings[accent_color]"]');

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
                $picker.on('change input', function() {
                    var newValue = $(this).val();
                    updatePreview(newValue);
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
            <input type="hidden" name="flexpress_general_settings[dolls_downunder_network]" value="0">
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
     * Render awards enabled field
     */

    /**
     * Render awards title field
     */

    /**
     * Render awards list field
     */

    /**
     * Render Featured On section description
     */

    /**
     * Render Featured On enabled field
     */

    /**
     * Render Featured On media outlets field
     */

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
            <input type="hidden" name="flexpress_general_settings[extras_enabled]" value="0">
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

    /**
     * Render Coming Soon enabled field
     */

    /**
     * Render Coming Soon logo field
     */

    /**
     * Render Coming Soon video URL field
     */

    /**
     * Render Coming Soon fallback image field
     */

    /**
     * Render Coming Soon text field
     */

    /**
     * Render Coming Soon links field
     */

    /**
     * Render Coming Soon whitelist field
     */

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
            <input type="hidden" name="flexpress_general_settings[featured_banner_enabled]" value="0">
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