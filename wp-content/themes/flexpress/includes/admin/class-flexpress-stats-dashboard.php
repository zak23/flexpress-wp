<?php
/**
 * FlexPress Stats Dashboard
 *
 * Admin dashboard widgets for displaying stats: sales, trials, rebills, ratings
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * FlexPress Stats Dashboard Class
 */
class FlexPress_Stats_Dashboard
{
    /**
     * Constructor
     */
    public function __construct()
    {
        // Register WordPress dashboard widgets
        add_action('wp_dashboard_setup', array($this, 'register_dashboard_widgets'));

        // Register admin menu page
        add_action('admin_menu', array($this, 'add_dashboard_page'), 15); // Priority 15 to register after parent menu exists (priority 10)

        // Enqueue admin assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Register AJAX endpoints
        add_action('wp_ajax_flexpress_get_stats', array($this, 'ajax_get_stats'));
    }

    /**
     * Register WordPress dashboard widgets
     */
    public function register_dashboard_widgets()
    {
        if (!flexpress_current_user_is_founder()) {
            return;
        }

        wp_add_dashboard_widget(
            'flexpress_stats_sales',
            __('FlexPress Sales', 'flexpress'),
            array($this, 'render_sales_widget')
        );

        wp_add_dashboard_widget(
            'flexpress_stats_trials',
            __('FlexPress Free Trials', 'flexpress'),
            array($this, 'render_trials_widget')
        );

        wp_add_dashboard_widget(
            'flexpress_stats_rebills',
            __('FlexPress Rebills', 'flexpress'),
            array($this, 'render_rebills_widget')
        );

        wp_add_dashboard_widget(
            'flexpress_stats_ratings',
            __('FlexPress Ratings', 'flexpress'),
            array($this, 'render_ratings_widget')
        );

        wp_add_dashboard_widget(
            'flexpress_stats_unlocks',
            __('FlexPress Episode Unlocks', 'flexpress'),
            array($this, 'render_unlocks_widget')
        );

        wp_add_dashboard_widget(
            'flexpress_stats_registrations',
            __('FlexPress Registrations', 'flexpress'),
            array($this, 'render_registrations_widget')
        );

        wp_add_dashboard_widget(
            'flexpress_stats_memberships',
            __('FlexPress Memberships', 'flexpress'),
            array($this, 'render_memberships_widget')
        );
    }

    /**
     * Add dashboard page to FlexPress admin menu
     * Priority 15 ensures this runs after the parent menu is created (priority 10)
     */
    public function add_dashboard_page()
    {
        add_submenu_page(
            'flexpress-settings',
            __('Dashboard', 'flexpress'),
            __('Dashboard', 'flexpress'),
            'manage_options',
            'flexpress-dashboard',
            array($this, 'render_dashboard_page'),
            0 // Position: first submenu item (0 or null = first)
        );

        // Make Dashboard appear first in submenu
        // Use admin_menu hook with later priority to reorder after Settings class
        add_action('admin_menu', array($this, 'reorder_admin_menu'), 999);
    }

