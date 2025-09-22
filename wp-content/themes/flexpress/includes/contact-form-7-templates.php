<?php
/**
 * Contact Form 7 Templates for FlexPress
 * 
 * This file contains pre-configured Contact Form 7 templates
 * for contact, casting, and support forms.
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Create Contact Form 7 forms programmatically
 */
function flexpress_create_cf7_forms() {
    // Only run if Contact Form 7 is active
    if (!class_exists('WPCF7_ContactForm')) {
        return;
    }

    // Create Contact Form
    flexpress_create_contact_form();
    
    // Create Casting Form
    flexpress_create_casting_form();
    
    // Create Support Form
    flexpress_create_support_form();
}

/**
 * Create the main contact form
 */
function flexpress_create_contact_form() {
    $form_id = get_option('flexpress_contact_form_id');
    
    // Check if form already exists
    if ($form_id && get_post($form_id)) {
        return $form_id;
    }

    $form_content = '
<div class="row">
    <div class="col-md-6 mb-3">
        <label for="name" class="form-label">' . __('Name', 'flexpress') . ' <span class="text-danger">*</span></label>
        [text* name id:name class:form-control placeholder "' . __('Your full name', 'flexpress') . '"]
        <div class="invalid-feedback">' . __('Please enter your name.', 'flexpress') . '</div>
    </div>
    <div class="col-md-6 mb-3">
        <label for="email" class="form-label">' . __('Email Address', 'flexpress') . ' <span class="text-danger">*</span></label>
        [email* email id:email class:form-control placeholder "' . __('your@email.com', 'flexpress') . '"]
        <div class="invalid-feedback">' . __('Please enter a valid email address.', 'flexpress') . '</div>
    </div>
</div>

<div class="mb-3">
    <label for="subject" class="form-label">' . __('Subject', 'flexpress') . ' <span class="text-danger">*</span></label>
    [text* subject id:subject class:form-control placeholder "' . __('What is this about?', 'flexpress') . '"]
    <div class="invalid-feedback">' . __('Please enter a subject.', 'flexpress') . '</div>
</div>

<div class="mb-3">
    <label for="message" class="form-label">' . __('Message', 'flexpress') . ' <span class="text-danger">*</span></label>
    [textarea* message id:message class:form-control rows:5 placeholder "' . __('Tell us more...', 'flexpress') . '"]
    <div class="invalid-feedback">' . __('Please enter your message.', 'flexpress') . '</div>
</div>

<div class="mb-3">
    [submit class:btn class:btn-primary "' . __('Send Message', 'flexpress') . '"]
</div>';

    $mail_template = '
<p><strong>' . __('Name:', 'flexpress') . '</strong> [name]</p>
<p><strong>' . __('Email:', 'flexpress') . '</strong> [email]</p>
<p><strong>' . __('Subject:', 'flexpress') . '</strong> [subject]</p>
<p><strong>' . __('Message:', 'flexpress') . '</strong></p>
<p>[message]</p>

<hr>
<p><em>' . __('This message was sent from the contact form on', 'flexpress') . ' ' . get_bloginfo('name') . '</em></p>';

    $mail_2_template = '
<p>' . __('Hello [name],', 'flexpress') . '</p>

<p>' . __('Thank you for contacting us. We have received your message and will get back to you as soon as possible.', 'flexpress') . '</p>

<p><strong>' . __('Your message:', 'flexpress') . '</strong></p>
<p>[message]</p>

<hr>
<p>' . __('Best regards,', 'flexpress') . '<br>
' . get_bloginfo('name') . '</p>';

    $form_data = array(
        'post_title' => 'FlexPress Contact Form',
        'post_content' => $form_content,
        'post_status' => 'publish',
        'post_type' => 'wpcf7_contact_form',
        'meta_input' => array(
            '_form' => $form_content,
            '_mail' => array(
                'active' => true,
                'subject' => sprintf(__('Contact Form: %s', 'flexpress'), '[subject]'),
                'sender' => '[name] <[email]>',
                'body' => $mail_template,
                'recipient' => flexpress_get_contact_email('contact'),
                'additional_headers' => 'Reply-To: [email]',
                'attachments' => '',
                'use_html' => true,
                'exclude_blank' => false
            ),
            '_mail_2' => array(
                'active' => true,
                'subject' => sprintf(__('Thank you for contacting %s', 'flexpress'), get_bloginfo('name')),
                'sender' => get_bloginfo('name') . ' <' . flexpress_get_contact_email('contact') . '>',
                'body' => $mail_2_template,
                'recipient' => '[email]',
                'additional_headers' => '',
                'attachments' => '',
                'use_html' => true,
                'exclude_blank' => false
            )
        )
    );

    $form_id = wp_insert_post($form_data);
    
    if ($form_id && !is_wp_error($form_id)) {
        update_option('flexpress_contact_form_id', $form_id);
        return $form_id;
    }
    
    return false;
}

