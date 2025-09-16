/**
 * FlexPress main JavaScript file
 */
(function($) {
    'use strict';

    // FlexPress global object
    window.FlexPress = {
        // Initialize the application
        init: function() {
            this.initHeroVideo();
            this.initMobileMenu();
            this.setupEventHandlers();
        },

        // Initialize video player
        initVideoPlayer: function() {
            if ($('.episode-video').length) {
                // Video player initialization code
            }
        },
        
        // Setup event handlers
        setupEventHandlers: function() {
            $('.episode-card').on('mouseenter', function() {
                // Show preview if available
            }).on('mouseleave', function() {
                // Hide preview
            });
            
            $('.favorite-toggle').on('click', function(e) {
                e.preventDefault();
                // Toggle favorite state
            });
            
            $('.watchlist-toggle').on('click', function(e) {
                e.preventDefault();
                // Toggle watchlist state
            });
        },

        // Initialize trailer modal
        initTrailerModal: function() {
            $('#trailerModal').on('hidden.bs.modal', function() {
                // The iframe will be recreated on next show, no need to reset src
            });
        },

        // Initialize hero video (disabled - using new hero-video.js)
        initHeroVideo: function() {
            // Disabled - hero video functionality moved to hero-video.js
        },

        // Initialize mobile menu
        initMobileMenu: function() {
            $('.navbar-nav .menu-item-has-children > a').after('<span class="dropdown-toggle"></span>');
            
            $('.dropdown-toggle').on('click', function(e) {
                e.preventDefault();
                $(this).toggleClass('active').next('.sub-menu').slideToggle(200);
            });
            
            $(window).on('resize', function() {
                if ($(window).width() > 991) {
                    $('.sub-menu').removeAttr('style');
                    $('.dropdown-toggle').removeClass('active');
                }
            });
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        FlexPress.init();
    });
    
    // PPV Episode Unlock System
    window.FlexPressUnlock = {
        init: function() {
            this.bindPurchaseButtons();
        },
        
        bindPurchaseButtons: function() {
            $(document).on('click', '.purchase-btn', this.handlePurchaseClick.bind(this));
            $(document).on('click', '.gallery-preview-purchase', this.handleGalleryPurchaseClick.bind(this));
        },
        
        handlePurchaseClick: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const episodeId = $button.data('episode-id');
            const basePrice = parseFloat($button.data('original-price')); // Use original price for validation
            const finalPrice = parseFloat($button.data('price')); // This is the discounted price
            const memberDiscount = parseFloat($button.data('discount')) || 0;
            const isActiveMember = $button.data('is-active-member') === 'true';
            
            if (!episodeId || !basePrice) {
                alert('Invalid episode data. Please refresh the page and try again.');
                return;
            }
            
            // Check if user is logged in first - REQUIRED for PPV purchases
            if (typeof FlexPressData === 'undefined' || !FlexPressData.isLoggedIn) {
                // Redirect to login with return URL
                const currentUrl = window.location.href;
                const loginUrl = '/login?redirect_to=' + encodeURIComponent(currentUrl);
                
                if (confirm('You need to be logged in to purchase episodes. Redirect to login page?')) {
                    window.location.href = loginUrl;
                }
                return;
            }
            
            // Use the pre-calculated final price from the template
            // The template already applies member discounts correctly
            let discountText = '';
            if (memberDiscount > 0 && isActiveMember) {
                discountText = ` (${memberDiscount}% member discount applied)`;
            }
            
            // Process purchase directly without confirmation dialog
            
            this.processPurchase(episodeId, finalPrice, basePrice, memberDiscount, $button);
        },
        
        processPurchase: function(episodeId, finalPrice, basePrice, memberDiscount, $button) {
            // Disable button and show loading state
            const originalText = $button.text();
            $button.prop('disabled', true).text('Processing...');
            
            // Make AJAX request to create payment URL
            $.ajax({
                url: FlexPressData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'flexpress_create_ppv_purchase',
                    episode_id: episodeId,
                    final_price: finalPrice,
                    base_price: basePrice,
                    member_discount: memberDiscount,
                    nonce: FlexPressData.nonce
                },
                success: function(response) {
                    if (response.success && response.data.payment_url) {
                        // Redirect to payment URL
                        window.location.href = response.data.payment_url;
                    } else {
                        alert(response.data.message || 'Error creating payment. Please try again.');
                        $button.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    alert('Connection error. Please try again.');
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },
        
        handleGalleryPurchaseClick: function(e) {
            e.preventDefault();
            
            const $link = $(e.currentTarget);
            const episodeId = $link.data('episode-id');
            const price = parseFloat($link.data('price'));
            const accessType = $link.data('access-type');
            
            // Use the same purchase logic as the main purchase button
            this.createPayment(episodeId, price);
        }
    };
    
    // Initialize PPV unlock system
    $(document).ready(function() {
        FlexPressUnlock.init();
    });
    
})(jQuery);

// Add a SHA-256 hash function for client-side token generation
async function sha256(message) {
    // Encode the message as UTF-8
    const msgBuffer = new TextEncoder().encode(message);
    // Hash the message
    const hashBuffer = await crypto.subtle.digest('SHA-256', msgBuffer);
    // Convert ArrayBuffer to Array
    const hashArray = Array.from(new Uint8Array(hashBuffer));
    // Convert bytes to hex string
    const hashHex = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
    return hashHex;
}

// Generate BunnyCDN token
async function generateBunnyCDNToken(videoId) {
    // Make sure we have the required data and FlexPressData exists
    if (!FlexPressData || !FlexPressData.token || !FlexPressData.expires) {
        console.error('Missing token or expiration timestamp');
        return '';
    }
    
    // Use the pre-generated token from the server
    return FlexPressData.token;
}

(function($) {
    'use strict';

    // Video preview functionality
    const videoPreviews = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            $('.episode-card').on('mouseenter', this.handleMouseEnter.bind(this));
            $('.episode-card').on('mouseleave', this.handleMouseLeave.bind(this));
        },

        handleMouseEnter: function(e) {
            const $card = $(e.currentTarget);
            const videoId = $card.data('preview-video');
            const $thumbnail = $card.find('img[data-preview-url]');
            
            if (videoId) {
                this.createPreviewPlayer($card, videoId);
            }
            
            if ($thumbnail.length) {
                const previewUrl = $thumbnail.data('preview-url');
                if (previewUrl) {
                    // Always use the full URL as provided by the server
                    $thumbnail.attr('src', previewUrl);
                }
            }
        },

        handleMouseLeave: function(e) {
            const $card = $(e.currentTarget);
            const $thumbnail = $card.find('img[data-preview-url]');
            
            this.removePreviewPlayer($card);
            
            if ($thumbnail.length) {
                const originalSrc = $thumbnail.data('original-src');
                if (originalSrc) {
                    // Always use the full URL as provided by the server
                    $thumbnail.attr('src', originalSrc);
                }
            }
        },

        createPreviewPlayer: async function($card, videoId) {
            // If we don't have BunnyCDN URL or FlexPressData is not defined, don't try to create player
            if (!FlexPressData || !FlexPressData.bunnycdnUrl || !FlexPressData.libraryId) {
                console.error('Missing BunnyCDN configuration');
                return;
            }

            // Get the preview container
            const $previewContainer = $card.find('.preview-container');
            if (!$previewContainer.length) {
                $card.append('<div class="preview-container position-absolute top-0 start-0 w-100 h-100"></div>');
            }

            // Get token from server via AJAX for this specific video ID
            let token = '';
            let expires = '';
            
            try {
                const response = await $.ajax({
                    url: FlexPressData.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'flexpress_generate_bunnycdn_token',
                        video_id: videoId,
                        nonce: FlexPressData.nonce
                    }
                });
                
                if (response.success) {
                    token = response.data.token;
                    expires = response.data.expires;
                }
            } catch (error) {
                console.error('Error getting token:', error);
            }

            // Create video element
            const $video = $('<video>', {
                class: 'preview-video w-100 h-100 object-fit-cover',
                muted: true,
                loop: true,
                playsinline: true,
                autoplay: false
            });

            // Create source element with token
            let videoSrc = 'https://' + FlexPressData.bunnycdnUrl + '/play/' + FlexPressData.libraryId + '/' + videoId;
            if (token && expires) {
                videoSrc += '?token=' + token + '&expires=' + expires;
            }

            const $source = $('<source>', {
                src: videoSrc,
                type: 'video/mp4'
            });

            // Append source to video
            $video.append($source);

            // Add video to card
            $card.find('.preview-container').append($video);

            // Try playing video with a delay and error handling
            setTimeout(function() {
                try {
                    const playPromise = $video[0].play();
                    if (playPromise !== undefined) {
                        playPromise.catch(function(error) {
                            console.log('Auto-play prevented:', error);
                        });
                    }
                } catch(e) {
                    console.log('Error playing video:', e);
                }
            }, 100);
        },

        removePreviewPlayer: function($card) {
            const $video = $card.find('.preview-video');
            if ($video.length) {
                $video[0].pause();
                $video.remove();
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        videoPreviews.init();
    });
})(jQuery);

