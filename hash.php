<?php
/**
 * Activeance - Password Hash Generator
 * Use this once to generate a real bcrypt hash for the admin password.
 *
 * Usage:  http://localhost/activeance/backend/hash.php?pw=YourNewPassword
 * Delete this file after you've generated your hash!
 */

// Safety: block this utility in production
if (($_SERVER['REMOTE_ADDR'] ?? '') !== '127.0.0.1' && ($_SERVER['REMOTE_ADDR'] ?? '') !== '::1') {
    http_response_code(403);
    exit("Forbidden — hash.php only runs on localhost.");
}

header('Content-Type: text/plain; charset=UTF-8');

$password = $_GET['pw'] ?? 'Activeance2024!';

// Generate a fresh bcrypt hash ($2y$, cost 10 by default — recommended)
$hash = password_hash($password, PASSWORD_DEFAULT);

// Verify it immediately (sanity check)
$ok = password_verify($password, $hash);

echo "Password : {$password}\n";
echo "Hash     : {$hash}\n";
echo "Verified : " . ($ok ? "✅ YES" : "❌ NO") . "\n\n";
echo "Copy the hash above into your users table:\n";
echo "  UPDATE users SET password_hash = '{$hash}' WHERE username = 'admin';\n";