/**
 * Create the casting application form
 */
function flexpress_create_casting_form() {
    $form_id = get_option('flexpress_casting_form_id');
    
    // Check if form already exists
    if ($form_id && get_post($form_id)) {
        return $form_id;
    }

    $form_content = '
<div class="alert alert-info mb-4">
    <i class="bi bi-info-circle-fill me-2"></i>
    ' . __('All applicants must be at least 18 years of age. ID verification will be required if selected.', 'flexpress') . '
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="name" class="form-label">' . __('Your Name', 'flexpress') . ' <span class="text-danger">*</span></label>
        [text* name id:name class:form-control placeholder "' . __('Enter your full name', 'flexpress') . '"]
        <div class="invalid-feedback">' . __('Please enter your full name.', 'flexpress') . '</div>
    </div>
    <div class="col-md-6 mb-3">
        <label for="email" class="form-label">' . __('Your Email', 'flexpress') . ' <span class="text-danger">*</span></label>
        [email* email id:email class:form-control placeholder "' . __('Enter your email address', 'flexpress') . '"]
        <div class="invalid-feedback">' . __('Please enter a valid email address.', 'flexpress') . '</div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="gender_identity" class="form-label">' . __('Gender Identity', 'flexpress') . ' <span class="text-danger">*</span></label>
        [select* gender_identity id:gender_identity class:form-control include_blank "' . __('Please select...', 'flexpress') . '" "' . __('Female', 'flexpress') . '" "' . __('Male', 'flexpress') . '" "' . __('Non-binary', 'flexpress') . '" "' . __('Transgender', 'flexpress') . '" "' . __('Genderfluid', 'flexpress') . '" "' . __('Agender', 'flexpress') . '" "' . __('Other', 'flexpress') . '"]
        <div class="invalid-feedback">' . __('Please select your gender identity.', 'flexpress') . '</div>
    </div>
    <div class="col-md-6 mb-3">
        <label for="stage_age" class="form-label">' . __('Preferred Stage Age', 'flexpress') . ' <span class="text-danger">*</span></label>
        [select* stage_age id:stage_age class:form-control include_blank "' . __('Please select...', 'flexpress') . '" "' . __('18-21', 'flexpress') . '" "' . __('22-25', 'flexpress') . '" "' . __('26-30', 'flexpress') . '" "' . __('31-35', 'flexpress') . '" "' . __('36-40', 'flexpress') . '" "' . __('41-45', 'flexpress') . '" "' . __('46-50', 'flexpress') . '" "' . __('50+', 'flexpress') . '"]
        <div class="invalid-feedback">' . __('Please select your preferred stage age.', 'flexpress') . '</div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="instagram" class="form-label">' . __('Instagram', 'flexpress') . '</label>
        [text instagram id:instagram class:form-control placeholder "' . __('Your Instagram handle (optional)', 'flexpress') . '"]
    </div>
    <div class="col-md-6 mb-3">
        <label for="twitter" class="form-label">' . __('Twitter', 'flexpress') . '</label>
        [text twitter id:twitter class:form-control placeholder "' . __('Your Twitter handle (optional)', 'flexpress') . '"]
    </div>
</div>

<div class="mb-3">
    <label for="about_you" class="form-label">' . __('About You', 'flexpress') . ' <span class="text-danger">*</span></label>
    [textarea* about_you id:about_you class:form-control rows:5 placeholder "' . __('Tell us about yourself, including any relevant experience, links to your work, social media profiles, and professional references...', 'flexpress') . '"]
    <div class="invalid-feedback">' . __('Please tell us about yourself.', 'flexpress') . '</div>
</div>

<div class="mb-3">
    <div class="form-check">
        <input type="checkbox" name="agreement" id="agreement" class="form-check-input" required>
        <label class="form-check-label" for="agreement">
            ' . sprintf(__('I confirm I am over 18 years old and understand that submitting this form does not guarantee acceptance. I agree that %s may contact me regarding casting opportunities.', 'flexpress'), get_bloginfo('name')) . ' <span class="text-danger">*</span>
        </label>
        <div class="invalid-feedback">' . __('You must agree to the terms to submit your application.', 'flexpress') . '</div>
    </div>
</div>

<div class="mb-3">
    [submit class:btn class:btn-primary "' . __('Submit Application', 'flexpress') . '"]
</div>';

    $mail_template = '
<p><strong>' . __('Casting Application Received', 'flexpress') . '</strong></p>

<p><strong>' . __('Applicant Details:', 'flexpress') . '</strong></p>
<p><strong>' . __('Name:', 'flexpress') . '</strong> [name]</p>
<p><strong>' . __('Email:', 'flexpress') . '</strong> [email]</p>
<p><strong>' . __('Gender Identity:', 'flexpress') . '</strong> [gender_identity]</p>
<p><strong>' . __('Preferred Stage Age:', 'flexpress') . '</strong> [stage_age]</p>

<p><strong>' . __('Social Media:', 'flexpress') . '</strong></p>
<p><strong>' . __('Instagram:', 'flexpress') . '</strong> [instagram]</p>
<p><strong>' . __('Twitter:', 'flexpress') . '</strong> [twitter]</p>

<p><strong>' . __('About You:', 'flexpress') . '</strong></p>
<p>[about_you]</p>

<p><strong>' . __('Agreement:', 'flexpress') . '</strong> ' . __('Confirmed', 'flexpress') . '</p>

<hr>
<p><em>' . __('This casting application was submitted from', 'flexpress') . ' ' . get_bloginfo('name') . '</em></p>';

    $mail_2_template = '
<p>' . __('Hello [name],', 'flexpress') . '</p>

<p>' . __('Thank you for your interest in joining our cast! We have received your application and will review it carefully.', 'flexpress') . '</p>

<p>' . __('If we think you might be a good fit, we will contact you within 7-10 business days to discuss next steps.', 'flexpress') . '</p>

<p>' . __('Thank you for your interest in', 'flexpress') . ' ' . get_bloginfo('name') . '!</p>

<hr>
<p>' . __('Best regards,', 'flexpress') . '<br>
' . __('The Casting Team', 'flexpress') . '<br>
' . get_bloginfo('name') . '</p>';

    $form_data = array(
        'post_title' => 'FlexPress Casting Application',
        'post_content' => $form_content,
        'post_status' => 'publish',
        'post_type' => 'wpcf7_contact_form',
        'meta_input' => array(
            '_form' => $form_content,
            '_mail' => array(
                'active' => true,
                'subject' => sprintf(__('Casting Application: %s', 'flexpress'), '[name]'),
                'sender' => '[name] <[email]>',
                'body' => $mail_template,
                'recipient' => flexpress_get_contact_email('contact'),
                'additional_headers' => 'Reply-To: [email]',
                'attachments' => '',
                'use_html' => true,
                'exclude_blank' => false
            ),
            '_mail_2' => array(
                'active' => true,
                'subject' => sprintf(__('Thank you for your casting application - %s', 'flexpress'), get_bloginfo('name')),
                'sender' => get_bloginfo('name') . ' <' . flexpress_get_contact_email('contact') . '>',
                'body' => $mail_2_template,
                'recipient' => '[email]',
                'additional_headers' => '',
                'attachments' => '',
                'use_html' => true,
                'exclude_blank' => false
            )
        )
    );

    $form_id = wp_insert_post($form_data);
    
    if ($form_id && !is_wp_error($form_id)) {
        update_option('flexpress_casting_form_id', $form_id);
        return $form_id;
    }
    
    return false;
}

