    </div><!-- #content -->

    <footer class="bg-black text-white">
        <div class="container">
            <div class="row footer justify-content-md-center py-5">
                
                <!-- Logo Section -->
                <div class="col-12 col-lg-3 mb-4 mb-lg-0 text-center">
                    <?php flexpress_display_logo(); ?>
                </div>

                <!-- Menu Sections -->
                <div class="col-lg-6 col-sm-12 text-lg-start text-center">
                    <div class="row">
                        <div class="col-md-4">
                            <h4 class="text-uppercase mb-3">Menu</h4>
                            <?php
                            wp_nav_menu(array(
                                'theme_location' => 'footer-menu',
                                'menu_class' => 'footer-menu list-unstyled',
                                'add_li_class' => 'mb-2',
                                'link_class' => 'text-white text-decoration-none',
                                'container' => false,
                                'fallback_cb' => false,
                            ));
                            ?>
                        </div>
                        
                        <div class="col-md-4">
                            <h4 class="text-uppercase mb-3">Support</h4>
                            <?php
                            wp_nav_menu(array(
                                'theme_location' => 'footer-support-menu',
                                'menu_class' => 'footer-menu list-unstyled',
                                'add_li_class' => 'mb-2',
                                'link_class' => 'text-white text-decoration-none',
                                'container' => false,
                                'fallback_cb' => false,
                            ));
                            ?>
                        </div>
                        
                        <div class="col-md-4">
                            <h4 class="text-uppercase mb-3">Legal</h4>
                            <?php
                            wp_nav_menu(array(
                                'theme_location' => 'footer-legal-menu',
                                'menu_class' => 'footer-menu list-unstyled',
                                'add_li_class' => 'mb-2',
                                'link_class' => 'text-white text-decoration-none',
                                'container' => false,
                                'fallback_cb' => false,
                            ));
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Social and Friends Section -->
                <div class="col-lg-3 col-sm-12 text-lg-start text-center">
                    <h4 class="text-uppercase mb-3">Follow Us:</h4>
                    <?php if (flexpress_has_social_media_links()) : ?>
                        <div class="social-icons d-flex justify-content-lg-start justify-content-center mb-4">
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
                    <?php endif; ?>
                    
                    <h4 class="text-uppercase mb-3">Our Friends</h4>
                    <?php
                    wp_nav_menu(array(
                        'theme_location' => 'footer-friends-menu',
                        'menu_class' => 'footer-menu list-unstyled',
                        'add_li_class' => 'mb-2',
                        'link_class' => 'text-white text-decoration-none',
                        'container' => false,
                        'fallback_cb' => false,
                    ));
                    ?>
                </div>
            </div>

            <!-- RTA Compliance Logo -->
            <div class="row">
                <div class="col-12 text-center">
                    <a href="https://www.rtalabel.org/" target="_blank" rel="nofollow" class="d-block">
                        <img src="<?php echo get_template_directory_uri(); ?>/assets/images/120x60_RTA-5042-1996-1400-1577-RTA_c.gif" 
                             class="img-fluid" 
                             alt="RTA" 
                             style="max-height: 60px;">
                    </a>
                </div>
            </div>

            <!-- Payment Methods -->
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-center gap-2 mt-4">
                        <div class="d-flex bg-white p-1 rounded shadow" style="width: 48px; height: 32px;">
                            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/ma_symbol.svg"
                                alt="Mastercard"
                                class="m-auto"
                                style="max-height: 24px;">
                        </div>
                        <div class="d-flex bg-white p-1 rounded shadow" style="width: 48px; height: 32px;">
                            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/Visa_Brandmark_Blue_RGB_2021.png"
                                alt="Visa"
                                class="m-auto"
                                style="max-height: 12px;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Text Section -->
            <div class="row bottom-text">
                <div class="col-12 text-center mt-4">
                    <div class="powered-by mb-3">
                        Powered by <a href="https://exclusv.life/" target="_blank" title="Powered by Exclusv.Life" class="text-white">Exclusv.Life</a>
                        <br>
                        <a href="https://exclusv.life/" title="Powered by Exclusv.Life" target="_blank">
                            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/exclusv-logo-white.svg" 
                                 alt="Exclusv.Life" 
                                 class="img-fluid" 
                                 style="max-height: 24px;">
                        </a>
                    </div>
                    
                    <div class="copyright mb-3">
                        &copy; 2023 - <?php echo date("Y"); ?> <?php echo esc_html(get_bloginfo('name')); ?>
                        <br>
                        Handcoded with ♥ by <a href="https://zakozbourne.com/" target="_blank" title="Adult Web Developer Zak Ozbourne" class="text-white">Zak Ozbourne</a>
                    </div>
                    
                    <!-- Small and uppercase -->
                    <div class="legal-text small text-uppercase">
                        FOR INQUIRIES OR TO CANCEL YOUR MEMBERSHIP, PLEASE VISIT: 
                        <a href="https://www.vtsup.com/en/" title="VTSUP" class="text-white" target="_blank" rel="nofollow">VTSUP</a>
                        <br>
                        <?php if (flexpress_has_business_info()) : ?>
                            <span class="text-muted">
                                <?php echo esc_html(strtoupper(flexpress_get_formatted_business_info())); ?>
                            </span>
                        <?php endif; ?>
                        <br>
                        <span class="text-muted small">
                            All performers appearing on this website are 18 years or older.<br>
                            All video, images, design, graphics are copyright.
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </footer><!-- #colophon -->
</div><!-- #page -->

<?php wp_footer(); ?>

<!-- Age Verification Modal -->
<div id="age-verification-modal" class="age-verification-modal">
    <div class="age-verification-modal-content">
        <div class="age-verification-modal-body">
            <?php
            // Get the custom logo or fallback to site title
            $logo_data = flexpress_get_custom_logo();
            if ($logo_data && isset($logo_data['url'])) {
                echo '<img src="' . esc_url($logo_data['url']) . '" alt="' . esc_attr(get_bloginfo('name')) . '" class="age-verification-modal-logo">';
            } else {
                echo '<div class="age-verification-modal-logo" style="background: var(--color-text); color: var(--color-accent); display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 24px;">' . esc_html(get_bloginfo('name')) . '</div>';
            }
            ?>
            <h3 class="age-verification-modal-title">Age Verification Required</h3>
            <p class="age-verification-modal-text">
                This website contains sensitive material that in some jurisdictions accessing this content might be considered violating prevailing law and regulation. By entering this website you are accepting responsibility, and confirming your age is considered 'adult' in your actual location and you are able to legally access this material responsibly.
            </p>
            <div class="age-verification-modal-buttons">
                <button type="button" class="age-verification-btn age-verification-btn-primary" id="age-verification-agree">
                    I am 18 or older - Enter
                </button>
                <button type="button" class="age-verification-btn age-verification-btn-secondary" id="age-verification-exit">
                    Exit Site
                </button>
            </div>
        </div>
    </div>
</div>

</body>
</html> 