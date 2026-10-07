<?php

require __DIR__ . "/../../config/database.php";
require __DIR__ . "/../../config/auth.php";

require_role(['owner']);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $farm = (int) ($_POST['farm_id'] ?? 0);


    if (!$farm) {
        die('Farm assignment is required.');
    }


    $hash = password_hash(
        $_POST['password'],
        PASSWORD_DEFAULT
    );


    $pdo->beginTransaction();


    try {

        $st = $pdo->prepare(
            "INSERT INTO users(
                name,
                email,
                phone,
                password,
                role,
                status,
                verification_status
            )
            VALUES(
                ?,?,?,?,
                'manager',
                'active',
                'verified'
            )"
        );


        $st->execute([
            trim($_POST['name']),
            strtolower(trim($_POST['email'])),
            trim($_POST['phone']),
            $hash
        ]);


        $mid = (int) $pdo->lastInsertId();


        $pdo->prepare(
            "INSERT INTO farm_managers(
                farm_id,
                manager_user_id,
                assigned_by,
                status
            )
            VALUES(
                ?,?,?,
                'active'
            )"
        )->execute([
            $farm,
            $mid,
            $_SESSION['user']['id']
        ]);


        foreach (
            [
                'workers',
                'attendance',
                'payroll',
                'products',
                'preorders',
                'stores',
                'inventory',
                'purchases',
                'feed',
                'medicine',
                'delivery',
                'support',
                'settlements'
            ] as $perm
        ) {

            $pdo->prepare(
                "INSERT INTO manager_permissions(
                    manager_user_id,
                    permission_key,
                    allowed
                )
                VALUES(?,?,1)
                ON DUPLICATE KEY UPDATE
                    allowed=1"
            )->execute([
                $mid,
                $perm
            ]);
        }


        $pdo->commit();

    } catch (Throwable $e) {

        $pdo->rollBack();

        die(
            'Could not create Manager: ' .
            htmlspecialchars($e->getMessage())
        );
    }


    header('Location:index.php');
    exit;
}


if (isset($_GET['toggle'])) {

    $pdo->prepare(
        "UPDATE users
         SET status=IF(
            status='active',
            'inactive',
            'active'
         )
         WHERE id=?
         AND role='manager'"
    )->execute([
        (int) $_GET['toggle']
    ]);


    header('Location:index.php');
    exit;
}


$rows = $pdo->query(
    "SELECT
        u.*,
        f.name farm_name,
        fm.farm_id
     FROM users u
     LEFT JOIN farm_managers fm
        ON fm.manager_user_id=u.id
        AND fm.status='active'
     LEFT JOIN farms f
        ON f.id=fm.farm_id
     WHERE u.role='manager'
     ORDER BY u.id DESC"
)->fetchAll();


$farms = $pdo->query(
    "SELECT
        id,
        name
     FROM farms
     WHERE status='active'
     ORDER BY name"
)->fetchAll();


$page_title = 'Managers';

require __DIR__ . "/../../partials/header.php";

?>


<div class="container-fluid">

    <div class="row">

        <?php
        require __DIR__ . "/../../partials/admin_sidebar.php";
        ?>


        <div class="col-md-10 p-4">

            <div class="hero-top mb-4">

                <h2>
                    Manager Accounts
                </h2>

            </div>


            <div class="cardx p-3 mb-4">

                <form
                    method="post"
                    class="row g-2"
                >

                    <div class="col-md-3">

                        <input
                            class="form-control"
                            name="name"
                            placeholder="Manager name"
                            required
                        >

                    </div>


                    <div class="col-md-3">

                        <input
                            class="form-control"
                            type="email"
                            name="email"
                            placeholder="Email"
                            required
                        >

                    </div>


                    <div class="col-md-2">

                        <input
                            class="form-control"
                            name="phone"
                            placeholder="Mobile"
                            required
                        >

                    </div>


                    <div class="col-md-2">

                        <select
                            class="form-select"
                            name="farm_id"
                            required
                        >

                            <option value="">
                                Assign Farm
                            </option>


                            <?php foreach ($farms as $f): ?>

                                <option value="<?= $f['id'] ?>">
                                    <?= htmlspecialchars($f['name']) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="col-md-2">

                        <input
                            class="form-control"
                            name="password"
                            value="123456"
                            required
                        >

                    </div>


                    <div class="col-md-2">

                        <button class="btn btn-success w-100">
                            Create Manager
                        </button>

                    </div>

                </form>

            </div>


            <div class="cardx p-3">

                <table class="table">

                    <thead>

                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Farm</th>
                            <th>Status</th>
                            <th></th>
                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($rows as $r): ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars($r['name']) ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars($r['email']) ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars($r['phone']) ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars(
                                        $r['farm_name'] ?: 'Not assigned'
                                    ) ?>
                                </td>


                                <td>
                                    <?= $r['status'] ?>
                                </td>


                                <td>

                                    <a
                                        class="btn btn-sm btn-success"
                                        href="access.php?id=<?= $r['id'] ?>"
                                    >
                                        Access
                                    </a>


                                    <a
                                        class="btn btn-sm btn-outline-success"
                                        href="edit.php?id=<?= $r['id'] ?>"
                                    >
                                        Edit
                                    </a>


                                    <a
                                        class="btn btn-sm btn-outline-secondary"
                                        href="?toggle=<?= $r['id'] ?>"
                                    >
                                        Toggle
                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>