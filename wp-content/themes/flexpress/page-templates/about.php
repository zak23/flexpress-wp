<?php
/**
 * Template Name: About
 */

get_header();
?>

<div class="site-main">
    <div class="container py-5">
        <!-- Hero Section -->
        <div class="text-center mb-5">
            <h1 class="display-4 mb-4"><?php the_title(); ?></h1>
            <p class="lead text-muted mb-4"><?php the_content(); ?></p>
        </div>

        <!-- Mission Statement -->
        <div class="row justify-content-center mb-5">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <div class="text-center">
                            <i class="bi bi-bullseye display-4 text-primary mb-3"></i>
                            <h2 class="h3 mb-4"><?php esc_html_e('Our Mission', 'flexpress'); ?></h2>
                            <p class="lead mb-0">
                                <?php echo esc_html(get_theme_mod('about_mission', 'To provide high-quality, engaging content that inspires and educates our audience.')); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Team Section -->
        <div class="row mb-5">
            <div class="col-12 text-center mb-4">
                <h2 class="h3"><?php esc_html_e('Our Team', 'flexpress'); ?></h2>
                <p class="text-muted"><?php esc_html_e('Meet the people behind our success', 'flexpress'); ?></p>
            </div>

            <?php
            $team_members = get_theme_mod('about_team_members', array());
            if (!empty($team_members)):
                foreach ($team_members as $member):
            ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <?php if (!empty($member['image'])): ?>
                            <img src="<?php echo esc_url($member['image']); ?>" class="card-img-top" alt="<?php echo esc_attr($member['name']); ?>">
                        <?php endif; ?>
                        <div class="card-body text-center">
                            <h3 class="h5 mb-1"><?php echo esc_html($member['name']); ?></h3>
                            <p class="text-muted mb-3"><?php echo esc_html($member['position']); ?></p>
                            <p class="card-text"><?php echo esc_html($member['bio']); ?></p>
                            <?php if (!empty($member['social_links'])): ?>
                                <div class="social-links">
                                    <?php foreach ($member['social_links'] as $platform => $url): ?>
                                        <a href="<?php echo esc_url($url); ?>" class="btn btn-outline-primary btn-sm me-2" target="_blank" rel="noopener noreferrer">
                                            <i class="bi bi-<?php echo esc_attr($platform); ?>"></i>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php
                endforeach;
            endif;
            ?>
        </div>

        <!-- Values Section -->
        <div class="row mb-5">
            <div class="col-12 text-center mb-4">
                <h2 class="h3"><?php esc_html_e('Our Values', 'flexpress'); ?></h2>
                <p class="text-muted"><?php esc_html_e('What we stand for', 'flexpress'); ?></p>
            </div>

            <?php
            $values = get_theme_mod('about_values', array());
            if (!empty($values)):
                foreach ($values as $value):
            ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-<?php echo esc_attr($value['icon']); ?> display-4 text-primary mb-3"></i>
                            <h3 class="h5 mb-3"><?php echo esc_html($value['title']); ?></h3>
                            <p class="card-text"><?php echo esc_html($value['description']); ?></p>
                        </div>
                    </div>
                </div>
            <?php
                endforeach;
            endif;
            ?>
        </div>

        <!-- Call to Action -->
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <div class="card bg-primary text-white">
                    <div class="card-body p-5">
                        <h2 class="h3 mb-4"><?php esc_html_e('Join Our Community', 'flexpress'); ?></h2>
                        <p class="lead mb-4"><?php esc_html_e('Be part of our growing community and get access to exclusive content.', 'flexpress'); ?></p>
                        <a href="<?php echo esc_url(home_url('/join')); ?>" class="btn btn-light btn-lg">
                            <?php esc_html_e('Sign Up Now', 'flexpress'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
get_footer(); 