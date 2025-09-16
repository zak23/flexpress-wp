<?php
/**
 * Custom stubs for PHPStan analysis
 * 
 * This file contains stubs for functions and classes that PHPStan
 * might not understand from WordPress core or plugins.
 */

// WordPress core functions that might be missing from stubs
if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script($handle, $src = '', $deps = array(), $ver = false, $in_footer = false) {}
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style($handle, $src = '', $deps = array(), $ver = false, $media = 'all') {}
}

if (!function_exists('get_template_directory_uri')) {
    function get_template_directory_uri() { return ''; }
}

if (!function_exists('get_template_directory')) {
    function get_template_directory() { return ''; }
}

if (!function_exists('get_the_title')) {
    function get_the_title($post = 0) { return ''; }
}

if (!function_exists('add_action')) {
    function add_action($hook_name, $callback, $priority = 10, $accepted_args = 1) {}
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) { return $str; }
}

if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($str) { return $str; }
}

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data, $options = 0, $depth = 512) { return json_encode($data, $options, $depth); }
}

if (!function_exists('current_time')) {
    function current_time($type, $gmt = 0) { return date('Y-m-d H:i:s'); }
}

if (!function_exists('wp_parse_args')) {
    function wp_parse_args($args, $defaults = array()) { return array_merge($defaults, $args); }
}

if (!function_exists('sanitize_sql_orderby')) {
    function sanitize_sql_orderby($orderby) { return $orderby; }
}

if (!function_exists('dbDelta')) {
    function dbDelta($queries) {}
}

// ACF functions (if using Advanced Custom Fields)
if (!function_exists('get_field')) {
    function get_field($selector, $post_id = false, $format_value = true) { return null; }
}

if (!function_exists('the_field')) {
    function the_field($selector, $post_id = false, $format_value = true) {}
}

if (!function_exists('have_rows')) {
    function have_rows($selector, $post_id = false) { return false; }
}

if (!function_exists('the_row')) {
    function the_row() {}
}

if (!function_exists('get_sub_field')) {
    function get_sub_field($selector, $format_value = true) { return null; }
}

// Custom theme functions (add your own here as needed)
// Example:
// if (!function_exists('flexpress_custom_function')) {
//     function flexpress_custom_function($param) { return $param; }
// } 