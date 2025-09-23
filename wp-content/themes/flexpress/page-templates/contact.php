<?php
/**
 * Template Name: Contact
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
                            <p class="lead text-muted mb-4"><?php the_content(); ?></p>
                        </div>

                        <?php
                        // Display Contact Form 7 form
                        if (class_exists('WPCF7')) {
                            flexpress_display_cf7_form('contact');
                        } else {
                            echo '<div class="alert alert-warning">';
                            echo '<p>' . esc_html__('Contact Form 7 plugin is required for this form to work. Please install and activate Contact Form 7.', 'flexpress') . '</p>';
                            echo '</div>';
                        }
                        ?>


                        <!-- Social Media -->
                        <div class="text-center mt-4">
                            <h3 class="h6 mb-3"><?php esc_html_e('Follow Us', 'flexpress'); ?></h3>
                            <div class="social-links d-flex justify-content-center">
                            <?php
                            flexpress_display_social_media_links(array(
                                'wrapper' => 'ul',
                                'item_wrapper' => 'li',
                                'class' => 'footer-menu social-icons list-unstyled d-flex gap-3',
                                'item_class' => '',
                                'link_class' => 'text-white',
                                'icon_class' => 'fa-lg',
                                'platforms' => array('facebook', 'instagram', 'twitter', 'tiktok', 'youtube', 'onlyfans'),
                                'show_icons' => true,
                                'show_labels' => false
                            ));
                            ?>
                                                             
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()
</script>

<?php
get_footer(); 