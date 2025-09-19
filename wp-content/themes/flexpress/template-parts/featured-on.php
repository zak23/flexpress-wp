<?php
/**
 * Template part for displaying the "Featured on" section
 * Shows media outlets and publications that have featured the site
 */

// Check if Featured On section is enabled
if (!flexpress_is_featured_on_enabled()) {
    return;
}

// Get featured media outlets from settings
$featured_media = flexpress_get_featured_on_media();

// Don't display section if no media outlets are configured
if (empty($featured_media)) {
    return;
}
?>

<section class="featured-on-section py-5">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h2 class="section-title text-center mb-5"><?php esc_html_e('Featured On', 'flexpress'); ?></h2>
                
                <div class="media-slider-wrapper">
                    <div class="media-slider" id="mediaSlider">
                        <?php foreach ($featured_media as $media): ?>
                        <div class="media-slide">
                            <div class="text-center d-flex flex-column align-items-center">
                                <a href="<?php echo esc_url($media['url']); ?>" target="_blank" class="d-inline-block media-link" rel="noopener">
                                    <?php if (!empty($media['logo_id'])): 
                                        $logo_image = wp_get_attachment_image($media['logo_id'], 'medium', false, array(
                                            'class' => 'img-fluid media-logo',
                                            'alt' => esc_attr($media['alt']),
                                            'loading' => 'lazy'
                                        ));
                                        echo $logo_image;
                                    else: ?>
                                        <img src="<?php echo esc_url($media['logo']); ?>" 
                                             class="img-fluid media-logo" 
                                             alt="<?php echo esc_attr($media['alt']); ?>"
                                             loading="lazy">
                                    <?php endif; ?>
                                    <p class="media-name"><?php echo esc_html($media['name']); ?></p>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Slick slider for media outlets
    if (typeof jQuery !== 'undefined' && jQuery.fn.slick) {
        jQuery('#mediaSlider').slick({
            dots: true,
            infinite: true,
            speed: 500,
            slidesToShow: 4,
            slidesToScroll: 1,
            autoplay: true,
            autoplaySpeed: 3000,
            pauseOnHover: true,
            responsive: [
                {
                    breakpoint: 1200,
                    settings: {
                        slidesToShow: 3,
                        slidesToScroll: 1
                    }
                },
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: 2,
                        slidesToScroll: 1
                    }
                },
                {
                    breakpoint: 480,
                    settings: {
                        slidesToShow: 1,
                        slidesToScroll: 1
                    }
                }
            ]
        });
    }
});
</script>
