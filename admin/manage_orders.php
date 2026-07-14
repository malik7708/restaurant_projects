<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once '../includes/db.php';

$page_title = 'Manage Orders - DigitalDine';

$errors = [];
$success = '';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_order_status'])) {
        $order_id = intval($_POST['order_id'] ?? 0);
        $status = $_POST['status'] ?? '';

        $valid_statuses = ['pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled'];

        if (!in_array($status, $valid_statuses)) {
            $errors[] = 'Invalid status';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
                $stmt->execute([$status, $order_id]);
                $success = 'Order status updated successfully!';
            } catch (PDOException $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['update_reservation_status'])) {
        $reservation_id = intval($_POST['reservation_id'] ?? 0);
        $status = $_POST['status'] ?? '';

        $valid_statuses = ['pending', 'confirmed', 'cancelled'];

        if (!in_array($status, $valid_statuses)) {
            $errors[] = 'Invalid status';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE reservations SET status = ? WHERE id = ?");
                $stmt->execute([$status, $reservation_id]);
                $success = 'Reservation status updated successfully!';
            } catch (PDOException $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Get all orders
try {
    $stmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC");
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Get all reservations
try {
    $stmt = $pdo->query("SELECT * FROM reservations ORDER BY created_at DESC");
    $reservations = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1><i class="fas fa-shopping-cart"></i> Manage Orders &amp; Reservations</h1>
            <div class="admin-actions">
                <a href="index.php" class="btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                <a href="logout.php" class="btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div class="admin-nav">
            <ul>
                <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="manage_banners.php"><i class="fas fa-images"></i> Manage Banners</a></li>
                <li><a href="manage_menu.php"><i class="fas fa-utensils"></i> Manage Menu</a></li>
                <li><a href="manage_orders.php" class="active"><i class="fas fa-shopping-cart"></i> Manage Orders</a></li>
            </ul>
        </div>

        <div class="admin-content">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Orders Section -->
            <div class="orders-section">
                <h3><i class="fas fa-shopping-cart"></i> Orders</h3>
                <?php if (empty($orders)): ?>
                    <p>No orders found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($order['customer_email'] ?? 'N/A'); ?><br>
                                                <?php echo htmlspecialchars($order['customer_phone'] ?? 'N/A'); ?></small>
                                        </td>
                                        <td>
                                            <?php
                                            $items = json_decode($order['items'], true);
                                            if ($items) {
                                                foreach ($items as $item) {
                                                    echo htmlspecialchars($item['name']) . ' (x' . $item['quantity'] . ')<br>';
                                                }
                                            } else {
                                                echo htmlspecialchars($order['items']);
                                            }
                                            ?>
                                        </td>
                                        <td>Rs <?php echo number_format($order['total_price'], 0); ?></td>
                                        <td>
                                            <span class="status status-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span>
                                        </td>
                                        <td><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <select name="status" onchange="this.form.submit()">
                                                    <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="confirmed" <?php echo $order['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                    <option value="preparing" <?php echo $order['status'] === 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                                                    <option value="ready" <?php echo $order['status'] === 'ready' ? 'selected' : ''; ?>>Ready</option>
                                                    <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                    <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                </select>
                                                <input type="hidden" name="update_order_status" value="1">
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Reservations Section -->
            <div class="reservations-section">
                <h3><i class="fas fa-calendar-alt"></i> Reservations</h3>
                <?php if (empty($reservations)): ?>
                    <p>No reservations found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Date &amp; Time</th>
                                    <th>Guests</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reservations as $reservation): ?>
                                    <tr>
                                        <td>#<?php echo $reservation['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($reservation['customer_name']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($reservation['email']); ?><br>
                                                <?php echo htmlspecialchars($reservation['phone']); ?></small>
                                        </td>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($reservation['date'])); ?><br>
                                            <small><?php echo $reservation['time']; ?></small>
                                        </td>
                                        <td><?php echo $reservation['guests']; ?></td>
                                        <td>
                                            <span class="status status-<?php echo $reservation['status']; ?>"><?php echo ucfirst($reservation['status']); ?></span>
                                        </td>
                                        <td><?php echo date('M d, Y H:i', strtotime($reservation['created_at'])); ?></td>
                                        <td>
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                                <select name="status" onchange="this.form.submit()">
                                                    <option value="pending" <?php echo $reservation['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="confirmed" <?php echo $reservation['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                    <option value="cancelled" <?php echo $reservation['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                </select>
                                                <input type="hidden" name="update_reservation_status" value="1">
                                            </form>
                                        </td>
                                    </tr>
                                    <?php if ($reservation['special_requests']): ?>
                                        <tr>
                                            <td colspan="7">
                                                <strong>Special Requests:</strong> <?php echo htmlspecialchars($reservation['special_requests']); ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <style>
        .orders-section,
        .reservations-section {
            margin-bottom: 3rem;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e1e5e9;
        }

        .table th {
            background: #f7fafc;
            font-weight: 600;
            color: #333;
        }

        .table tr:hover {
            background: #f8f9fa;
        }

        .status {
            display: inline-block;
            padding: 0.3rem 0.6rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .status-preparing {
            background: #cce5ff;
            color: #004085;
        }

        .status-ready {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-delivered {
            background: #d4edda;
            color: #155724;
        }

        select {
            padding: 0.3rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
        }
    </style>
</body>

</html>