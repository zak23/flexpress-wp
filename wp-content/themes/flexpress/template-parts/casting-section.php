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
                <h2 class="casting-title text-uppercase mb-3">Want to Join the Cast?</h2>
                <p class="casting-subtitle lead">We're always looking for new talent to join the team</p>
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

                                error_log('FlexPress Casting Section: Loading casting image with ID: ' . $casting_image_id);

                                if ($casting_image_id) {
                                    $image_url = wp_get_attachment_url($casting_image_id);
                                    error_log('FlexPress Casting Section: Using uploaded image: ' . ($image_url ? $image_url : 'No URL found'));
                                    echo wp_get_attachment_image($casting_image_id, 'casting-image', false, array(
                                        'alt' => esc_attr__('Join Our Cast', 'flexpress'),
                                        'class' => 'img-fluid rounded-3 shadow'
                                    ));
                                } else {
                                    error_log('FlexPress Casting Section: No casting image ID, using default SVG placeholder');
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
                                    <li class="casting-benefit-item">
                                        <i class="fas fa-film casting-benefit-icon"></i>
                                        <span class="casting-benefit-text">Professional production environment</span>
                                    </li>
                                    <li class="casting-benefit-item">
                                        <i class="fas fa-calendar-alt casting-benefit-icon"></i>
                                        <span class="casting-benefit-text">Flexible scheduling</span>
                                    </li>
                                    <li class="casting-benefit-item">
                                        <i class="fas fa-heart casting-benefit-icon"></i>
                                        <span class="casting-benefit-text">Safe and respectful workplace</span>
                                    </li>
                                    <li class="casting-benefit-item">
                                        <i class="fas fa-camera casting-benefit-icon"></i>
                                        <span class="casting-benefit-text">Professional photography included</span>
                                    </li>
                                    <li class="casting-benefit-item">
                                        <i class="fas fa-file-signature casting-benefit-icon"></i>
                                        <span class="casting-benefit-text">Flexible content agreements</span>
                                    </li>
                                    <li class="casting-benefit-item">
                                        <i class="fas fa-trophy casting-benefit-icon"></i>
                                        <span class="casting-benefit-text">Award-winning production team</span>
                                    </li>
                                    <li class="casting-benefit-item">
                                        <i class="fas fa-shield-alt casting-benefit-icon"></i>
                                        <span class="casting-benefit-text">Secure, private filming locations</span>
                                    </li>
                                    <li class="casting-benefit-item">
                                        <i class="fas fa-handshake casting-benefit-icon"></i>
                                        <span class="casting-benefit-text">Industry-standard contracts</span>
                                    </li>
                                </ul>

                                <div class="text-center mt-4">
                                    <a href="/casting" class="btn casting-apply-btn btn-lg">Apply Now</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>