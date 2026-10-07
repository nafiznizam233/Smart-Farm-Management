<?php

require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../config/auth.php';

require_role(['owner', 'manager']);

require __DIR__ . '/../../config/smart_ops.php';


if (is_manager()) {
    require_work_checkin($pdo);
}


$err = $msg = '';


if (isset($_POST['assign_order'])) {

    $oid = (int) $_POST['order_id'];
    $sid = (int) $_POST['staff_id'];


    $st = $pdo->prepare(
        "SELECT staff_type
         FROM staff
         WHERE id=?
         AND status='active'"
    );

    $st->execute([$sid]);

    $type = $st->fetchColumn();


    if (!in_array(
        $type,
        ['delivery_man', 'multipurpose'],
        true
    )) {

        $err = 'Select Delivery Only or Multipurpose staff.';

    } else {

        $pdo->beginTransaction();

        try {

            $o = $pdo->prepare(
                "SELECT *
                 FROM shop_orders
                 WHERE id=?
                 FOR UPDATE"
            );

            $o->execute([$oid]);

            $order = $o->fetch();


            if (!$order) {
                throw new Exception(
                    'Order not found.'
                );
            }


            $pdo->prepare(
                "UPDATE shop_orders
                 SET
                    assigned_staff_id=?,
                    status=IF(
                        status IN(
                            'pending',
                            'confirmed',
                            'preparing'
                        ),
                        'ready_for_delivery',
                        status
                    )
                 WHERE id=?"
            )->execute([
                $sid,
                $oid
            ]);


            $su = $pdo->prepare(
                "SELECT user_id
                 FROM staff
                 WHERE id=?"
            );

            $su->execute([$sid]);


            if ($staffUserId = $su->fetchColumn()) {

                notify_user(
                    $pdo,
                    (int) $staffUserId,
                    'delivery',
                    'Delivery assigned',
                    'Order #' . $oid . ' has been assigned to you.',
                    'staff/deliveries.php',
                    'order',
                    $oid
                );
            }


            $pdo->prepare(
                "INSERT INTO shop_order_status_log(
                    order_id,
                    status,
                    note,
                    changed_by
                )
                VALUES(
                    ?,
                    'ready_for_delivery',
                    'Delivery staff assigned by management',
                    ?
                )"
            )->execute([
                $oid,
                $_SESSION['user']['id']
            ]);


            $pdo->commit();

            $msg = 'Order assigned to delivery staff.';

        } catch (Throwable $e) {

            $pdo->rollBack();

            $err = $e->getMessage();
        }
    }
}


if (isset($_POST['manual_delivery'])) {

    $sid = (int) $_POST['staff_id'];


    $pdo->prepare(
        "INSERT INTO delivery_assignments(
            staff_id,
            customer_name,
            customer_phone,
            address,
            order_ref,
            delivery_date,
            start_time,
            end_time,
            status,
            created_by
        )
        VALUES(
            ?,?,?,?,?,?,?,?,
            'assigned',
            ?
        )"
    )->execute([
        $sid,
        trim($_POST['customer_name']),
        trim($_POST['customer_phone']),
        trim($_POST['address']),
        trim($_POST['order_ref']),
        $_POST['delivery_date'],
        $_POST['start_time'],
        $_POST['end_time'],
        $_SESSION['user']['id']
    ]);


    $msg = 'Manual delivery assigned.';
}


$staff = $pdo->query(
    "SELECT
        s.id,
        u.name,
        s.staff_type
     FROM staff s
     JOIN users u
        ON u.id=s.user_id
     WHERE s.staff_type IN(
        'delivery_man',
        'multipurpose'
     )
     AND s.status='active'
     ORDER BY u.name"
)->fetchAll();


