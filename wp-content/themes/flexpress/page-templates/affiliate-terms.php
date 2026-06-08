<?php
/**
 * Template Name: Affiliate Terms
 *
 * Page template for affiliate terms and conditions.
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$signup_url = esc_url(home_url('/affiliate-signup/'));
$dashboard_url = esc_url(home_url('/affiliate-dashboard/'));
$contact_url = esc_url(home_url('/contact/'));
$privacy_url = esc_url(home_url('/privacy/'));
?>

<div class="site-main legal-page affiliate-terms-page">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h1 class="h2 mb-4"><?php the_title(); ?></h1>

                        <div class="content affiliate-terms-content">
                            <?php echo flexpress_get_affiliate_terms_html(); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mt-4 mt-lg-0">
                <div class="affiliate-terms-sidebar">
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <h2 class="h5 mb-3"><?php esc_html_e('Quick Links', 'flexpress'); ?></h2>
                            <ul class="affiliate-terms-links list-unstyled mb-0">
                                <li><a href="<?php echo $signup_url; ?>"><?php esc_html_e('Apply Now', 'flexpress'); ?></a></li>
                                <li><a href="<?php echo $dashboard_url; ?>"><?php esc_html_e('Affiliate Dashboard', 'flexpress'); ?></a></li>
                                <li><a href="<?php echo $contact_url; ?>"><?php esc_html_e('Contact Support', 'flexpress'); ?></a></li>
                                <li><a href="<?php echo $privacy_url; ?>"><?php esc_html_e('Privacy Policy', 'flexpress'); ?></a></li>
                            </ul>
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <h2 class="h5 mb-3"><?php esc_html_e('Important Notes', 'flexpress'); ?></h2>
                            <div class="affiliate-terms-note">
                                <strong><?php esc_html_e('Tax Responsibility', 'flexpress'); ?></strong>
                                <p class="text-muted mb-3"><?php esc_html_e('You are responsible for reporting and paying taxes on all commissions earned.', 'flexpress'); ?></p>
                            </div>
                            <div class="affiliate-terms-note">
                                <strong><?php esc_html_e('Compliance', 'flexpress'); ?></strong>
                                <p class="text-muted mb-3"><?php esc_html_e('All promotional activities must comply with applicable laws and FTC disclosure requirements.', 'flexpress'); ?></p>
                            </div>
                            <div class="affiliate-terms-note">
                                <strong><?php esc_html_e('Updates', 'flexpress'); ?></strong>
                                <p class="text-muted mb-0"><?php esc_html_e('These terms may be updated periodically. Please check back regularly.', 'flexpress'); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm">
                        <div class="card-body text-center">
                            <h2 class="h5 mb-3"><?php esc_html_e('Need Help?', 'flexpress'); ?></h2>
                            <p class="text-muted"><?php esc_html_e('Have questions about our affiliate program or these terms?', 'flexpress'); ?></p>
                            <a href="<?php echo $contact_url; ?>" class="btn btn-primary">
                                <?php esc_html_e('Contact Support', 'flexpress'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>