/**
 * Create the support form
 */
function flexpress_create_support_form() {
    $form_id = get_option('flexpress_support_form_id');
    
    // Check if form already exists
    if ($form_id && get_post($form_id)) {
        return $form_id;
    }

    $form_content = '
<div class="row">
    <div class="col-md-6 mb-3">
        <label for="name" class="form-label">' . __('Name', 'flexpress') . ' <span class="text-danger">*</span></label>
        [text* name id:name class:form-control placeholder "' . __('Your full name', 'flexpress') . '"]
        <div class="invalid-feedback">' . __('Please enter your name.', 'flexpress') . '</div>
    </div>
    <div class="col-md-6 mb-3">
        <label for="email" class="form-label">' . __('Email Address', 'flexpress') . ' <span class="text-danger">*</span></label>
        [email* email id:email class:form-control placeholder "' . __('your@email.com', 'flexpress') . '"]
        <div class="invalid-feedback">' . __('Please enter a valid email address.', 'flexpress') . '</div>
    </div>
</div>

<div class="mb-3">
    <label for="issue_type" class="form-label">' . __('Issue Type', 'flexpress') . ' <span class="text-danger">*</span></label>
    [select* issue_type id:issue_type class:form-control include_blank "' . __('Select an issue type', 'flexpress') . '" "' . __('Technical Support', 'flexpress') . '" "' . __('Account Issues', 'flexpress') . '" "' . __('Billing Questions', 'flexpress') . '" "' . __('Content Access', 'flexpress') . '" "' . __('Other', 'flexpress') . '"]
    <div class="invalid-feedback">' . __('Please select an issue type.', 'flexpress') . '</div>
</div>

<div class="mb-3">
    <label for="priority" class="form-label">' . __('Priority', 'flexpress') . '</label>
    [select priority id:priority class:form-control "' . __('Low', 'flexpress') . '" "' . __('Medium', 'flexpress') . '" "' . __('High', 'flexpress') . '" "' . __('Urgent', 'flexpress') . '"]
</div>

<div class="mb-3">
    <label for="subject" class="form-label">' . __('Subject', 'flexpress') . ' <span class="text-danger">*</span></label>
    [text* subject id:subject class:form-control placeholder "' . __('Brief description of your issue', 'flexpress') . '"]
    <div class="invalid-feedback">' . __('Please enter a subject.', 'flexpress') . '</div>
</div>

<div class="mb-3">
    <label for="message" class="form-label">' . __('Message', 'flexpress') . ' <span class="text-danger">*</span></label>
    [textarea* message id:message class:form-control rows:5 placeholder "' . __('Please provide detailed information about your issue...', 'flexpress') . '"]
    <div class="invalid-feedback">' . __('Please enter your message.', 'flexpress') . '</div>
</div>

<div class="mb-3">
    [submit class:btn class:btn-primary "' . __('Submit Support Request', 'flexpress') . '"]
</div>';

    $mail_template = '
<p><strong>' . __('Support Request Received', 'flexpress') . '</strong></p>

<p><strong>' . __('Customer Details:', 'flexpress') . '</strong></p>
<p><strong>' . __('Name:', 'flexpress') . '</strong> [name]</p>
<p><strong>' . __('Email:', 'flexpress') . '</strong> [email]</p>
<p><strong>' . __('Issue Type:', 'flexpress') . '</strong> [issue_type]</p>
<p><strong>' . __('Priority:', 'flexpress') . '</strong> [priority]</p>
<p><strong>' . __('Subject:', 'flexpress') . '</strong> [subject]</p>

<p><strong>' . __('Message:', 'flexpress') . '</strong></p>
<p>[message]</p>

<hr>
<p><em>' . __('This support request was submitted from', 'flexpress') . ' ' . get_bloginfo('name') . '</em></p>';

    $mail_2_template = '
<p>' . __('Hello [name],', 'flexpress') . '</p>

<p>' . __('Thank you for contacting our support team. We have received your request and will respond as soon as possible.', 'flexpress') . '</p>

<p><strong>' . __('Your request details:', 'flexpress') . '</strong></p>
<p><strong>' . __('Issue Type:', 'flexpress') . '</strong> [issue_type]</p>
<p><strong>' . __('Priority:', 'flexpress') . '</strong> [priority]</p>
<p><strong>' . __('Subject:', 'flexpress') . '</strong> [subject]</p>

<p><strong>' . __('Your message:', 'flexpress') . '</strong></p>
<p>[message]</p>

<p>' . __('We typically respond within 24-48 hours during business days.', 'flexpress') . '</p>

<hr>
<p>' . __('Best regards,', 'flexpress') . '<br>
' . __('Support Team', 'flexpress') . '<br>
' . get_bloginfo('name') . '</p>';

    $form_data = array(
        'post_title' => 'FlexPress Support Request',
        'post_content' => $form_content,
        'post_status' => 'publish',
        'post_type' => 'wpcf7_contact_form',
        'meta_input' => array(
            '_form' => $form_content,
            '_mail' => array(
                'active' => true,
                'subject' => sprintf(__('Support Request: %s', 'flexpress'), '[subject]'),
                'sender' => '[name] <[email]>',
                'body' => $mail_template,
                'recipient' => flexpress_get_contact_email('support'),
                'additional_headers' => 'Reply-To: [email]',
                'attachments' => '',
                'use_html' => true,
                'exclude_blank' => false
            ),
            '_mail_2' => array(
                'active' => true,
                'subject' => sprintf(__('Support Request Received - %s', 'flexpress'), get_bloginfo('name')),
                'sender' => get_bloginfo('name') . ' <' . flexpress_get_contact_email('support') . '>',
                'body' => $mail_2_template,
                'recipient' => '[email]',
                'additional_headers' => '',
                'attachments' => '',
                'use_html' => true,
                'exclude_blank' => false
            )
        )
    );

    $form_id = wp_insert_post($form_data);
    
    if ($form_id && !is_wp_error($form_id)) {
        update_option('flexpress_support_form_id', $form_id);
        return $form_id;
    }
    
    return false;
}

