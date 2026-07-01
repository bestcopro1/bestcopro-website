<?php
function bestcopro_session_context()
{
    // The app root on disk is where this file lives. Stripping the script's
    // app-relative path from its URL yields the app's base URL, so every page
    // (including controllers/ and export/) shares one session name and cookie
    // path whether the app is deployed at the docroot or in a subdirectory.
    $appRoot = str_replace('\\', '/', __DIR__);
    $scriptFile = isset($_SERVER['SCRIPT_FILENAME']) ? str_replace('\\', '/', (string) $_SERVER['SCRIPT_FILENAME']) : '';
    $scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : (isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '');
    if ($scriptName === '' && isset($_SERVER['REQUEST_URI'])) {
        $scriptName = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }
    $scriptName = str_replace('\\', '/', (string) $scriptName);

    $basePath = null;
    if ($scriptFile !== '' && $scriptName !== '' && strpos($scriptFile, $appRoot . '/') === 0) {
        $relative = substr($scriptFile, strlen($appRoot)); // e.g. "/login.php" or "/export/export_budget.php"
        $relativeLength = strlen($relative);
        if ($relativeLength > 0 && substr($scriptName, -$relativeLength) === $relative) {
            $basePath = substr($scriptName, 0, strlen($scriptName) - $relativeLength);
        }
    }

    if ($basePath === null) {
        // Fallback when disk and URL paths cannot be matched (e.g. symlinked
        // docroot): use the script's directory, minus known app subfolders.
        $basePath = str_replace('\\', '/', dirname($scriptName));
        if ($basePath === '.' || $basePath === '\\') {
            $basePath = '';
        }
        foreach (array('/controllers', '/export') as $appSubdir) {
            $subdirLength = strlen($appSubdir);
            if (substr($basePath, -$subdirLength) === $appSubdir) {
                $basePath = substr($basePath, 0, strlen($basePath) - $subdirLength);
                break;
            }
        }
    }

    $basePath = rtrim($basePath, '/');
    $cookiePath = $basePath === '' ? '/' : $basePath;
    $sessionSuffix = preg_replace('/[^A-Za-z0-9]/', '', strtoupper($cookiePath));
    $sessionName = 'BESTCOPRO' . ($sessionSuffix !== '' ? $sessionSuffix : 'ROOT');

    return array($sessionName, $cookiePath);
}

function bestcopro_is_https()
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    return isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
        && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https';
}

function bestcopro_expire_cookie($name, $path, $domain, $secure, $httpOnly)
{
    if (headers_sent()) {
        return;
    }

    setcookie($name, '', time() - 3600, $path, $domain, $secure, $httpOnly);
}

function bestcopro_expire_legacy_session_cookies($cookiePath, $domain, $secure)
{
    bestcopro_expire_cookie('PHPSESSID', $cookiePath, $domain, $secure, true);

    if ($cookiePath !== '/') {
        bestcopro_expire_cookie('PHPSESSID', '/', $domain, $secure, true);
    }
}

function bestcopro_start_session()
{
    // Keep staging and production cookies isolated on the same domain.
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    list($sessionName, $cookiePath) = bestcopro_session_context();
    $cookieParams = session_get_cookie_params();
    $domain = isset($cookieParams['domain']) ? $cookieParams['domain'] : '';
    $secure = bestcopro_is_https();

    session_name($sessionName);
    session_set_cookie_params(0, $cookiePath, $domain, $secure, true);
    session_start();
    bestcopro_expire_legacy_session_cookies($cookiePath, $domain, $secure);
}

function bestcopro_destroy_session()
{
    bestcopro_start_session();
    $_SESSION = array();

    $cookieParams = session_get_cookie_params();
    $path = isset($cookieParams['path']) ? $cookieParams['path'] : '/';
    $domain = isset($cookieParams['domain']) ? $cookieParams['domain'] : '';
    $secure = isset($cookieParams['secure']) ? (bool) $cookieParams['secure'] : bestcopro_is_https();
    $httpOnly = isset($cookieParams['httponly']) ? (bool) $cookieParams['httponly'] : true;

    bestcopro_expire_cookie(session_name(), $path, $domain, $secure, $httpOnly);
    bestcopro_expire_legacy_session_cookies($path, $domain, $secure);
    session_destroy();
}