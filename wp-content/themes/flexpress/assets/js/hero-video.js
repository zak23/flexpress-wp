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
            playButton.style.transform = 'translate(-50%, -50%) scale(1)';
        });

        link.addEventListener('mouseleave', () => {
            playButton.style.opacity = '0';
            playButton.style.transform = 'translate(-50%, -50%) scale(0.8)';
        });
    }
} 