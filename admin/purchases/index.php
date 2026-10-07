<?php

require __DIR__ . "/../../config/database.php";
require __DIR__ . "/../../config/auth.php";

require_role(['owner', 'manager']);


if (is_manager() || is_staff()) {
    require_work_checkin($pdo);
}


require __DIR__ . "/../../config/finance.php";


$msg = $err = '';

$up = __DIR__ . "/../../uploads/receipts/";


if (!is_dir($up)) {
    mkdir($up, 0777, true);
}


function upload_receipt($up)
{
    if (
        empty($_FILES['receipt']['name']) ||
        $_FILES['receipt']['error'] !== UPLOAD_ERR_OK
    ) {
        return null;
    }


    $ext = strtolower(
        pathinfo(
            $_FILES['receipt']['name'],
            PATHINFO_EXTENSION
        )
    );


    if (
        !in_array(
            $ext,
            ['jpg', 'jpeg', 'png', 'webp', 'pdf'],
            true
        )
    ) {
        return null;
    }


    $n = time() .
        '_' .
        bin2hex(random_bytes(4)) .
        '.' .
        $ext;


    move_uploaded_file(
        $_FILES['receipt']['tmp_name'],
        $up . $n
    );


    return $n;
}


if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['submit_purchase'])
) {

    try {

        $receipt = upload_receipt($up);


        if (is_manager() && !$receipt) {
            throw new Exception(
                'Manager purchase requires receipt/invoice/document.'
            );
        }


        $qty = (float) $_POST['qty'];

        $price = (float) $_POST['unit_price'];

        $total = $qty * $price;

        $catalog = (int) $_POST['catalog_item_id'];


        $st = $pdo->prepare(
            "SELECT *
             FROM purchase_catalog
             WHERE id=?
             AND status='active'"
        );

        $st->execute([$catalog]);

        $cat = $st->fetch();


        if (!$cat) {
            throw new Exception(
                'Select a valid farm-related item.'
            );
        }


        $farm = (int) $_POST['farm_id'];


        $st = $pdo->prepare(
            "SELECT category
             FROM farms
             WHERE id=?"
        );

        $st->execute([$farm]);

        $farmCat = $st->fetchColumn();


        if ($farmCat !== $cat['farm_category']) {
            throw new Exception(
                'Selected item does not belong to this farm category.'
            );
        }


        $payment = $_POST['payment_source'];


        if (is_owner()) {
            $payment = 'account';
        }


        $status = is_owner()
            ? 'pending'
            : 'pending';


        $pdo->beginTransaction();


        $st = $pdo->prepare(
            "INSERT INTO purchases(
                farm_id,
                store_id,
                inventory_item_id,
                catalog_item_id,
                purchase_type,
                item_name,
                qty,
                unit,
                unit_price,
                total_amount,
                supplier,
                purchase_date,
                receipt,
                notes,
                created_by,
                status,
                payment_source,
                account_id
            )
            VALUES(
                ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?
            )"
        );


        $st->execute([
            $farm,
            $_POST['store_id'] ?: null,
            $_POST['inventory_item_id'] ?: null,
            $catalog,
            $cat['purchase_type'],
            $cat['item_name'],
            $qty,
            $cat['default_unit'],
            $price,
            $total,
            trim($_POST['supplier']),
            $_POST['purchase_date'],
            $receipt,
            trim($_POST['notes']),
            current_user_id(),
            $status,
            $payment,
            $_POST['account_id'] ?: null
        ]);


        $pid = (int) $pdo->lastInsertId();


        $pdo->prepare(
            "INSERT INTO approval_log(
                module_name,
                reference_id,
                action,
                action_by,
                note
            )
            VALUES(
                'purchase',
                ?,
                'submitted',
                ?,
                'Purchase submitted'
            )"
        )->execute([
            $pid,
            current_user_id()
        ]);


        if (is_owner()) {
            apply_purchase($pdo, $pid);
        }


        $pdo->commit();


        $msg = is_owner()
            ? 'Purchase approved and posted.'
            : 'Purchase submitted. Waiting for Owner approval.';

    } catch (Exception $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $err = $e->getMessage();
    }
}


