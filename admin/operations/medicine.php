<?php

require __DIR__ . "/../../config/database.php";
require __DIR__ . "/../../config/auth.php";

require_role(['owner', 'manager', 'staff']);


if (is_manager() || is_staff()) {
    require_work_checkin($pdo);
}


$staffId = null;


if (is_staff()) {

    $st = $pdo->prepare(
        "SELECT id
         FROM staff
         WHERE user_id=?"
    );

    $st->execute([
        $_SESSION['user']['id']
    ]);

    $staffId = $st->fetchColumn();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $sid = $staffId ?: ((int) $_POST['staff_id']);

    $item = (int) $_POST['inventory_item_id'];

    $qty = (float) $_POST['qty'];


    $st = $pdo->prepare(
        "SELECT
            current_qty,
            unit_cost
         FROM inventory_items
         WHERE id=?"
    );

    $st->execute([$item]);

    $inv = $st->fetch();


    if (!$inv || $inv['current_qty'] < $qty) {
        die('Not enough medicine stock.');
    }


    $cost = $qty * (float) $inv['unit_cost'];


    $pdo->beginTransaction();


    try {

        $pdo->prepare(
            "INSERT INTO medicine_usage(
                farm_id,
                target_area,
                inventory_item_id,
                staff_id,
                qty,
                reason,
                usage_date,
                cost
            )
            VALUES(?,?,?,?,?,?,?,?)"
        )->execute([
            (int) $_POST['farm_id'],
            trim($_POST['target_area']),
            $item,
            $sid,
            $qty,
            trim($_POST['reason']),
            $_POST['usage_date'],
            $cost
        ]);


        $id = $pdo->lastInsertId();


        $pdo->prepare(
            "UPDATE inventory_items
             SET current_qty=current_qty-?
             WHERE id=?"
        )->execute([
            $qty,
            $item
        ]);


        $pdo->prepare(
            "INSERT INTO inventory_usage(
                inventory_item_id,
                farm_id,
                staff_id,
                qty_used,
                usage_date,
                usage_type,
                reference_id
            )
            VALUES(
                ?,?,?,?,?,
                'Medicine',
                ?
            )"
        )->execute([
            $item,
            (int) $_POST['farm_id'],
            $sid,
            $qty,
            $_POST['usage_date'],
            $id
        ]);


        $pdo->prepare(
            "INSERT INTO expenses(
                farm_id,
                category,
                amount,
                expense_date,
                note
            )
            VALUES(
                ?,
                'Medicine Usage',
                ?,?,?
            )"
        )->execute([
            (int) $_POST['farm_id'],
            $cost,
            $_POST['usage_date'],
            'Medicine: ' . trim($_POST['reason'])
        ]);


        $pdo->commit();

    } catch (Exception $e) {

        $pdo->rollBack();

        die($e->getMessage());
    }


    header('Location:medicine.php');
    exit;
}


$farms = $pdo->query(
    "SELECT
        id,
        name
     FROM farms
     WHERE status='active'"
)->fetchAll();


$items = $pdo->query(
    "SELECT
        i.id,
        i.item_name,
        i.current_qty,
        i.unit,
        st.name store_name
     FROM inventory_items i
     JOIN stores st
        ON st.id=i.store_id
     WHERE i.item_type='Medicine'
     ORDER BY i.item_name"
)->fetchAll();


$staff = $pdo->query(
    "SELECT
        s.id,
        u.name
     FROM staff s
     JOIN users u
        ON u.id=s.user_id
     WHERE s.staff_type IN(
        'farm_worker',
        'multipurpose'
     )
     AND s.status='active'"
)->fetchAll();


$logs = $pdo->query(
    "SELECT
        m.*,
        f.name farm_name,
        i.item_name,
        i.unit,
        u.name staff_name
     FROM medicine_usage m
     JOIN farms f
        ON f.id=m.farm_id
     JOIN inventory_items i
        ON i.id=m.inventory_item_id
     JOIN staff s
        ON s.id=m.staff_id
     JOIN users u
        ON u.id=s.user_id
     ORDER BY m.id DESC
     LIMIT 100"
)->fetchAll();


$page_title = 'Medicine Usage';

require __DIR__ . "/../../partials/header.php";

?>


<div class="container-fluid">

    <div class="row">

        <?php

        if (is_owner()) {
            require __DIR__ . "/../../partials/admin_sidebar.php";
        } elseif (is_manager()) {
            require __DIR__ . "/../../partials/manager_sidebar.php";
        }

        ?>


        <div class="<?= is_staff() ? 'container' : 'col-md-10' ?> p-4">

            <div class="hero-top mb-4">

                <h2>
                    Medicine Store & Usage
                </h2>

            </div>


            <div class="cardx p-3 mb-4">

                <form
                    method="post"
                    class="row g-2"
                >

                    <div class="col-md-2">

                        <select
                            class="form-select"
                            name="farm_id"
                            required
                        >

                            <option value="">
                                Farm
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
                            name="target_area"
                            placeholder="Pond / animal group"
                        >

                    </div>


                    <div class="col-md-3">

                        <select
                            class="form-select"
                            name="inventory_item_id"
                            required
                        >

                            <option value="">
                                Medicine
                            </option>


                            <?php foreach ($items as $i): ?>

                                <option value="<?= $i['id'] ?>">

                                    <?= htmlspecialchars($i['item_name']) ?>

                                    (<?= $i['current_qty'] . ' ' . $i['unit'] ?>)

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <?php if (!is_staff()): ?>

                        <div class="col-md-2">

                            <select
                                class="form-select"
                                name="staff_id"
                                required
                            >

                                <option value="">
                                    Used by
                                </option>


                                <?php foreach ($staff as $s): ?>

                                    <option value="<?= $s['id'] ?>">
                                        <?= htmlspecialchars($s['name']) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                    <?php endif; ?>


                    <div class="col-md-1">

                        <input
                            class="form-control"
                            type="number"
                            step=".01"
                            name="qty"
                            placeholder="Qty"
                            required
                        >

                    </div>


                    <div class="col-md-2">

                        <input
                            class="form-control"
                            type="date"
                            name="usage_date"
                            value="<?= date('Y-m-d') ?>"
                        >

                    </div>


                    <div class="col-md-10">

                        <input
                            class="form-control"
                            name="reason"
                            placeholder="Reason / disease / treatment"
                        >

                    </div>


                    <div class="col-md-2">

                        <button class="btn btn-success w-100">
                            Record Use
                        </button>

                    </div>

                </form>

            </div>


            <div class="cardx p-3">

                <table class="table">

                    <thead>

                        <tr>
                            <th>Date</th>
                            <th>Farm/Target</th>
                            <th>Medicine</th>
                            <th>Qty</th>
                            <th>Staff</th>
                            <th>Cost</th>
                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($logs as $l): ?>

                            <tr>

                                <td>
                                    <?= $l['usage_date'] ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars(
                                        $l['farm_name'] .
                                        ' / ' .
                                        $l['target_area']
                                    ) ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars($l['item_name']) ?>
                                </td>


                                <td>
                                    <?= $l['qty'] . ' ' . $l['unit'] ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars($l['staff_name']) ?>
                                </td>


                                <td>
                                    ৳<?= number_format($l['cost'], 2) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>