/**
 * Get Contact Form 7 form ID by type
 */
function flexpress_get_cf7_form_id($type) {
    switch ($type) {
        case 'contact':
            return get_option('flexpress_contact_form_id');
        case 'casting':
            return get_option('flexpress_casting_form_id');
        case 'support':
            return get_option('flexpress_support_form_id');
        default:
            return false;
    }
}

/**
 * Display Contact Form 7 form by type
 */
function flexpress_display_cf7_form($type, $args = array()) {
    $form_id = flexpress_get_cf7_form_id($type);
    
    if (!$form_id) {
        // Create form if it doesn't exist
        switch ($type) {
            case 'contact':
                $form_id = flexpress_create_contact_form();
                break;
            case 'casting':
                $form_id = flexpress_create_casting_form();
                break;
            case 'support':
                $form_id = flexpress_create_support_form();
                break;
        }
    }
    
    if ($form_id) {
        $class = isset($args['class']) ? $args['class'] : 'needs-validation';
        echo '<div class="' . esc_attr($class) . '">';
        echo do_shortcode('[contact-form-7 id="' . esc_attr($form_id) . '"]');
        echo '</div>';
    } else {
        echo '<div class="alert alert-warning">';
        echo '<p>' . esc_html__('Form could not be loaded. Please contact the administrator.', 'flexpress') . '</p>';
        echo '</div>';
    }
}

// Initialize forms when Contact Form 7 is active
add_action('wpcf7_init', 'flexpress_create_cf7_forms');
