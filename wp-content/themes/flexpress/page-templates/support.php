<?php
/**
 * Template Name: Support
 */

get_header();
?>

<div class="site-main legal-page">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
               
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

                                      
                       
                       
                    </div>
            </div>
        </div>
         <!-- FAQ Section -->
         <div class="support-faq casting-faq">
                            <div class="container">
                                <div class="row">
                                    <div class="col-12 text-center mb-4">
                                        <h2><?php echo get_field('support_faq_title') ?: 'Frequently Asked Questions'; ?></h2>
                                    </div>

                                    <div class="col-md-8 mx-auto">
                                        <div class="accordion" id="supportFAQ">
                                            <?php 
                                            // Load FAQ items from ACF supporting multiple field shapes
                                            $faq_items = null;
                                            if (function_exists('get_field')) {
                                                $possible_fields = array('support_faq_items', 'support_faq_json', 'support_faq', 'faq_items');
                                                foreach ($possible_fields as $field_name) {
                                                    $raw = get_field($field_name);
                                                    if (empty($raw)) {
                                                        continue;
                                                    }
                                                    if (is_string($raw)) {
                                                        $decoded = json_decode($raw, true);
                                                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                                            $faq_items = $decoded;
                                                            break;
                                                        }
                                                    } elseif (is_array($raw)) {
                                                        $faq_items = $raw;
                                                        break;
                                                    }
                                                }
                                            }

                                            if ($faq_items && is_array($faq_items)):
                                                foreach ($faq_items as $index => $faq_item): 
                                                    $faq_id = 'faq' . ($index + 1);
                                                    $is_expanded = false;
                                                    if (isset($faq_item['expanded'])) {
                                                        $expanded_val = $faq_item['expanded'];
                                                        $is_expanded = ($expanded_val === true || $expanded_val === 1 || $expanded_val === '1' || $expanded_val === 'true');
                                                    }
                                                    $expanded_class = $is_expanded ? ' show' : '';
                                                    $button_class = $is_expanded ? '' : ' collapsed';
                                                    $question = isset($faq_item['question']) ? $faq_item['question'] : (isset($faq_item['faq_question']) ? $faq_item['faq_question'] : (isset($faq_item['question_text']) ? $faq_item['question_text'] : ''));
                                                    $answer = isset($faq_item['answer']) ? $faq_item['answer'] : (isset($faq_item['faq_answer']) ? $faq_item['faq_answer'] : (isset($faq_item['answer_html']) ? $faq_item['answer_html'] : ''));
                                            ?>
                                            <div class="accordion-item">
                                                <h2 class="accordion-header">
                                                    <button class="accordion-button<?php echo $button_class; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $faq_id; ?>">
                                                        <?php echo esc_html($question); ?>
                                                    </button>
                                                </h2>
                                                <div id="<?php echo $faq_id; ?>" class="accordion-collapse collapse<?php echo $expanded_class; ?>" data-bs-parent="#supportFAQ">
                                                    <div class="accordion-body">
                                                        <?php echo wp_kses_post($answer); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php 
                                                endforeach;
                                            else:
                                                // Fallback to default FAQs if no ACF data
                                            ?>
                                         
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
    </div>
</div>

<?php
get_footer();
