/**
 * FlexPress Hero Video - Clean Rebuild
 * Shows thumbnail for 5 seconds, then switches to video element
 */

/**
 * Hero Video Initialization
 * 
 * Handles the transition from thumbnail to video after a delay
 * and manages hover interactions.
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialize hero videos
    initializeHeroVideo('heroVideo');
    initializeHeroVideo('upcomingHeroVideo', true);
});

/**
 * Initialize a hero video section
 * @param {string} containerId - The ID of the hero video container
 * @param {boolean} isUpcoming - Whether this is an upcoming episode (optional)
 */
function initializeHeroVideo(containerId, isUpcoming = false) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const thumbnail = container.querySelector('.hero-thumbnail');
    const video = container.querySelector('.hero-video');
    
    if (!video || !thumbnail) return;

    // If this is an upcoming episode, don't start the video
    if (isUpcoming) return;

    // Preload the video
    video.load();

    // After 5 seconds, fade out thumbnail and start video
    setTimeout(() => {
        // Ensure video is ready to play
        if (video.readyState >= 3) {
            startVideoTransition();
        } else {
            // Wait for video to be ready
            video.addEventListener('canplay', startVideoTransition, { once: true });
        }
    }, 5000);

    function startVideoTransition() {
        // Show video behind thumbnail
        video.style.display = 'block';
        video.style.opacity = '0';
        
        // Start playing
        video.play().catch(() => {
            // If autoplay fails, keep thumbnail visible
            return;
        });

        // Fade out thumbnail, fade in video
        requestAnimationFrame(() => {
            thumbnail.style.opacity = '0';
            video.style.opacity = '1';
            
            // Remove thumbnail after transition
            setTimeout(() => {
                thumbnail.style.display = 'none';
            }, 1000); // Match CSS transition duration
        });
    }

    // Handle hover effects
    const playButton = container.querySelector('.hero-play-button');
    const link = container.closest('.hero-link');
    
    if (playButton && link) {
        link.addEventListener('mouseenter', () => {
            playButton.style.opacity = '1';
            playButton.style.transform = 'scale(1)';
        });

        link.addEventListener('mouseleave', () => {
            playButton.style.opacity = '0';
            playButton.style.transform = 'scale(0.8)';
        });
    }
}

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        const heroContainer = document.getElementById('heroVideo');
        if (!heroContainer) return;

        const thumbnail = heroContainer.querySelector('.hero-thumbnail');
        const video = heroContainer.querySelector('.hero-video');
        
        if (!video) {
            console.log('[Hero Video] No video element found');
            return;
        }

        console.log('[Hero Video] Initializing...');

        // Wait 5 seconds, then transition to video
        setTimeout(function() {
            console.log('[Hero Video] Starting transition to video...');
            
            // Fade out thumbnail
            if (thumbnail) {
                thumbnail.style.transition = 'opacity 0.5s ease';
                thumbnail.style.opacity = '0';
            }

            // Show and play video
            video.style.display = 'block';
            video.style.opacity = '0';
            video.style.transition = 'opacity 0.5s ease';
            
            // Start playing
            const playPromise = video.play();
            
            if (playPromise !== undefined) {
                playPromise.then(function() {
                    console.log('[Hero Video] Video playing successfully');
                    // Fade in video
                    setTimeout(function() {
                        video.style.opacity = '1';
                        // Hide thumbnail completely
                        if (thumbnail) {
                            thumbnail.style.display = 'none';
                        }
                    }, 100);
                }).catch(function(error) {
                    console.error('[Hero Video] Play failed:', error);
                    // If autoplay fails, show video anyway (user can click to play)
                    video.style.opacity = '1';
                    if (thumbnail) {
                        thumbnail.style.display = 'none';
                    }
                });
            }
        }, 5000);

        // Handle upcoming hero video if present
        const upcomingContainer = document.getElementById('upcomingHeroVideo');
        if (upcomingContainer) {
            const upcomingThumbnail = upcomingContainer.querySelector('.hero-thumbnail');
            const upcomingVideo = upcomingContainer.querySelector('.hero-video');
            
            if (upcomingVideo) {
                setTimeout(function() {
                    console.log('[Hero Video] Starting upcoming video transition...');
                    
                    if (upcomingThumbnail) {
                        upcomingThumbnail.style.transition = 'opacity 0.5s ease';
                        upcomingThumbnail.style.opacity = '0';
                    }

                    upcomingVideo.style.display = 'block';
                    upcomingVideo.style.opacity = '0';
                    upcomingVideo.style.transition = 'opacity 0.5s ease';
                    
                    const playPromise = upcomingVideo.play();
                    
                    if (playPromise !== undefined) {
                        playPromise.then(function() {
                            setTimeout(function() {
                                upcomingVideo.style.opacity = '1';
                                if (upcomingThumbnail) {
                                    upcomingThumbnail.style.display = 'none';
                                }
                            }, 100);
                        }).catch(function(error) {
                            console.error('[Hero Video] Upcoming play failed:', error);
                            upcomingVideo.style.opacity = '1';
                            if (upcomingThumbnail) {
                                upcomingThumbnail.style.display = 'none';
                            }
                        });
                    }
                }, 5000);
            }
        }
    });
})(); 