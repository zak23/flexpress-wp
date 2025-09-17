<?php
/**
 * Template Name: Affiliate Application
 * 
 * Page template for affiliate application form.
 * 
 * @package FlexPress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="affiliate-application-page">
                <div class="page-header">
                    <h1 class="page-title"><?php the_title(); ?></h1>
                    <?php if (get_the_content()): ?>
                        <div class="page-content">
                            <?php the_content(); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="application-content">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="application-form-section">
                                <h2><?php esc_html_e('Apply to Become an Affiliate', 'flexpress'); ?></h2>
                                <p class="form-description">
                                    <?php esc_html_e('Join our affiliate program and start earning commissions by promoting our content. Fill out the form below to apply.', 'flexpress'); ?>
                                </p>
                                
                                <?php echo do_shortcode('[affiliate_application_form]'); ?>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <div class="application-info-section">
                                <div class="info-card">
                                    <h3><?php esc_html_e('Why Join Our Affiliate Program?', 'flexpress'); ?></h3>
                                    <ul class="benefits-list">
                                        <li><?php esc_html_e('Competitive commission rates', 'flexpress'); ?></li>
                                        <li><?php esc_html_e('Real-time tracking and reporting', 'flexpress'); ?></li>
                                        <li><?php esc_html_e('Marketing materials provided', 'flexpress'); ?></li>
                                        <li><?php esc_html_e('Regular payouts', 'flexpress'); ?></li>
                                        <li><?php esc_html_e('Dedicated support team', 'flexpress'); ?></li>
                                    </ul>
                                </div>
                                
                                <div class="info-card">
                                    <h3><?php esc_html_e('Commission Structure', 'flexpress'); ?></h3>
                                    <?php 
                                    $settings = get_option('flexpress_affiliate_settings', array());
                                    $initial_rate = $settings['commission_rate'] ?? 25.00;
                                    $rebill_rate = $settings['rebill_commission_rate'] ?? 10.00;
                                    $unlock_rate = $settings['unlock_commission_rate'] ?? 15.00;
                                    ?>
                                    <div class="commission-rates">
                                        <div class="rate-item">
                                            <span class="rate-label"><?php esc_html_e('Initial Sales:', 'flexpress'); ?></span>
                                            <span class="rate-value"><?php echo esc_html($initial_rate); ?>%</span>
                                        </div>
                                        <div class="rate-item">
                                            <span class="rate-label"><?php esc_html_e('Recurring Payments:', 'flexpress'); ?></span>
                                            <span class="rate-value"><?php echo esc_html($rebill_rate); ?>%</span>
                                        </div>
                                        <div class="rate-item">
                                            <span class="rate-label"><?php esc_html_e('Unlock Purchases:', 'flexpress'); ?></span>
                                            <span class="rate-value"><?php echo esc_html($unlock_rate); ?>%</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="info-card">
                                    <h3><?php esc_html_e('Requirements', 'flexpress'); ?></h3>
                                    <ul class="requirements-list">
                                        <li><?php esc_html_e('Valid website or social media presence', 'flexpress'); ?></li>
                                        <li><?php esc_html_e('Compliance with our terms and conditions', 'flexpress'); ?></li>
                                        <li><?php esc_html_e('Professional marketing practices', 'flexpress'); ?></li>
                                        <li><?php esc_html_e('Minimum payout threshold: $100', 'flexpress'); ?></li>
                                    </ul>
                                </div>
                                
                                <div class="info-card">
                                    <h3><?php esc_html_e('Need Help?', 'flexpress'); ?></h3>
                                    <p><?php esc_html_e('Have questions about our affiliate program?', 'flexpress'); ?></p>
                                    <a href="<?php echo esc_url(home_url('/contact')); ?>" class="button">
                                        <?php esc_html_e('Contact Support', 'flexpress'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.affiliate-application-page {
    padding: 2rem 0;
}

.page-header {
    text-align: center;
    margin-bottom: 3rem;
}

.page-title {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    color: #333;
}

.page-content {
    font-size: 1.1rem;
    color: #666;
    max-width: 800px;
    margin: 0 auto;
}

.application-form-section {
    background: #fff;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.form-description {
    color: #666;
    margin-bottom: 2rem;
    font-size: 1.1rem;
}

.application-info-section {
    position: sticky;
    top: 2rem;
}

.info-card {
    background: #fff;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 1.5rem;
}

.info-card h3 {
    color: #333;
    margin-bottom: 1rem;
    font-size: 1.2rem;
}

.benefits-list,
.requirements-list {
    list-style: none;
    padding: 0;
}

.benefits-list li,
.requirements-list li {
    padding: 0.5rem 0;
    border-bottom: 1px solid #eee;
    position: relative;
    padding-left: 1.5rem;
}

.benefits-list li:before {
    content: "✓";
    position: absolute;
    left: 0;
    color: #00a32a;
    font-weight: bold;
}

.requirements-list li:before {
    content: "•";
    position: absolute;
    left: 0;
    color: #007cba;
    font-weight: bold;
}

.commission-rates {
    margin-top: 1rem;
}

.rate-item {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid #eee;
}

.rate-label {
    color: #666;
}

.rate-value {
    font-weight: bold;
    color: #007cba;
}

@media (max-width: 768px) {
    .application-info-section {
        position: static;
        margin-top: 2rem;
    }
    
    .page-title {
        font-size: 2rem;
    }
    
    .application-form-section {
        padding: 1.5rem;
    }
}
</style>

<?php get_footer(); ?>
