<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

$page_title = 'Track Order - DigitalDine';

$order = null;
$error = '';
$searched = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $searched = true;
    $order_id = trim($_POST['order_id'] ?? '');

    if ($order_id === '') {
        $error = 'Please enter an order ID.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, table_id, customer_name, customer_email, customer_phone, items, total_price, status, created_at FROM orders WHERE id = ?");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($order) {
                $order['items'] = json_decode($order['items'], true);
                if (!is_array($order['items'])) {
                    $order['items'] = [];
                }
                // resolve table number if available
                $order['table_number'] = null;
                if (!empty($order['table_id'])) {
                    $tstmt = $pdo->prepare("SELECT table_number FROM tables WHERE id = ?");
                    $tstmt->execute([$order['table_id']]);
                    $trow = $tstmt->fetch(PDO::FETCH_ASSOC);
                    if ($trow && isset($trow['table_number'])) {
                        $order['table_number'] = $trow['table_number'];
                    }
                }
            } else {
                $error = 'No order found with that ID.';
            }
        } catch (PDOException $e) {
            $error = 'Unable to fetch order details.';
            error_log($e->getMessage());
        }
    }

    header('Content-Type: application/json');
    echo json_encode([
        'success' => $order ? true : false,
        'message' => $order ? 'Order found.' : $error,
        'order' => $order,
    ]);
    exit;
}

header('Location: /restaurant_project/customer/index.php');
exit;
