/**
 * FlexPress Admin Stats Dashboard JavaScript
 *
 * Handles AJAX calls, dynamic updates, and time range filtering
 */

(function ($) {
    'use strict';

    // Initialize on document ready
    $(document).ready(function () {
        initStatsDashboard();
    });

    /**
     * Initialize stats dashboard
     */
    function initStatsDashboard() {
        const context = typeof flexpressStats !== 'undefined' ? flexpressStats.context : 'wordpress_dashboard';
        
        // Initialize date pickers if jQuery UI datepicker is available
        if ($.fn.datepicker) {
            $('.flexpress-date-picker').datepicker({
                dateFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true
            });
        }

        // Handle time range changes
        $('.flexpress-stats-time-range, .flexpress-stats-time-range-small').on('change', function () {
            const timeRange = $(this).val();
            handleTimeRangeChange(timeRange, context);
        });

        // Show/hide custom date range
        $('#flexpress-stats-time-range').on('change', function () {
            const timeRange = $(this).val();
            if (timeRange === 'custom') {
                $('#flexpress-stats-custom-range').slideDown();
            } else {
                $('#flexpress-stats-custom-range').slideUp();
            }
        });

        // Handle custom date changes
        $('#flexpress-stats-date-from, #flexpress-stats-date-to').on('change', function () {
            const timeRange = $('#flexpress-stats-time-range').val();
            if (timeRange === 'custom') {
                handleTimeRangeChange('custom', context);
            }
        });

        // Load initial stats with default or selected time range
        const defaultTimeRange = $('.flexpress-stats-time-range, .flexpress-stats-time-range-small').first().val() || 'this_month';
        loadAllStats(context, defaultTimeRange);
    }

    /**
     * Handle time range change
     *
     * @param {string} timeRange Selected time range
     * @param {string} context Dashboard context
     */
    function handleTimeRangeChange(timeRange, context) {
        // Update all time range selectors to match
        $('.flexpress-stats-time-range, .flexpress-stats-time-range-small').val(timeRange);

        // Get custom dates if applicable
        let customFrom = '';
        let customTo = '';
        if (timeRange === 'custom') {
            customFrom = $('#flexpress-stats-date-from').val();
            customTo = $('#flexpress-stats-date-to').val();

            if (!customFrom || !customTo) {
                // Don't load if dates are missing
                return;
            }
        }

        // Load stats for all widgets
        loadAllStats(context, timeRange, customFrom, customTo);
    }

    /**
     * Load stats for all widgets
     *
     * @param {string} context Dashboard context
     * @param {string} timeRange Time range
     * @param {string} customFrom Custom from date
     * @param {string} customTo Custom to date
     */
    function loadAllStats(context, timeRange = 'this_month', customFrom = '', customTo = '') {
        const statTypes = ['sales', 'trials', 'rebills', 'ratings', 'unlocks', 'registrations', 'memberships'];

        statTypes.forEach(function (type) {
            loadStats(type, timeRange, customFrom, customTo, context);
        });
    }

    /**
     * Load stats for a specific widget
     *
     * @param {string} type Stat type
     * @param {string} timeRange Time range
     * @param {string} customFrom Custom from date
     * @param {string} customTo Custom to date
     * @param {string} context Dashboard context
     */
    function loadStats(type, timeRange, customFrom, customTo, context) {
        // Find the widget container
        const $widget = context === 'flexpress_page'
            ? $('.flexpress-stats-card[data-stat-type="' + type + '"]')
            : $('.flexpress-stats-widget[data-stat-type="' + type + '"]');

        if ($widget.length === 0) {
            return;
        }

        // Find content containers
        const $loading = $widget.find('.flexpress-stats-loading');
        const $data = $widget.find('.flexpress-stats-data');

        // Show loading, hide data
        $loading.show();
        $data.hide();

        // Make AJAX request
        $.ajax({
            url: typeof flexpressStats !== 'undefined' ? flexpressStats.ajaxurl : ajaxurl,
            type: 'POST',
            data: {
                action: 'flexpress_get_stats',
                nonce: typeof flexpressStats !== 'undefined' ? flexpressStats.nonce : '',
                type: type,
                time_range: timeRange,
                custom_from: customFrom,
                custom_to: customTo
            },
            success: function (response) {
                if (response.success && response.data && response.data.html) {
                    $data.html(response.data.html);
                    $loading.hide();
                    $data.fadeIn(300);
                } else {
                    showError($widget, response.data && response.data.message ? response.data.message : 'Failed to load stats');
                }
            },
            error: function (xhr, status, error) {
                console.error('FlexPress Stats Error:', error);
                showError($widget, 'An error occurred while loading stats. Please try again.');
            }
        });
    }

    /**
     * Show error message
     *
     * @param {jQuery} $widget Widget container
     * @param {string} message Error message
     */
    function showError($widget, message) {
        const $loading = $widget.find('.flexpress-stats-loading');
        const $data = $widget.find('.flexpress-stats-data');

        $loading.hide();
        $data.html('<div class="notice notice-error inline"><p>' + message + '</p></div>').show();
    }

    /**
     * Refresh all stats (public function for potential future use)
     */
    window.flexpressRefreshStats = function () {
        const context = typeof flexpressStats !== 'undefined' ? flexpressStats.context : 'wordpress_dashboard';
        const timeRange = $('.flexpress-stats-time-range, .flexpress-stats-time-range-small').first().val() || 'this_month';
        let customFrom = '';
        let customTo = '';

        if (timeRange === 'custom') {
            customFrom = $('#flexpress-stats-date-from').val();
            customTo = $('#flexpress-stats-date-to').val();
        }

        loadAllStats(context, timeRange, customFrom, customTo);
    };

})(jQuery);

