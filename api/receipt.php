<?php
require_once '../config/db.php';
require_once '../utils/security.php';
require_once '../utils/auth_check.php';

api_require_login();

$sale_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$sale_id) {
    die("Invalid Sale ID");
}

try {
    // Get Sale Details
    $stmt = $pdo->prepare("
        SELECT s.*, u.name as cashier_name 
        FROM sales s 
        LEFT JOIN users u ON s.user_id = u.id 
        WHERE s.id = ?
    ");
    $stmt->execute([$sale_id]);
    $sale = $stmt->fetch();

    if (!$sale) {
        die("Sale not found");
    }

    // Get Sale Items
    $stmt = $pdo->prepare("
        SELECT si.*, p.name as product_name, p.code as product_code 
        FROM sale_items si 
        LEFT JOIN products p ON si.product_id = p.id 
        WHERE si.sale_id = ?
    ");
    $stmt->execute([$sale_id]);
    $items = $stmt->fetchAll();

    // Get Store Settings
    $stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
    $settings = $stmt->fetch();

} catch (PDOException $e) {
    die("Error loading receipt data");
}

// Generate HTML Receipt for printing
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt - <?php echo htmlspecialchars($sale['reference_no']); ?></title>
    <style>
        body {
            font-family: monospace;
            background: #fff;
            color: #000;
            padding: 20px;
            max-width: 300px;
            margin: 0 auto;
        }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 1.5em; }
        .header p { margin: 5px 0; font-size: 0.9em; }
        .divider { border-top: 1px dashed #000; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { text-align: left; padding: 4px 0; font-size: 0.9em; }
        th.right, td.right { text-align: right; }
        .totals { margin-top: 10px; }
        .totals-row { display: flex; justify-content: space-between; margin-bottom: 4px; font-size: 0.9em; }
        .totals-row.grand { font-weight: bold; font-size: 1.1em; border-top: 1px dashed #000; padding-top: 5px; }
        .footer { text-align: center; margin-top: 20px; font-size: 0.8em; }
        
        @media print {
            body { margin: 0; padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="header">
        <h1><?php echo htmlspecialchars($settings['store_name'] ?? 'Super Store'); ?></h1>
        <p><?php echo nl2br(htmlspecialchars($settings['address'] ?? '')); ?></p>
        <p>Tel: <?php echo htmlspecialchars($settings['phone'] ?? ''); ?></p>
    </div>

    <div class="divider"></div>

    <div style="font-size: 0.9em; margin-bottom: 10px;">
        <div>Receipt No: <?php echo htmlspecialchars($sale['reference_no']); ?></div>
        <div>Date: <?php echo date('Y-m-d H:i:s', strtotime($sale['created_at'])); ?></div>
        <div>Cashier: <?php echo htmlspecialchars($sale['cashier_name'] ?? 'Unknown'); ?></div>
    </div>

    <div class="divider"></div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th class="right">Qty</th>
                <th class="right">Price</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td colspan="4"><?php echo htmlspecialchars($item['product_name']); ?></td>
            </tr>
            <tr>
                <td></td>
                <td class="right"><?php echo $item['quantity']; ?></td>
                <td class="right"><?php echo number_format($item['unit_price'], 2); ?></td>
                <td class="right"><?php echo number_format($item['subtotal'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="divider"></div>

    <div class="totals">
        <div class="totals-row">
            <span>Subtotal:</span>
            <span>$<?php echo number_format($sale['subtotal'], 2); ?></span>
        </div>
        <div class="totals-row">
            <span>Tax:</span>
            <span>$<?php echo number_format($sale['tax_amount'], 2); ?></span>
        </div>
        <?php if($sale['discount'] > 0): ?>
        <div class="totals-row">
            <span>Discount:</span>
            <span>-$<?php echo number_format($sale['discount'], 2); ?></span>
        </div>
        <?php endif; ?>
        <div class="totals-row grand">
            <span>Total:</span>
            <span>$<?php echo number_format($sale['total'], 2); ?></span>
        </div>
    </div>

    <div class="divider"></div>

    <div class="totals">
        <div class="totals-row">
            <span>Payment (<?php echo htmlspecialchars($sale['payment_method']); ?>):</span>
            <span>$<?php echo number_format($sale['amount_paid'], 2); ?></span>
        </div>
        <div class="totals-row">
            <span>Change:</span>
            <span>$<?php echo number_format($sale['change_returned'], 2); ?></span>
        </div>
        <?php if($sale['payment_reference']): ?>
        <div class="totals-row">
            <span>Ref:</span>
            <span><?php echo htmlspecialchars($sale['payment_reference']); ?></span>
        </div>
        <?php endif; ?>
    </div>

    <div class="footer">
        <p>Thank you for shopping with us!</p>
        <p>Please come again.</p>
    </div>

    <div class="no-print" style="margin-top: 30px; text-align: center;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer;">Print Receipt</button>
        <button onclick="window.close()" style="padding: 10px 20px; font-size: 16px; cursor: pointer;">Close</button>
    </div>
</body>
</html>
