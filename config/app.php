<?php

$script = str_replace(
    '\\',
    '/',
    dirname($_SERVER['SCRIPT_NAME'] ?? '/')
);

foreach (
    [
        '/admin',
        '/manager',
        '/staff',
        '/applicant',
        '/customer',
        '/shop',
        '/account',
        '/portal'
    ] as $part
) {
    $p = strpos($script, $part);

    if ($p !== false) {
        $script = substr($script, 0, $p);
        break;
    }
}

if (!defined('BASE_URL')) {
    define('BASE_URL', rtrim($script, '/'));
}

function url($p = '')
{
    return BASE_URL . '/' . ltrim($p, '/');
}

function url_path($p = '')
{
    return url($p);
}

function asset_path($p = '')
{
    return url('assets/' . ltrim($p, '/'));
}

?>