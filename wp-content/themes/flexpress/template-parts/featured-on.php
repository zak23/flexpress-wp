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

// Count entries to determine layout
$media_count = count($featured_media);
$use_carousel = $media_count > 5;
?>

<section class="featured-on-section py-5">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h2 class="section-title"><?php esc_html_e('Featured On', 'flexpress'); ?></h2>

                <?php if ($use_carousel): ?>
                    <!-- Carousel layout for more than 5 entries -->
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
                <?php else: ?>
                    <!-- Flex layout for 5 or fewer entries (matching Awards style) -->
                    <div class="featured-on-logos d-flex flex-wrap align-items-start justify-content-md-end">
                        <?php foreach ($featured_media as $media): ?>
                            <div class="featured-item me-4 mb-3">
                                <a href="<?php echo esc_url($media['url']); ?>" target="_blank" class="featured-link-subtle" rel="noopener">
                                    <?php if (!empty($media['logo_id'])):
                                        $logo_image = wp_get_attachment_image($media['logo_id'], 'medium', false, array(
                                            'class' => 'featured-logo',
                                            'alt' => esc_attr($media['alt']),
                                            'loading' => 'lazy'
                                        ));
                                        echo $logo_image;
                                    else: ?>
                                        <img src="<?php echo esc_url($media['logo']); ?>"
                                            class="featured-logo"
                                            alt="<?php echo esc_attr($media['alt']); ?>"
                                            loading="lazy">
                                    <?php endif; ?>
                                    <?php if (!empty($media['name'])): ?>
                                        <div class="featured-name"><?php echo esc_html($media['name']); ?></div>
                                    <?php endif; ?>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php if ($use_carousel): ?>
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

        // Only initialize if we have more than 5 slides
        if (slideCount > 5) {
            $slider.slick({
                dots: true,
                infinite: true,
                speed: 500,
                slidesToShow: 5,
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
                    breakpoint: 1200,
                    settings: {
                        slidesToShow: 4,
                        slidesToScroll: 1
                    }
                }, {
                    breakpoint: 992,
                    settings: {
                        slidesToShow: 3,
                        slidesToScroll: 1
                    }
                }, {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: 2,
                        slidesToScroll: 1,
                        arrows: false
                    }
                }, {
                    breakpoint: 576,
                    settings: {
                        slidesToShow: 1,
                        slidesToScroll: 1,
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
<?php endif; ?>
