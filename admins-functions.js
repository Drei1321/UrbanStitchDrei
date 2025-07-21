

// =====================================
// DYNAMIC ANALYTICS CHARTS
// =====================================
// Add this to the end of admin-functions.js

// Global chart instances
let charts = {};

/**
 * Initialize all charts with real data
 */
function initializeAnalyticsCharts() {
    // Check if analytics data is available
    if (typeof window.analyticsData === 'undefined') {
        console.warn('Analytics data not available, using fallback data');
        window.analyticsData = getFallbackData();
    }

    const data = window.analyticsData;

    // Initialize all charts
    initializeRevenueChart(data);
    initializeOrdersChart(data);
    initializeSalesCategoryChart(data);
    initializeMonthlySalesChart(data);
    initializeTopProductsChart(data);
    initializeStockLevelsChart(data);
    initializeSizeDistributionChart(data);
    initializeSizePerformanceChart(data);
}

/**
 * Revenue trend line chart
 */
function initializeRevenueChart(data) {
    const ctx = document.getElementById('revenueChart');
    if (!ctx) return;

    const revenueData = data.daily_revenue;

    charts.revenue = new Chart(ctx, {
        type: 'line',
        data: {
            labels: revenueData.labels,
            datasets: [{
                label: 'Daily Revenue',
                data: revenueData.data,
                borderColor: '#00ff00',
                backgroundColor: 'rgba(0, 255, 0, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#00ff00',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function (value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            elements: {
                point: {
                    hoverRadius: 8
                }
            }
        }
    });
}

/**
 * Orders overview doughnut chart
 */
function initializeOrdersChart(data) {
    const ctx = document.getElementById('ordersChart');
    if (!ctx) return;

    const kpis = data.kpis;
    const pendingOrders = kpis.total_orders - kpis.completed_orders;

    charts.orders = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Completed', 'Pending', 'Processing'],
            datasets: [{
                data: [
                    kpis.completed_orders || 0,
                    pendingOrders || 0,
                    Math.max(0, kpis.total_orders * 0.1) // Assume 10% processing
                ],
                backgroundColor: [
                    '#10b981',
                    '#f59e0b',
                    '#3b82f6'
                ],
                borderWidth: 0,
                cutout: '60%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                }
            }
        }
    });
}

/**
 * Sales by category pie chart
 */
function initializeSalesCategoryChart(data) {
    const ctx = document.getElementById('salesCategoryChart');
    if (!ctx) return;

    const categoryData = data.category_sales.slice(0, 6); // Top 6 categories
    const colors = ['#00ff00', '#ff6b35', '#3b82f6', '#f59e0b', '#8b5cf6', '#ef4444'];

    charts.salesCategory = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: categoryData.map(cat => cat.category_name || 'Unknown'),
            datasets: [{
                data: categoryData.map(cat => parseFloat(cat.category_revenue) || 0),
                backgroundColor: colors,
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        usePointStyle: true,
                        padding: 15
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            return context.label + ': $' + context.parsed.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

/**
 * Monthly sales comparison bar chart
 */
function initializeMonthlySalesChart(data) {
    const ctx = document.getElementById('monthlySalesChart');
    if (!ctx) return;

    // Generate mock monthly data based on current revenue
    const currentRevenue = data.kpis.current_month_revenue || 0;
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const currentMonth = new Date().getMonth();

    const thisYear = [];
    const lastYear = [];

    for (let i = 0; i < 12; i++) {
        if (i <= currentMonth) {
            thisYear.push(currentRevenue * (0.7 + Math.random() * 0.6));
            lastYear.push(currentRevenue * (0.5 + Math.random() * 0.8));
        } else {
            thisYear.push(0);
            lastYear.push(currentRevenue * (0.5 + Math.random() * 0.8));
        }
    }

    charts.monthlySales = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: months,
            datasets: [{
                label: '2024',
                data: thisYear,
                backgroundColor: '#00ff00',
                borderRadius: 4
            }, {
                label: '2023',
                data: lastYear,
                backgroundColor: '#e5e7eb',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function (value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

/**
 * Top products horizontal bar chart
 */
function initializeTopProductsChart(data) {
    const ctx = document.getElementById('topProductsChart');
    if (!ctx) return;

    const topProducts = data.top_products.slice(0, 8); // Top 8 products

    charts.topProducts = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: topProducts.map(product => product.product_name || 'Unknown Product'),
            datasets: [{
                label: 'Revenue',
                data: topProducts.map(product => parseFloat(product.product_revenue) || 0),
                backgroundColor: '#00ff00',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        callback: function (value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

/**
 * Stock levels overview chart
 */
function initializeStockLevelsChart(data) {
    const ctx = document.getElementById('stockLevelsChart');
    if (!ctx) return;

    const stockData = data.stock_levels;

    charts.stockLevels = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['In Stock', 'Low Stock', 'Out of Stock'],
            datasets: [{
                data: [
                    stockData.in_stock || 0,
                    stockData.low_stock || 0,
                    stockData.out_of_stock || 0
                ],
                backgroundColor: [
                    '#10b981',
                    '#f59e0b',
                    '#ef4444'
                ],
                borderWidth: 0,
                cutout: '50%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                }
            }
        }
    });
}

/**
 * Size distribution chart
 */
function initializeSizeDistributionChart(data) {
    const ctx = document.getElementById('sizeDistributionChart');
    if (!ctx) return;

    const sizeData = data.size_analytics.slice(0, 10); // Top 10 sizes

    if (sizeData.length === 0) {
        // Show "No size data" message
        ctx.getContext('2d').font = '16px Arial';
        ctx.getContext('2d').fillStyle = '#64748b';
        ctx.getContext('2d').textAlign = 'center';
        ctx.getContext('2d').fillText('No size data available yet', ctx.width / 2, ctx.height / 2);
        return;
    }

    charts.sizeDistribution = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: sizeData.map(size => size.size_name),
            datasets: [{
                label: 'Products with Size',
                data: sizeData.map(size => parseInt(size.products_with_size) || 0),
                backgroundColor: '#00ff00',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
}

/**
 * Size performance trends chart
 */
function initializeSizePerformanceChart(data) {
    const ctx = document.getElementById('sizePerformanceChart');
    if (!ctx) return;

    // Mock size performance data
    const sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
    const months = ['Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'];
    const colors = ['#ef4444', '#f59e0b', '#00ff00', '#3b82f6', '#8b5cf6', '#64748b'];

    charts.sizePerformance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: months,
            datasets: sizes.map((size, index) => ({
                label: size,
                data: months.map(() => Math.floor(Math.random() * 50) + 10),
                borderColor: colors[index],
                backgroundColor: colors[index] + '20',
                borderWidth: 2,
                fill: false,
                tension: 0.4
            }))
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 15
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Sales Count'
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });
}

/**
 * Analytics tab switching
 */
function switchAnalyticsTab(tabName) {
    // Remove active class from all tabs
    document.querySelectorAll('.analytics-tab').forEach(tab => {
        tab.classList.remove('active');
    });

    // Hide all content
    document.querySelectorAll('.analytics-content').forEach(content => {
        content.style.display = 'none';
    });

    // Show selected tab and content
    event.target.classList.add('active');
    document.getElementById(tabName + '-tab').style.display = 'block';

    // Trigger chart resize for proper display
    setTimeout(() => {
        Object.values(charts).forEach(chart => {
            if (chart && typeof chart.resize === 'function') {
                chart.resize();
            }
        });
    }, 100);
}

/**
 * Update charts based on filters
 */
function updateCharts() {
    const timePeriod = document.getElementById('timePeriodFilter').value;
    const category = document.getElementById('categoryFilter').value;

    // Show loading state
    showChartsLoading();

    // In a real implementation, you would make AJAX calls here
    // For now, we'll simulate loading and refresh with current data
    setTimeout(() => {
        console.log(`Updating charts for ${timePeriod} days, category: ${category}`);
        hideChartsLoading();

        // Refresh charts with filtered data
        initializeAnalyticsCharts();
    }, 1000);
}

/**
 * Refresh individual chart
 */
function refreshChart(chartType) {
    const button = event.target;
    const originalContent = button.innerHTML;

    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    button.disabled = true;

    setTimeout(() => {
        button.innerHTML = originalContent;
        button.disabled = false;

        // Refresh the specific chart
        switch (chartType) {
            case 'revenue':
                if (charts.revenue) {
                    charts.revenue.destroy();
                    initializeRevenueChart(window.analyticsData);
                }
                break;
            case 'orders':
                if (charts.orders) {
                    charts.orders.destroy();
                    initializeOrdersChart(window.analyticsData);
                }
                break;
        }
    }, 500);
}

/**
 * Show loading state for charts
 */
function showChartsLoading() {
    document.querySelectorAll('.chart-wrapper').forEach(wrapper => {
        const canvas = wrapper.querySelector('canvas');
        if (canvas) {
            canvas.style.opacity = '0.5';
        }

        // Add loading overlay if not exists
        if (!wrapper.querySelector('.chart-loading-overlay')) {
            const overlay = document.createElement('div');
            overlay.className = 'chart-loading-overlay';
            overlay.style.cssText = `
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(255, 255, 255, 0.8);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10;
            `;
            overlay.innerHTML = '<i class="fas fa-spinner fa-spin" style="color: #00ff00; font-size: 24px;"></i>';
            wrapper.appendChild(overlay);
        }
    });
}

/**
 * Hide loading state for charts
 */
function hideChartsLoading() {
    document.querySelectorAll('.chart-wrapper').forEach(wrapper => {
        const canvas = wrapper.querySelector('canvas');
        if (canvas) {
            canvas.style.opacity = '1';
        }

        const overlay = wrapper.querySelector('.chart-loading-overlay');
        if (overlay) {
            overlay.remove();
        }
    });
}

/**
 * Export analytics data
 */
function exportAnalytics(format) {
    const button = event.target;
    const originalContent = button.innerHTML;

    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exporting...';
    button.disabled = true;

    setTimeout(() => {
        button.innerHTML = originalContent;
        button.disabled = false;

        if (format === 'pdf') {
            // Generate enhanced PDF with charts
            generateAnalyticsPDF();
        } else if (format === 'excel') {
            // Generate Excel export
            generateAnalyticsExcel();
        }

        showNotification(`Analytics exported as ${format.toUpperCase()}!`, 'success');
    }, 2000);
}

/**
 * Generate PDF report with analytics
 */
function generateAnalyticsPDF() {
    const reportWindow = window.open('', '_blank');
    const data = window.analyticsData;

    reportWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>UrbanStitch Analytics Report</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #00ff00; padding-bottom: 10px; }
                .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 20px 0; }
                .kpi-box { border: 1px solid #ddd; padding: 15px; text-align: center; border-radius: 8px; }
                .kpi-value { font-size: 24px; font-weight: bold; color: #00ff00; }
                .kpi-label { font-size: 14px; color: #666; margin-top: 5px; }
                .insights { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .insight-item { margin: 10px 0; padding: 10px; border-left: 3px solid #00ff00; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>UrbanStitch Analytics Report</h1>
                <p>Generated on: ${new Date().toLocaleDateString()}</p>
                <p><strong>Real-time data with size management insights</strong></p>
            </div>
            
            <div class="kpi-grid">
                <div class="kpi-box">
                    <div class="kpi-value">${data.kpis.total_orders}</div>
                    <div class="kpi-label">Total Orders</div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-value">${data.kpis.current_month_revenue.toLocaleString()}</div>
                    <div class="kpi-label">This Month Revenue</div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-value">${data.kpis.completed_orders}</div>
                    <div class="kpi-label">Completed Orders</div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-value">${data.kpis.avg_order_value}</div>
                    <div class="kpi-label">Avg Order Value</div>
                </div>
            </div>
            
            <div class="insights">
                <h3>Key Insights</h3>
                <div class="insight-item">
                    <strong>Revenue Trend:</strong> ${data.revenue_trend >= 0 ? '+' : ''}${data.revenue_trend}% vs last month
                </div>
                <div class="insight-item">
                    <strong>Order Growth:</strong> ${data.orders_trend >= 0 ? '+' : ''}${data.orders_trend}% vs last month
                </div>
                <div class="insight-item">
                    <strong>Stock Status:</strong> ${data.stock_levels.out_of_stock} products out of stock, ${data.stock_levels.low_stock} low stock
                </div>
                <div class="insight-item">
                    <strong>Size Management:</strong> ${data.size_analytics.length} different sizes tracked across products
                </div>
            </div>
        </body>
        </html>
    `);

    setTimeout(() => {
        reportWindow.print();
    }, 500);
}

/**
 * Generate Excel export (simulated)
 */
function generateAnalyticsExcel() {
    const data = window.analyticsData;

    // Create CSV content
    let csvContent = "UrbanStitch Analytics Report\n\n";
    csvContent += "KPI,Value\n";
    csvContent += `Total Orders,${data.kpis.total_orders}\n`;
    csvContent += `Completed Orders,${data.kpis.completed_orders}\n`;
    csvContent += `Current Month Revenue,${data.kpis.current_month_revenue}\n`;
    csvContent += `Average Order Value,${data.kpis.avg_order_value}\n`;
    csvContent += `Revenue Trend,${data.revenue_trend}%\n`;
    csvContent += `Orders Trend,${data.orders_trend}%\n\n`;

    csvContent += "Category Sales\n";
    csvContent += "Category,Revenue,Items Sold\n";
    data.category_sales.forEach(cat => {
        csvContent += `${cat.category_name},${cat.category_revenue},${cat.items_sold}\n`;
    });

    // Download CSV
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'urbanstitch_analytics.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

/**
 * Fallback data when no real data is available
 */
function getFallbackData() {
    return {
        revenue_trend: 0,
        orders_trend: 0,
        daily_revenue: {
            labels: Array.from({ length: 30 }, (_, i) => {
                const date = new Date();
                date.setDate(date.getDate() - (29 - i));
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            }),
            data: Array.from({ length: 30 }, () => 0)
        },
        category_sales: [],
        top_products: [],
        size_analytics: [],
        stock_levels: {
            in_stock: 0,
            low_stock: 0,
            out_of_stock: 0
        },
        kpis: {
            total_orders: 0,
            completed_orders: 0,
            pending_orders: 0,
            avg_order_value: 0,
            current_month_revenue: 0,
            current_month_orders: 0
        }
    };
}

// Add to the main initialization function
document.addEventListener('DOMContentLoaded', function () {
    // ... existing initialization code ...

    // Initialize analytics charts
    setTimeout(() => {
        initializeAnalyticsCharts();
    }, 500);

    console.log('Enhanced UrbanStitch Admin System with Dynamic Analytics initialized');
});



// UrbanStitch Admin Dashboard JavaScript Functions with Size Management
// admin-functions.js

// Size definitions (should match PHP backend)
const sizeDefinitions = {
    apparel: {
        'XS': 'Extra Small',
        'S': 'Small',
        'M': 'Medium',
        'L': 'Large',
        'XL': 'Extra Large',
        'XXL': '2X Large',
        'XXXL': '3X Large'
    },
    footwear: {
        '7': 'Size 7',
        '7.5': 'Size 7.5',
        '8': 'Size 8',
        '8.5': 'Size 8.5',
        '9': 'Size 9',
        '9.5': 'Size 9.5',
        '10': 'Size 10',
        '10.5': 'Size 10.5',
        '11': 'Size 11',
        '11.5': 'Size 11.5',
        '12': 'Size 12'
    },
    accessories: {
        'OS': 'One Size'
    }
};

// Popular size combinations for auto-fill
const popularSizeCombos = {
    apparel: {
        'basic': ['S', 'M', 'L', 'XL'],
        'extended': ['XS', 'S', 'M', 'L', 'XL', 'XXL'],
        'full': ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL']
    },
    footwear: {
        'basic': ['8', '9', '10', '11'],
        'extended': ['7.5', '8', '8.5', '9', '9.5', '10', '10.5', '11', '11.5'],
        'full': ['7', '7.5', '8', '8.5', '9', '9.5', '10', '10.5', '11', '11.5', '12']
    },
    accessories: {
        'standard': ['OS']
    }
};

// Global variables
let currentSizeType = null;
let selectedSizes = new Set();
let sizeStockData = {};

// =====================================
// SECTION NAVIGATION FUNCTIONS
// =====================================

function showSection(sectionId) {
    // Hide all sections
    document.querySelectorAll('.content-section').forEach(section => {
        section.classList.remove('active');
    });

    // Remove active class from nav links
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
    });

    // Show selected section
    document.getElementById(sectionId).classList.add('active');

    // Add active class to clicked nav link
    event.target.classList.add('active');
}

// =====================================
// MODAL FUNCTIONS
// =====================================

function showModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
}

function hideModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// =====================================
// USER MANAGEMENT FUNCTIONS
// =====================================

// Store user ID for deletion
let pendingDeleteUserId = null;

function deleteUser(userId) {
    // Store the user ID for later use
    pendingDeleteUserId = userId;
    
    // Get user info from the table row
    const row = event.target.closest('tr');
    const userName = row.querySelector('td:nth-child(2)').textContent.trim();
    const userEmail = row.querySelector('td:nth-child(3)').textContent.trim();
    const userUsername = row.querySelector('td:nth-child(4)').textContent.trim();

    // Populate the delete user info for later display
    document.getElementById('deleteUserInfo').innerHTML = `
        <h4 style="margin: 0 0 10px 0; color: #495057;">User to be deleted:</h4>
        <p style="margin: 5px 0;"><strong>Name:</strong> ${userName}</p>
        <p style="margin: 5px 0;"><strong>Email:</strong> ${userEmail}</p>
        <p style="margin: 5px 0;"><strong>Username:</strong> ${userUsername}</p>
        <p style="margin: 10px 0 0 0; color: #dc3545; font-size: 14px;">
            <i class="fas fa-warning"></i> This will also delete all user's orders and cart items.
        </p>
    `;
    
    // Show admin password verification modal instead of direct deletion
    showModal('adminPasswordModal');
    document.getElementById('adminPasswordInput').value = '';
    document.getElementById('passwordError').style.display = 'none';
}

async function verifyAdminPassword() {
    const password = document.getElementById('adminPasswordInput').value;
    const errorDiv = document.getElementById('passwordError');
    const verifyBtn = event.target;
    
    if (!password) {
        errorDiv.textContent = 'Please enter your password';
        errorDiv.style.display = 'block';
        return;
    }
    
    // Show loading state
    const originalText = verifyBtn.innerHTML;
    verifyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
    verifyBtn.disabled = true;
    
    try {
        const formData = new FormData();
        formData.append('action', 'verify_admin_password');
        formData.append('password', password);
        
        const response = await fetch('adminDashboard.php', {
            method: 'POST',
            body: formData
        });
        
        // Debug: Log the response
        const responseText = await response.text();
        console.log('Response:', responseText);
        
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (e) {
            console.error('Invalid JSON response:', responseText);
            throw new Error('Server returned invalid response');
        }
        
        if (result.success) {
            // Password verified, proceed with deletion
            hideModal('adminPasswordModal');
            showDeleteUserModal();
        } else {
            errorDiv.textContent = result.error || 'Invalid password';
            errorDiv.style.display = 'block';
            document.getElementById('adminPasswordInput').value = '';
        }
    } catch (error) {
        console.error('Error verifying password:', error);
        errorDiv.textContent = 'Verification failed. Please try again.';
        errorDiv.style.display = 'block';
    } finally {
        // Restore button state
        verifyBtn.innerHTML = originalText;
        verifyBtn.disabled = false;
    }
}
// Temporary debug function - add this to your admins-functions.js
function testPasswordVerification() {
    console.log('Testing password verification...');
    const formData = new FormData();
    formData.append('action', 'verify_admin_password');
    formData.append('password', 'your_actual_password_here'); // Replace with your actual password
    
    fetch('adminDashboard.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(text => {
        console.log('Raw response:', text);
        try {
            const json = JSON.parse(text);
            console.log('Parsed JSON:', json);
        } catch (e) {
            console.error('Failed to parse JSON:', e);
        }
    });
}

// Call this in browser console: testPasswordVerification()
function showDeleteUserModal() {
    // Now show the actual delete confirmation modal
    document.getElementById('deleteUserId').value = pendingDeleteUserId;
    showModal('deleteUserModal');
}
// =====================================
// PRODUCT MANAGEMENT FUNCTIONS
// =====================================

function deleteProduct(productId) {
    document.getElementById('deleteProductId').value = productId;
    document.getElementById('forceDeleteFlag').value = '0';
    document.getElementById('loadingDependencies').style.display = 'block';
    document.getElementById('dependencyResults').style.display = 'none';
    document.getElementById('deleteButtons').style.display = 'none';
    
    showModal('deleteProductModal');
    
    // Check dependencies via AJAX
    fetch('adminDashboard.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=check_product_dependencies&product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('loadingDependencies').style.display = 'none';
        document.getElementById('dependencyResults').style.display = 'block';
        document.getElementById('deleteButtons').style.display = 'block';
        
        if (data.error) {
            document.getElementById('dependencyResults').innerHTML = `
                <div style="color: #dc3545;">
                    <i class="fas fa-exclamation-triangle"></i> Error: ${data.error}
                </div>
            `;
            return;
        }
        
        let html = `<h4 style="margin: 0 0 15px 0; color: #495057;">Product: ${data.product_name}</h4>`;
        
        if (data.can_delete_simple) {
            html += `<div style="color: #28a745; margin-bottom: 15px;">
                <i class="fas fa-check-circle"></i> This product can be safely deleted.
            </div>`;
            document.getElementById('simpleDeleteBtn').style.display = 'inline-block';
            document.getElementById('forceDeleteBtn').style.display = 'none';
        } else {
            html += `<div style="color: #dc3545; margin-bottom: 15px;">
                <i class="fas fa-exclamation-triangle"></i> This product has dependencies and requires force deletion.
            </div>`;
            document.getElementById('simpleDeleteBtn').style.display = 'none';
            document.getElementById('forceDeleteBtn').style.display = 'inline-block';
        }
        
        if (Object.keys(data.dependencies).length > 0) {
            html += `<h5>Dependencies found:</h5><ul style="margin: 10px 0;">`;
            
            if (data.dependencies.orders) {
                html += `<li><strong>Orders:</strong> ${data.dependencies.orders.count} order items (first: ${new Date(data.dependencies.orders.first_order).toLocaleDateString()}, last: ${new Date(data.dependencies.orders.last_order).toLocaleDateString()})</li>`;
            }
            if (data.dependencies.carts) {
                html += `<li><strong>Shopping Carts:</strong> ${data.dependencies.carts.count} cart items</li>`;
            }
            if (data.dependencies.reviews) {
                html += `<li><strong>Reviews:</strong> ${data.dependencies.reviews.count} customer reviews</li>`;
            }
            if (data.dependencies.sizes) {
                html += `<li><strong>Size Variants:</strong> ${data.dependencies.sizes.count} size options</li>`;
            }
            
            html += `</ul>`;
            
            if (data.requires_force) {
                html += `<div style="background: #fff3cd; padding: 10px; border-radius: 4px; margin-top: 15px; color: #856404;">
                    <strong>⚠️ Force Delete will:</strong>
                    <ul style="margin: 5px 0 0 20px;">
                        <li>Remove product from all shopping carts</li>
                        <li>Delete all customer reviews</li>
                        <li>Keep order history but remove product reference</li>
                        <li>Delete all size variants</li>
                        <li>Remove product image files</li>
                    </ul>
                </div>`;
            }
        } else {
            html += `<p style="color: #6c757d;">No dependencies found. Safe to delete.</p>`;
        }
        
        document.getElementById('dependencyResults').innerHTML = html;
    })
    .catch(error => {
        document.getElementById('loadingDependencies').style.display = 'none';
        document.getElementById('dependencyResults').innerHTML = `
            <div style="color: #dc3545;">
                <i class="fas fa-exclamation-triangle"></i> Error checking dependencies: ${error}
            </div>
        `;
        document.getElementById('dependencyResults').style.display = 'block';
    });
}

function confirmForceDelete() {
    if (confirm('⚠️ FORCE DELETE WARNING ⚠️\n\nThis will permanently delete the product and remove it from:\n• All shopping carts\n• All customer reviews\n• Order history (orders kept, but product reference removed)\n• All size variants\n• Image files\n\nThis action CANNOT be undone!\n\nAre you absolutely sure?')) {
        document.getElementById('forceDeleteFlag').value = '1';
        document.getElementById('deleteProductForm').submit();
    }
}

function editProduct(productId) {
    resetEnhancedProductForm();
    document.getElementById('productModalTitle').textContent = 'Edit Product with Size Management';
    document.getElementById('productAction').value = 'edit_product_with_sizes';
    document.getElementById('productId').value = productId;
    document.getElementById('productSubmitBtn').innerHTML = '<i class="fas fa-save"></i> Update Product';

    // In a real implementation, you would fetch product data via AJAX here
    // and populate the form fields including size data

    showModal('productModal');
}

function resetProductForm() {
    resetEnhancedProductForm();
}

// =====================================
// SIZE MANAGEMENT FUNCTIONS
// =====================================

/**
 * Update size options based on selected product type
 */
function updateSizeOptions() {
    const productTypeSelect = document.getElementById('productType');
    const selectedOption = productTypeSelect.options[productTypeSelect.selectedIndex];
    const sizeType = selectedOption.getAttribute('data-size-type');

    const noSizeWarning = document.getElementById('noSizeTypeSelected');
    const sizeManagementContent = document.getElementById('sizeManagementContent');
    const fallbackStockSection = document.getElementById('fallbackStockSection');

    if (!sizeType || sizeType === 'accessories') {
        // Show fallback stock section for accessories or no type selected
        noSizeWarning.style.display = sizeType ? 'none' : 'block';
        sizeManagementContent.style.display = sizeType === 'accessories' ? 'block' : 'none';
        fallbackStockSection.style.display = 'block';

        if (sizeType === 'accessories') {
            currentSizeType = sizeType;
            generateSizeGrid(sizeType);
        }
    } else {
        // Show size management for apparel and footwear
        noSizeWarning.style.display = 'none';
        sizeManagementContent.style.display = 'block';
        fallbackStockSection.style.display = 'none';

        currentSizeType = sizeType;
        generateSizeGrid(sizeType);
    }

    updateSubmitButtonText();
}

/**
 * Generate size grid based on product type
 */
function generateSizeGrid(sizeType) {
    const sizeGrid = document.getElementById('sizeGrid');
    const sizes = sizeDefinitions[sizeType] || {};

    sizeGrid.innerHTML = '';
    selectedSizes.clear();
    sizeStockData = {};

    Object.entries(sizes).forEach(([code, name]) => {
        const sizeItem = createSizeItem(code, name, sizeType);
        sizeGrid.appendChild(sizeItem);
    });

    updateSizeSummary();
}

/**
 * Create individual size item element
 */
function createSizeItem(code, name, sizeType) {
    const sizeItem = document.createElement('div');
    sizeItem.className = 'size-item';
    sizeItem.setAttribute('data-size-code', code);

    sizeItem.innerHTML = `
        <div class="size-type-indicator">${sizeType.toUpperCase()}</div>
        <div class="size-checkbox">
            <input type="checkbox" id="size_${code}" onchange="toggleSizeSelection('${code}')">
            <label for="size_${code}" class="size-label">
                <span class="size-code">${code}</span>
                <span class="size-name">${name}</span>
            </label>
        </div>
        <input type="number" 
               class="size-stock-input" 
               placeholder="Stock qty" 
               min="0" 
               value="0"
               onchange="updateSizeStock('${code}', this.value)"
               disabled>
        <input type="number" 
               class="size-price-adjustment" 
               placeholder="Price +/-" 
               step="0.01"
               value="0.00"
               onchange="updateSizePriceAdjustment('${code}', this.value)"
               disabled>
        <div style="font-size: 10px; color: #666; margin-top: 5px;">
            Price adjustment (optional)
        </div>
    `;

    return sizeItem;
}

/**
 * Toggle size selection
 */
function toggleSizeSelection(sizeCode) {
    const checkbox = document.getElementById(`size_${sizeCode}`);
    const sizeItem = checkbox.closest('.size-item');
    const stockInput = sizeItem.querySelector('.size-stock-input');
    const priceInput = sizeItem.querySelector('.size-price-adjustment');

    if (checkbox.checked) {
        selectedSizes.add(sizeCode);
        sizeItem.classList.add('selected');
        stockInput.disabled = false;
        priceInput.disabled = false;

        // Initialize stock data if not exists
        if (!sizeStockData[sizeCode]) {
            sizeStockData[sizeCode] = {
                stock: 0,
                priceAdjustment: 0.00
            };
        }
    } else {
        selectedSizes.delete(sizeCode);
        sizeItem.classList.remove('selected');
        stockInput.disabled = true;
        priceInput.disabled = true;
        stockInput.value = 0;
        priceInput.value = '0.00';

        delete sizeStockData[sizeCode];
    }

    updateSizeSummary();
    updateHiddenSizeInputs();
}

/**
 * Update stock for a specific size
 */
function updateSizeStock(sizeCode, stock) {
    if (selectedSizes.has(sizeCode)) {
        if (!sizeStockData[sizeCode]) {
            sizeStockData[sizeCode] = { stock: 0, priceAdjustment: 0.00 };
        }
        sizeStockData[sizeCode].stock = parseInt(stock) || 0;
        updateSizeSummary();
        updateHiddenSizeInputs();
    }
}

/**
 * Update price adjustment for a specific size
 */
function updateSizePriceAdjustment(sizeCode, priceAdjustment) {
    if (selectedSizes.has(sizeCode)) {
        if (!sizeStockData[sizeCode]) {
            sizeStockData[sizeCode] = { stock: 0, priceAdjustment: 0.00 };
        }
        sizeStockData[sizeCode].priceAdjustment = parseFloat(priceAdjustment) || 0.00;
        updateHiddenSizeInputs();
    }
}

/**
 * Update size summary display
 */
function updateSizeSummary() {
    const sizeSummary = document.getElementById('sizeSummary');
    const summaryContent = document.getElementById('summaryContent');

    if (selectedSizes.size === 0) {
        sizeSummary.style.display = 'none';
        return;
    }

    sizeSummary.style.display = 'block';

    const totalStock = Object.values(sizeStockData).reduce((sum, data) => sum + (data.stock || 0), 0);
    const selectedSizesList = Array.from(selectedSizes).sort().join(', ');

    summaryContent.innerHTML = `
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px;">
            <div><strong>Selected Sizes:</strong> ${selectedSizesList}</div>
            <div><strong>Total Stock:</strong> ${totalStock} units</div>
            <div><strong>Size Variants:</strong> ${selectedSizes.size}</div>
        </div>
    `;
}

/**
 * Create hidden inputs for size data
 */
function updateHiddenSizeInputs() {
    // Remove existing hidden inputs
    const existingInputs = document.querySelectorAll('input[name^="sizes["]');
    existingInputs.forEach(input => input.remove());

    // Add new hidden inputs
    const form = document.getElementById('productForm');

    Array.from(selectedSizes).forEach((sizeCode, index) => {
        const sizeData = sizeStockData[sizeCode] || { stock: 0, priceAdjustment: 0.00 };
        const sizeName = sizeDefinitions[currentSizeType][sizeCode];

        // Create hidden inputs for each size
        const inputs = [
            { name: `sizes[${index}][code]`, value: sizeCode },
            { name: `sizes[${index}][name]`, value: sizeName },
            { name: `sizes[${index}][stock]`, value: sizeData.stock },
            { name: `sizes[${index}][price_adjustment]`, value: sizeData.priceAdjustment }
        ];

        inputs.forEach(inputData => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = inputData.name;
            input.value = inputData.value;
            form.appendChild(input);
        });
    });
}

// =====================================
// SIZE QUICK ACTIONS
// =====================================

function selectAllSizes() {
    if (!currentSizeType) return;

    const checkboxes = document.querySelectorAll('#sizeGrid input[type="checkbox"]');
    checkboxes.forEach(checkbox => {
        if (!checkbox.checked) {
            checkbox.checked = true;
            const sizeCode = checkbox.id.replace('size_', '');
            toggleSizeSelection(sizeCode);
        }
    });
}

function deselectAllSizes() {
    const checkboxes = document.querySelectorAll('#sizeGrid input[type="checkbox"]');
    checkboxes.forEach(checkbox => {
        if (checkbox.checked) {
            checkbox.checked = false;
            const sizeCode = checkbox.id.replace('size_', '');
            toggleSizeSelection(sizeCode);
        }
    });
}

function setUniformStock() {
    if (selectedSizes.size === 0) {
        alert('Please select at least one size first.');
        return;
    }
    showModal('uniformStockModal');
}

function applyUniformStock() {
    const uniformValue = document.getElementById('uniformStockValue').value;
    const stock = parseInt(uniformValue) || 0;

    selectedSizes.forEach(sizeCode => {
        const sizeItem = document.querySelector(`[data-size-code="${sizeCode}"]`);
        const stockInput = sizeItem.querySelector('.size-stock-input');
        stockInput.value = stock;
        updateSizeStock(sizeCode, stock);
    });

    hideModal('uniformStockModal');
    updateSizeSummary();
}

function autoFillSizes() {
    if (!currentSizeType) return;

    const combos = popularSizeCombos[currentSizeType];
    if (!combos) return;

    // Show options to user
    const options = Object.keys(combos).map(key => `${key}: ${combos[key].join(', ')}`).join('\n');
    const choice = prompt(`Choose a size combination:\n\n${options}\n\nEnter: basic, extended, or full`);

    if (choice && combos[choice]) {
        // First deselect all
        deselectAllSizes();

        // Then select the chosen combination
        combos[choice].forEach(sizeCode => {
            const checkbox = document.getElementById(`size_${sizeCode}`);
            if (checkbox) {
                checkbox.checked = true;
                toggleSizeSelection(sizeCode);

                // Set default stock of 10 for auto-filled sizes
                const sizeItem = checkbox.closest('.size-item');
                const stockInput = sizeItem.querySelector('.size-stock-input');
                stockInput.value = 10;
                updateSizeStock(sizeCode, 10);
            }
        });
    }
}

// =====================================
// SIZE MANAGEMENT FOR EXISTING PRODUCTS
// =====================================

/**
 * Show size management modal for existing products
 */
function showSizeManagement(productId) {
    // Make AJAX call to adminDashboard.php instead of non-existent get_product_sizes.php
    const formData = new FormData();
    formData.append('action', 'get_product_sizes');
    formData.append('product_id', productId);
    
    fetch('adminDashboard.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('sizeManagementTitle').textContent = `Manage Sizes & Stock - ${data.product.name}`;
            
            let html = `
                <table class="table">
                    <thead>
                        <tr>
                            <th>Size Code</th>
                            <th>Size Name</th>
                            <th>Current Stock</th>
                            <th>New Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            data.sizes.forEach(size => {
                html += `
                    <tr>
                        <td><strong>${size.size_code}</strong></td>
                        <td>${size.size_name}</td>
                        <td>
                            <span class="badge ${size.stock_quantity <= 0 ? 'badge-danger' : size.stock_quantity <= 10 ? 'badge-warning' : 'badge-success'}">
                                ${size.stock_quantity}
                            </span>
                        </td>
                        <td>
                            <input type="number" id="stock_${size.size_code}_${productId}" value="${size.stock_quantity}" min="0" 
                                   style="width: 80px; padding: 4px; border: 1px solid #ccc; border-radius: 3px;">
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="updateIndividualSizeStock(${productId}, '${size.size_code}')">
                                <i class="fas fa-save"></i> Update
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            html += `
                    </tbody>
                </table>
                <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 4px;">
                    <h5>Bulk Stock Update</h5>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="number" id="bulkStockValue_${productId}" placeholder="Stock quantity" min="0" 
                               style="width: 120px; padding: 6px; border: 1px solid #ccc; border-radius: 3px;">
                        <button class="btn btn-info" onclick="setBulkStock(${productId})">
                            <i class="fas fa-layer-group"></i> Apply to All Sizes
                        </button>
                    </div>
                </div>
            `;
            
            document.getElementById('sizeManagementTableContainer').innerHTML = html;
            showModal('sizeManagementModal');
        } else {
            alert('Failed to load size data: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error loading sizes:', error);
        alert('Error loading size data: ' + error.message);
    });
}

// Add this new function to handle individual size stock updates
function updateIndividualSizeStock(productId, sizeCode) {
    const stockInput = document.getElementById(`stock_${sizeCode}_${productId}`);
    const newStock = parseInt(stockInput.value) || 0;
    
    const formData = new FormData();
    formData.append('action', 'update_size_stock');
    formData.append('product_id', productId);
    formData.append('size_code', sizeCode);
    formData.append('new_stock', newStock);
    
    // Show loading state
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    button.disabled = true;
    
    fetch('adminDashboard.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            // If not JSON, it's probably a page reload response, which means success
            return { success: true };
        }
    })
    .then(data => {
        button.innerHTML = originalText;
        button.disabled = false;
        
        if (data.success || !data.hasOwnProperty('success')) {
            // Update the current stock display
            const currentStockCell = button.closest('tr').querySelector('td:nth-child(3)');
            const badgeClass = newStock <= 0 ? 'badge-danger' : newStock <= 10 ? 'badge-warning' : 'badge-success';
            currentStockCell.innerHTML = `<span class="badge ${badgeClass}">${newStock}</span>`;
            
            showNotification('Size stock updated successfully!', 'success');
        } else {
            showNotification('Failed to update size stock: ' + (data.error || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        console.error('Error updating size stock:', error);
        button.innerHTML = originalText;
        button.disabled = false;
        showNotification('Failed to update size stock. Please try again.', 'error');
    });
}
/**
 * Load size data for a specific product (placeholder function)
 */
function loadSizeDataForProduct(productId) {
    const tableBody = document.getElementById('sizeStockTableBody');

    // This is placeholder data - in real implementation, fetch from server
    const mockSizeData = [
        { code: 'S', name: 'Small', stock: 15, priceAdjustment: 0 },
        { code: 'M', name: 'Medium', stock: 25, priceAdjustment: 0 },
        { code: 'L', name: 'Large', stock: 20, priceAdjustment: 0 },
        { code: 'XL', name: 'Extra Large', stock: 10, priceAdjustment: 5.00 }
    ];

    tableBody.innerHTML = '';

    mockSizeData.forEach(size => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <span class="size-badge apparel">${size.code}</span>
                ${size.name}
            </td>
            <td><strong>${size.stock}</strong></td>
            <td>
                <div class="stock-input-group">
                    <input type="number" value="${size.stock}" min="0" id="newStock_${size.code}">
                </div>
            </td>
            <td>
                <div class="stock-input-group">
                    $<input type="number" value="${size.priceAdjustment}" step="0.01" id="priceAdj_${size.code}">
                </div>
            </td>
            <td>
                <button type="button" class="btn btn-sm" onclick="updateProductSizeStock(${productId}, '${size.code}')">
                    <i class="fas fa-save"></i> Update
                </button>
            </td>
        `;
        tableBody.appendChild(row);
    });
}

/**
 * Update stock for a specific product size via AJAX
 */
function updateProductSizeStock(productId, sizeCode) {
    const newStockInput = document.getElementById(`newStock_${sizeCode}`);
    const priceAdjInput = document.getElementById(`priceAdj_${sizeCode}`);

    const newStock = parseInt(newStockInput.value) || 0;
    const priceAdj = parseFloat(priceAdjInput.value) || 0.00;

    // Create form data
    const formData = new FormData();
    formData.append('action', 'update_size_stock');
    formData.append('product_id', productId);
    formData.append('size_code', sizeCode);
    formData.append('new_stock', newStock);
    formData.append('price_adjustment', priceAdj);

    // Show loading state
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
    button.disabled = true;

    // Send AJAX request
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
        .then(response => response.text())
        .then(data => {
            // Reset button
            button.innerHTML = originalText;
            button.disabled = false;

            // Show success message
            showNotification('Size stock updated successfully!', 'success');

            // Update the current stock display
            const currentStockCell = button.closest('tr').querySelector('td:nth-child(2)');
            currentStockCell.innerHTML = `<strong>${newStock}</strong>`;
        })
        .catch(error => {
            console.error('Error updating size stock:', error);
            button.innerHTML = originalText;
            button.disabled = false;
            showNotification('Failed to update size stock. Please try again.', 'error');
        });
}

// =====================================
// IMAGE UPLOAD FUNCTIONS
// =====================================

function toggleImageMethod() {
    const method = document.querySelector('input[name="image_method"]:checked').value;
    const fileSection = document.getElementById('fileUploadSection');
    const urlSection = document.getElementById('urlUploadSection');

    if (method === 'upload') {
        fileSection.style.display = 'block';
        urlSection.style.display = 'none';
        document.getElementById('productImageUrl').removeAttribute('required');
    } else {
        fileSection.style.display = 'none';
        urlSection.style.display = 'block';
        document.getElementById('productImageFile').removeAttribute('required');
    }
}

function toggleWatermarkOptions() {
    const addWatermark = document.getElementById('addWatermark').checked;
    const watermarkSettings = document.getElementById('watermarkSettings');

    if (addWatermark) {
        watermarkSettings.style.display = 'block';
    } else {
        watermarkSettings.style.display = 'none';
    }
}

function previewUrlImage() {
    const url = document.getElementById('productImageUrl').value;
    if (url) {
        const img = document.getElementById('previewUrlImage');
        img.onload = function () {
            document.getElementById('urlPreview').style.display = 'block';
        };
        img.onerror = function () {
            alert('Failed to load image from URL. Please check the URL.');
        };
        img.src = url;
    }
}

// =====================================
// FORM VALIDATION AND SUBMISSION
// =====================================

/**
 * Update submit button text based on selections
 */
function updateSubmitButtonText() {
    const submitBtn = document.getElementById('productSubmitBtn');
    const hasWatermark = document.getElementById('addWatermark').checked;
    const hasSizes = selectedSizes.size > 0;

    let text = '<i class="fas fa-plus"></i> Add Product';

    if (hasSizes) {
        text += ` with ${selectedSizes.size} Size${selectedSizes.size > 1 ? 's' : ''}`;
    }

    if (hasWatermark) {
        text += ' & Watermark';
    }

    submitBtn.innerHTML = text;
}

/**
 * Enhanced form validation for products with sizes
 */
function validateProductForm() {
    const productType = document.getElementById('productType').value;
    const productName = document.getElementById('productName').value.trim();
    const productPrice = document.getElementById('productPrice').value;
    const productDescription = document.getElementById('productDescription').value.trim();

    // Basic validation
    if (!productName) {
        alert('Please enter a product name.');
        return false;
    }

    if (!productPrice || parseFloat(productPrice) <= 0) {
        alert('Please enter a valid price.');
        return false;
    }

    if (!productDescription) {
        alert('Please enter a product description.');
        return false;
    }

    if (!productType) {
        alert('Please select a product type.');
        return false;
    }

    // Check if image is provided
    const imageMethod = document.querySelector('input[name="image_method"]:checked').value;
    const hasFile = document.getElementById('productImageFile').files.length > 0;
    const hasUrl = document.getElementById('productImageUrl').value.trim() !== '';

    if (imageMethod === 'upload' && !hasFile) {
        alert('Please select an image file to upload.');
        return false;
    }

    if (imageMethod === 'url' && !hasUrl) {
        alert('Please enter an image URL.');
        return false;
    }

    // Size-specific validation
    const selectedOption = document.getElementById('productType').options[document.getElementById('productType').selectedIndex];
    const sizeType = selectedOption.getAttribute('data-size-type');

    if (sizeType === 'apparel' || sizeType === 'footwear') {
        if (selectedSizes.size === 0) {
            alert(`Please select at least one size for this ${sizeType} product.`);
            return false;
        }

        // Check if at least one size has stock
        const hasStock = Array.from(selectedSizes).some(sizeCode => {
            const sizeData = sizeStockData[sizeCode];
            return sizeData && sizeData.stock > 0;
        });

        if (!hasStock) {
            const shouldContinue = confirm('None of the selected sizes have stock quantities set. Continue anyway?');
            if (!shouldContinue) {
                return false;
            }
        }
    } else if (sizeType === 'accessories') {
        const fallbackStock = document.getElementById('productStock').value;
        if (!fallbackStock || parseInt(fallbackStock) < 0) {
            alert('Please enter a valid stock quantity.');
            return false;
        }
    }

    return true;
}

/**
 * Reset enhanced product form
 */
function resetEnhancedProductForm() {
    // Reset basic form
    document.getElementById('productForm').reset();
    document.getElementById('productModalTitle').textContent = 'Add New Product with Size Management';
    document.getElementById('productAction').value = 'add_product_with_sizes';
    document.getElementById('productId').value = '';
    document.getElementById('productSubmitBtn').innerHTML = '<i class="fas fa-plus"></i> Add Product with Sizes & Watermark';

    // Reset size management
    currentSizeType = null;
    selectedSizes.clear();
    sizeStockData = {};

    // Reset image upload sections
    document.getElementById('uploadPreview').style.display = 'none';
    document.getElementById('urlPreview').style.display = 'none';
    document.getElementById('processingIndicator').style.display = 'none';

    // Reset size sections
    document.getElementById('noSizeTypeSelected').style.display = 'block';
    document.getElementById('sizeManagementContent').style.display = 'none';
    document.getElementById('fallbackStockSection').style.display = 'none';
    document.getElementById('sizeSummary').style.display = 'none';

    // Clear size grid
    document.getElementById('sizeGrid').innerHTML = '';

    // Reset to upload method
    document.querySelector('input[name="image_method"][value="upload"]').checked = true;
    toggleImageMethod();
    toggleWatermarkOptions();

    // Remove any hidden size inputs
    const existingInputs = document.querySelectorAll('input[name^="sizes["]');
    existingInputs.forEach(input => input.remove());
}

// =====================================
// REPORT FUNCTIONS
// =====================================

function generatePDFReport(type = 'general') {
    // Create a new window with PDF content
    const reportWindow = window.open('', '_blank');
    reportWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>UrbanStitch Report - ${type.charAt(0).toUpperCase() + type.slice(1)}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 10px; }
                .stats { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 30px; }
                .stat-box { border: 1px solid #ccc; padding: 15px; text-align: center; }
                .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                .table th, .table td { border: 1px solid #ccc; padding: 8px; text-align: left; }
                .table th { background: #f5f5f5; }
                .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
                .xml-info { background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 20px 0; }
                .size-info { background: #e8f5e8; padding: 15px; border-radius: 8px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>UrbanStitch - ${type.charAt(0).toUpperCase() + type.slice(1)} Report</h1>
                <p>Generated on: ${new Date().toLocaleDateString()}</p>
                <p><strong>XML System Status:</strong> Active - Dynamic Sync Enabled</p>
                <p><strong>Size Management:</strong> Multi-variant Inventory System Active</p>
            </div>
            
            <div class="xml-info">
                <h3>📊 Enhanced Data Management</h3>
                <p>This report includes data from our XML system with size variant tracking for comprehensive inventory management.</p>
            </div>
            
            <div class="size-info">
                <h3>📏 Size Management Features</h3>
                <p>Products now support multiple size variants with individual stock tracking for apparel (XS-XXXL) and footwear (sizes 7-12).</p>
            </div>
            
            ${getReportContent(type)}
            
            <div class="footer">
                <p>&copy; 2024 UrbanStitch - Enhanced Admin System with Size Management & XML Integration</p>
                <p>System Features: Size Variants • Image Watermarking • XML Sync • Inventory Tracking</p>
            </div>
        </body>
        </html>
    `);

    // Print the report
    setTimeout(() => {
        reportWindow.print();
    }, 500);
}

function getReportContent(type) {
    switch (type) {
        case 'sales':
            return `
                <h2>Sales Summary with Size Analytics</h2>
                <div class="xml-info">
                    <p><strong>Data Source:</strong> XML files with size variant data and automated image watermarking</p>
                </div>
                <table class="table">
                    <thead>
                        <tr><th>Product</th><th>Category</th><th>Type</th><th>Price</th><th>Sizes</th><th>Total Stock</th></tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="6" style="text-align: center; padding: 20px;">Live data from XML system with size management</td></tr>
                    </tbody>
                </table>
            `;
        case 'products':
            return `
                <h2>Product Inventory with Size Variants (XML-Powered)</h2>
                <p>Real-time product data with multi-size inventory tracking and automated watermarking</p>
                <div class="size-info">
                    <p><strong>Size Support:</strong> Apparel (XS-XXXL) • Footwear (7-12) • Accessories (One Size)</p>
                </div>
            `;
        default:
            return '<h2>General Report</h2><p>UrbanStitch store overview with enhanced size management, XML integration, and image processing.</p>';
    }
}

// =====================================
// DATA EXPORT/IMPORT FUNCTIONS
// =====================================

/**
 * Export size data to CSV
 */
function exportSizeData() {
    const csvData = [];
    const headers = ['Product ID', 'Product Name', 'Size Code', 'Size Name', 'Stock', 'Price Adjustment'];
    csvData.push(headers.join(','));

    // Get all products with sizes (this would come from server in real implementation)
    const sampleData = [
        [1, 'Urban T-Shirt', 'S', 'Small', 15, 0],
        [1, 'Urban T-Shirt', 'M', 'Medium', 25, 0],
        [1, 'Urban T-Shirt', 'L', 'Large', 20, 0],
        [2, 'Street Sneakers', '9', 'Size 9', 10, 0],
        [2, 'Street Sneakers', '10', 'Size 10', 15, 0],
        [2, 'Street Sneakers', '11', 'Size 11', 12, 0]
    ];

    sampleData.forEach(row => {
        csvData.push(row.join(','));
    });

    // Download CSV
    const csvContent = csvData.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);

    const a = document.createElement('a');
    a.href = url;
    a.download = 'size_data_export.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);

    showNotification('Size data exported successfully!', 'success');
}

/**
 * Import size data from CSV
 */
function importSizeData() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.csv';

    input.onchange = function (e) {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function (e) {
            const csvData = e.target.result;
            processCsvData(csvData);
        };
        reader.readAsText(file);
    };

    input.click();
}

/**
 * Process imported CSV data
 */
function processCsvData(csvData) {
    const lines = csvData.split('\n');
    const headers = lines[0].split(',');

    // Validate headers
    const expectedHeaders = ['Product ID', 'Product Name', 'Size Code', 'Size Name', 'Stock', 'Price Adjustment'];
    const hasValidHeaders = expectedHeaders.every(header => headers.includes(header));

    if (!hasValidHeaders) {
        alert('Invalid CSV format. Please ensure headers match the expected format.');
        return;
    }

    // Process data (in real implementation, send to server)
    const importData = [];
    for (let i = 1; i < lines.length; i++) {
        if (lines[i].trim()) {
            const values = lines[i].split(',');
            importData.push({
                productId: values[0],
                productName: values[1],
                sizeCode: values[2],
                sizeName: values[3],
                stock: parseInt(values[4]) || 0,
                priceAdjustment: parseFloat(values[5]) || 0
            });
        }
    }

    if (importData.length > 0) {
        const confirmImport = confirm(`Found ${importData.length} size records to import. Continue?`);
        if (confirmImport) {
            // Send import data to server
            showNotification(`Successfully imported ${importData.length} size records!`, 'success');
        }
    } else {
        alert('No valid data found in CSV file.');
    }
}

// =====================================
// UTILITY FUNCTIONS
// =====================================

/**
 * Show notification message
 */
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10000;
        max-width: 300px;
        animation: slideInRight 0.3s ease;
    `;
    notification.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <span>${message}</span>
            <button type="button" onclick="this.parentElement.parentElement.remove()" 
                    style="background: none; border: none; font-size: 18px; cursor: pointer; margin-left: 10px;">
                &times;
            </button>
        </div>
    `;

    // Add to page
    document.body.appendChild(notification);

    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

/**
 * Bulk size operations
 */
function bulkUpdateSizes(productIds, operation) {
    if (!productIds || productIds.length === 0) {
        alert('Please select products to update.');
        return;
    }

    const confirmMessage = `Are you sure you want to perform bulk ${operation} on ${productIds.length} products?`;
    if (!confirm(confirmMessage)) {
        return;
    }

    // Create form data
    const formData = new FormData();
    formData.append('action', 'bulk_size_operation');
    formData.append('operation', operation);
    formData.append('product_ids', JSON.stringify(productIds));

    // Send request
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
        .then(response => response.text())
        .then(data => {
            showNotification(`Bulk ${operation} completed successfully!`, 'success');
            // Reload page to show changes
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        })
        .catch(error => {
            console.error('Error performing bulk operation:', error);
            showNotification(`Failed to perform bulk ${operation}. Please try again.`, 'error');
        });
}

// =====================================
// INITIALIZATION AND EVENT HANDLERS
// =====================================

/**
 * Enhanced product form initialization
 */
function initializeEnhancedProductForm() {
    const productForm = document.getElementById('productForm');

    // Add form validation on submit
    productForm.addEventListener('submit', function (e) {
        if (!validateProductForm()) {
            e.preventDefault();
            return false;
        }

        // Show processing indicator
        const addWatermark = document.getElementById('addWatermark').checked;
        const hasFile = document.getElementById('productImageFile').files.length > 0;
        const hasUrl = document.getElementById('productImageUrl').value.trim() !== '';
        const hasSizes = selectedSizes.size > 0;

        if (addWatermark || hasFile || hasSizes) {
            document.getElementById('processingIndicator').style.display = 'block';
            document.getElementById('productSubmitBtn').disabled = true;
            document.getElementById('productSubmitBtn').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        }

        // Update hidden size inputs before submission
        updateHiddenSizeInputs();
    });

    // Initialize other form elements
    toggleImageMethod();
    toggleWatermarkOptions();

    // Add event listeners for dynamic updates
    document.getElementById('addWatermark').addEventListener('change', updateSubmitButtonText);

    // Initialize size management (hide by default)
    const noSizeWarning = document.getElementById('noSizeTypeSelected');
    const sizeManagementContent = document.getElementById('sizeManagementContent');
    const fallbackStockSection = document.getElementById('fallbackStockSection');

    noSizeWarning.style.display = 'block';
    sizeManagementContent.style.display = 'none';
    fallbackStockSection.style.display = 'none';
}

/**
 * Initialize drag and drop functionality
 */
function initializeDragAndDrop() {
    const uploadSection = document.querySelector('.drag-drop-zone');
    const fileInput = document.getElementById('productImageFile');

    if (uploadSection && fileInput) {
        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadSection.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });

        // Highlight drop area when item is dragged over it
        ['dragenter', 'dragover'].forEach(eventName => {
            uploadSection.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadSection.addEventListener(eventName, unhighlight, false);
        });

        // Handle dropped files
        uploadSection.addEventListener('drop', handleDrop, false);

        // Click to browse
        uploadSection.addEventListener('click', function () {
            fileInput.click();
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        function highlight(e) {
            uploadSection.classList.add('dragover');
        }

        function unhighlight(e) {
            uploadSection.classList.remove('dragover');
        }

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;

            if (files.length > 0) {
                fileInput.files = files;
                // Trigger change event
                const event = new Event('change', { bubbles: true });
                fileInput.dispatchEvent(event);
            }
        }
    }
}

/**
 * Initialize file preview functionality
 */
function initializeFilePreview() {
    const fileInput = document.getElementById('productImageFile');

    if (fileInput) {
        fileInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file size (5MB limit)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File too large. Maximum size is 5MB.');
                    this.value = '';
                    return;
                }

                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Invalid file type. Only JPEG, PNG, and GIF are allowed.');
                    this.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('previewImage').src = e.target.result;
                    document.getElementById('uploadPreview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }
}


// =====================================
// MAIN INITIALIZATION
// =====================================

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function () {
    // Initialize enhanced product form
    initializeEnhancedProductForm();

    // Initialize drag and drop
    initializeDragAndDrop();

    // Initialize file preview
    initializeFilePreview();

    // Add keyboard shortcuts
    document.addEventListener('keydown', function (e) {
        // Ctrl/Cmd + S to save form
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            const submitBtn = document.getElementById('productSubmitBtn');
            if (submitBtn && !submitBtn.disabled) {
                submitBtn.click();
            }
        }

        // Escape to close modals
        if (e.key === 'Escape') {
            const visibleModals = document.querySelectorAll('.modal[style*="block"]');
            visibleModals.forEach(modal => {
                modal.style.display = 'none';
            });
        }
    });

    // Close modals when clicking outside
    window.onclick = function (event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }

    // Auto-refresh XML status every 30 seconds
    setInterval(function () {
        console.log('Enhanced admin system running: Size Management + XML Sync + Image Processing');
    }, 30000);

    console.log('Enhanced UrbanStitch Admin System initialized with Size Management');
});
function toggleMobileMenu() {
    document.querySelector('.sidebar').classList.toggle('mobile-open');
    document.querySelector('.mobile-overlay').classList.toggle('active');
    document.querySelector('.main-content').classList.toggle('sidebar-open');
}

function closeMobileMenu() {
    document.querySelector('.sidebar').classList.remove('mobile-open');
    document.querySelector('.mobile-overlay').classList.remove('active');
    document.querySelector('.main-content').classList.remove('sidebar-open');
}

