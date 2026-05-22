<?php
/**
 * Hwange SRMS - Unified Setup Wizard
 * This script initializes the database schema and seeds the parishes/deaneries.
 */

// Basic styling for the setup page
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Hwange SRMS Setup Wizard</title>
    <style>
        body { font-family: sans-serif; background: #f7fafc; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .setup-card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 500px; }
        h1 { color: #2a4365; border-bottom: 2px solid #2a4365; padding-bottom: 0.5rem; margin-top: 0; }
        .status { padding: 1rem; border-radius: 4px; margin: 1rem 0; font-family: monospace; font-size: 0.9rem; }
        .success { background: #c6f6d5; color: #22543d; border: 1px solid #9ae6b4; }
        .error { background: #fed7d7; color: #822727; border: 1px solid #feb2b2; }
        .btn { display: inline-block; background: #2a4365; color: white; padding: 0.8rem 1.5rem; text-decoration: none; border-radius: 4px; font-weight: bold; margin-top: 1rem; }
    </style>
</head>
<body>
<div class='setup-card'>
    <h1>SRMS Setup Wizard</h1>";

try {
    require_once 'includes/db.php';
    $pdo = getDB();

    echo "<div class='status success'>✓ Database connection established.</div>";

    // 1. Run Schema
    $schema = file_get_contents('schema.sql');
    if ($schema === false) throw new Exception("Could not find schema.sql");
    $pdo->exec($schema);
    echo "<div class='status success'>✓ Database schema initialized.</div>";

    // 2. Run Seeding
    require_once 'seed.php'; 
    // seed.php already does its own output, but we can wrap it or just rely on the echo in seed.php
    // Since seed.php echoes internally, it will appear here.

    echo "<div class='status success'>✓ Parishes, Deaneries, and Admin user created.</div>";
    
    echo "<p>Setup complete! You can now log in using the default credentials.</p>";
    echo "<ul>
            <li><strong>Username:</strong> admin</li>
            <li><strong>Password:</strong> admin123</li>
          </ul>";
    echo "<a href='index.php' class='btn'>Go to Login Page</a>";

} catch (Exception $e) {
    echo "<div class='status error'><strong>Setup Error:</strong> " . $e->getMessage() . "</div>";
    echo "<p>Please ensure your database settings in <strong>includes/db.php</strong> are correct and that the database server is running.</p>";
}

echo "</div></body></html>";
?>
