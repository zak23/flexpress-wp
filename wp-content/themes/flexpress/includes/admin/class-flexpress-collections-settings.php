<?php

/**
 * FlexPress Collections Settings
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * FlexPress Collections Settings Class
 */
class FlexPress_Collections_Settings
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Register collections settings
     */
    public function register_settings()
    {
        // Register the settings option with validation
        register_setting(
            'flexpress_collections_settings',
            'flexpress_collections_settings',
            array(
                'sanitize_callback' => array($this, 'sanitize_settings')
            )
        );

        add_settings_section(
            'flexpress_collections_section',
            __('Collections Settings', 'flexpress'),
            array($this, 'render_section_description'),
            'flexpress_collections_settings'
        );

        // Add collections enabled field
        add_settings_field(
            'flexpress_collections_enabled',
            __('Enable Collections Feature', 'flexpress'),
            array($this, 'render_collections_enabled_field'),
            'flexpress_collections_settings',
            'flexpress_collections_section'
        );

        // Add collections page title field
        add_settings_field(
            'flexpress_collections_page_title',
            __('Collections Page Title', 'flexpress'),
            array($this, 'render_collections_page_title_field'),
            'flexpress_collections_settings',
            'flexpress_collections_section'
        );

        // Add collections page description field
        add_settings_field(
            'flexpress_collections_page_description',
            __('Collections Page Description', 'flexpress'),
            array($this, 'render_collections_page_description_field'),
            'flexpress_collections_settings',
            'flexpress_collections_section'
        );
    }

    /**
     * Sanitize settings
     *
     * @param array $input
     * @return array
     */
    public function sanitize_settings($input)
    {
        $sanitized = array();

        // Sanitize enabled checkbox
        if (isset($input['collections_enabled'])) {
            $sanitized['collections_enabled'] = absint($input['collections_enabled']);
        } else {
            $sanitized['collections_enabled'] = 0;
        }

        // Sanitize page title
        if (isset($input['collections_page_title'])) {
            $sanitized['collections_page_title'] = sanitize_text_field($input['collections_page_title']);
        } else {
            $sanitized['collections_page_title'] = 'Collections';
        }

        // Sanitize page description
        if (isset($input['collections_page_description'])) {
            $sanitized['collections_page_description'] = wp_kses_post($input['collections_page_description']);
        }

        return $sanitized;
    }

    /**
     * Render the collections settings page
     */
    public function render_collections_settings_page()
    {
?>
        <div class="wrap">
            <h1><?php echo esc_html__('Collections Settings', 'flexpress'); ?></h1>

            <form method="post" action="options.php">
                <?php
                settings_fields('flexpress_collections_settings');
                do_settings_sections('flexpress_collections_settings');
                submit_button();
                ?>
            </form>
        </div>
    <?php
    }

    /**
     * Render Collections section description
     */
    public function render_section_description()
    {
    ?>
        <p>
            <?php esc_html_e('Configure the Collections feature that allows you to organize and display related episodes together. When enabled, a dedicated collections page will be available at /collections.', 'flexpress'); ?>
        </p>
    <?php
    }

    /**
     * Render Collections enabled field
     */
    public function render_collections_enabled_field()
    {
        $options = get_option('flexpress_collections_settings');
        $value = isset($options['collections_enabled']) ? $options['collections_enabled'] : 0;
    ?>
        <label>
            <input type="checkbox"
                name="flexpress_collections_settings[collections_enabled]"
                value="1"
                <?php checked($value, 1); ?>>
            <?php esc_html_e('Enable Collections Feature', 'flexpress'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('When enabled, collection-specific features will be active across your site. This includes the /collections page, collection badges, and collection links. When disabled, all collection UI elements are hidden and the /collections page redirects to /episodes/.', 'flexpress'); ?>
        </p>
    <?php
    }

    /**
     * Render Collections page title field
     */
    public function render_collections_page_title_field()
    {
        $options = get_option('flexpress_collections_settings');
        $value = isset($options['collections_page_title']) ? $options['collections_page_title'] : 'Collections';
    ?>
        <input type="text"
            name="flexpress_collections_settings[collections_page_title]"
            value="<?php echo esc_attr($value); ?>"
            class="regular-text">
        <p class="description">
            <?php esc_html_e('The title displayed on the Collections page. Default: "Collections"', 'flexpress'); ?>
        </p>
    <?php
    }

    /**
     * Render Collections page description field
     */
    public function render_collections_page_description_field()
    {
        $options = get_option('flexpress_collections_settings');
        $value = isset($options['collections_page_description']) ? $options['collections_page_description'] : '';
    ?>
        <textarea
            name="flexpress_collections_settings[collections_page_description]"
            class="large-text"
            rows="3"
            placeholder="<?php esc_attr_e('Optional introductory text for the Collections page...', 'flexpress'); ?>"><?php echo esc_textarea($value); ?></textarea>
        <p class="description">
            <?php esc_html_e('Optional introductory text displayed on the Collections page (supports HTML).', 'flexpress'); ?>
        </p>
<?php
    }
}

// Initialize the collections settings only in admin
if (is_admin()) {
    new FlexPress_Collections_Settings();
}
