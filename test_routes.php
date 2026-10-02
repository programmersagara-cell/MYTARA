<?php
/**
 * Route test: verifies ticketing pages render correctly after login.
 * Uses curl via exec() to handle cookies + CSRF properly.
 * Run: php test_routes.php
 */

$base = getenv('ITARA_TEST_BASE') ?: 'http://localhost/Itara';
$jar = sys_get_temp_dir() . '/itara_test_cookies.txt';
@unlink($jar);

// 1. Get login page + CSRF token
$loginPage = shell_exec('curl.exe -s -c ' . escapeshellarg($jar) . ' ' . $base . '/login 2>&1');
$csrf = null;
if (preg_match('/name="_csrf_token" value="([^"]+)"/', (string)$loginPage, $m)) {
    $csrf = $m[1];
}
echo "CSRF token: " . ($csrf ? substr($csrf, 0, 8) . '...' : 'NOT FOUND') . "\n";
if (!$csrf) {
    echo "FAIL: Could not retrieve CSRF token.\n";
    exit(1);
}

// 2. Login
$loginCmd = 'curl.exe -s -b ' . escapeshellarg($jar) . ' -c ' . escapeshellarg($jar) .
' -d ' . escapeshellarg("login=admin&password=admin123&_csrf_token={$csrf}") .
    ' -o /dev/null -w "%{http_code} %{redirect_url}" ' . $base . '/login 2>&1';
$loginResult = shell_exec($loginCmd);
echo "Login result: {$loginResult}\n";

// 3. Test authenticated pages
$pages = [
    '/tickets'            => 'Submit New Concern',
    '/tickets/manage'     => 'Manage',
    '/tickets/history'    => 'History',
    '/tickets/analytics'  => 'Monthly',
    '/tickets/instructions' => 'Instruction',
    '/tickets/backup'     => 'Backup',
    '/dashboard'          => 'Dashboard',
    '/assets'             => 'Asset',
];

$allOk = true;
foreach ($pages as $path => $needle) {
    $cmd = 'curl.exe -s -b ' . escapeshellarg($jar) . ' -c ' . escapeshellarg($jar) . ' ' . $base . $path . ' 2>&1';
    $content = shell_exec($cmd);
    $status = (int) shell_exec('curl.exe -s -o /dev/null -w "%{http_code}" -b ' . escapeshellarg($jar) . ' ' . $base . $path . ' 2>&1');
    $found = is_string($content) && stripos($content, $needle) !== false;
    $label = $found ? 'OK' : 'CHECK';
    if (!$found) {
        $allOk = false;
    }
    printf("[%s] %-24s HTTP %s (needle: '%s' %s)\n", $label, $path, $status, $needle, $found ? 'found' : 'NOT found');
}

echo "\n" . ($allOk ? "ALL PAGES RENDER OK\n" : "SOME PAGES NEED REVIEW\n");
