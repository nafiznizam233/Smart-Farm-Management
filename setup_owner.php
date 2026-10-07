<?php

require __DIR__ . '/config/database.php';
require __DIR__ . '/config/app.php';


$email = 'owner@farm.local';
$defaultPassword = '123456';

$msg = '';
$err = '';


try {

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $hash = password_hash(
            $defaultPassword,
            PASSWORD_DEFAULT
        );


        $pdo->beginTransaction();


        $st = $pdo->prepare("
            SELECT id
            FROM users
            WHERE LOWER(email) = LOWER(?)
            LIMIT 1
        ");

        $st->execute([
            $email
        ]);

        $id = $st->fetchColumn();


        if ($id) {

            $pdo->prepare("
                UPDATE users
                SET
                    name = ?,
                    email = ?,
                    phone = COALESCE(
                        NULLIF(phone, ''),
                        '01700000000'
                    ),
                    password = ?,
                    role = 'owner',
                    status = 'active',
                    verification_status = 'verified'
                WHERE id = ?
            ")->execute([
                'Farm Owner',
                $email,
                $hash,
                $id
            ]);


            $msg = 'Owner login repaired successfully.';

        } else {

            $pdo->prepare("
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
                    'owner',
                    'active',
                    'verified'
                )
            ")->execute([
                'Farm Owner',
                $email,
                '01700000000',
                $hash
            ]);


            $msg = 'Owner account created successfully.';
        }


        $pdo->commit();
    }

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $err = $e->getMessage();
}


$st = $pdo->prepare("
    SELECT
        id,
        name,
        email,
        role,
        status
    FROM users
    WHERE LOWER(email) = LOWER(?)
    LIMIT 1
");

$st->execute([
    $email
]);

$owner = $st->fetch();

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
        Owner Setup
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


<div class="login-wrap">

    <div
        class="cardx p-4"
        style="width:min(650px,94vw)"
    >

        <div class="text-center mb-3">

            <img
                src="<?= asset_path('logo.png') ?>"
                style="
                    width:72px;
                    height:72px;
                    border-radius:50%;
                    object-fit:cover;
                "
            >

            <h2 class="mt-2">
                Owner Account Setup
            </h2>

        </div>


        <?php if ($msg): ?>

            <div class="alert alert-success">
                <?= htmlspecialchars($msg) ?>
            </div>

        <?php endif; ?>


        <?php if ($err): ?>

            <div class="alert alert-danger">
                <?= htmlspecialchars($err) ?>
            </div>

        <?php endif; ?>


        <div class="alert <?= $owner ? 'alert-success' : 'alert-warning' ?>">

            <b>
                Email:
            </b>

            owner@farm.local

            <br>


            <b>
                Default/repair password:
            </b>

            123456

            <br>


            <b>
                Account:
            </b>

            <?= $owner
                ? 'Ready — ' .
                    htmlspecialchars(
                        $owner['role'] .
                        ' / ' .
                        $owner['status']
                    )
                : 'Not created yet'
            ?>

        </div>


        <form
            method="post"
            class="d-flex gap-2 flex-wrap"
        >

            <button
                class="btn btn-danger"
                type="submit"
            >
                <?= $owner
                    ? 'Repair & Reset Owner Login'
                    : 'Create Owner Login'
                ?>
            </button>


            <?php if ($owner): ?>

                <a
                    class="btn btn-success"
                    href="<?= url_path('login.php') ?>"
                >
                    Go to Admin Login
                </a>

            <?php endif; ?>


            <a
                class="btn btn-outline-secondary"
                href="<?= url_path() ?>"
            >
                Marketplace
            </a>

        </form>


        <p class="text-muted mt-3 mb-0">
            After a repair, sign in with the credentials shown above
            and change the password from the Owner account.
        </p>

    </div>

</div>


</body>

</html>