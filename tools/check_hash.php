<?php
$hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
echo "Checking hash against 'admin123' and 'password'...\n";
echo "admin123: "; var_export(password_verify('admin123', $hash)); echo "\n";
echo "password: "; var_export(password_verify('password', $hash)); echo "\n";