    /**
     * Reorder admin menu to put Dashboard first
     */
    public function reorder_admin_menu()
    {
        global $submenu;
        if (!isset($submenu['flexpress-settings'])) {
            return;
        }

        // Find Dashboard item
        $dashboard_item = null;
        $dashboard_key = null;
        foreach ($submenu['flexpress-settings'] as $key => $item) {
            if (isset($item[2]) && $item[2] === 'flexpress-dashboard') {
                $dashboard_item = $item;
                $dashboard_key = $key;
                break;
            }
        }

        // Move Dashboard to first position (after main menu item which is usually at index 0 or 1)
        if ($dashboard_item && $dashboard_key !== null) {
            // Remove from current position
            unset($submenu['flexpress-settings'][$dashboard_key]);
            // Add to beginning (after main menu item if it exists at index 0)
            // We'll insert after the first item (which is usually the main page)
            $menu_items = $submenu['flexpress-settings'];
            $reordered = array();
            $inserted = false;

            foreach ($menu_items as $key => $item) {
                // Insert Dashboard right after first item (main menu item or General)
                if (!$inserted && ($key === 0 || (isset($item[2]) && $item[2] === 'flexpress-settings'))) {
                    $reordered[] = $item;
                    $reordered[] = $dashboard_item;
                    $inserted = true;
                } else {
                    $reordered[] = $item;
                }
            }

            // If we didn't insert (shouldn't happen), just add to beginning
            if (!$inserted) {
                array_unshift($reordered, $dashboard_item);
            }

            $submenu['flexpress-settings'] = $reordered;
        }
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_admin_assets($hook)
    {
        // Only load on dashboard pages
        // WordPress dashboard: 'index.php'
        // FlexPress dashboard page: 'flexpress-settings_page_flexpress-dashboard'
        if ($hook !== 'index.php' && strpos($hook, 'flexpress-dashboard') === false) {
            return;
        }

        wp_enqueue_style(
            'flexpress-admin-stats',
            get_template_directory_uri() . '/assets/css/admin-stats.css',
            array(),
            wp_get_theme()->get('Version')
        );

        wp_enqueue_script(
            'flexpress-admin-stats',
            get_template_directory_uri() . '/assets/js/admin-stats.js',
            array('jquery', 'jquery-ui-datepicker'),
            wp_get_theme()->get('Version'),
            true
        );

        // Enqueue WordPress date picker styles
        wp_enqueue_style('jquery-ui-datepicker');

        // Localize script
        wp_localize_script(
            'flexpress-admin-stats',
            'flexpressStats',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('flexpress_stats_nonce'),
                'context' => $hook === 'index.php' ? 'wordpress_dashboard' : 'flexpress_page',
            )
        );
    }

