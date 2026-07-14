<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../includes/db.php';

$page_title = 'Admin Dashboard - DigitalDine';

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM menu_items");
    $menu_count = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders");
    $orders_count = $stmt->fetch()['total'];

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE status = ?");
    $stmt->execute(['pending']);
    $pending_orders = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM reservations");
    $reservations_count = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM tables");
    $tables_count = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as active FROM tables WHERE status = 'active'");
    $active_tables = $stmt->fetch()['active'];

    $stmt = $pdo->query("SELECT o.id, o.customer_name, o.total_price, o.status, o.created_at, t.table_number FROM orders o LEFT JOIN tables t ON o.table_id = t.id ORDER BY o.created_at DESC LIMIT 5");
    $recent_orders = $stmt->fetchAll();

    $stmt = $pdo->query("SELECT id, customer_name, date, time, status FROM reservations ORDER BY created_at DESC LIMIT 5");
    $recent_reservations = $stmt->fetchAll();

    $stmt = $pdo->query("SELECT t.table_number, COUNT(o.id) as order_count, SUM(o.total_price) as total_revenue FROM tables t LEFT JOIN orders o ON t.id = o.table_id GROUP BY t.id, t.table_number ORDER BY t.table_number");
    $orders_by_table = $stmt->fetchAll();
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
    <link rel="stylesheet" href="/restaurant_project/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
            <div class="admin-actions">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                <a href="logout.php" class="btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div class="admin-nav">
            <ul>
                <li><a href="index.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="manage_banners.php"><i class="fas fa-images"></i> Manage Banners</a></li>
                <li><a href="manage_menu.php"><i class="fas fa-utensils"></i> Manage Menu</a></li>
                <li><a href="manage_orders.php"><i class="fas fa-shopping-cart"></i> Manage Orders</a></li>
                <li><a href="manage_tables.php"><i class="fas fa-chair"></i> Manage Tables</a></li>
            </ul>
        </div>

        <div class="admin-content">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-utensils"></i></div>
                    <div class="stat-content">
                        <h3><?php echo $menu_count; ?></h3>
                        <p>Menu Items</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                    <div class="stat-content">
                        <h3><?php echo $orders_count; ?></h3>
                        <p>Total Orders</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-content">
                        <h3><?php echo $pending_orders; ?></h3>
                        <p>Pending Orders</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div class="stat-content">
                        <h3><?php echo $reservations_count; ?></h3>
                        <p>Reservations</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-chair"></i></div>
                    <div class="stat-content">
                        <h3><?php echo $tables_count; ?></h3>
                        <p>Total Tables</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-content">
                        <h3><?php echo $active_tables; ?></h3>
                        <p>Active Tables</p>
                    </div>
                </div>
            </div>

            <div class="recent-activity">
                <div class="activity-section">
                    <h3><i class="fas fa-shopping-cart"></i> Recent Orders</h3>
                    <div class="activity-list">
                        <?php if (empty($recent_orders)): ?>
                            <p>No orders yet.</p>
                        <?php else: ?>
                            <?php foreach ($recent_orders as $order): ?>
                                <div class="activity-item">
                                    <div class="activity-info">
                                        <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                                        <p>Order #<?php echo $order['id']; ?> - Rs <?php echo number_format($order['total_price'], 0); ?>
                                            <?php if ($order['table_number']): ?>
                                                <br><small>Table: <?php echo htmlspecialchars($order['table_number']); ?></small>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <div class="activity-meta">
                                        <span class="status status-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span>
                                        <small><?php echo date('M d, H:i', strtotime($order['created_at'])); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="activity-section">
                    <h3><i class="fas fa-calendar-alt"></i> Recent Reservations</h3>
                    <div class="activity-list">
                        <?php if (empty($recent_reservations)): ?>
                            <p>No reservations yet.</p>
                        <?php else: ?>
                            <?php foreach ($recent_reservations as $reservation): ?>
                                <div class="activity-item">
                                    <div class="activity-info">
                                        <strong><?php echo htmlspecialchars($reservation['customer_name']); ?></strong>
                                        <p><?php echo date('M d, Y', strtotime($reservation['date'])); ?> at <?php echo $reservation['time']; ?></p>
                                    </div>
                                    <div class="activity-meta">
                                        <span class="status status-<?php echo $reservation['status']; ?>"><?php echo ucfirst($reservation['status']); ?></span>
                                        <small>ID: <?php echo $reservation['id']; ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="activity-section">
                    <h3><i class="fas fa-utensils"></i> Orders by Table</h3>
                    <div class="activity-list">
                        <?php if (empty($orders_by_table)): ?>
                            <p>No table data available.</p>
                        <?php else: ?>
                            <?php foreach ($orders_by_table as $table_data): ?>
                                <div class="activity-item">
                                    <div class="activity-info">
                                        <strong>Table <?php echo htmlspecialchars($table_data['table_number']); ?></strong>
                                        <p><?php echo $table_data['order_count']; ?> orders - Rs <?php echo number_format($table_data['total_revenue'] ?? 0, 0); ?> total</p>
                                    </div>
                                    <div class="activity-meta">
                                        <small>Last updated: <?php echo date('M d, H:i'); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            display: flex;
            align-items: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            font-size: 2rem;
            margin-right: 1rem;
            opacity: 0.8;
        }

        .stat-content h3 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .stat-content p {
            opacity: 0.8;
            font-size: 0.9rem;
        }

        .recent-activity {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
        }

        .activity-section h3 {
            margin-bottom: 1rem;
            color: #333;
        }

        .activity-list {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
        }

        .activity-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e9ecef;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-info strong {
            display: block;
            margin-bottom: 0.25rem;
        }

        .activity-meta {
            text-align: right;
            color: #666;
        }

        .status {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            margin-bottom: 0.25rem;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-confirmed {
            background: #d4edda;
            color: #155724;
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

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</body>

</html>