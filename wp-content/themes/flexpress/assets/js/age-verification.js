/**
 * Age Verification Modal
 * Handles the age verification popup with localStorage persistence
 */

(function() {
    'use strict';

    // Configuration
    const STORAGE_KEY = 'flexpress_age_verified';
    const MODAL_ID = 'age-verification-modal';
    
    // DOM elements
    let modal = null;
    let agreeButton = null;
    let exitButton = null;
    
    /**
     * Initialize the age verification system
     */
    function init() {
        // Check if user has already verified age
        if (hasVerifiedAge()) {
            return;
        }
        
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', showModal);
        } else {
            showModal();
        }
    }
    
    /**
     * Check if user has already verified their age
     * @returns {boolean}
     */
    function hasVerifiedAge() {
        try {
            return localStorage.getItem(STORAGE_KEY) === 'true';
        } catch (e) {
            // If localStorage is not available, always show modal
            console.warn('localStorage not available, showing age verification modal');
            return false;
        }
    }
    
    /**
     * Mark age as verified in localStorage
     */
    function markAgeAsVerified() {
        try {
            localStorage.setItem(STORAGE_KEY, 'true');
        } catch (e) {
            console.warn('Could not save age verification to localStorage:', e);
        }
    }
    
    /**
     * Show the age verification modal
     */
    function showModal() {
        modal = document.getElementById(MODAL_ID);
        if (!modal) {
            console.error('Age verification modal not found in DOM');
            return;
        }
        
        // Get button elements
        agreeButton = modal.querySelector('#age-verification-agree');
        exitButton = modal.querySelector('#age-verification-exit');
        
        if (!agreeButton || !exitButton) {
            console.error('Age verification buttons not found');
            return;
        }
        
        // Add event listeners
        agreeButton.addEventListener('click', handleAgree);
        exitButton.addEventListener('click', handleExit);
        
        // Prevent body scroll
        document.body.classList.add('age-verification-modal-open');
        
        // Show modal with animation
        modal.classList.add('show');
        
        // Focus on agree button for accessibility
        agreeButton.focus();
        
        // Add keyboard event listener
        document.addEventListener('keydown', handleKeydown);
    }
    
    /**
     * Hide the age verification modal
     */
    function hideModal() {
        if (!modal) return;
        
        // Remove event listeners
        agreeButton.removeEventListener('click', handleAgree);
        exitButton.removeEventListener('click', handleExit);
        document.removeEventListener('keydown', handleKeydown);
        
        // Remove body scroll prevention
        document.body.classList.remove('age-verification-modal-open');
        
        // Hide modal
        modal.classList.remove('show');
        
        // Clean up references
        modal = null;
        agreeButton = null;
        exitButton = null;
    }
    
    /**
     * Handle agree button click
     */
    function handleAgree() {
        markAgeAsVerified();
        hideModal();
        
        // Dispatch custom event for other scripts to listen to
        document.dispatchEvent(new CustomEvent('flexpress:ageVerified'));
    }
    
    /**
     * Handle exit button click
     */
    function handleExit() {
        // Redirect to a safe page or close the tab
        if (window.history.length > 1) {
            window.history.back();
        } else {
            // If no history, redirect to a safe page
            window.location.href = 'https://www.google.com';
        }
    }
    
    /**
     * Handle keyboard events
     * @param {KeyboardEvent} e
     */
    function handleKeydown(e) {
        // ESC key closes modal (but doesn't verify age)
        if (e.key === 'Escape') {
            handleExit();
        }
        
        // Enter key on agree button
        if (e.key === 'Enter' && document.activeElement === agreeButton) {
            handleAgree();
        }
        
        // Enter key on exit button
        if (e.key === 'Enter' && document.activeElement === exitButton) {
            handleExit();
        }
    }
    
    /**
     * Reset age verification (for testing purposes)
     * Call this from browser console: flexpressAgeVerification.reset()
     */
    function reset() {
        try {
            localStorage.removeItem(STORAGE_KEY);
            console.log('Age verification reset. Refresh the page to see the modal again.');
        } catch (e) {
            console.error('Could not reset age verification:', e);
        }
    }
    
    /**
     * Check current verification status
     * Call this from browser console: flexpressAgeVerification.status()
     */
    function status() {
        const verified = hasVerifiedAge();
        console.log('Age verification status:', verified ? 'Verified' : 'Not verified');
        return verified;
    }
    
    // Expose functions to global scope for debugging
    window.flexpressAgeVerification = {
        reset: reset,
        status: status,
        hasVerifiedAge: hasVerifiedAge
    };
    
    // Initialize when script loads
    init();
    
})();
