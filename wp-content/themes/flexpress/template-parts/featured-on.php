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
    function initializeFeaturedOnCarousel() {
        // Check if jQuery and Slick are available
        if (typeof jQuery === 'undefined' || !jQuery.fn.slick) {
            // If not available, wait and try again
            setTimeout(initializeFeaturedOnCarousel, 100);
            return;
        }

        const $slider = jQuery('#mediaSlider');

        // Ensure the slider element exists
        if (!$slider.length) {
            return;
        }

        // Destroy existing slider if it exists
        if ($slider.hasClass('slick-initialized')) {
            $slider.slick('destroy');
        }

        // Count the number of slides
        const slideCount = $slider.find('.media-slide').length;

        // Only initialize if we have more than 1 slide
        if (slideCount > 1) {
            $slider.slick({
                dots: true,
                infinite: true,
                speed: 500,
                slidesToShow: 1,
                slidesToScroll: 1,
                autoplay: true,
                autoplaySpeed: 3000,
                pauseOnHover: true,
                adaptiveHeight: false,
                variableWidth: false,
                centerMode: false,
                focusOnSelect: true,
                swipeToSlide: true,
                arrows: true,
                lazyLoad: 'ondemand',
                responsive: [{
                    breakpoint: 768,
                    settings: {
                        arrows: false
                    }
                }]
            });
        } else if (slideCount === 1) {
            // For single slide, ensure proper display without carousel
            $slider.addClass('single-slide');
        }
    }

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', initializeFeaturedOnCarousel);

    // Also try to initialize after a delay to catch deferred scripts
    setTimeout(initializeFeaturedOnCarousel, 500);

    // Final fallback - if carousel still hasn't initialized after 2 seconds, show all slides
    setTimeout(function() {
        const $slider = jQuery('#mediaSlider');
        if ($slider.length && !$slider.hasClass('slick-initialized')) {
            $slider.addClass('fallback-display');
            $slider.find('.media-slide').show();
        }
    }, 2000);
</script>
