<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('FLEXPRESS_FOUNDER_CAPABILITY')) {
    define('FLEXPRESS_FOUNDER_CAPABILITY', 'manage_flexpress_founder_settings');
}

/**
 * Get the capability string used to identify FlexPress founders.
 *
 * @return string
 */
function flexpress_get_founder_capability()
{
    return FLEXPRESS_FOUNDER_CAPABILITY;
}

/**
 * Ensure the founder option exists and is populated with administrators the first time.
 * This prevents accidental lock-out when the feature is introduced.
 */
function flexpress_bootstrap_founder_option()
{
    $stored = get_option('flexpress_founder_user_ids', null);
    if ($stored !== null) {
        return;
    }

    $administrators = get_users(
        array(
            'role'   => 'administrator',
            'fields' => 'ID',
        )
    );

    $admin_ids = array_map('absint', $administrators);
    update_option('flexpress_founder_user_ids', $admin_ids, false);
}
add_action('after_setup_theme', 'flexpress_bootstrap_founder_option', 5);

/**
 * Retrieve the list of user IDs flagged as founders.
 *
 * @return int[]
 */
function flexpress_get_founder_user_ids()
{
    $ids = get_option('flexpress_founder_user_ids', array());
    if (!is_array($ids)) {
        return array();
    }

    $ids = array_map('absint', array_unique($ids));

    return array_values(array_filter($ids));
}

/**
 * Persist a sanitized list of founder user IDs and sync capabilities.
 *
 * @param int[] $user_ids
 */
function flexpress_set_founder_user_ids($user_ids)
{
    if (!is_array($user_ids)) {
        $user_ids = array();
    }

    $user_ids = array_map('absint', array_unique($user_ids));

    update_option('flexpress_founder_user_ids', array_values(array_filter($user_ids)), false);

    // After updating the option, ensure capabilities are in sync
    flexpress_rebuild_founder_capabilities();
}

/**
 * Assign the founder capability to a specific user if required.
 *
 * @param int $user_id
 */
function flexpress_grant_founder_capability($user_id)
{
    $user = $user_id ? get_user_by('ID', $user_id) : false;
    if (!$user instanceof WP_User) {
        return;
    }

    $capability = flexpress_get_founder_capability();

    if (!$user->has_cap($capability)) {
        $user->add_cap($capability);
    }
}

/**
 * Remove the founder capability from a user if present.
 *
 * @param int $user_id
 */
function flexpress_revoke_founder_capability($user_id)
{
    $user = $user_id ? get_user_by('ID', $user_id) : false;
    if (!$user instanceof WP_User) {
        return;
    }

    $capability = flexpress_get_founder_capability();

    if ($user->has_cap($capability)) {
        $user->remove_cap($capability);
    }
}

/**
 * Sync founder capability assignments on each request.
 * Also grants capability to all administrators so they can always access FlexPress.
 */
function flexpress_sync_founder_capabilities()
{
    $capability = flexpress_get_founder_capability();
    $founders   = flexpress_get_founder_user_ids();

    // Grant capability to all administrators (they should always have access)
    $administrators = get_users(
        array(
            'role'   => 'administrator',
            'fields' => 'ID',
        )
    );

    foreach ($administrators as $user_id) {
        flexpress_grant_founder_capability($user_id);
    }

    // Also grant to listed founders
    if (!empty($founders)) {
        foreach ($founders as $user_id) {
            flexpress_grant_founder_capability($user_id);
        }
    }
}
add_action('init', 'flexpress_sync_founder_capabilities', 12);

/**
 * Revoke founder caps from users no longer listed and assign to the current list.
 */
function flexpress_rebuild_founder_capabilities()
{
    $capability = flexpress_get_founder_capability();
    $current    = flexpress_get_founder_user_ids();

    // Remove capability from users who no longer qualify
    $users_with_cap = get_users(
        array(
            'capability' => $capability,
            'fields'     => 'ID',
        )
    );

    foreach ($users_with_cap as $user_id) {
        if (!in_array((int) $user_id, $current, true)) {
            flexpress_revoke_founder_capability((int) $user_id);
        }
    }

    // Ensure current founders have the capability
    foreach ($current as $user_id) {
        flexpress_grant_founder_capability((int) $user_id);
    }
}

/**
 * Check whether the provided (or current) user is a FlexPress founder.
 *
 * @param int|null $user_id
 * @return bool
 */
function flexpress_user_is_founder($user_id = null)
{
    if ($user_id === null) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return false;
    }

    $capability = flexpress_get_founder_capability();
    if (user_can($user_id, $capability)) {
        return true;
    }

    $founders = flexpress_get_founder_user_ids();
    if (empty($founders)) {
        return user_can($user_id, 'manage_options');
    }

    return in_array((int) $user_id, $founders, true);
}

/**
 * Evaluate whether a mixed user reference should be treated as founder.
 *
 * @param mixed $user
 * @return bool
 */
