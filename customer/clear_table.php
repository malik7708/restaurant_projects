<?php
session_start();

// Clear table session data
if (isset($_SESSION['table_id'])) {
    unset($_SESSION['table_id']);
}
if (isset($_SESSION['table_number'])) {
    unset($_SESSION['table_number']);
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Table session cleared'
]);
