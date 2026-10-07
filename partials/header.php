<?php

require_once __DIR__ . '/../config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$uiTheme = 'green';
$uiMode = 'light';


if (!empty($_SESSION['user'])) {

    $uiTheme = $_SESSION['user']['ui_theme'] ?? 'green';
    $uiMode = $_SESSION['user']['ui_mode'] ?? 'light';


    if (isset($pdo)) {

        try {

            $stUi = $pdo->prepare("
                SELECT
                    ui_theme,
                    ui_mode
                FROM users
                WHERE id = ?
            ");

            $stUi->execute([
                (int) $_SESSION['user']['id']
            ]);


            if ($pref = $stUi->fetch()) {

                $uiTheme = $pref['ui_theme'] ?: 'green';
                $uiMode = $pref['ui_mode'] ?: 'light';

                $_SESSION['user']['ui_theme'] = $uiTheme;
                $_SESSION['user']['ui_mode'] = $uiMode;
            }

        } catch (Throwable $e) {
        }
    }
}


$allowedThemes = [
    'green',
    'blue',
    'purple',
    'teal',
    'orange',
    'rose'
];


if (!in_array($uiTheme, $allowedThemes, true)) {
    $uiTheme = 'green';
}


if (!in_array($uiMode, ['light', 'dark'], true)) {
    $uiMode = 'light';
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width,initial-scale=1"
    >

    <title>
        <?= htmlspecialchars(
            $page_title ?? 'Smart Farm Management'
        ) ?>
    </title>

    <link
        rel="stylesheet"
        href="<?= asset_path('vendor/bootstrap.min.css') ?>"
    >

    <link
        rel="stylesheet"
        href="<?= asset_path('css/style.css') ?>"
    >

</head>

<body
    data-palette="<?= htmlspecialchars($uiTheme) ?>"
    data-mode="<?= htmlspecialchars($uiMode) ?>"
>

<nav class="app-topbar no-print">

    <a
        class="brand"
        href="<?= url_path() ?>"
    >

        <img
            src="<?= asset_path('logo.png') ?>"
            alt="Logo"
        >

        <span>

            <b>
                Smart Farm
            </b>

            <small>
                Management System
            </small>

        </span>

    </a>


    <?php if (!empty($_SESSION['user'])): ?>

        <div class="top-actions">

            <?php

            $dashboardUrl = is_owner()
                ? url_path('admin/dashboard.php')
                : (
                    is_manager()
                        ? url_path('manager/dashboard.php')
                        : (
                            ($_SESSION['user']['role'] ?? '') === 'staff'
                                ? url_path('staff/dashboard.php')
                                : url_path()
                        )
                );

            ?>

            <a
                class="top-back-btn"
                href="<?= htmlspecialchars($dashboardUrl) ?>"
            >
                ← Back to Dashboard
            </a>


            <?php require __DIR__ . '/notification_bell.php'; ?>


            <span class="role-chip">
                <?= htmlspecialchars(
                    ucfirst($_SESSION['user']['role'])
                ) ?>
            </span>


            <div class="dropdown">

                <button
                    class="profile-button"
                    data-bs-toggle="dropdown"
                    aria-label="Profile"
                >
                    👤
                </button>


                <ul class="dropdown-menu dropdown-menu-end shadow-sm">

                    <li>

                        <div class="px-3 py-2">

                            <b>
                                <?= htmlspecialchars(
                                    $_SESSION['user']['name']
                                ) ?>
                            </b>

                            <div class="text-muted small">
                                <?= htmlspecialchars(
                                    $_SESSION['user']['email'] ?? ''
                                ) ?>
                            </div>

                        </div>

                    </li>


                    <?php if (is_owner()): ?>

                        <li>

                            <a
                                class="dropdown-item"
                                href="<?= url_path('admin/accounts/index.php') ?>"
                            >
                                💰 Balance
                            </a>

                        </li>

                    <?php endif; ?>


                    <?php if (is_manager()): ?>

                        <li>

                            <a
                                class="dropdown-item"
                                href="<?= url_path('manager/balance.php') ?>"
                            >
                                💵 My Cash
                            </a>

                        </li>

                    <?php endif; ?>


                    <?php if (
                        in_array(
                            $_SESSION['user']['role'],
                            ['owner', 'manager', 'staff'],
                            true
                        )
                    ): ?>

                        <li>

                            <a
                                class="dropdown-item"
                                href="<?= url_path('account/profile.php') ?>"
                            >
                                🪪 Profile
                            </a>

                        </li>


                        <li>

                            <a
                                class="dropdown-item"
                                href="<?= url_path('account/profile.php?tab=settings') ?>"
                            >
                                ⚙️ Settings
                            </a>

                        </li>


                        <li>

                            <a
                                class="dropdown-item"
                                href="<?= url_path('notifications.php') ?>"
                            >
                                🔔 Notifications
                            </a>

                        </li>

                    <?php endif; ?>


                    <li>

                        <a
                            class="dropdown-item"
                            href="<?= url_path('account/change_password.php') ?>"
                        >
                            🔑 Change Password
                        </a>

                    </li>


                    <li>
                        <hr class="dropdown-divider">
                    </li>


                    <li>

                        <a
                            class="dropdown-item text-danger"
                            href="<?= url_path('logout.php') ?>"
                        >
                            ↪ Logout
                        </a>

                    </li>

                </ul>

            </div>

        </div>

    <?php else: ?>

        <div class="top-actions">

            <a
                class="btn btn-outline-light"
                href="<?= url_path('register.php') ?>"
            >
                Register
            </a>

            <a
                class="btn btn-light"
                href="<?= url_path('login.php') ?>"
            >
                Login
            </a>

        </div>

    <?php endif; ?>

</nav>


<?php if (!empty($_SESSION['flash_error'])): ?>

    <div class="container mt-3">

        <div class="alert alert-warning">

            <?= htmlspecialchars($_SESSION['flash_error']) ?>

            <?php unset($_SESSION['flash_error']); ?>

        </div>

    </div>

<?php endif; ?>


<script src="<?= asset_path('vendor/bootstrap.bundle.min.js') ?>"></script>