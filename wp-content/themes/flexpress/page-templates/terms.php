<?php
/**
 * Template Name: Terms and Conditions
 */

get_header();
?>

<div class="site-main legal-page">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h1 class="h2 mb-4"><?php the_title(); ?></h1>
                        
                        <div class="content">
                            <?php the_content(); ?>
                        </div>

                        <?php 
                        // Display additional content if set
                        flexpress_display_legal_additional_content(); 
                        ?>

                        <?php 
                        // Display contact form if configured
                        flexpress_display_legal_contact_form(); 
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
get_footer(); 