<?php
/**
 * FlexPress Email Blacklist Page Template
 * 
 * Shows email blacklist information for users
 * 
 * @package FlexPress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<div class="flexpress-blacklist-page">
    <div class="container">
        <div class="page-header">
            <h1><?php echo get_the_title(); ?></h1>
            <p class="page-description">
                <?php esc_html_e('View information about our email blacklist policy and prevention measures.', 'flexpress'); ?>
            </p>
        </div>

        <div class="blacklist-content">
            <div class="blacklist-info">
                <h2><?php esc_html_e('Email Blacklist Policy', 'flexpress'); ?></h2>
                <p><?php esc_html_e('Our platform maintains an email blacklist to protect against fraudulent activities, including refund abuse and chargebacks. Email addressed that violate our terms of service may be added to this list.', 'flexpress'); ?></p>
                
                <h3><?php esc_html_e('Why Emails Get Blacklisted', 'flexpress'); ?></h3>
                <ul>
                    <li><?php esc_html_e('Chargeback abuse and fraudulent payment disputes', 'flexpress'); ?></li>
                    <li><?php esc_html_e('Refund abuse and policy violations', 'flexpress'); ?></li>
                    <li><?php esc_html_e('Terms of service violations', 'flexpress'); ?></li>
                    <li><?php esc_html_e('Security concerns or suspicious activity', 'flexpress'); ?></li>
                </ul>

                <h3><?php esc_html_e('Blacklist Effects', 'flexpress'); ?></h3>
                <p><?php esc_html_e('Blacklisted email addresses cannot register new accounts or access our services. This prevents fraud and protects our platform integrity.', 'flexpress'); ?></p>

                <h3><?php esc_html_e('Appeal Process', 'flexpress'); ?></h3>
                <p><?php esc_html_e('If you believe you have been incorrectly blacklisted, you may contact our support team for review. Appeals are reviewed on a case-by-case basis.', 'flexpress'); ?></p>
                
                <div class="support-contact">
                    <a href="/contact" class="button button-primary">
                        <?php esc_html_e('Contact Support', 'flexpress'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.flexpress-blacklist-page {
    padding: 40px 0;
    background: #1a1a1a;
    min-height: 80vh;
}

.flexpress-blacklist-page .container {
    max-width: 800px;
    margin: 0 auto;
    padding: 0 20px;
}

.page-header {
    text-align: center;
    margin-bottom: 40px;
}

.page-header h1 {
    color: #ffffff;
    font-size: 2.5rem;
    margin-bottom: 10px;
}

.page-description {
    color: #cccccc;
    font-size: 1.1rem;
    line-height: 1.6;
}

.blacklist-content {
    background: #2a2a2a;
    border-radius: 8px;
    padding: 30px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
}

.blacklist-info h2,
.blacklist-info h3 {
    color: #ffffff;
    margin-top: 30px;
    margin-bottom: 15px;
}

.blacklist-info h2 {
    font-size: 1.8rem;
    border-bottom: 2px solid #ff6b6b;
    padding-bottom: 10px;
    margin-top: 0;
}

.blacklist-info h3 {
    font-size: 1.3rem;
    color: #ff6b6b;
}

.blacklist-info p {
    color: #cccccc;
    line-height: 1.6;
    margin-bottom: 20px;
}

.blacklist-info ul {
    color: #cccccc;
    margin-left: 20px;
    margin-bottom: 20px;
}

.blacklist-info li {
    margin-bottom: 8px;
    line-height: 1.5;
}

.support-contact {
    margin-top: 30px;
    text-align: center;
}

.button-primary {
    background: linear-gradient(135deg, #ff6b6b, #ee5a52);
    color: #ffffff;
    border: none;
    padding: 15px 30px;
    border-radius: 5px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-block;
}

.button-primary:hover {
    background: linear-gradient(135deg, #ff5252, #e53935);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 107, 107, 0.3);
}

@media (max-width: 768px) {
    .flexpress-blacklist-page {
        padding: 20px 0;
    }
    
    .page-header h1 {
        font-size: 2rem;
    }
    
    .blacklist-content {
        padding: 20px;
    }
    
    .button-primary {
        padding: 12px 25px;
    }
}
</style>

<?php
get_footer();
?>
