<?php

/**
 * Admin - Manage Tables
 * CRUD operations for restaurant tables in QR Dining System
 */

session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../includes/db.php';

$page_title = 'Manage Tables - Admin';
$errors = [];
$success = '';

function resetTableSessionsForTable($table_id, $table_number)
{
    $save_path = session_save_path();
    if (empty($save_path)) {
        $save_path = sys_get_temp_dir();
    }

    if (!is_dir($save_path)) {
        return 0;
    }

    $cleared = 0;
    $entries = scandir($save_path);

    if ($entries === false) {
        return 0;
    }

    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        $path = rtrim($save_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $entry;
        if (!is_file($path)) {
            continue;
        }

        $contents = @file_get_contents($path);
        if ($contents === false || $contents === '') {
            continue;
        }

        $matched_table_id = strpos($contents, 'table_id|i:' . (int)$table_id . ';') !== false;
        $matched_table_number = strpos($contents, 'table_number|s:' . strlen($table_number) . ':"' . $table_number . '";') !== false;

        if ($matched_table_id || $matched_table_number) {
            @unlink($path);
            $cleared++;
        }
    }

    return $cleared;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'add_table') {
            // Add new table
            $table_number = trim($_POST['table_number'] ?? '');

            if (empty($table_number)) {
                $errors[] = 'Table number is required';
            } else {
                try {
                    // Check if table number already exists
                    $stmt = $pdo->prepare("SELECT id FROM tables WHERE table_number = ?");
                    $stmt->execute([$table_number]);
                    if ($stmt->fetch()) {
                        $errors[] = 'Table number already exists';
                    } else {
                        // Insert new table
                        $stmt = $pdo->prepare("INSERT INTO tables (table_number, status) VALUES (?, 'active')");
                        $stmt->execute([$table_number]);
                        $success = 'Table added successfully!';
                    }
                } catch (PDOException $e) {
                    $errors[] = 'Database error: ' . $e->getMessage();
                }
            }
        } elseif ($action === 'update_table') {
            // Update table
            $table_id = (int)($_POST['table_id'] ?? 0);
            $table_number = trim($_POST['table_number'] ?? '');
            $status = $_POST['status'] ?? 'active';

            if (empty($table_number)) {
                $errors[] = 'Table number is required';
            } elseif (!in_array($status, ['active', 'inactive'])) {
                $errors[] = 'Invalid status';
            } else {
                try {
                    // Check if table number already exists for another table
                    $stmt = $pdo->prepare("SELECT id FROM tables WHERE table_number = ? AND id != ?");
                    $stmt->execute([$table_number, $table_id]);
                    if ($stmt->fetch()) {
                        $errors[] = 'Table number already exists';
                    } else {
                        // Update table
                        $stmt = $pdo->prepare("UPDATE tables SET table_number = ?, status = ? WHERE id = ?");
                        $stmt->execute([$table_number, $status, $table_id]);
                        $success = 'Table updated successfully!';
                    }
                } catch (PDOException $e) {
                    $errors[] = 'Database error: ' . $e->getMessage();
                }
            }
        } elseif ($action === 'delete_table') {
            // Delete table
            $table_id = (int)($_POST['table_id'] ?? 0);

            try {
                $stmt = $pdo->prepare("DELETE FROM tables WHERE id = ?");
                $stmt->execute([$table_id]);
                $success = 'Table deleted successfully!';
            } catch (PDOException $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        } elseif ($action === 'toggle_status') {
            // Toggle table status
            $table_id = (int)($_POST['table_id'] ?? 0);

            try {
                // Get current status
                $stmt = $pdo->prepare("SELECT status FROM tables WHERE id = ?");
                $stmt->execute([$table_id]);
                $current = $stmt->fetch();

                if ($current) {
                    $new_status = $current['status'] === 'active' ? 'inactive' : 'active';
                    $stmt = $pdo->prepare("UPDATE tables SET status = ? WHERE id = ?");
                    $stmt->execute([$new_status, $table_id]);
                    $success = 'Table status updated successfully!';
                }
            } catch (PDOException $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        } elseif ($action === 'reset_table') {
            $table_id = (int)($_POST['table_id'] ?? 0);
            $table_number = trim($_POST['table_number'] ?? '');

            if ($table_id > 0) {
                $cleared_sessions = resetTableSessionsForTable($table_id, $table_number);
                $success = 'Table session reset completed. Cleared ' . $cleared_sessions . ' active session(s).';
            } else {
                $errors[] = 'Invalid table selected';
            }
        }
    }
}

// Get all tables
try {
    $stmt = $pdo->query("SELECT * FROM tables ORDER BY table_number ASC");
    $tables = $stmt->fetchAll();
} catch (PDOException $e) {
    $tables = [];
    $errors[] = 'Failed to load tables';
}

