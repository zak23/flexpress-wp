/**
 * Models Archive Lazy Loading with Infinite Scroll
 * 
 * @package FlexPress
 */

(function($) {
    'use strict';
    
    // Configuration
    const CONFIG = {
        container: '#models-container',
        loadingIndicator: '#models-loading',
        loadMoreButton: '#load-more-models',
        noMoreMessage: '#no-more-models',
        scrollThreshold: 300, // pixels from bottom to trigger load
        debounceDelay: 250 // milliseconds to debounce scroll events
    };
    
    let isLoading = false;
    let hasMoreModels = true;
    let currentPage = 1;
    let maxPages = 1;
    
    /**
     * Initialize the lazy loading system
     */
    function init() {
        const $container = $(CONFIG.container);
        
        if (!$container.length) {
            return; // Not on models archive page
        }
        
        // Get initial data
        currentPage = parseInt($container.data('page'), 10) || 1;
        maxPages = parseInt($container.data('max-pages'), 10) || 1;
        hasMoreModels = currentPage < maxPages;
        
        // Setup event listeners
        setupInfiniteScroll();
        setupLoadMoreButton();
        
        console.log('Models lazy loading initialized:', {
            currentPage,
            maxPages,
            hasMoreModels
        });
    }
    
    /**
     * Setup infinite scroll functionality
     */
    function setupInfiniteScroll() {
        let scrollTimeout;
        
        $(window).on('scroll', function() {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(function() {
                if (shouldLoadMore()) {
                    loadMoreModels();
                }
            }, CONFIG.debounceDelay);
        });
    }
    
    /**
     * Setup load more button as fallback
     */
    function setupLoadMoreButton() {
        $(CONFIG.loadMoreButton).on('click', function(e) {
            e.preventDefault();
            loadMoreModels();
        });
        
        // Show button if needed (fallback for slow connections)
        if (hasMoreModels) {
            setTimeout(function() {
                if (!isLoading && hasMoreModels) {
                    $(CONFIG.loadMoreButton).show();
                }
            }, 3000); // Show after 3 seconds if no auto-load
        }
    }
    
    /**
     * Check if we should load more models
     */
    function shouldLoadMore() {
        if (isLoading || !hasMoreModels) {
            return false;
        }
        
        const scrollTop = $(window).scrollTop();
        const windowHeight = $(window).height();
        const documentHeight = $(document).height();
        const distanceFromBottom = documentHeight - (scrollTop + windowHeight);
        
        return distanceFromBottom <= CONFIG.scrollThreshold;
    }
    
    /**
     * Load more models via AJAX
     */
    function loadMoreModels() {
        if (isLoading || !hasMoreModels) {
            return;
        }
        
        isLoading = true;
        const nextPage = currentPage + 1;
        
        // Show loading indicator
        $(CONFIG.loadingIndicator).show();
        $(CONFIG.loadMoreButton).hide();
        
        // AJAX request
        $.ajax({
            url: flexpress_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'load_more_models',
                page: nextPage,
                nonce: flexpress_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.html) {
                    // Append new models to container
                    const $newModels = $(response.html);
                    $(CONFIG.container).append($newModels);
                    
                    // Update state
                    currentPage = nextPage;
                    hasMoreModels = response.has_more;
                    
                    // Update container data attributes
                    $(CONFIG.container).data('page', currentPage);
                    
                    // Trigger custom event for potential integrations
                    $(document).trigger('modelsLoaded', [$newModels, currentPage]);
                    
                    console.log('Loaded page', currentPage, 'of', maxPages);
                    
                } else {
                    hasMoreModels = false;
                    console.log('No more models to load');
                }
                
                // Handle end state
                if (!hasMoreModels) {
                    $(CONFIG.noMoreMessage).show();
                    $(CONFIG.loadMoreButton).hide();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading models:', error);
                
                // Show load more button on error as fallback
                $(CONFIG.loadMoreButton).show();
                
                // Show user-friendly message
                const errorMsg = '<div class="alert alert-warning text-center mt-3">' +
                    '<i class="fas fa-exclamation-triangle"></i> ' +
                    'Unable to load more models. Please try again.' +
                    '</div>';
                $(CONFIG.container).after(errorMsg);
                
                // Remove error message after 5 seconds
                setTimeout(function() {
                    $('.alert-warning').fadeOut();
                }, 5000);
            },
            complete: function() {
                isLoading = false;
                $(CONFIG.loadingIndicator).hide();
            }
        });
    }
    
    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        // Only initialize if we have the necessary AJAX data
        if (typeof flexpress_ajax !== 'undefined') {
            init();
        } else {
            console.warn('FlexPress AJAX data not found - lazy loading disabled');
        }
    });
    
})(jQuery); 