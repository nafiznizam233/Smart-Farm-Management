<?php

require __DIR__ . "/../../config/database.php";
require __DIR__ . "/../../config/auth.php";

require_role(['owner', 'manager']);


if (is_manager() || is_staff()) {
    require_work_checkin($pdo);
}


if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['new_item'])
) {

    $pdo->prepare(
        "INSERT INTO inventory_items(
            store_id,
            item_name,
            item_type,
            for_category,
            unit,
            current_qty,
            minimum_qty,
            unit_cost,
            expiry_date
        )
        VALUES(?,?,?,?,?,?,?,?,?)"
    )->execute([
        (int) $_POST['store_id'],
        trim($_POST['item_name']),
        $_POST['item_type'],
        $_POST['for_category'],
        $_POST['unit'],
        (float) $_POST['current_qty'],
        (float) $_POST['minimum_qty'],
        (float) $_POST['unit_cost'],
        $_POST['expiry_date'] ?: null
    ]);


    header('Location:index.php');
    exit;
}


$stores = $pdo->query(
    "SELECT *
     FROM stores
     WHERE status='active'
     ORDER BY name"
)->fetchAll();


$sid = (int) ($_GET['store_id'] ?? 0);


$q =
    "SELECT
        i.*,
        st.name store_name,
        COALESCE(
            (
                SELECT AVG(iu.qty_used)
                FROM inventory_usage iu
                WHERE iu.inventory_item_id=i.id
                AND iu.usage_date>=DATE_SUB(
                    CURDATE(),
                    INTERVAL 7 DAY
                )
            ),
            0
        ) avg_daily
     FROM inventory_items i
     JOIN stores st
        ON st.id=i.store_id" .
    ($sid ? " WHERE i.store_id=$sid" : "") .
    " ORDER BY i.item_name";


$items = $pdo->query($q)->fetchAll();


$page_title = 'Inventory';

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
                    Inventory & Stock Days
                </h2>

            </div>


            <div class="cardx p-3 mb-4">

                <form
                    method="post"
                    class="row g-2"
                >

                    <input
                        type="hidden"
                        name="new_item"
                        value="1"
                    >


                    <div class="col-md-2">

                        <select
                            class="form-select"
                            name="store_id"
                            required
                        >

                            <option value="">
                                Store
                            </option>


                            <?php foreach ($stores as $s): ?>

                                <option value="<?= $s['id'] ?>">
                                    <?= htmlspecialchars($s['name']) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="col-md-2">

                        <input
                            class="form-control"
                            name="item_name"
                            placeholder="Item / medicine / feed"
                            required
                        >

                    </div>


                    <div class="col-md-2">

                        <select
                            class="form-select"
                            name="item_type"
                        >

                            <option>Feed</option>
                            <option>Medicine</option>
                            <option>Seed</option>
                            <option>Fertilizer</option>
                            <option>Equipment</option>
                            <option>Other</option>

                        </select>

                    </div>


                    <div class="col-md-1">

                        <input
                            class="form-control"
                            name="for_category"
                            placeholder="Fish/Cow"
                        >

                    </div>


                    <div class="col-md-1">

                        <select
                            class="form-select"
                            name="unit"
                        >

                            <option>KG</option>
                            <option>Bag</option>
                            <option>Litre</option>
                            <option>Piece</option>
                            <option>Box</option>

                        </select>

                    </div>


                    <div class="col-md-1">

                        <input
                            class="form-control"
                            type="number"
                            step=".01"
                            name="current_qty"
                            placeholder="Qty"
                            value="0"
                        >

                    </div>


                    <div class="col-md-1">

                        <input
                            class="form-control"
                            type="number"
                            step=".01"
                            name="minimum_qty"
                            placeholder="Min"
                            value="0"
                        >

                    </div>


                    <div class="col-md-1">

                        <input
                            class="form-control"
                            type="number"
                            step=".01"
                            name="unit_cost"
                            placeholder="Cost"
                        >

                    </div>


                    <div class="col-md-1">

                        <button class="btn btn-success w-100">
                            Add
                        </button>

                    </div>


                    <input
                        type="hidden"
                        name="expiry_date"
                    >

                </form>

            </div>


            <div class="cardx p-3">

                <table class="table">

                    <thead>

                        <tr>
                            <th>Item</th>
                            <th>Store</th>
                            <th>For</th>
                            <th>Current</th>
                            <th>Min</th>
                            <th>Avg/day</th>
                            <th>Est. days</th>
                            <th>Status</th>
                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($items as $i): ?>

                            <?php
                            $days = $i['avg_daily'] > 0
                                ? round(
                                    $i['current_qty'] / $i['avg_daily'],
                                    1
                                )
                                : null;
                            ?>

                            <tr>

                                <td>

                                    <b>
                                        <?= htmlspecialchars($i['item_name']) ?>
                                    </b>

                                    <div class="small-muted">
                                        <?= htmlspecialchars($i['item_type']) ?>
                                    </div>

                                </td>


                                <td>
                                    <?= htmlspecialchars($i['store_name']) ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars($i['for_category']) ?>
                                </td>


                                <td>

                                    <?= number_format(
                                        $i['current_qty'],
                                        2
                                    ) ?>

                                    <?= $i['unit'] ?>

                                </td>


                                <td>
                                    <?= number_format(
                                        $i['minimum_qty'],
                                        2
                                    ) ?>
                                </td>


                                <td>
                                    <?= number_format(
                                        $i['avg_daily'],
                                        2
                                    ) ?>
                                </td>


                                <td>

                                    <?= $days === null
                                        ? '—'
                                        : $days . ' days'
                                    ?>

                                </td>


                                <td>

                                    <?= $i['current_qty'] <= $i['minimum_qty']
                                        ? '<span class="badge text-bg-danger">BUY NOW</span>'
                                        : '<span class="badge text-bg-success">OK</span>'
                                    ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>