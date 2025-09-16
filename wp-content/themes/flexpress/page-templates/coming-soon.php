<?php
/**
 * Template Name: Coming Soon
 */

get_header();

// Get the showreel video ID if available
$showreel_video_id = get_field('showreel_video');
?>

<div class="coming-soon-page">
    <div class="container">
        <div class="coming-soon-content text-center">
            <div class="site-branding mb-5">
                <h1 class="coming-soon-title display-2">
                    <?php echo esc_html(get_bloginfo('name')); ?>
                </h1>
                <p class="coming-soon-tagline lead">
                    <?php esc_html_e('Coming Soon', 'flexpress'); ?>
                </p>
            </div>

            <?php if (!empty($showreel_video_id)): ?>
                <div class="showreel-container mb-5">
                    <div class="embed-responsive embed-responsive-16by9">
                        <?php 
                        // Use the BunnyCDN integration if available
                        if (function_exists('flexpress_get_bunnycdn_video_url')):
                            $video_url = flexpress_get_bunnycdn_video_url($showreel_video_id);
                            $poster_url = flexpress_get_bunnycdn_poster_url($showreel_video_id);
                        ?>
                            <video class="embed-responsive-item" poster="<?php echo esc_url($poster_url); ?>" controls>
                                <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
                                <?php esc_html_e('Your browser does not support HTML5 video.', 'flexpress'); ?>
                            </video>
                        <?php else: ?>
                            <!-- Fallback to generic video player if BunnyCDN integration not available -->
                            <video class="embed-responsive-item" controls>
                                <source src="<?php echo esc_url($showreel_video_id); ?>" type="video/mp4">
                                <?php esc_html_e('Your browser does not support HTML5 video.', 'flexpress'); ?>
                            </video>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<footer class="coming-soon-footer py-3 mt-auto">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center">
                <p class="mb-0">
                    &copy; <?php echo date('Y'); ?> <?php echo esc_html(get_bloginfo('name')); ?>
                </p>
            </div>
        </div>
    </div>
</footer>

<?php
// We're not using the standard footer to keep it minimal
wp_footer();
?>
</body>
</html> 