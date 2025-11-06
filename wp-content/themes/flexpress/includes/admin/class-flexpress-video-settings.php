<?php

/**
 * FlexPress Video Settings
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * FlexPress Video Settings Class
 */
class FlexPress_Video_Settings
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_init', array($this, 'migrate_old_settings'));
        add_action('admin_notices', array($this, 'display_duration_update_notice'));
        add_action('admin_menu', array($this, 'add_submenu_page'));
    }

    /**
     * Display notice when video durations are updated
     */
    public function display_duration_update_notice()
    {
        if (
            isset($_GET['page']) && $_GET['page'] === 'flexpress-settings' &&
            isset($_GET['tab']) && $_GET['tab'] === 'video' &&
            isset($_GET['updated']) && isset($_GET['count'])
        ) {

            $count = intval($_GET['count']);
            $message = sprintf(
                _n(
                    'Updated %d episode duration from BunnyCDN.',
                    'Updated %d episode durations from BunnyCDN.',
                    $count,
                    'flexpress'
                ),
                $count
            );

            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
        }
    }

    /**
     * Migrate old settings to new structure
     */
    public function migrate_old_settings()
    {
        $new_settings = array();

        // Check for old API key
        $api_key = get_option('bunnycdn_api_key', '');
        if (!empty($api_key)) {
            $new_settings['api_key'] = $api_key;
        }

        // Check for old library ID
        $library_id = get_option('bunnycdn_library_id', '');
        if (!empty($library_id)) {
            $new_settings['library_id'] = $library_id;
        }

        // Check for old token key (try multiple possible option names)
        $token_key = '';
        $possible_token_keys = array(
            'bunnycdn_token_key',
            'bunnycdn_token',
            'bunnycdn_auth_key',
            'flexpress_video_bunnycdn_token',
        );

        foreach ($possible_token_keys as $key) {
            $value = get_option($key, '');
            if (!empty($value)) {
                $token_key = $value;
                break;
            }
        }

        if (!empty($token_key)) {
            $new_settings['token_key'] = $token_key;
        }

        // Only update if we have at least one value
        if (!empty($new_settings)) {
            $this->update_settings($new_settings);
        }
    }

    /**
     * Update video settings
     */
    private function update_settings($new_settings)
    {
        $current_settings = get_option('flexpress_video_settings', array());
        $updated_settings = array_merge($current_settings, $new_settings);
        update_option('flexpress_video_settings', $updated_settings);
    }

    /**
     * Register video settings
     */
    public function register_settings()
    {
        // Register the settings option
        register_setting('flexpress_video_settings', 'flexpress_video_settings');

        add_settings_section(
            'flexpress_video_section',
            __('Bunny Stream Settings', 'flexpress'),
            array($this, 'render_section_description'),
            'flexpress_video_settings'
        );

        // Bunny Stream Library ID
        add_settings_field(
            'flexpress_bunnycdn_library_id',
            __('Bunny Stream Library ID', 'flexpress'),
            array($this, 'render_bunnycdn_library_id_field'),
            'flexpress_video_settings',
            'flexpress_video_section'
        );

        // Bunny Stream URL
        add_settings_field(
            'flexpress_bunnycdn_url',
            __('Bunny Stream URL', 'flexpress'),
            array($this, 'render_bunnycdn_url_field'),
            'flexpress_video_settings',
            'flexpress_video_section'
        );

        // Bunny Stream API Key
        add_settings_field(
            'flexpress_bunnycdn_api_key',
            __('Bunny Stream API Key', 'flexpress'),
            array($this, 'render_bunnycdn_api_key_field'),
            'flexpress_video_settings',
            'flexpress_video_section'
        );

        // Bunny Stream Token Key
        add_settings_field(
            'flexpress_bunnycdn_token_key',
            __('Bunny Stream Token Key', 'flexpress'),
            array($this, 'render_bunnycdn_token_key_field'),
            'flexpress_video_settings',
            'flexpress_video_section'
        );

        // Video Player Settings
        add_settings_field(
            'flexpress_video_player_theme',
            __('Player Theme', 'flexpress'),
            array($this, 'render_video_player_theme_field'),
            'flexpress_video_settings',
            'flexpress_video_section'
        );

        add_settings_field(
            'flexpress_video_player_autoplay',
            __('Autoplay', 'flexpress'),
            array($this, 'render_video_player_autoplay_field'),
            'flexpress_video_settings',
            'flexpress_video_section'
        );

        // Cache Duration Setting
        add_settings_field(
            'flexpress_bunnycdn_cache_duration',
            __('Cache Duration', 'flexpress'),
            array($this, 'render_bunnycdn_cache_duration_field'),
            'flexpress_video_settings',
            'flexpress_video_section'
        );

        // BunnyCDN Storage Zone
        add_settings_field(
            'flexpress_bunnycdn_storage_zone',
            __('Storage Zone', 'flexpress'),
            array($this, 'render_bunnycdn_storage_zone_field'),
            'flexpress_video_settings',
            'flexpress_video_section'
        );

        // BunnyCDN Storage URL
        add_settings_field(
            'flexpress_bunnycdn_storage_url',
            __('Storage URL', 'flexpress'),
            array($this, 'render_bunnycdn_storage_url_field'),
            'flexpress_video_settings',
            'flexpress_video_section'
        );

        // BunnyCDN Serve URL
        add_settings_field(
            'flexpress_bunnycdn_serve_url',
            __('Serve URL', 'flexpress'),
            array($this, 'render_bunnycdn_serve_url_field'),
            'flexpress_video_settings',
            'flexpress_video_section'
        );

        // BunnyCDN Static CDN Hostname (for images and other static assets)
        add_settings_field(
            'flexpress_bunnycdn_static_host',
            __('Static CDN Hostname', 'flexpress'),
            array($this, 'render_bunnycdn_static_host_field'),
            'flexpress_video_settings',
            'flexpress_video_section'
        );

        // BunnyCDN Storage API Key
        add_settings_field(
            'flexpress_bunnycdn_storage_api_key',
            __('Storage API Key', 'flexpress'),
            array($this, 'render_bunnycdn_storage_api_key_field'),
            'flexpress_video_settings',
            'flexpress_video_section'
        );

        // BunnyCDN Storage Token Key
        add_settings_field(
            'flexpress_bunnycdn_storage_token_key',
            __('Storage Token Key', 'flexpress'),
            array($this, 'render_bunnycdn_storage_token_key_field'),
            'flexpress_video_settings',
            'flexpress_video_section'
        );

        // Gallery Thumbnail Size
        add_settings_field(
            'flexpress_gallery_thumbnail_size',
            __('Gallery Thumbnail Size', 'flexpress'),
            array($this, 'render_gallery_thumbnail_size_field'),
            'flexpress_video_settings',
            'flexpress_video_section'
        );
    }

    /**
     * Render section description
     */
    public function render_section_description()
    {
        echo '<p>' . esc_html__('Configure Bunny Stream integration and video player settings.', 'flexpress') . '</p>';
    }

    /**
     * Render BunnyCDN Library ID field
     */
    public function render_bunnycdn_library_id_field()
    {
        $options = get_option('flexpress_video_settings');
        $value = isset($options['bunnycdn_library_id']) ? $options['bunnycdn_library_id'] : '';
?>
        <input type="text"
            name="flexpress_video_settings[bunnycdn_library_id]"
            value="<?php echo esc_attr($value); ?>"
            class="regular-text">
        <p class="description"><?php esc_html_e('Enter your Bunny Stream Library ID.', 'flexpress'); ?></p>
    <?php
    }

    /**
     * Render BunnyCDN URL field
     */
    public function render_bunnycdn_url_field()
    {
        $options = get_option('flexpress_video_settings');
        $value = isset($options['bunnycdn_url']) ? $options['bunnycdn_url'] : '';
        // Remove https:// if it exists
        $value = preg_replace('#^https?://#', '', $value);
    ?>
        <input type="text"
            name="flexpress_video_settings[bunnycdn_url]"
            value="<?php echo esc_attr($value); ?>"
            class="regular-text">
        <p class="description"><?php esc_html_e('Enter your Bunny Stream URL (e.g., vz-191fb414-531.b-cdn.net)', 'flexpress'); ?></p>
    <?php
    }

    /**
     * Render BunnyCDN API Key field
     */
    public function render_bunnycdn_api_key_field()
    {
        $options = get_option('flexpress_video_settings');
        $value = isset($options['bunnycdn_api_key']) ? $options['bunnycdn_api_key'] : '';
    ?>
        <input type="password"
            name="flexpress_video_settings[bunnycdn_api_key]"
            value="<?php echo esc_attr($value); ?>"
            class="regular-text">
        <p class="description"><?php esc_html_e('Enter your Bunny Stream API Key.', 'flexpress'); ?></p>
    <?php
    }

    /**
     * Render BunnyCDN Token Key field
     */
    public function render_bunnycdn_token_key_field()
    {
        $options = get_option('flexpress_video_settings');
        $value = isset($options['bunnycdn_token_key']) ? $options['bunnycdn_token_key'] : '';
    ?>
        <input type="password"
            name="flexpress_video_settings[bunnycdn_token_key]"
            value="<?php echo esc_attr($value); ?>"
            class="regular-text">
        <p class="description"><?php esc_html_e('Enter your Bunny Stream Token Key. This is used for generating secure video URLs.', 'flexpress'); ?></p>
    <?php
    }

    /**
     * Render video player theme field
     */
    public function render_video_player_theme_field()
    {
        $options = get_option('flexpress_video_settings');
        $value = isset($options['player_theme']) ? $options['player_theme'] : 'light';
    ?>
        <select name="flexpress_video_settings[player_theme]">
            <option value="light" <?php selected($value, 'light'); ?>><?php esc_html_e('Light', 'flexpress'); ?></option>
            <option value="dark" <?php selected($value, 'dark'); ?>><?php esc_html_e('Dark', 'flexpress'); ?></option>
        </select>
        <p class="description"><?php esc_html_e('Choose the video player theme.', 'flexpress'); ?></p>
    <?php
    }

    /**
     * Render video player autoplay field
     */
    public function render_video_player_autoplay_field()
    {
        $options = get_option('flexpress_video_settings');
        $value = isset($options['player_autoplay']) ? $options['player_autoplay'] : false;
    ?>
        <input type="checkbox"
            name="flexpress_video_settings[player_autoplay]"
            value="1"
            <?php checked($value, 1); ?>>
        <p class="description"><?php esc_html_e('Enable video autoplay.', 'flexpress'); ?></p>
    <?php
    }

    /**
     * Render BunnyCDN cache duration field
     */
    public function render_bunnycdn_cache_duration_field()
    {
        $options = get_option('flexpress_video_settings');
        $value = isset($options['bunnycdn_cache_duration']) ? $options['bunnycdn_cache_duration'] : 12;
    ?>
        <select name="flexpress_video_settings[bunnycdn_cache_duration]">
            <option value="0.5" <?php selected($value, '0.5'); ?>><?php esc_html_e('30 minutes', 'flexpress'); ?></option>
            <option value="1" <?php selected($value, '1'); ?>><?php esc_html_e('1 hour', 'flexpress'); ?></option>
            <option value="2" <?php selected($value, '2'); ?>><?php esc_html_e('2 hours', 'flexpress'); ?></option>
            <option value="6" <?php selected($value, '6'); ?>><?php esc_html_e('6 hours', 'flexpress'); ?></option>
            <option value="12" <?php selected($value, '12'); ?>><?php esc_html_e('12 hours (default)', 'flexpress'); ?></option>
            <option value="24" <?php selected($value, '24'); ?>><?php esc_html_e('24 hours', 'flexpress'); ?></option>
            <option value="48" <?php selected($value, '48'); ?>><?php esc_html_e('48 hours', 'flexpress'); ?></option>
            <option value="168" <?php selected($value, '168'); ?>><?php esc_html_e('1 week', 'flexpress'); ?></option>
        </select>
        <p class="description">
            <?php esc_html_e('How long to cache BunnyCDN video details. Shorter duration = more up-to-date thumbnails but more API calls.', 'flexpress'); ?>
            <br>
            <strong><?php esc_html_e('Recommended:', 'flexpress'); ?></strong>
            <?php esc_html_e('12 hours for most sites, 1-2 hours if you frequently update thumbnails.', 'flexpress'); ?>
        </p>
    <?php
    }

    /**
     * Format cache duration for display
     */
    private function format_cache_duration($hours)
    {
        if ($hours < 1) {
            return ($hours * 60) . ' minutes';
        } elseif ($hours == 1) {
            return '1 hour';
        } elseif ($hours < 24) {
            return $hours . ' hours';
        } elseif ($hours == 24) {
            return '1 day';
        } elseif ($hours == 168) {
            return '1 week';
        } else {
            return ($hours / 24) . ' days';
        }
    }

    /**
     * Add cache management section
     */
    public function add_cache_management_section()
    {
        // Get current cache duration
        $options = get_option('flexpress_video_settings');
        $cache_duration = isset($options['bunnycdn_cache_duration']) ? $options['bunnycdn_cache_duration'] : 12;

        echo '<h2>' . esc_html__('Cache Management', 'flexpress') . '</h2>';
        echo '<p>' . sprintf(
            esc_html__('BunnyCDN video details are currently cached for %s to improve performance. If you update thumbnails on BunnyCDN, you may need to clear the cache.', 'flexpress'),
            '<strong>' . esc_html($this->format_cache_duration($cache_duration)) . '</strong>'
        ) . '</p>';

        echo '<div style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0;">';

        // Clear all cache button
        echo '<p>';
        echo '<button type="button" class="button button-secondary" id="clear-all-bunnycdn-cache">';
        echo esc_html__('Clear All Video Cache', 'flexpress');
        echo '</button>';
        echo ' <span class="description">' . esc_html__('Clears cached data for all videos', 'flexpress') . '</span>';
        echo '</p>';

        // Clear specific video cache
        echo '<p>';
        echo '<input type="text" id="clear-specific-video-id" placeholder="' . esc_attr__('Video ID', 'flexpress') . '" style="width: 200px;">';
        echo ' <button type="button" class="button button-secondary" id="clear-specific-bunnycdn-cache">';
        echo esc_html__('Clear Specific Video Cache', 'flexpress');
        echo '</button>';
        echo ' <span class="description">' . esc_html__('Clear cache for a specific video ID', 'flexpress') . '</span>';
        echo '</p>';

        echo '<div id="cache-clear-message" style="margin-top: 10px;"></div>';

        echo '</div>';

        // Add JavaScript for cache clearing
    ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('#clear-all-bunnycdn-cache').on('click', function() {
                    var button = $(this);
                    button.prop('disabled', true).text('<?php echo esc_js(__('Clearing...', 'flexpress')); ?>');

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'flexpress_clear_bunnycdn_cache',
                            type: 'all',
                            nonce: '<?php echo wp_create_nonce('flexpress_clear_cache'); ?>'
                        },
                        success: function(response) {
                            $('#cache-clear-message').html('<div class="notice notice-success"><p>' + response.data + '</p></div>');
                            button.prop('disabled', false).text('<?php echo esc_js(__('Clear All Video Cache', 'flexpress')); ?>');
                        },
                        error: function() {
                            $('#cache-clear-message').html('<div class="notice notice-error"><p><?php echo esc_js(__('Error clearing cache', 'flexpress')); ?></p></div>');
                            button.prop('disabled', false).text('<?php echo esc_js(__('Clear All Video Cache', 'flexpress')); ?>');
                        }
                    });
                });

                $('#clear-specific-bunnycdn-cache').on('click', function() {
                    var button = $(this);
                    var videoId = $('#clear-specific-video-id').val();

                    if (!videoId) {
                        alert('<?php echo esc_js(__('Please enter a video ID', 'flexpress')); ?>');
                        return;
                    }

                    button.prop('disabled', true).text('<?php echo esc_js(__('Clearing...', 'flexpress')); ?>');

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'flexpress_clear_bunnycdn_cache',
                            type: 'specific',
                            video_id: videoId,
                            nonce: '<?php echo wp_create_nonce('flexpress_clear_cache'); ?>'
                        },
                        success: function(response) {
                            $('#cache-clear-message').html('<div class="notice notice-success"><p>' + response.data + '</p></div>');
                            button.prop('disabled', false).text('<?php echo esc_js(__('Clear Specific Video Cache', 'flexpress')); ?>');
                            $('#clear-specific-video-id').val('');
                        },
                        error: function() {
                            $('#cache-clear-message').html('<div class="notice notice-error"><p><?php echo esc_js(__('Error clearing cache', 'flexpress')); ?></p></div>');
                            button.prop('disabled', false).text('<?php echo esc_js(__('Clear Specific Video Cache', 'flexpress')); ?>');
                        }
                    });
                });
            });
        </script>
    <?php
    }

    /**
     * Render BunnyCDN Storage Zone field
     */
    public function render_bunnycdn_storage_zone_field()
    {
        $options = get_option('flexpress_video_settings');
        $value = isset($options['bunnycdn_storage_zone']) ? $options['bunnycdn_storage_zone'] : '';
    ?>
        <input type="text"
            name="flexpress_video_settings[bunnycdn_storage_zone]"
            value="<?php echo esc_attr($value); ?>"
            class="regular-text">
        <p class="description"><?php esc_html_e('Enter your BunnyCDN Storage Zone name for gallery images.', 'flexpress'); ?></p>
    <?php
    }

    /**
     * Render BunnyCDN Storage URL field
     */
    public function render_bunnycdn_storage_url_field()
    {
        $options = get_option('flexpress_video_settings');
        $value = isset($options['bunnycdn_storage_url']) ? $options['bunnycdn_storage_url'] : '';
        // Remove https:// if it exists
        $value = preg_replace('#^https?://#', '', $value);
    ?>
        <input type="text"
            name="flexpress_video_settings[bunnycdn_storage_url]"
            value="<?php echo esc_attr($value); ?>"
            class="regular-text">
        <p class="description"><?php esc_html_e('Enter your BunnyCDN Storage URL for gallery images (e.g., sg.storage.bunnycdn.com).', 'flexpress'); ?></p>
    <?php
    }

    /**
     * Render BunnyCDN Serve URL field
     */
    public function render_bunnycdn_serve_url_field()
    {
        $options = get_option('flexpress_video_settings');
        $value = isset($options['bunnycdn_serve_url']) ? $options['bunnycdn_serve_url'] : '';
        // Remove https:// if it exists
        $value = preg_replace('#^https?://#', '', $value);
    ?>
        <input type="text"
            name="flexpress_video_settings[bunnycdn_serve_url]"
            value="<?php echo esc_attr($value); ?>"
            class="regular-text">
        <p class="description"><?php esc_html_e('Enter your BunnyCDN Serve URL for gallery images (e.g., storage.b-cdn.net).', 'flexpress'); ?></p>
    <?php
    }

    /**
     * Render BunnyCDN Static CDN Hostname field
     */
    public function render_bunnycdn_static_host_field()
    {
        $options = get_option('flexpress_video_settings');
        $value = isset($options['bunnycdn_static_host']) ? $options['bunnycdn_static_host'] : '';
        // Remove https:// if it exists
        $value = preg_replace('#^https?://#', '', $value);
    ?>
        <input type="text"
            name="flexpress_video_settings[bunnycdn_static_host]"
            value="<?php echo esc_attr($value); ?>"
            class="regular-text">
        <p class="description"><?php echo esc_html(sprintf(__('Hostname only, no protocol (e.g., static.%s). Used to rewrite image URLs to BunnyCDN.', 'flexpress'), preg_replace('#^https?://#', '', parse_url(home_url(), PHP_URL_HOST)))); ?></p>
    <?php
    }

    /**
     * Render BunnyCDN Storage API Key field
     */
    public function render_bunnycdn_storage_api_key_field()
    {
        $options = get_option('flexpress_video_settings');
        $value = isset($options['bunnycdn_storage_api_key']) ? $options['bunnycdn_storage_api_key'] : '';
    ?>
        <input type="password"
            name="flexpress_video_settings[bunnycdn_storage_api_key]"
            value="<?php echo esc_attr($value); ?>"
            class="regular-text">
        <p class="description"><?php esc_html_e('Enter your BunnyCDN Storage API Key for gallery image uploads.', 'flexpress'); ?></p>
    <?php
    }

    /**
     * Render BunnyCDN Storage Token Key field
     */
    public function render_bunnycdn_storage_token_key_field()
    {
        $options = get_option('flexpress_video_settings');
        $value = isset($options['bunnycdn_storage_token_key']) ? $options['bunnycdn_storage_token_key'] : '';
    ?>
        <input type="password"
            name="flexpress_video_settings[bunnycdn_storage_token_key]"
            value="<?php echo esc_attr($value); ?>"
            class="regular-text">
        <p class="description"><?php esc_html_e('Enter your BunnyCDN Storage Token Key for generating signed URLs for gallery images.', 'flexpress'); ?></p>
    <?php
    }

    /**
     * Render Gallery Thumbnail Size field
     */
    public function render_gallery_thumbnail_size_field()
    {
        $options = get_option('flexpress_video_settings');
        $value = isset($options['gallery_thumbnail_size']) ? $options['gallery_thumbnail_size'] : 300;
    ?>
        <input type="number"
            name="flexpress_video_settings[gallery_thumbnail_size]"
            value="<?php echo esc_attr($value); ?>"
            min="100"
            max="800"
            step="50"
            class="small-text">
        <span class="description"><?php esc_html_e('pixels (square thumbnails)', 'flexpress'); ?></span>
        <p class="description"><?php esc_html_e('Size of square thumbnails generated for gallery images. Images will be center-cropped to square format.', 'flexpress'); ?></p>
    <?php
    }

    /**
     * Add submenu page under FlexPress Settings
     */
    public function add_submenu_page()
    {
        add_submenu_page(
            'flexpress-settings',
            __('Bunny Stream Settings', 'flexpress'),
            __('Bunny Stream Settings', 'flexpress'),
            'manage_options',
            'flexpress-bunnycdn-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Render the BunnyCDN settings page
     */
    public function render_settings_page()
    {
    ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Bunny Stream Settings', 'flexpress'); ?></h1>

            <form method="post" action="options.php">
                <?php
                settings_fields('flexpress_video_settings');
                do_settings_sections('flexpress_video_settings');

                // Add cache management section
                if (method_exists($this, 'add_cache_management_section')) {
                    echo '<hr style="margin: 30px 0;">';
                    $this->add_cache_management_section();
                }

                submit_button();
                ?>
            </form>
        </div>
<?php
    }
}

// Initialize the video settings
new FlexPress_Video_Settings();
