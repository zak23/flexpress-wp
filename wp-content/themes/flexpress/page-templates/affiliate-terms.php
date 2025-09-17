<?php
/**
 * Template Name: Affiliate Terms
 * 
 * Page template for affiliate terms and conditions.
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
            <div class="affiliate-terms-page">
                <div class="page-header">
                    <h1 class="page-title"><?php the_title(); ?></h1>
                    <?php if (get_the_content()): ?>
                        <div class="page-content">
                            <?php the_content(); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="terms-content">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="terms-section">
                                <?php 
                                $settings = get_option('flexpress_affiliate_settings', array());
                                $terms = $settings['affiliate_terms'] ?? '';
                                
                                if (!empty($terms)) {
                                    echo wp_kses_post($terms);
                                } else {
                                    // Default terms if none are set
                                    ?>
                                    <h2><?php esc_html_e('Affiliate Program Terms and Conditions', 'flexpress'); ?></h2>
                                    
                                    <p><strong><?php esc_html_e('Last Updated:', 'flexpress'); ?></strong> <?php echo date_i18n(get_option('date_format')); ?></p>
                                    
                                    <h3><?php esc_html_e('1. Program Overview', 'flexpress'); ?></h3>
                                    <p><?php esc_html_e('Our affiliate program allows approved partners to earn commissions by promoting our content and services. By participating in this program, you agree to be bound by these terms and conditions.', 'flexpress'); ?></p>
                                    
                                    <h3><?php esc_html_e('2. Eligibility Requirements', 'flexpress'); ?></h3>
                                    <ul>
                                        <li><?php esc_html_e('Must be at least 18 years of age', 'flexpress'); ?></li>
                                        <li><?php esc_html_e('Have a valid website, blog, or social media presence', 'flexpress'); ?></li>
                                        <li><?php esc_html_e('Comply with all applicable laws and regulations', 'flexpress'); ?></li>
                                        <li><?php esc_html_e('Provide accurate and complete application information', 'flexpress'); ?></li>
                                        <li><?php esc_html_e('Maintain professional standards in all promotional activities', 'flexpress'); ?></li>
                                    </ul>
                                    
                                    <h3><?php esc_html_e('3. Commission Structure', 'flexpress'); ?></h3>
                                    <p><?php esc_html_e('Commissions are paid based on the following structure:', 'flexpress'); ?></p>
                                    <ul>
                                        <li><?php printf(esc_html__('Initial Sales: %s%%', 'flexpress'), $settings['commission_rate'] ?? 25.00); ?></li>
                                        <li><?php printf(esc_html__('Recurring Payments: %s%%', 'flexpress'), $settings['rebill_commission_rate'] ?? 10.00); ?></li>
                                        <li><?php printf(esc_html__('Unlock Purchases: %s%%', 'flexpress'), $settings['unlock_commission_rate'] ?? 15.00); ?></li>
                                    </ul>
                                    
                                    <h3><?php esc_html_e('4. Payment Terms', 'flexpress'); ?></h3>
                                    <ul>
                                        <li><?php printf(esc_html__('Minimum payout threshold: $%s', 'flexpress'), $settings['minimum_payout'] ?? 100.00); ?></li>
                                        <li><?php esc_html_e('Payouts are processed monthly', 'flexpress'); ?></li>
                                        <li><?php esc_html_e('Commissions are subject to a 30-day hold period', 'flexpress'); ?></li>
                                        <li><?php esc_html_e('Payment methods include PayPal (Free), Cryptocurrency (Free), Australian Bank Transfer (Free), Yoursafe (Free), ACH - US Only ($10 USD Fee), and Swift International ($30 USD Fee)', 'flexpress'); ?></li>
                                    </ul>
                                    
                                    <h3><?php esc_html_e('5. Prohibited Activities', 'flexpress'); ?></h3>
                                    <p><?php esc_html_e('The following activities are strictly prohibited:', 'flexpress'); ?></p>
                                    <ul>
                                        <li><?php esc_html_e('Spam or unsolicited email marketing', 'flexpress'); ?></li>
                                        <li><?php esc_html_e('False or misleading advertising', 'flexpress'); ?></li>
                                        <li><?php esc_html_e('Self-referrals or fraudulent transactions', 'flexpress'); ?></li>
                                        <li><?php esc_html_e('Violation of intellectual property rights', 'flexpress'); ?></li>
                                        <li><?php esc_html_e('Use of automated tools to generate clicks or conversions', 'flexpress'); ?></li>
                                        <li><?php esc_html_e('Promotion on adult content sites without prior approval', 'flexpress'); ?></li>
                                    </ul>
                                    
                                    <h3><?php esc_html_e('6. Marketing Guidelines', 'flexpress'); ?></h3>
                                    <ul>
                                        <li><?php esc_html_e('Use only approved marketing materials', 'flexpress'); ?></li>
                                        <li><?php esc_html_e('Clearly disclose your affiliate relationship', 'flexpress'); ?></li>
                                        <li><?php esc_html_e('Comply with FTC guidelines and local advertising laws', 'flexpress'); ?></li>
                                        <li><?php esc_html_e('Maintain professional standards in all communications', 'flexpress'); ?></li>
                                        <li><?php esc_html_e('Do not make false claims about our products or services', 'flexpress'); ?></li>
                                    </ul>
                                    
                                    <h3><?php esc_html_e('7. Termination', 'flexpress'); ?></h3>
                                    <p><?php esc_html_e('We reserve the right to terminate your affiliate account at any time for violation of these terms. Upon termination:', 'flexpress'); ?></p>
                                    <ul>
                                        <li><?php esc_html_e('All pending commissions will be forfeited', 'flexpress'); ?></li>
                                        <li><?php esc_html_e('You must cease all promotional activities immediately', 'flexpress'); ?></li>
                                        <li><?php esc_html_e('You must remove all affiliate links and materials', 'flexpress'); ?></li>
                                    </ul>
                                    
                                    <h3><?php esc_html_e('8. Privacy and Data Protection', 'flexpress'); ?></h3>
                                    <p><?php esc_html_e('We are committed to protecting your privacy and personal information. Please review our Privacy Policy for details on how we collect, use, and protect your data.', 'flexpress'); ?></p>
                                    
                                    <h3><?php esc_html_e('9. Limitation of Liability', 'flexpress'); ?></h3>
                                    <p><?php esc_html_e('We shall not be liable for any indirect, incidental, special, or consequential damages arising from your participation in the affiliate program.', 'flexpress'); ?></p>
                                    
                                    <h3><?php esc_html_e('10. Changes to Terms', 'flexpress'); ?></h3>
                                    <p><?php esc_html_e('We reserve the right to modify these terms at any time. Changes will be effective immediately upon posting. Continued participation in the program constitutes acceptance of the modified terms.', 'flexpress'); ?></p>
                                    
                                    <h3><?php esc_html_e('11. Contact Information', 'flexpress'); ?></h3>
                                    <p><?php esc_html_e('If you have any questions about these terms, please contact us:', 'flexpress'); ?></p>
                                    <p>
                                        <?php 
                                        $contact_email = get_option('admin_email');
                                        printf(
                                            esc_html__('Email: %s', 'flexpress'),
                                            '<a href="mailto:' . esc_attr($contact_email) . '">' . esc_html($contact_email) . '</a>'
                                        );
                                        ?>
                                    </p>
                                    
                                    <div class="terms-footer">
                                        <p><strong><?php esc_html_e('By applying to our affiliate program, you acknowledge that you have read, understood, and agree to be bound by these terms and conditions.', 'flexpress'); ?></strong></p>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <div class="terms-sidebar">
                                <div class="sidebar-card">
                                    <h3><?php esc_html_e('Quick Links', 'flexpress'); ?></h3>
                                    <ul class="quick-links">
                                        <li><a href="<?php echo esc_url(home_url('/affiliate-application')); ?>"><?php esc_html_e('Apply Now', 'flexpress'); ?></a></li>
                                        <li><a href="<?php echo esc_url(home_url('/affiliate-dashboard')); ?>"><?php esc_html_e('Dashboard', 'flexpress'); ?></a></li>
                                        <li><a href="<?php echo esc_url(home_url('/contact')); ?>"><?php esc_html_e('Contact Support', 'flexpress'); ?></a></li>
                                        <li><a href="<?php echo esc_url(home_url('/privacy')); ?>"><?php esc_html_e('Privacy Policy', 'flexpress'); ?></a></li>
                                    </ul>
                                </div>
                                
                                <div class="sidebar-card">
                                    <h3><?php esc_html_e('Important Notes', 'flexpress'); ?></h3>
                                    <div class="important-notes">
                                        <div class="note-item">
                                            <strong><?php esc_html_e('Tax Responsibility:', 'flexpress'); ?></strong>
                                            <p><?php esc_html_e('You are responsible for reporting and paying taxes on all commissions earned.', 'flexpress'); ?></p>
                                        </div>
                                        
                                        <div class="note-item">
                                            <strong><?php esc_html_e('Compliance:', 'flexpress'); ?></strong>
                                            <p><?php esc_html_e('All promotional activities must comply with applicable laws and regulations.', 'flexpress'); ?></p>
                                        </div>
                                        
                                        <div class="note-item">
                                            <strong><?php esc_html_e('Updates:', 'flexpress'); ?></strong>
                                            <p><?php esc_html_e('These terms may be updated periodically. Please check back regularly.', 'flexpress'); ?></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="sidebar-card">
                                    <h3><?php esc_html_e('Need Help?', 'flexpress'); ?></h3>
                                    <p><?php esc_html_e('Have questions about our affiliate program or these terms?', 'flexpress'); ?></p>
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
.affiliate-terms-page {
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

.terms-section {
    background: #fff;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.terms-section h2 {
    color: #333;
    margin-bottom: 1.5rem;
    font-size: 1.8rem;
    border-bottom: 2px solid #007cba;
    padding-bottom: 0.5rem;
}

.terms-section h3 {
    color: #333;
    margin: 2rem 0 1rem 0;
    font-size: 1.3rem;
}

.terms-section p {
    line-height: 1.6;
    margin-bottom: 1rem;
    color: #555;
}

.terms-section ul {
    margin: 1rem 0;
    padding-left: 1.5rem;
}

.terms-section li {
    margin-bottom: 0.5rem;
    line-height: 1.6;
    color: #555;
}

.terms-footer {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 6px;
    margin-top: 2rem;
    border-left: 4px solid #007cba;
}

.terms-footer p {
    margin: 0;
    color: #333;
}

.terms-sidebar {
    position: sticky;
    top: 2rem;
}

.sidebar-card {
    background: #fff;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 1.5rem;
}

.sidebar-card h3 {
    color: #333;
    margin-bottom: 1rem;
    font-size: 1.2rem;
    border-bottom: 1px solid #eee;
    padding-bottom: 0.5rem;
}

.quick-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.quick-links li {
    margin-bottom: 0.5rem;
}

.quick-links a {
    color: #007cba;
    text-decoration: none;
    padding: 0.5rem 0;
    display: block;
    border-bottom: 1px solid #f0f0f0;
    transition: color 0.2s ease;
}

.quick-links a:hover {
    color: #005177;
}

.important-notes {
    margin-top: 1rem;
}

.note-item {
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #f0f0f0;
}

.note-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.note-item strong {
    color: #333;
    display: block;
    margin-bottom: 0.5rem;
}

.note-item p {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
    line-height: 1.5;
}

.sidebar-card .button {
    display: block;
    text-align: center;
    padding: 0.75rem 1.5rem;
    background: #007cba;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    transition: background 0.2s ease;
    margin-top: 1rem;
}

.sidebar-card .button:hover {
    background: #005177;
    color: white;
}

@media (max-width: 768px) {
    .terms-sidebar {
        position: static;
        margin-top: 2rem;
    }
    
    .page-title {
        font-size: 2rem;
    }
    
    .terms-section {
        padding: 1.5rem;
    }
    
    .terms-section h2 {
        font-size: 1.5rem;
    }
    
    .terms-section h3 {
        font-size: 1.2rem;
    }
}
</style>

<?php get_footer(); ?>
