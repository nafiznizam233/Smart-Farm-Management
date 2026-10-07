<?php

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';

$fid = require_manager_farm($pdo);
$uid = $_SESSION['user']['id'];

$err = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $pid = (int) $_POST['product_id'];
        $expected = (float) $_POST['expected'];
        $limit = (float) $_POST['limit'];


        if ($expected <= 0 || $limit <= 0) {
            throw new Exception(
                'Expected quantity and pre-order limit must be greater than zero.'
            );
        }


        if ($limit > $expected) {
            throw new Exception(
                'Pre-order limit cannot be greater than expected production.'
            );
        }


        $ck = $pdo->prepare("
            SELECT id
            FROM shop_products
            WHERE id = ?
              AND farm_id = ?
        ");

        $ck->execute([
            $pid,
            $fid
        ]);


        if (!$ck->fetchColumn()) {
            throw new Exception(
                'This product does not belong to your farm.'
            );
        }


        $pdo->prepare("
            INSERT INTO farm_daily_capacity (
                farm_id,
                product_id,
                capacity_date,
                expected_qty,
                preorder_limit,
                pickup_start,
                pickup_end,
                created_by
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)

            ON DUPLICATE KEY UPDATE
                expected_qty = VALUES(expected_qty),
                preorder_limit = VALUES(preorder_limit),
                pickup_start = VALUES(pickup_start),
                pickup_end = VALUES(pickup_end),
                created_by = VALUES(created_by)
        ")->execute([
            $fid,
            $pid,
            $_POST['date'],
            $expected,
            $limit,
            $_POST['start'],
            $_POST['end'],
            $uid
        ]);


        $pdo->prepare("
            UPDATE shop_products
            SET
                preorder_enabled = 1,
                delivery_scope = 'pickup'
            WHERE id = ?
              AND farm_id = ?
        ")->execute([
            $pid,
            $fid
        ]);


        header('Location:preorders.php');
        exit;

    } catch (Throwable $e) {

        $err = $e->getMessage();
    }
}


$p = $pdo->prepare("
    SELECT *
    FROM shop_products
    WHERE farm_id = ?
      AND status IN ('active', 'draft')
    ORDER BY name
");

$p->execute([$fid]);

$products = $p->fetchAll();


$q = $pdo->prepare("
    SELECT
        c.*,
        p.name,
        p.unit,

        (
            SELECT COALESCE(SUM(oi.qty), 0)
            FROM shop_order_items oi
            JOIN shop_orders o
                ON o.id = oi.order_id
            WHERE oi.product_id = c.product_id
              AND o.preorder = 1
              AND o.preorder_date = c.capacity_date
              AND o.status <> 'cancelled'
        ) reserved

    FROM farm_daily_capacity c

    JOIN shop_products p
        ON p.id = c.product_id

    WHERE c.farm_id = ?

    ORDER BY c.capacity_date DESC
");

$q->execute([$fid]);

$rows = $q->fetchAll();


$page_title = 'Pre-order';

require __DIR__ . '/../partials/header.php';

?>

<div class="app-shell">

    <?php require __DIR__ . '/../partials/manager_sidebar.php'; ?>

    <main class="app-main">

        <div class="hero-top mb-3">

            <h2>
                Active Pickup Pre-order
            </h2>

            <p>
                Set expected production and the maximum reservable quantity.
                Example: 10 L milk → 5 L pre-order limit.
                Customers must collect pre-orders from the farm/station.
            </p>

        </div>


        <?php if ($err): ?>

            <div class="alert alert-danger">
                <?= htmlspecialchars($err) ?>
            </div>

        <?php endif; ?>


        <form
            method="post"
            class="cardx p-3 row g-2"
        >

            <div class="col-md-3">

                <select
                    class="form-select"
                    name="product_id"
                    required
                >

                    <option value="">
                        Product
                    </option>

                    <?php foreach ($products as $p): ?>

                        <option value="<?= $p['id'] ?>">
                            <?= htmlspecialchars($p['name']) ?>
                            (<?= $p['unit'] ?>)
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="col-md-2">

                <input
                    class="form-control"
                    type="date"
                    name="date"
                    min="<?= date('Y-m-d') ?>"
                    value="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                    required
                >

            </div>


            <div class="col-md-2">

                <input
                    class="form-control"
                    type="number"
                    step=".01"
                    name="expected"
                    placeholder="Expected qty"
                    required
                >

            </div>


            <div class="col-md-2">

                <input
                    class="form-control"
                    type="number"
                    step=".01"
                    name="limit"
                    placeholder="Max pre-order"
                    required
                >

            </div>


            <div class="col-md-1">

                <input
                    class="form-control"
                    type="time"
                    name="start"
                    value="10:00"
                >

            </div>


            <div class="col-md-1">

                <input
                    class="form-control"
                    type="time"
                    name="end"
                    value="18:00"
                >

            </div>


            <div class="col-md-1">

                <button class="btn btn-success">
                    Activate
                </button>

            </div>

        </form>


        <div class="cardx p-3 mt-3">

            <table class="table">

                <tr>
                    <th>Date</th>
                    <th>Product</th>
                    <th>Expected</th>
                    <th>Limit</th>
                    <th>Reserved</th>
                    <th>Remaining</th>
                    <th>Pickup</th>
                </tr>


                <?php foreach ($rows as $r): ?>

                    <tr>

                        <td>
                            <?= $r['capacity_date'] ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($r['name']) ?>
                        </td>

                        <td>
                            <?= $r['expected_qty'] . ' ' . $r['unit'] ?>
                        </td>

                        <td>
                            <?= $r['preorder_limit'] . ' ' . $r['unit'] ?>
                        </td>

                        <td>
                            <?= $r['reserved'] . ' ' . $r['unit'] ?>
                        </td>

                        <td>
                            <b>
                                <?= max(
                                    0,
                                    $r['preorder_limit'] - $r['reserved']
                                ) . ' ' . $r['unit'] ?>
                            </b>
                        </td>

                        <td>
                            <?= $r['pickup_start'] ?>
                            –
                            <?= $r['pickup_end'] ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

            </table>

        </div>

    </main>

</div>