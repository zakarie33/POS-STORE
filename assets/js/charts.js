/**
 * Dashboard Charts logic using Chart.js
 */

document.addEventListener('DOMContentLoaded', () => {
    // Initialize charts only if elements exist
    const revenueCanvas = document.getElementById('revenueChart');
    const paymentCanvas = document.getElementById('paymentChart');

    if (!revenueCanvas || !paymentCanvas) return;

    // Default styles based on theme
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = getComputedStyle(document.body).getPropertyValue('--text-muted').trim();
    Chart.defaults.scale.grid.color = getComputedStyle(document.body).getPropertyValue('--border-color').trim();

    // Setup Revenue Chart (Line Chart)
    const revCtx = revenueCanvas.getContext('2d');
    
    // Gradient for line chart
    const gradient = revCtx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(99, 102, 241, 0.5)'); // primary color
    gradient.addColorStop(1, 'rgba(99, 102, 241, 0.0)');

    let revenueChart = new Chart(revCtx, {
        type: 'line',
        data: {
            labels: window.chartData ? window.chartData.revenue.labels : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'Revenue ($)',
                data: window.chartData ? window.chartData.revenue.data : [0, 0, 0, 0, 0, 0, 0],
                borderColor: '#6366f1',
                backgroundColor: gradient,
                borderWidth: 2,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#6366f1',
                pointBorderWidth: 2,
                pointRadius: 4,
                fill: true,
                tension: 0.4 // Smooth curves
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    padding: 10,
                    cornerRadius: 8,
                    displayColors: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    border: { display: false },
                    grid: { drawBorder: false }
                },
                x: {
                    border: { display: false },
                    grid: { display: false }
                }
            }
        }
    });

    // Setup Payment Methods Chart (Doughnut Chart)
    const payCtx = paymentCanvas.getContext('2d');
    let paymentChart = new Chart(payCtx, {
        type: 'doughnut',
        data: {
            labels: window.chartData ? window.chartData.payment_methods.labels : ['Cash', 'Zaad', 'eDahab', 'Sahal'],
            datasets: [{
                data: window.chartData ? window.chartData.payment_methods.data : [0, 0, 0, 0],
                backgroundColor: [
                    '#10b981', // Success (Cash)
                    '#3b82f6', // Info (Zaad)
                    '#f59e0b', // Warning (eDahab)
                    '#ec4899'  // Accent (Sahal)
                ],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '75%',
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

    // Handle Theme Change to update chart colors dynamically
    window.addEventListener('themeChanged', () => {
        const textColor = getComputedStyle(document.body).getPropertyValue('--text-muted').trim();
        const gridColor = getComputedStyle(document.body).getPropertyValue('--border-color').trim();
        
        Chart.defaults.color = textColor;
        
        revenueChart.options.scales.x.grid.color = gridColor;
        revenueChart.options.scales.y.grid.color = gridColor;
        revenueChart.update();
        
        paymentChart.update();
    });
});
