<?php

session_start();

require __DIR__ . '/config/database.php';
require __DIR__ . '/config/app.php';


$type = $_GET['type']
    ?? ($_POST['type'] ?? 'customer');

$error = '';


if (!in_array($type, ['customer', 'admin'], true)) {
    $type = 'customer';
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = strtolower(
        trim($_POST['email'] ?? '')
    );

    $pass = $_POST['password'] ?? '';


    if ($type === 'customer') {

        $st = $pdo->prepare("
            SELECT
                id,
                name,
                email,
                password,
                status
            FROM users
            WHERE LOWER(email) = ?
              AND role = 'customer'
            LIMIT 1
        ");

        $st->execute([$email]);

        $u = $st->fetch();


        if (
            $u &&
            $u['status'] === 'active' &&
            password_verify($pass, $u['password'])
        ) {

            $_SESSION['customer'] = [
                'id'    => (int) $u['id'],
                'name'  => $u['name'],
                'email' => $u['email']
            ];


            header(
                'Location: ' .
                url_path('customer/dashboard.php')
            );

            exit;
        }


        $error = 'Invalid customer email or password.';

    } else {

        $st = $pdo->prepare("
            SELECT
                id,
                name,
                email,
                password,
                role,
                status,
                ui_theme,
                ui_mode
            FROM users
            WHERE LOWER(email) = ?
              AND role IN ('owner', 'manager')
            LIMIT 1
        ");

        $st->execute([$email]);

        $u = $st->fetch();


        if (!$u) {

            $error = 'No Owner or Manager account found.';

        } elseif ($u['status'] !== 'active') {

            $error = 'This admin account is inactive.';

        } elseif (!password_verify($pass, $u['password'])) {

            $error = 'Incorrect password.';

        } else {

            session_regenerate_id(true);


            $_SESSION['user'] = [
                'id'       => (int) $u['id'],
                'name'     => $u['name'],
                'email'    => $u['email'],
                'role'     => $u['role'],
                'ui_theme' => $u['ui_theme'] ?: 'green',
                'ui_mode'  => $u['ui_mode'] ?: 'light'
            ];


            header(
                'Location: ' .
                url_path(
                    $u['role'] === 'owner'
                        ? 'admin/dashboard.php'
                        : 'manager/dashboard.php'
                )
            );

            exit;
        }
    }
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
        Login | Smart Farm
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


<body class="login-page">


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
                Secure Login Center
            </small>

        </span>

    </a>


    <a
        class="btn btn-light btn-sm fw-bold"
        href="<?= url_path() ?>"
    >
        ← Home
    </a>

</nav>


<div class="login-wrap">

    <div class="login-card">

        <div class="login-brand">

            <img src="<?= asset_path('logo.png') ?>">

            <h2 class="fw-bold">
                Smart Farm
            </h2>

            <p class="mb-3">
                Farm operations, workforce, inventory and marketplace
                management from one connected platform.
            </p>

            <div class="small opacity-75">
                Customer and administration access are kept together
                in one clear login center.
            </div>

        </div>


        <div class="login-form">

            <div class="login-banner">

                <b>
                    Login Center
                </b>

                — choose Customer Login or Admin Login.
                Owner and Manager are automatically routed
                after authentication.

            </div>


            <div class="btn-group w-100 mb-4">

                <a
                    class="btn <?= $type === 'customer'
                        ? 'btn-success'
                        : 'btn-outline-success'
                    ?>"
                    href="?type=customer"
                >
                    Customer Login
                </a>


                <a
                    class="btn <?= $type === 'admin'
                        ? 'btn-success'
                        : 'btn-outline-success'
                    ?>"
                    href="?type=admin"
                >
                    Admin Login
                </a>

            </div>


            <h3 class="fw-bold mb-3">
                <?= $type === 'admin'
                    ? 'Admin Login'
                    : 'Customer Login'
                ?>
            </h3>


            <?php if ($error): ?>

                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>

            <?php endif; ?>


            <form method="post">

                <input
                    type="hidden"
                    name="type"
                    value="<?= htmlspecialchars($type) ?>"
                >


                <label class="form-label fw-semibold">
                    Email
                </label>

                <input
                    class="form-control mb-3"
                    type="email"
                    name="email"
                    required
                    autofocus
                    placeholder="Enter your email"
                >


                <label class="form-label fw-semibold">
                    Password
                </label>

                <input
                    class="form-control mb-3"
                    type="password"
                    name="password"
                    required
                    placeholder="Enter your password"
                >


                <button class="btn btn-success w-100 py-2">
                    Login
                </button>

            </form>


            <?php if ($type === 'customer'): ?>

                <div class="text-center mt-3">

                    <a href="<?= url_path('customer/register.php') ?>">
                        Create customer account
                    </a>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>


</body>

</html>