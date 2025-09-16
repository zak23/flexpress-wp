<?php
/**
 * Template Name: My Account Handler
 * Description: Routes users to appropriate page based on login status
 */

if (is_user_logged_in()) {
    // Logged in users go to dashboard with profile settings
    wp_redirect(home_url('/dashboard/'));
} else {
    // Logged out users see login form
    wp_redirect(home_url('/login/'));
}
exit;
?> 