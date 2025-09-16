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