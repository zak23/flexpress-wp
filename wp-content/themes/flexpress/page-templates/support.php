<?php
/**
 * Template Name: Support
 */

get_header();
?>

<div class="site-main">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <h1 class="h3 mb-3"><?php the_title(); ?></h1>
                            <p class="lead text-muted mb-4"><?php the_content(); ?></p>
                        </div>

                        <?php
                        // Display Contact Form 7 support form
                        if (class_exists('WPCF7')) {
                            flexpress_display_cf7_form('support');
                        } else {
                            echo '<div class="alert alert-warning">';
                            echo '<p>' . esc_html__('Contact Form 7 plugin is required for this form to work. Please install and activate Contact Form 7.', 'flexpress') . '</p>';
                            echo '</div>';
                        }
                        ?>

                        <hr class="my-4">

                        <!-- Support Information -->
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="text-center">
                                    <i class="bi bi-headset display-4 text-primary mb-3"></i>
                                    <h3 class="h6 mb-2"><?php esc_html_e('Support Hours', 'flexpress'); ?></h3>
                                    <p class="text-muted mb-0">
                                        <?php esc_html_e('Monday - Friday: 9:00 AM - 6:00 PM', 'flexpress'); ?><br>
                                        <?php esc_html_e('Saturday: 10:00 AM - 4:00 PM', 'flexpress'); ?><br>
                                        <?php esc_html_e('Sunday: Closed', 'flexpress'); ?>
                                    </p>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="text-center">
                                    <i class="bi bi-envelope display-4 text-primary mb-3"></i>
                                    <h3 class="h6 mb-2"><?php esc_html_e('Direct Support', 'flexpress'); ?></h3>
                                    <p class="text-muted mb-0">
                                        <a href="mailto:<?php echo esc_attr(flexpress_get_contact_email('support')); ?>" class="text-decoration-none">
                                            <?php echo esc_html(flexpress_get_contact_email('support')); ?>
                                        </a>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- FAQ Section -->
                        <div class="mt-5">
                            <h3 class="h5 mb-4"><?php esc_html_e('Frequently Asked Questions', 'flexpress'); ?></h3>
                            
                            <div class="accordion" id="supportFAQ">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="faq1">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1" aria-expanded="false" aria-controls="collapse1">
                                            <?php esc_html_e('How do I reset my password?', 'flexpress'); ?>
                                        </button>
                                    </h2>
                                    <div id="collapse1" class="accordion-collapse collapse" aria-labelledby="faq1" data-bs-parent="#supportFAQ">
                                        <div class="accordion-body">
                                            <?php esc_html_e('Click on "Forgot Password" on the login page and enter your email address. You will receive a password reset link via email.', 'flexpress'); ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="faq2">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2" aria-expanded="false" aria-controls="collapse2">
                                            <?php esc_html_e('How do I update my payment method?', 'flexpress'); ?>
                                        </button>
                                    </h2>
                                    <div id="collapse2" class="accordion-collapse collapse" aria-labelledby="faq2" data-bs-parent="#supportFAQ">
                                        <div class="accordion-body">
                                            <?php esc_html_e('Log into your account and go to the billing section in your dashboard. You can update your payment method there.', 'flexpress'); ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="faq3">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3" aria-expanded="false" aria-controls="collapse3">
                                            <?php esc_html_e('How do I cancel my subscription?', 'flexpress'); ?>
                                        </button>
                                    </h2>
                                    <div id="collapse3" class="accordion-collapse collapse" aria-labelledby="faq3" data-bs-parent="#supportFAQ">
                                        <div class="accordion-body">
                                            <?php esc_html_e('You can cancel your subscription at any time from your account dashboard. Go to the subscription section and click "Cancel Subscription".', 'flexpress'); ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="faq4">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse4" aria-expanded="false" aria-controls="collapse4">
                                            <?php esc_html_e('Why can\'t I access certain content?', 'flexpress'); ?>
                                        </button>
                                    </h2>
                                    <div id="collapse4" class="accordion-collapse collapse" aria-labelledby="faq4" data-bs-parent="#supportFAQ">
                                        <div class="accordion-body">
                                            <?php esc_html_e('Some content may require a specific membership level or individual purchase. Check the content details to see what access is required.', 'flexpress'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
get_footer();