if (
    is_owner() &&
    isset($_GET['approve'])
) {

    try {

        $pdo->beginTransaction();


        $id = (int) $_GET['approve'];


        if (
            isset($_GET['account_id']) &&
            $_GET['account_id']
        ) {

            $pdo->prepare(
                "UPDATE purchases
                 SET account_id=?
                 WHERE id=?
                 AND status='pending'"
            )->execute([
                (int) $_GET['account_id'],
                $id
            ]);
        }


        apply_purchase(
            $pdo,
            $id
        );


        $pdo->prepare(
            "INSERT INTO approval_log(
                module_name,
                reference_id,
                action,
                action_by,
                note
            )
            VALUES(
                'purchase',
                ?,
                'approved',
                ?,
                'Owner approved purchase'
            )"
        )->execute([
            $id,
            current_user_id()
        ]);


        $pdo->commit();

        $msg = 'Purchase approved.';

    } catch (Exception $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $err = $e->getMessage();
    }
}


if (
    is_owner() &&
    isset($_GET['reject'])
) {

    $pdo->prepare(
        "UPDATE purchases
         SET
            status='rejected',
            approved_by=?,
            approved_at=NOW(),
            rejection_reason='Rejected by Owner'
         WHERE id=?
         AND status='pending'"
    )->execute([
        current_user_id(),
        (int) $_GET['reject']
    ]);


    $msg = 'Purchase rejected.';
}


$farms = $pdo->query(
    "SELECT
        id,
        name,
        category
     FROM farms
     WHERE status='active'
     ORDER BY name"
)->fetchAll();


$stores = $pdo->query(
    "SELECT
        id,
        name,
        farm_id
     FROM stores
     WHERE status='active'
     ORDER BY name"
)->fetchAll();


$items = $pdo->query(
    "SELECT
        i.id,
        i.item_name,
        i.for_category,
        i.store_id,
        st.name store_name
     FROM inventory_items i
     JOIN stores st
        ON st.id=i.store_id
     ORDER BY i.item_name"
)->fetchAll();


$catalog = $pdo->query(
    "SELECT *
     FROM purchase_catalog
     WHERE status='active'
     ORDER BY
        farm_category,
        purchase_type,
        item_name"
)->fetchAll();


$accounts = $pdo->query(
    "SELECT
        id,
        account_name,
        account_type,
        balance
     FROM accounts
     WHERE status='active'
     ORDER BY
        account_type,
        account_name"
)->fetchAll();


if (is_owner()) {

    $rows = $pdo->query(
        "SELECT
            p.*,
            f.name farm_name,
            u.name creator,
            a.account_name
         FROM purchases p
         LEFT JOIN farms f
            ON f.id=p.farm_id
         JOIN users u
            ON u.id=p.created_by
         LEFT JOIN accounts a
            ON a.id=p.account_id
         ORDER BY p.id DESC
         LIMIT 150"
    )->fetchAll();

} else {

    $st = $pdo->prepare(
        "SELECT
            p.*,
            f.name farm_name,
            u.name creator,
            a.account_name
         FROM purchases p
         LEFT JOIN farms f
            ON f.id=p.farm_id
         JOIN users u
            ON u.id=p.created_by
         LEFT JOIN accounts a
            ON a.id=p.account_id
         WHERE p.created_by=?
         ORDER BY p.id DESC
         LIMIT 150"
    );


    $st->execute([
        current_user_id()
    ]);


    $rows = $st->fetchAll();
}


