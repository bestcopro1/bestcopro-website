<?php
if (session_status() === PHP_SESSION_NONE) {
    $cookieParams = session_get_cookie_params();
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '');
    if ($scriptName === '' && isset($_SERVER['REQUEST_URI'])) {
        $scriptName = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }

    $scriptName = str_replace('\\', '/', (string) $scriptName);
    $segments = array_values(array_filter(explode('/', $scriptName), 'strlen'));
    $baseSegment = $segments[0] ?? '';
    $cookiePath = $baseSegment === '' ? '/' : '/' . $baseSegment;
    $sessionSuffix = preg_replace('/[^A-Za-z0-9]/', '', strtoupper($baseSegment));
    $sessionName = 'BESTCOPRO' . ($sessionSuffix !== '' ? $sessionSuffix : 'ROOT');
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');

    session_name($sessionName);
    session_set_cookie_params(0, $cookiePath, $cookieParams['domain'], $secure, true);
    session_start();

    if (!headers_sent()) {
        setcookie('PHPSESSID', '', time() - 3600, $cookiePath, $cookieParams['domain'], $secure, true);
        if ($cookiePath !== '/') {
            setcookie('PHPSESSID', '', time() - 3600, '/', $cookieParams['domain'], $secure, true);
        }
    }
}
$_SESSION = array();
$logoutCookieParams = session_get_cookie_params();
if (!headers_sent()) {
    setcookie(session_name(), '', time() - 3600, $logoutCookieParams['path'], $logoutCookieParams['domain'], (bool) $logoutCookieParams['secure'], true);
}
session_destroy();


header("Location: ./index.php"); ?>
