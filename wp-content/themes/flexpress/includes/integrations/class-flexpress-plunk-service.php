<?php
/**
 * FlexPress Plunk Service Facade
 *
 * High-level helper that wraps FlexPress_Plunk_API for identify/track and trait updates.
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit;
}

class FlexPress_Plunk_Service
{
    /**
     * @var FlexPress_Plunk_API
     */
    private $api;

    public function __construct()
    {
        if (!class_exists('FlexPress_Plunk_API')) {
            require_once FLEXPRESS_PATH . '/includes/class-flexpress-plunk-api.php';
        }
        $this->api = new FlexPress_Plunk_API();
    }

    /**
     * Ensure a contact exists for email; create if missing.
     *
     * @param string $email
     * @param array $data
     * @return string|WP_Error Contact ID or error
     */
    public function ensure_contact($email, $data = array())
    {
        if (!$this->api->is_configured()) {
            return new WP_Error('plunk_not_configured', 'Plunk is not configured');
        }
        $email = sanitize_email($email);
        if (empty($email)) {
            return new WP_Error('invalid_email', 'Invalid email');
        }
        $existing = $this->api->get_contact_by_email($email);
        if (!is_wp_error($existing) && isset($existing['id'])) {
            return $existing['id'];
        }
        $payload = array(
            'email' => $email,
            'subscribed' => false,
            'data' => $data
        );
        $created = $this->api->add_contact($payload);
        if (is_wp_error($created) || empty($created['id'])) {
            return new WP_Error('create_failed', 'Failed to create contact');
        }
        return $created['id'];
    }

    /**
     * Update traits (data) for a user by WP user ID.
     *
     * @param int $user_id
     * @param array $traits
     * @return bool
     */
    public function identify_user($user_id, $traits = array())
    {
        if (!$this->api->is_configured()) {
            return false;
        }
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        $contact = $this->api->get_contact_by_email($user->user_email);
        $contact_id = !is_wp_error($contact) && isset($contact['id']) ? $contact['id'] : null;
        if (!$contact_id) {
            $contact_id = $this->ensure_contact($user->user_email, array(
                'userId' => (string)$user_id,
                'name' => $user->display_name
            ));
            if (is_wp_error($contact_id)) {
                return false;
            }
        }
        $data = array(
            'email' => $user->user_email,
            'data' => $traits
        );
        $resp = $this->api->update_contact($contact_id, $data);
        return !is_wp_error($resp);
    }

    /**
     * Track an event for a user.
     *
     * @param int $user_id
     * @param string $event
     * @param array $event_data
     * @return bool
     */
    public function track_user_event($user_id, $event, $event_data = array())
    {
        if (!$this->api->is_configured()) {
            return false;
        }
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        $contact = $this->api->get_contact_by_email($user->user_email);
        if (is_wp_error($contact) || empty($contact['id'])) {
            $contact_id = $this->ensure_contact($user->user_email, array(
                'userId' => (string)$user_id,
                'name' => $user->display_name
            ));
            if (is_wp_error($contact_id)) {
                return false;
            }
        } else {
            $contact_id = $contact['id'];
        }
        $resp = $this->api->track_event($contact_id, $event, $user->user_email, $event_data);
        return !is_wp_error($resp);
    }

    /**
     * Set newsletter status and subscribe/unsubscribe in Plunk.
     *
     * @param string $email
     * @param string $status confirmed|unsubscribed|unconfirmed
     * @return bool
     */
    public function set_newsletter_status($email, $status)
    {
        if (!$this->api->is_configured()) {
            return false;
        }
        $email = sanitize_email($email);
        if (empty($email)) {
            return false;
        }
        $contact = $this->api->get_contact_by_email($email);
        if (is_wp_error($contact) || empty($contact['id'])) {
            $contact_id = $this->ensure_contact($email, array());
            if (is_wp_error($contact_id)) {
                return false;
            }
        } else {
            $contact_id = $contact['id'];
        }
        $ok = true;
        if ($status === 'confirmed') {
            $resp = $this->api->subscribe_contact($contact_id, $email);
            $ok = !is_wp_error($resp);
        } elseif ($status === 'unsubscribed') {
            $resp = $this->api->unsubscribe_contact($contact_id, $email);
            $ok = !is_wp_error($resp);
        }
        // Also persist trait
        $this->api->update_contact($contact_id, array(
            'email' => $email,
            'data' => array('newsletter_status' => $status)
        ));
        return $ok;
    }

    /**
     * Update core membership traits for a user.
     *
     * @param int $user_id
     * @param string $statusLabel Current|Cancelled|Expired|Banned
     * @return void
     */
    public function update_membership_traits($user_id, $statusLabel)
    {
        $subscription_label = function_exists('flexpress_get_user_subscription_type')
            ? flexpress_get_user_subscription_type($user_id)
            : 'none';
        $expires_at = get_user_meta($user_id, 'membership_expires', true);
        $traits = array(
            'status' => $statusLabel,
            'subscription_label' => $subscription_label,
            'membership_expires_at' => $expires_at ? date('c', strtotime($expires_at)) : null
        );
        $this->identify_user($user_id, $traits);
    }
}


