<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="RATING" content="RTA-5042-1996-1400-1577-RTA" />
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php
// Check for banned users and redirect (but not on specific pages)
$current_url = $_SERVER['REQUEST_URI'] ?? '';
$current_page_slug = '';

// Extract page slug from URL
if (preg_match('/\/([^\/\?]+)/', $current_url, $matches)) {
    $current_page_slug = $matches[1];
}

// Handle special cases for post type archives
if (empty($current_page_slug) && strpos($current_url, '/models') !== false) {
    $current_page_slug = 'models';
} elseif (empty($current_page_slug) && strpos($current_url, '/episodes') !== false) {
    $current_page_slug = 'episodes';
}

// Pages that banned users can access
$allowed_pages = array('banned', 'support', 'contact');

if (is_user_logged_in() && !in_array($current_page_slug, $allowed_pages)) {
    $user_id = get_current_user_id();
    $membership_status = get_user_meta($user_id, 'membership_status', true);
    
    // Debug logging
    error_log('FlexPress Banned Check: User ID=' . $user_id . ', Status=' . var_export($membership_status, true) . ', Page=' . $current_page_slug . ', URL=' . $current_url . ', Allowed=' . implode(',', $allowed_pages));
    
    // Check if status is exactly 'banned' (not empty string or other values)
    if ($membership_status === 'banned') {
        // Redirect to banned page
        error_log('FlexPress Banned Check: Redirecting user ' . $user_id . ' to banned page');
        wp_redirect(home_url('/banned'));
        exit;
    } else {
        error_log('FlexPress Banned Check: User ' . $user_id . ' NOT banned, status=' . var_export($membership_status, true));
    }
}
?>

<div id="page" class="site">
    <header id="masthead" class="site-header">
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container">
                <a class="navbar-brand" href="<?php echo esc_url(home_url('/')); ?>">
                    <?php
                    flexpress_display_logo(array(
                        'class' => 'navbar-logo',
                        'alt' => get_bloginfo('name'),
                        'title_class' => 'text-uppercase fw-bold text-white',
                        'title_tag' => 'span'
                    ));
                    ?>
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#primary-menu" aria-controls="primary-menu" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="primary-menu">
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link <?php echo (is_post_type_archive('episode') || is_singular('episode')) ? 'active' : ''; ?>" href="<?php echo esc_url(get_post_type_archive_link('episode')); ?>">
                                <?php esc_html_e('Episodes', 'flexpress'); ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (is_post_type_archive('model') || is_singular('model')) ? 'active' : ''; ?>" href="<?php echo esc_url(get_post_type_archive_link('model')); ?>">
                                <?php esc_html_e('Models', 'flexpress'); ?>
                            </a>
                        </li>
                        <?php if (has_nav_menu('primary')): ?>
                            <?php
                            wp_nav_menu(array(
                                'theme_location' => 'primary',
                                'menu_class' => 'navbar-nav',
                                'container' => false,
                                'items_wrap' => '%3$s',
                                'fallback_cb' => false,
                                'depth' => 2,
                                'walker' => new WP_Bootstrap_Navwalker(),
                            ));
                            ?>
                        <?php endif; ?>
                    </ul>
                    
                    <div class="ms-lg-4 mt-3 mt-lg-0 d-flex">
                        <?php
                        // Prevent fragment caches from serving logged-out UI to logged-in users
                        if (function_exists('nocache_headers')) { nocache_headers(); }
                        ?>
                        <?php if (is_user_logged_in()): ?>
                            <a href="<?php echo esc_url(home_url('/my-account')); ?>" class="btn btn-outline-light me-2">
                                <i class="fas fa-user me-1"></i> <?php esc_html_e('My Account', 'flexpress'); ?>
                            </a>
                            <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="btn btn-outline-light">
                                <i class="fas fa-sign-out-alt me-1"></i> <?php esc_html_e('Logout', 'flexpress'); ?>
                            </a>
                        <?php else: ?>
                            <a href="<?php echo esc_url(home_url('/login')); ?>" class="btn btn-outline-light me-2">
                                <?php esc_html_e('Login', 'flexpress'); ?>
                            </a>
                            <a href="<?php echo esc_url(home_url('/join')); ?>" class="btn btn-primary">
                                <?php esc_html_e('Join Now', 'flexpress'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <div id="content" class="site-content"> 