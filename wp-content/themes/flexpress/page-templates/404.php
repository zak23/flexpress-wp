<?php
/**
 * The template for displaying 404 pages (not found)
 */

get_header();
?>

<div class="site-main">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <div class="error-404 mb-5">
                    <h1 class="display-1 text-primary mb-4">404</h1>
                    <h2 class="h3 mb-4"><?php esc_html_e('Page Not Found', 'flexpress'); ?></h2>
                    <p class="lead text-muted mb-5">
                        <?php esc_html_e('The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.', 'flexpress'); ?>
                    </p>
                </div>

                <!-- Search Form -->
                <div class="row justify-content-center mb-5">
                    <div class="col-md-8">
                        <form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
                            <div class="input-group input-group-lg">
                                <input type="search" class="form-control" placeholder="<?php esc_attr_e('Search episodes...', 'flexpress'); ?>" value="<?php echo get_search_query(); ?>" name="s" title="<?php esc_attr_e('Search for:', 'flexpress'); ?>" />
                                <button class="btn btn-primary" type="submit">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="row g-4 mb-5">
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <i class="bi bi-collection-play display-4 text-primary mb-3"></i>
                                <h3 class="h5 mb-3"><?php esc_html_e('Browse Episodes', 'flexpress'); ?></h3>
                                <p class="text-muted mb-3"><?php esc_html_e('Check out our latest episodes and exclusive content.', 'flexpress'); ?></p>
                                <a href="<?php echo esc_url(home_url('/episodes')); ?>" class="btn btn-outline-primary">
                                    <?php esc_html_e('View Episodes', 'flexpress'); ?>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <i class="bi bi-person-plus display-4 text-primary mb-3"></i>
                                <h3 class="h5 mb-3"><?php esc_html_e('Join Now', 'flexpress'); ?></h3>
                                <p class="text-muted mb-3"><?php esc_html_e('Create an account to access exclusive content and member benefits.', 'flexpress'); ?></p>
                                <a href="<?php echo esc_url(home_url('/join')); ?>" class="btn btn-outline-primary">
                                    <?php esc_html_e('Sign Up', 'flexpress'); ?>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <i class="bi bi-question-circle display-4 text-primary mb-3"></i>
                                <h3 class="h5 mb-3"><?php esc_html_e('Need Help?', 'flexpress'); ?></h3>
                                <p class="text-muted mb-3"><?php esc_html_e('Contact our support team for assistance.', 'flexpress'); ?></p>
                                <a href="<?php echo esc_url(home_url('/contact')); ?>" class="btn btn-outline-primary">
                                    <?php esc_html_e('Contact Us', 'flexpress'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Back to Home -->
                <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-primary btn-lg">
                    <?php esc_html_e('Back to Homepage', 'flexpress'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<?php
get_footer(); 