<?php

/**
 * FlexPress Coming Soon Settings
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * FlexPress Coming Soon Settings Class
 */
class FlexPress_Coming_Soon_Settings
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
     * Register coming soon settings
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
            'flexpress_coming_soon_section',
            __('Coming Soon Mode', 'flexpress'),
            array($this, 'render_section_description'),
            'flexpress_coming_soon_settings'
        );

        // Add coming soon enabled field
        add_settings_field(
            'flexpress_coming_soon_enabled',
            __('Enable Coming Soon Mode', 'flexpress'),
            array($this, 'render_coming_soon_enabled_field'),
            'flexpress_coming_soon_settings',
            'flexpress_coming_soon_section'
        );

        // Add coming soon logo field
        add_settings_field(
            'flexpress_coming_soon_logo',
            __('Coming Soon Logo', 'flexpress'),
            array($this, 'render_coming_soon_logo_field'),
            'flexpress_coming_soon_settings',
            'flexpress_coming_soon_section'
        );

        // Add coming soon video URL field
        add_settings_field(
            'flexpress_coming_soon_video_url',
            __('Video ID (Bunny CDN)', 'flexpress'),
            array($this, 'render_coming_soon_video_url_field'),
            'flexpress_coming_soon_settings',
            'flexpress_coming_soon_section'
        );

        // Add coming soon fallback image field
        add_settings_field(
            'flexpress_coming_soon_fallback_image',
            __('Thumbnail Image', 'flexpress'),
            array($this, 'render_coming_soon_fallback_image_field'),
            'flexpress_coming_soon_settings',
            'flexpress_coming_soon_section'
        );

        // Add coming soon text field
        add_settings_field(
            'flexpress_coming_soon_text',
            __('Coming Soon Text', 'flexpress'),
            array($this, 'render_coming_soon_text_field'),
            'flexpress_coming_soon_settings',
            'flexpress_coming_soon_section'
        );

        // Add coming soon links field
        add_settings_field(
            'flexpress_coming_soon_links',
            __('Custom Links', 'flexpress'),
            array($this, 'render_coming_soon_links_field'),
            'flexpress_coming_soon_settings',
            'flexpress_coming_soon_section'
        );

        // Add coming soon whitelist field
        add_settings_field(
            'flexpress_coming_soon_whitelist',
            __('Whitelisted Pages', 'flexpress'),
            array($this, 'render_coming_soon_whitelist_field'),
            'flexpress_coming_soon_settings',
            'flexpress_coming_soon_section'
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
     * Render the coming soon settings page
     */
    public function render_coming_soon_settings_page()
    {
?>
        <div class="wrap">
            <h1><?php echo esc_html__('Coming Soon Mode', 'flexpress'); ?></h1>

            <form method="post" action="options.php">
                <?php
                settings_fields('flexpress_general_settings');
                do_settings_sections('flexpress_coming_soon_settings');
                submit_button();
                ?>
            </form>
        </div>
    <?php
    }

    /**
     * Render Coming Soon section description
     */
    public function render_section_description()
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
            <?php esc_html_e('When enabled, visitors will see a coming soon page instead of your normal site. Administrators will automatically bypass this.', 'flexpress'); ?>
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
            $logo_url = wp_get_attachment_url($logo_id);
            if ($logo_url) {
                echo '<div class="flexpress-coming-soon-logo-preview"><img src="' . esc_url($logo_url) . '" style="max-width: 300px; height: auto; margin-bottom: 10px;" /></div>';
            }
        }
    ?>
        <input type="hidden" name="flexpress_general_settings[coming_soon_logo]" id="flexpress_coming_soon_logo" value="<?php echo esc_attr($logo_id); ?>" />
        <input type="button" class="button button-secondary" id="flexpress_upload_coming_soon_logo_button" value="<?php esc_attr_e('Upload Logo', 'flexpress'); ?>" />
        <?php if (!empty($logo_id)) : ?>
            <input type="button" class="button button-secondary" id="flexpress_remove_coming_soon_logo_button" value="<?php esc_attr_e('Remove Logo', 'flexpress'); ?>" />
        <?php endif; ?>
        <p class="description"><?php esc_html_e('Upload a logo to display on the coming soon page. Recommended size: 400x200px or similar.', 'flexpress'); ?></p>

        <script>
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
            <?php esc_html_e('Enter the Bunny CDN video ID (just the number, not the full URL). This video will play on the coming soon page.', 'flexpress'); ?>
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
            $image_url = wp_get_attachment_url($image_id);
            if ($image_url) {
                echo '<div class="flexpress-coming-soon-fallback-preview"><img src="' . esc_url($image_url) . '" style="max-width: 300px; height: auto; margin-bottom: 10px;" /></div>';
            }
        }
    ?>
        <input type="hidden" name="flexpress_general_settings[coming_soon_fallback_image]" id="flexpress_coming_soon_fallback_image" value="<?php echo esc_attr($image_id); ?>" />
        <input type="button" class="button button-secondary" id="flexpress_upload_coming_soon_fallback_button" value="<?php esc_attr_e('Upload Thumbnail Image', 'flexpress'); ?>" />
        <?php if (!empty($image_id)) : ?>
            <input type="button" class="button button-secondary" id="flexpress_remove_coming_soon_fallback_button" value="<?php esc_attr_e('Remove Image', 'flexpress'); ?>" />
        <?php endif; ?>
        <p class="description"><?php esc_html_e('Upload a thumbnail image that shows while the video loads. If video fails to load, this image will remain visible. Recommended size: 1920x1080px.', 'flexpress'); ?></p>

        <script>
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
                        title: '<?php esc_html_e('Select or Upload Coming Soon Thumbnail', 'flexpress'); ?>',
                        button: {
                            text: '<?php esc_html_e('Use this image as thumbnail', 'flexpress'); ?>'
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
                                        placeholder="contact or https://example.com">
                                    <p class="description">
                                        <?php esc_html_e('Enter a page slug (like "contact") or full URL. Page slugs will be converted to proper URLs automatically.', 'flexpress'); ?>
                                    </p>
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
                                        <?php esc_html_e('Open this link in a new tab', 'flexpress'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="button" id="add-coming-soon-link" class="button button-secondary">
                <?php esc_html_e('Add New Link', 'flexpress'); ?>
            </button>
        </div>

        <script>
            jQuery(document).ready(function($) {
                var linkIndex = <?php echo count($links_list); ?>;

                // Add new link
                $('#add-coming-soon-link').on('click', function() {
                    var newLinkHtml =
                        '<div class="coming-soon-link-item" data-index="' + linkIndex + '">' +
                        '<div class="coming-soon-link-header">' +
                        '<h4>Link ' + (linkIndex + 1) + '</h4>' +
                        '<button type="button" class="button button-secondary remove-coming-soon-link" data-index="' + linkIndex + '"><?php esc_html_e('Remove', 'flexpress'); ?></button>' +
                        '</div>' +
                        '<table class="form-table">' +
                        '<tr>' +
                        '<th scope="row"><label><?php esc_html_e('Link Title', 'flexpress'); ?></label></th>' +
                        '<td><input type="text" name="flexpress_general_settings[coming_soon_links][' + linkIndex + '][title]" class="regular-text"></td>' +
                        '</tr>' +
                        '<tr>' +
                        '<th scope="row"><label><?php esc_html_e('Link URL', 'flexpress'); ?></label></th>' +
                        '<td><input type="text" name="flexpress_general_settings[coming_soon_links][' + linkIndex + '][url]" class="regular-text" placeholder="contact or https://example.com"></td>' +
                        '</tr>' +
                        '<tr>' +
                        '<th scope="row"><label><?php esc_html_e('Open in New Tab', 'flexpress'); ?></label></th>' +
                        '<td><label><input type="checkbox" name="flexpress_general_settings[coming_soon_links][' + linkIndex + '][new_tab]" value="1"> <?php esc_html_e('Open this link in a new tab', 'flexpress'); ?></label></td>' +
                        '</tr>' +
                        '</table>' +
                        '</div>';

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
                background: #fff;
            }

            #flexpress_coming_soon_whitelist option {
                padding: 4px 8px;
            }

            #flexpress_coming_soon_whitelist option:checked {
                background: #0073aa;
                color: #fff;
            }
        </style>
<?php
    }
}

// Initialize the coming soon settings only in admin
if (is_admin()) {
    new FlexPress_Coming_Soon_Settings();
}
