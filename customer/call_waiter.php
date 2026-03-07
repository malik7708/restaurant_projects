<?php

/**
 * Call Waiter API Endpoint
 * Handles AJAX requests to call waiter from customer interface
 */

session_start();
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

// Check if request is AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Check if table is selected
if (!isset($_SESSION['table_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No table selected']);
    exit;
}

$table_id = $_SESSION['table_id'];

try {
    // Check if there's already a pending call for this table
    $stmt = $pdo->prepare("SELECT id FROM waiter_calls WHERE table_id = ? AND status = 'pending'");
    $stmt->execute([$table_id]);
    $existing_call = $stmt->fetch();

    if ($existing_call) {
        echo json_encode(['success' => false, 'message' => 'Waiter already called. Please wait for assistance.']);
        exit;
    }

    // Insert new waiter call
    $stmt = $pdo->prepare("INSERT INTO waiter_calls (table_id, status) VALUES (?, 'pending')");
    $stmt->execute([$table_id]);

    echo json_encode(['success' => true, 'message' => 'Waiter has been called successfully!']);
} catch (PDOException $e) {
    error_log("Call waiter error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
