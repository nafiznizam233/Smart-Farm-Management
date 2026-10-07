<?php

require __DIR__ . "/../../config/database.php";
require __DIR__ . "/../../config/auth.php";

require_role(['owner', 'manager']);


if (is_manager() || is_staff()) {
    require_work_checkin($pdo);
}


if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['add_staff'])
) {

    $pdo->beginTransaction();


    try {

        $hash = password_hash(
            $_POST['password'],
            PASSWORD_DEFAULT
        );


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
                'staff',
                'active',
                'unverified'
            )"
        );


        $st->execute([
            trim($_POST['name']),
            trim($_POST['email']),
            trim($_POST['phone']),
            $hash
        ]);


        $uid = $pdo->lastInsertId();


        $pdo->prepare(
            "INSERT INTO staff(
                user_id,
                staff_type,
                monthly_salary,
                preferred_payment_method,
                status
            )
            VALUES(
                ?,?,?,
                'cash',
                'active'
            )"
        )->execute([
            $uid,
            $_POST['staff_type'],
            $_POST['monthly_salary']
        ]);


        $pdo->commit();

    } catch (Exception $e) {

        $pdo->rollBack();

        die($e->getMessage());
    }


    header('Location:index.php');
    exit;
}


if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_role'])
) {

    $sid = (int) $_POST['staff_id'];

    $type = $_POST['staff_type'];


    if (
        in_array(
            $type,
            [
                'farm_worker',
                'delivery_man',
                'multipurpose'
            ],
            true
        )
    ) {

        $pdo->prepare(
            "UPDATE staff
             SET staff_type=?
             WHERE id=?"
        )->execute([
            $type,
            $sid
        ]);
    }


    header('Location:index.php');
    exit;
}


if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_phone']) &&
    is_owner()
) {

    $pdo->prepare(
        "UPDATE users
         SET phone=?
         WHERE id=?
         AND role='staff'"
    )->execute([
        trim($_POST['phone']),
        (int) $_POST['user_id']
    ]);


    header('Location:index.php');
    exit;
}


$rows = $pdo->query(
    "SELECT
        s.*,
        u.id user_id,
        u.name,
        u.email,
        u.phone,
        u.verification_status
     FROM staff s
     JOIN users u
        ON u.id=s.user_id
     ORDER BY s.id DESC"
)->fetchAll();


$page_title = 'Staff';

require __DIR__ . "/../../partials/header.php";

?>


<div class="container-fluid">

    <div class="row">

        <?php

        if (is_owner()) {
            require __DIR__ . "/../../partials/admin_sidebar.php";
        } else {
            require __DIR__ . "/../../partials/manager_sidebar.php";
        }

        ?>


        <div class="col-md-10 p-4">

            <div class="hero-top mb-4">

                <h2>
                    Staff Management
                </h2>

            </div>


            <div class="cardx p-3 mb-4">

                <form
                    method="post"
                    class="row g-2"
                >

                    <input
                        type="hidden"
                        name="add_staff"
                        value="1"
                    >


                    <div class="col-md-2">

                        <input
                            class="form-control"
                            name="name"
                            placeholder="Name"
                            required
                        >

                    </div>


                    <div class="col-md-2">

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

                        <input
                            class="form-control"
                            name="password"
                            value="123456"
                            required
                        >

                    </div>


                    <div class="col-md-2">

                        <select
                            class="form-select"
                            name="staff_type"
                        >

                            <option value="farm_worker">
                                Farm Worker
                            </option>

                            <option value="delivery_man">
                                Delivery Only
                            </option>

                            <option value="multipurpose">
                                Multipurpose Staff
                            </option>

                        </select>

                    </div>


                    <div class="col-md-1">

                        <input
                            class="form-control"
                            name="monthly_salary"
                            type="number"
                            placeholder="Salary"
                            required
                        >

                    </div>


                    <div class="col-md-1">

                        <button class="btn btn-success w-100">
                            Add
                        </button>

                    </div>

                </form>

            </div>


            <div class="cardx p-3">

                <table class="table">

                    <thead>

                        <tr>

                            <th>Name</th>
                            <th>Type</th>
                            <th>Mobile</th>
                            <th>Verification</th>
                            <th>Salary</th>
                            <th>Role Update</th>

                            <?php if (is_owner()): ?>
                                <th>Owner Mobile Update</th>
                            <?php endif; ?>

                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($rows as $s): ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars($s['name']) ?>
                                </td>


                                <td>
                                    <?= $s['staff_type'] ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars($s['phone']) ?>
                                </td>


                                <td>
                                    <?= $s['verification_status'] ?>
                                </td>


                                <td>
                                    ৳<?= number_format(
                                        $s['monthly_salary'],
                                        2
                                    ) ?>
                                </td>


                                <td>

                                    <form
                                        method="post"
                                        class="d-flex gap-1"
                                    >

                                        <input
                                            type="hidden"
                                            name="update_role"
                                            value="1"
                                        >

                                        <input
                                            type="hidden"
                                            name="staff_id"
                                            value="<?= $s['id'] ?>"
                                        >


                                        <select
                                            class="form-select form-select-sm"
                                            name="staff_type"
                                        >

                                            <option
                                                value="farm_worker"
                                                <?= $s['staff_type'] === 'farm_worker'
                                                    ? 'selected'
                                                    : ''
                                                ?>
                                            >
                                                Farm Worker
                                            </option>


                                            <option
                                                value="delivery_man"
                                                <?= $s['staff_type'] === 'delivery_man'
                                                    ? 'selected'
                                                    : ''
                                                ?>
                                            >
                                                Delivery Only
                                            </option>


                                            <option
                                                value="multipurpose"
                                                <?= $s['staff_type'] === 'multipurpose'
                                                    ? 'selected'
                                                    : ''
                                                ?>
                                            >
                                                Multipurpose
                                            </option>

                                        </select>


                                        <button class="btn btn-sm btn-success">
                                            Update
                                        </button>

                                    </form>

                                </td>


                                <?php if (is_owner()): ?>

                                    <td>

                                        <form
                                            method="post"
                                            class="d-flex gap-1"
                                        >

                                            <input
                                                type="hidden"
                                                name="update_phone"
                                                value="1"
                                            >

                                            <input
                                                type="hidden"
                                                name="user_id"
                                                value="<?= $s['user_id'] ?>"
                                            >


                                            <input
                                                class="form-control form-control-sm"
                                                name="phone"
                                                value="<?= htmlspecialchars($s['phone']) ?>"
                                            >


                                            <button
                                                class="btn btn-sm btn-outline-primary"
                                            >
                                                Save
                                            </button>

                                        </form>

                                    </td>

                                <?php endif; ?>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>