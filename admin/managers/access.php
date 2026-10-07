<?php

require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../config/auth.php';

require_role(['owner']);


$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);


$st = $pdo->prepare(
    "SELECT
        id,
        name,
        email,
        status
     FROM users
     WHERE id=?
     AND role='manager'"
);

$st->execute([$id]);

$m = $st->fetch();


if (!$m) {
    die('Manager not found.');
}


$permissions = [
    'workers'     => 'Worker Records',
    'attendance'  => 'Worker Attendance',
    'payroll'     => 'Worker Salary / Payroll',
    'products'    => 'Products & Stock',
    'preorders'   => 'Pre-order Capacity',
    'stores'      => 'Stores',
    'inventory'   => 'Inventory',
    'purchases'   => 'Purchases',
    'feed'        => 'Feed Operations',
    'medicine'    => 'Medicine Operations',
    'delivery'    => 'Delivery Management',
    'support'     => 'Customer Support',
    'settlements' => 'Delivery Settlements'
];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $pdo->beginTransaction();

    try {

        foreach ($permissions as $key => $label) {

            $allow = isset($_POST['perm'][$key])
                ? 1
                : 0;


            $x = $pdo->prepare(
                "INSERT INTO manager_permissions(
                    manager_user_id,
                    permission_key,
                    allowed,
                    updated_by
                )
                VALUES(?,?,?,?)
                ON DUPLICATE KEY UPDATE
                    allowed=VALUES(allowed),
                    updated_by=VALUES(updated_by)"
            );


            $x->execute([
                $id,
                $key,
                $allow,
                $_SESSION['user']['id']
            ]);
        }


        $pdo->commit();

        $saved = true;

    } catch (Throwable $e) {

        $pdo->rollBack();

        $err = $e->getMessage();
    }
}


$cur = [];


$x = $pdo->prepare(
    "SELECT
        permission_key,
        allowed
     FROM manager_permissions
     WHERE manager_user_id=?"
);

$x->execute([$id]);


foreach ($x as $r) {

    $cur[$r['permission_key']] =
        (bool) $r['allowed'];
}


$page_title = 'Manager Access';

require __DIR__ . '/../../partials/header.php';

?>


<div class="app-shell">

    <?php
    require __DIR__ . '/../../partials/admin_sidebar.php';
    ?>


    <main class="app-main">

        <div class="hero-top mb-3">

            <h2>
                Manager Access
            </h2>

            <p>
                <?= htmlspecialchars($m['name']) ?>

                ·

                <?= htmlspecialchars($m['email']) ?>
            </p>

        </div>


        <?php if (!empty($saved)): ?>

            <div class="alert alert-success">
                Manager access updated.
            </div>

        <?php endif; ?>


        <?php if (!empty($err)): ?>

            <div class="alert alert-danger">
                <?= htmlspecialchars($err) ?>
            </div>

        <?php endif; ?>


        <form
            method="post"
            class="cardx p-4"
        >

            <input
                type="hidden"
                name="id"
                value="<?= $id ?>"
            >


            <h5>
                Choose what this Manager can use
            </h5>


            <p class="text-muted">
                Unchecked features are blocked even if the Manager knows the direct URL.
            </p>


            <div class="row g-3">

                <?php foreach ($permissions as $key => $label): ?>

                    <?php

                    $on = array_key_exists($key, $cur)
                        ? $cur[$key]
                        : true;

                    ?>


                    <div class="col-md-4">

                        <label
                            class="border rounded p-3 d-flex gap-2 align-items-center"
                        >

                            <input
                                type="checkbox"
                                name="perm[<?= $key ?>]"
                                <?= $on ? 'checked' : '' ?>
                            >


                            <span>

                                <b>
                                    <?= htmlspecialchars($label) ?>
                                </b>

                                <br>

                                <small>
                                    <?= $on
                                        ? 'Currently allowed'
                                        : 'Currently blocked'
                                    ?>
                                </small>

                            </span>

                        </label>

                    </div>

                <?php endforeach; ?>

            </div>


            <button class="btn btn-success mt-4">
                Save Manager Access
            </button>


            <a
                class="btn btn-outline-secondary mt-4"
                href="index.php"
            >
                Back
            </a>

        </form>

    </main>

</div>