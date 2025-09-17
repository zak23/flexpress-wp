/**
 * FlexPress Promo Codes Admin JavaScript
 * Handles admin interface interactions for promo codes management
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Global variables
    var currentPromoId = null;
    var promoTable = $('#promo-codes-list');
    
    // Initialize
    init();
    
    function init() {
        bindEvents();
        setupDataTables();
        loadPromoCodes();
    }
    
    function bindEvents() {
        // Add new promo button
        $('#add-new-promo').on('click', function() {
            openPromoModal();
        });
        
        // Modal close buttons
        $('.modal-close').on('click', function() {
            closeModal($(this).closest('.promo-modal'));
        });
        
        // Close modal when clicking outside
        $('.promo-modal').on('click', function(e) {
            if (e.target === this) {
                closeModal($(this));
            }
        });
        
        // Form submission
        $('#promo-form').on('submit', function(e) {
            e.preventDefault();
            savePromoCode();
        });
        
        // Discount type change handler
        $('#discount-type').on('change', function() {
            updateDiscountDescription();
        });
        
        // View promo button
        $(document).on('click', '.view-promo', function() {
            var promoId = $(this).data('id');
            viewPromoDetails(promoId);
        });
        
        // Edit promo button
        $(document).on('click', '.edit-promo', function() {
            var promoId = $(this).data('id');
            editPromoCode(promoId);
        });
        
        // Delete promo button
        $(document).on('click', '.delete-promo', function() {
            var promoId = $(this).data('id');
            deletePromoCode(promoId);
        });
        
        // Toggle status button
        $(document).on('click', '.toggle-status', function() {
            var promoId = $(this).data('id');
            var newStatus = $(this).data('status');
            togglePromoStatus(promoId, newStatus);
        });
        
        // Select all checkbox
        $('#select-all-promos').on('change', function() {
            var isChecked = $(this).is(':checked');
            $('.promo-checkbox').prop('checked', isChecked);
        });
        
        // Bulk actions
        $('#bulk-actions').on('click', function() {
            showBulkActions();
        });
        
        // Refresh button
        $('#refresh-promos').on('click', function() {
            loadPromoCodes();
        });
    }
    
    function setupDataTables() {
        // Initialize any data tables if needed
        if ($.fn.DataTable) {
            $('.promo-table').DataTable({
                pageLength: 25,
                order: [[0, 'desc']],
                responsive: true
            });
        }
    }
    
    function loadPromoCodes() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_promo_codes_list',
                nonce: flexpressPromo.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderPromoCodesTable(response.data);
                } else {
                    showMessage('Error loading promo codes: ' + response.data.message, 'error');
                }
            },
            error: function() {
                showMessage('An error occurred while loading promo codes.', 'error');
            }
        });
    }
    
    function renderPromoCodesTable(promoCodes) {
        var tbody = promoTable.find('tbody');
        tbody.empty();
        
        if (promoCodes.length === 0) {
            tbody.append('<tr><td colspan="8">No promo codes found.</td></tr>');
            return;
        }
        
        $.each(promoCodes, function(index, promo) {
            var row = createPromoRow(promo);
            tbody.append(row);
        });
    }
    
    function createPromoRow(promo) {
        var statusClass = 'status-' + promo.status;
        var statusLabel = promo.status.charAt(0).toUpperCase() + promo.status.slice(1);
        
        // Format discount
        var discountDisplay = '';
        if (promo.discount_type === 'percentage') {
            discountDisplay = promo.discount_value + '%';
        } else if (promo.discount_type === 'fixed') {
            discountDisplay = '$' + parseFloat(promo.discount_value).toFixed(2);
        } else if (promo.discount_type === 'free_trial') {
            discountDisplay = promo.discount_value + ' days free';
        }
        
        // Format usage
        var usageDisplay = promo.usage_count;
        if (promo.usage_limit > 0) {
            usageDisplay += ' / ' + promo.usage_limit;
        }
        
        // Format valid until
        var validUntilDisplay = 'Never';
        if (promo.valid_until) {
            validUntilDisplay = formatDate(promo.valid_until);
        }
        
        var row = $('<tr>');
        row.append('<td><input type="checkbox" class="promo-checkbox" value="' + promo.id + '"></td>');
        row.append('<td><strong>' + escapeHtml(promo.code) + '</strong></td>');
        row.append('<td>' + escapeHtml(promo.name) + '</td>');
        row.append('<td>' + discountDisplay + '</td>');
        row.append('<td>' + usageDisplay + '</td>');
        row.append('<td><span class="status ' + statusClass + '">' + statusLabel + '</span></td>');
        row.append('<td>' + validUntilDisplay + '</td>');
        
        var actionsCell = $('<td>');
        actionsCell.append('<button type="button" class="button button-small view-promo" data-id="' + promo.id + '">View</button> ');
        actionsCell.append('<button type="button" class="button button-small edit-promo" data-id="' + promo.id + '">Edit</button> ');
        actionsCell.append('<button type="button" class="button button-small delete-promo" data-id="' + promo.id + '">Delete</button>');
        
        // Status toggle buttons
        if (promo.status === 'active') {
            actionsCell.append(' <button type="button" class="button button-secondary button-small toggle-status" data-id="' + promo.id + '" data-status="inactive">Deactivate</button>');
        } else if (promo.status === 'inactive') {
            actionsCell.append(' <button type="button" class="button button-primary button-small toggle-status" data-id="' + promo.id + '" data-status="active">Activate</button>');
        }
        
        row.append(actionsCell);
        
        return row;
    }
    
    function openPromoModal(promoId) {
        currentPromoId = promoId || null;
        
        if (promoId) {
            $('#modal-title').text('Edit Promo Code');
            loadPromoData(promoId);
        } else {
            $('#modal-title').text('Add New Promo Code');
            $('#promo-form')[0].reset();
            $('#promo-id').val('');
        }
        
        $('#promo-modal').fadeIn(300);
    }
    
    function loadPromoData(promoId) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_promo_details',
                promo_id: promoId,
                nonce: flexpressPromo.nonce
            },
            success: function(response) {
                if (response.success) {
                    populatePromoForm(response.data);
                } else {
                    showMessage('Error loading promo code: ' + response.data.message, 'error');
                }
            },
            error: function() {
                showMessage('An error occurred while loading promo code.', 'error');
            }
        });
    }
    
    function populatePromoForm(promo) {
        $('#promo-id').val(promo.id);
        $('#promo-code').val(promo.code);
        $('#promo-name').val(promo.name);
        $('#promo-description').val(promo.description);
        $('#discount-type').val(promo.discount_type);
        $('#discount-value').val(promo.discount_value);
        $('#minimum-amount').val(promo.minimum_amount);
        $('#maximum-discount').val(promo.maximum_discount);
        $('#usage-limit').val(promo.usage_limit);
        $('#user-limit').val(promo.user_limit);
        $('#valid-from').val(promo.valid_from ? formatDateTimeLocal(promo.valid_from) : '');
        $('#valid-until').val(promo.valid_until ? formatDateTimeLocal(promo.valid_until) : '');
        $('#promo-status').val(promo.status);
        
        updateDiscountDescription();
    }
    
    function savePromoCode() {
        var formData = new FormData($('#promo-form')[0]);
        formData.append('action', currentPromoId ? 'update_promo_code' : 'create_promo_code');
        formData.append('nonce', flexpressPromo.nonce);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    closeModal($('#promo-modal'));
                    loadPromoCodes();
                } else {
                    showMessage('Error: ' + response.data.message, 'error');
                }
            },
            error: function() {
                showMessage('An error occurred. Please try again.', 'error');
            }
        });
    }
    
    function viewPromoDetails(promoId) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_promo_details',
                promo_id: promoId,
                nonce: flexpressPromo.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayPromoDetails(response.data);
                } else {
                    showMessage('Error loading promo details: ' + response.data.message, 'error');
                }
            },
            error: function() {
                showMessage('An error occurred while loading promo details.', 'error');
            }
        });
    }
    
    function displayPromoDetails(promo) {
        var content = '<div class="promo-details">';
        content += '<h3>' + escapeHtml(promo.name) + '</h3>';
        content += '<p><strong>Code:</strong> ' + escapeHtml(promo.code) + '</p>';
        content += '<p><strong>Description:</strong> ' + escapeHtml(promo.description || 'None') + '</p>';
        content += '<p><strong>Discount:</strong> ' + formatDiscount(promo) + '</p>';
        content += '<p><strong>Status:</strong> <span class="status status-' + promo.status + '">' + promo.status.charAt(0).toUpperCase() + promo.status.slice(1) + '</span></p>';
        content += '<p><strong>Usage:</strong> ' + promo.usage_count + (promo.usage_limit > 0 ? ' / ' + promo.usage_limit : '') + '</p>';
        content += '<p><strong>User Limit:</strong> ' + (promo.user_limit > 0 ? promo.user_limit : 'Unlimited') + '</p>';
        content += '<p><strong>Minimum Amount:</strong> $' + parseFloat(promo.minimum_amount).toFixed(2) + '</p>';
        if (promo.maximum_discount > 0) {
            content += '<p><strong>Maximum Discount:</strong> $' + parseFloat(promo.maximum_discount).toFixed(2) + '</p>';
        }
        if (promo.valid_from) {
            content += '<p><strong>Valid From:</strong> ' + formatDate(promo.valid_from) + '</p>';
        }
        if (promo.valid_until) {
            content += '<p><strong>Valid Until:</strong> ' + formatDate(promo.valid_until) + '</p>';
        }
        content += '<p><strong>Created:</strong> ' + formatDate(promo.created_at) + '</p>';
        content += '</div>';
        
        $('#promo-details-content').html(content);
        $('#view-promo-modal').fadeIn(300);
    }
    
    function editPromoCode(promoId) {
        openPromoModal(promoId);
    }
    
    function deletePromoCode(promoId) {
        if (!confirm('Are you sure you want to delete this promo code? This action cannot be undone.')) {
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_promo_code',
                promo_id: promoId,
                nonce: flexpressPromo.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    loadPromoCodes();
                } else {
                    showMessage('Error: ' + response.data.message, 'error');
                }
            },
            error: function() {
                showMessage('An error occurred. Please try again.', 'error');
            }
        });
    }
    
    function togglePromoStatus(promoId, newStatus) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'toggle_promo_status',
                promo_id: promoId,
                status: newStatus,
                nonce: flexpressPromo.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    loadPromoCodes();
                } else {
                    showMessage('Error: ' + response.data.message, 'error');
                }
            },
            error: function() {
                showMessage('An error occurred. Please try again.', 'error');
            }
        });
    }
    
    function showBulkActions() {
        var selectedPromos = $('.promo-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
        
        if (selectedPromos.length === 0) {
            showMessage('Please select promo codes to perform bulk actions.', 'error');
            return;
        }
        
        // Show bulk actions modal or dropdown
        var actions = [
            { label: 'Activate Selected', action: 'activate' },
            { label: 'Deactivate Selected', action: 'deactivate' },
            { label: 'Delete Selected', action: 'delete' }
        ];
        
        // Implementation for bulk actions
        showMessage('Bulk actions for ' + selectedPromos.length + ' promo codes', 'info');
    }
    
    function updateDiscountDescription() {
        var type = $('#discount-type').val();
        var description = $('#discount-description');
        
        switch(type) {
            case 'percentage':
                description.text('Percentage discount (e.g., 20 for 20%)');
                break;
            case 'fixed':
                description.text('Fixed amount discount (e.g., 10.00)');
                break;
            case 'free_trial':
                description.text('Number of free trial days');
                break;
        }
    }
    
    function closeModal(modal) {
        modal.fadeOut(300);
    }
    
    function showMessage(message, type) {
        var messageClass = 'notice notice-' + (type === 'success' ? 'success' : type === 'error' ? 'error' : 'info');
        var messageHtml = '<div class="' + messageClass + ' is-dismissible"><p>' + escapeHtml(message) + '</p></div>';
        
        $('.wrap h1').after(messageHtml);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $('.notice').fadeOut();
        }, 5000);
    }
    
    function formatDiscount(promo) {
        switch(promo.discount_type) {
            case 'percentage':
                return promo.discount_value + '% off';
            case 'fixed':
                return '$' + parseFloat(promo.discount_value).toFixed(2) + ' off';
            case 'free_trial':
                return promo.discount_value + ' days free';
            default:
                return '';
        }
    }
    
    function formatDate(dateString) {
        var date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
    }
    
    function formatDateTimeLocal(dateString) {
        var date = new Date(dateString);
        return date.toISOString().slice(0, 16);
    }
    
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
});
