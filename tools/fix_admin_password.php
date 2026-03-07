<?php
require_once __DIR__ . '/../includes/db.php';

$password = 'admin123';
$hashed = password_hash($password, PASSWORD_DEFAULT);

echo "Generating hash for 'admin123': \n";
echo $hashed . "\n\n";

echo "Updating database...\n";

try {
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
    $stmt->execute([$hashed]);
    
    echo "✓ Password updated successfully!\n";
    
    // Verify
    $stmt = $pdo->prepare("SELECT username, password FROM users WHERE username = 'admin'");
    $stmt->execute();
    $user = $stmt->fetch();
    
    echo "\nCurrent admin record:\n";
    echo "Username: " . $user['username'] . "\n";
    echo "Password hash: " . $user['password'] . "\n";
    
    // Test the hash
    echo "\nTesting 'admin123' against stored hash: ";
    var_export(password_verify('admin123', $user['password']));
    echo "\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
