<?php

/**
 * Admin - Manage Waiter Calls
 * Handle waiter call requests from QR dining system
 */

session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../includes/db.php';

$page_title = 'Manage Waiter Calls - Admin';
$errors = [];
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'complete_call') {
            // Mark call as completed
            $call_id = (int)($_POST['call_id'] ?? 0);

            try {
                $stmt = $pdo->prepare("UPDATE waiter_calls SET status = 'completed', completed_at = NOW() WHERE id = ?");
                $stmt->execute([$call_id]);
                $success = 'Waiter call marked as completed!';
            } catch (PDOException $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        } elseif ($action === 'delete_call') {
            // Delete call
            $call_id = (int)($_POST['call_id'] ?? 0);

            try {
                $stmt = $pdo->prepare("DELETE FROM waiter_calls WHERE id = ?");
                $stmt->execute([$call_id]);
                $success = 'Waiter call deleted successfully!';
            } catch (PDOException $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Get waiter calls with table info
try {
    $stmt = $pdo->query("SELECT wc.*, t.table_number FROM waiter_calls wc JOIN tables t ON wc.table_id = t.id ORDER BY wc.created_at DESC");
    $waiter_calls = $stmt->fetchAll();

    // Get statistics
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM waiter_calls");
    $total_calls = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as pending FROM waiter_calls WHERE status = 'pending'");
    $pending_calls = $stmt->fetch()['pending'];

    $stmt = $pdo->query("SELECT COUNT(*) as completed FROM waiter_calls WHERE status = 'completed'");
    $completed_calls = $stmt->fetch()['completed'];
} catch (PDOException $e) {
    $waiter_calls = [];
    $errors[] = 'Failed to load waiter calls';
    $total_calls = $pending_calls = $completed_calls = 0;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .calls-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .calls-table {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .calls-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .calls-table th,
        .calls-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .calls-table th {
            background: #f8f9fa;
            font-weight: bold;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            margin-right: 0.5rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1><i class="fas fa-bell"></i> Manage Waiter Calls</h1>
            <div class="admin-actions">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
                <a href="logout.php" class="btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div class="admin-nav">
            <ul>
                <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="manage_banners.php"><i class="fas fa-images"></i> Manage Banners</a></li>
                <li><a href="manage_menu.php"><i class="fas fa-utensils"></i> Manage Menu</a></li>
                <li><a href="manage_orders.php"><i class="fas fa-shopping-cart"></i> Manage Orders</a></li>
                <li><a href="manage_tables.php"><i class="fas fa-chair"></i> Manage Tables</a></li>
                <li><a href="manage_waiter_calls.php" class="active"><i class="fas fa-bell"></i> Waiter Calls</a></li>
            </ul>
        </div>

        <div class="admin-content">
            <div class="content-header">
                <h1><i class="fas fa-bell"></i> Manage Waiter Calls</h1>
            </div>

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

            <!-- Waiter Calls Statistics -->
            <div class="calls-stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_calls; ?></div>
                    <div>Total Calls</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $pending_calls; ?></div>
                    <div>Pending Calls</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $completed_calls; ?></div>
                    <div>Completed Calls</div>
                </div>
            </div>

            <!-- Waiter Calls Table -->
            <div class="calls-table">
                <?php if (!empty($waiter_calls)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Table</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Completed At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($waiter_calls as $call): ?>
                                <tr>
                                    <td><?php echo $call['id']; ?></td>
                                    <td><strong>Table <?php echo htmlspecialchars($call['table_number']); ?></strong></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $call['status']; ?>">
                                            <?php echo ucfirst($call['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($call['created_at'])); ?></td>
                                    <td>
                                        <?php if ($call['completed_at']): ?>
                                            <?php echo date('M d, Y H:i', strtotime($call['completed_at'])); ?>
                                        <?php else: ?>
                                            <em>Not completed</em>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($call['status'] === 'pending'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="complete_call">
                                                <input type="hidden" name="call_id" value="<?php echo $call['id']; ?>">
                                                <button type="submit" class="btn btn-small btn-success" onclick="return confirm('Mark this waiter call as completed?')">
                                                    <i class="fas fa-check"></i> Complete
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_call">
                                            <input type="hidden" name="call_id" value="<?php echo $call['id']; ?>">
                                            <button type="submit" class="btn btn-small btn-danger" onclick="return confirm('Delete this waiter call?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-bell-slash"></i>
                        <h3>No waiter calls yet</h3>
                        <p>Waiter calls from QR dining tables will appear here.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>