    /**
     * Render FlexPress dashboard page
     */
    public function render_dashboard_page()
    {
        if (!flexpress_current_user_is_founder()) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'flexpress'));
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('FlexPress Dashboard', 'flexpress'); ?></h1>
            <div class="flexpress-stats-dashboard-page">
                <div class="flexpress-stats-time-range-selector">
                    <label for="flexpress-stats-time-range">
                        <?php esc_html_e('Time Range:', 'flexpress'); ?>
                    </label>
                    <select id="flexpress-stats-time-range" class="flexpress-stats-time-range">
                        <option value="today"><?php esc_html_e('Today', 'flexpress'); ?></option>
                        <option value="this_week"><?php esc_html_e('This Week', 'flexpress'); ?></option>
                        <option value="this_month" selected><?php esc_html_e('This Month', 'flexpress'); ?></option>
                        <option value="this_year"><?php esc_html_e('This Year', 'flexpress'); ?></option>
                        <option value="all_time"><?php esc_html_e('All Time', 'flexpress'); ?></option>
                        <option value="custom"><?php esc_html_e('Custom Range', 'flexpress'); ?></option>
                    </select>
                    <div id="flexpress-stats-custom-range" style="display: none; margin-top: 10px;">
                        <input type="text" id="flexpress-stats-date-from" class="flexpress-date-picker" placeholder="<?php esc_attr_e('From Date', 'flexpress'); ?>" />
                        <input type="text" id="flexpress-stats-date-to" class="flexpress-date-picker" placeholder="<?php esc_attr_e('To Date', 'flexpress'); ?>" />
                    </div>
                </div>

                <div class="flexpress-stats-grid flexpress-stats-grid-page">
                    <?php
                    $this->render_stats_card('sales', __('Sales', 'flexpress'), 'dashicons-money-alt');
                    $this->render_stats_card('trials', __('Free Trials', 'flexpress'), 'dashicons-star-filled');
                    $this->render_stats_card('rebills', __('Rebills', 'flexpress'), 'dashicons-update');
                    $this->render_stats_card('ratings', __('Ratings', 'flexpress'), 'dashicons-star-half');
                    $this->render_stats_card('unlocks', __('Episode Unlocks', 'flexpress'), 'dashicons-unlock');
                    $this->render_stats_card('registrations', __('Registrations', 'flexpress'), 'dashicons-groups');
                    $this->render_stats_card('memberships', __('Active Memberships', 'flexpress'), 'dashicons-admin-users');
                    ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render stats card
     *
     * @param string $type Stat type: sales, trials, rebills, ratings
     * @param string $title Card title
     * @param string $icon Dashicon class
     */
    private function render_stats_card($type, $title, $icon)
    {
        ?>
        <div class="flexpress-stats-card" data-stat-type="<?php echo esc_attr($type); ?>">
            <div class="flexpress-stats-card-header">
                <span class="dashicons <?php echo esc_attr($icon); ?>"></span>
                <h3><?php echo esc_html($title); ?></h3>
            </div>
            <div class="flexpress-stats-card-content">
                <div class="flexpress-stats-loading">
                    <span class="spinner is-active"></span>
                    <?php esc_html_e('Loading...', 'flexpress'); ?>
                </div>
                <div class="flexpress-stats-data" style="display: none;"></div>
            </div>
        </div>
        <?php
    }

    /**
     * Render sales widget for WordPress dashboard
     */
    public function render_sales_widget()
    {
        $this->render_widget_content('sales', __('Sales', 'flexpress'));
    }

    /**
     * Render trials widget for WordPress dashboard
     */
    public function render_trials_widget()
    {
        $this->render_widget_content('trials', __('Free Trials', 'flexpress'));
    }

    /**
     * Render rebills widget for WordPress dashboard
     */
    public function render_rebills_widget()
    {
        $this->render_widget_content('rebills', __('Rebills', 'flexpress'));
    }

    /**
     * Render ratings widget for WordPress dashboard
     */
    public function render_ratings_widget()
    {
        $this->render_widget_content('ratings', __('Ratings', 'flexpress'));
    }

    /**
     * Render unlocks widget for WordPress dashboard
     */
    public function render_unlocks_widget()
    {
        $this->render_widget_content('unlocks', __('Episode Unlocks', 'flexpress'));
    }

    /**
     * Render registrations widget for WordPress dashboard
     */
    public function render_registrations_widget()
    {
        $this->render_widget_content('registrations', __('Registrations', 'flexpress'));
    }

    /**
     * Render memberships widget for WordPress dashboard
     */
    public function render_memberships_widget()
    {
        $this->render_widget_content('memberships', __('Active Memberships', 'flexpress'));
    }

    /**
     * Render widget content
     *
     * @param string $type Stat type
     * @param string $title Widget title
     */
    private function render_widget_content($type, $title)
    {
        ?>
        <div class="flexpress-stats-widget" data-stat-type="<?php echo esc_attr($type); ?>">
            <div class="flexpress-stats-widget-time-range">
                <select class="flexpress-stats-time-range-small">
                    <option value="today"><?php esc_html_e('Today', 'flexpress'); ?></option>
                    <option value="this_week"><?php esc_html_e('This Week', 'flexpress'); ?></option>
                    <option value="this_month" selected><?php esc_html_e('This Month', 'flexpress'); ?></option>
                    <option value="this_year"><?php esc_html_e('This Year', 'flexpress'); ?></option>
                    <option value="all_time"><?php esc_html_e('All Time', 'flexpress'); ?></option>
                </select>
            </div>
            <div class="flexpress-stats-widget-content">
                <div class="flexpress-stats-loading">
                    <span class="spinner is-active"></span>
                    <?php esc_html_e('Loading...', 'flexpress'); ?>
                </div>
                <div class="flexpress-stats-data" style="display: none;"></div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX handler to get stats
     */
    public function ajax_get_stats()
    {
        check_ajax_referer('flexpress_stats_nonce', 'nonce');

        if (!flexpress_current_user_is_founder()) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'flexpress')));
        }

        $type = sanitize_text_field($_POST['type'] ?? 'sales');
        $time_range = sanitize_text_field($_POST['time_range'] ?? 'this_month');
        $custom_from = sanitize_text_field($_POST['custom_from'] ?? '');
        $custom_to = sanitize_text_field($_POST['custom_to'] ?? '');

        // Validate time range
        $valid_ranges = array('today', 'this_week', 'this_month', 'this_year', 'all_time', 'custom');
        if (!in_array($time_range, $valid_ranges)) {
            $time_range = 'this_month';
        }

        // Validate custom dates
        if ($time_range === 'custom') {
            if (empty($custom_from) || empty($custom_to)) {
                wp_send_json_error(array('message' => __('Custom date range requires both from and to dates.', 'flexpress')));
            }
            // Validate date format (Y-m-d)
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $custom_from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $custom_to)) {
                wp_send_json_error(array('message' => __('Invalid date format. Use YYYY-MM-DD.', 'flexpress')));
            }
        }

        // Get stats based on type
        switch ($type) {
            case 'sales':
                $stats = flexpress_get_sales_stats($time_range, $custom_from, $custom_to);
                $html = $this->format_sales_html($stats);
                break;

            case 'trials':
                $stats = flexpress_get_trial_stats($time_range, $custom_from, $custom_to);
                $html = $this->format_trials_html($stats);
                break;

            case 'rebills':
                $stats = flexpress_get_rebill_stats($time_range, $custom_from, $custom_to);
                $html = $this->format_rebills_html($stats);
                break;

            case 'ratings':
                $stats = flexpress_get_rating_stats($time_range, $custom_from, $custom_to);
                $html = $this->format_ratings_html($stats);
                break;

            case 'unlocks':
                $stats = flexpress_get_unlock_stats($time_range, $custom_from, $custom_to);
                $html = $this->format_unlocks_html($stats);
                break;

            case 'registrations':
                $stats = flexpress_get_registration_stats($time_range, $custom_from, $custom_to);
                $html = $this->format_registrations_html($stats);
                break;

            case 'memberships':
                $stats = flexpress_get_membership_stats($time_range, $custom_from, $custom_to);
                $html = $this->format_memberships_html($stats);
                break;

            default:
                wp_send_json_error(array('message' => __('Invalid stat type.', 'flexpress')));
                return;
        }

        wp_send_json_success(array(
            'html' => $html,
            'stats' => $stats,
        ));
    }

    /**
     * Format sales stats HTML
     *
     * @param array $stats Stats array
     * @return string HTML
     */
    private function format_sales_html($stats)
    {
        $total_amount = number_format($stats['total_amount'], 2);
        $total_count = number_format($stats['total_count']);
        $avg_amount = number_format($stats['avg_amount'], 2);

        $html = '<div class="flexpress-stats-primary">';
        $html .= '<span class="flexpress-stats-value">$' . esc_html($total_amount) . '</span>';
        $html .= '<span class="flexpress-stats-label">' . esc_html__('Total Sales', 'flexpress') . '</span>';
        $html .= '</div>';

        $html .= '<div class="flexpress-stats-secondary">';
        $html .= '<div class="flexpress-stats-metric">';
        $html .= '<span class="flexpress-stats-metric-value">' . esc_html($total_count) . '</span>';
        $html .= '<span class="flexpress-stats-metric-label">' . esc_html__('Transactions', 'flexpress') . '</span>';
        $html .= '</div>';
        $html .= '<div class="flexpress-stats-metric">';
        $html .= '<span class="flexpress-stats-metric-value">$' . esc_html($avg_amount) . '</span>';
        $html .= '<span class="flexpress-stats-metric-label">' . esc_html__('Average', 'flexpress') . '</span>';
        $html .= '</div>';

        if (!empty($stats['subscription_count']) || !empty($stats['purchase_count'])) {
            $html .= '<div class="flexpress-stats-breakdown">';
            if (!empty($stats['subscription_count'])) {
                $html .= '<div>' . esc_html__('Subscriptions:', 'flexpress') . ' ' . number_format($stats['subscription_count']) . '</div>';
            }
            if (!empty($stats['purchase_count'])) {
                $html .= '<div>' . esc_html__('Purchases:', 'flexpress') . ' ' . number_format($stats['purchase_count']) . '</div>';
            }
            $html .= '</div>';
        }

        // Show previous period comparison if available
        if (!empty($stats['previous_comparison'])) {
            $comparison = $stats['previous_comparison'];
            $amount_change = $comparison['amount_change'];
            $change_class = $amount_change >= 0 ? 'positive' : 'negative';
            $change_icon = $amount_change >= 0 ? '↑' : '↓';
            $html .= '<div class="flexpress-stats-comparison ' . $change_class . '">';
            $html .= '<span class="flexpress-stats-change">' . $change_icon . ' ' . abs($amount_change) . '%</span>';
            $html .= '<span class="flexpress-stats-change-label">' . esc_html__('vs Previous Period', 'flexpress') . '</span>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Format trials stats HTML
     *
     * @param array $stats Stats array
     * @return string HTML
     */
    private function format_trials_html($stats)
    {
        $total_created = number_format($stats['total_created']);
        $active_trials = number_format($stats['active_trials']);
        $conversions = number_format($stats['conversions']);

        $html = '<div class="flexpress-stats-primary">';
        $html .= '<span class="flexpress-stats-value">' . esc_html($total_created) . '</span>';
        $html .= '<span class="flexpress-stats-label">' . esc_html__('Trial Links Created', 'flexpress') . '</span>';
        $html .= '</div>';

        $html .= '<div class="flexpress-stats-secondary">';
        $html .= '<div class="flexpress-stats-metric">';
        $html .= '<span class="flexpress-stats-metric-value">' . esc_html($active_trials) . '</span>';
        $html .= '<span class="flexpress-stats-metric-label">' . esc_html__('Active Trials', 'flexpress') . '</span>';
        $html .= '</div>';
        $html .= '<div class="flexpress-stats-metric">';
        $html .= '<span class="flexpress-stats-metric-value">' . esc_html($conversions) . '</span>';
        $html .= '<span class="flexpress-stats-metric-label">' . esc_html__('Conversions', 'flexpress') . '</span>';
        $html .= '</div>';

        if ($stats['total_created'] > 0) {
            $html .= '<div class="flexpress-stats-breakdown">';
            $html .= '<div>' . esc_html__('Usage Rate:', 'flexpress') . ' ' . number_format($stats['usage_rate'], 1) . '%</div>';
            $html .= '<div>' . esc_html__('Total Uses:', 'flexpress') . ' ' . number_format($stats['total_uses']) . '</div>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Format rebills stats HTML
     *
     * @param array $stats Stats array
     * @return string HTML
     */
    private function format_rebills_html($stats)
    {
        $total_amount = number_format($stats['total_amount'], 2);
        $total_count = number_format($stats['total_count']);
        $avg_amount = number_format($stats['avg_amount'], 2);

        $html = '<div class="flexpress-stats-primary">';
        $html .= '<span class="flexpress-stats-value">$' . esc_html($total_amount) . '</span>';
        $html .= '<span class="flexpress-stats-label">' . esc_html__('Total Rebill Revenue', 'flexpress') . '</span>';
        $html .= '</div>';

        $html .= '<div class="flexpress-stats-secondary">';
        $html .= '<div class="flexpress-stats-metric">';
        $html .= '<span class="flexpress-stats-metric-value">' . esc_html($total_count) . '</span>';
        $html .= '<span class="flexpress-stats-metric-label">' . esc_html__('Total Rebills', 'flexpress') . '</span>';
        $html .= '</div>';
        $html .= '<div class="flexpress-stats-metric">';
        $html .= '<span class="flexpress-stats-metric-value">$' . esc_html($avg_amount) . '</span>';
        $html .= '<span class="flexpress-stats-metric-label">' . esc_html__('Average', 'flexpress') . '</span>';
        $html .= '</div>';

        if (!empty($stats['unique_users'])) {
            $html .= '<div class="flexpress-stats-breakdown">';
            $html .= '<div>' . esc_html__('Unique Users:', 'flexpress') . ' ' . number_format($stats['unique_users']) . '</div>';
            if (!empty($stats['unique_subscriptions'])) {
                $html .= '<div>' . esc_html__('Subscriptions:', 'flexpress') . ' ' . number_format($stats['unique_subscriptions']) . '</div>';
            }
            $html .= '</div>';
        }

        // Show previous period comparison if available
        if (!empty($stats['previous_comparison'])) {
            $comparison = $stats['previous_comparison'];
            $amount_change = $comparison['amount_change'];
            $change_class = $amount_change >= 0 ? 'positive' : 'negative';
            $change_icon = $amount_change >= 0 ? '↑' : '↓';
            $html .= '<div class="flexpress-stats-comparison ' . $change_class . '">';
            $html .= '<span class="flexpress-stats-change">' . $change_icon . ' ' . abs($amount_change) . '%</span>';
            $html .= '<span class="flexpress-stats-change-label">' . esc_html__('vs Previous Period', 'flexpress') . '</span>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Format ratings stats HTML
     *
     * @param array $stats Stats array
     * @return string HTML
     */
    private function format_ratings_html($stats)
    {
        $total_count = number_format($stats['total_count']);
        $avg_rating = number_format($stats['avg_rating'], 1);

        $html = '<div class="flexpress-stats-primary">';
        $html .= '<span class="flexpress-stats-value">' . esc_html($avg_rating) . '</span>';
        $html .= '<span class="flexpress-stats-label">' . esc_html__('Average Rating', 'flexpress') . '</span>';
        $html .= '</div>';

        $html .= '<div class="flexpress-stats-secondary">';
        $html .= '<div class="flexpress-stats-metric">';
        $html .= '<span class="flexpress-stats-metric-value">' . esc_html($total_count) . '</span>';
        $html .= '<span class="flexpress-stats-metric-label">' . esc_html__('Total Ratings', 'flexpress') . '</span>';
        $html .= '</div>';
        $html .= '<div class="flexpress-stats-metric">';
        $html .= '<span class="flexpress-stats-metric-value">' . esc_html(number_format($stats['episodes_rated'])) . '</span>';
        $html .= '<span class="flexpress-stats-metric-label">' . esc_html__('Episodes Rated', 'flexpress') . '</span>';
        $html .= '</div>';

        if (!empty($stats['distribution'])) {
            $html .= '<div class="flexpress-stats-breakdown">';
            $html .= '<div class="flexpress-stats-rating-distribution">';
            foreach ($stats['distribution'] as $rating => $count) {
                if ($count > 0) {
                    $stars = str_repeat('★', intval($rating));
                    $html .= '<div class="flexpress-rating-dist-item">';
                    $html .= '<span class="flexpress-rating-stars">' . esc_html($stars) . '</span>';
                    $html .= '<span class="flexpress-rating-count">' . esc_html(number_format($count)) . '</span>';
                    $html .= '</div>';
                }
            }
            $html .= '</div>';
            $html .= '</div>';
        }

        // Show previous period comparison if available
        if (!empty($stats['previous_comparison'])) {
            $comparison = $stats['previous_comparison'];
            $count_change = $comparison['count_change'];
            $change_class = $count_change >= 0 ? 'positive' : 'negative';
            $change_icon = $count_change >= 0 ? '↑' : '↓';
            $html .= '<div class="flexpress-stats-comparison ' . $change_class . '">';
            $html .= '<span class="flexpress-stats-change">' . $change_icon . ' ' . abs($count_change) . '%</span>';
            $html .= '<span class="flexpress-stats-change-label">' . esc_html__('vs Previous Period', 'flexpress') . '</span>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Format unlocks stats HTML
     *
     * @param array $stats Stats array
     * @return string HTML
     */
    private function format_unlocks_html($stats)
    {
        $total_count = number_format($stats['total_count']);
        $total_amount = number_format($stats['total_amount'], 2);

        $html = '<div class="flexpress-stats-primary">';
        $html .= '<span class="flexpress-stats-value">' . esc_html($total_count) . '</span>';
        $html .= '<span class="flexpress-stats-label">' . esc_html__('Total Unlocks', 'flexpress') . '</span>';
        $html .= '</div>';

        $html .= '<div class="flexpress-stats-secondary">';
        $html .= '<div class="flexpress-stats-metric">';
        $html .= '<span class="flexpress-stats-metric-value">$' . esc_html($total_amount) . '</span>';
        $html .= '<span class="flexpress-stats-metric-label">' . esc_html__('Total Revenue', 'flexpress') . '</span>';
        $html .= '</div>';
        $html .= '<div class="flexpress-stats-metric">';
        $html .= '<span class="flexpress-stats-metric-value">' . esc_html(number_format($stats['unique_users'])) . '</span>';
        $html .= '<span class="flexpress-stats-metric-label">' . esc_html__('Unique Users', 'flexpress') . '</span>';
        $html .= '</div>';

        if (!empty($stats['unique_episodes'])) {
            $html .= '<div class="flexpress-stats-breakdown">';
            $html .= '<div>' . esc_html__('Episodes Unlocked:', 'flexpress') . ' ' . number_format($stats['unique_episodes']) . '</div>';
            if (!empty($stats['avg_amount'])) {
                $html .= '<div>' . esc_html__('Avg Price:', 'flexpress') . ' $' . number_format($stats['avg_amount'], 2) . '</div>';
            }
            $html .= '</div>';
        }

        // Show previous period comparison if available
        if (!empty($stats['previous_comparison'])) {
            $comparison = $stats['previous_comparison'];
            $amount_change = $comparison['amount_change'];
            $change_class = $amount_change >= 0 ? 'positive' : 'negative';
            $change_icon = $amount_change >= 0 ? '↑' : '↓';
            $html .= '<div class="flexpress-stats-comparison ' . $change_class . '">';
            $html .= '<span class="flexpress-stats-change">' . $change_icon . ' ' . abs($amount_change) . '%</span>';
            $html .= '<span class="flexpress-stats-change-label">' . esc_html__('vs Previous Period', 'flexpress') . '</span>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Format registrations stats HTML
     *
     * @param array $stats Stats array
     * @return string HTML
     */
    private function format_registrations_html($stats)
    {
        $total_count = number_format($stats['total_count']);

        $html = '<div class="flexpress-stats-primary">';
        $html .= '<span class="flexpress-stats-value">' . esc_html($total_count) . '</span>';
        $html .= '<span class="flexpress-stats-label">' . esc_html__('Total Registrations', 'flexpress') . '</span>';
        $html .= '</div>';

        $html .= '<div class="flexpress-stats-secondary">';
        $html .= '<div class="flexpress-stats-metric">';
        $html .= '<span class="flexpress-stats-metric-value">' . esc_html(number_format($stats['trial_registrations'])) . '</span>';
        $html .= '<span class="flexpress-stats-metric-label">' . esc_html__('Free Trials', 'flexpress') . '</span>';
        $html .= '</div>';
        $html .= '<div class="flexpress-stats-metric">';
        $html .= '<span class="flexpress-stats-metric-value">' . esc_html(number_format($stats['paid_registrations'])) . '</span>';
        $html .= '<span class="flexpress-stats-metric-label">' . esc_html__('Paid Signups', 'flexpress') . '</span>';
        $html .= '</div>';

        if (!empty($stats['sources'])) {
            $html .= '<div class="flexpress-stats-breakdown">';
            foreach ($stats['sources'] as $source => $count) {
                $html .= '<div>' . esc_html(ucfirst($source)) . ': ' . number_format($count) . '</div>';
            }
            $html .= '</div>';
        }

        // Show previous period comparison if available
        if (!empty($stats['previous_comparison'])) {
            $comparison = $stats['previous_comparison'];
            $count_change = $comparison['count_change'];
            $change_class = $count_change >= 0 ? 'positive' : 'negative';
            $change_icon = $count_change >= 0 ? '↑' : '↓';
            $html .= '<div class="flexpress-stats-comparison ' . $change_class . '">';
            $html .= '<span class="flexpress-stats-change">' . $change_icon . ' ' . abs($count_change) . '%</span>';
            $html .= '<span class="flexpress-stats-change-label">' . esc_html__('vs Previous Period', 'flexpress') . '</span>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Format memberships stats HTML
     *
     * @param array $stats Stats array
     * @return string HTML
     */
    private function format_memberships_html($stats)
    {
        $total_members = number_format($stats['total_members']);

        $html = '<div class="flexpress-stats-primary">';
        $html .= '<span class="flexpress-stats-value">' . esc_html($total_members) . '</span>';
        $html .= '<span class="flexpress-stats-label">' . esc_html__('Total Active Members', 'flexpress') . '</span>';
        $html .= '</div>';

        $html .= '<div class="flexpress-stats-secondary">';
        $html .= '<div class="flexpress-stats-metric">';
        $html .= '<span class="flexpress-stats-metric-value">' . esc_html(number_format($stats['paid_members'])) . '</span>';
        $html .= '<span class="flexpress-stats-metric-label">' . esc_html__('Paid Members', 'flexpress') . '</span>';
        $html .= '</div>';
        $html .= '<div class="flexpress-stats-metric">';
        $html .= '<span class="flexpress-stats-metric-value">' . esc_html(number_format($stats['trial_members'])) . '</span>';
        $html .= '<span class="flexpress-stats-metric-label">' . esc_html__('Trial Members', 'flexpress') . '</span>';
        $html .= '</div>';

        $html .= '<div class="flexpress-stats-breakdown">';
        $html .= '<div>' . esc_html__('Active:', 'flexpress') . ' ' . number_format($stats['active_members']) . '</div>';
        if (!empty($stats['cancelled_but_active'])) {
            $html .= '<div>' . esc_html__('Cancelled (still active):', 'flexpress') . ' ' . number_format($stats['cancelled_but_active']) . '</div>';
        }
        if (!empty($stats['expired_members'])) {
            $html .= '<div>' . esc_html__('Expired:', 'flexpress') . ' ' . number_format($stats['expired_members']) . '</div>';
        }
        if (!empty($stats['started_in_period'])) {
            $html .= '<div>' . esc_html__('Started in Period:', 'flexpress') . ' ' . number_format($stats['started_in_period']) . '</div>';
        }
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }
}