$page_title = 'Purchase Workflow';

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
                    Controlled Purchase Workflow
                </h2>

                <p class="mb-0">
                    Farm → relevant item only → document → Owner approval → expense/stock/balance.
                </p>

            </div>


            <?php if ($msg): ?>

                <div class="alert alert-success">
                    <?= $msg ?>
                </div>

            <?php endif; ?>


            <?php if ($err): ?>

                <div class="alert alert-danger">
                    <?= $err ?>
                </div>

            <?php endif; ?>


            <div class="cardx p-3 mb-4">

                <form
                    method="post"
                    enctype="multipart/form-data"
                    class="row g-2"
                >

                    <input
                        type="hidden"
                        name="submit_purchase"
                        value="1"
                    >


                    <div class="col-md-3">

                        <label>
                            Farm
                        </label>

                        <select
                            class="form-select"
                            name="farm_id"
                            id="farm"
                            required
                        >

                            <option value="">
                                Select Farm
                            </option>


                            <?php foreach ($farms as $f): ?>

                                <option
                                    value="<?= $f['id'] ?>"
                                    data-category="<?= htmlspecialchars($f['category']) ?>"
                                >
                                    <?= htmlspecialchars($f['name']) ?>
                                    (<?= $f['category'] ?>)
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="col-md-3">

                        <label>
                            Relevant Item
                        </label>

                        <select
                            class="form-select"
                            name="catalog_item_id"
                            id="catalog"
                            required
                        >

                            <option value="">
                                Select farm first
                            </option>


                            <?php foreach ($catalog as $c): ?>

                                <option
                                    value="<?= $c['id'] ?>"
                                    data-category="<?= htmlspecialchars($c['farm_category']) ?>"
                                    data-unit="<?= htmlspecialchars($c['default_unit']) ?>"
                                >
                                    <?= htmlspecialchars(
                                        $c['purchase_type'] .
                                        ' — ' .
                                        $c['item_name']
                                    ) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="col-md-2">

                        <label>
                            Store
                        </label>

                        <select
                            class="form-select"
                            name="store_id"
                            id="store"
                        >

                            <option value="">
                                No store
                            </option>


                            <?php foreach ($stores as $s): ?>

                                <option value="<?= $s['id'] ?>">
                                    <?= htmlspecialchars($s['name']) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="col-md-2">

                        <label>
                            Existing Inventory
                        </label>

                        <select
                            class="form-select"
                            name="inventory_item_id"
                            id="inventory"
                        >

                            <option value="">
                                New/Not stock item
                            </option>


                            <?php foreach ($items as $i): ?>

                                <option
                                    value="<?= $i['id'] ?>"
                                    data-category="<?= htmlspecialchars($i['for_category']) ?>"
                                >
                                    <?= htmlspecialchars(
                                        $i['item_name'] .
                                        ' - ' .
                                        $i['store_name']
                                    ) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="col-md-1">

                        <label>
                            Qty
                        </label>

                        <input
                            class="form-control"
                            type="number"
                            step=".001"
                            name="qty"
                            required
                        >

                    </div>


                    <div class="col-md-1">

                        <label>
                            Unit
                        </label>

                        <input
                            class="form-control"
                            id="unitShow"
                            value="Auto"
                            disabled
                        >

                    </div>


                    <div class="col-md-2">

                        <label>
                            Unit Price
                        </label>

                        <input
                            class="form-control"
                            type="number"
                            step=".01"
                            name="unit_price"
                            required
                        >

                    </div>


                    <div class="col-md-2">

                        <label>
                            Supplier
                        </label>

                        <input
                            class="form-control"
                            name="supplier"
                        >

                    </div>


                    <div class="col-md-2">

                        <label>
                            Date
                        </label>

                        <input
                            class="form-control"
                            type="date"
                            name="purchase_date"
                            value="<?= date('Y-m-d') ?>"
                            required
                        >

                    </div>


                    <div class="col-md-3">

                        <label>
                            Receipt / Invoice

                            <?= is_manager()
                                ? '<b class="text-danger">*</b>'
                                : ''
                            ?>
                        </label>

                        <input
                            class="form-control"
                            type="file"
                            name="receipt"
                            accept=".jpg,.jpeg,.png,.webp,.pdf"
                            <?= is_manager() ? 'required' : '' ?>
                        >

                    </div>


                    <div class="col-md-2">

                        <label>
                            Payment Source
                        </label>


                        <?php if (is_manager()): ?>

                            <select
                                class="form-select"
                                name="payment_source"
                                id="paySource"
                            >

                                <option value="manager_cash">
                                    My Approved Cash
                                </option>

                                <option value="account">
                                    Farm Account (Owner approves)
                                </option>

                            </select>

                        <?php else: ?>

                            <input
                                type="hidden"
                                name="payment_source"
                                value="account"
                            >

                            <input
                                class="form-control"
                                value="Farm Account"
                                disabled
                            >

                        <?php endif; ?>

                    </div>


                    <div class="col-md-3">

                        <label>
                            Account
                        </label>

                        <select
                            class="form-select"
                            name="account_id"
                        >

                            <option value="">
                                Select when account payment
                            </option>


                            <?php foreach ($accounts as $a): ?>

                                <option value="<?= $a['id'] ?>">

                                    <?= htmlspecialchars($a['account_name']) ?>

                                    —

                                    <?= strtoupper($a['account_type']) ?>

                                    —

                                    ৳<?= number_format($a['balance'], 0) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="col-md-4">

                        <label>
                            Notes
                        </label>

                        <input
                            class="form-control"
                            name="notes"
                        >

                    </div>


                    <div class="col-md-2 d-flex align-items-end">

                        <button class="btn btn-success w-100">

                            <?= is_owner()
                                ? 'Buy & Post'
                                : 'Submit for Approval'
                            ?>

                        </button>

                    </div>

                </form>

            </div>


            <div class="cardx p-3">

                <table class="table">

                    <thead>

                        <tr>

                            <th>Date</th>
                            <th>By</th>
                            <th>Farm</th>
                            <th>Item</th>
                            <th>Total</th>
                            <th>Document</th>
                            <th>Payment</th>
                            <th>Status</th>

                            <?php if (is_owner()): ?>
                                <th>Action</th>
                            <?php endif; ?>

                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($rows as $r): ?>

                            <tr>

                                <td>
                                    <?= $r['purchase_date'] ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars($r['creator']) ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars(
                                        $r['farm_name'] ?? '-'
                                    ) ?>
                                </td>


                                <td>

                                    <?= htmlspecialchars($r['item_name']) ?>

                                    <div class="small-muted">
                                        <?= $r['qty'] . ' ' . $r['unit'] ?>
                                    </div>

                                </td>


                                <td>
                                    ৳<?= number_format(
                                        $r['total_amount'],
                                        2
                                    ) ?>
                                </td>


                                <td>

                                    <?php if ($r['receipt']): ?>

                                        <a
                                            target="_blank"
                                            href="/smart_farm_php_v7/uploads/receipts/<?= htmlspecialchars($r['receipt']) ?>"
                                        >
                                            View
                                        </a>

                                    <?php else: ?>

                                        <span class="text-danger">
                                            None
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <?= $r['payment_source'] === 'manager_cash'
                                        ? 'Manager Cash'
                                        : htmlspecialchars(
                                            $r['account_name'] ?? 'Account TBD'
                                        )
                                    ?>

                                </td>


                                <td>
                                    <b>
                                        <?= $r['status'] ?>
                                    </b>
                                </td>


                                <?php if (is_owner()): ?>

                                    <td>

                                        <?php if ($r['status'] === 'pending'): ?>

                                            <form
                                                method="get"
                                                class="d-flex gap-1"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="approve"
                                                    value="<?= $r['id'] ?>"
                                                >


                                                <?php if ($r['payment_source'] === 'account'): ?>

                                                    <select
                                                        class="form-select form-select-sm"
                                                        name="account_id"
                                                        required
                                                    >

                                                        <option value="">
                                                            Account
                                                        </option>


                                                        <?php foreach ($accounts as $a): ?>

                                                            <option
                                                                value="<?= $a['id'] ?>"
                                                                <?= $r['account_id'] == $a['id']
                                                                    ? 'selected'
                                                                    : ''
                                                                ?>
                                                            >

                                                                <?= htmlspecialchars(
                                                                    $a['account_name']
                                                                ) ?>

                                                                ৳<?= number_format(
                                                                    $a['balance'],
                                                                    0
                                                                ) ?>

                                                            </option>

                                                        <?php endforeach; ?>

                                                    </select>

                                                <?php endif; ?>


                                                <button
                                                    class="btn btn-sm btn-success"
                                                >
                                                    Approve
                                                </button>


                                                <a
                                                    class="btn btn-sm btn-danger"
                                                    href="?reject=<?= $r['id'] ?>"
                                                >
                                                    Reject
                                                </a>

                                            </form>

                                        <?php endif; ?>

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


<script>

const farm = document.getElementById('farm');

const catalog = document.getElementById('catalog');

const inv = document.getElementById('inventory');

const unit = document.getElementById('unitShow');


function filter() {

    const c =
        farm.options[farm.selectedIndex]
            ?.dataset.category || '';


    [catalog, inv].forEach(sel => {

        Array.from(sel.options).forEach(
            (o, i) => {

                if (i === 0) {
                    return;
                }

                o.hidden =
                    !!c &&
                    o.dataset.category !== c;
            }
        );


        sel.value = '';
    });


    unit.value = 'Auto';
}


farm.addEventListener(
    'change',
    filter
);


catalog.addEventListener(
    'change',
    () => {

        unit.value =
            catalog.options[catalog.selectedIndex]
                ?.dataset.unit || 'Auto';
    }
);


filter();

</script>