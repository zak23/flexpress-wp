<?php
/**
 * Template for displaying single model profiles - Enhanced Layout
 *
 * @package FlexPress
 */

get_header();
?>

<main class="site-main">
<?php while (have_posts()) : the_post(); ?>
    <article id="post-<?php the_ID(); ?>" <?php post_class('model-profile'); ?>>
        
        <!-- Hero Section -->
        <?php 
        $hero_image = get_field('model_hero_image');
        if ($hero_image) :
            $hero_src = isset($hero_image['sizes']['hero-desktop']) ? $hero_image['sizes']['hero-desktop'] : (isset($hero_image['sizes']['large']) ? $hero_image['sizes']['large'] : (isset($hero_image['url']) ? $hero_image['url'] : ''));
            ?>
            <div class="hero-section-wrapper">
                <div class="container">
                    <div class="row">
                        <div class="col-12">
                            <div class="hero-section">
                                <div class="hero-video-container">
                                    <div class="hero-thumbnail" style="background-image: url('<?php echo esc_url($hero_src); ?>');"></div>
                                    <div class="hero-content-overlay">
                                        <h1 class="hero-episode-title"><?php the_title(); ?></h1>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else : ?>
            <!-- Fallback header if no hero image -->
            <div class="container py-5">
                <div class="text-center">
                    <h1 class="display-4 mb-4"><?php the_title(); ?></h1>
                </div>
            </div>
        <?php endif; ?>

        <!-- Model Profile Section -->
        <div class="container py-5">
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="text-center mb-4">
                        <?php 
                        $profile_image = get_field('model_profile_image');
                        if ($profile_image) :
                            $profile_id = isset($profile_image['ID']) ? (int)$profile_image['ID'] : 0;
                            if ($profile_id) {
                                echo wp_get_attachment_image($profile_id, 'model-portrait', false, array(
                                    'class' => 'img-fluid rounded',
                                    'alt' => get_the_title()
                                ));
                            } else {
                                echo '<img src="' . esc_url(isset($profile_image['url']) ? $profile_image['url'] : '') . '" alt="' . esc_attr(get_the_title()) . '" class="img-fluid rounded">';
                            }
                            ?>
                        <?php elseif (has_post_thumbnail()) : ?>
                            <?php the_post_thumbnail('model-portrait', array('class' => 'img-fluid rounded')); ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-lg-6 mb-4">
                    <div class="text-white">
                        <h2 class="h3 mb-4 text-white"><?php the_title(); ?></h2>
                        
                        <?php if (get_field('model_about')) : ?>
                            <div class="model-bio mb-4 text-white">
                                <?php echo wpautop(esc_html(get_field('model_about'))); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (get_the_content()) : ?>
                            <div class="model-additional-content mb-4 text-white">
                                <?php the_content(); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Model Details -->
                        <div class="model-details-section mb-5">
                           
                            <div class="model-details-grid">
                                <?php if (get_field('model_gender')) : ?>
                                    <div class="model-detail-item">
                                        <div class="model-detail-icon">
                                            <i class="fas fa-venus-mars"></i>
                                        </div>
                                        <div class="model-detail-content">
                                            <span class="model-detail-label">Gender</span>
                                            <span class="model-detail-value">
                                                <?php 
                                                $gender = get_field('model_gender');
                                                echo esc_html(ucwords(str_replace('-', ' ', $gender)));
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (get_field('model_birthdate')) : ?>
                                    <div class="model-detail-item">
                                        <div class="model-detail-icon">
                                            <i class="fas fa-birthday-cake"></i>
                                        </div>
                                        <div class="model-detail-content">
                                            <span class="model-detail-label">Date of Birth</span>
                                            <span class="model-detail-value">
                                                <?php 
                                                $date = DateTime::createFromFormat('Y-m-d', get_field('model_birthdate'));
                                                echo $date ? $date->format('F j, Y') : '';
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (get_field('model_height')) : ?>
                                    <div class="model-detail-item">
                                        <div class="model-detail-icon">
                                            <i class="fas fa-ruler-vertical"></i>
                                        </div>
                                        <div class="model-detail-content">
                                            <span class="model-detail-label">Height</span>
                                            <span class="model-detail-value"><?php echo esc_html(get_field('model_height')); ?></span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (get_field('model_measurements')) : ?>
                                    <div class="model-detail-item">
                                        <div class="model-detail-icon">
                                            <i class="fas fa-chart-line"></i>
                                        </div>
                                        <div class="model-detail-content">
                                            <span class="model-detail-label">Measurements</span>
                                            <span class="model-detail-value"><?php echo esc_html(get_field('model_measurements')); ?></span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
