<?php
/**
 * Template Name: Affiliate Dashboard
 * Description: Dashboard for affiliates to track performance and earnings
 */

get_header();

// Check if user is logged in and is an affiliate
$current_user_id = get_current_user_id();
if (!$current_user_id) {
    wp_redirect(home_url('/login?redirect_to=' . urlencode(get_permalink())));
    exit;
}

global $wpdb;
$affiliate = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}flexpress_affiliates WHERE user_id = %d OR email = %s",
    $current_user_id,
    wp_get_current_user()->user_email
));

if (!$affiliate) {
    wp_redirect(home_url('/affiliate-signup'));
    exit;
}

// Get dashboard data
$dashboard_data = flexpress_get_affiliate_dashboard_data($affiliate->id);
if (!$dashboard_data) {
    wp_die('Error loading affiliate data');
}

$recent_commissions = $dashboard_data['recent_commissions'];
$commission_stats = $dashboard_data['commission_stats'];
$monthly_stats = $dashboard_data['monthly_stats'];

// Calculate stats
$total_signups = 0;
$total_rebills = 0;
$total_commission = 0;
$total_revenue = 0;

foreach ($commission_stats as $stat) {
    if ($stat->transaction_type === 'signup') {
        $total_signups = $stat->count;
    } elseif ($stat->transaction_type === 'rebill') {
        $total_rebills = $stat->count;
    }
    $total_commission += $stat->total_commission;
    $total_revenue += $stat->total_revenue;
}

// Current month stats
$current_month = date('Y-m');
$current_month_stats = array_filter($monthly_stats, function($stat) use ($current_month) {
    return $stat->month === $current_month;
});
$current_month_commission = !empty($current_month_stats) ? $current_month_stats[0]->commission : 0;

?>

<div class="affiliate-dashboard-page">
    <div class="container-fluid mt-4 mb-5">
        
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 mb-1">Affiliate Dashboard</h1>
                        <p class="text-muted mb-0">
                            Welcome back, <?php echo esc_html($affiliate->display_name); ?>! 
                            <span class="badge bg-<?php echo $affiliate->status === 'active' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst($affiliate->status); ?>
                            </span>
                        </p>
                    </div>
                    <div>
                        <strong>Your Code:</strong> 
                        <code class="bg-primary text-white px-2 py-1 rounded" id="affiliate-code">
                            <?php echo esc_html($affiliate->affiliate_code); ?>
                        </code>
                        <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyAffiliateCode()">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-user-plus text-primary mb-2" style="font-size: 2rem;"></i>
                        <h3 class="h4 mb-1"><?php echo number_format($total_signups); ?></h3>
                        <p class="text-muted mb-0">Total Signups</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-redo text-success mb-2" style="font-size: 2rem;"></i>
                        <h3 class="h4 mb-1"><?php echo number_format($total_rebills); ?></h3>
                        <p class="text-muted mb-0">Total Rebills</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-dollar-sign text-warning mb-2" style="font-size: 2rem;"></i>
                        <h3 class="h4 mb-1">$<?php echo number_format($affiliate->pending_commission, 2); ?></h3>
                        <p class="text-muted mb-0">Pending</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-chart-line text-info mb-2" style="font-size: 2rem;"></i>
                        <h3 class="h4 mb-1">$<?php echo number_format($affiliate->total_commission, 2); ?></h3>
                        <p class="text-muted mb-0">Total Earned</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Commission Details -->
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Recent Commissions</h5>
                        <small class="text-muted">Last 10 transactions</small>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recent_commissions)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Revenue</th>
                                            <th>Rate</th>
                                            <th>Commission</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_commissions as $commission): ?>
                                            <tr>
                                                <td><?php echo date('M j, Y', strtotime($commission->created_at)); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $commission->transaction_type === 'signup' ? 'primary' : 'success'; ?>">
                                                        <?php echo ucfirst($commission->transaction_type); ?>
                                                    </span>
                                                </td>
                                                <td>$<?php echo number_format($commission->revenue_amount, 2); ?></td>
                                                <td><?php echo number_format($commission->commission_rate, 1); ?>%</td>
                                                <td>$<?php echo number_format($commission->commission_amount, 2); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $commission->status === 'paid' ? 'success' : 
                                                             ($commission->status === 'pending' ? 'warning' : 'secondary'); 
                                                    ?>">
                                                        <?php echo ucfirst($commission->status); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-chart-line mb-3" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p>No commissions yet. Start promoting to earn your first commission!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Affiliate Info & Tools -->
            <div class="col-lg-4 mb-4">
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-cogs me-2"></i>Commission Rates</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Signup Commission:</span>
                            <strong><?php echo number_format($affiliate->commission_signup, 1); ?>%</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Rebill Commission:</span>
                            <strong><?php echo number_format($affiliate->commission_rebill, 1); ?>%</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Commission Type:</span>
                            <strong><?php echo ucfirst($affiliate->commission_type); ?></strong>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-link me-2"></i>Promotional Links</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label small">Join Page Link:</label>
                            <div class="input-group input-group-sm">
                                <input type="text" 
                                       class="form-control" 
                                       id="join-link" 
                                       value="<?php echo home_url('/join/' . $affiliate->affiliate_code); ?>" 
                                       readonly>
                                <button class="btn btn-outline-secondary" onclick="copyLink('join-link')">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-0">
                            <label class="form-label small">Homepage Link:</label>
                            <div class="input-group input-group-sm">
                                <input type="text" 
                                       class="form-control" 
                                       id="home-link" 
                                       value="<?php echo home_url('/?promo=' . $affiliate->affiliate_code); ?>" 
                                       readonly>
                                <button class="btn btn-outline-secondary" onclick="copyLink('home-link')">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-calendar me-2"></i>This Month</h6>
                    </div>
                    <div class="card-body">
                        <div class="text-center">
                            <h4 class="text-primary">$<?php echo number_format($current_month_commission, 2); ?></h4>
                            <p class="text-muted mb-0">Commissions Earned</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Monthly Performance Chart -->
        <?php if (!empty($monthly_stats)): ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-area me-2"></i>Monthly Performance</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="performanceChart" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Copy functions
