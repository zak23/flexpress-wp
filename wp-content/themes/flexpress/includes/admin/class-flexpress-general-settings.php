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
        error_log('FlexPress General Settings: Constructor called');
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_media_scripts'));
    }

    /**
     * Register general settings
     */
    public function register_settings()
    {
        error_log('FlexPress General Settings: register_settings called');
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
        error_log('FlexPress General Settings: General section added');

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
        error_log('FlexPress General Settings: Casting image field added');
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
        error_log('FlexPress General Settings: render_casting_image_field called');
        $options = get_option('flexpress_general_settings');
        $casting_image_id = isset($options['casting_image']) ? $options['casting_image'] : '';

        error_log('FlexPress General Settings: Current casting image ID from options: ' . $casting_image_id);

        // Display the current image if it exists
        if (!empty($casting_image_id)) {
            $image_url = wp_get_attachment_image_url($casting_image_id, 'medium');
            error_log('FlexPress General Settings: Image URL for ID ' . $casting_image_id . ': ' . ($image_url ? $image_url : 'No URL found'));
            if ($image_url) {
                echo '<div class="flexpress-casting-image-preview">';
                echo '<img src="' . esc_url($image_url) . '" style="max-width: 300px; height: auto; margin-bottom: 10px;" />';
                echo '</div>';
            }
        } else {
            error_log('FlexPress General Settings: No casting image ID found, will show upload button');
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
}

// General settings initialization moved to functions.php 