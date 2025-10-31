<?php
/**
 * Template Name: Affiliate Applications Management
 * 
 * Admin page for managing affiliate applications and accounts
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check if user has admin permissions
if (!flexpress_current_user_is_founder()) {
    wp_die('You do not have sufficient permissions to access this page.');
}

// Initialize affiliate settings
$affiliate_settings = new FlexPress_Affiliate_Settings();

get_header(); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="affiliate-applications-page">
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-users"></i>
                        Affiliate Applications & Account Management
                    </h1>
                    <p class="page-description">
                        Manage affiliate applications, approve/reject accounts, and monitor affiliate performance.
                    </p>
                </div>

                <!-- Statistics Overview -->
                <div class="stats-overview">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 class="stat-number" id="pending-count">0</h3>
                                    <p class="stat-label">Pending Applications</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 class="stat-number" id="active-count">0</h3>
                                    <p class="stat-label">Active Affiliates</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-ban"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 class="stat-number" id="suspended-count">0</h3>
                                    <p class="stat-label">Suspended Accounts</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 class="stat-number" id="total-revenue">$0</h3>
                                    <p class="stat-label">Total Revenue</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters and Actions -->
                <div class="filters-section">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <div class="filter-group">
                                <label for="status-filter">Filter by Status:</label>
                                <select id="status-filter" class="form-control">
                                    <option value="">All Statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="active">Active</option>
                                    <option value="suspended">Suspended</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="filter-group">
                                <label for="search-affiliates">Search Affiliates:</label>
                                <input type="text" id="search-affiliates" class="form-control" placeholder="Search by name, email, or code...">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="action-buttons">
                                <button type="button" class="btn btn-primary" id="add-new-affiliate">
                                    <i class="fas fa-plus"></i> Add New Affiliate
                                </button>
                                <button type="button" class="btn btn-secondary" id="export-affiliates">
                                    <i class="fas fa-download"></i> Export Data
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Affiliates Table -->
                <div class="affiliates-table-section">
                    <div class="table-header">
                        <h3>Affiliate Accounts</h3>
                        <div class="table-actions">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="refresh-table">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="affiliates-table">
                            <thead>
                                <tr>
                                    <th>Affiliate</th>
                                    <th>Contact</th>
                                    <th>Status</th>
                                    <th>Commission Rates</th>
                                    <th>Performance</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="affiliates-list">
                                <tr>
                                    <td colspan="6" class="text-center">
                                        <div class="loading-spinner">
                                            <i class="fas fa-spinner fa-spin"></i>
                                            Loading affiliates...
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="pagination-section">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <div class="pagination-info">
                                    Showing <span id="showing-start">0</span> to <span id="showing-end">0</span> 
                                    of <span id="total-count">0</span> affiliates
                                </div>
                            </div>
                            <div class="col-md-6">
                                <nav aria-label="Affiliates pagination">
                                    <ul class="pagination justify-content-end" id="pagination">
                                        <!-- Pagination will be generated by JavaScript -->
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bulk Actions -->
                <div class="bulk-actions-section" id="bulk-actions" style="display: none;">
                    <div class="bulk-actions-bar">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <span class="bulk-selection-info">
                                    <span id="selected-count">0</span> affiliates selected
                                </span>
                            </div>
                            <div class="col-md-6">
                                <div class="bulk-action-buttons">
                                    <button type="button" class="btn btn-sm btn-success" id="bulk-approve">
                                        <i class="fas fa-check"></i> Approve Selected
                                    </button>
                                    <button type="button" class="btn btn-sm btn-warning" id="bulk-suspend">
                                        <i class="fas fa-ban"></i> Suspend Selected
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" id="bulk-reject">
                                        <i class="fas fa-times"></i> Reject Selected
                                    </button>
                                    <button type="button" class="btn btn-sm btn-secondary" id="clear-selection">
                                        <i class="fas fa-times"></i> Clear Selection
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add New Affiliate Modal -->
<div id="add-affiliate-modal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus"></i> Add New Affiliate
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="add-affiliate-form">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="add-affiliate-name">Affiliate Name *</label>
                                <input type="text" class="form-control" id="add-affiliate-name" name="display_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="affiliate-email">Email Address *</label>
                                <input type="email" class="form-control" id="affiliate-email" name="email" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="affiliate-website">Website/Social Media</label>
                                <input type="url" class="form-control" id="affiliate-website" name="website" placeholder="https://example.com">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="payout-method">Payout Method *</label>
                                <select class="form-control" id="payout-method" name="payout_method" required>
                                    <option value="">Select payout method</option>
                                    <option value="paypal">PayPal (Free)</option>
                                    <option value="crypto">Cryptocurrency (Free)</option>
                                    <option value="aus_bank_transfer">Australian Bank Transfer (Free)</option>
                                    <option value="yoursafe">Yoursafe (Free)</option>
                                    <option value="ach">ACH - US Only ($10 USD Fee)</option>
                                    <option value="swift">Swift International ($30 USD Fee)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="payout-details">Payout Details *</label>
                        <input type="text" class="form-control" id="payout-details" name="payout_details" required placeholder="PayPal email, bank account, etc.">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="commission-initial">Initial Commission (%)</label>
                                <input type="number" class="form-control" id="commission-initial" name="commission_initial" min="0" max="100" step="0.1" value="25">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="commission-rebill">Rebill Commission (%)</label>
                                <input type="number" class="form-control" id="commission-rebill" name="commission_rebill" min="0" max="100" step="0.1" value="10">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="commission-unlock">Unlock Commission (%)</label>
                                <input type="number" class="form-control" id="commission-unlock" name="commission_unlock" min="0" max="100" step="0.1" value="15">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="payout-threshold">Payout Threshold ($)</label>
                                <input type="number" class="form-control" id="payout-threshold" name="payout_threshold" min="0" step="0.01" value="100">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="affiliate-status">Initial Status</label>
                                <select class="form-control" id="affiliate-status" name="status">
                                    <option value="pending">Pending</option>
                                    <option value="active">Active</option>
                                    <option value="suspended">Suspended</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="affiliate-notes">Notes</label>
                        <textarea class="form-control" id="affiliate-notes" name="notes" rows="3" placeholder="Additional notes about this affiliate..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" form="add-affiliate-form" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Affiliate
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Status Update Modal -->
<div id="status-update-modal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit"></i> Update Affiliate Status
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="status-update-form">
                    <input type="hidden" id="status-affiliate-id" name="affiliate_id">
                    <input type="hidden" id="status-new-status" name="status">
                    
                    <div class="form-group">
                        <label for="status-notes">Reason/Notes</label>
                        <textarea class="form-control" id="status-notes" name="notes" rows="4" placeholder="Provide a reason for this status change..."></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Note:</strong> The affiliate will receive an email notification about this status change.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" form="status-update-form" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Status
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.affiliate-applications-page {
    padding: 20px;
    background: #f8f9fa;
    min-height: 100vh;
}

.page-header {
    background: white;
    padding: 30px;
    border-radius: 10px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.page-title {
    color: #2c3e50;
    margin-bottom: 10px;
    font-size: 2rem;
    font-weight: 600;
}

.page-title i {
    color: #3498db;
    margin-right: 10px;
}

.page-description {
    color: #7f8c8d;
    font-size: 1.1rem;
    margin: 0;
}

.stats-overview {
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-icon {
    font-size: 2.5rem;
    margin-right: 20px;
    color: #3498db;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #2c3e50;
    margin: 0;
}

.stat-label {
    color: #7f8c8d;
    margin: 0;
    font-size: 0.9rem;
}

.filters-section {
    background: white;
    padding: 25px;
    border-radius: 10px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.filter-group label {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 8px;
    display: block;
}

.action-buttons {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.affiliates-table-section {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.table-header {
    padding: 25px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.table-header h3 {
    margin: 0;
    color: #2c3e50;
    font-weight: 600;
}

.table-responsive {
    overflow-x: auto;
}

.table th {
    background: #f8f9fa;
    border-top: none;
    font-weight: 600;
    color: #2c3e50;
    padding: 15px;
}

.table td {
    padding: 15px;
    vertical-align: middle;
}

.status {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-active {
    background: #d4edda;
    color: #155724;
}

.status-suspended {
    background: #f8d7da;
    color: #721c24;
}

.status-rejected {
    background: #f5c6cb;
    color: #721c24;
}

.btn-action {
    margin: 2px;
    padding: 5px 10px;
    font-size: 0.8rem;
}

.pagination-section {
    padding: 20px 25px;
    border-top: 1px solid #e9ecef;
    background: #f8f9fa;
}

.pagination-info {
    color: #7f8c8d;
    font-size: 0.9rem;
}

.bulk-actions-section {
    background: #e3f2fd;
    border: 1px solid #bbdefb;
    border-radius: 10px;
    margin-top: 20px;
}

.bulk-actions-bar {
    padding: 15px 25px;
}

.bulk-selection-info {
    font-weight: 600;
    color: #1976d2;
}

.bulk-action-buttons {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.loading-spinner {
    padding: 40px;
    text-align: center;
    color: #7f8c8d;
}

.loading-spinner i {
    font-size: 2rem;
    margin-right: 10px;
}

.modal-header {
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
}

.modal-title {
    color: #2c3e50;
    font-weight: 600;
}

.modal-title i {
    color: #3498db;
    margin-right: 8px;
}

.form-group label {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 8px;
}

.alert {
    border-radius: 8px;
}

.alert i {
    margin-right: 8px;
}

.btn {
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-1px);
}

.btn-primary {
    background: #3498db;
    border-color: #3498db;
}

.btn-success {
    background: #27ae60;
    border-color: #27ae60;
}

.btn-warning {
    background: #f39c12;
    border-color: #f39c12;
}

.btn-danger {
    background: #e74c3c;
    border-color: #e74c3c;
}

.btn-secondary {
    background: #95a5a6;
    border-color: #95a5a6;
}

@media (max-width: 768px) {
    .affiliate-applications-page {
        padding: 10px;
    }
    
    .page-header {
        padding: 20px;
    }
    
    .page-title {
        font-size: 1.5rem;
    }
    
    .stat-card {
        padding: 20px;
        margin-bottom: 15px;
    }
    
    .action-buttons {
        flex-direction: column;
        margin-top: 15px;
    }
    
    .bulk-action-buttons {
        flex-direction: column;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    'use strict';
    
    let currentPage = 1;
    let currentStatus = '';
    let currentSearch = '';
    let selectedAffiliates = new Set();
    
    // Initialize page
    loadAffiliates();
    loadStatistics();
    
    // Event handlers
    $('#status-filter').on('change', function() {
        currentStatus = $(this).val();
        currentPage = 1;
        loadAffiliates();
    });
    
    $('#search-affiliates').on('input', debounce(function() {
        currentSearch = $(this).val();
        currentPage = 1;
        loadAffiliates();
    }, 500));
    
    $('#refresh-table').on('click', function() {
        loadAffiliates();
        loadStatistics();
    });
    
    $('#add-new-affiliate').on('click', function() {
        $('#add-affiliate-modal').modal('show');
    });
    
    // Add affiliate form submission
    $('#add-affiliate-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        
        $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Adding...');
        
        $.ajax({
            url: flexpressAffiliate.ajaxurl,
            type: 'POST',
            data: {
                action: 'add_affiliate',
                nonce: flexpressAffiliate.nonce,
                ...Object.fromEntries(new FormData(this))
            },
            success: function(response) {
                if (response.success) {
                    showNotice('Affiliate added successfully!', 'success');
                    $('#add-affiliate-modal').modal('hide');
                    $form[0].reset();
                    loadAffiliates();
                    loadStatistics();
                } else {
                    showNotice(response.data.message || 'Error adding affiliate', 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotice('Error adding affiliate: ' + error, 'error');
            },
            complete: function() {
                $submitBtn.prop('disabled', false).html('<i class="fas fa-plus"></i> Add Affiliate');
            }
        });
    });
    
    // Status update handlers
    $(document).on('click', '.approve-affiliate, .reject-affiliate, .suspend-affiliate, .reactivate-affiliate', function() {
        const affiliateId = $(this).data('id');
        const action = $(this).hasClass('approve-affiliate') ? 'active' :
                      $(this).hasClass('reject-affiliate') ? 'rejected' :
                      $(this).hasClass('suspend-affiliate') ? 'suspended' :
                      $(this).hasClass('reactivate-affiliate') ? 'active' : 'pending';
        
        $('#status-affiliate-id').val(affiliateId);
        $('#status-new-status').val(action);
        $('#status-update-modal').modal('show');
    });
    
    // Status update form submission
    $('#status-update-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        
        $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');
        
        $.ajax({
            url: flexpressAffiliate.ajaxurl,
            type: 'POST',
            data: {
                action: 'toggle_affiliate_status',
                nonce: flexpressAffiliate.nonce,
                affiliate_id: $('#status-affiliate-id').val(),
                status: $('#status-new-status').val(),
                notes: $('#status-notes').val()
            },
            success: function(response) {
                if (response.success) {
                    showNotice('Affiliate status updated successfully!', 'success');
                    $('#status-update-modal').modal('hide');
                    $form[0].reset();
                    loadAffiliates();
                    loadStatistics();
                } else {
                    showNotice(response.data.message || 'Error updating status', 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotice('Error updating status: ' + error, 'error');
            },
            complete: function() {
                $submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Update Status');
            }
        });
    });
    
    // View affiliate details
    $(document).on('click', '.view-affiliate', function() {
        const affiliateId = $(this).data('id');
        // Implementation for viewing affiliate details
        console.log('View affiliate:', affiliateId);
    });
    
    // Edit affiliate
    $(document).on('click', '.edit-affiliate', function() {
        const affiliateId = $(this).data('id');
        // Implementation for editing affiliate
        console.log('Edit affiliate:', affiliateId);
    });
    
    // Bulk selection
    $(document).on('change', '.affiliate-checkbox', function() {
        const affiliateId = $(this).val();
        if ($(this).is(':checked')) {
            selectedAffiliates.add(affiliateId);
        } else {
            selectedAffiliates.delete(affiliateId);
        }
        updateBulkActions();
    });
    
    // Select all checkbox
    $(document).on('change', '#select-all-affiliates', function() {
        const isChecked = $(this).is(':checked');
        $('.affiliate-checkbox').prop('checked', isChecked);
        
        if (isChecked) {
            $('.affiliate-checkbox').each(function() {
                selectedAffiliates.add($(this).val());
            });
        } else {
            selectedAffiliates.clear();
        }
        updateBulkActions();
    });
    
    // Bulk actions
    $('#bulk-approve').on('click', function() {
        performBulkAction('active', 'approve');
    });
    
    $('#bulk-suspend').on('click', function() {
        performBulkAction('suspended', 'suspend');
    });
    
    $('#bulk-reject').on('click', function() {
        performBulkAction('rejected', 'reject');
    });
    
    $('#clear-selection').on('click', function() {
        selectedAffiliates.clear();
        $('.affiliate-checkbox, #select-all-affiliates').prop('checked', false);
        updateBulkActions();
    });
    
    // Functions
    function loadAffiliates() {
        const $tbody = $('#affiliates-list');
        $tbody.html('<tr><td colspan="6" class="text-center"><div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading affiliates...</div></td></tr>');
        
        $.ajax({
            url: flexpressAffiliate.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_affiliates_list',
                nonce: flexpressAffiliate.nonce,
                page: currentPage,
                per_page: 20,
                status: currentStatus,
                search: currentSearch
            },
            success: function(response) {
                if (response.success) {
                    renderAffiliatesTable(response.data.affiliates);
                    updatePagination(response.data);
                } else {
                    $tbody.html('<tr><td colspan="6" class="text-center text-danger">Error loading affiliates: ' + (response.data.message || 'Unknown error') + '</td></tr>');
                }
            },
            error: function(xhr, status, error) {
                $tbody.html('<tr><td colspan="6" class="text-center text-danger">Error loading affiliates: ' + error + '</td></tr>');
            }
        });
    }
    
    function loadStatistics() {
        $.ajax({
            url: flexpressAffiliate.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_affiliate_statistics',
                nonce: flexpressAffiliate.nonce
            },
            success: function(response) {
                if (response.success) {
                    const stats = response.data;
                    $('#pending-count').text(stats.pending || 0);
                    $('#active-count').text(stats.active || 0);
                    $('#suspended-count').text(stats.suspended || 0);
                    $('#total-revenue').text('$' + (stats.total_revenue || 0).toFixed(2));
                }
            }
        });
    }
    
    function renderAffiliatesTable(affiliates) {
        const $tbody = $('#affiliates-list');
        
        if (affiliates.length === 0) {
            $tbody.html('<tr><td colspan="6" class="text-center">No affiliates found.</td></tr>');
            return;
        }
        
        let html = '';
        affiliates.forEach(function(affiliate) {
            const statusClass = 'status-' + affiliate.status;
            const statusLabel = affiliate.status.charAt(0).toUpperCase() + affiliate.status.slice(1);
            const commissionDisplay = affiliate.commission_initial + '% / ' + affiliate.commission_rebill + '% / ' + affiliate.commission_unlock + '%';
            const revenueDisplay = '$' + parseFloat(affiliate.total_revenue).toFixed(2);
            
            html += '<tr>';
            html += '<td>';
            html += '<div class="form-check">';
            html += '<input class="form-check-input affiliate-checkbox" type="checkbox" value="' + affiliate.id + '">';
            html += '</div>';
            html += '<strong>' + escapeHtml(affiliate.display_name) + '</strong><br>';
            html += '<small class="text-muted">' + escapeHtml(affiliate.affiliate_code) + '</small>';
            html += '</td>';
            html += '<td>' + escapeHtml(affiliate.email) + '</td>';
            html += '<td><span class="status ' + statusClass + '">' + statusLabel + '</span></td>';
            html += '<td>' + commissionDisplay + '</td>';
            html += '<td>' + revenueDisplay + '</td>';
            html += '<td>';
            html += '<button type="button" class="btn btn-sm btn-outline-primary view-affiliate" data-id="' + affiliate.id + '">View</button> ';
            html += '<button type="button" class="btn btn-sm btn-outline-secondary edit-affiliate" data-id="' + affiliate.id + '">Edit</button>';
            
            // Status management buttons
            if (affiliate.status === 'pending') {
                html += ' <button type="button" class="btn btn-sm btn-success approve-affiliate" data-id="' + affiliate.id + '">Approve</button>';
                html += ' <button type="button" class="btn btn-sm btn-danger reject-affiliate" data-id="' + affiliate.id + '">Reject</button>';
            } else if (affiliate.status === 'active') {
                html += ' <button type="button" class="btn btn-sm btn-warning suspend-affiliate" data-id="' + affiliate.id + '">Suspend</button>';
            } else if (affiliate.status === 'suspended') {
                html += ' <button type="button" class="btn btn-sm btn-success reactivate-affiliate" data-id="' + affiliate.id + '">Reactivate</button>';
            }
            
            html += '</td>';
            html += '</tr>';
        });
        
        $tbody.html(html);
    }
    
    function updatePagination(data) {
        const $pagination = $('#pagination');
        $pagination.empty();
        
        if (data.total_pages <= 1) return;
        
        // Previous button
        if (data.page > 1) {
            $pagination.append('<li class="page-item"><a class="page-link" href="#" data-page="' + (data.page - 1) + '">Previous</a></li>');
        }
        
        // Page numbers
        for (let i = Math.max(1, data.page - 2); i <= Math.min(data.total_pages, data.page + 2); i++) {
            const activeClass = i === data.page ? 'active' : '';
            $pagination.append('<li class="page-item ' + activeClass + '"><a class="page-link" href="#" data-page="' + i + '">' + i + '</a></li>');
        }
        
        // Next button
        if (data.page < data.total_pages) {
            $pagination.append('<li class="page-item"><a class="page-link" href="#" data-page="' + (data.page + 1) + '">Next</a></li>');
        }
        
        // Update pagination info
        $('#showing-start').text(((data.page - 1) * data.per_page) + 1);
        $('#showing-end').text(Math.min(data.page * data.per_page, data.total));
        $('#total-count').text(data.total);
    }
    
    function updateBulkActions() {
        const $bulkActions = $('#bulk-actions');
        const $selectedCount = $('#selected-count');
        
        if (selectedAffiliates.size > 0) {
            $bulkActions.show();
            $selectedCount.text(selectedAffiliates.size);
        } else {
            $bulkActions.hide();
        }
    }
    
    function performBulkAction(status, action) {
        if (selectedAffiliates.size === 0) {
            showNotice('Please select at least one affiliate.', 'warning');
            return;
        }
        
        const confirmMessage = 'Are you sure you want to ' + action + ' ' + selectedAffiliates.size + ' selected affiliate(s)?';
        if (!confirm(confirmMessage)) {
            return;
        }
        
        const affiliateIds = Array.from(selectedAffiliates);
        
        $.ajax({
            url: flexpressAffiliate.ajaxurl,
            type: 'POST',
            data: {
                action: 'bulk_update_affiliate_status',
                nonce: flexpressAffiliate.nonce,
                affiliate_ids: affiliateIds,
                status: status
            },
            success: function(response) {
                if (response.success) {
                    showNotice(affiliateIds.length + ' affiliate(s) ' + action + 'd successfully!', 'success');
                    selectedAffiliates.clear();
                    $('.affiliate-checkbox, #select-all-affiliates').prop('checked', false);
                    updateBulkActions();
                    loadAffiliates();
                    loadStatistics();
                } else {
                    showNotice(response.data.message || 'Error performing bulk action', 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotice('Error performing bulk action: ' + error, 'error');
            }
        });
    }
    
    function showNotice(message, type) {
        const alertClass = type === 'success' ? 'alert-success' : 
                          type === 'error' ? 'alert-danger' : 
                          type === 'warning' ? 'alert-warning' : 'alert-info';
        
        const $notice = $('<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
            '<i class="fas fa-' + (type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle') + '"></i> ' +
            message +
            '<button type="button" class="close" data-dismiss="alert">' +
            '<span>&times;</span>' +
            '</button>' +
            '</div>');
        
        $('.affiliate-applications-page').prepend($notice);
        
        setTimeout(function() {
            $notice.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // Pagination click handler
    $(document).on('click', '.page-link', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        if (page && page !== currentPage) {
            currentPage = page;
            loadAffiliates();
        }
    });
});
</script>

<?php get_footer(); ?>