// Get table statistics
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM tables");
    $total_tables = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as active FROM tables WHERE status = 'active'");
    $active_tables = $stmt->fetch()['active'];
} catch (PDOException $e) {
    $total_tables = $active_tables = 0;
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
        .table-stats {
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

        .table-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
        }

        .table-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .table-header {
            background: #667eea;
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-content {
            padding: 1rem;
        }

        .table-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        .qr-code {
            text-align: center;
            margin: 1rem 0;
        }

        .qr-link {
            word-break: break-all;
            background: #f8f9fa;
            padding: 0.5rem;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-content {
            background: white;
            margin: 10% auto;
            padding: 2rem;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1><i class="fas fa-chair"></i> Manage Tables</h1>
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
                <li><a href="manage_tables.php" class="active"><i class="fas fa-chair"></i> Manage Tables</a></li>
            </ul>
        </div>

        <div class="admin-content">
            <div class="content-header">
                <h1><i class="fas fa-utensils"></i> Manage Tables</h1>
                <button class="btn btn-primary" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> Add New Table
                </button>
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

            <!-- Table Statistics -->
            <div class="table-stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_tables; ?></div>
                    <div>Total Tables</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $active_tables; ?></div>
                    <div>Active Tables</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_tables - $active_tables; ?></div>
                    <div>Inactive Tables</div>
                </div>
            </div>

            <!-- Tables Grid -->
            <div class="table-grid">
                <?php if (!empty($tables)): ?>
                    <?php foreach ($tables as $table): ?>
                        <div class="table-card">
                            <div class="table-header">
                                <h3>Table <?php echo htmlspecialchars($table['table_number']); ?></h3>
                                <span class="status-badge status-<?php echo $table['status']; ?>">
                                    <?php echo ucfirst($table['status']); ?>
                                </span>
                            </div>
                            <div class="table-content">
                                <div class="qr-code">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo urlencode('http://localhost/restaurant_project/customer/index.php?table=' . $table['table_number']); ?>" alt="QR Code">
                                    <div class="qr-link">
                                        <?php echo 'http://localhost/restaurant_project/customer/index.php?table=' . htmlspecialchars($table['table_number']); ?>
                                    </div>
                                </div>
                                <div class="table-actions">
                                    <button class="btn btn-small btn-secondary" onclick="openEditModal(<?php echo $table['id']; ?>, '<?php echo htmlspecialchars($table['table_number']); ?>', '<?php echo $table['status']; ?>')">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-small btn-warning" onclick="toggleStatus(<?php echo $table['id']; ?>)">
                                        <i class="fas fa-toggle-<?php echo $table['status'] === 'active' ? 'off' : 'on'; ?>"></i>
                                        <?php echo $table['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                    </button>
                                    <button class="btn btn-small btn-info" onclick="resetTableSession(<?php echo $table['id']; ?>, '<?php echo htmlspecialchars($table['table_number']); ?>')">
                                        <i class="fas fa-refresh"></i> Reset
                                    </button>
                                    <button class="btn btn-small btn-danger" onclick="deleteTable(<?php echo $table['id']; ?>, '<?php echo htmlspecialchars($table['table_number']); ?>')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-utensils"></i>
                        <h3>No tables found</h3>
                        <p>Add your first table to get started with QR dining.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add/Edit Table Modal -->
    <div id="tableModal" class="modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h2 id="modalTitle">Add New Table</h2>
                <button onclick="closeModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <form id="tableForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="add_table">
                <input type="hidden" name="table_id" id="tableId">

                <div class="form-group">
                    <label for="table_number">Table Number:</label>
                    <input type="text" id="table_number" name="table_number" required placeholder="e.g., T01, 101, A1">
                </div>

                <div class="form-group" id="statusGroup" style="display: none;">
                    <label for="status">Status:</label>
                    <select id="status" name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div style="text-align: right; margin-top: 1rem;">
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Table</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Table';
            document.getElementById('formAction').value = 'add_table';
            document.getElementById('tableId').value = '';
            document.getElementById('table_number').value = '';
            document.getElementById('statusGroup').style.display = 'none';
            document.getElementById('tableModal').style.display = 'block';
        }

        function openEditModal(id, number, status) {
            document.getElementById('modalTitle').textContent = 'Edit Table';
            document.getElementById('formAction').value = 'update_table';
            document.getElementById('tableId').value = id;
            document.getElementById('table_number').value = number;
            document.getElementById('status').value = status;
            document.getElementById('statusGroup').style.display = 'block';
            document.getElementById('tableModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('tableModal').style.display = 'none';
        }

        function toggleStatus(id) {
            if (confirm('Are you sure you want to change this table\'s status?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="table_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function deleteTable(id, number) {
            if (confirm(`Are you sure you want to delete Table ${number}? This action cannot be undone.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_table">
                    <input type="hidden" name="table_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function resetTableSession(id, number) {
            if (confirm(`Reset Table ${number} for active sessions? This will clear the current table selection for any active customer sessions using this table.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="reset_table">
                    <input type="hidden" name="table_id" value="${id}">
                    <input type="hidden" name="table_number" value="${number}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('tableModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>

</html>