function flexpress_is_founder_entity($user)
{
    if ($user instanceof WP_User) {
        return flexpress_user_is_founder($user->ID);
    }

    if (is_numeric($user)) {
        return flexpress_user_is_founder((int) $user);
    }

    return false;
}

/**
 * Convenience wrapper for current user founder check.
 *
 * @return bool
 */
function flexpress_current_user_is_founder()
{
    return flexpress_user_is_founder(get_current_user_id());
}

/**
 * Ensure the current user has founder access or trigger wp_die.
 */
function flexpress_require_founder_capability()
{
    if (flexpress_current_user_is_founder()) {
        return;
    }

    wp_die(
        esc_html__('You do not have permission to access this resource.', 'flexpress'),
        esc_html__('Permission Denied', 'flexpress'),
        array('response' => 403)
    );
}

/**
 * Get the list of restricted features that require explicit founder access.
 *
 * @return array Array of feature slugs
 */
function flexpress_get_restricted_features()
{
    return array(
        'pages_menus',
        'auto_setup',
        'discord',
        'turnstile',
        'plunk',
        'google_smtp',
        'smtp2go',
        'flowguard',
        'dashboard',
        'contact_social',
        'pricing_plans',
        'amazon_ses',
        'bunny_stream',
        'email_blacklist',
        'trial_links',
        'affiliate_system',
        'flowguard_references',
        'manage_members',
        'tools',
        'earnings',
    );
}

/**
 * Get the list of features that founders can access.
 *
 * @return array Array of feature slugs that founders can access
 */
function flexpress_get_founder_feature_access()
{
    $access = get_option('flexpress_founder_feature_access', array());
    if (!is_array($access)) {
        return array();
    }

    return array_values(array_filter($access));
}

/**
 * Set the list of features that founders can access.
 *
 * @param array $features Array of feature slugs
 */
function flexpress_set_founder_feature_access($features)
{
    if (!is_array($features)) {
        $features = array();
    }

    $restricted = flexpress_get_restricted_features();
    $features    = array_intersect($features, $restricted);
    $features    = array_unique($features);

    update_option('flexpress_founder_feature_access', array_values($features), false);
}

/**
 * Check if a user can access a specific feature.
 * Administrators always have access. Founders need explicit permission.
 *
 * @param string $feature_slug Feature slug to check
 * @param int|null $user_id User ID to check (null for current user)
 * @return bool
 */
function flexpress_user_can_access_feature($feature_slug, $user_id = null)
{
    if ($user_id === null) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return false;
    }

    // Administrators always have access
    if (user_can($user_id, 'manage_options')) {
        return true;
    }

    // Check if feature is restricted
    $restricted = flexpress_get_restricted_features();
    if (!in_array($feature_slug, $restricted, true)) {
        // Not a restricted feature, founders have default access
        return flexpress_user_is_founder($user_id);
    }

    // Restricted feature - check if founder has explicit access
    if (!flexpress_user_is_founder($user_id)) {
        return false;
    }

    $allowed_features = flexpress_get_founder_feature_access();
    return in_array($feature_slug, $allowed_features, true);
}

/**
 * Get feature capability for menu registration.
 * Returns a capability that checks both admin and founder feature access.
 *
 * @param string $feature_slug Feature slug
 * @return string Capability name
 */
function flexpress_get_feature_capability($feature_slug)
{
    // Use a custom capability that we'll check dynamically
    return 'flexpress_access_' . $feature_slug;
}

/**
 * Check if current user has access to a feature (for capability checks).
 * This is used as a capability callback for menu registration.
 *
 * @param string $feature_slug Feature slug
 * @return bool
 */
function flexpress_check_feature_access($feature_slug)
{
    return flexpress_user_can_access_feature($feature_slug);
}

/**
 * Filter user capabilities to handle feature access dynamically.
 * This allows us to use custom capabilities in menu registration.
 *
 * @param array $allcaps All capabilities for the user
 * @param array $caps Required capabilities
 * @param array $args Additional arguments
 * @return array
 */
function flexpress_filter_feature_capabilities($allcaps, $caps, $args)
{
    // Get user ID from args if provided
    $user_id = isset($args[0]) ? (int) $args[0] : get_current_user_id();

    // Administrators always have founder capability (can always see FlexPress menu)
    $founder_cap = flexpress_get_founder_capability();
    if (in_array($founder_cap, $caps, true) && user_can($user_id, 'manage_options')) {
        $allcaps[$founder_cap] = true;
    }

    // Check if this is a feature capability check
    foreach ($caps as $cap) {
        if (strpos($cap, 'flexpress_access_') === 0) {
            $feature_slug = str_replace('flexpress_access_', '', $cap);
            if (flexpress_user_can_access_feature($feature_slug, $user_id)) {
                $allcaps[$cap] = true;
            }
        }
    }

    return $allcaps;
}
add_filter('user_has_cap', 'flexpress_filter_feature_capabilities', 10, 3);


