<?php

require_once __DIR__ . '/../config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>

<!doctype html>

<html>

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width,initial-scale=1"
    >

    <title>
        <?= htmlspecialchars($page_title ?? 'Smart Farm') ?>
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

<body>

<nav class="app-topbar">

    <a
        class="brand"
        href="<?= url_path() ?>"
    >

        <img src="<?= asset_path('logo.png') ?>">

        <span>

            <b>
                Smart Farm
            </b>

            <small>
                Farm & Customer Platform
            </small>

        </span>

    </a>


    <div class="top-actions">

        <button
            type="button"
            class="top-back-btn"
            onclick="history.length > 1 ? history.back() : location.href='<?= url_path() ?>'"
        >
            ← Back
        </button>


        <a
            class="btn btn-sm btn-outline-light"
            href="<?= url_path() ?>"
        >
            Home
        </a>


        <a
            class="btn btn-sm btn-outline-light"
            href="<?= url_path('shop/index.php') ?>"
        >
            Shop
        </a>


        <?php if (!empty($_SESSION['customer'])): ?>

            <a
                class="btn btn-sm btn-outline-light"
                href="<?= url_path('customer/support.php') ?>"
            >
                Support Chat
            </a>

            <a
                class="btn btn-sm btn-light"
                href="<?= url_path('customer/dashboard.php') ?>"
            >
                My Orders
            </a>

            <a
                class="btn btn-sm btn-outline-light"
                href="<?= url_path('customer/logout.php') ?>"
            >
                Logout
            </a>

        <?php else: ?>

            <a
                class="btn btn-sm btn-outline-light"
                href="<?= url_path('customer/register.php') ?>"
            >
                Register
            </a>

            <a
                class="btn btn-sm btn-light"
                href="<?= url_path('customer/login.php') ?>"
            >
                Customer Login
            </a>

        <?php endif; ?>

    </div>

</nav>