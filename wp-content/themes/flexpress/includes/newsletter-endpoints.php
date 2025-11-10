<?php
/**
 * Newsletter confirm/unsubscribe endpoints
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add pretty rewrite rules for newsletter endpoints
 */
function flexpress_newsletter_add_rewrite_rules()
{
    add_rewrite_rule(
        '^newsletter/(confirm|unsubscribe)/?$',
        'index.php?flexpress_newsletter=$matches[1]',
        'top'
    );
}
add_action('init', 'flexpress_newsletter_add_rewrite_rules');

/**
 * Register query vars
 *
 * @param array $vars
 * @return array
 */
function flexpress_newsletter_add_query_vars($vars)
{
    $vars[] = 'flexpress_newsletter';
    return $vars;
}
add_filter('query_vars', 'flexpress_newsletter_add_query_vars');

/**
 * Handle newsletter confirm/unsubscribe
 */
function flexpress_newsletter_template_redirect()
{
    $action = get_query_var('flexpress_newsletter');
    if (!$action) {
        return;
    }
    $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';
    if (empty($token)) {
        wp_redirect(home_url('/?newsletter=missing_token'));
        exit;
    }
    $payload = flexpress_verify_newsletter_token($token, 14 * DAY_IN_SECONDS);
    if (is_wp_error($payload)) {
        wp_redirect(home_url('/?newsletter=invalid'));
        exit;
    }
    $email = $payload['email'];
    $service = flexpress_plunk_service();
    if ($action === 'confirm') {
        $service->set_newsletter_status($email, 'confirmed');
        $service->track_user_event(get_user_by('email', $email)->ID ?? 0, 'newsletter_confirmed', array(
            'timestamp' => date('c')
        ));
        wp_redirect(home_url('/?newsletter=confirmed'));
        exit;
    }
    if ($action === 'unsubscribe') {
        $service->set_newsletter_status($email, 'unsubscribed');
        $service->track_user_event(get_user_by('email', $email)->ID ?? 0, 'newsletter_unsubscribed', array(
            'timestamp' => date('c')
        ));
        wp_redirect(home_url('/?newsletter=unsubscribed'));
        exit;
    }
}
add_action('template_redirect', 'flexpress_newsletter_template_redirect', 1);


