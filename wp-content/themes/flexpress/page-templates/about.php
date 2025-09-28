<?php
/**
 * Template Name: About
 */

get_header();

// Get ACF fields
$hero_title = get_field('about_hero_title') ?: get_the_title();
$hero_subtitle = get_field('about_hero_subtitle');
$hero_image = get_field('about_hero_image');

$mission_title = get_field('about_mission_title');
$mission_content = get_field('about_mission_content');
$mission_icon = get_field('about_mission_icon') ?: 'bullseye';

$story_title = get_field('about_story_title');
$story_content = get_field('about_story_content');
$story_image = get_field('about_story_image');

$team_title = get_field('about_team_title');
$team_subtitle = get_field('about_team_subtitle');
$team_members = get_field('about_team_members');

$values_title = get_field('about_values_title');
$values_subtitle = get_field('about_values_subtitle');
$values = get_field('about_values');

$stats_title = get_field('about_stats_title');
$stats = get_field('about_stats');

$cta_title = get_field('about_cta_title');
$cta_content = get_field('about_cta_content');
$cta_button_text = get_field('about_cta_button_text');
$cta_button_url = get_field('about_cta_button_url');

// Set meta description for SEO
$meta_description = get_field('about_meta_description');
if ($meta_description) {
    echo '<meta name="description" content="' . esc_attr($meta_description) . '">';
}
?>

<main class="site-main">
    <!-- Hero Section -->
    <section class="about-hero-section" <?php if ($hero_image): ?>style="background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('<?php echo esc_url($hero_image); ?>');"<?php endif; ?>>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <h1 class="about-hero-title"><?php echo esc_html($hero_title); ?></h1>
                    <?php if ($hero_subtitle): ?>
                        <p class="about-hero-subtitle"><?php echo esc_html($hero_subtitle); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <div class="container py-5">
        <!-- Mission Statement Section -->
        <?php if ($mission_title && $mission_content): ?>
        <section class="about-mission-section mb-5">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="about-card">
                        <div class="about-card-body text-center">
                            <div class="about-icon-wrapper mb-4">
                                <i class="bi bi-<?php echo esc_attr($mission_icon); ?> about-section-icon"></i>
                            </div>
                            <h2 class="about-section-title"><?php echo esc_html($mission_title); ?></h2>
                            <p class="about-mission-text"><?php echo esc_html($mission_content); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Story Section -->
        <?php if ($story_title && $story_content): ?>
        <section class="about-story-section mb-5">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h2 class="about-section-title mb-4"><?php echo esc_html($story_title); ?></h2>
                    <div class="about-story-content">
                        <?php echo wp_kses_post($story_content); ?>
                    </div>
                </div>
                <?php if ($story_image): ?>
                <div class="col-lg-6">
                    <div class="about-story-image-wrapper">
                        <img src="<?php echo esc_url($story_image); ?>" alt="<?php echo esc_attr($story_title); ?>" class="about-story-image">
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Statistics Section -->
        <?php if ($stats_title && $stats): ?>
        <section class="about-stats-section mb-5">
            <div class="text-center mb-5">
                <h2 class="about-section-title"><?php echo esc_html($stats_title); ?></h2>
            </div>
            <div class="row">
                <?php foreach ($stats as $stat): ?>
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="about-stat-card text-center">
                        <div class="about-stat-number"><?php echo esc_html($stat['number']); ?></div>
                        <div class="about-stat-label"><?php echo esc_html($stat['label']); ?></div>
                        <?php if ($stat['description']): ?>
                            <div class="about-stat-description"><?php echo esc_html($stat['description']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Values Section -->
        <?php if ($values_title && $values): ?>
        <section class="about-values-section mb-5">
            <div class="text-center mb-5">
                <h2 class="about-section-title"><?php echo esc_html($values_title); ?></h2>
                <?php if ($values_subtitle): ?>
                    <p class="about-section-subtitle"><?php echo esc_html($values_subtitle); ?></p>
                <?php endif; ?>
            </div>
            <div class="row">
                <?php foreach ($values as $value): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="about-card h-100">
                        <div class="about-card-body text-center">
                            <div class="about-icon-wrapper mb-3">
                                <i class="bi bi-<?php echo esc_attr($value['icon']); ?> about-value-icon"></i>
                            </div>
                            <h3 class="about-value-title"><?php echo esc_html($value['title']); ?></h3>
                            <p class="about-value-description"><?php echo esc_html($value['description']); ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Team Section -->
        <?php if ($team_title && $team_members): ?>
        <section class="about-team-section mb-5">
            <div class="text-center mb-5">
                <h2 class="about-section-title"><?php echo esc_html($team_title); ?></h2>
                <?php if ($team_subtitle): ?>
                    <p class="about-section-subtitle"><?php echo esc_html($team_subtitle); ?></p>
                <?php endif; ?>
            </div>
            <div class="row">
                <?php foreach ($team_members as $member): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="about-card h-100">
                        <?php if ($member['image']): ?>
                            <div class="about-team-image-wrapper">
                                <img src="<?php echo esc_url($member['image']); ?>" alt="<?php echo esc_attr($member['name']); ?>" class="about-team-image">
                            </div>
                        <?php endif; ?>
                        <div class="about-card-body text-center">
                            <h3 class="about-team-name"><?php echo esc_html($member['name']); ?></h3>
                            <p class="about-team-position"><?php echo esc_html($member['position']); ?></p>
                            <?php if ($member['bio']): ?>
                                <p class="about-team-bio"><?php echo esc_html($member['bio']); ?></p>
                            <?php endif; ?>
                            
                            <!-- Social Links -->
                            <div class="about-team-social">
                                <?php if ($member['email']): ?>
                                    <a href="mailto:<?php echo esc_attr($member['email']); ?>" class="about-social-link" title="Email">
                                        <i class="bi bi-envelope"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if ($member['linkedin']): ?>
                                    <a href="<?php echo esc_url($member['linkedin']); ?>" class="about-social-link" title="LinkedIn" target="_blank" rel="noopener noreferrer">
                                        <i class="bi bi-linkedin"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if ($member['twitter']): ?>
                                    <a href="<?php echo esc_url($member['twitter']); ?>" class="about-social-link" title="Twitter" target="_blank" rel="noopener noreferrer">
                                        <i class="bi bi-twitter"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if ($member['instagram']): ?>
                                    <a href="<?php echo esc_url($member['instagram']); ?>" class="about-social-link" title="Instagram" target="_blank" rel="noopener noreferrer">
                                        <i class="bi bi-instagram"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Call to Action Section -->
        <?php if ($cta_title && $cta_content): ?>
        <section class="about-cta-section">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <div class="about-cta-card">
                        <div class="about-cta-body">
                            <h2 class="about-cta-title"><?php echo esc_html($cta_title); ?></h2>
                            <p class="about-cta-content"><?php echo esc_html($cta_content); ?></p>
                            <?php if ($cta_button_text && $cta_button_url): ?>
                                <a href="<?php echo esc_url($cta_button_url); ?>" class="btn btn-primary btn-lg about-cta-button">
                                    <?php echo esc_html($cta_button_text); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>
    </div>
</main>

<?php
get_footer();