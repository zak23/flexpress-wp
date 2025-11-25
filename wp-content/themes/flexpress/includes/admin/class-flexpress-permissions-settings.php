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
            // Allow administrators to remove themselves, but prevent regular founders from doing so
            if ($current_user_id && !in_array($current_user_id, $submitted, true)) {
                if (!user_can($current_user_id, 'manage_options')) {
                    add_settings_error(
                        'flexpress_permissions',
                        'cannot_remove_self',
                        __('You cannot remove yourself from the founders list. Ask another founder or administrator to make that change.', 'flexpress')
                    );
                    return;
                }
                // Administrator removing themselves - check if at least one administrator remains
                $remaining_admins = get_users(
                    array(
                        'role'   => 'administrator',
                        'fields' => 'ID',
                        'exclude' => array($current_user_id),
                    )
                );
                if (empty($remaining_admins)) {
                    add_settings_error(
                        'flexpress_permissions',
                        'cannot_remove_last_admin',
                        __('You cannot remove yourself as you are the only administrator. At least one administrator must remain as a founder.', 'flexpress')
                    );
                    return;
                }
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
            $read_access = isset($_POST['feature_read']) ? (array) $_POST['feature_read'] : array();
            $write_access = isset($_POST['feature_write']) ? (array) $_POST['feature_write'] : array();
            
            $read_access = array_map('sanitize_text_field', $read_access);
            $write_access = array_map('sanitize_text_field', $write_access);

            // Build permissions array
            $restricted = flexpress_get_restricted_features();
            $permissions = array();
            
            foreach ($restricted as $feature_slug) {
                $permissions[$feature_slug] = array(
                    'read'  => in_array($feature_slug, $read_access, true),
                    'write' => in_array($feature_slug, $write_access, true),
                );
                // Write access implies read access
                if ($permissions[$feature_slug]['write']) {
                    $permissions[$feature_slug]['read'] = true;
                }
            }

            flexpress_set_founder_feature_permissions($permissions);

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
                <?php esc_html_e('Control which features founders (non-administrators) can access. Administrators always have full read and write access to all features.', 'flexpress'); ?>
                <br>
                <strong><?php esc_html_e('Read:', 'flexpress'); ?></strong> <?php esc_html_e('Can view settings pages and data, but cannot save changes.', 'flexpress'); ?>
                <br>
                <strong><?php esc_html_e('Write:', 'flexpress'); ?></strong> <?php esc_html_e('Can view and edit settings. Write access automatically includes read access.', 'flexpress'); ?>
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
            $permissions = flexpress_get_founder_feature_permissions();
            ?>

            <form method="post" action="">
                <?php wp_nonce_field('flexpress_permissions_save', 'flexpress_permissions_nonce'); ?>
                <input type="hidden" name="flexpress_permissions_action" value="update_feature_access" />

                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col" class="manage-column">
                                <?php esc_html_e('Feature', 'flexpress'); ?>
                            </th>
                            <th scope="col" class="manage-column" style="width: 150px; text-align: center;">
                                <?php esc_html_e('Read', 'flexpress'); ?>
                                <br>
                                <button type="button" class="button button-small" id="flexpress-check-all-read" style="margin-top: 5px;">
                                    <?php esc_html_e('Check All', 'flexpress'); ?>
                                </button>
                            </th>
                            <th scope="col" class="manage-column" style="width: 150px; text-align: center;">
                                <?php esc_html_e('Write', 'flexpress'); ?>
                                <br>
                                <button type="button" class="button button-small" id="flexpress-check-all-write" style="margin-top: 5px;">
                                    <?php esc_html_e('Check None', 'flexpress'); ?>
                                </button>
                            </th>
                            <th scope="col" class="manage-column">
                                <?php esc_html_e('Description', 'flexpress'); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($restricted_features as $feature_slug) : ?>
                            <?php
                            $label      = isset($feature_labels[$feature_slug]) ? $feature_labels[$feature_slug] : ucwords(str_replace('_', ' ', $feature_slug));
                            $has_read   = isset($permissions[$feature_slug]['read']) && $permissions[$feature_slug]['read'];
                            $has_write  = isset($permissions[$feature_slug]['write']) && $permissions[$feature_slug]['write'];
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
                                <td>
                                    <strong><?php echo esc_html($label); ?></strong>
                                </td>
                                <td style="text-align: center;">
                                    <input type="checkbox" 
                                           name="feature_read[]" 
                                           value="<?php echo esc_attr($feature_slug); ?>" 
                                           id="feature_read_<?php echo esc_attr($feature_slug); ?>" 
                                           <?php checked($has_read); ?>
                                           class="feature-read-checkbox" />
                                </td>
                                <td style="text-align: center;">
                                    <input type="checkbox" 
                                           name="feature_write[]" 
                                           value="<?php echo esc_attr($feature_slug); ?>" 
                                           id="feature_write_<?php echo esc_attr($feature_slug); ?>" 
                                           <?php checked($has_write); ?>
                                           class="feature-write-checkbox"
                                           data-feature="<?php echo esc_attr($feature_slug); ?>" />
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
                // Wait for DOM to be ready
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', init);
                } else {
                    init();
                }

                function init() {
                    const selectAllFounders = document.getElementById('flexpress-select-all-founders');
                    if (selectAllFounders) {
                        selectAllFounders.addEventListener('change', function (event) {
                            const checkboxes = document.querySelectorAll('input[name="founder_ids[]"]');
                            checkboxes.forEach(function (checkbox) {
                                checkbox.checked = event.target.checked;
                            });
                        });
                    }

                    // Auto-check read when write is checked
                    const writeCheckboxes = document.querySelectorAll('.feature-write-checkbox');
                    writeCheckboxes.forEach(function (writeCheckbox) {
                        writeCheckbox.addEventListener('change', function (event) {
                            if (event.target.checked) {
                                const featureSlug = event.target.getAttribute('data-feature');
                                const readCheckbox = document.getElementById('feature_read_' + featureSlug);
                                if (readCheckbox) {
                                    readCheckbox.checked = true;
                                }
                            }
                        });
                    });

                    // Warn if unchecking read when write is checked
                    const readCheckboxes = document.querySelectorAll('.feature-read-checkbox');
                    readCheckboxes.forEach(function (readCheckbox) {
                        readCheckbox.addEventListener('change', function (event) {
                            if (!event.target.checked) {
                                const featureSlug = readCheckbox.id.replace('feature_read_', '');
                                const writeCheckbox = document.getElementById('feature_write_' + featureSlug);
                                if (writeCheckbox && writeCheckbox.checked) {
                                    const message = '<?php echo esc_js(__('Write access requires read access. Unchecking read will also uncheck write. Continue?', 'flexpress')); ?>';
                                    if (confirm(message)) {
                                        writeCheckbox.checked = false;
                                    } else {
                                        event.target.checked = true;
                                    }
                                }
                            }
                        });
                    });

                    // Check All Read button - always checks all read checkboxes
                    const checkAllReadBtn = document.getElementById('flexpress-check-all-read');
                    if (checkAllReadBtn) {
                        checkAllReadBtn.addEventListener('click', function (e) {
                            e.preventDefault();
                            e.stopPropagation();
                            const readBoxes = document.querySelectorAll('.feature-read-checkbox');
                            readBoxes.forEach(function (checkbox) {
                                checkbox.checked = true;
                            });
                            return false;
                        });
                    }

                    // Check None Write button - always unchecks all write checkboxes
                    const checkAllWriteBtn = document.getElementById('flexpress-check-all-write');
                    if (checkAllWriteBtn) {
                        checkAllWriteBtn.addEventListener('click', function (e) {
                            e.preventDefault();
                            e.stopPropagation();
                            const writeBoxes = document.querySelectorAll('.feature-write-checkbox');
                            writeBoxes.forEach(function (writeCheckbox) {
                                writeCheckbox.checked = false;
                            });
                            return false;
                        });
                    }
                }
            })();
        </script>
        <?php
    }
}

if (is_admin()) {
    new FlexPress_Permissions_Settings();
}


