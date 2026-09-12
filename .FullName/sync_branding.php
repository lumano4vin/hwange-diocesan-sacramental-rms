<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Branding Synchronization Tool
 * 
 * Mass-renames "Hwange Diocese RMS" to "Hwange Diocese RMS" to ensure consistent branding.
 */

$root = dirname(__DIR__); // Get the parent directory (project root)
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

$count = 0;
foreach ($files as $file) {
    if ($file->isDir()) continue;
    $ext = pathinfo($file->getFilename(), PATHINFO_EXTENSION);
    if (!in_array($ext, ['php', 'html', 'css', 'sql'])) continue;

    $content = file_get_contents($file->getPathname());
    if (strpos($content, 'Hwange Diocese RMS') !== false) {
        $updated = str_replace('Hwange Diocese RMS', 'Hwange Diocese RMS', $content);
        file_put_contents($file->getPathname(), $updated);
        echo "UPDATED: " . $file->getFilename() . "\n";
        $count++;
    }
}

echo "\nBranding Synchronization Complete. Total files updated: $count\n";
?>
