<?php
// tools/sync_table_status.php
// One-time script to sync `tables.status` with active orders
// Run from CLI: php tools/sync_table_status.php
// Or in browser: http://localhost/restaurant_project/tools/sync_table_status.php

require_once __DIR__ . '/../includes/db.php';

header('Content-Type: text/plain; charset=utf-8');

echo "Starting table status sync...\n\n";

try {
    // Mark tables inactive if they have non-final orders
    $sqlInactive = "UPDATE tables SET status = 'inactive' WHERE id IN (
        SELECT DISTINCT table_id FROM orders WHERE table_id IS NOT NULL AND status IN ('pending','confirmed','preparing','ready')
    )";
    $affectedInactive = $pdo->exec($sqlInactive);

    // Mark tables active if they do not have non-final orders
    $sqlActive = "UPDATE tables SET status = 'active' WHERE id NOT IN (
        SELECT DISTINCT table_id FROM orders WHERE table_id IS NOT NULL AND status IN ('pending','confirmed','preparing','ready')
    )";
    $affectedActive = $pdo->exec($sqlActive);

    echo "Tables marked inactive (reserved): " . intval($affectedInactive) . "\n";
    echo "Tables marked active: " . intval($affectedActive) . "\n\n";

    // Show current table summary
    $stmt = $pdo->query("SELECT id, table_number, status FROM tables ORDER BY table_number");
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Current tables:\n";
    foreach ($tables as $t) {
        echo " - {$t['table_number']} (ID: {$t['id']}) => {$t['status']}\n";
    }

    echo "\nDone.\n";
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
}
