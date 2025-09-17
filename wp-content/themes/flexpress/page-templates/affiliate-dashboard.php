<?php
/**
 * Template Name: Affiliate Dashboard
 * 
 * Page template for affiliate dashboard.
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
            <div class="affiliate-dashboard-page">
                <div class="page-header">
                    <h1 class="page-title"><?php the_title(); ?></h1>
                    <?php if (get_the_content()): ?>
                        <div class="page-content">
                            <?php the_content(); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="dashboard-content">
                    <?php echo do_shortcode('[affiliate_dashboard]'); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.affiliate-dashboard-page {
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

.dashboard-content {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

/* Affiliate Dashboard Styles */
.affiliate-dashboard {
    padding: 0;
}

.dashboard-header {
    background: linear-gradient(135deg, #007cba 0%, #005177 100%);
    color: white;
    padding: 2rem;
    text-align: center;
}

.dashboard-header h2 {
    margin: 0 0 0.5rem 0;
    font-size: 2rem;
}

.welcome-message {
    margin: 0;
    opacity: 0.9;
    font-size: 1.1rem;
}

.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    padding: 2rem;
    background: #f8f9fa;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.stat-icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.stat-card h3 {
    margin: 0 0 0.5rem 0;
    color: #666;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: #007cba;
}

.dashboard-sections {
    padding: 2rem;
}

.section {
    margin-bottom: 3rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid #eee;
}

.section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.section h3 {
    color: #333;
    margin-bottom: 1.5rem;
    font-size: 1.3rem;
    display: flex;
    align-items: center;
}

.section h3:before {
    content: "";
    width: 4px;
    height: 20px;
    background: #007cba;
    margin-right: 0.75rem;
    border-radius: 2px;
}

.referral-link-container {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.referral-link-container input {
    flex: 1;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-family: monospace;
    background: #f8f9fa;
}

.copy-link-button {
    padding: 0.75rem 1.5rem;
    background: #007cba;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background 0.2s ease;
}

.copy-link-button:hover {
    background: #005177;
}

.referral-description {
    color: #666;
    font-style: italic;
    margin: 0;
}

.promo-codes-list {
    display: grid;
    gap: 1rem;
}

.promo-code-item {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 6px;
    border-left: 4px solid #007cba;
}

.promo-code-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.promo-code-name {
    font-weight: bold;
    font-family: monospace;
    color: #333;
}

.promo-code-status {
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.8rem;
    text-transform: uppercase;
    font-weight: bold;
}

.status-active {
    background: #d4edda;
    color: #155724;
}

.status-inactive {
    background: #f8d7da;
    color: #721c24;
}

.promo-code-stats {
    display: flex;
    gap: 1rem;
    font-size: 0.9rem;
    color: #666;
}

.custom-pricing {
    margin-top: 0.5rem;
    font-size: 0.8rem;
    color: #007cba;
}

.commission-rates {
    display: grid;
    gap: 0.75rem;
}

.rate-item {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 4px;
}

.rate-label {
    color: #666;
}

.rate-value {
    font-weight: bold;
    color: #007cba;
}

.activity-list {
    display: grid;
    gap: 0.75rem;
}

.activity-item {
    display: flex;
    align-items: center;
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 4px;
}

.activity-type {
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.8rem;
    text-transform: uppercase;
    font-weight: bold;
    margin-right: 1rem;
    min-width: 60px;
    text-align: center;
}

.activity-click {
    background: #d1ecf1;
    color: #0c5460;
}

.activity-conversion {
    background: #d4edda;
    color: #155724;
}

.activity-description {
    flex: 1;
    color: #333;
}

.activity-date {
    color: #666;
    font-size: 0.9rem;
}

.payouts-table {
    overflow-x: auto;
}

.payouts-table table {
    width: 100%;
    border-collapse: collapse;
}

.payouts-table th,
.payouts-table td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.payouts-table th {
    background: #f8f9fa;
    font-weight: bold;
    color: #333;
}

.payout-status {
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.8rem;
    text-transform: uppercase;
    font-weight: bold;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-processing {
    background: #d1ecf1;
    color: #0c5460;
}

.status-completed {
    background: #d4edda;
    color: #155724;
}

.status-failed {
    background: #f8d7da;
    color: #721c24;
}

.dashboard-actions {
    padding: 2rem;
    background: #f8f9fa;
    text-align: center;
    border-top: 1px solid #eee;
}

.dashboard-actions .button {
    margin: 0 0.5rem;
    padding: 0.75rem 1.5rem;
    background: #007cba;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    transition: background 0.2s ease;
}

.dashboard-actions .button:hover {
    background: #005177;
    color: white;
}

.no-promo-codes,
.no-activity,
.no-payouts {
    text-align: center;
    color: #666;
    font-style: italic;
    padding: 2rem;
}

@media (max-width: 768px) {
    .dashboard-stats {
        grid-template-columns: repeat(2, 1fr);
        padding: 1rem;
    }
    
    .dashboard-sections {
        padding: 1rem;
    }
    
    .referral-link-container {
        flex-direction: column;
    }
    
    .promo-code-stats {
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .activity-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .activity-type {
        margin-right: 0;
        margin-bottom: 0.5rem;
    }
    
    .payouts-table {
        font-size: 0.9rem;
    }
    
    .dashboard-actions .button {
        display: block;
        margin: 0.5rem 0;
    }
}
</style>

<?php get_footer(); ?>