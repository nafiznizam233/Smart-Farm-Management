<?php

require __DIR__ . "/../config/database.php";
require __DIR__ . "/../config/auth.php";

require_login();

$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $st = $pdo->prepare(
        "SELECT password FROM users WHERE id=?"
    );

    $st->execute([
        $_SESSION['user']['id']
    ]);

    $old = $st->fetchColumn();

    if (!password_verify($_POST['current_password'], $old)) {

        $err = 'Current password is incorrect.';

    } elseif (strlen($_POST['new_password']) < 6) {

        $err = 'Password must be at least 6 characters.';

    } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {

        $err = 'Passwords do not match.';

    } else {

        $pdo->prepare(
            "UPDATE users SET password=? WHERE id=?"
        )->execute([
            password_hash(
                $_POST['new_password'],
                PASSWORD_DEFAULT
            ),
            $_SESSION['user']['id']
        ]);

        $msg = 'Password changed successfully.';
    }
}

$page_title = 'Change Password';

require __DIR__ . "/../partials/header.php";

?>

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-6">

            <div class="cardx p-4">

                <h3>Change Password</h3>

                <?php if ($msg): ?>

                    <div class="alert alert-success">
                        <?= $msg ?>
                    </div>

                <?php endif; ?>

                <?php if ($err): ?>

                    <div class="alert alert-danger">
                        <?= $err ?>
                    </div>

                <?php endif; ?>

                <form method="post">

                    <div class="mb-3">

                        <label>Current Password</label>

                        <input
                            class="form-control"
                            type="password"
                            name="current_password"
                            required
                        >

                    </div>

                    <div class="mb-3">

                        <label>New Password</label>

                        <input
                            class="form-control"
                            type="password"
                            name="new_password"
                            required
                        >

                    </div>

                    <div class="mb-3">

                        <label>Confirm New Password</label>

                        <input
                            class="form-control"
                            type="password"
                            name="confirm_password"
                            required
                        >

                    </div>

                    <button class="btn btn-success">
                        Change Password
                    </button>

                </form>

            </div>

        </div>

    </div>

</div>