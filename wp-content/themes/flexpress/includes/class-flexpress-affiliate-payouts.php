<?php
/**
 * FlexPress Affiliate Payout Management
 * 
 * Handles payout processing, threshold management, and payment tracking.
 * 
 * @package FlexPress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * FlexPress Affiliate Payouts Class
 */
class FlexPress_Affiliate_Payouts {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_get_payouts_list', array($this, 'get_payouts_list'));
        add_action('wp_ajax_create_payout', array($this, 'create_payout'));
        add_action('wp_ajax_update_payout_status', array($this, 'update_payout_status'));
        add_action('wp_ajax_get_payout_details', array($this, 'get_payout_details'));
        add_action('wp_ajax_process_payout', array($this, 'process_payout'));
        add_action('wp_ajax_get_eligible_affiliates', array($this, 'get_eligible_affiliates'));
        
        // Schedule automatic payout processing
        add_action('flexpress_process_scheduled_payouts', array($this, 'process_scheduled_payouts'));
        
        // Add cron job for scheduled payouts
        if (!wp_next_scheduled('flexpress_process_scheduled_payouts')) {
            wp_schedule_event(time(), 'daily', 'flexpress_process_scheduled_payouts');
        }
    }
    
    /**
     * Get list of payouts for admin interface
     */
    public function get_payouts_list() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'flexpress_affiliate_nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'flexpress')]);
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'flexpress')]);
        }

        global $wpdb;
        $payouts_table = $wpdb->prefix . 'flexpress_affiliate_payouts';
        $affiliates_table = $wpdb->prefix . 'flexpress_affiliates';
        
        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 20);
        $status_filter = sanitize_text_field($_POST['status'] ?? '');
        $affiliate_filter = intval($_POST['affiliate_id'] ?? 0);
        
        $offset = ($page - 1) * $per_page;
        
        // Build query
        $where_conditions = array('1=1');
        $query_params = array();
        
        if (!empty($status_filter)) {
            $where_conditions[] = "p.status = %s";
            $query_params[] = $status_filter;
        }
        
        if ($affiliate_filter > 0) {
            $where_conditions[] = "p.affiliate_id = %d";
            $query_params[] = $affiliate_filter;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        // Get payouts with affiliate details
        $payouts = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, a.display_name, a.email, a.payout_method, a.payout_details
             FROM $payouts_table p
             LEFT JOIN $affiliates_table a ON p.affiliate_id = a.id
             WHERE $where_clause
             ORDER BY p.created_at DESC
             LIMIT %d OFFSET %d",
            array_merge($query_params, [$per_page, $offset])
        ));
        
        // Get total count
        $total_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*)
             FROM $payouts_table p
             WHERE $where_clause",
            $query_params
        ));
        
        // Format payout data
        $formatted_payouts = array();
        foreach ($payouts as $payout) {
            $formatted_payouts[] = array(
                'id' => $payout->id,
                'affiliate_id' => $payout->affiliate_id,
                'affiliate_name' => $payout->display_name,
                'affiliate_email' => $payout->email,
                'period_start' => $payout->period_start,
                'period_end' => $payout->period_end,
                'total_commissions' => floatval($payout->total_commissions),
                'payout_amount' => floatval($payout->payout_amount),
                'payout_method' => $payout->payout_method,
                'payout_details' => $payout->payout_details,
                'status' => $payout->status,
                'reference_id' => $payout->reference_id,
                'notes' => $payout->notes,
                'created_at' => $payout->created_at,
                'processed_at' => $payout->processed_at
            );
        }
        
        wp_send_json_success(array(
            'payouts' => $formatted_payouts,
            'total_count' => intval($total_count),
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total_count / $per_page)
        ));
    }
    
    /**
     * Create a new payout
     */
    public function create_payout() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'flexpress_affiliate_nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'flexpress')]);
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'flexpress')]);
        }

        $affiliate_id = intval($_POST['affiliate_id'] ?? 0);
        $period_start = sanitize_text_field($_POST['period_start'] ?? '');
        $period_end = sanitize_text_field($_POST['period_end'] ?? '');
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');
        
        if (!$affiliate_id || empty($period_start) || empty($period_end)) {
            wp_send_json_error(['message' => __('Please fill in all required fields.', 'flexpress')]);
        }
        
        // Validate dates
        $start_date = DateTime::createFromFormat('Y-m-d', $period_start);
        $end_date = DateTime::createFromFormat('Y-m-d', $period_end);
        
        if (!$start_date || !$end_date) {
            wp_send_json_error(['message' => __('Please enter valid dates.', 'flexpress')]);
        }
        
        if ($start_date >= $end_date) {
            wp_send_json_error(['message' => __('End date must be after start date.', 'flexpress')]);
        }
        
        global $wpdb;
        $affiliates_table = $wpdb->prefix . 'flexpress_affiliates';
        $transactions_table = $wpdb->prefix . 'flexpress_affiliate_transactions';
        $payouts_table = $wpdb->prefix . 'flexpress_affiliate_payouts';
        
        // Check if affiliate exists
        $affiliate = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $affiliates_table WHERE id = %d",
            $affiliate_id
        ));
        
        if (!$affiliate) {
            wp_send_json_error(['message' => __('Affiliate not found.', 'flexpress')]);
        }
        
        // Check for overlapping payouts
        $overlapping = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $payouts_table 
             WHERE affiliate_id = %d 
             AND status IN ('pending', 'processing', 'completed')
             AND (
                 (period_start <= %s AND period_end >= %s) OR
                 (period_start <= %s AND period_end >= %s) OR
                 (period_start >= %s AND period_end <= %s)
             )",
            $affiliate_id, $period_start, $period_start, $period_end, $period_end, $period_start, $period_end
        ));
        
        if ($overlapping > 0) {
            wp_send_json_error(['message' => __('A payout already exists for this period.', 'flexpress')]);
        }
        
        // Calculate total commissions for the period
        $total_commissions = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(commission_amount) FROM $transactions_table 
             WHERE affiliate_id = %d 
             AND status = 'approved'
             AND created_at >= %s 
             AND created_at <= %s",
            $affiliate_id, $period_start . ' 00:00:00', $period_end . ' 23:59:59'
        ));
        
        $total_commissions = floatval($total_commissions ?: 0);
        
        if ($total_commissions <= 0) {
            wp_send_json_error(['message' => __('No approved commissions found for this period.', 'flexpress')]);
        }
        
        // Create payout record
        $result = $wpdb->insert(
            $payouts_table,
            array(
                'affiliate_id' => $affiliate_id,
                'period_start' => $period_start,
                'period_end' => $period_end,
                'total_commissions' => $total_commissions,
                'payout_amount' => $total_commissions,
                'payout_method' => $affiliate->payout_method,
                'payout_details' => $affiliate->payout_details,
                'status' => 'pending',
                'notes' => $notes,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            wp_send_json_error(['message' => __('Failed to create payout. Please try again.', 'flexpress')]);
        }
        
        $payout_id = $wpdb->insert_id;
        
        wp_send_json_success(array(
            'message' => __('Payout created successfully.', 'flexpress'),
            'payout_id' => $payout_id,
            'total_commissions' => $total_commissions
        ));
    }
    
    /**
     * Update payout status
     */
    public function update_payout_status() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'flexpress_affiliate_nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'flexpress')]);
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'flexpress')]);
        }

        $payout_id = intval($_POST['payout_id'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? '');
        $reference_id = sanitize_text_field($_POST['reference_id'] ?? '');
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');
        
        if (!$payout_id || empty($status)) {
            wp_send_json_error(['message' => __('Invalid payout ID or status.', 'flexpress')]);
        }
        
        if (!in_array($status, ['pending', 'processing', 'completed', 'failed'])) {
            wp_send_json_error(['message' => __('Invalid status.', 'flexpress')]);
        }
        
        global $wpdb;
        $payouts_table = $wpdb->prefix . 'flexpress_affiliate_payouts';
        $affiliates_table = $wpdb->prefix . 'flexpress_affiliates';
        $transactions_table = $wpdb->prefix . 'flexpress_affiliate_transactions';
        
        // Get payout details
        $payout = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $payouts_table WHERE id = %d",
            $payout_id
        ));
        
        if (!$payout) {
            wp_send_json_error(['message' => __('Payout not found.', 'flexpress')]);
        }
        
        // Prepare update data
        $update_data = array(
            'status' => $status,
            'notes' => $notes
        );
        
        if (!empty($reference_id)) {
            $update_data['reference_id'] = $reference_id;
        }
        
        if ($status === 'completed') {
            $update_data['processed_at'] = current_time('mysql');
            
            // Mark related transactions as paid
            $wpdb->update(
                $transactions_table,
                array('status' => 'paid', 'paid_at' => current_time('mysql')),
                array(
                    'affiliate_id' => $payout->affiliate_id,
                    'status' => 'approved',
                    'created_at' => array($payout->period_start . ' 00:00:00', $payout->period_end . ' 23:59:59')
                ),
                array('%s', '%s'),
                array('%d', '%s', '%s')
            );
            
            // Update affiliate's paid commission total
            $wpdb->query($wpdb->prepare(
                "UPDATE $affiliates_table 
                 SET paid_commission = paid_commission + %f,
                     approved_commission = approved_commission - %f
                 WHERE id = %d",
                $payout->payout_amount,
                $payout->payout_amount,
                $payout->affiliate_id
            ));
        }
        
        // Update payout
        $result = $wpdb->update(
            $payouts_table,
            $update_data,
            array('id' => $payout_id),
            array_fill(0, count($update_data), '%s'),
            array('%d')
        );
        
        if ($result === false) {
            wp_send_json_error(['message' => __('Failed to update payout status.', 'flexpress')]);
        }
        
        wp_send_json_success(array(
            'message' => __('Payout status updated successfully.', 'flexpress')
        ));
    }
    
    /**
     * Get payout details
     */
    public function get_payout_details() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'flexpress_affiliate_nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'flexpress')]);
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'flexpress')]);
        }

        $payout_id = intval($_POST['payout_id'] ?? 0);
        
        if (!$payout_id) {
            wp_send_json_error(['message' => __('Invalid payout ID.', 'flexpress')]);
        }
        
        global $wpdb;
        $payouts_table = $wpdb->prefix . 'flexpress_affiliate_payouts';
        $affiliates_table = $wpdb->prefix . 'flexpress_affiliates';
        $transactions_table = $wpdb->prefix . 'flexpress_affiliate_transactions';
        
        // Get payout with affiliate details
        $payout = $wpdb->get_row($wpdb->prepare(
            "SELECT p.*, a.display_name, a.email, a.payout_method, a.payout_details
             FROM $payouts_table p
             LEFT JOIN $affiliates_table a ON p.affiliate_id = a.id
             WHERE p.id = %d",
            $payout_id
        ));
        
        if (!$payout) {
            wp_send_json_error(['message' => __('Payout not found.', 'flexpress')]);
        }
        
        // Get related transactions
        $transactions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $transactions_table 
             WHERE affiliate_id = %d 
             AND created_at >= %s 
             AND created_at <= %s
             ORDER BY created_at DESC",
            $payout->affiliate_id,
            $payout->period_start . ' 00:00:00',
            $payout->period_end . ' 23:59:59'
        ));
        
        // Format payout data
        $payout_data = array(
            'id' => $payout->id,
            'affiliate_id' => $payout->affiliate_id,
            'affiliate_name' => $payout->display_name,
            'affiliate_email' => $payout->email,
            'period_start' => $payout->period_start,
            'period_end' => $payout->period_end,
            'total_commissions' => floatval($payout->total_commissions),
            'payout_amount' => floatval($payout->payout_amount),
            'payout_method' => $payout->payout_method,
            'payout_details' => $payout->payout_details,
            'status' => $payout->status,
            'reference_id' => $payout->reference_id,
            'notes' => $payout->notes,
            'created_at' => $payout->created_at,
            'processed_at' => $payout->processed_at,
            'transactions' => array()
        );
        
        // Format transactions
        foreach ($transactions as $transaction) {
            $payout_data['transactions'][] = array(
                'id' => $transaction->id,
                'transaction_type' => $transaction->transaction_type,
                'transaction_id' => $transaction->transaction_id,
                'plan_id' => $transaction->plan_id,
                'revenue_amount' => floatval($transaction->revenue_amount),
                'commission_rate' => floatval($transaction->commission_rate),
                'commission_amount' => floatval($transaction->commission_amount),
                'status' => $transaction->status,
                'created_at' => $transaction->created_at
            );
        }
        
        wp_send_json_success($payout_data);
    }
    
    /**
     * Process payout (mark as processing/completed)
     */
    public function process_payout() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'flexpress_affiliate_nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'flexpress')]);
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'flexpress')]);
        }

        $payout_id = intval($_POST['payout_id'] ?? 0);
        $action = sanitize_text_field($_POST['action'] ?? '');
        $reference_id = sanitize_text_field($_POST['reference_id'] ?? '');
        
        if (!$payout_id || !in_array($action, ['process', 'complete', 'fail'])) {
            wp_send_json_error(['message' => __('Invalid payout ID or action.', 'flexpress')]);
        }
        
        $status_map = array(
            'process' => 'processing',
            'complete' => 'completed',
            'fail' => 'failed'
        );
        
        $new_status = $status_map[$action];
        
        // Update payout status
        $this->update_payout_status();
    }
    
    /**
     * Get affiliates eligible for payout
     */
    public function get_eligible_affiliates() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'flexpress_affiliate_nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'flexpress')]);
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'flexpress')]);
        }

        global $wpdb;
        $affiliates_table = $wpdb->prefix . 'flexpress_affiliates';
        $transactions_table = $wpdb->prefix . 'flexpress_affiliate_transactions';
        
        // Get affiliates with approved commissions above threshold
        $affiliates = $wpdb->get_results(
            "SELECT a.*, 
                    COALESCE(SUM(t.commission_amount), 0) as pending_amount
             FROM $affiliates_table a
             LEFT JOIN $transactions_table t ON a.id = t.affiliate_id 
                 AND t.status = 'approved'
             WHERE a.status = 'active'
             GROUP BY a.id
             HAVING pending_amount >= a.payout_threshold
             ORDER BY pending_amount DESC"
        );
        
        $eligible_affiliates = array();
        foreach ($affiliates as $affiliate) {
            $eligible_affiliates[] = array(
                'id' => $affiliate->id,
                'display_name' => $affiliate->display_name,
                'email' => $affiliate->email,
                'payout_method' => $affiliate->payout_method,
                'payout_threshold' => floatval($affiliate->payout_threshold),
                'pending_amount' => floatval($affiliate->pending_amount),
                'approved_commission' => floatval($affiliate->approved_commission)
            );
        }
        
        wp_send_json_success($eligible_affiliates);
    }
    
    /**
     * Process scheduled payouts (cron job)
     */
    public function process_scheduled_payouts() {
        $settings = get_option('flexpress_affiliate_settings', array());
        $payout_schedule = $settings['payout_schedule'] ?? 'monthly';
        
        // Only process if it's the right time based on schedule
        if (!$this->should_process_scheduled_payouts($payout_schedule)) {
            return;
        }
        
        global $wpdb;
        $affiliates_table = $wpdb->prefix . 'flexpress_affiliates';
        $transactions_table = $wpdb->prefix . 'flexpress_affiliate_transactions';
        $payouts_table = $wpdb->prefix . 'flexpress_affiliate_payouts';
        
        // Get period dates
        $period_dates = $this->get_payout_period_dates($payout_schedule);
        
        // Get eligible affiliates
        $affiliates = $wpdb->get_results(
            "SELECT a.*, 
                    COALESCE(SUM(t.commission_amount), 0) as pending_amount
             FROM $affiliates_table a
             LEFT JOIN $transactions_table t ON a.id = t.affiliate_id 
                 AND t.status = 'approved'
                 AND t.created_at >= %s 
                 AND t.created_at <= %s
             WHERE a.status = 'active'
             GROUP BY a.id
             HAVING pending_amount >= a.payout_threshold",
            $period_dates['start'], $period_dates['end']
        );
        
        foreach ($affiliates as $affiliate) {
            // Check if payout already exists for this period
            $existing_payout = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $payouts_table 
                 WHERE affiliate_id = %d 
                 AND period_start = %s 
                 AND period_end = %s",
                $affiliate->id, $period_dates['start'], $period_dates['end']
            ));
            
            if ($existing_payout) {
                continue; // Skip if payout already exists
            }
            
            // Create payout
            $wpdb->insert(
                $payouts_table,
                array(
                    'affiliate_id' => $affiliate->id,
                    'period_start' => $period_dates['start'],
                    'period_end' => $period_dates['end'],
                    'total_commissions' => $affiliate->pending_amount,
                    'payout_amount' => $affiliate->pending_amount,
                    'payout_method' => $affiliate->payout_method,
                    'payout_details' => $affiliate->payout_details,
                    'status' => 'pending',
                    'notes' => 'Automatically generated payout',
                    'created_at' => current_time('mysql')
                ),
                array('%d', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%s', '%s')
            );
        }
    }
    
    /**
     * Check if scheduled payouts should be processed
     */
    private function should_process_scheduled_payouts($schedule) {
        $now = new DateTime();
        $day_of_week = $now->format('N'); // 1 = Monday, 7 = Sunday
        $day_of_month = $now->format('j');
        
        switch ($schedule) {
            case 'weekly':
                return $day_of_week == 1; // Monday
            case 'monthly':
                return $day_of_month == 1; // 1st of month
            case 'quarterly':
                return $day_of_month == 1 && in_array($now->format('n'), [1, 4, 7, 10]); // 1st of quarter months
            default:
                return false;
        }
    }
    
    /**
     * Get payout period dates based on schedule
     */
    private function get_payout_period_dates($schedule) {
        $now = new DateTime();
        
        switch ($schedule) {
            case 'weekly':
                $start = clone $now;
                $start->modify('last monday');
                $end = clone $start;
                $end->modify('+6 days');
                break;
            case 'monthly':
                $start = clone $now;
                $start->modify('first day of last month');
                $end = clone $start;
                $end->modify('last day of last month');
                break;
            case 'quarterly':
                $start = clone $now;
                $start->modify('first day of 3 months ago');
                $end = clone $start;
                $end->modify('last day of 2 months ago');
                break;
            default:
                $start = clone $now;
                $start->modify('-30 days');
                $end = clone $now;
                break;
        }
        
        return array(
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d')
        );
    }
}

// Initialize the payout system
new FlexPress_Affiliate_Payouts();
