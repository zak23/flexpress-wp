<?php
/**
 * Contact Form 7 Discord Integration
 * 
 * Sends Discord notifications when Contact Form 7 forms are submitted.
 * Integrates with FlexPress Discord notification system.
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Contact Form 7 Discord Integration Class
 */
class FlexPress_CF7_Discord_Integration {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Hook into Contact Form 7 mail sent event
        add_action('wpcf7_mail_sent', array($this, 'handle_form_submission'), 10, 1);
        
        // Hook into Contact Form 7 mail failed event
        add_action('wpcf7_mail_failed', array($this, 'handle_form_failure'), 10, 1);
    }
    
    /**
     * Handle successful form submission
     * 
     * @param WPCF7_ContactForm $contact_form The contact form object
     */
    public function handle_form_submission($contact_form) {
        $form_id = $contact_form->id();
        $form_title = $contact_form->title();
        
        // Get form data
        $submission = WPCF7_Submission::get_instance();
        if (!$submission) {
            return;
        }
        
        $posted_data = $submission->get_posted_data();
        
        // Determine form type based on FlexPress form IDs
        $form_type = $this->get_form_type($form_id);
        
        // Send Discord notification
        $this->send_discord_notification($form_type, $form_title, $posted_data);
    }
    
    /**
     * Handle form submission failure
     * 
     * @param WPCF7_ContactForm $contact_form The contact form object
     */
    public function handle_form_failure($contact_form) {
        $form_id = $contact_form->id();
        $form_title = $contact_form->title();
        
        // Get form data
        $submission = WPCF7_Submission::get_instance();
        if (!$submission) {
            return;
        }
        
        $posted_data = $submission->get_posted_data();
        
        // Determine form type
        $form_type = $this->get_form_type($form_id);
        
        // Send failure notification
        $this->send_discord_failure_notification($form_type, $form_title, $posted_data);
    }
    
    /**
     * Determine form type based on FlexPress form ID
     * 
     * @param int $form_id Contact Form 7 form ID
     * @return string Form type
     */
    private function get_form_type($form_id) {
        $contact_form_id = get_option('flexpress_contact_form_id');
        $casting_form_id = get_option('flexpress_casting_form_id');
        $support_form_id = get_option('flexpress_support_form_id');
        
        if ($form_id == $contact_form_id) {
            return 'contact';
        } elseif ($form_id == $casting_form_id) {
            return 'casting';
        } elseif ($form_id == $support_form_id) {
            return 'support';
        } else {
            return 'general';
        }
    }
    
    /**
     * Send Discord notification for successful form submission
     * 
     * @param string $form_type Type of form (contact, casting, support, general)
     * @param string $form_title Form title
     * @param array $posted_data Form submission data
     */
    private function send_discord_notification($form_type, $form_title, $posted_data) {
        $discord_settings = get_option('flexpress_discord_settings', []);
        
        // Check if Discord notifications are enabled for this form type
        if (!$this->is_notification_enabled($form_type, $discord_settings)) {
            return;
        }
        
        // Create Discord embed based on form type
        $embed = $this->create_form_embed($form_type, $form_title, $posted_data);
        
        // Send notification
        $discord = new FlexPress_Discord_Notifications();
        $content = $this->get_notification_content($form_type);
        
        $discord->send_notification($embed, $content, 'contact');
    }
    
    /**
     * Send Discord notification for form submission failure
     * 
     * @param string $form_type Type of form
     * @param string $form_title Form title
     * @param array $posted_data Form submission data
     */
    private function send_discord_failure_notification($form_type, $form_title, $posted_data) {
        $discord_settings = get_option('flexpress_discord_settings', []);
        
        // Check if failure notifications are enabled
        if (!($discord_settings['notify_form_failures'] ?? false)) {
            return;
        }
        
        // Create failure embed
        $embed = $this->create_failure_embed($form_type, $form_title, $posted_data);
        
        // Send notification
        $discord = new FlexPress_Discord_Notifications();
        $discord->send_notification($embed, '⚠️ **Form submission failed!**', 'contact');
    }
    
    /**
     * Check if notifications are enabled for this form type
     * 
     * @param string $form_type Form type
     * @param array $discord_settings Discord settings
     * @return bool
     */
    private function is_notification_enabled($form_type, $discord_settings) {
        switch ($form_type) {
            case 'contact':
                return $discord_settings['notify_contact_forms'] ?? true;
            case 'casting':
                return $discord_settings['notify_casting_applications'] ?? true;
            case 'support':
                return $discord_settings['notify_support_requests'] ?? true;
            default:
                return $discord_settings['notify_general_forms'] ?? true;
        }
    }
    
    /**
     * Create Discord embed for form submission
     * 
     * @param string $form_type Form type
     * @param string $form_title Form title
     * @param array $posted_data Form data
     * @return array Discord embed
     */
    private function create_form_embed($form_type, $form_title, $posted_data) {
        $discord = new FlexPress_Discord_Notifications();
        
        // Get form-specific data
        $title = $this->get_form_title($form_type);
        $color = $this->get_form_color($form_type);
        $fields = $this->get_form_fields($form_type, $posted_data, $form_title);
        
        $description = sprintf(
            __('New %s form submission received from %s', 'flexpress'),
            $form_type,
            get_bloginfo('name')
        );
        
        return $discord->create_general_embed($title, $description, $color, $fields);
    }
    
    /**
     * Create Discord embed for form failure
     * 
     * @param string $form_type Form type
     * @param string $form_title Form title
     * @param array $posted_data Form data
     * @return array Discord embed
     */
    private function create_failure_embed($form_type, $form_title, $posted_data) {
        $discord = new FlexPress_Discord_Notifications();
        
        $title = sprintf(__('%s Form Submission Failed', 'flexpress'), ucfirst($form_type));
        $description = sprintf(
            __('A %s form submission failed to send properly. Please check the form configuration.', 'flexpress'),
            $form_type
        );
        
        $fields = [
            [
                'name' => __('Form Title', 'flexpress'),
                'value' => $form_title,
                'inline' => true
            ],
            [
                'name' => __('Form Type', 'flexpress'),
                'value' => ucfirst($form_type),
                'inline' => true
            ],
            [
                'name' => __('Timestamp', 'flexpress'),
                'value' => current_time('Y-m-d H:i:s'),
                'inline' => true
            ]
        ];
        
        return $discord->create_general_embed($title, $description, 0xff0000, $fields);
    }
    
    /**
     * Get notification content based on form type
     * 
     * @param string $form_type Form type
     * @return string Notification content
     */
    private function get_notification_content($form_type) {
        switch ($form_type) {
            case 'contact':
                return '📧 **New contact form submission!**';
            case 'casting':
                return '🌟 **New casting application received!**';
            case 'support':
                return '🆘 **New support request submitted!**';
            default:
                return '📝 **New form submission received!**';
        }
    }
    
    /**
     * Get form title for Discord embed
     * 
     * @param string $form_type Form type
     * @return string Form title
     */
    private function get_form_title($form_type) {
        switch ($form_type) {
            case 'contact':
                return __('📧 Contact Form Submission', 'flexpress');
            case 'casting':
                return __('🌟 Casting Application', 'flexpress');
            case 'support':
                return __('🆘 Support Request', 'flexpress');
            default:
                return __('📝 Form Submission', 'flexpress');
        }
    }
    
    /**
     * Get form color for Discord embed
     * 
     * @param string $form_type Form type
     * @return int Color hex value
     */
    private function get_form_color($form_type) {
        switch ($form_type) {
            case 'contact':
                return 0x0099ff; // Blue
            case 'casting':
                return 0xff6b35; // Orange
            case 'support':
                return 0xff0000; // Red
            default:
                return 0x00ff00; // Green
        }
    }
    
    /**
     * Get form fields for Discord embed
     * 
     * @param string $form_type Form type
     * @param array $posted_data Form data
     * @param string $form_title Form title
     * @return array Discord embed fields
     */
    private function get_form_fields($form_type, $posted_data, $form_title = '') {
        $fields = [];
        
        // Common fields for all forms
        if (isset($posted_data['name'])) {
            $fields[] = [
                'name' => __('Name', 'flexpress'),
                'value' => $posted_data['name'],
                'inline' => true
            ];
        }
        
        if (isset($posted_data['email'])) {
            $fields[] = [
                'name' => __('Email', 'flexpress'),
                'value' => $posted_data['email'],
                'inline' => true
            ];
        }
        
        // Form-specific fields
        switch ($form_type) {
            case 'contact':
                if (isset($posted_data['subject'])) {
                    $fields[] = [
                        'name' => __('Subject', 'flexpress'),
                        'value' => $posted_data['subject'],
                        'inline' => true
                    ];
                }
                break;
                
            case 'casting':
                if (isset($posted_data['gender_identity'])) {
                    $fields[] = [
                        'name' => __('Gender Identity', 'flexpress'),
                        'value' => $posted_data['gender_identity'],
                        'inline' => true
                    ];
                }
                if (isset($posted_data['stage_age'])) {
                    $fields[] = [
                        'name' => __('Preferred Stage Age', 'flexpress'),
                        'value' => $posted_data['stage_age'],
                        'inline' => true
                    ];
                }
                if (isset($posted_data['instagram']) && !empty($posted_data['instagram'])) {
                    $fields[] = [
                        'name' => __('Instagram', 'flexpress'),
                        'value' => $posted_data['instagram'],
                        'inline' => true
                    ];
                }
                if (isset($posted_data['twitter']) && !empty($posted_data['twitter'])) {
                    $fields[] = [
                        'name' => __('Twitter', 'flexpress'),
                        'value' => $posted_data['twitter'],
                        'inline' => true
                    ];
                }
                if (isset($posted_data['about_you'])) {
                    $about_you = $posted_data['about_you'];
                    // Truncate long descriptions
                    if (strlen($about_you) > 1000) {
                        $about_you = substr($about_you, 0, 1000) . '...';
                    }
                    $fields[] = [
                        'name' => __('About You', 'flexpress'),
                        'value' => $about_you,
                        'inline' => false
                    ];
                }
                if (isset($posted_data['agreement'])) {
                    $fields[] = [
                        'name' => __('Agreement', 'flexpress'),
                        'value' => __('Confirmed', 'flexpress'),
                        'inline' => true
                    ];
                }
                break;
                
            case 'support':
                if (isset($posted_data['issue_type'])) {
                    $fields[] = [
                        'name' => __('Issue Type', 'flexpress'),
                        'value' => $posted_data['issue_type'],
                        'inline' => true
                    ];
                }
                if (isset($posted_data['priority'])) {
                    $fields[] = [
                        'name' => __('Priority', 'flexpress'),
                        'value' => $posted_data['priority'],
                        'inline' => true
                    ];
                }
                break;
        }
        
        // Add message field if present
        if (isset($posted_data['message'])) {
            $message = $posted_data['message'];
            // Truncate long messages
            if (strlen($message) > 1000) {
                $message = substr($message, 0, 1000) . '...';
            }
            $fields[] = [
                'name' => __('Message', 'flexpress'),
                'value' => $message,
                'inline' => false
            ];
        }
        
        // Add form title
        $fields[] = [
            'name' => __('Form', 'flexpress'),
            'value' => $form_title,
            'inline' => true
        ];
        
        // Add timestamp
        $fields[] = [
            'name' => __('Submitted', 'flexpress'),
            'value' => current_time('Y-m-d H:i:s'),
            'inline' => true
        ];
        
        return $fields;
    }
}

// Initialize the integration
new FlexPress_CF7_Discord_Integration();
