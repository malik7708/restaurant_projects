<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Test QR code functionality
$table_param = $_GET['table'] ?? null;
$message = '';

if ($table_param) {
    $table_number = trim($table_param);

    try {
        $stmt = $pdo->prepare("SELECT id, table_number FROM tables WHERE table_number = ? AND status = 'active'");
        $stmt->execute([$table_number]);
        $table = $stmt->fetch();

        if ($table) {
            $_SESSION['table_id'] = $table['id'];
            $_SESSION['table_number'] = $table['table_number'];
            $message = "✅ Table {$table['table_number']} selected successfully!";
        } else {
            $message = "❌ Table {$table_number} not found or inactive.";
        }
    } catch (PDOException $e) {
        $message = "❌ Database error: " . $e->getMessage();
    }
}

// Get current table from session
$current_table = null;
if (isset($_SESSION['table_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT id, table_number FROM tables WHERE id = ? AND status = 'active'");
        $stmt->execute([$_SESSION['table_id']]);
        $current_table = $stmt->fetch();
    } catch (PDOException $e) {
        unset($_SESSION['table_id'], $_SESSION['table_number']);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Test - DigitalDine</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="container" style="padding: 2rem;">
        <h1>🔍 QR Code Dining System Test</h1>

        <?php if ($message): ?>
            <div style="padding: 1rem; margin: 1rem 0; border-radius: 8px; background: <?php echo strpos($message, '✅') === 0 ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo strpos($message, '✅') === 0 ? '#155724' : '#721c24'; ?>;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div style="margin: 2rem 0;">
            <h2>📋 Available Tables</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; margin: 1rem 0;">
                <?php
                try {
                    $stmt = $pdo->query("SELECT table_number FROM tables WHERE status = 'active' ORDER BY table_number");
                    $tables = $stmt->fetchAll();
                    foreach ($tables as $table) {
                        $url = "http://localhost/restaurant_project/customer/index.php?table=" . urlencode($table['table_number']);
                        echo "<div style='border: 1px solid #ddd; padding: 1rem; border-radius: 8px;'>";
                        echo "<h3>Table {$table['table_number']}</h3>";
                        echo "<a href='{$url}' target='_blank' style='display: inline-block; margin: 0.5rem 0; padding: 0.5rem 1rem; background: #667eea; color: white; text-decoration: none; border-radius: 4px;'>Test QR Link</a>";
                        echo "<br><small style='color: #666;'>{$url}</small>";
                        echo "</div>";
                    }
                } catch (PDOException $e) {
                    echo "<p>Error loading tables: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
                ?>
            </div>
        </div>

        <div style="margin: 2rem 0;">
            <h2>📊 Current Session Status</h2>
            <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px;">
                <?php if ($current_table): ?>
                    <p><strong>✅ Active Table:</strong> <?php echo htmlspecialchars($current_table['table_number']); ?> (ID: <?php echo $current_table['id']; ?>)</p>
                    <p><strong>Session Data:</strong></p>
                    <ul>
                        <li>table_id: <?php echo $_SESSION['table_id'] ?? 'Not set'; ?></li>
                        <li>table_number: <?php echo $_SESSION['table_number'] ?? 'Not set'; ?></li>
                    </ul>
                <?php else: ?>
                    <p>❌ No table selected in current session.</p>
                <?php endif; ?>
            </div>
        </div>

        <div style="margin: 2rem 0;">
            <h2>🔗 Test Links</h2>
            <ul>
                <li><a href="index.php">🏠 Regular Homepage</a></li>
                <li><a href="index.php?table=T01">📱 Homepage with Table T01</a></li>
                <li><a href="index.php?table=T02">📱 Homepage with Table T02</a></li>
                <li><a href="index.php?table=INVALID">❌ Homepage with Invalid Table</a></li>
            </ul>
        </div>

        <div style="margin: 2rem 0;">
            <h2>🧹 Session Management</h2>
            <a href="?clear_session=1" style="display: inline-block; margin: 0.5rem 0; padding: 0.5rem 1rem; background: #dc3545; color: white; text-decoration: none; border-radius: 4px;">Clear Table Session</a>
        </div>
    </div>

    <?php
    // Clear session if requested
    if (isset($_GET['clear_session'])) {
        unset($_SESSION['table_id'], $_SESSION['table_number']);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    ?>
</body>

</html>