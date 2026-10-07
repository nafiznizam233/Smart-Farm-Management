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
        die('Not enough stock.');
    }


    $cost = $qty * (float) $inv['unit_cost'];


    $pdo->beginTransaction();


    try {

        $pdo->prepare(
            "INSERT INTO feed_logs(
                farm_id,
                pond_or_area,
                inventory_item_id,
                staff_id,
                qty,
                feed_time,
                feed_date,
                cost,
                status,
                notes
            )
            VALUES(?,?,?,?,?,?,?,?,?,?)"
        )->execute([
            (int) $_POST['farm_id'],
            trim($_POST['pond_or_area']),
            $item,
            $sid,
            $qty,
            $_POST['feed_time'],
            $_POST['feed_date'],
            $cost,
            is_staff() ? 'submitted' : 'approved',
            trim($_POST['notes'])
        ]);


        $lid = $pdo->lastInsertId();


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
                'Feed',
                ?
            )"
        )->execute([
            $item,
            (int) $_POST['farm_id'],
            $sid,
            $qty,
            $_POST['feed_date'],
            $lid
        ]);


        $pdo->prepare(
            "INSERT INTO inventory_movements(
                inventory_item_id,
                movement_type,
                qty,
                reference_type,
                reference_id,
                movement_date,
                note
            )
            VALUES(
                ?,
                'OUT',
                ?,
                'feed_log',
                ?,?,
                'Feed usage'
            )"
        )->execute([
            $item,
            $qty,
            $lid,
            $_POST['feed_date']
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
                'Feed Usage',
                ?,?,?
            )"
        )->execute([
            (int) $_POST['farm_id'],
            $cost,
            $_POST['feed_date'],
            'Feed usage ' . $qty
        ]);


        $pdo->commit();

    } catch (Exception $e) {

        $pdo->rollBack();

        die($e->getMessage());
    }


    header('Location:feed.php');
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
     WHERE i.item_type='Feed'
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
        l.*,
        f.name farm_name,
        i.item_name,
        i.unit,
        u.name staff_name
     FROM feed_logs l
     JOIN farms f
        ON f.id=l.farm_id
     JOIN inventory_items i
        ON i.id=l.inventory_item_id
     JOIN staff s
        ON s.id=l.staff_id
     JOIN users u
        ON u.id=s.user_id
     ORDER BY l.id DESC
     LIMIT 100"
)->fetchAll();


$page_title = 'Feed Log';

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
                    Feed Usage Log
                </h2>

                <p class="mb-0">
                    Records who fed, how much, where, when, cost and deducts stock automatically.
                </p>

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
                            name="pond_or_area"
                            placeholder="Pond / area"
                        >

                    </div>


                    <div class="col-md-3">

                        <select
                            class="form-select"
                            name="inventory_item_id"
                            required
                        >

                            <option value="">
                                Feed item
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
                                    Given by
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
                            name="feed_date"
                            value="<?= date('Y-m-d') ?>"
                        >

                    </div>


                    <div class="col-md-2">

                        <input
                            class="form-control"
                            type="time"
                            name="feed_time"
                            value="<?= date('H:i') ?>"
                        >

                    </div>


                    <div class="col-md-8">

                        <input
                            class="form-control"
                            name="notes"
                            placeholder="Notes"
                        >

                    </div>


                    <div class="col-md-2">

                        <button class="btn btn-success w-100">
                            Submit Feed
                        </button>

                    </div>

                </form>

            </div>


            <div class="cardx p-3">

                <table class="table">

                    <thead>

                        <tr>
                            <th>Date</th>
                            <th>Farm/Area</th>
                            <th>Feed</th>
                            <th>Qty</th>
                            <th>Staff</th>
                            <th>Cost</th>
                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($logs as $l): ?>

                            <tr>

                                <td>
                                    <?= $l['feed_date'] . ' ' . $l['feed_time'] ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars(
                                        $l['farm_name'] .
                                        ' / ' .
                                        $l['pond_or_area']
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