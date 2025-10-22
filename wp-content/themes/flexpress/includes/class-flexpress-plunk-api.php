<?php

/**
 * FlexPress Plunk API Integration
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * FlexPress Plunk API Class
 */
class FlexPress_Plunk_API
{
    /**
     * API Key
     *
     * @var string
     */
    private $public_api_key;

    /**
     * Secret API Key
     *
     * @var string
     */
    private $secret_api_key;

    /**
     * Install URL
     *
     * @var string
     */
    private $install_url;

    /**
     * Constructor
     */
    public function __construct()
    {
        $options = get_option('flexpress_plunk_settings', array());
        $this->public_api_key = $options['public_api_key'] ?? '';
        $this->secret_api_key = $options['secret_api_key'] ?? '';
        $this->install_url = $options['install_url'] ?? '';
    }

    /**
     * Check if API is configured
     */
    public function is_configured()
    {
        return !empty($this->public_api_key) && !empty($this->secret_api_key) && !empty($this->install_url);
    }

    /**
     * Make API request
     */
    private function make_request($endpoint, $args = array())
    {
        if (!$this->is_configured()) {
            return new WP_Error('plunk_not_configured', 'Plunk API is not configured');
        }

        $method = isset($args['method']) ? $args['method'] : 'GET';
        $url = rtrim($this->install_url, '/') . '/' . ltrim($endpoint, '/');

        $default_args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->secret_api_key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ),
            'timeout' => 30,
            'sslverify' => true
        );

        $args = wp_parse_args($args, $default_args);

        // LOG: Outbound request (mask secrets)
        $masked_secret = $this->secret_api_key ? substr($this->secret_api_key, 0, 6) . '…' . substr($this->secret_api_key, -4) : '';
        $masked_public = $this->public_api_key ? substr($this->public_api_key, 0, 6) . '…' . substr($this->public_api_key, -4) : '';
        $log_context = array(
            'url' => $url,
            'method' => $method,
            'timeout' => $args['timeout'],
            'has_body' => isset($args['body']) && !empty($args['body']),
            'secret_key' => $masked_secret,
            'public_key' => $masked_public
        );
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[FlexPress][Plunk][Request] ' . wp_json_encode($log_context));
        }

        $start_time = microtime(true);
        $response = wp_remote_request($url, $args);
        $duration_ms = (microtime(true) - $start_time) * 1000.0;

        if (is_wp_error($response)) {
            // Always log errors (not just in debug mode)
            error_log('[FlexPress][Plunk][Response][WP_Error] ' . $response->get_error_message());
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        $status_code = (int) wp_remote_retrieve_response_code($response);
        $logged_body = is_string($body) ? substr($body, 0, 600) : '';
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[FlexPress][Plunk][Response] ' . wp_json_encode(array(
                'status' => $status_code,
                'duration_ms' => round($duration_ms, 1),
                'body_snippet' => $logged_body
            )));
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('plunk_invalid_response', 'Invalid JSON response from Plunk API');
        }

        return $data;
    }

    /**
     * Add new contact
     */
    public function add_contact($data)
    {
        $contact_data = array(
            'email' => $data['email'],
            'subscribed' => $data['subscribed'] ?? false,
            'data' => $data['data'] ?? array()
        );

        return $this->make_request('/api/v1/contacts', array(
            'method' => 'POST',
            'body' => json_encode($contact_data)
        ));
    }

    /**
     * Get contact by email
     */
    public function get_contact_by_email($email)
    {
        $result = $this->make_request('/api/v1/contacts?' . http_build_query(array('email' => $email)), array(
            'method' => 'GET'
        ));

        if (is_wp_error($result) || empty($result)) {
            return new WP_Error('contact_not_found', 'Contact not found');
        }

        foreach ($result as $contact) {
            if ($contact['email'] === $email) {
                return $contact;
            }
        }

        return new WP_Error('contact_not_found', 'Contact not found');
    }

    /**
     * Get contact by ID
     */
    public function get_contact_by_id($contact_id)
    {
        return $this->make_request('/api/v1/contacts/' . $contact_id, array(
            'method' => 'GET'
        ));
    }

    /**
     * Update contact
     */
    public function update_contact($contact_id, $data)
    {
        return $this->make_request('/api/v1/contacts/' . $contact_id, array(
            'method' => 'PUT',
            'body' => json_encode($data)
        ));
    }

    /**
     * Delete contact
     */
    public function delete_contact($contact_id)
    {
        return $this->make_request('/api/v1/contacts/' . $contact_id, array(
            'method' => 'DELETE'
        ));
    }

    /**
     * Subscribe contact
     */
    public function subscribe_contact($contact_id, $email)
    {
        return $this->make_request('/api/v1/contacts/subscribe', array(
            'method' => 'POST',
            'body' => json_encode(array('id' => $contact_id))
        ));
    }

    /**
     * Unsubscribe contact
     */
    public function unsubscribe_contact($contact_id, $email)
    {
        return $this->make_request('/api/v1/contacts/unsubscribe', array(
            'method' => 'POST',
            'body' => json_encode(array('id' => $contact_id))
        ));
    }

    /**
     * Track event
     */
    public function track_event($contact_id, $event_name, $email, $event_data = array())
    {
        $event_payload = array(
            'contactId' => $contact_id,
            'event' => $event_name,
            'email' => $email
        );

        if (!empty($event_data)) {
            $event_payload['data'] = $event_data;
        }

        return $this->make_request('/api/v1/track', array(
            'method' => 'POST',
            'body' => json_encode($event_payload)
        ));
    }

    /**
     * Get all contacts
     */
    public function get_contacts($limit = 100, $offset = 0)
    {
        return $this->make_request('/api/v1/contacts?' . http_build_query(array(
            'limit' => $limit,
            'offset' => $offset
        )), array(
            'method' => 'GET'
        ));
    }

    /**
     * Test API connection
     */
    public function test_connection()
    {
        $result = $this->make_request('/api/v1/contacts?limit=1', array(
            'method' => 'GET'
        ));

        if (is_wp_error($result)) {
            return $result;
        }

        // Success if we received an array/JSON from API
        return true;
    }

    /**
     * Sync existing WordPress users with Plunk
     */
    public function sync_existing_users($limit = 50)
    {
        $users = get_users(array(
            'number' => $limit,
            'meta_query' => array(
                array(
                    'key' => 'plunk_contact_id',
                    'compare' => 'NOT EXISTS'
                )
            )
        ));

        $results = array();

        foreach ($users as $user) {
            $contact_data = array(
                'email' => $user->user_email,
                'subscribed' => true,
                'data' => array(
                    'name' => $user->display_name,
                    'source' => 'Manual Sync',
                    'syncDate' => date('c'),
                    'userType' => 'existing_user',
                    'membershipStatus' => 'unknown'
                )
            );

            $result = $this->add_contact($contact_data);

            if (!is_wp_error($result) && isset($result['id'])) {
                update_user_meta($user->ID, 'plunk_contact_id', $result['id']);
                $results[$user->ID] = array('success' => true, 'contact_id' => $result['id']);
            } else {
                $results[$user->ID] = array('success' => false, 'error' => $result->get_error_message());
            }
        }

        return $results;
    }

    /**
     * Get contact events
     */
    public function get_contact_events($contact_id)
    {
        return $this->make_request('/api/v1/contacts/' . $contact_id . '/events', array(
            'method' => 'GET'
        ));
    }

    /**
     * Bulk update contacts
     */
    public function bulk_update_contacts($updates)
    {
        $results = array();

        foreach ($updates as $update) {
            $result = $this->update_contact($update['contact_id'], $update['data']);
            $results[] = array(
                'contact_id' => $update['contact_id'],
                'success' => !is_wp_error($result),
                'error' => is_wp_error($result) ? $result->get_error_message() : null
            );
        }

        return $results;
    }
}
