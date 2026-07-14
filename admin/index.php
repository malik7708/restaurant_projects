<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../includes/db.php';

// Sync table statuses: mark tables 'inactive' if they have active orders
try {
    $pdo->beginTransaction();
    // Mark tables inactive if they have non-final orders
    $pdo->exec("UPDATE tables SET status = 'inactive' WHERE id IN (SELECT DISTINCT table_id FROM orders WHERE table_id IS NOT NULL AND status IN ('pending','confirmed','preparing','ready'))");
    // Mark tables active if they do not have non-final orders
    $pdo->exec("UPDATE tables SET status = 'active' WHERE id NOT IN (SELECT DISTINCT table_id FROM orders WHERE table_id IS NOT NULL AND status IN ('pending','confirmed','preparing','ready'))");
    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    // Continue without failing dashboard
}

$page_title = 'Admin Dashboard - DigitalDine';
$success = '';
$errors = [];
$reserved_tables = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_reserved_table'])) {
    $table_id = intval($_POST['table_id'] ?? 0);

    if ($table_id > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE tables SET status = 'active' WHERE id = ? AND status = 'inactive'");
            $stmt->execute([$table_id]);
            if ($stmt->rowCount() > 0) {
                $success = 'Table has been reset and is now active.';
                // Also clear table association from any non-final orders so table stays free
                try {
                    $ust = $pdo->prepare("UPDATE orders SET table_id = NULL WHERE table_id = ? AND status IN ('pending','confirmed','preparing','ready')");
                    $ust->execute([$table_id]);
                } catch (PDOException $e) {
                    // log but don't fail
                    error_log('Failed to clear orders for table reset: ' . $e->getMessage());
                }

                // Clear any session files that reference this table (so sessions won't reassign it)
                try {
                    $save_path = session_save_path();
                    if (empty($save_path)) $save_path = sys_get_temp_dir();
                    if (is_dir($save_path)) {
                        $entries = scandir($save_path);
                        foreach ($entries as $entry) {
                            if ($entry === '.' || $entry === '..') continue;
                            $path = rtrim($save_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $entry;
                            if (!is_file($path)) continue;
                            $contents = @file_get_contents($path);
                            if ($contents === false || $contents === '') continue;
                            if (strpos($contents, 'table_id|i:' . (int)$table_id . ';') !== false) {
                                @unlink($path);
                            }
                        }
                    }
                } catch (Exception $e) {
                    // ignore
                }
            } else {
                $errors[] = 'Unable to reset table. It may already be active or not exist.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $errors[] = 'Please select a reserved table to reset.';
    }
}

// Get dashboard statistics
try {
    // Total menu items
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM menu_items");
    $menu_count = $stmt->fetch()['total'];

    // Total orders
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders");
    $orders_count = $stmt->fetch()['total'];

    // Pending orders
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE status = ?");
    $stmt->execute(['pending']);
    $pending_orders = $stmt->fetch()['total'];

    // Total reservations
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM reservations");
    $reservations_count = $stmt->fetch()['total'];

    // Table statistics
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM tables");
    $tables_count = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as active FROM tables WHERE status = 'active'");
    $active_tables = $stmt->fetch()['active'];

    // Recent orders with table info
    $stmt = $pdo->query("SELECT o.id, o.customer_name, o.total_price, o.status, o.created_at, t.table_number FROM orders o LEFT JOIN tables t ON o.table_id = t.id ORDER BY o.created_at DESC LIMIT 5");
    $recent_orders = $stmt->fetchAll();

    // Recent reservations
    $stmt = $pdo->query("SELECT id, customer_name, date, time, status FROM reservations ORDER BY created_at DESC LIMIT 5");
    $recent_reservations = $stmt->fetchAll();

    // Orders by table
    $stmt = $pdo->query("SELECT t.table_number, COUNT(o.id) as order_count, SUM(o.total_price) as total_revenue FROM tables t LEFT JOIN orders o ON t.id = o.table_id GROUP BY t.id, t.table_number ORDER BY t.table_number");
    $orders_by_table = $stmt->fetchAll();

    // Reserved tables for reset option
    $stmt = $pdo->query("SELECT id, table_number FROM tables WHERE status = 'inactive' ORDER BY table_number");
    $reserved_tables = $stmt->fetchAll();
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

            <div class="table-reset-card" style="margin-bottom: 1.5rem;">
                <div class="card" style="padding: 1.5rem; border-radius: 12px; background: white; box-shadow: 0 8px 20px rgba(0,0,0,0.08);">
                    <h3 style="margin-top: 0; margin-bottom: 1rem;">Reset Reserved Table</h3>
                    <form method="POST" style="display: grid; gap: 1rem; max-width: 500px;">
                        <select name="table_id" required style="padding: 0.9rem 1rem; border: 1px solid #ddd; border-radius: 10px;">
                            <option value="">Select a reserved table</option>
                            <?php foreach ($reserved_tables as $table): ?>
                                <option value="<?php echo htmlspecialchars($table['id']); ?>">Table <?php echo htmlspecialchars($table['table_number']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="reset_reserved_table" class="btn btn-primary" style="width: fit-content;">Reset Table</button>
                        <?php if (empty($reserved_tables)): ?>
                            <p style="margin: 0; color: #666;">No reserved tables available for reset.</p>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $menu_count; ?></h3>
                        <p>Menu Items</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $orders_count; ?></h3>
                        <p>Total Orders</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $pending_orders; ?></h3>
                        <p>Pending Orders</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $reservations_count; ?></h3>
                        <p>Reservations</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chair"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $tables_count; ?></h3>
                        <p>Total Tables</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $active_tables; ?></h3>
                        <p>Active Tables</p>
                    </div>
                </div>

            </div>

            <!-- Recent Activity -->
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

                <!-- Orders by Table -->
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
            padding: 1rem 0;
            border-bottom: 1px solid #e1e5e9;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-info strong {
            color: #333;
        }

        .activity-info p {
            color: #666;
            font-size: 0.9rem;
            margin: 0.2rem 0;
        }

        .activity-meta {
            text-align: right;
        }

        .activity-meta small {
            color: #666;
            display: block;
            font-size: 0.8rem;
        }

        .status {
            display: inline-block;
            padding: 0.2rem 0.5rem;
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
    </style>
</body>

</html>