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
class FlexPress_General_Settings {
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_media_scripts'));
    }

    /**
     * Register general settings
     */
    public function register_settings() {
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
    }

    /**
     * Render section description
     */
    public function render_section_description() {
        echo '<p>' . esc_html__('Configure general settings for your FlexPress site.', 'flexpress') . '</p>';
    }

    /**
     * Render site title field
     */
    public function render_site_title_field() {
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
    public function render_site_description_field() {
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
    public function render_logo_section_description() {
        echo '<p>' . esc_html__('Upload a custom logo for your FlexPress site.', 'flexpress') . '</p>';
    }

    /**
     * Render color section description
     */
    public function render_color_section_description() {
        echo '<p>' . esc_html__('Customize the color scheme for your FlexPress site. The accent color will be used for buttons, links, and highlights throughout the theme.', 'flexpress') . '</p>';
    }

    /**
     * Render accent color field
     */
    public function render_accent_color_field() {
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
    public function render_age_verification_exit_url_field() {
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
    public function render_custom_logo_field() {
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
     * Enqueue media scripts on settings page
     */
    public function enqueue_media_scripts($hook) {
        if (strpos($hook, 'flexpress-settings') !== false) {
            wp_enqueue_media();
        }
    }
}

// Initialize the general settings only in admin
if (is_admin()) {
new FlexPress_General_Settings(); 
} 