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
        <h2 class="section-title"><?php esc_html_e('Awards & Recognition', 'flexpress'); ?></h2>
           
            <div class="col-12">
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
            </div>
        </div>
    </div>
</section>
