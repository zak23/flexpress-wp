<?php
/**
 * Template part for displaying Awards and Nominations section
 *
 * @package FlexPress
 */

// Check if awards section should be displayed
if (!flexpress_should_display_awards_section()) {
    return;
}

// Get awards data from settings
$awards_data = flexpress_get_awards_data();

// Count entries to determine layout
$awards_count = count($awards_data['awards']);
$use_carousel = $awards_count > 5;
?>

<section class="awards-nominations-section py-4">
    <div class="container">
        <div class="row align-items-center">
        <h2 class="section-title"><?php esc_html_e('Awards & Recognition', 'flexpress'); ?></h2>
           
            <div class="col-12">
                <?php if ($use_carousel): ?>
                    <!-- Carousel layout for more than 5 entries -->
                    <div class="awards-slider-wrapper">
                        <div class="awards-slider" id="awardsSlider">
                            <?php foreach ($awards_data['awards'] as $award): ?>
                                <?php if (!empty($award['logo_url'])): ?>
                                    <div class="award-slide">
                                        <div class="text-center d-flex flex-column align-items-center">
                                            <?php if (!empty($award['link'])): ?>
                                                <a href="<?php echo esc_url($award['link']); ?>" target="_blank" class="d-inline-block award-link-carousel" rel="noopener">
                                                    <img src="<?php echo esc_url($award['logo_url']); ?>" 
                                                         class="award-image-carousel" 
                                                         alt="<?php echo esc_attr($award['alt']); ?>"
                                                         loading="lazy">
                                                    <?php if (!empty($award['title'])): ?>
                                                        <p class="award-title-carousel"><?php echo esc_html($award['title']); ?></p>
                                                    <?php endif; ?>
                                                </a>
                                            <?php else: ?>
                                                <div class="award-link-carousel">
                                                    <img src="<?php echo esc_url($award['logo_url']); ?>" 
                                                         class="award-image-carousel" 
                                                         alt="<?php echo esc_attr($award['alt']); ?>"
                                                         loading="lazy">
                                                    <?php if (!empty($award['title'])): ?>
                                                        <p class="award-title-carousel"><?php echo esc_html($award['title']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Flex layout for 5 or fewer entries -->
                    <div class="awards-logos d-flex flex-wrap align-items-start justify-content-md-end">
                        <?php foreach ($awards_data['awards'] as $award): ?>
                            <?php if (!empty($award['logo_url'])): ?>
                                <div class="award-item me-4 mb-3">
                                    <?php if (!empty($award['link'])): ?>
                                        <a href="<?php echo esc_url($award['link']); ?>" target="_blank" class="award-link-subtle">
                                            <img src="<?php echo esc_url($award['logo_url']); ?>" 
                                                 class="award-image-subtle" 
                                                 alt="<?php echo esc_attr($award['alt']); ?>">
                                            <?php if (!empty($award['title'])): ?>
                                                <div class="award-title"><?php echo esc_html($award['title']); ?></div>
                                            <?php endif; ?>
                                        </a>
                                    <?php else: ?>
                                        <div class="award-link-subtle">
                                            <img src="<?php echo esc_url($award['logo_url']); ?>" 
                                                 class="award-image-subtle" 
                                                 alt="<?php echo esc_attr($award['alt']); ?>">
                                            <?php if (!empty($award['title'])): ?>
                                                <div class="award-title"><?php echo esc_html($award['title']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php if ($use_carousel): ?>
<script>
    function initializeAwardsCarousel() {
        // Check if jQuery and Slick are available
        if (typeof jQuery === 'undefined' || !jQuery.fn.slick) {
            // If not available, wait and try again
            setTimeout(initializeAwardsCarousel, 100);
            return;
        }

        const $slider = jQuery('#awardsSlider');

        // Ensure the slider element exists
        if (!$slider.length) {
            return;
        }

        // Destroy existing slider if it exists
        if ($slider.hasClass('slick-initialized')) {
            $slider.slick('destroy');
        }

        // Count the number of slides
        const slideCount = $slider.find('.award-slide').length;

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
    document.addEventListener('DOMContentLoaded', initializeAwardsCarousel);

    // Also try to initialize after a delay to catch deferred scripts
    setTimeout(initializeAwardsCarousel, 500);

    // Final fallback - if carousel still hasn't initialized after 2 seconds, show all slides
    setTimeout(function() {
        const $slider = jQuery('#awardsSlider');
        if ($slider.length && !$slider.hasClass('slick-initialized')) {
            $slider.addClass('fallback-display');
            $slider.find('.award-slide').show();
        }
    }, 2000);
</script>
<?php endif; ?>
