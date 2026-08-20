<?php
require_once '../config/db.php';
require_once '../utils/security.php';
require_once '../utils/auth_check.php';

api_require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON payload
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['csrf_token']) || !verify_csrf_token($data['csrf_token'])) {
        json_response(['success' => false, 'message' => 'Invalid CSRF token'], 403);
    }

    $cart = $data['cart'] ?? [];
    if (empty($cart)) {
        json_response(['success' => false, 'message' => 'Cart is empty']);
    }

    try {
        $pdo->beginTransaction();

        $subtotal = floatval($data['subtotal']);
        $tax = floatval($data['tax']);
        $discount = floatval($data['discount']);
        $total = floatval($data['total']);
        $amount_paid = floatval($data['amount_paid']);
        $change_returned = floatval($data['change_returned']);
        $payment_method = sanitize_input($data['payment_method']);
        $payment_reference = sanitize_input($data['payment_reference'] ?? null);
        $user_id = $_SESSION['user_id'];
        
        // Generate Reference No
        $ref_no = 'SALE-' . date('YmdHis') . '-' . rand(100, 999);

        // Insert Sale
        $stmt = $pdo->prepare("INSERT INTO sales (reference_no, user_id, subtotal, discount, tax_amount, total, amount_paid, change_returned, payment_method, payment_reference) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$ref_no, $user_id, $subtotal, $discount, $tax, $total, $amount_paid, $change_returned, $payment_method, $payment_reference]);
        
        $sale_id = $pdo->lastInsertId();

        // Process Items
        $stmtItem = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)");
        $stmtUpdateStock = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?");

        foreach ($cart as $item) {
            $pid = intval($item['id']);
            $qty = intval($item['qty']);
            $price = floatval($item['price']);
            $item_sub = $price * $qty;

            // Update Stock
            $stmtUpdateStock->execute([$qty, $pid, $qty]);
            if ($stmtUpdateStock->rowCount() == 0) {
                throw new Exception("Product ID $pid is out of stock or insufficient quantity.");
            }

            // Insert Item
            $stmtItem->execute([$sale_id, $pid, $qty, $price, $item_sub]);
        }

        // Log Activity
        log_activity($pdo, $user_id, 'sale_completed', "Sale $ref_no completed. Total: $total");

        // Notify Admins
        $notif_title = "💰 New Sale Completed!";
        $notif_message = "Sale reference $ref_no was completed successfully for $" . number_format($total, 2) . ". Payment Method: $payment_method.";
        $stmtNotif = $pdo->prepare("INSERT INTO notifications (title, message, type) VALUES (?, ?, 'success')");
        $stmtNotif->execute([$notif_title, $notif_message]);

        $pdo->commit();

        json_response([
            'success' => true,
            'message' => 'Sale completed successfully',
            'sale_id' => $sale_id,
            'reference_no' => $ref_no
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Sale Error: " . $e->getMessage());
        json_response(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    json_response(['success' => false, 'message' => 'Invalid request method'], 405);
}
