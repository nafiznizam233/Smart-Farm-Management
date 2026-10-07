<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

session_start();

$e = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $pw = $_POST['password'] ?? '';

    if (strlen($pw) < 6) {
        $e = 'Password must be at least 6 characters.';
    } else {
        try {
            $pdo->beginTransaction();

            $pdo->prepare(
                "INSERT INTO users(
                    name,
                    email,
                    phone,
                    password,
                    role,
                    status,
                    verification_status
                )
                VALUES (?, ?, ?, ?, 'customer', 'active', 'verified')"
            )->execute([
                $name,
                $email,
                $phone,
                password_hash($pw, PASSWORD_DEFAULT)
            ]);

            $uid = $pdo->lastInsertId();

            $pdo->prepare(
                "INSERT INTO customer_profiles(
                    user_id,
                    address
                )
                VALUES (?, NULL)"
            )->execute([
                $uid
            ]);

            $pdo->commit();

            header(
                'Location: '
                . url_path('customer/login.php?registered=1')
            );

            exit;
        } catch (Throwable $x) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $e = 'This email is already registered or the database is not ready.';
        }
    }
}

$page_title = 'Customer Registration';

require __DIR__ . '/../partials/public_header.php';

?>

<div class="login-wrap">

    <div class="login-card">

        <div class="login-brand">

            <img src="<?= asset_path('logo.png') ?>">

            <h2 class="fw-bold">
                Farm Shop Account
            </h2>

            <p>
                Create a customer account to order fresh products and track delivery.
            </p>

        </div>

        <div class="login-form">

            <h3 class="fw-bold">
                Customer Registration
            </h3>

            <p class="text-muted">
                This account cannot access Owner, Manager or Staff panels.
            </p>

            <?php if ($e): ?>

                <div class="alert alert-danger">
                    <?= htmlspecialchars($e) ?>
                </div>

            <?php endif; ?>

            <form method="post">

                <div class="mb-3">

                    <label>Name</label>

                    <input
                        class="form-control"
                        name="name"
                        required
                    >

                </div>

                <div class="mb-3">

                    <label>Email</label>

                    <input
                        class="form-control"
                        type="email"
                        name="email"
                        required
                    >

                </div>

                <div class="mb-3">

                    <label>Mobile</label>

                    <input
                        class="form-control"
                        name="phone"
                        required
                    >

                </div>

                <div class="mb-3">

                    <label>Password</label>

                    <input
                        class="form-control"
                        type="password"
                        name="password"
                        required
                    >

                </div>

                <button class="btn btn-success w-100 py-2">
                    Create Customer Account
                </button>

            </form>

            <div class="text-center mt-3">

                <a href="<?= url_path('customer/login.php') ?>">
                    Already registered? Login
                </a>

            </div>

        </div>

    </div>

</div>

<?php require __DIR__ . '/../partials/public_footer.php'; ?>