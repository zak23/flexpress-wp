<?php
/**
 * FlexPress REST API - Affiliates
 */

if (!defined('ABSPATH')) {
    exit;
}

class FlexPress_REST_Affiliates {
    public static function init() {
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
    }

    public static function register_routes() {
        register_rest_route('flexpress/v1', '/auth/login', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'auth_login'],
            'permission_callback' => '__return_true',
        ]);
        register_rest_route('flexpress/v1', '/auth/mint', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'auth_mint'],
            'permission_callback' => function () {
                return is_user_logged_in();
            },
        ]);

        register_rest_route('flexpress/v1', '/me', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'me'],
            'permission_callback' => [__CLASS__, 'require_affiliate_jwt'],
        ]);

        register_rest_route('flexpress/v1', '/me/commissions', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'me_commissions'],
            'permission_callback' => [__CLASS__, 'require_affiliate_jwt'],
        ]);

        register_rest_route('flexpress/v1', '/me/visits', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'me_visits'],
            'permission_callback' => [__CLASS__, 'require_affiliate_jwt'],
        ]);

        register_rest_route('flexpress/v1', '/me/payouts', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'me_payouts'],
            'permission_callback' => [__CLASS__, 'require_affiliate_jwt'],
        ]);

        register_rest_route('flexpress/v1', '/me/payouts/request', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'request_payout'],
            'permission_callback' => [__CLASS__, 'require_affiliate_jwt'],
        ]);

        // New: affiliate promo codes and settings
        register_rest_route('flexpress/v1', '/me/promo-codes', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'me_promo_codes'],
            'permission_callback' => [__CLASS__, 'require_affiliate_jwt'],
        ]);
        register_rest_route('flexpress/v1', '/me/settings', [
            'methods' => 'PATCH',
            'callback' => [__CLASS__, 'me_update_settings'],
            'permission_callback' => [__CLASS__, 'require_affiliate_jwt'],
        ]);

        register_rest_route('flexpress/v1', '/flowguard/webhook', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'flowguard_webhook'],
            'permission_callback' => '__return_true',
        ]);

        // Admin endpoints (nonce + caps)
        register_rest_route('flexpress/v1', '/admin/affiliates', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'admin_affiliates'],
            'permission_callback' => [__CLASS__, 'require_admin'],
        ]);
        register_rest_route('flexpress/v1', '/admin/transactions', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'admin_transactions'],
            'permission_callback' => [__CLASS__, 'require_admin'],
        ]);
        register_rest_route('flexpress/v1', '/admin/payouts', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'admin_payouts'],
            'permission_callback' => [__CLASS__, 'require_admin'],
        ]);
        register_rest_route('flexpress/v1', '/admin/payouts/(?P<id>\d+)/(?P<action>approve|deny|complete)', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'admin_payout_action'],
            'permission_callback' => [__CLASS__, 'require_admin'],
        ]);
        register_rest_route('flexpress/v1', '/admin/payouts/export', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'admin_payouts_export'],
            'permission_callback' => [__CLASS__, 'require_admin'],
        ]);
        
        register_rest_route('flexpress/v1', '/admin/affiliates/check-id', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'admin_check_affiliate_id'],
            'permission_callback' => '__return_true', // Public endpoint for form validation
        ]);
    }

    public static function get_bearer() {
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['Authorization'] ?? '');
        if (stripos($auth, 'Bearer ') === 0) {
            return trim(substr($auth, 7));
        }
        return '';
    }

    public static function require_admin() {
        if (!current_user_can('manage_options')) {
            return new WP_Error('forbidden', 'Admin only', ['status' => 403]);
        }
        return true;
    }

    /**
     * Set security headers for affiliate endpoints
     */
    public static function set_security_headers() {
        // Prevent caching of sensitive affiliate data
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
        
        // Add Vary header for logged-in users
        header('Vary: Cookie, Authorization');
        
        // Security headers
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        
        // CORS headers for affiliate dashboard
        $origin = get_http_origin();
        if ($origin && in_array($origin, array(get_site_url(), home_url()))) {
            header("Access-Control-Allow-Origin: $origin");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Methods: GET, POST, PATCH, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-WP-Nonce');
        }
    }

    public static function require_affiliate_jwt() {
        $token = self::get_bearer();
        if (!$token) {
            return new WP_Error('unauthorized', 'Missing token', ['status' => 401]);
        }
        list($ok, $payload) = FlexPress_JWT::validate($token);
        if (!$ok) {
            return new WP_Error('unauthorized', 'Invalid token', ['status' => 401]);
        }
        if (empty($payload['sub']) || empty($payload['role']) || $payload['role'] !== 'affiliate_user') {
            return new WP_Error('forbidden', 'Insufficient scope', ['status' => 403]);
        }
        return true;
    }

    public static function auth_login(WP_REST_Request $req) {
        self::set_security_headers();
        
        $email = sanitize_email($req->get_param('email'));
        $password = (string)$req->get_param('password');
        if (!$email || !$password) {
            return new WP_Error('bad_request', 'Email and password required', ['status' => 400]);
        }
        $user = wp_authenticate($email, $password);
        if (is_wp_error($user)) {
            return new WP_Error('unauthorized', 'Invalid credentials', ['status' => 401]);
        }
        // Check affiliate role
        if (!in_array('affiliate_user', (array)$user->roles, true) && !current_user_can('manage_options')) {
            return new WP_Error('forbidden', 'Not an affiliate', ['status' => 403]);
        }
        $payload = [
            'sub' => $user->ID,
            'role' => in_array('manage_options', (array)$user->caps, true) ? 'affiliate_admin' : 'affiliate_user',
        ];
        $token = FlexPress_JWT::issue($payload, 900);
        return [
            'token' => $token,
            'user' => [
                'id' => $user->ID,
                'email' => $user->user_email,
                'display_name' => $user->display_name,
            ],
        ];
    }

    public static function auth_mint(WP_REST_Request $req) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_Error('unauthorized', 'Login required', ['status' => 401]);
        }
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return new WP_Error('unauthorized', 'Invalid user', ['status' => 401]);
        }
        $payload = [
            'sub' => $user->ID,
            'role' => in_array('manage_options', (array)$user->caps, true) ? 'affiliate_admin' : (in_array('affiliate_user', (array)$user->roles, true) ? 'affiliate_user' : 'subscriber'),
        ];
        $token = FlexPress_JWT::issue($payload, 900);
        return [ 'token' => $token ];
    }

    public static function me(WP_REST_Request $req) {
        self::set_security_headers();
        list(, $payload) = FlexPress_JWT::validate(self::get_bearer());
        $user_id = intval($payload['sub']);
        global $wpdb;
        $affiliate = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}flexpress_affiliates WHERE user_id = %d",
            $user_id
        ));
        if (!$affiliate) {
            return new WP_Error('not_found', 'Affiliate not found', ['status' => 404]);
        }
        return [
            'profile' => $affiliate,
            'stats' => flexpress_get_affiliate_statistics(intval($affiliate->id), '30d'),
        ];
    }

    public static function me_commissions(WP_REST_Request $req) {
        self::set_security_headers();
        list(, $payload) = FlexPress_JWT::validate(self::get_bearer());
        $user_id = intval($payload['sub']);
        global $wpdb;
        $affiliate_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}flexpress_affiliates WHERE user_id = %d",
            $user_id
        ));
        if (!$affiliate_id) {
            return [];
        }
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}flexpress_affiliate_transactions WHERE affiliate_id = %d ORDER BY created_at DESC LIMIT 200",
            $affiliate_id
        ));
        return $rows ?: [];
    }

    public static function me_visits(WP_REST_Request $req) {
        self::set_security_headers();
        list(, $payload) = FlexPress_JWT::validate(self::get_bearer());
        $user_id = intval($payload['sub']);
        global $wpdb;
        $affiliate_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}flexpress_affiliates WHERE user_id = %d",
            $user_id
        ));
        if (!$affiliate_id) {
            return [];
        }
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}flexpress_affiliate_clicks WHERE affiliate_id = %d ORDER BY created_at DESC LIMIT 200",
            $affiliate_id
        ));
        return $rows ?: [];
    }

    public static function me_payouts(WP_REST_Request $req) {
        self::set_security_headers();
        list(, $payload) = FlexPress_JWT::validate(self::get_bearer());
        $user_id = intval($payload['sub']);
        global $wpdb;
        $affiliate_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}flexpress_affiliates WHERE user_id = %d",
            $user_id
        ));
        if (!$affiliate_id) {
            return [];
        }
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}flexpress_affiliate_payouts WHERE affiliate_id = %d ORDER BY created_at DESC LIMIT 200",
            $affiliate_id
        ));
        return $rows ?: [];
    }

    public static function request_payout(WP_REST_Request $req) {
        list(, $payload) = FlexPress_JWT::validate(self::get_bearer());
        $user_id = intval($payload['sub']);
        global $wpdb;
        $affiliate = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}flexpress_affiliates WHERE user_id = %d",
            $user_id
        ));
        if (!$affiliate) {
            return new WP_Error('not_found', 'Affiliate not found', ['status' => 404]);
        }
        // Basic threshold check before creating a payout row
        if (floatval($affiliate->approved_commission) < floatval($affiliate->payout_threshold)) {
            return new WP_Error('bad_request', 'Threshold not met', ['status' => 400]);
        }
        $payout_id = flexpress_process_affiliate_payout(intval($affiliate->id), [
            'period_start' => date('Y-m-01'),
            'period_end' => date('Y-m-t'),
        ]);
        if (!$payout_id) {
            return new WP_Error('server_error', 'Unable to create payout', ['status' => 500]);
        }
        return ['payout_id' => $payout_id];
    }

    public static function flowguard_webhook(WP_REST_Request $req) {
        // Mirror existing handler (reads php://input). Ensure body passthrough works.
        flexpress_flowguard_webhook_handler();
        return ['ok' => true];
    }

    // New affiliate endpoints
    public static function me_promo_codes(WP_REST_Request $req) {
        list(, $payload) = FlexPress_JWT::validate(self::get_bearer());
        $user_id = intval($payload['sub']);
        global $wpdb;
        $affiliate_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}flexpress_affiliates WHERE user_id = %d",
            $user_id
        ));
        if (!$affiliate_id) {
            return [];
        }
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}flexpress_affiliate_promo_codes WHERE affiliate_id = %d ORDER BY created_at DESC",
            $affiliate_id
        ));
        return $rows ?: [];
    }

    public static function me_update_settings(WP_REST_Request $req) {
        list(, $payload) = FlexPress_JWT::validate(self::get_bearer());
        $user_id = intval($payload['sub']);
        $payout_method = sanitize_text_field($req->get_param('payout_method'));
        $payout_details = (string)$req->get_param('payout_details');
        $allowed = ['paypal','crypto','aus_bank_transfer','yoursafe','ach','swift'];
        if ($payout_method && !in_array($payout_method, $allowed, true)) {
            return new WP_Error('bad_request', 'Invalid payout method', ['status' => 400]);
        }
        global $wpdb;
        $affiliate = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}flexpress_affiliates WHERE user_id = %d",
            $user_id
        ));
        if (!$affiliate) {
            return new WP_Error('not_found', 'Affiliate not found', ['status' => 404]);
        }
        $data = [];
        $where = ['id' => intval($affiliate->id)];
        $fmt = [];
        $where_fmt = ['%d'];
        if ($payout_method) {
            $data['payout_method'] = $payout_method;
            $fmt[] = '%s';
        }
        if ($payout_details !== '') {
            $data['payout_details'] = flexpress_encrypt_payout_details($payout_details);
            $fmt[] = '%s';
        }
        if (!$data) {
            return ['ok' => true];
        }
        $wpdb->update($wpdb->prefix . 'flexpress_affiliates', $data, $where, $fmt, $where_fmt);
        return ['ok' => true];
    }

    public static function admin_affiliates(WP_REST_Request $req) {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}flexpress_affiliates ORDER BY created_at DESC LIMIT 500");
        return $rows ?: [];
    }

    public static function admin_transactions(WP_REST_Request $req) {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}flexpress_affiliate_transactions ORDER BY created_at DESC LIMIT 500");
        return $rows ?: [];
    }

    public static function admin_payouts(WP_REST_Request $req) {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}flexpress_affiliate_payouts ORDER BY created_at DESC LIMIT 500");
        return $rows ?: [];
    }

    public static function admin_payout_action(WP_REST_Request $req) {
        global $wpdb;
        $id = intval($req['id']);
        $action = sanitize_text_field($req['action'] ?? '');
        if (!$id) {
            return new WP_Error('bad_request', 'Missing payout id', ['status' => 400]);
        }
        switch ($action) {
            case 'approve':
                $wpdb->update($wpdb->prefix . 'flexpress_affiliate_payouts', ['status' => 'processing'], ['id' => $id], ['%s'], ['%d']);
                break;
            case 'deny':
                $wpdb->update($wpdb->prefix . 'flexpress_affiliate_payouts', ['status' => 'failed'], ['id' => $id], ['%s'], ['%d']);
                break;
            case 'complete':
                $wpdb->update($wpdb->prefix . 'flexpress_affiliate_payouts', ['status' => 'completed', 'processed_at' => current_time('mysql')], ['id' => $id], ['%s','%s'], ['%d']);
                break;
            default:
                return new WP_Error('bad_request', 'Unknown action', ['status' => 400]);
        }
        return ['ok' => true];
    }

    public static function admin_payouts_export(WP_REST_Request $req) {
        global $wpdb;
        $method = sanitize_text_field($req->get_param('method'));
        $allowed = ['paypal','crypto','aus_bank_transfer','yoursafe','ach','swift'];
        if ($method && !in_array($method, $allowed, true)) {
            return new WP_Error('bad_request', 'Invalid method', ['status' => 400]);
        }
        $query = "SELECT p.*, a.display_name, a.email, a.payout_method, a.payout_details FROM {$wpdb->prefix}flexpress_affiliate_payouts p JOIN {$wpdb->prefix}flexpress_affiliates a ON p.affiliate_id = a.id WHERE p.status IN ('pending','processing')";
        if ($method) {
            $query .= $wpdb->prepare(" AND a.payout_method = %s", $method);
        }
        $rows = $wpdb->get_results($query);
        $csv = "Affiliate,Email,Method,Amount,PeriodStart,PeriodEnd,Reference\n";
        foreach ($rows as $r) {
            $csv .= sprintf("%s,%s,%s,%.2f,%s,%s,%s\n",
                $r->display_name,
                $r->email,
                $r->payout_method,
                floatval($r->payout_amount),
                $r->period_start,
                $r->period_end,
                $r->reference_id
            );
        }
        return [
            'method' => $method ?: 'all',
            'csv' => $csv,
        ];
    }

    public static function admin_check_affiliate_id(WP_REST_Request $req) {
        $affiliate_id = sanitize_text_field($req->get_param('affiliate_id'));
        
        if (empty($affiliate_id)) {
            return new WP_Error('bad_request', 'Affiliate ID required', ['status' => 400]);
        }
        
        // Validate format
        if (!preg_match('/^[a-zA-Z0-9]{3,20}$/', $affiliate_id)) {
            return ['available' => false, 'reason' => 'Invalid format'];
        }
        
        global $wpdb;
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}flexpress_affiliates WHERE affiliate_id = %s",
            $affiliate_id
        ));
        
        return ['available' => !$exists];
    }
}

FlexPress_REST_Affiliates::init();


