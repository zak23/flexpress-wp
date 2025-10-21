<?php

/**
 * FlexPress Earnings Settings
 * 
 * Admin settings page for earnings and revenue tracking
 * 
 * @package FlexPress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * FlexPress Earnings Settings Class
 */
class FlexPress_Earnings_Settings
{

    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_flexpress_get_earnings_data', array($this, 'ajax_get_earnings_data'));
        add_action('wp_ajax_flexpress_export_earnings_csv', array($this, 'ajax_export_earnings_csv'));
    }

    /**
     * Register settings
     */
    public function register_settings()
    {
        // No settings to register - this is a display-only page
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook)
    {
        if ($hook !== 'flexpress_page_flexpress-earnings') {
            return;
        }

        // Enqueue Chart.js
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
            array(),
            '4.4.0',
            true
        );

        // Enqueue custom earnings JS
        wp_enqueue_script(
            'flexpress-earnings-admin',
            get_template_directory_uri() . '/assets/js/admin-earnings.js',
            array('jquery', 'chartjs'),
            '1.0.0',
            true
        );

        // Enqueue custom earnings CSS
        wp_enqueue_style(
            'flexpress-earnings-admin',
            get_template_directory_uri() . '/assets/css/admin-earnings.css',
            array(),
            '1.0.0'
        );

        // Localize script with AJAX URL and nonce
        wp_localize_script('flexpress-earnings-admin', 'flexpressEarnings', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flexpress_earnings_nonce'),
            'currency' => 'USD' // TODO: Make this configurable
        ));
    }

    /**
     * Get earnings data for a specific period
     * 
     * @param string $period Period type (today, week, month, year, custom)
     * @param string $start_date Start date for custom period
     * @param string $end_date End date for custom period
     * @return array Earnings data
     */
    public function get_earnings_data($period = 'month', $start_date = null, $end_date = null)
    {
        global $wpdb;

        $transactions_table = $wpdb->prefix . 'flexpress_flowguard_transactions';
        $webhooks_table = $wpdb->prefix . 'flexpress_flowguard_webhooks';
        $affiliate_transactions_table = $wpdb->prefix . 'flexpress_affiliate_transactions';

        // Calculate date range
        $date_condition = $this->get_date_condition($period, $start_date, $end_date, 't');

        // Get all transactions with webhook event types
        $transactions = $wpdb->get_results("
            SELECT 
                t.id,
                t.user_id,
                t.transaction_id,
                t.amount,
                t.currency,
                t.status,
                t.order_type,
                t.plan_id,
                t.created_at,
                w.event_type,
                u.user_email,
                u.display_name
            FROM {$transactions_table} t
            LEFT JOIN {$webhooks_table} w ON t.transaction_id = w.transaction_id
            LEFT JOIN {$wpdb->users} u ON t.user_id = u.ID
            WHERE {$date_condition}
            ORDER BY t.created_at DESC
        ", ARRAY_A);

        // Get affiliate commissions for the period
        $date_condition_affiliate = $this->get_date_condition($period, $start_date, $end_date, '');
        $commissions = $wpdb->get_var("
            SELECT COALESCE(SUM(commission_amount), 0)
            FROM {$affiliate_transactions_table}
            WHERE {$date_condition_affiliate}
            AND status IN ('approved', 'paid')
        ");

        // Calculate metrics
        $gross_revenue = 0;
        $total_transactions = 0;
        $refunds_total = 0;
        $chargebacks_total = 0;

        $breakdown = array(
            'subscriptions' => array('count' => 0, 'amount' => 0),
            'rebills' => array('count' => 0, 'amount' => 0),
            'unlocks' => array('count' => 0, 'amount' => 0),
            'refunds' => array('count' => 0, 'amount' => 0),
            'chargebacks' => array('count' => 0, 'amount' => 0)
        );

        $daily_revenue = array();

        foreach ($transactions as $transaction) {
            $amount = floatval($transaction['amount']);
            $event_type = $transaction['event_type'];
            $order_type = $transaction['order_type'];
            $date = date('Y-m-d', strtotime($transaction['created_at']));

            // Categorize transaction
            if ($event_type === 'chargeback') {
                $breakdown['chargebacks']['count']++;
                $breakdown['chargebacks']['amount'] += abs($amount);
                $chargebacks_total += abs($amount);
                $gross_revenue -= abs($amount);
            } elseif ($event_type === 'credit') {
                $breakdown['refunds']['count']++;
                $breakdown['refunds']['amount'] += abs($amount);
                $refunds_total += abs($amount);
                $gross_revenue -= abs($amount);
            } elseif ($event_type === 'rebill') {
                $breakdown['rebills']['count']++;
                $breakdown['rebills']['amount'] += $amount;
                $gross_revenue += $amount;
                $total_transactions++;
            } elseif ($order_type === 'subscription' && $event_type === 'approved') {
                $breakdown['subscriptions']['count']++;
                $breakdown['subscriptions']['amount'] += $amount;
                $gross_revenue += $amount;
                $total_transactions++;
            } elseif ($order_type === 'purchase' && $event_type === 'approved') {
                $breakdown['unlocks']['count']++;
                $breakdown['unlocks']['amount'] += $amount;
                $gross_revenue += $amount;
                $total_transactions++;
            } else {
                // Default to positive transaction
                if ($transaction['status'] === 'approved') {
                    $gross_revenue += $amount;
                    $total_transactions++;
                }
            }

            // Track daily revenue for charts
            if (!isset($daily_revenue[$date])) {
                $daily_revenue[$date] = 0;
            }

            // Add to daily revenue (subtract refunds/chargebacks)
            if ($event_type === 'chargeback' || $event_type === 'credit') {
                $daily_revenue[$date] -= abs($amount);
            } else {
                $daily_revenue[$date] += $amount;
            }
        }

        // Calculate net revenue
        $net_revenue = $gross_revenue - floatval($commissions);

        // Calculate average transaction value
        $avg_transaction = $total_transactions > 0 ? $gross_revenue / $total_transactions : 0;

        // Sort daily revenue by date
        ksort($daily_revenue);

        return array(
            'gross_revenue' => $gross_revenue,
            'net_revenue' => $net_revenue,
            'total_transactions' => $total_transactions,
            'affiliate_commissions' => floatval($commissions),
            'average_transaction' => $avg_transaction,
            'refunds_total' => $refunds_total,
            'chargebacks_total' => $chargebacks_total,
            'breakdown' => $breakdown,
            'daily_revenue' => $daily_revenue,
            'transactions' => $transactions,
            'period' => $period,
            'start_date' => $start_date,
            'end_date' => $end_date
        );
    }

    /**
     * Get date condition SQL for period
     * 
     * @param string $period Period type
     * @param string $start_date Start date for custom
     * @param string $end_date End date for custom
     * @param string $table_alias Table alias (e.g., 't.' or empty string)
     * @return string SQL WHERE condition
     */
    private function get_date_condition($period, $start_date = null, $end_date = null, $table_alias = 't')
    {
        global $wpdb;

        // Add dot if alias is provided
        $prefix = $table_alias ? $table_alias . '.' : '';

        switch ($period) {
            case 'today':
                return "DATE({$prefix}created_at) = CURDATE()";

            case 'week':
                return "{$prefix}created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";

            case 'month':
                return "{$prefix}created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";

            case 'year':
                return "{$prefix}created_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)";

            case 'custom':
                if ($start_date && $end_date) {
                    return $wpdb->prepare(
                        "DATE({$prefix}created_at) BETWEEN %s AND %s",
                        $start_date,
                        $end_date
                    );
                }
                return "{$prefix}created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";

            default:
                return "{$prefix}created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        }
    }

    /**
     * AJAX handler for getting earnings data
     */
    public function ajax_get_earnings_data()
    {
        check_ajax_referer('flexpress_earnings_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $period = sanitize_text_field($_POST['period'] ?? 'month');
        $start_date = sanitize_text_field($_POST['start_date'] ?? null);
        $end_date = sanitize_text_field($_POST['end_date'] ?? null);

        $data = $this->get_earnings_data($period, $start_date, $end_date);

        wp_send_json_success($data);
    }

    /**
     * AJAX handler for CSV export
     */
    public function ajax_export_earnings_csv()
    {
        check_ajax_referer('flexpress_earnings_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $period = sanitize_text_field($_GET['period'] ?? 'month');
        $start_date = sanitize_text_field($_GET['start_date'] ?? null);
        $end_date = sanitize_text_field($_GET['end_date'] ?? null);

        $data = $this->get_earnings_data($period, $start_date, $end_date);

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=earnings-' . $period . '-' . date('Y-m-d') . '.csv');

        // Create output stream
        $output = fopen('php://output', 'w');

        // Add BOM for UTF-8
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Write summary section
        fputcsv($output, array('FlexPress Earnings Report'));
        fputcsv($output, array('Period', ucfirst($period)));
        fputcsv($output, array('Generated', date('Y-m-d H:i:s')));
        fputcsv($output, array(''));

        fputcsv($output, array('Summary'));
        fputcsv($output, array('Gross Revenue', '$' . number_format($data['gross_revenue'], 2)));
        fputcsv($output, array('Affiliate Commissions', '$' . number_format($data['affiliate_commissions'], 2)));
        fputcsv($output, array('Net Revenue', '$' . number_format($data['net_revenue'], 2)));
        fputcsv($output, array('Total Transactions', $data['total_transactions']));
        fputcsv($output, array('Average Transaction', '$' . number_format($data['average_transaction'], 2)));
        fputcsv($output, array('Refunds Total', '$' . number_format($data['refunds_total'], 2)));
        fputcsv($output, array('Chargebacks Total', '$' . number_format($data['chargebacks_total'], 2)));
        fputcsv($output, array(''));

        // Write breakdown section
        fputcsv($output, array('Transaction Breakdown'));
        fputcsv($output, array('Type', 'Count', 'Amount'));
        fputcsv($output, array('New Subscriptions', $data['breakdown']['subscriptions']['count'], '$' . number_format($data['breakdown']['subscriptions']['amount'], 2)));
        fputcsv($output, array('Rebills', $data['breakdown']['rebills']['count'], '$' . number_format($data['breakdown']['rebills']['amount'], 2)));
        fputcsv($output, array('PPV Unlocks', $data['breakdown']['unlocks']['count'], '$' . number_format($data['breakdown']['unlocks']['amount'], 2)));
        fputcsv($output, array('Refunds', $data['breakdown']['refunds']['count'], '-$' . number_format($data['breakdown']['refunds']['amount'], 2)));
        fputcsv($output, array('Chargebacks', $data['breakdown']['chargebacks']['count'], '-$' . number_format($data['breakdown']['chargebacks']['amount'], 2)));
        fputcsv($output, array(''));

        // Write detailed transactions
        fputcsv($output, array('Detailed Transactions'));
        fputcsv($output, array('Date', 'Transaction ID', 'User', 'Email', 'Type', 'Event', 'Amount', 'Currency', 'Status'));

        foreach ($data['transactions'] as $transaction) {
            fputcsv($output, array(
                $transaction['created_at'],
                $transaction['transaction_id'],
                $transaction['display_name'] ?? 'N/A',
                $transaction['user_email'] ?? 'N/A',
                $transaction['order_type'],
                $transaction['event_type'] ?? 'approved',
                number_format($transaction['amount'], 2),
                $transaction['currency'],
                $transaction['status']
            ));
        }

        fclose($output);
        exit;
    }

    /**
     * Render the earnings page
     */
    public function render_earnings_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        // Get initial data (last 30 days)
        $data = $this->get_earnings_data('month');

?>
        <div class="wrap flexpress-earnings-wrap">
            <h1>💰 FlexPress Earnings Dashboard</h1>

            <div class="flexpress-earnings-controls">
                <div class="period-selector">
                    <button class="button" data-period="today">Today</button>
                    <button class="button" data-period="week">Last 7 Days</button>
                    <button class="button button-primary" data-period="month">Last 30 Days</button>
                    <button class="button" data-period="year">Last Year</button>
                    <button class="button" data-period="custom">Custom Range</button>
                </div>

                <div class="custom-date-range" style="display: none;">
                    <input type="date" id="start-date" class="regular-text" />
                    <span>to</span>
                    <input type="date" id="end-date" class="regular-text" />
                    <button class="button" id="apply-custom-range">Apply</button>
                </div>

                <div class="export-controls">
                    <button class="button button-secondary" id="export-csv">
                        <span class="dashicons dashicons-download"></span> Export CSV
                    </button>
                </div>
            </div>

            <div class="flexpress-earnings-loading" style="display: none;">
                <span class="spinner is-active"></span> Loading earnings data...
            </div>

            <!-- Summary Cards -->
            <div class="flexpress-earnings-cards">
                <div class="earnings-card earnings-card-primary">
                    <div class="earnings-card-icon">💵</div>
                    <div class="earnings-card-content">
                        <h3>Gross Revenue</h3>
                        <div class="earnings-card-value" data-metric="gross_revenue">
                            $<?php echo number_format($data['gross_revenue'], 2); ?>
                        </div>
                        <p class="earnings-card-description">Total before commissions</p>
                    </div>
                </div>

                <div class="earnings-card earnings-card-success">
                    <div class="earnings-card-icon">✨</div>
                    <div class="earnings-card-content">
                        <h3>Net Revenue</h3>
                        <div class="earnings-card-value" data-metric="net_revenue">
                            $<?php echo number_format($data['net_revenue'], 2); ?>
                        </div>
                        <p class="earnings-card-description">After affiliate commissions</p>
                    </div>
                </div>

                <div class="earnings-card">
                    <div class="earnings-card-icon">📊</div>
                    <div class="earnings-card-content">
                        <h3>Total Transactions</h3>
                        <div class="earnings-card-value" data-metric="total_transactions">
                            <?php echo number_format($data['total_transactions']); ?>
                        </div>
                        <p class="earnings-card-description">Successful payments</p>
                    </div>
                </div>

                <div class="earnings-card">
                    <div class="earnings-card-icon">🤝</div>
                    <div class="earnings-card-content">
                        <h3>Affiliate Commissions</h3>
                        <div class="earnings-card-value" data-metric="affiliate_commissions">
                            $<?php echo number_format($data['affiliate_commissions'], 2); ?>
                        </div>
                        <p class="earnings-card-description">Paid to affiliates</p>
                    </div>
                </div>

                <div class="earnings-card">
                    <div class="earnings-card-icon">📈</div>
                    <div class="earnings-card-content">
                        <h3>Average Transaction</h3>
                        <div class="earnings-card-value" data-metric="average_transaction">
                            $<?php echo number_format($data['average_transaction'], 2); ?>
                        </div>
                        <p class="earnings-card-description">Per transaction</p>
                    </div>
                </div>

                <div class="earnings-card earnings-card-warning">
                    <div class="earnings-card-icon">⚠️</div>
                    <div class="earnings-card-content">
                        <h3>Refunds & Chargebacks</h3>
                        <div class="earnings-card-value" data-metric="refunds_chargebacks">
                            $<?php echo number_format($data['refunds_total'] + $data['chargebacks_total'], 2); ?>
                        </div>
                        <p class="earnings-card-description">Total losses</p>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="flexpress-earnings-charts">
                <div class="chart-container">
                    <h2>Revenue Over Time</h2>
                    <canvas id="revenue-chart"></canvas>
                </div>

                <div class="chart-container">
                    <h2>Transaction Breakdown</h2>
                    <canvas id="breakdown-chart"></canvas>
                </div>

                <div class="chart-container">
                    <h2>Gross vs Net Revenue</h2>
                    <canvas id="comparison-chart"></canvas>
                </div>
            </div>

            <!-- Transaction Breakdown Table -->
            <div class="flexpress-earnings-breakdown">
                <h2>Transaction Type Breakdown</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Transaction Type</th>
                            <th>Count</th>
                            <th>Total Amount</th>
                        </tr>
                    </thead>
                    <tbody id="breakdown-table-body">
                        <tr>
                            <td><strong>🎉 New Subscriptions</strong></td>
                            <td data-breakdown="subscriptions-count"><?php echo $data['breakdown']['subscriptions']['count']; ?></td>
                            <td data-breakdown="subscriptions-amount">$<?php echo number_format($data['breakdown']['subscriptions']['amount'], 2); ?></td>
                        </tr>
                        <tr>
                            <td><strong>🔄 Rebills</strong></td>
                            <td data-breakdown="rebills-count"><?php echo $data['breakdown']['rebills']['count']; ?></td>
                            <td data-breakdown="rebills-amount">$<?php echo number_format($data['breakdown']['rebills']['amount'], 2); ?></td>
                        </tr>
                        <tr>
                            <td><strong>🔓 PPV Unlocks</strong></td>
                            <td data-breakdown="unlocks-count"><?php echo $data['breakdown']['unlocks']['count']; ?></td>
                            <td data-breakdown="unlocks-amount">$<?php echo number_format($data['breakdown']['unlocks']['amount'], 2); ?></td>
                        </tr>
                        <tr class="refund-row">
                            <td><strong>💸 Refunds</strong></td>
                            <td data-breakdown="refunds-count"><?php echo $data['breakdown']['refunds']['count']; ?></td>
                            <td data-breakdown="refunds-amount">-$<?php echo number_format($data['breakdown']['refunds']['amount'], 2); ?></td>
                        </tr>
                        <tr class="chargeback-row">
                            <td><strong>⚠️ Chargebacks</strong></td>
                            <td data-breakdown="chargebacks-count"><?php echo $data['breakdown']['chargebacks']['count']; ?></td>
                            <td data-breakdown="chargebacks-amount">-$<?php echo number_format($data['breakdown']['chargebacks']['amount'], 2); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Detailed Transactions Table -->
            <div class="flexpress-earnings-transactions">
                <h2>Detailed Transactions</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Transaction ID</th>
                            <th>User</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="transactions-table-body">
                        <?php foreach (array_slice($data['transactions'], 0, 50) as $transaction): ?>
                            <tr>
                                <td><?php echo esc_html(date('Y-m-d H:i', strtotime($transaction['created_at']))); ?></td>
                                <td><code><?php echo esc_html($transaction['transaction_id']); ?></code></td>
                                <td><?php echo esc_html($transaction['display_name'] ?? 'N/A'); ?><br>
                                    <small><?php echo esc_html($transaction['user_email'] ?? ''); ?></small>
                                </td>
                                <td><?php echo esc_html($transaction['event_type'] ?? $transaction['order_type']); ?></td>
                                <td><strong>$<?php echo number_format($transaction['amount'], 2); ?></strong></td>
                                <td><span class="status-badge status-<?php echo esc_attr($transaction['status']); ?>">
                                        <?php echo esc_html($transaction['status']); ?>
                                    </span></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (count($data['transactions']) > 50): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 20px;">
                                    <em>Showing first 50 transactions. Export CSV for full list.</em>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <script type="text/javascript">
            var flexpressInitialData = <?php echo json_encode($data); ?>;
        </script>
<?php
    }
}

// Initialize the earnings settings page only in admin
if (is_admin()) {
    new FlexPress_Earnings_Settings();
}
