<?php

session_start();

require __DIR__ . '/config/database.php';
require __DIR__ . '/config/app.php';


$err = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    $pw = $_POST['password'];
    $cp = $_POST['confirm_password'];


    if (strlen($pw) < 6) {

        $err = 'Password must be at least 6 characters.';

    } elseif ($pw !== $cp) {

        $err = 'Passwords do not match.';

    } else {

        try {

            $st = $pdo->prepare("
                INSERT INTO users (
                    name,
                    email,
                    phone,
                    password,
                    role,
                    status,
                    verification_status
                )
                VALUES (
                    ?,
                    ?,
                    ?,
                    ?,
                    'applicant',
                    'active',
                    'unverified'
                )
            ");

            $st->execute([
                $name,
                $email,
                $phone,
                password_hash(
                    $pw,
                    PASSWORD_DEFAULT
                )
            ]);


            $uid = $pdo->lastInsertId();


            $pdo->prepare("
                INSERT INTO applicant_profiles (
                    user_id,
                    created_at
                )
                VALUES (
                    ?,
                    NOW()
                )
            ")->execute([
                $uid
            ]);


            $_SESSION['user'] = [
                'id'    => $uid,
                'name'  => $name,
                'email' => $email,
                'role'  => 'applicant'
            ];


            header(
                'Location: ' .
                url_path('applicant/dashboard.php')
            );

            exit;

        } catch (Throwable $e) {

            $err = 'This email may already be registered.';
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
        Applicant Registration
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
                Careers
            </small>

        </span>

    </a>


    <a
        class="btn btn-light"
        href="<?= url_path('login.php?role=applicant') ?>"
    >
        Applicant Login
    </a>

</nav>


<div class="login-wrap">

    <div
        class="cardx p-4"
        style="width:min(620px,96vw)"
    >

        <h3 class="fw-bold">
            Create Applicant Account
        </h3>

        <p class="text-muted">
            Register once, apply for jobs and track interview schedules.
        </p>


        <?php if ($err): ?>

            <div class="alert alert-danger">
                <?= $err ?>
            </div>

        <?php endif; ?>


        <form
            method="post"
            class="row g-3"
        >

            <div class="col-md-6">

                <label>
                    Name
                </label>

                <input
                    class="form-control"
                    name="name"
                    required
                >

            </div>


            <div class="col-md-6">

                <label>
                    Mobile
                </label>

                <input
                    class="form-control"
                    name="phone"
                    required
                >

            </div>


            <div class="col-12">

                <label>
                    Email
                </label>

                <input
                    class="form-control"
                    type="email"
                    name="email"
                    required
                >

            </div>


            <div class="col-md-6">

                <label>
                    Password
                </label>

                <input
                    class="form-control"
                    type="password"
                    name="password"
                    required
                >

            </div>


            <div class="col-md-6">

                <label>
                    Confirm Password
                </label>

                <input
                    class="form-control"
                    type="password"
                    name="confirm_password"
                    required
                >

            </div>


            <div class="col-12">

                <button class="btn btn-success w-100">
                    Register & Open Applicant Portal
                </button>

            </div>

        </form>

    </div>

</div>


</body>

</html>