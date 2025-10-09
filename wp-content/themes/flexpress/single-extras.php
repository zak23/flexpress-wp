<?php
/**
 * The template for displaying single extras
 */

get_header();

while (have_posts()):
    the_post();
    
    // Check if user can view this extra content
    if (!flexpress_can_user_view_extras(get_the_ID())) {
        // Redirect non-logged-in users to login page
        if (!is_user_logged_in()) {
            wp_redirect(wp_login_url(get_permalink()));
            exit;
        }
    }
    
    $price = get_field('extras_price');
    $member_discount = get_field('member_discount');
    $release_date = get_field('release_date');
    $content_type = get_field('content_type');
    $content_format = get_field('content_format') ?: 'gallery';
    $gallery_columns = get_field('gallery_columns') ?: 3;
    
    // Video fields (only for video content)
    $preview_video = get_field('preview_video');
    $full_video = get_field('full_video');
    $duration = get_field('extras_duration');
    
    // Check for PPV unlock success message
    $ppv_unlocked = isset($_GET['ppv']) && $_GET['ppv'] === 'unlocked';
    $payment_cancelled = isset($_GET['payment']) && $_GET['payment'] === 'cancelled';
    
    // Check if user has access
    $access_info = flexpress_check_extras_access(get_the_ID());
    $has_access = $access_info['has_access'];
    $is_active_member = $access_info['is_member'];
?>

