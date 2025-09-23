<?php
/**
 * Template Name: Content Removal
 */

get_header();
?>

<div class="site-main legal-page">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <h1 class="h3 mb-3"><?php the_title(); ?></h1>
                            <div class="lead text-muted mb-4"><?php the_content(); ?></div>
                        </div>

                        <?php
                        // Show any error messages
                        if (isset($_GET['sent']) && $_GET['sent'] === 'failed') {
                            echo '<div class="alert alert-danger">';
                            echo esc_html__('Failed to submit request. Please try again.', 'flexpress');
                            echo '</div>';
                        }

                        // Show success message
                        if (isset($_GET['sent']) && $_GET['sent'] === 'success') {
                            echo '<div class="alert alert-success">';
                            echo esc_html__('Request submitted successfully. We\'ll review your request and get back to you soon.', 'flexpress');
                            echo '</div>';
                        }
                        ?>

                        <?php
                        // Display the Contact Form 7 content removal form
                        flexpress_display_cf7_form('content_removal', array('class' => 'needs-validation'));
                        ?>

                        <?php 
                        // Display additional content if set
                        flexpress_display_legal_additional_content(); 
                        ?>

                        <?php 
                        // Display additional contact form if configured (separate from removal form)
                        $additional_form_id = get_field('legal_contact_form_id');
                        if ($additional_form_id) {
                            $form_title = get_field('legal_contact_form_title') ?: __('Additional Questions?', 'flexpress');
                            ?>
                            <div class="mt-5 pt-4 border-top">
                                <div class="text-center mb-4">
                                    <h2 class="h4 mb-3"><?php echo esc_html($form_title); ?></h2>
                                    <p class="text-muted"><?php esc_html_e('Have other questions not related to content removal? Use the form below.', 'flexpress'); ?></p>
                                </div>
                                
                                <?php
                                // Support both Contact Form 7 and WPForms
                                if (class_exists('WPCF7')) {
                                    echo do_shortcode('[contact-form-7 id="' . esc_attr($additional_form_id) . '"]');
                                } elseif (function_exists('wpforms')) {
                                    echo do_shortcode('[wpforms id="' . esc_attr($additional_form_id) . '"]');
                                } else {
                                    echo '<div class="alert alert-warning">';
                                    echo '<p>' . esc_html__('Contact form plugin not found. Please install Contact Form 7 or WPForms.', 'flexpress') . '</p>';
                                    echo '</div>';
                                }
                                ?>
                            </div>
                            <?php
                        }
                        ?>

                        <?php if (flexpress_should_show_legal_last_updated()): ?>
                        <div class="mt-5">
                            <h2 class="h4 mb-3"><?php esc_html_e('Last Updated', 'flexpress'); ?></h2>
                            <p class="text-muted">
                                <?php echo esc_html(flexpress_get_legal_last_updated_date()); ?>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<?php
get_footer(); 