</div>
</div>
<div class="row">
    <div class="col-12">
                        <!-- Social Media Links -->
                        <?php 
                        $is_logged_in = is_user_logged_in();
                        $has_social = get_field('model_instagram') || get_field('model_twitter') || 
                                     get_field('model_tiktok') || get_field('model_onlyfans') || 
                                     get_field('model_website');
                        
                        if ($has_social) :
                        ?>
                        <div class="model-social-section <?php echo !$is_logged_in ? 'locked' : ''; ?>" data-logged-in="<?php echo $is_logged_in ? 'true' : 'false'; ?>">
                            <div class="model-social-header mb-4">
                                <h4 class="model-section-heading">Connect with <?php the_title(); ?></h4>
                            </div>
                            
                            <div class="model-social-grid <?php echo !$is_logged_in ? 'social-locked' : ''; ?>">
                                <?php if (get_field('model_instagram')) : ?>
                                    <?php if ($is_logged_in) : ?>
                                        <a href="<?php echo esc_url(get_field('model_instagram')); ?>" target="_blank" class="social-icon-link" data-platform="instagram" rel="noopener" title="Follow on Instagram">
                                            <i class="fab fa-instagram"></i>
                                            <span class="social-label">Instagram</span>
                                        </a>
                                    <?php else : ?>
                                        <button class="social-icon-link locked" data-platform="instagram" onclick="showLoginPrompt('instagram')" title="Login to view Instagram">
                                            <i class="fab fa-instagram"></i>
                                            <span class="social-label">Instagram</span>
                                            <i class="fas fa-lock social-lock-icon"></i>
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php if (get_field('model_twitter')) : ?>
                                    <?php if ($is_logged_in) : ?>
                                        <a href="<?php echo esc_url(get_field('model_twitter')); ?>" target="_blank" class="social-icon-link" data-platform="twitter" rel="noopener" title="Follow on Twitter/X">
                                            <i class="fab fa-x-twitter"></i>
                                            <span class="social-label">Twitter/X</span>
                                        </a>
                                    <?php else : ?>
                                        <button class="social-icon-link locked" data-platform="twitter" onclick="showLoginPrompt('twitter')" title="Login to view Twitter">
                                            <i class="fab fa-x-twitter"></i>
                                            <span class="social-label">Twitter/X</span>
                                            <i class="fas fa-lock social-lock-icon"></i>
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php if (get_field('model_tiktok')) : ?>
                                    <?php if ($is_logged_in) : ?>
                                        <a href="<?php echo esc_url(get_field('model_tiktok')); ?>" target="_blank" class="social-icon-link" data-platform="tiktok" rel="noopener" title="Follow on TikTok">
                                            <i class="fab fa-tiktok"></i>
                                            <span class="social-label">TikTok</span>
                                        </a>
                                    <?php else : ?>
                                        <button class="social-icon-link locked" data-platform="tiktok" onclick="showLoginPrompt('tiktok')" title="Login to view TikTok">
                                            <i class="fab fa-tiktok"></i>
                                            <span class="social-label">TikTok</span>
                                            <i class="fas fa-lock social-lock-icon"></i>
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php if (get_field('model_onlyfans')) : ?>
                                    <?php if ($is_logged_in) : ?>
                                        <a href="<?php echo esc_url(flexpress_append_onlyfans_referral(get_field('model_onlyfans'))); ?>" target="_blank" class="social-icon-link" data-platform="onlyfans" rel="noopener" title="Subscribe on OnlyFans">
                                            <i class="fas fa-heart"></i>
                                            <span class="social-label">OnlyFans</span>
                                        </a>
                                    <?php else : ?>
                                        <button class="social-icon-link locked" data-platform="onlyfans" onclick="showLoginPrompt('onlyfans')" title="Login to view OnlyFans">
                                            <i class="fas fa-heart"></i>
                                            <span class="social-label">OnlyFans</span>
                                            <i class="fas fa-lock social-lock-icon"></i>
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php if (get_field('model_website')) : ?>
                                    <?php if ($is_logged_in) : ?>
                                        <a href="<?php echo esc_url(get_field('model_website')); ?>" target="_blank" class="social-icon-link" data-platform="website" rel="noopener" title="Visit Website">
                                            <i class="fas fa-globe"></i>
                                            <span class="social-label">
                                                <?php 
                                                $website_title = get_field('model_website_title');
                                                echo esc_html($website_title ? $website_title : 'Website');
                                                ?>
                                            </span>
                                        </a>
                                    <?php else : ?>
                                        <button class="social-icon-link locked" data-platform="website" onclick="showLoginPrompt('website')" title="Login to view Website">
                                            <i class="fas fa-globe"></i>
                                            <span class="social-label">  <?php 
                                                $website_title = get_field('model_website_title');
                                                echo esc_html($website_title ? $website_title : 'Website');
                                                ?></span>
                                            <i class="fas fa-lock social-lock-icon"></i>
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php
        // Get latest episode featuring this model
        $latest_episode_query = new WP_Query(array(
            'post_type' => 'episode',
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key' => 'featured_models',
                    'value' => '"' . get_the_ID() . '"',
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => 'release_date',
                    'value' => current_time('mysql'),
                    'compare' => '<=',
                    'type' => 'DATETIME'
                )
            ),
            'meta_key' => 'release_date',
            'orderby' => 'meta_value',
            'order' => 'DESC'
        ));
        
        if ($latest_episode_query->have_posts()) : 
            $latest_episode_query->the_post();
            
            // Get video details for hero
            $preview_video = get_field('preview_video');
            $library_id = get_option('flexpress_video_bunnycdn_library_id');
            $token_key = get_option('flexpress_video_bunnycdn_token_key');
            
            // Generate thumbnail URL
            $thumbnail_url = flexpress_get_bunnycdn_thumbnail_url($preview_video);
            
            // Generate token for video if available
            $token = '';
            $expires = time() + 3600; // 1 hour
            if ($library_id && $token_key && $preview_video) {
                $token = hash('sha256', $token_key . $preview_video . $expires);
            }
        ?>
            <!-- Latest Scene Hero Section -->
            <div class="hero-section-wrapper">
                <div class="container">
                    <div class="row">
                        <div class="col-12 text-left mb-4">
                            <h2 class="section-title">Latest Scene</h2>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="hero-section">
                                <a href="<?php echo get_permalink(); ?>" class="hero-link">
                                    <?php if ($preview_video && $library_id): ?>
                                    <div class="hero-video-container" id="modelLatestVideo" 
                                         data-video-id="<?php echo esc_attr($preview_video); ?>"
                                         data-library-id="<?php echo esc_attr($library_id); ?>"
                                         data-token="<?php echo esc_attr($token); ?>"
                                         data-expires="<?php echo esc_attr($expires); ?>"
                                         data-thumbnail="<?php echo esc_url($thumbnail_url); ?>">
                                        <?php if ($thumbnail_url): ?>
                                        <div class="hero-thumbnail" style="background-image: url('<?php echo esc_url($thumbnail_url); ?>')"></div>
                                        <?php endif; ?>
                                        <div class="hero-transition-overlay"></div>
                                        <div class="hero-video-wrapper"></div>
                                    </div>
                                    <?php elseif ($thumbnail_url): ?>
                                    <div class="hero-video-container">
                                        <div class="hero-thumbnail" style="background-image: url('<?php echo esc_url($thumbnail_url); ?>')"></div>
                                    </div>
                                    <?php else: ?>
                                    <div class="hero-video-container">
                                        <?php flexpress_display_episode_thumbnail('hero-desktop', 'hero-thumbnail-fallback'); ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="hero-play-button">
                                        <i class="fa-solid fa-play"></i>
                                    </div>
                                    
                                    <div class="hero-content-overlay">
                                        <?php 
                                        $featured_models = get_field('featured_models');
                                        if ($featured_models && !empty($featured_models)): 
                                            $model_names = array();
                                            foreach ($featured_models as $model) {
                                                $model_names[] = $model->post_title;
                                            }
                                            $hero_performers = implode(', ', $model_names);
                                        ?>
                                        <div class="hero-model-name"><?php echo esc_html($hero_performers); ?></div>
                                        <?php endif; ?>
                                        <h3 class="hero-episode-title"><?php the_title(); ?></h3>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php 
        endif;
        wp_reset_postdata();
        ?>

        <!-- All Episodes Grid Section -->
        <?php
        $all_episodes_query = new WP_Query(array(
            'post_type' => 'episode',
            'posts_per_page' => 12,
            'meta_query' => array(
                array(
                    'key' => 'featured_models',
                    'value' => '"' . get_the_ID() . '"',
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => 'release_date',
                    'value' => current_time('mysql'),
                    'compare' => '<=',
                    'type' => 'DATETIME'
                )
            ),
            'meta_key' => 'release_date',
            'orderby' => 'meta_value',
            'order' => 'DESC'
        ));
        
        if ($all_episodes_query->have_posts()) : ?>
            <div class="container py-5">
                <div class="row">
                    <div class="col-12 text-left mb-5">
                        <h2 class="section-title">All <?php echo get_the_title(get_queried_object_id()); ?> Episodes</h2>
                    </div>
                </div>
                
                <!-- Episode Grid - Same style as episodes page -->
                <div class="video-grid">
                    <div class="row g-4">
                        <?php while ($all_episodes_query->have_posts()) : $all_episodes_query->the_post(); ?>
                            <div class="col-lg-6 col-md-6 col-sm-6">
                                <?php get_template_part('template-parts/content', 'episode-card'); ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                
                <?php if ($all_episodes_query->found_posts > 12) : ?>
                    <div class="text-center mt-5">
                        <p class="text-muted">Showing 12 of <?php echo $all_episodes_query->found_posts; ?> episodes</p>
                        <!-- You could add pagination or "Load More" here if needed -->
                    </div>
                <?php endif; ?>
            </div>
        <?php 
        endif;
        wp_reset_postdata();
        ?>

        <!-- Model Messages Section -->
        <?php 
        // Store the main post context before checking comments
        global $post;
        $main_post = $post;
        
        // Get membership status for access control
        $membership_status = function_exists('flexpress_get_membership_status') ? flexpress_get_membership_status() : 'none';
        $is_active_member = in_array($membership_status, ['active', 'cancelled']);
        
        if (comments_open($main_post->ID) || get_comments_number($main_post->ID)) : ?>
            <div class="container py-5">
                <div class="row">
                    <div class="col-lg-12 mx-auto">
                        <div class="text-white">
                            <h2 class="section-title text-left mb-5 text-white">
                                Leave <?php echo get_the_title($main_post->ID); ?> a Message
                            </h2>
                                
                                
                                <?php
                                // Display existing comments - Use direct comment count instead of have_comments()
                                $comment_count = get_comments_number($main_post->ID);
                                if ($comment_count > 0) : ?>
                                    <div class="model-messages-list mb-5">
                                        <h3 class="h4 mb-4">
                                            <?php
                                            $comments_number = get_comments_number($main_post->ID);
                                            if ($comments_number == 1) {
                                                echo '1 Member Message for ' . get_the_title($main_post->ID);
                                            } else {
                                                echo $comments_number . ' Member Messages for ' . get_the_title($main_post->ID);
                                            }
                                            ?>
                                        </h3>
                                        
                                        <?php 
                                        if ($comments_number > 0 && !$is_active_member && !current_user_can('administrator')) : ?>
                                        <div class="alert alert-info mb-4">
                                            <i class="fas fa-crown me-2"></i>
                                            <strong>Exclusive Member Messages:</strong> Only active members can send messages to models. Join our community to interact directly with your favorite performers!
                                        </div>
                                        <?php endif; ?>
                                        
                                        <ol class="commentlist">
                                            <?php
                                            // Get comments for this specific post
                                            $comments = get_comments(array(
                                                'post_id' => $main_post->ID,
                                                'status' => 'approve',
                                                'type' => 'comment'
                                            ));
                                            
                                            // Manually loop through comments
                                            foreach ($comments as $comment) {
                                                $GLOBALS['comment'] = $comment;
                                                flexpress_model_message_callback($comment, array('style' => 'ol'), 1);
                                            }
                                            ?>
                                        </ol>
                                        
                                        <?php
                                        // Comment pagination
                                        if (get_comment_pages_count() > 1 && get_option('page_comments')) :
                                            ?>
                                            <nav class="comment-navigation">
                                                <div class="nav-previous"><?php previous_comments_link(__('&larr; Older Messages')); ?></div>
                                                <div class="nav-next"><?php next_comments_link(__('Newer Messages &rarr;')); ?></div>
                                            </nav>
                                            <?php
                                        endif;
                                        ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php
                                // Check user access for commenting
                                $can_comment = false;
                                $comment_message = '';
                                
                                if (!is_user_logged_in()) {
                                    $comment_message = '<div class="alert alert-warning"><i class="fas fa-lock me-2"></i>You must <a href="/login/">log in</a> to send messages to ' . get_the_title($main_post->ID) . '.</div>';
                                } elseif (!$is_active_member) {
                                    if ($membership_status === 'cancelled') {
                                        $comment_message = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>Your membership has been cancelled. <a href="/membership/">Reactivate your membership</a> to send messages to models.</div>';
                                    } elseif ($membership_status === 'expired') {
                                        $comment_message = '<div class="alert alert-warning"><i class="fas fa-clock me-2"></i>Your membership has expired. <a href="/membership/">Renew your membership</a> to send messages to models.</div>';
                                    } else {
                                        $comment_message = '<div class="alert alert-info"><i class="fas fa-star me-2"></i>You need an active membership to send messages to models. <a href="/membership/">Join now</a> for exclusive access!</div>';
                                    }
                                } else {
                                    $can_comment = true;
                                }
                                
                                // Display comment form or access message
                                if (comments_open($main_post->ID) && $can_comment) {
                                    $model_name = get_the_title($main_post->ID);
                                    $comment_args = array(
                                        'title_reply'          => sprintf('Send %s a Message', $model_name),
                                        'title_reply_to'       => sprintf('Reply to Message for %s', $model_name),
                                        'comment_field'        => '<p class="comment-form-comment"><label for="comment">Your Message for ' . $model_name . ' <span class="required">*</span></label><textarea id="comment" name="comment" cols="45" rows="8" maxlength="65525" required="required" placeholder="Write your message to ' . $model_name . ' here..."></textarea></p>',
                                        'comment_notes_before' => '<p class="comment-notes">Your message will be public and visible to other fans of ' . $model_name . '. As an active member, you have exclusive access to interact with models.</p>',
                                        'comment_notes_after'  => '',
                                        'label_submit'         => sprintf('Send Message'),
                                        'submit_button'        => '<input name="%1$s" type="submit" id="%2$s" class="%3$s btn btn-primary btn-lg" value="%4$s" />',
                                        'class_submit'         => 'submit',
                                    );
                                    comment_form($comment_args, $main_post->ID);
                                } elseif (!comments_open($main_post->ID)) {
                                    echo '<p class="no-comments">Messages are currently closed for ' . get_the_title($main_post->ID) . '.</p>';
                                } else {
                                    echo $comment_message;
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </article>
<?php endwhile; ?>
</main>

<?php
get_footer();
?> 