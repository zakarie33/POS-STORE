<?php
require_once '../config/db.php';
require_once '../utils/security.php';
require_once '../utils/auth_check.php';
require_login();
require_role(['admin', 'manager']);

// Fetch basic report data (Real-world would have AJAX filtering)
$total_revenue = $pdo->query("SELECT SUM(total) FROM sales WHERE status='completed'")->fetchColumn() ?: 0;
$total_cost = $pdo->query("SELECT SUM(si.quantity * p.cost_price) FROM sale_items si JOIN products p ON si.product_id = p.id JOIN sales s ON si.sale_id = s.id WHERE s.status='completed'")->fetchColumn() ?: 0;
$profit = $total_revenue - $total_cost;

$top_products = $pdo->query("SELECT p.name, SUM(si.quantity) as qty, SUM(si.subtotal) as rev FROM sale_items si JOIN products p ON si.product_id = p.id GROUP BY p.id ORDER BY qty DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - SuperPOS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .report-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .report-card { padding: 1.5rem; border-left: 4px solid var(--primary-color); }
        .report-card.profit { border-left-color: var(--success-color); }
        .report-card.cost { border-left-color: var(--danger-color); }
        .report-val { font-size: 2rem; font-weight: 700; margin-top: 0.5rem; color: var(--text-color); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { font-weight: 600; color: var(--text-muted); background: var(--bg-color); }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include '../components/sidebar.php'; ?>
        <div class="main-wrapper">
            <?php include '../components/header.php'; ?>
            <main class="main-content">
                <div class="page-header">
                    <div>
                        <h1>Financial Reports</h1>
                        <p class="text-muted">Overview of store performance</p>
                    </div>
                    <button class="btn btn-outline" onclick="window.print()">
                        <i class="ph ph-printer"></i> Print Report
                    </button>
                </div>

                <div class="report-grid">
                    <div class="card report-card">
                        <div class="text-muted" style="text-transform: uppercase; font-size: 0.85rem; font-weight: 600;">Total Revenue</div>
                        <div class="report-val">$<?php echo number_format($total_revenue, 2); ?></div>
                    </div>
                    <div class="card report-card cost">
                        <div class="text-muted" style="text-transform: uppercase; font-size: 0.85rem; font-weight: 600;">Total Cost of Goods</div>
                        <div class="report-val">$<?php echo number_format($total_cost, 2); ?></div>
                    </div>
                    <div class="card report-card profit">
                        <div class="text-muted" style="text-transform: uppercase; font-size: 0.85rem; font-weight: 600;">Gross Profit</div>
                        <div class="report-val text-success">$<?php echo number_format($profit, 2); ?></div>
                    </div>
                </div>

                <div class="card" style="margin-bottom: 2rem;">
                    <div style="padding: 1.5rem; border-bottom: 1px solid var(--border-color);">
                        <h3 style="margin: 0;">Top Selling Products</h3>
                    </div>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Product Name</th>
                                    <th>Quantity Sold</th>
                                    <th>Total Revenue Generated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($top_products)): ?>
                                <tr><td colspan="3" style="text-align: center;">No sales data available.</td></tr>
                                <?php else: ?>
                                    <?php foreach($top_products as $p): ?>
                                    <tr>
                                        <td style="font-weight: 500;"><?php echo htmlspecialchars($p['name']); ?></td>
                                        <td><?php echo number_format($p['qty']); ?></td>
                                        <td style="color: var(--primary-color); font-weight: 600;">$<?php echo number_format($p['rev'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="../assets/js/app.js"></script>
</body>
</html>
