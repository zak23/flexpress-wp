<?php

/**
 * FlexPress Casting Settings
 *
 * Dedicated settings page to configure the Casting section content.
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class FlexPress_Casting_Settings
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_media_scripts'));
    }

    /**
     * Enqueue media and jQuery on our page
     */
    public function enqueue_media_scripts($hook)
    {
        // Detect our settings page hook
        if (strpos($hook, 'flexpress_casting_settings') !== false || strpos($hook, 'flexpress-casting') !== false) {
            wp_enqueue_media();
            wp_enqueue_script('jquery');
        }
    }

    /**
     * Render settings page
     */
    public function render_settings_page()
    {
        if (!function_exists('flexpress_current_user_is_founder') || !flexpress_current_user_is_founder()) {
            echo '<div class="wrap"><h1>' . esc_html__('Casting', 'flexpress') . '</h1><p>' . esc_html__('You do not have permission to view this page.', 'flexpress') . '</p></div>';
            return;
        }

        $options = get_option('flexpress_general_settings', array());

        $title         = isset($options['casting_title']) ? $options['casting_title'] : '';
        $subtitle      = isset($options['casting_subtitle']) ? $options['casting_subtitle'] : '';
        $benefits      = isset($options['casting_benefits']) && is_array($options['casting_benefits']) ? array_values($options['casting_benefits']) : array();
        $button_text   = isset($options['casting_button_text']) ? $options['casting_button_text'] : '';
        $button_url    = isset($options['casting_button_url']) ? $options['casting_button_url'] : '';
        $image_id      = isset($options['casting_image']) ? absint($options['casting_image']) : 0;

        $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : '';
?>
        <div class="wrap">
            <h1><?php echo esc_html__('Casting', 'flexpress'); ?></h1>

            <form method="post" action="options.php">
                <?php settings_fields('flexpress_general_settings'); ?>

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="casting_title"><?php echo esc_html__('Title', 'flexpress'); ?></label></th>
                            <td>
                                <input type="text" id="casting_title" name="flexpress_general_settings[casting_title]" value="<?php echo esc_attr($title); ?>" class="regular-text" />
                                <p class="description"><?php echo esc_html__('Main title displayed for the casting section.', 'flexpress'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="casting_subtitle"><?php echo esc_html__('Subtitle', 'flexpress'); ?></label></th>
                            <td>
                                <textarea id="casting_subtitle" name="flexpress_general_settings[casting_subtitle]" rows="3" class="large-text"><?php echo esc_textarea($subtitle); ?></textarea>
                                <p class="description"><?php echo esc_html__('Subtitle text shown below the title.', 'flexpress'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php echo esc_html__('Benefits', 'flexpress'); ?></th>
                            <td>
                                <div id="casting-benefits">
                                    <?php if (!empty($benefits)) : ?>
                                        <?php foreach ($benefits as $idx => $benefit) : ?>
                                            <?php
                                            $icon_class = isset($benefit['icon_class']) ? $benefit['icon_class'] : '';
                                            $text = isset($benefit['text']) ? $benefit['text'] : '';
                                            ?>
                                            <div class="casting-benefit-item" style="margin-bottom:8px;display:flex;gap:8px;align-items:center;">
                                                <input type="text" name="flexpress_general_settings[casting_benefits][<?php echo esc_attr($idx); ?>][icon_class]" value="<?php echo esc_attr($icon_class); ?>" class="regular-text" placeholder="fas fa-film" style="width:220px;" />
                                                <input type="text" name="flexpress_general_settings[casting_benefits][<?php echo esc_attr($idx); ?>][text]" value="<?php echo esc_attr($text); ?>" class="regular-text" placeholder="Professional production environment" style="flex:1;" />
                                                <button type="button" class="button remove-benefit">&times;</button>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <div class="casting-benefit-item" style="margin-bottom:8px;display:flex;gap:8px;align-items:center;">
                                            <input type="text" name="flexpress_general_settings[casting_benefits][0][icon_class]" value="" class="regular-text" placeholder="fas fa-film" style="width:220px;" />
                                            <input type="text" name="flexpress_general_settings[casting_benefits][0][text]" value="" class="regular-text" placeholder="Professional production environment" style="flex:1;" />
                                            <button type="button" class="button remove-benefit">&times;</button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <p><button type="button" class="button" id="add-benefit"><?php echo esc_html__('Add Benefit', 'flexpress'); ?></button></p>
                                <p class="description"><?php echo esc_html__('Enter FontAwesome icon classes (e.g., "fas fa-film") and a short benefit text.', 'flexpress'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="casting_button_text"><?php echo esc_html__('Button Text', 'flexpress'); ?></label></th>
                            <td>
                                <input type="text" id="casting_button_text" name="flexpress_general_settings[casting_button_text]" value="<?php echo esc_attr($button_text); ?>" class="regular-text" />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="casting_button_url"><?php echo esc_html__('Button URL', 'flexpress'); ?></label></th>
                            <td>
                                <input type="url" id="casting_button_url" name="flexpress_general_settings[casting_button_url]" value="<?php echo esc_attr($button_url); ?>" class="regular-text" />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php echo esc_html__('Casting Image', 'flexpress'); ?></th>
                            <td>
                                <input type="hidden" id="flexpress_casting_image_hidden" name="flexpress_general_settings[casting_image]" value="<?php echo esc_attr($image_id); ?>" />
                                <button type="button" class="button" id="upload_casting_image"><?php echo esc_html__('Select Image', 'flexpress'); ?></button>
                                <button type="button" class="button" id="remove_casting_image" <?php echo $image_id ? '' : 'style="display:none;"'; ?>><?php echo esc_html__('Remove Image', 'flexpress'); ?></button>
                                <div id="casting_image_preview" style="margin-top:10px;<?php echo $image_url ? '' : 'display:none;'; ?>">
                                    <?php if ($image_url) : ?>
                                        <img src="<?php echo esc_url($image_url); ?>" style="max-width:300px;height:auto;" />
                                    <?php endif; ?>
                                </div>
                                <p class="description"><?php echo esc_html__('Upload an image for the casting section. Recommended size: 600x400px.', 'flexpress'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Benefits repeater
                var container = $('#casting-benefits');
                var addBtn = $('#add-benefit');

                addBtn.on('click', function() {
                    var idx = container.children('.casting-benefit-item').length;
                    var row = $('<div class="casting-benefit-item" style="margin-bottom:8px;display:flex;gap:8px;align-items:center;"></div>');
                    row.html('<input type="text" name="flexpress_general_settings[casting_benefits][' + idx + '][icon_class]" value="" class="regular-text" placeholder="fas fa-film" style="width:220px;" />' +
                        '<input type="text" name="flexpress_general_settings[casting_benefits][' + idx + '][text]" value="" class="regular-text" placeholder="Professional production environment" style="flex:1;" />' +
                        '<button type="button" class="button remove-benefit">&times;</button>');
                    container.append(row);
                });

                container.on('click', '.remove-benefit', function(e) {
                    e.preventDefault();
                    var rows = container.children('.casting-benefit-item');
                    if (rows.length > 1) {
                        $(this).closest('.casting-benefit-item').remove();
                    } else {
                        $(this).closest('.casting-benefit-item').find('input').val('');
                    }
                });

                // Media uploader
                var uploadBtn = $('#upload_casting_image');
                var removeBtn = $('#remove_casting_image');
                var hidden = $('#flexpress_casting_image_hidden');
                var preview = $('#casting_image_preview');
                var frame;

                uploadBtn.on('click', function(e) {
                    e.preventDefault();
                    if (frame) {
                        frame.open();
                        return;
                    }
                    frame = wp.media({
                        title: '<?php echo esc_js(__('Select or Upload Casting Image', 'flexpress')); ?>',
                        button: {
                            text: '<?php echo esc_js(__('Use this image', 'flexpress')); ?>'
                        },
                        multiple: false
                    });
                    frame.on('select', function() {
                        var attachment = frame.state().get('selection').first().toJSON();
                        hidden.val(attachment.id);
                        if (preview.find('img').length === 0) {
                            preview.html('<img src="" style="max-width:300px;height:auto;" />');
                        }
                        preview.find('img').attr('src', attachment.url);
                        preview.show();
                        removeBtn.show();
                    });
                    frame.open();
                });

                removeBtn.on('click', function(e) {
                    e.preventDefault();
                    hidden.val('');
                    preview.html('');
                    preview.hide();
                    removeBtn.hide();
                });
            });
        </script>
<?php
    }
}
