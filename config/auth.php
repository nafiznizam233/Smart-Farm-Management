<?php

require_once __DIR__ . '/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login()
{
    if (empty($_SESSION['user'])) {
        header('Location: ' . url_path('login.php'));
        exit;
    }
}

function manager_permission_for_path(): ?string
{
    $path = str_replace(
        '\\',
        '/',
        $_SERVER['SCRIPT_NAME'] ?? ''
    );

    $map = [
        '/manager/workers.php' => 'workers',
        '/manager/worker_attendance.php' => 'attendance',
        '/manager/worker_payroll.php' => 'payroll',
        '/manager/shop/' => 'products',
        '/manager/preorders.php' => 'preorders',
        '/admin/stores/' => 'stores',
        '/admin/inventory/' => 'inventory',
        '/admin/purchases/' => 'purchases',
        '/admin/operations/feed.php' => 'feed',
        '/admin/operations/medicine.php' => 'medicine',
        '/manager/deliveries.php' => 'delivery',
        '/manager/support.php' => 'support',
        '/manager/delivery_settlements.php' => 'settlements'
    ];

    foreach ($map as $needle => $perm) {
        if (strpos($path, $needle) !== false) {
            return $perm;
        }
    }

    return null;
}

function manager_has_permission(
    PDO $pdo,
    string $permission,
    ?int $uid = null
): bool {
    $uid = $uid ?: ($_SESSION['user']['id'] ?? 0);

    if (!$uid) {
        return false;
    }

    $st = $pdo->prepare(
        "SELECT allowed
         FROM manager_permissions
         WHERE manager_user_id = ?
         AND permission_key = ?
         LIMIT 1"
    );

    $st->execute([
        $uid,
        $permission
    ]);

    $v = $st->fetchColumn();

    return $v === false ? true : (bool) $v;
}

function require_role($roles)
{
    require_login();

    if (!in_array(
        $_SESSION['user']['role'],
        (array) $roles,
        true
    )) {
        http_response_code(403);
        die('Access denied');
    }

    if (
        $_SESSION['user']['role'] === 'manager'
        && isset($GLOBALS['pdo'])
    ) {
        $perm = manager_permission_for_path();

        if (
            $perm
            && !manager_has_permission(
                $GLOBALS['pdo'],
                $perm
            )
        ) {
            http_response_code(403);

            die(
                'This feature is disabled for your Manager account by the Owner.'
            );
        }
    }
}

function is_owner()
{
    return !empty($_SESSION['user'])
        && $_SESSION['user']['role'] === 'owner';
}

function is_manager()
{
    return !empty($_SESSION['user'])
        && $_SESSION['user']['role'] === 'manager';
}

function is_staff()
{
    return !empty($_SESSION['user'])
        && $_SESSION['user']['role'] === 'staff';
}

function is_applicant()
{
    return !empty($_SESSION['user'])
        && $_SESSION['user']['role'] === 'applicant';
}

function user_checked_in(
    PDO $pdo,
    ?int $uid = null
): bool {
    $uid = $uid ?: ($_SESSION['user']['id'] ?? 0);

    if (!$uid) {
        return false;
    }

    $st = $pdo->prepare(
        "SELECT check_in, check_out
         FROM work_attendance
         WHERE user_id = ?
         AND work_date = CURDATE()
         LIMIT 1"
    );

    $st->execute([$uid]);

    $r = $st->fetch();

    return $r
        && !empty($r['check_in'])
        && empty($r['check_out']);
}

function require_work_checkin(PDO $pdo)
{
    require_login();

    if (
        $_SESSION['user']['role'] === 'staff'
        && !user_checked_in($pdo)
    ) {
        $_SESSION['flash_error'] =
            'You must Check In before doing operational work.';

        $dest = 'staff/dashboard.php';

        header('Location: ' . url_path($dest));
        exit;
    }
}

function manager_farm_id(
    PDO $pdo,
    ?int $uid = null
): int {
    $uid = $uid ?: ($_SESSION['user']['id'] ?? 0);

    $st = $pdo->prepare(
        "SELECT farm_id
         FROM farm_managers
         WHERE manager_user_id = ?
         AND status = 'active'
         ORDER BY id DESC
         LIMIT 1"
    );

    $st->execute([$uid]);

    return (int) ($st->fetchColumn() ?: 0);
}

function require_manager_farm(PDO $pdo): int
{
    require_role(['manager']);

    return manager_farm_id($pdo);
}