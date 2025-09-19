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
?>

<section class="awards-nominations-section py-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-3">
                <h3 class="awards-subtitle mb-0"><?php echo esc_html($awards_data['title']); ?></h3>
            </div>
            <div class="col-md-9">
                <div class="awards-logos d-flex flex-wrap align-items-center justify-content-md-end">
                    <?php foreach ($awards_data['awards'] as $award): ?>
                        <?php if (!empty($award['logo_url'])): ?>
                            <?php if (!empty($award['link'])): ?>
                                <a href="<?php echo esc_url($award['link']); ?>" target="_blank" class="award-link-subtle me-3 mb-2">
                                    <img src="<?php echo esc_url($award['logo_url']); ?>" 
                                         class="award-image-subtle" 
                                         alt="<?php echo esc_attr($award['alt']); ?>">
                                </a>
                            <?php else: ?>
                                <div class="award-link-subtle me-3 mb-2">
                                    <img src="<?php echo esc_url($award['logo_url']); ?>" 
                                         class="award-image-subtle" 
                                         alt="<?php echo esc_attr($award['alt']); ?>">
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>