/**
 * FlexPress Main JavaScript
 * Handles video preview and other interactive elements
 */

(function($) {
    'use strict';
    
    /**
     * Initialize mobile menu
     */
    function initMobileMenu() {
        // Add dropdown toggle for submenus in mobile view
        $('.navbar-nav .menu-item-has-children > a').after('<span class="dropdown-toggle"></span>');
        
        // Handle dropdown toggle click
        $('.dropdown-toggle').on('click', function(e) {
            e.preventDefault();
            $(this).toggleClass('active').next('.sub-menu').slideToggle(200);
        });
        
        // Handle window resize
        $(window).on('resize', function() {
            if ($(window).width() > 991) {
                $('.sub-menu').removeAttr('style');
                $('.dropdown-toggle').removeClass('active');
            }
        });
    }
    
})(jQuery);
 
// Plan Type Switching
$(document).on('change', '[name="plan_type"]', function() {
    const planType = $(this).val();
    const $recurringPlans = $('#recurring-plans');
    const $onetimePlans = $('#onetime-plans');
    
    // Hide all plan groups first
    $('.plan-group').removeClass('active').hide();
    
    // Show selected plan group with animation
    if (planType === 'recurring') {
        $recurringPlans.addClass('active').fadeIn();
        // Uncheck any selected one-time plans
        $onetimePlans.find('input[type="radio"]').prop('checked', false);
    } else {
        $onetimePlans.addClass('active').fadeIn();
        // Uncheck any selected recurring plans
        $recurringPlans.find('input[type="radio"]').prop('checked', false);
    }
    
    // Clear any error messages
    $('.alert-danger').fadeOut();
});

// Ensure plan type matches selected plan
$(document).on('change', '[name="selected_plan"]', function() {
    const $selectedPlan = $(this).closest('.plan-card');
    const planType = $selectedPlan.data('plan-type');
    
    // Update plan type tabs
    if (planType === 'one_time') {
        $('#onetime-tab').prop('checked', true).trigger('change');
    } else {
        $('#recurring-tab').prop('checked', true).trigger('change');
    }
});
 