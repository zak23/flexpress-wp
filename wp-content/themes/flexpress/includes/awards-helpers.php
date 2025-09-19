<?php
/**
 * FlexPress Awards Helper Functions
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Check if awards section is enabled
 *
 * @return bool True if enabled
 */
function flexpress_is_awards_section_enabled() {
    $options = get_option('flexpress_general_settings', array());
    return !empty($options['awards_enabled']);
}

/**
 * Get awards section title
 *
 * @return string Awards section title
 */
function flexpress_get_awards_title() {
    $options = get_option('flexpress_general_settings', array());
    return isset($options['awards_title']) ? $options['awards_title'] : 'Awards & Recognition';
}

/**
 * Get awards list
 *
 * @return array Array of awards
 */
function flexpress_get_awards_list() {
    $options = get_option('flexpress_general_settings', array());
    $awards_list = isset($options['awards_list']) ? $options['awards_list'] : array();
    
    // Process awards to get logo URLs
    foreach ($awards_list as &$award) {
        if (!empty($award['logo_id'])) {
            $award['logo_url'] = wp_get_attachment_url($award['logo_id']);
        }
        // Ensure alt text exists
        if (empty($award['alt'])) {
            $award['alt'] = $award['title'] ?? 'Award';
        }
    }
    
    return $awards_list;
}

/**
 * Get awards count
 *
 * @return int Number of awards
 */
function flexpress_get_awards_count() {
    return count(flexpress_get_awards_list());
}

/**
 * Check if awards section should be displayed
 *
 * @return bool True if section should be displayed
 */
function flexpress_should_display_awards_section() {
    return flexpress_is_awards_section_enabled() && flexpress_get_awards_count() > 0;
}

/**
 * Get awards section data
 *
 * @return array Awards section data
 */
function flexpress_get_awards_data() {
    return array(
        'enabled' => flexpress_is_awards_section_enabled(),
        'title' => flexpress_get_awards_title(),
        'awards' => flexpress_get_awards_list(),
        'count' => flexpress_get_awards_count(),
        'should_display' => flexpress_should_display_awards_section()
    );
}