<div class="extras-single">
    <div class="container pt-3 pb-5">
        <?php if ($ppv_unlocked): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-unlock me-2"></i>
                <strong><?php esc_html_e('Extra Content Unlocked!', 'flexpress'); ?></strong>
                <?php esc_html_e('Thank you for your purchase. You now have full access to this extra content.', 'flexpress'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($payment_cancelled): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong><?php esc_html_e('Payment Cancelled', 'flexpress'); ?></strong>
                <?php esc_html_e('Your payment was cancelled. You can try again at any time.', 'flexpress'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <!-- Content Section -->
        <?php if ($content_format === 'gallery'): ?>
            <!-- Gallery Section -->
            <?php if (flexpress_has_extras_gallery()): ?>
            <div class="row mb-5">
                <div class="col-12">
                    <div class="extras-gallery-section">
                        <?php flexpress_display_extras_gallery(get_the_ID(), $gallery_columns, $has_access); ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        <?php elseif ($content_format === 'video'): ?>
            <!-- Video Section -->
            <div class="row mb-5">
                <div class="col-12">
                    <div class="video-player">
                        <?php 
                        $video_id = flexpress_get_extras_video_for_access(get_the_ID());
                        if ($video_id): 
                            $video_settings = get_option('flexpress_video_settings', array());
                            $library_id = isset($video_settings['bunnycdn_library_id']) ? $video_settings['bunnycdn_library_id'] : '';
                            $token_key = isset($video_settings['bunnycdn_token_key']) ? $video_settings['bunnycdn_token_key'] : '';
                            
                            // Generate token and expiration
                            $expires = time() + 3600; // 1 hour expiry
                            $token = '';
                            
                            if (!empty($token_key)) {
                                $token = hash('sha256', $token_key . $video_id . $expires);
                            }
                        ?>
                            <div style="position:relative;padding-top:56.25%;">
                                <iframe src="https://iframe.mediadelivery.net/embed/<?php echo esc_attr($library_id); ?>/<?php echo esc_attr($video_id); ?>?token=<?php echo esc_attr($token); ?>&expires=<?php echo esc_attr($expires); ?>&autoplay=true&loop=true&muted=true&controls=false"
                                        loading="lazy"
                                        style="border:0;position:absolute;top:0;height:100%;width:100%;"
                                        allow="accelerometer;gyroscope;autoplay;encrypted-media;picture-in-picture;"
                                        allowfullscreen="true">
                                </iframe>
                            </div>
                        <?php else: ?>
                            <div class="d-flex align-items-center justify-content-center" style="aspect-ratio: 16/9; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);">
                                <div class="text-center text-light">
                                    <i class="fas fa-lock fa-3x mb-3"></i>
                                    <h4><?php esc_html_e('Premium Extra Content', 'flexpress'); ?></h4>
                                    <p><?php esc_html_e('Please purchase this extra content to view.', 'flexpress'); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Content Section -->
        <div class="row">
            <div class="col-lg-8">
                <!-- Extra Content Details -->
                <div class="extras-details mb-4">
                    <h1 class="mb-4"><?php the_title(); ?></h1>
                    
                    <div class="meta-info mb-4 text-muted">
                        <?php if ($content_type): ?>
                            <span class="me-3">
                                <i class="fas fa-tag me-1"></i>
                                <?php echo esc_html(ucwords(str_replace('_', ' ', $content_type))); ?>
                            </span>
                        <?php endif; ?>
                        
                        <?php if ($release_date): ?>
                            <span class="me-3">
                                <i class="far fa-calendar-alt me-1"></i>
                                <?php 
                                // More robust date parsing
                                if (preg_match('/^\d{2}\/\d{2}\/\d{4} \d{2}:\d{2} [ap]m$/i', $release_date)) {
                                    // Format like "23/03/2025 12:00 am"
                                    $date_parts = explode(' ', $release_date);
                                    $date_numbers = explode('/', $date_parts[0]);
                                    echo esc_html($date_numbers[0] . ' ' . date('F', mktime(0, 0, 0, $date_numbers[1], 1)) . ' ' . $date_numbers[2]);
                                } else {
                                    // Try with standard strtotime
                                    $timestamp = strtotime($release_date);
                                    if ($timestamp && $timestamp > 0) {
                                        echo esc_html(date('j F Y', $timestamp));
                                    } else {
                                        // Fallback to just showing the raw date
                                        echo esc_html($release_date);
                                    }
                                }
                                ?>
                            </span>
                        <?php endif; ?>
                        
                        <?php if ($price && !$has_access): ?>
                            <span>
                                <i class="fas fa-tag me-1"></i>
                                <?php
                                if (is_user_logged_in() && $member_discount) {
                                    $discounted_price = $price * (1 - ($member_discount / 100));
                                    echo '$' . number_format($discounted_price, 2);
                                } else {
                                    echo '$' . number_format($price, 2);
                                }
                                ?>
                            </span>
                        <?php endif; ?>

                        <?php if (is_user_logged_in() && function_exists('flexpress_get_membership_status')): ?>
                            <span class="me-3">
                                <i class="fas fa-user-shield me-1"></i>
                                <?php 
                                $membership_status = flexpress_get_membership_status();
                                $status_class = '';
                                switch($membership_status) {
                                    case 'active':
                                        $status_class = 'text-success';
                                        break;
                                    case 'cancelled':
                                        $status_class = 'text-warning';
                                        break;
                                    case 'expired':
                                    case 'banned':
                                        $status_class = 'text-danger';
                                        break;
                                    default:
                                        $status_class = 'text-secondary';
                                }
                                ?>
                                <span class="<?php echo esc_attr($status_class); ?>">
                                    <?php echo esc_html(ucfirst($membership_status)); ?>
                                </span>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="extras-description mb-4">
                        <?php the_content(); ?>
                    </div>

                    <?php if (has_tag()): ?>
                        <div class="extras-tags mt-4">
                            <?php the_tags('<i class="fas fa-tags me-2"></i>', ', '); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Purchase Section -->
                <?php if (!$has_access): ?>
                <div class="extras-purchase-section" id="purchase-section">
                    <?php if ($access_info['show_purchase_button']): ?>
                        <div class="card bg-dark border-secondary">
                            <div class="card-body">
                                <h5 class="text-white mb-3 text-center">
                                    <i class="fas fa-unlock me-2"></i>
                                    <?php esc_html_e('Unlock Extra Content', 'flexpress'); ?>
                                </h5>
                                
                                <!-- Access Type Badge -->
                                <div class="access-type-badge mb-3 text-center">
                                    <span class="badge bg-dark text-white fs-6 px-3 py-2 border border-secondary">
                                        <?php echo wp_kses(flexpress_get_extras_access_summary(get_the_ID()), array('br' => array())); ?>
                                    </span>
                                </div>
                                
                                <!-- Purchase Reason -->
                                <?php if (!empty($access_info['purchase_reason'])): ?>
                                    <div class="purchase-reason mb-3 p-3 bg-dark border border-secondary rounded">
                                        <small class="text-white">
                                            <i class="fas fa-info-circle me-2 text-secondary"></i>
                                            <?php echo esc_html($access_info['purchase_reason']); ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Membership Notice for PPV-Only -->
                                <?php if (!empty($access_info['membership_notice'])): ?>
                                    <div class="membership-notice mb-3 p-3 bg-warning text-dark rounded">
                                        <small>
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            <?php echo esc_html($access_info['membership_notice']); ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Price Display -->
                                <?php if ($access_info['price'] > 0): ?>
                                    <div class="price-display mb-3 text-center p-3 bg-dark border border-secondary rounded">
                                        <?php if ($access_info['discount'] > 0): ?>
                                            <div class="original-price text-secondary text-decoration-line-through mb-1">
                                                $<?php echo number_format($access_info['price'], 2); ?>
                                            </div>
                                            <div class="discounted-price fs-4 text-white fw-bold">
                                                $<?php echo number_format($access_info['final_price'], 2); ?>
                                            </div>
                                            <div class="fs-6 text-secondary">
                                                (<?php echo $access_info['discount']; ?>% member discount)
                                            </div>
                                        <?php else: ?>
                                            <div class="current-price fs-4 text-white fw-bold">
                                                $<?php echo number_format($access_info['final_price'], 2); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Purchase Button -->
                                <?php if (is_user_logged_in()): ?>
                                    <button class="btn btn-primary w-100 purchase-btn mb-3" 
                                            data-extras-id="<?php echo get_the_ID(); ?>"
                                            data-price="<?php echo esc_attr($access_info['final_price']); ?>"
                                            data-original-price="<?php echo esc_attr($access_info['price']); ?>"
                                            data-discount="<?php echo esc_attr($access_info['discount']); ?>"
                                            data-access-type="<?php echo esc_attr($access_info['access_type']); ?>"
                                            data-is-active-member="<?php echo $is_active_member ? 'true' : 'false'; ?>">
                                        <i class="fas fa-shopping-cart me-2"></i>
                                        <?php esc_html_e('Unlock Now', 'flexpress'); ?>
                                    </button>
                                <?php else: ?>
                                    <a href="<?php echo esc_url(home_url('/login?redirect_to=' . urlencode(get_permalink()))); ?>" 
                                       class="btn btn-primary w-100 mb-3">
                                        <i class="fas fa-sign-in-alt me-2"></i>
                                        <?php esc_html_e('Login to Purchase', 'flexpress'); ?>
                                    </a>
                                    <div class="text-center mb-3">
                                        <small class="text-secondary">
                                            <?php esc_html_e('New here?', 'flexpress'); ?>
                                            <a href="<?php echo esc_url(home_url('/register?redirect_to=' . urlencode(get_permalink()))); ?>" class="text-white">
                                                <?php esc_html_e('Create Account', 'flexpress'); ?>
                                            </a>
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!is_user_logged_in() || (is_user_logged_in() && !function_exists('flexpress_has_active_membership') || !flexpress_has_active_membership())): ?>
                        <hr class="my-3 border-secondary">
                        <div class="text-center">
                            <h6 class="mb-2 text-white">
                                <?php esc_html_e('Or get unlimited access', 'flexpress'); ?>
                            </h6>
                            <a href="<?php echo esc_url(home_url('/join')); ?>" class="btn btn-outline-light w-100 border border-secondary">
                                <i class="fas fa-crown me-2"></i>
                                <?php esc_html_e('Premium Membership', 'flexpress'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Featured Models -->
        <?php
        // Display featured models
        $featured_models = get_field('featured_models');
        if ($featured_models): ?>
        <div class="row mt-5">
            <div class="col-12">
                <div class="featured-models mb-4">
                    <h2 class="section-title"><?php esc_html_e('Featured Models', 'flexpress'); ?></h2>
                    <div class="models-grid">
                        <?php foreach ($featured_models as $model): 
                            // Set up post data for the model template part
                            global $post;
                            $original_post = $post;
                            $post = $model;
                            setup_postdata($post);
                        ?>
                            <div class="model-grid-item">
                                <?php get_template_part('template-parts/content-model/card'); ?>
                            </div>
                        <?php 
                            // Restore original post data
                            $post = $original_post;
                            setup_postdata($post);
                        endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Related Extras -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="related-extras mb-4">
                    <h2 class="section-title"><?php esc_html_e('Related Extras', 'flexpress'); ?></h2>
                    
                    <?php
                    $related_args = array(
                        'post_type' => 'extras',
                        'posts_per_page' => 4,
                        'post__not_in' => array(get_the_ID()),
                        'orderby' => 'rand'
                    );
                    
                    $related_extras = new WP_Query($related_args);
                    
                    if ($related_extras->have_posts()):
                    ?>
                        <div class="extras-grid">
                            <div class="row g-4">
                                <?php
                                while ($related_extras->have_posts()): $related_extras->the_post();
                                ?>
                                    <div class="col-6 col-lg-3">
                                        <?php get_template_part('template-parts/content', 'extras-card'); ?>
                                    </div>
                                <?php
                                endwhile;
                                wp_reset_postdata();
                                ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <?php esc_html_e('No related extras available.', 'flexpress'); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
endwhile;
get_footer();