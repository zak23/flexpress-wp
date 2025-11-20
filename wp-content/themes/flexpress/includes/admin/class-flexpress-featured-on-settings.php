<?php

/**
 * FlexPress Featured On Settings
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * FlexPress Featured On Settings Class
 */
class FlexPress_Featured_On_Settings
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
     * Register featured on settings
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
            'flexpress_featured_on_section',
            __('Featured On Section', 'flexpress'),
            array($this, 'render_section_description'),
            'flexpress_featured_on_settings'
        );

        // Add Featured On enable field
        add_settings_field(
            'flexpress_featured_on_enabled',
            __('Enable Featured On Section', 'flexpress'),
            array($this, 'render_featured_on_enabled_field'),
            'flexpress_featured_on_settings',
            'flexpress_featured_on_section'
        );

        // Add Featured On media outlets field
        add_settings_field(
            'flexpress_featured_on_media',
            __('Media Outlets', 'flexpress'),
            array($this, 'render_featured_on_media_field'),
            'flexpress_featured_on_settings',
            'flexpress_featured_on_section'
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
     * Render the featured on settings page
     */
    public function render_featured_on_settings_page()
    {
?>
        <div class="wrap">
            <h1><?php echo esc_html__('Featured On Section', 'flexpress'); ?></h1>

            <form method="post" action="options.php">
                <?php
                settings_fields('flexpress_general_settings');
                do_settings_sections('flexpress_featured_on_settings');
                submit_button();
                ?>
            </form>
        </div>
    <?php
    }

    /**
     * Render Featured On section description
     */
    public function render_section_description()
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
            <input type="hidden" name="flexpress_general_settings[featured_on_enabled]" value="0">
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
                    'name' => 'Adult Awards',
                    'url' => 'https://adultawards.com.au',
                    'logo_id' => '',
                    'logo' => '',
                    'alt' => 'Adult Awards'
                ),
                array(
                    'name' => 'Adult Industry Awards',
                    'url' => 'https://adultawards.com.au',
                    'logo_id' => '',
                    'logo' => '',
                    'alt' => 'Adult Industry Awards'
                )
            );
        }
    ?>
        <div id="featured-on-media-container">
            <p class="description" style="margin-bottom: 20px;">
                <?php esc_html_e('Add media outlets and publications that have featured your site. Each outlet will appear as a slide in the Featured On carousel. Use the arrow buttons to reorder items.', 'flexpress'); ?>
            </p>

            <div id="featured-on-media-list">
                <?php foreach ($media_outlets as $index => $outlet): ?>
                    <div class="featured-on-outlet" data-index="<?php echo $index; ?>">
                        <div class="featured-on-outlet-header">
                            <h4><?php printf(esc_html__('Media Outlet %d', 'flexpress') ?: 'Media Outlet %d', $index + 1); ?></h4>
                            <div class="featured-on-outlet-actions">
                                <button type="button" class="button button-small move-outlet-up" data-index="<?php echo $index; ?>" title="<?php esc_attr_e('Move Up', 'flexpress'); ?>" <?php echo $index === 0 ? 'disabled' : ''; ?>>
                                    <span class="dashicons dashicons-arrow-up-alt"></span>
                                </button>
                                <button type="button" class="button button-small move-outlet-down" data-index="<?php echo $index; ?>" title="<?php esc_attr_e('Move Down', 'flexpress'); ?>" <?php echo $index === count($media_outlets) - 1 ? 'disabled' : ''; ?>>
                                    <span class="dashicons dashicons-arrow-down-alt"></span>
                                </button>
                                <button type="button" class="button button-secondary remove-outlet" data-index="<?php echo $index; ?>">
                                    <?php esc_html_e('Remove Outlet', 'flexpress'); ?>
                                </button>
                            </div>
                        </div>
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
                                        <?php if (!empty($outlet['logo_id'])): ?>
                                            <?php
                                            $logo_url = wp_get_attachment_url($outlet['logo_id']);
                                            $logo_alt = !empty($outlet['alt']) ? $outlet['alt'] : $outlet['name'];
                                            ?>
                                            <div class="featured-on-logo-preview">
                                                <img src="<?php echo esc_url($logo_url); ?>"
                                                    style="max-width: 200px; height: auto; margin-bottom: 10px;"
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
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="button" id="add-media-outlet" class="button button-secondary">
                <?php esc_html_e('Add Media Outlet', 'flexpress'); ?>
            </button>
        </div>

        <p class="description">
            <?php esc_html_e('Add media outlets and publications that have featured your site. Each outlet will appear as a slide in the Featured On carousel.', 'flexpress'); ?>
        </p>

        <style>
            .featured-on-outlet {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                padding: 15px 20px;
                margin-bottom: 15px;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
                transition: all 0.2s ease;
            }
            .featured-on-outlet:hover {
                border-color: #8c8f94;
                box-shadow: 0 1px 3px rgba(0,0,0,.1);
            }
            .featured-on-outlet-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 15px;
                padding-bottom: 12px;
                border-bottom: 1px solid #e5e5e5;
            }
            .featured-on-outlet-header h4 {
                margin: 0;
                font-size: 14px;
                font-weight: 600;
                color: #1d2327;
            }
            .featured-on-outlet-actions {
                display: flex;
                gap: 6px;
                align-items: center;
            }
            .move-outlet-up,
            .move-outlet-down {
                padding: 0 !important;
                height: 32px !important;
                width: 32px !important;
                min-width: 32px !important;
                line-height: 32px !important;
                border-radius: 3px !important;
                border: 1px solid #c3c4c7 !important;
                background: #f6f7f7 !important;
                color: #50575e !important;
                cursor: pointer;
                transition: all 0.15s ease;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }
            .move-outlet-up:hover:not(:disabled),
            .move-outlet-down:hover:not(:disabled) {
                background: #f0f0f1 !important;
                border-color: #8c8f94 !important;
                color: #1d2327 !important;
            }
            .move-outlet-up .dashicons,
            .move-outlet-down .dashicons {
                font-size: 18px;
                width: 18px;
                height: 18px;
                line-height: 18px;
                margin: 0;
            }
            .move-outlet-up:disabled,
            .move-outlet-down:disabled {
                opacity: 0.4;
                cursor: not-allowed;
                background: #f6f7f7 !important;
            }
            .remove-outlet {
                margin-left: 8px;
            }
            #featured-on-media-list {
                margin-bottom: 20px;
            }
            #featured-on-media-container {
                margin-top: 15px;
            }
            #add-media-outlet {
                margin-top: 10px;
            }
        </style>

        <script>
            jQuery(document).ready(function($) {
                var mediaIndex = <?php echo count($media_outlets); ?>;
                var mediaUploaders = {};

                // Add new media outlet
                $('#add-media-outlet').on('click', function() {
                    var newOutletHtml =
                        '<div class="featured-on-outlet" data-index="' + mediaIndex + '">' +
                        '<div class="featured-on-outlet-header">' +
                        '<h4>Media Outlet ' + (mediaIndex + 1) + '</h4>' +
                        '<div class="featured-on-outlet-actions">' +
                        '<button type="button" class="button button-small move-outlet-up" data-index="' + mediaIndex + '" title="<?php esc_attr_e('Move Up', 'flexpress'); ?>"><span class="dashicons dashicons-arrow-up-alt"></span></button>' +
                        '<button type="button" class="button button-small move-outlet-down" data-index="' + mediaIndex + '" title="<?php esc_attr_e('Move Down', 'flexpress'); ?>"><span class="dashicons dashicons-arrow-down-alt"></span></button>' +
                        '<button type="button" class="button button-secondary remove-outlet" data-index="' + mediaIndex + '"><?php esc_html_e('Remove Outlet', 'flexpress'); ?></button>' +
                        '</div>' +
                        '</div>' +
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
                        '</div>';

                    $('#featured-on-media-list').append(newOutletHtml);
                    mediaIndex++;
                    updateOutletNumbers();
                });

                // Remove media outlet
                $(document).on('click', '.remove-outlet', function() {
                    $(this).closest('.featured-on-outlet').remove();
                    updateOutletNumbers();
                });

                // Move outlet up
                $(document).on('click', '.move-outlet-up', function() {
                    var $outlet = $(this).closest('.featured-on-outlet');
                    var $prev = $outlet.prev('.featured-on-outlet');
                    if ($prev.length) {
                        $outlet.insertBefore($prev);
                        updateOutletNumbers();
                    }
                });

                // Move outlet down
                $(document).on('click', '.move-outlet-down', function() {
                    var $outlet = $(this).closest('.featured-on-outlet');
                    var $next = $outlet.next('.featured-on-outlet');
                    if ($next.length) {
                        $outlet.insertAfter($next);
                        updateOutletNumbers();
                    }
                });

                // Upload logo
                $(document).on('click', '.upload-logo', function(e) {
                    e.preventDefault();
                    var index = $(this).data('index');
                    var button = $(this);

                    // If the media uploader already exists, open it
                    if (mediaUploaders[index]) {
                        mediaUploaders[index].open();
                        return;
                    }

                    // Create the media uploader
                    mediaUploaders[index] = wp.media({
                        title: '<?php esc_html_e('Select Media Outlet Logo', 'flexpress'); ?>',
                        button: {
                            text: '<?php esc_html_e('Use this image', 'flexpress'); ?>'
                        },
                        multiple: false
                    });

                    // When an image is selected, run a callback
                    mediaUploaders[index].on('select', function() {
                        var attachment = mediaUploaders[index].state().get('selection').first().toJSON();
                        button.siblings('input[type="hidden"]').val(attachment.id);

                        // Update preview
                        if (attachment.url) {
                            var preview = button.closest('.featured-on-logo-upload').find('.featured-on-logo-preview');
                            if (preview.length === 0) {
                                preview = $('<div class="featured-on-logo-preview"></div>');
                                button.before(preview);
                            }
                            preview.html('<img src="' + attachment.url + '" style="max-width: 200px; height: auto; margin-bottom: 10px;" />');

                            // Show remove button
                            if (button.closest('.featured-on-logo-upload').find('.remove-logo').length === 0) {
                                button.after('<button type="button" class="button remove-logo" data-index="' + index + '"><?php esc_html_e('Remove Logo', 'flexpress'); ?></button>');
                            }
                        }
                    });

                    // Open the media uploader
                    mediaUploaders[index].open();
                });

                // Remove logo
                $(document).on('click', '.remove-logo', function() {
                    var index = $(this).data('index');
                    $(this).closest('.featured-on-logo-upload').find('input[type="hidden"]').val('');
                    $(this).closest('.featured-on-logo-upload').find('.featured-on-logo-preview').remove();
                    $(this).remove();
                });

                // Update outlet numbers
                function updateOutletNumbers() {
                    var $outlets = $('#featured-on-media-list .featured-on-outlet');
                    var totalOutlets = $outlets.length;
                    
                    $outlets.each(function(newIndex) {
                        var $outlet = $(this);
                        $outlet.attr('data-index', newIndex);
                        $outlet.find('h4').text('Media Outlet ' + (newIndex + 1));
                        
                        // Update all inputs and buttons
                        $outlet.find('input, button').each(function() {
                            var name = $(this).attr('name');
                            var id = $(this).attr('id');
                            var dataIndex = $(this).attr('data-index');
                            
                            if (name) {
                                $(this).attr('name', name.replace(/\[\d+\]/, '[' + newIndex + ']'));
                            }
                            if (id) {
                                $(this).attr('id', id.replace(/\d+/, newIndex));
                            }
                            if (dataIndex !== undefined) {
                                $(this).attr('data-index', newIndex);
                            }
                        });
                        
                        // Update arrow button states
                        var $upBtn = $outlet.find('.move-outlet-up');
                        var $downBtn = $outlet.find('.move-outlet-down');
                        
                        if (newIndex === 0) {
                            $upBtn.prop('disabled', true);
                        } else {
                            $upBtn.prop('disabled', false);
                        }
                        
                        if (newIndex === totalOutlets - 1) {
                            $downBtn.prop('disabled', true);
                        } else {
                            $downBtn.prop('disabled', false);
                        }
                    });
                }
            });
        </script>
<?php
    }
}

// Initialize the featured on settings only in admin
if (is_admin()) {
    new FlexPress_Featured_On_Settings();
}
