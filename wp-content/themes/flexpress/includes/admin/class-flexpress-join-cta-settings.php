<?php

/**
 * FlexPress Join CTA Settings
 *
 * Dedicated settings page to configure the Join Now CTA content.
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class FlexPress_Join_CTA_Settings
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_media_scripts'));
    }

    /**
     * Enqueue media on our page
     */
    public function enqueue_media_scripts($hook)
    {
        // Check if we're on the Join CTA settings page
        if (strpos($hook, 'flexpress_join_cta_settings') !== false || strpos($hook, 'flexpress-join-cta') !== false) {
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
            echo '<div class="wrap"><h1>' . esc_html__('Join CTA', 'flexpress') . '</h1><p>' . esc_html__('You do not have permission to view this page.', 'flexpress') . '</p></div>';
            return;
        }

        $options = get_option('flexpress_general_settings', array());

        $headline       = isset($options['join_cta_headline']) ? $options['join_cta_headline'] : '';
        $subtitle       = isset($options['join_cta_subtitle']) ? $options['join_cta_subtitle'] : '';
        $features       = isset($options['join_cta_features']) && is_array($options['join_cta_features']) ? array_values(array_filter($options['join_cta_features'], 'strlen')) : array();
        $offer_text     = isset($options['join_cta_offer_text']) ? $options['join_cta_offer_text'] : '';
        $button_text    = isset($options['join_cta_button_text']) ? $options['join_cta_button_text'] : '';
        $button_url     = isset($options['join_cta_button_url']) ? $options['join_cta_button_url'] : '';
        $security_text  = isset($options['join_cta_security_text']) ? $options['join_cta_security_text'] : '';
        $login_prompt   = isset($options['join_cta_login_prompt']) ? $options['join_cta_login_prompt'] : '';
        $login_text     = isset($options['join_cta_login_text']) ? $options['join_cta_login_text'] : '';
        $login_url      = isset($options['join_cta_login_url']) ? $options['join_cta_login_url'] : '';
        $image_id       = isset($options['join_cta_image']) ? absint($options['join_cta_image']) : 0;

        $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : '';
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Join CTA', 'flexpress'); ?></h1>

            <form method="post" action="options.php">
                <?php settings_fields('flexpress_general_settings'); ?>

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="join_cta_headline"><?php echo esc_html__('Headline', 'flexpress'); ?></label></th>
                            <td>
                                <input type="text" id="join_cta_headline" name="flexpress_general_settings[join_cta_headline]" value="<?php echo esc_attr($headline); ?>" class="regular-text" />
                                <p class="description"><?php echo esc_html__('Main title displayed in the CTA.', 'flexpress'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="join_cta_subtitle"><?php echo esc_html__('Subtitle', 'flexpress'); ?></label></th>
                            <td>
                                <textarea id="join_cta_subtitle" name="flexpress_general_settings[join_cta_subtitle]" rows="3" class="large-text"><?php echo esc_textarea($subtitle); ?></textarea>
                                <p class="description"><?php echo esc_html__('Subtitle text below the headline.', 'flexpress'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php echo esc_html__('Features', 'flexpress'); ?></th>
                            <td>
                                <div id="join-cta-features">
                                    <?php if (!empty($features)) : ?>
                                        <?php foreach ($features as $idx => $feature_text) : ?>
                                            <div class="join-cta-feature-item" style="margin-bottom:8px;display:flex;gap:8px;align-items:center;">
                                                <input type="text" name="flexpress_general_settings[join_cta_features][]" value="<?php echo esc_attr($feature_text); ?>" class="regular-text" style="flex:1;" />
                                                <button type="button" class="button remove-feature">&times;</button>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <div class="join-cta-feature-item" style="margin-bottom:8px;display:flex;gap:8px;align-items:center;">
                                            <input type="text" name="flexpress_general_settings[join_cta_features][]" value="" class="regular-text" style="flex:1;" />
                                            <button type="button" class="button remove-feature">&times;</button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <p><button type="button" class="button" id="add-feature"><?php echo esc_html__('Add Feature', 'flexpress'); ?></button></p>
                                <p class="description"><?php echo esc_html__('Add one feature per line. Empty lines are ignored.', 'flexpress'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="join_cta_offer_text"><?php echo esc_html__('Offer Text', 'flexpress'); ?></label></th>
                            <td>
                                <input type="text" id="join_cta_offer_text" name="flexpress_general_settings[join_cta_offer_text]" value="<?php echo esc_attr($offer_text); ?>" class="regular-text" />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="join_cta_button_text"><?php echo esc_html__('Button Text', 'flexpress'); ?></label></th>
                            <td>
                                <input type="text" id="join_cta_button_text" name="flexpress_general_settings[join_cta_button_text]" value="<?php echo esc_attr($button_text); ?>" class="regular-text" />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="join_cta_button_url"><?php echo esc_html__('Button URL', 'flexpress'); ?></label></th>
                            <td>
                                <input type="url" id="join_cta_button_url" name="flexpress_general_settings[join_cta_button_url]" value="<?php echo esc_attr($button_url); ?>" class="regular-text" />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="join_cta_security_text"><?php echo esc_html__('Security Note', 'flexpress'); ?></label></th>
                            <td>
                                <input type="text" id="join_cta_security_text" name="flexpress_general_settings[join_cta_security_text]" value="<?php echo esc_attr($security_text); ?>" class="regular-text" />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="join_cta_login_prompt"><?php echo esc_html__('Login Prompt', 'flexpress'); ?></label></th>
                            <td>
                                <input type="text" id="join_cta_login_prompt" name="flexpress_general_settings[join_cta_login_prompt]" value="<?php echo esc_attr($login_prompt); ?>" class="regular-text" />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="join_cta_login_text"><?php echo esc_html__('Login Link Text', 'flexpress'); ?></label></th>
                            <td>
                                <input type="text" id="join_cta_login_text" name="flexpress_general_settings[join_cta_login_text]" value="<?php echo esc_attr($login_text); ?>" class="regular-text" />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="join_cta_login_url"><?php echo esc_html__('Login URL', 'flexpress'); ?></label></th>
                            <td>
                                <input type="url" id="join_cta_login_url" name="flexpress_general_settings[join_cta_login_url]" value="<?php echo esc_attr($login_url); ?>" class="regular-text" />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php echo esc_html__('CTA Image', 'flexpress'); ?></th>
                            <td>
                                <input type="hidden" id="flexpress_join_cta_image_alt" name="flexpress_general_settings[join_cta_image]" value="<?php echo esc_attr($image_id); ?>" />
                                <button type="button" class="button" id="upload_join_cta_image"><?php echo esc_html__('Select Image', 'flexpress'); ?></button>
                                <button type="button" class="button" id="remove_join_cta_image" <?php echo $image_id ? '' : 'style="display:none;"'; ?>><?php echo esc_html__('Remove Image', 'flexpress'); ?></button>
                                <div id="join_cta_image_preview" style="margin-top:10px;<?php echo $image_url ? '' : 'display:none;'; ?>">
                                    <?php if ($image_url) : ?>
                                        <img src="<?php echo esc_url($image_url); ?>" style="max-width:300px;height:auto;" />
                                    <?php endif; ?>
                                </div>
                                <p class="description"><?php echo esc_html__('Optional: override the CTA image here (same as General → Join CTA Image).', 'flexpress'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Features add/remove
                var container = $('#join-cta-features');
                var addBtn = $('#add-feature');
                
                addBtn.on('click', function() {
                    var wrap = $('<div class="join-cta-feature-item" style="margin-bottom:8px;display:flex;gap:8px;align-items:center;"></div>');
                    wrap.html('<input type="text" name="flexpress_general_settings[join_cta_features][]" value="" class="regular-text" style="flex:1;" />' +
                             '<button type="button" class="button remove-feature">&times;</button>');
                    container.append(wrap);
                });

                container.on('click', '.remove-feature', function(e) {
                    e.preventDefault();
                    var row = $(this).closest('.join-cta-feature-item');
                    if (container.children('.join-cta-feature-item').length > 1) {
                        row.remove();
                    } else {
                        row.find('input').val('');
                    }
                });

                // Media uploader
                var uploadBtn = $('#upload_join_cta_image');
                var removeBtn = $('#remove_join_cta_image');
                var hidden = $('#flexpress_join_cta_image_alt');
                var preview = $('#join_cta_image_preview');
                var frame;

                uploadBtn.on('click', function(e) {
                    e.preventDefault();

                    if (frame) {
                        frame.open();
                        return;
                    }

                    frame = wp.media({
                        title: '<?php echo esc_js(__('Select or Upload Join CTA Image', 'flexpress')); ?>',
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


