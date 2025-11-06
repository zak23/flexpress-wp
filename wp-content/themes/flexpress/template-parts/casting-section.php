<?php

/**
 * Template part for displaying the casting section
 *
 * @package FlexPress
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="casting-section bg-black text-white py-5">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center mb-5">
                <?php
                $options = get_option('flexpress_general_settings', array());
                $casting_title = isset($options['casting_title']) && $options['casting_title'] !== ''
                    ? $options['casting_title']
                    : __('Want to Join the Cast?', 'flexpress');
                $casting_subtitle = isset($options['casting_subtitle']) && $options['casting_subtitle'] !== ''
                    ? $options['casting_subtitle']
                    : __('We\'re always looking for new talent to join the team', 'flexpress');
                ?>
                <h2 class="casting-title text-uppercase mb-3"><?php echo esc_html($casting_title); ?></h2>
                <p class="casting-subtitle lead"><?php echo esc_html($casting_subtitle); ?></p>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="casting-content-container bg-dark rounded-3 p-4 shadow-lg">
                    <div class="row align-items-center">
                        <!-- Large Image on the Left -->
                        <div class="col-md-6 mb-4 mb-md-0">
                            <div class="casting-image">
                                <?php
                                // Get casting image from theme options or use default
                                $general_settings = get_option('flexpress_general_settings', array());
                                $casting_image_id = isset($general_settings['casting_image']) ? $general_settings['casting_image'] : '';


                                if ($casting_image_id) {
                                    $image_url = wp_get_attachment_url($casting_image_id);
                                    echo wp_get_attachment_image($casting_image_id, 'casting-image', false, array(
                                        'alt' => esc_attr__('Join Our Cast', 'flexpress'),
                                        'class' => 'img-fluid rounded-3 shadow'
                                    ));
                                } else {
                                    // Fallback to a default image or placeholder
                                    echo '<img src="' . get_template_directory_uri() . '/assets/images/casting-default.svg" alt="' . esc_attr__('Join Our Cast', 'flexpress') . '" class="img-fluid rounded-3 shadow">';
                                }
                                ?>
                            </div>
                        </div>

                        <!-- Benefits on the Right -->
                        <div class="col-md-6">
                            <div class="casting-benefits">
                                <ul class="casting-benefits-list list-unstyled">
                                    <?php
                                    $benefits = array();
                                    if (isset($options['casting_benefits']) && is_array($options['casting_benefits'])) {
                                        foreach ($options['casting_benefits'] as $benefit) {
                                            $icon_class = isset($benefit['icon_class']) ? trim((string) $benefit['icon_class']) : '';
                                            $text = isset($benefit['text']) ? trim((string) $benefit['text']) : '';
                                            if ($icon_class !== '' || $text !== '') {
                                                $benefits[] = array(
                                                    'icon_class' => $icon_class,
                                                    'text' => $text,
                                                );
                                            }
                                        }
                                    }

                                    if (empty($benefits)) {
                                        $benefits = array(
                                            array('icon_class' => 'fas fa-film', 'text' => __('Professional production environment', 'flexpress')),
                                            array('icon_class' => 'fas fa-calendar-alt', 'text' => __('Flexible scheduling', 'flexpress')),
                                            array('icon_class' => 'fas fa-heart', 'text' => __('Safe and respectful workplace', 'flexpress')),
                                            array('icon_class' => 'fas fa-camera', 'text' => __('Professional photography included', 'flexpress')),
                                            array('icon_class' => 'fas fa-file-signature', 'text' => __('Flexible content agreements', 'flexpress')),
                                            array('icon_class' => 'fas fa-trophy', 'text' => __('Award-winning production team', 'flexpress')),
                                            array('icon_class' => 'fas fa-shield-alt', 'text' => __('Secure, private filming locations', 'flexpress')),
                                            array('icon_class' => 'fas fa-handshake', 'text' => __('Industry-standard contracts', 'flexpress')),
                                        );
                                    }
                                    ?>
                                    <?php foreach ($benefits as $benefit) : ?>
                                        <li class="casting-benefit-item">
                                            <i class="<?php echo esc_attr($benefit['icon_class']); ?> casting-benefit-icon"></i>
                                            <span class="casting-benefit-text"><?php echo esc_html($benefit['text']); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>

                                <div class="text-center mt-4">
                                    <?php
                                    $btn_text = isset($options['casting_button_text']) && $options['casting_button_text'] !== ''
                                        ? $options['casting_button_text']
                                        : __('Apply Now', 'flexpress');
                                    $btn_url = isset($options['casting_button_url']) && $options['casting_button_url'] !== ''
                                        ? $options['casting_button_url']
                                        : '/casting';
                                    ?>
                                    <a href="<?php echo esc_url($btn_url); ?>" class="btn casting-apply-btn btn-lg"><?php echo esc_html($btn_text); ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>