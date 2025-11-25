<?php

/**
 * FlexPress Featured Banner Settings
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * FlexPress Featured Banner Settings Class
 */
class FlexPress_Featured_Banner_Settings
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
     * Register featured banner settings
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
            'flexpress_featured_banner_section',
            __('Featured Banner', 'flexpress'),
            array($this, 'render_section_description'),
            'flexpress_featured_banner_settings'
        );

        // Add featured banner enabled field
        add_settings_field(
            'flexpress_featured_banner_enabled',
            __('Enable Featured Banner', 'flexpress'),
            array($this, 'render_featured_banner_enabled_field'),
            'flexpress_featured_banner_settings',
            'flexpress_featured_banner_section'
        );

        // Add featured banner image field
        add_settings_field(
            'flexpress_featured_banner_image',
            __('Banner Image', 'flexpress'),
            array($this, 'render_featured_banner_image_field'),
            'flexpress_featured_banner_settings',
            'flexpress_featured_banner_section'
        );

        // Add featured banner URL field
        add_settings_field(
            'flexpress_featured_banner_url',
            __('Banner URL', 'flexpress'),
            array($this, 'render_featured_banner_url_field'),
            'flexpress_featured_banner_settings',
            'flexpress_featured_banner_section'
        );
    }

    /**
     * Enqueue media scripts
     */
    public function enqueue_media_scripts()
    {
        wp_enqueue_media();
    }

    /**
     * Render the featured banner settings page
     */
    public function render_featured_banner_settings_page()
    {
?>
        <div class="wrap">
            <h1><?php echo esc_html__('Featured Banner', 'flexpress'); ?></h1>

            <form method="post" action="options.php">
                <?php
                settings_fields('flexpress_general_settings');
                do_settings_sections('flexpress_featured_banner_settings');
                submit_button();
                ?>
            </form>
        </div>
    <?php
    }

    /**
     * Render Featured Banner section description
     */
    public function render_section_description()
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

// Initialize the featured banner settings only in admin
if (is_admin()) {
    new FlexPress_Featured_Banner_Settings();
}

