<?php

if (!defined('ABSPATH')) {
    exit;
}

class FlexPress_Permissions_Settings
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'register_menu'), 20);
        add_action('admin_init', array($this, 'handle_submission'));
    }

    public function register_menu()
    {
        add_submenu_page(
            'flexpress-settings',
            __('Permissions', 'flexpress'),
            __('Permissions', 'flexpress'),
            flexpress_get_founder_capability(),
            'flexpress-permissions-settings',
            array($this, 'render_page')
        );
    }

    public function handle_submission()
    {
        if (!isset($_POST['flexpress_permissions_action'])) {
            return;
        }

        check_admin_referer('flexpress_permissions_save', 'flexpress_permissions_nonce');

        if (!flexpress_current_user_is_founder()) {
            flexpress_require_founder_capability();
        }

        // Handle founder assignments
        if ($_POST['flexpress_permissions_action'] === 'update_founders') {
            $submitted = isset($_POST['founder_ids']) ? (array) $_POST['founder_ids'] : array();
            $submitted = array_map('absint', $submitted);
            $submitted = array_filter($submitted);

            if (empty($submitted)) {
                add_settings_error(
                    'flexpress_permissions',
                    'founder_required',
                    __('At least one founder must remain assigned.', 'flexpress')
                );
                return;
            }

            $current_user_id = get_current_user_id();
            if ($current_user_id && !in_array($current_user_id, $submitted, true)) {
                add_settings_error(
                    'flexpress_permissions',
                    'cannot_remove_self',
                    __('You cannot remove yourself from the founders list. Ask another founder to make that change.', 'flexpress')
                );
                return;
            }

            flexpress_set_founder_user_ids($submitted);

            add_settings_error(
                'flexpress_permissions',
                'founder_saved',
                __('Founder permissions updated successfully.', 'flexpress'),
                'updated'
            );
        }

        // Handle feature access settings
        if ($_POST['flexpress_permissions_action'] === 'update_feature_access') {
            $submitted = isset($_POST['feature_access']) ? (array) $_POST['feature_access'] : array();
            $submitted = array_map('sanitize_text_field', $submitted);

            flexpress_set_founder_feature_access($submitted);

            add_settings_error(
                'flexpress_permissions',
                'feature_access_saved',
                __('Feature access permissions updated successfully.', 'flexpress'),
                'updated'
            );
        }
    }

    public function render_page()
    {
        flexpress_require_founder_capability();

        $current_founders = flexpress_get_founder_user_ids();
        $users            = get_users(
            array(
                'orderby' => 'display_name',
                'order'   => 'ASC',
                'fields'  => array('ID', 'display_name', 'user_email', 'roles'),
            )
        );

        settings_errors('flexpress_permissions');
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('FlexPress Permissions', 'flexpress'); ?></h1>
            <p class="description">
                <?php esc_html_e('Founders retain full control over site settings, memberships, and content unlocks. Use this screen to manage which accounts are treated as founders.', 'flexpress'); ?>
            </p>

            <form method="post" action="">
                <?php wp_nonce_field('flexpress_permissions_save', 'flexpress_permissions_nonce'); ?>
                <input type="hidden" name="flexpress_permissions_action" value="update_founders" />

                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col" class="manage-column column-cb check-column">
                                <input type="checkbox" id="flexpress-select-all-founders" />
                            </th>
                            <th scope="col" class="manage-column">
                                <?php esc_html_e('User', 'flexpress'); ?>
                            </th>
                            <th scope="col" class="manage-column">
                                <?php esc_html_e('Email', 'flexpress'); ?>
                            </th>
                            <th scope="col" class="manage-column">
                                <?php esc_html_e('Roles', 'flexpress'); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)) : ?>
                            <tr>
                                <td colspan="4">
                                    <?php esc_html_e('No users found.', 'flexpress'); ?>
                                </td>
                            </tr>
                        <?php else : ?>
                            <?php
                            foreach ($users as $user) :
                                $is_founder = in_array((int) $user->ID, $current_founders, true);

                                $role_labels = array();
                                $roles       = wp_roles();

                                if ($roles instanceof WP_Roles) {
                                    foreach ((array) $user->roles as $role_key) {
                                        $label = $roles->roles[$role_key]['name'] ?? $role_key;
                                        $role_labels[] = translate_user_role($label);
                                    }
                                } else {
                                    $role_labels = (array) $user->roles;
                                }
                                ?>
                                <tr>
                                    <th scope="row" class="check-column">
                                        <input type="checkbox" name="founder_ids[]" value="<?php echo esc_attr($user->ID); ?>" <?php checked($is_founder); ?> />
                                    </th>
                                    <td>
                                        <strong><?php echo esc_html($user->display_name ?: $user->user_email); ?></strong>
                                    </td>
                                    <td><?php echo esc_html($user->user_email); ?></td>
                                    <td>
                                        <?php echo esc_html(implode(', ', $role_labels)); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e('Save Founder Assignments', 'flexpress'); ?>
                    </button>
                </p>
            </form>

            <hr style="margin: 30px 0;" />

            <h2><?php esc_html_e('Feature Access Control', 'flexpress'); ?></h2>
            <p class="description">
                <?php esc_html_e('Control which features founders can access. Administrators always have full access to all features.', 'flexpress'); ?>
            </p>

            <?php
            $restricted_features = flexpress_get_restricted_features();
            $feature_labels      = array(
                'pages_menus'         => __('Pages & Menus', 'flexpress'),
                'auto_setup'          => __('Auto Setup', 'flexpress'),
                'discord'             => __('Discord', 'flexpress'),
                'turnstile'           => __('Turnstile', 'flexpress'),
                'plunk'               => __('Plunk', 'flexpress'),
                'google_smtp'         => __('Google SMTP', 'flexpress'),
                'smtp2go'             => __('SMTP2GO', 'flexpress'),
                'flowguard'           => __('Flowguard', 'flexpress'),
                'dashboard'           => __('Dashboard', 'flexpress'),
                'contact_social'      => __('Contact & Social', 'flexpress'),
                'pricing_plans'        => __('Pricing Plans', 'flexpress'),
                'amazon_ses'          => __('Amazon SES', 'flexpress'),
                'bunny_stream'        => __('Bunny Stream Settings', 'flexpress'),
                'email_blacklist'      => __('Email Blacklist', 'flexpress'),
                'trial_links'         => __('Trial Links', 'flexpress'),
                'affiliate_system'     => __('Affiliate System', 'flexpress'),
                'flowguard_references' => __('Flowguard References', 'flexpress'),
                'manage_members'       => __('Manage Members', 'flexpress'),
                'tools'                => __('Tools', 'flexpress'),
                'earnings'             => __('Earnings', 'flexpress'),
            );
            $allowed_features = flexpress_get_founder_feature_access();
            ?>

            <form method="post" action="">
                <?php wp_nonce_field('flexpress_permissions_save', 'flexpress_permissions_nonce'); ?>
                <input type="hidden" name="flexpress_permissions_action" value="update_feature_access" />

                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col" class="manage-column column-cb check-column">
                                <input type="checkbox" id="flexpress-select-all-features" />
                            </th>
                            <th scope="col" class="manage-column">
                                <?php esc_html_e('Feature', 'flexpress'); ?>
                            </th>
                            <th scope="col" class="manage-column">
                                <?php esc_html_e('Description', 'flexpress'); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($restricted_features as $feature_slug) : ?>
                            <?php
                            $is_allowed = in_array($feature_slug, $allowed_features, true);
                            $label      = isset($feature_labels[$feature_slug]) ? $feature_labels[$feature_slug] : ucwords(str_replace('_', ' ', $feature_slug));
                            $descriptions = array(
                                'pages_menus'         => __('Create and manage pages and navigation menus', 'flexpress'),
                                'auto_setup'          => __('Run automatic site setup and configuration', 'flexpress'),
                                'discord'             => __('Configure Discord webhook notifications', 'flexpress'),
                                'turnstile'           => __('Configure Cloudflare Turnstile settings', 'flexpress'),
                                'plunk'               => __('Configure Plunk email marketing integration', 'flexpress'),
                                'google_smtp'         => __('Configure Google SMTP email settings', 'flexpress'),
                                'smtp2go'             => __('Configure SMTP2GO email settings', 'flexpress'),
                                'flowguard'           => __('Configure Flowguard payment integration', 'flexpress'),
                                'dashboard'           => __('View site statistics and dashboard', 'flexpress'),
                                'contact_social'      => __('Manage contact information and social media links', 'flexpress'),
                                'pricing_plans'       => __('Create and manage subscription pricing plans', 'flexpress'),
                                'amazon_ses'          => __('Configure Amazon SES email delivery', 'flexpress'),
                                'bunny_stream'        => __('Configure Bunny Stream video settings', 'flexpress'),
                                'email_blacklist'      => __('Manage email blacklist for newsletter', 'flexpress'),
                                'trial_links'         => __('Create and manage trial link codes', 'flexpress'),
                                'affiliate_system'    => __('Manage affiliate system and payouts', 'flexpress'),
                                'flowguard_references' => __('Manage Flowguard reference IDs and transactions', 'flexpress'),
                                'manage_members'      => __('Manage user memberships and subscriptions', 'flexpress'),
                                'tools'               => __('Access administrative tools and utilities', 'flexpress'),
                                'earnings'            => __('View and manage earnings and transaction data', 'flexpress'),
                            );
                            $description = isset($descriptions[$feature_slug]) ? $descriptions[$feature_slug] : '';
                            ?>
                            <tr>
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="feature_access[]" value="<?php echo esc_attr($feature_slug); ?>" id="feature_<?php echo esc_attr($feature_slug); ?>" <?php checked($is_allowed); ?> />
                                </th>
                                <td>
                                    <label for="feature_<?php echo esc_attr($feature_slug); ?>">
                                        <strong><?php echo esc_html($label); ?></strong>
                                    </label>
                                </td>
                                <td>
                                    <?php echo esc_html($description); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e('Save Feature Access', 'flexpress'); ?>
                    </button>
                </p>
            </form>
        </div>

        <script>
            (function () {
                const selectAllFounders = document.getElementById('flexpress-select-all-founders');
                if (selectAllFounders) {
                    selectAllFounders.addEventListener('change', function (event) {
                        const checkboxes = document.querySelectorAll('input[name="founder_ids[]"]');
                        checkboxes.forEach(function (checkbox) {
                            checkbox.checked = event.target.checked;
                        });
                    });
                }

                const selectAllFeatures = document.getElementById('flexpress-select-all-features');
                if (selectAllFeatures) {
                    selectAllFeatures.addEventListener('change', function (event) {
                        const checkboxes = document.querySelectorAll('input[name="feature_access[]"]');
                        checkboxes.forEach(function (checkbox) {
                            checkbox.checked = event.target.checked;
                        });
                    });
                }
            })();
        </script>
        <?php
    }
}

if (is_admin()) {
    new FlexPress_Permissions_Settings();
}


