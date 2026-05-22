<?php
require_once '../includes/db.php';
echo "<h1>System Diagnostic</h1>";
echo "<b>Current Folder:</b> " . __DIR__ . "<br>";
echo "<b>Root Folder:</b> " . dirname(__DIR__) . "<br>";
echo "<b>Database File:</b> " . realpath(dirname(__DIR__) . "/database.sqlite") . "<br>";

$username = 'stanislaus_lumano';
$u = getDB()->prepare("SELECT * FROM users WHERE username = ?");
$u->execute([$username]);
$user = $u->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "<h2 style='color:green'>User '$username' FOUND in this database.</h2>";
    echo "<pre>" . print_r($user, true) . "</pre>";
    $pass = "Hwange2026!";
    if (password_verify($pass, $user['password_hash'])) {
        echo "<h3 style='color:green'>PASSWORD VERIFIED OK!</h3>";
    } else {
        echo "<h3 style='color:red'>PASSWORD VERIFICATION FAILED!</h3>";
    }
} else {
    echo "<h2 style='color:red'>User '$username' NOT FOUND in this database.</h2>";
    // Check for old username
    $u = getDB()->prepare("SELECT * FROM users WHERE username = 'Francis Xavier'");
    $u->execute();
    $old = $u->fetch();
    if ($old) {
        echo "<p>Found user with OLD username 'Francis Xavier'. This database is NOT updated.</p>";
    }
}
