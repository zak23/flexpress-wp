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
                <h2 class="section-title"><?php esc_html_e('Featured On', 'flexpress'); ?></h2>
                
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
        // Destroy existing slider if it exists
        if (jQuery('#mediaSlider').hasClass('slick-initialized')) {
            jQuery('#mediaSlider').slick('destroy');
        }
        
        // Count the number of slides
        const slideCount = jQuery('#mediaSlider .media-slide').length;
        
        // Determine slidesToShow based on actual slide count
        // For dots to show properly, we need slidesToShow to be less than total slides
        let slidesToShow = slideCount > 1 ? 1 : slideCount; // Show 1 slide at a time to force dots
        let slidesToShowMedium = slideCount > 1 ? 1 : slideCount;
        let slidesToShowSmall = slideCount > 1 ? 1 : slideCount;
        
        jQuery('#mediaSlider').slick({
            dots: slideCount > 1, // Only show dots if there's more than 1 slide
            infinite: slideCount > 1, // Only enable infinite scroll if there's more than 1 slide
            speed: 500,
            slidesToShow: slidesToShow,
            slidesToScroll: 1,
            autoplay: slideCount > 1, // Only autoplay if there's more than 1 slide
            autoplaySpeed: 3000,
            pauseOnHover: true,
            adaptiveHeight: false,
            variableWidth: false,
            responsive: [
                {
                    breakpoint: 1200,
                    settings: {
                        slidesToShow: slidesToShowMedium,
                        slidesToScroll: 1
                    }
                },
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: slidesToShowSmall,
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
        
        // Force dots to show if we have multiple slides but Slick isn't showing them
        if (slideCount > 1) {
            setTimeout(() => {
                const dotsContainer = jQuery('#mediaSlider').siblings('.slick-dots');
                if (dotsContainer.length === 0 || dotsContainer.find('li').length < slideCount) {
                    console.log('Forcing dots regeneration...');
                    jQuery('#mediaSlider').slick('setPosition');
                }
            }, 100);
        }
    }
});
</script>
