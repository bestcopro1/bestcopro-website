<?php
// Temporary staging diagnostic — remove after the session investigation.
if (!isset($_GET['k']) || $_GET['k'] !== 'bd62d7ff057669646ea2fb29d2766b5f') {
    http_response_code(404);
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/session.php';
bestcopro_start_session();

$_SESSION['diag_counter'] = isset($_SESSION['diag_counter']) ? $_SESSION['diag_counter'] + 1 : 1;
$sessionId = session_id();
$sessionName = session_name();
$counter = $_SESSION['diag_counter'];

session_write_close();

$savePath = ini_get('session.save_path');
if ($savePath === '' || $savePath === false) {
    $savePath = sys_get_temp_dir();
}
// Strip "N;/path" prefix format if present.
if (strpos($savePath, ';') !== false) {
    $parts = explode(';', $savePath);
    $savePath = end($parts);
}
$sessionFile = rtrim($savePath, '/\\') . '/sess_' . $sessionId;

echo "counter=" . $counter . "\n";
echo "session_name=" . $sessionName . "\n";
echo "session_id=" . $sessionId . "\n";
echo "php_version=" . PHP_VERSION . "\n";
echo "save_path=" . $savePath . "\n";
echo "save_path_exists=" . var_export(is_dir($savePath), true) . "\n";
echo "save_path_writable=" . var_export(is_writable($savePath), true) . "\n";
echo "session_file_exists_after_write=" . var_export(file_exists($sessionFile), true) . "\n";
$free = @disk_free_space($savePath);
echo "disk_free_space=" . ($free === false ? 'unknown' : sprintf('%.1f MB', $free / 1048576)) . "\n";

$testFile = rtrim($savePath, '/\\') . '/bestcopro_diag_write_test.txt';
$writeOk = @file_put_contents($testFile, 'x');
echo "direct_write_test=" . var_export($writeOk !== false, true) . "\n";
if ($writeOk !== false) {
    @unlink($testFile);
}

$errorLog = __DIR__ . '/error_log';
if (is_readable($errorLog)) {
    $lines = file($errorLog);
    echo "\n--- last 15 lines of error_log ---\n";
    echo implode('', array_slice($lines, -15));
} else {
    echo "error_log=not readable or absent\n";
}
