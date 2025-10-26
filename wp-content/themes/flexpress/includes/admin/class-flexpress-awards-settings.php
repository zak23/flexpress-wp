<?php

/**
 * FlexPress Awards & Recognition Settings
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * FlexPress Awards & Recognition Settings Class
 */
class FlexPress_Awards_Settings
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
     * Register awards settings
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
            'flexpress_awards_section',
            __('Awards & Recognition', 'flexpress'),
            array($this, 'render_section_description'),
            'flexpress_awards_settings'
        );

        // Add awards section enable/disable field
        add_settings_field(
            'flexpress_awards_enabled',
            __('Enable Awards Section', 'flexpress'),
            array($this, 'render_awards_enabled_field'),
            'flexpress_awards_settings',
            'flexpress_awards_section'
        );

        // Add awards title field
        add_settings_field(
            'flexpress_awards_title',
            __('Awards Section Title', 'flexpress'),
            array($this, 'render_awards_title_field'),
            'flexpress_awards_settings',
            'flexpress_awards_section'
        );

        // Add awards repeater field
        add_settings_field(
            'flexpress_awards_list',
            __('Awards & Recognitions', 'flexpress'),
            array($this, 'render_awards_list_field'),
            'flexpress_awards_settings',
            'flexpress_awards_section'
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
     * Render the awards settings page
     */
    public function render_awards_settings_page()
    {
?>
        <div class="wrap">
            <h1><?php echo esc_html__('Awards & Recognition', 'flexpress'); ?></h1>

            <form method="post" action="options.php">
                <?php
                settings_fields('flexpress_general_settings');
                do_settings_sections('flexpress_awards_settings');
                submit_button();
                ?>
            </form>
        </div>
    <?php
    }

    /**
     * Render awards section description
     */
    public function render_section_description()
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
                                <?php esc_html_e('Remove Award', 'flexpress'); ?>
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
                                    if (!empty($award['logo_id'])) {
                                        $logo_url = wp_get_attachment_url($award['logo_id']);
                                        if ($logo_url) {
                                            echo '<div class="award-logo-preview">';
                                            echo '<img src="' . esc_url($logo_url) . '" style="max-width: 200px; height: auto; margin-bottom: 10px;" />';
                                            echo '</div>';
                                        }
                                    }
                                    ?>

                                    <button type="button" class="button upload-award-logo" data-index="<?php echo $index; ?>">
                                        <?php esc_html_e('Upload Logo', 'flexpress'); ?>
                                    </button>
                                    <button type="button" class="button remove-award-logo" data-index="<?php echo $index; ?>" style="display: <?php echo !empty($award['logo_id']) ? 'inline-block' : 'none'; ?>;">
                                        <?php esc_html_e('Remove Logo', 'flexpress'); ?>
                                    </button>
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
                                    <p class="description"><?php esc_html_e('Optional: Link to the award page or announcement', 'flexpress'); ?></p>
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
                                    <p class="description"><?php esc_html_e('Alt text for accessibility', 'flexpress'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="button" id="add-award" class="button button-secondary">
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
                                    <?php esc_html_e('Remove Award', 'flexpress'); ?>
                                </button>
                            </div>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="award_title_${awardIndex}"><?php esc_html_e('Award Title', 'flexpress'); ?></label>
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
                                        <label for="award_logo_${awardIndex}"><?php esc_html_e('Award Logo', 'flexpress'); ?></label>
                                    </th>
                                    <td>
                                        <input type="hidden" 
                                               id="award_logo_id_${awardIndex}"
                                               name="flexpress_general_settings[awards_list][${awardIndex}][logo_id]" 
                                               value="">
                                        
                                        <input type="button" 
                                               class="button upload-award-logo" 
                                               data-index="${awardIndex}"
                                               value="<?php esc_html_e('Upload Logo', 'flexpress'); ?>">
                                        <input type="button" 
                                               class="button remove-award-logo" 
                                               data-index="${awardIndex}"
                                               value="<?php esc_html_e('Remove Logo', 'flexpress'); ?>"
                                               style="display: none;">
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="award_link_${awardIndex}"><?php esc_html_e('Award Link', 'flexpress'); ?></label>
                                    </th>
                                    <td>
                                        <input type="url" 
                                               id="award_link_${awardIndex}"
                                               name="flexpress_general_settings[awards_list][${awardIndex}][link]" 
                                               value="" 
                                               class="regular-text"
                                               placeholder="https://example.com/award">
                                        <p class="description"><?php esc_html_e('Optional: Link to the award page or announcement', 'flexpress'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="award_alt_${awardIndex}"><?php esc_html_e('Alt Text', 'flexpress'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" 
                                               id="award_alt_${awardIndex}"
                                               name="flexpress_general_settings[awards_list][${awardIndex}][alt]" 
                                               value="" 
                                               class="regular-text"
                                               placeholder="Award">
                                        <p class="description"><?php esc_html_e('Alt text for accessibility', 'flexpress'); ?></p>
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
                $(document).on('click', '.upload-award-logo', function(e) {
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
                        title: '<?php esc_html_e('Select Award Logo', 'flexpress'); ?>',
                        button: {
                            text: '<?php esc_html_e('Use this image', 'flexpress'); ?>'
                        },
                        multiple: false
                    });

                    // When an image is selected, run a callback
                    mediaUploaders[index].on('select', function() {
                        var attachment = mediaUploaders[index].state().get('selection').first().toJSON();
                        $('#award_logo_id_' + index).val(attachment.id);

                        // Update preview
                        if (attachment.url) {
                            var preview = button.closest('td').find('.award-logo-preview');
                            if (preview.length === 0) {
                                preview = $('<div class="award-logo-preview"></div>');
                                button.before(preview);
                            }
                            preview.html('<img src="' + attachment.url + '" style="max-width: 200px; height: auto; margin-bottom: 10px;" />');
                            button.closest('td').find('.remove-award-logo').show();
                        }
                    });

                    // Open the media uploader
                    mediaUploaders[index].open();
                });

                // Remove award logo
                $(document).on('click', '.remove-award-logo', function() {
                    var index = $(this).data('index');
                    $('#award_logo_id_' + index).val('');
                    $(this).closest('td').find('.award-logo-preview').remove();
                    $(this).hide();
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
<?php
    }
}

// Initialize the awards settings only in admin
if (is_admin()) {
    new FlexPress_Awards_Settings();
}
