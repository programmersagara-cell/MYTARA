<?php
/**
 * Script to add the retire route to index.php
 */
$file = dirname(__DIR__) . '/index.php';
$content = file_get_contents($file);

// Check if retire route already exists
if (strpos($content, "AssetController@retire") !== false) {
    echo "Retire route already exists.\n";
    exit(0);
}

// The search pattern - use single-quoted strings to avoid PHP variable interpolation
$search = '    $router->post(\'/assets/{id}/delete\', \'AssetController@destroy\');' . "\n\n    // Network Topology";
// Handle potential Windows line endings
if (strpos($content, $search) === false) {
    $search = '    $router->post(\'/assets/{id}/delete\', \'AssetController@destroy\');' . "\r\n\r\n    // Network Topology";
}

if (strpos($content, $search) === false) {
    echo "Pattern not found.\n";
    // Debug: look for the delete route line
    $lines = explode("\n", $content);
    foreach ($lines as $i => $line) {
        if (strpos($line, 'AssetController@destroy') !== false) {
            echo "Found destroy at line $i: " . trim($line) . "\n";
        }
    }
    exit(1);
}

// The replacement
$replace = '    $router->post(\'/assets/{id}/delete\', \'AssetController@destroy\');' . "\n    \$router->post('/assets/{id}/retire', 'AssetController@retire');\n\n    // Network Topology";
// Match line endings
if (strpos($content, "\r\n") !== false) {
    $replace = str_replace("\n", "\r\n", $replace);
}

$newContent = str_replace($search, $replace, $content);
file_put_contents($file, $newContent);
echo "Retire route added successfully.\n";
