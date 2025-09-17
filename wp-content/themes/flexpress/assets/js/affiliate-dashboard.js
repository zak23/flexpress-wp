/**
 * FlexPress Affiliate Dashboard JavaScript
 * 
 * @package FlexPress
 * @since 1.0.0
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        initAffiliateDashboard();
    });

    function initAffiliateDashboard() {
        initCopyButtons();
        loadAffiliateStats();
    }

    function initCopyButtons() {
        $('.copy-link-button').on('click', function(e) {
            e.preventDefault();
            
            var button = $(this);
            var targetId = button.data('copy-target');
            var input = $('#' + targetId);
            
            if (input.length) {
                input.select();
                input[0].setSelectionRange(0, 99999);
                
                try {
                    var successful = document.execCommand('copy');
                    if (successful) {
                        showCopySuccess(button);
                    } else {
                        showCopyError(button);
                    }
                } catch (err) {
                    showCopyError(button);
                }
            }
        });
    }

    function showCopySuccess(button) {
        var originalText = button.text();
        button.text('Copied!').addClass('copied');
        
        setTimeout(function() {
            button.text(originalText).removeClass('copied');
        }, 2000);
    }

    function showCopyError(button) {
        var originalText = button.text();
        button.text('Failed').addClass('error');
        
        setTimeout(function() {
            button.text(originalText).removeClass('error');
        }, 2000);
    }

    function loadAffiliateStats() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_affiliate_dashboard_data',
                nonce: flexpressAffiliate.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateDashboardStats(response.data);
                }
            }
        });
    }

    function updateDashboardStats(data) {
        if (data.stats) {
            $('.stat-card').each(function() {
                var card = $(this);
                var label = card.find('h3').text().toLowerCase();
                
                if (label.includes('clicks') && data.stats.clicks !== undefined) {
                    card.find('.stat-number').text(data.stats.clicks.toLocaleString());
                } else if (label.includes('conversions') && data.stats.conversions !== undefined) {
                    card.find('.stat-number').text(data.stats.conversions.toLocaleString());
                } else if (label.includes('revenue') && data.stats.revenue !== undefined) {
                    card.find('.stat-number').text('$' + data.stats.revenue.toFixed(2));
                } else if (label.includes('commission') && data.stats.commission !== undefined) {
                    card.find('.stat-number').text('$' + data.stats.commission.toFixed(2));
                }
            });
        }
    }

})(jQuery);