$orders = $pdo->query(
    "SELECT
        o.*,
        u.name customer_name,
        su.name staff_name
     FROM shop_orders o
     JOIN users u
        ON u.id=o.customer_user_id
     LEFT JOIN staff s
        ON s.id=o.assigned_staff_id
     LEFT JOIN users su
        ON su.id=s.user_id
     WHERE o.status NOT IN(
        'delivered',
        'cancelled'
     )
     ORDER BY o.id DESC"
)->fetchAll();


$page_title = 'Delivery Control';

require __DIR__ . '/../../partials/header.php';

?>


<div class="app-shell">

    <?php

    if (is_owner()) {
        require __DIR__ . '/../../partials/admin_sidebar.php';
    } else {
        require __DIR__ . '/../../partials/manager_sidebar.php';
    }

    ?>


    <main class="app-main">

        <div class="page-head">

            <div>

                <span class="eyebrow green">
                    FULFILMENT
                </span>

                <h2>
                    Delivery Control
                </h2>

                <p>
                    Staff can accept an unassigned order.
                    If nobody accepts, management can assign one directly.
                </p>

            </div>

        </div>


        <?php if ($msg): ?>

            <div class="alert alert-success">
                <?= $msg ?>
            </div>

        <?php endif; ?>


        <?php if ($err): ?>

            <div class="alert alert-danger">
                <?= htmlspecialchars($err) ?>
            </div>

        <?php endif; ?>


        <div class="cardx p-3">

            <div class="table-wrap">

                <table class="table align-middle">

                    <thead>

                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Status</th>
                            <th>Assigned Staff</th>
                            <th>Management Action</th>
                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($orders as $o): ?>

                            <tr>

                                <td>

                                    <b>
                                        #<?= $o['id'] ?>
                                    </b>

                                    <small class="d-block text-muted">
                                        ৳<?= number_format(
                                            $o['total_amount'],
                                            2
                                        ) ?>
                                    </small>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $o['customer_name']
                                    ) ?>

                                    <small class="d-block text-muted">

                                        <?= htmlspecialchars(
                                            $o['phone']
                                        ) ?>

                                        ·

                                        <?= htmlspecialchars(
                                            $o['delivery_address']
                                        ) ?>

                                    </small>

                                </td>


                                <td>

                                    <span class="badge-soft">

                                        <?= str_replace(
                                            '_',
                                            ' ',
                                            $o['status']
                                        ) ?>

                                    </span>

                                </td>


                                <td>

                                    <?= $o['staff_name']
                                        ? htmlspecialchars($o['staff_name'])
                                        : '<span class="text-muted">Waiting for staff</span>'
                                    ?>

                                </td>


                                <td>

                                    <form
                                        method="post"
                                        class="d-flex gap-2"
                                    >

                                        <input
                                            type="hidden"
                                            name="assign_order"
                                            value="1"
                                        >

                                        <input
                                            type="hidden"
                                            name="order_id"
                                            value="<?= $o['id'] ?>"
                                        >


                                        <select
                                            class="form-select form-select-sm"
                                            name="staff_id"
                                            required
                                        >

                                            <option value="">
                                                Choose staff
                                            </option>


                                            <?php foreach ($staff as $s): ?>

                                                <option
                                                    value="<?= $s['id'] ?>"
                                                    <?= $o['assigned_staff_id'] == $s['id']
                                                        ? 'selected'
                                                        : ''
                                                    ?>
                                                >

                                                    <?= htmlspecialchars(
                                                        $s['name']
                                                    ) ?>

                                                    —

                                                    <?= str_replace(
                                                        '_',
                                                        ' ',
                                                        $s['staff_type']
                                                    ) ?>

                                                </option>

                                            <?php endforeach; ?>

                                        </select>


                                        <button
                                            class="btn btn-success btn-sm"
                                        >
                                            Assign
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endforeach; ?>


                        <?php if (!$orders): ?>

                            <tr>

                                <td
                                    colspan="5"
                                    class="text-center text-muted py-4"
                                >
                                    No active customer deliveries.
                                </td>

                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </main>

</div>