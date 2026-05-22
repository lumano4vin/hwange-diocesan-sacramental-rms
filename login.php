<?php
/**
 * Redirect to the premium home login page
 */
$query = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
// Map legacy parameters
if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $name = isset($_GET['name']) ? '&name=' . urlencode($_GET['name']) : '';
    header("Location: index.php?msg=logged_out" . $name);
} else {
    header("Location: index.php" . $query);
}
exit();