function copyAffiliateCode() {
    copyToClipboard(document.getElementById('affiliate-code').textContent);
}

function copyLink(elementId) {
    copyToClipboard(document.getElementById(elementId).value);
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        // Show toast or alert
        const toast = document.createElement('div');
        toast.className = 'alert alert-success position-fixed';
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; opacity: 0; transition: opacity 0.3s;';
        toast.innerHTML = '<i class="fas fa-check me-2"></i>Copied to clipboard!';
        document.body.appendChild(toast);
        
        setTimeout(() => toast.style.opacity = '1', 100);
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => document.body.removeChild(toast), 300);
        }, 2000);
    });
}

// Performance Chart
<?php if (!empty($monthly_stats)): ?>
const monthlyData = <?php echo json_encode(array_reverse($monthly_stats)); ?>;
const ctx = document.getElementById('performanceChart').getContext('2d');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: monthlyData.map(item => {
            const date = new Date(item.month + '-01');
            return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
        }),
        datasets: [{
            label: 'Commission ($)',
            data: monthlyData.map(item => parseFloat(item.commission)),
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.1)',
            tension: 0.1,
            fill: true
        }, {
            label: 'Transactions',
            data: monthlyData.map(item => parseInt(item.transactions)),
            borderColor: 'rgb(255, 99, 132)',
            backgroundColor: 'rgba(255, 99, 132, 0.1)',
            tension: 0.1,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Commission ($)'
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Transactions'
                },
                grid: {
                    drawOnChartArea: false,
                }
            }
        }
    }
});
<?php endif; ?>
</script>

<style>
.stat-card {
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.affiliate-dashboard-page {
    background-color: #f8f9fa;
    min-height: 100vh;
}

code {
    font-size: 0.9em;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #6c757d;
    font-size: 0.875rem;
}

.card {
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.card-header {
    background-color: #fff;
    border-bottom: 1px solid #eee;
    font-weight: 600;
}
</style>

<?php get_footer(); ?> 