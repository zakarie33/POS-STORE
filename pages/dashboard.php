<?php
require_once '../config/db.php';
require_once '../utils/security.php';
require_once '../utils/auth_check.php';
require_login();

// Fetch KPI Data
$stats = [
    'sales_today' => 0,
    'sales_month' => 0,
    'total_products' => 0,
    'low_stock' => 0
];

try {
    // Sales Today
    $stmt = $pdo->query("SELECT SUM(total) FROM sales WHERE DATE(created_at) = CURDATE() AND status = 'completed'");
    $stats['sales_today'] = $stmt->fetchColumn() ?: 0;

    // Sales This Month
    $stmt = $pdo->query("SELECT SUM(total) FROM sales WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND status = 'completed'");
    $stats['sales_month'] = $stmt->fetchColumn() ?: 0;

    // Total Products
    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    $stats['total_products'] = $stmt->fetchColumn() ?: 0;

    // Low Stock (less than 10)
    $stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity <= 10");
    $stats['low_stock'] = $stmt->fetchColumn() ?: 0;

    // Revenue last 7 days
    $revenue_data = ['labels' => [], 'data' => []];
    $stmt = $pdo->query("
        SELECT DATE(created_at) as date, SUM(total) as daily_total
        FROM sales
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        AND status = 'completed'
        GROUP BY DATE(created_at)
        ORDER BY DATE(created_at) ASC
    ");
    $sales_by_date = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $revenue_data['labels'][] = date('D', strtotime($date));
        $revenue_data['data'][] = isset($sales_by_date[$date]) ? (float)$sales_by_date[$date] : 0;
    }

    // Payment Methods
    $payment_data = ['labels' => [], 'data' => []];
    $stmt = $pdo->query("
        SELECT payment_method, COUNT(*) as count
        FROM sales
        WHERE status = 'completed'
        GROUP BY payment_method
    ");
    $payment_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $methods = ['Cash', 'Zaad', 'eDahab', 'Sahal'];
    foreach ($methods as $method) {
        $payment_data['labels'][] = $method;
        $payment_data['data'][] = isset($payment_counts[$method]) ? (int)$payment_counts[$method] : 0;
    }

    // Recent Transactions
    $stmt = $pdo->query("
        SELECT reference_no, total, payment_method, created_at 
        FROM sales 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recent_transactions = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SuperPOS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .kpi-card {
            padding: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .kpi-info h3 {
            color: var(--text-muted);
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .kpi-info .value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-color);
        }

        .kpi-icon {
            width: 56px;
            height: 56px;
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .chart-card {
            padding: 1.5rem;
        }

        .chart-card h3 {
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
        }

        @media (max-width: 992px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include '../components/sidebar.php'; ?>
        
        <div class="main-wrapper">
            <?php include '../components/header.php'; ?>
            
            <main class="main-content">
                <div style="margin-bottom: 2rem;">
                    <h1 style="font-size: 1.5rem; margin-bottom: 0.5rem;">Dashboard</h1>
                    <p class="text-muted">Welcome back! Here's what's happening with your store today.</p>
                </div>

                <!-- KPI Cards -->
                <div class="kpi-grid">
                    <div class="card kpi-card">
                        <div class="kpi-info">
                            <h3>Sales Today</h3>
                            <div class="value">$<?php echo number_format($stats['sales_today'], 2); ?></div>
                        </div>
                        <div class="kpi-icon bg-success-light text-success">
                            <i class="ph-fill ph-currency-circle-dollar"></i>
                        </div>
                    </div>

                    <div class="card kpi-card">
                        <div class="kpi-info">
                            <h3>Sales This Month</h3>
                            <div class="value">$<?php echo number_format($stats['sales_month'], 2); ?></div>
                        </div>
                        <div class="kpi-icon bg-info-light text-info">
                            <i class="ph-fill ph-trend-up"></i>
                        </div>
                    </div>

                    <div class="card kpi-card">
                        <div class="kpi-info">
                            <h3>Total Products</h3>
                            <div class="value"><?php echo number_format($stats['total_products']); ?></div>
                        </div>
                        <div class="kpi-icon bg-warning-light text-warning">
                            <i class="ph-fill ph-package"></i>
                        </div>
                    </div>

                    <div class="card kpi-card">
                        <div class="kpi-info">
                            <h3>Low Stock Alerts</h3>
                            <div class="value text-danger"><?php echo number_format($stats['low_stock']); ?></div>
                        </div>
                        <div class="kpi-icon bg-danger-light text-danger">
                            <i class="ph-fill ph-warning-circle"></i>
                        </div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="charts-grid">
                    <div class="card chart-card">
                        <h3>Revenue Trend (Last 7 Days)</h3>
                        <canvas id="revenueChart" height="100"></canvas>
                    </div>
                    <div class="card chart-card">
                        <h3>Payment Methods</h3>
                        <canvas id="paymentChart" height="200"></canvas>
                    </div>
                </div>
                
                <!-- Recent Transactions -->
                <div class="card chart-card" style="margin-top: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h3 style="margin: 0;">Recent Transactions</h3>
                        <a href="reports.php" class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">View All</a>
                    </div>
                    
                    <?php if (empty($recent_transactions)): ?>
                        <p class="text-muted">No recent transactions.</p>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="border-bottom: 1px solid var(--border-color); color: var(--text-muted); text-transform: uppercase; font-size: 0.75rem;">
                                        <th style="padding: 0.75rem; text-align: left;">Reference</th>
                                        <th style="padding: 0.75rem; text-align: left;">Date</th>
                                        <th style="padding: 0.75rem; text-align: left;">Payment Method</th>
                                        <th style="padding: 0.75rem; text-align: right;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_transactions as $txn): ?>
                                    <tr style="border-bottom: 1px solid var(--border-color);">
                                        <td style="padding: 0.75rem; font-weight: 500;"><?php echo htmlspecialchars($txn['reference_no']); ?></td>
                                        <td style="padding: 0.75rem; color: var(--text-muted);"><?php echo date('M d, Y h:i A', strtotime($txn['created_at'])); ?></td>
                                        <td style="padding: 0.75rem;">
                                            <span style="background: var(--bg-color); padding: 0.2rem 0.5rem; border-radius: var(--radius-sm); font-size: 0.8rem;">
                                                <?php echo htmlspecialchars($txn['payment_method']); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 0.75rem; text-align: right; font-weight: 600;">$<?php echo number_format($txn['total'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

            </main>
        </div>
    </div>

    <script>
        window.chartData = {
            revenue: <?php echo json_encode($revenue_data); ?>,
            payment_methods: <?php echo json_encode($payment_data); ?>
        };
    </script>
    <script src="../assets/js/app.js"></script>
    <script src="../assets/js/charts.js"></script>
</body>
</html>
