<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

session_start();


if (empty($_SESSION['customer'])) {

    header(
        'Location: ' . url_path('login.php?type=customer')
    );

    exit;
}


$cart = $_SESSION['cart'] ?? [];
$err = '';

$preDate = $_POST['preorder_date']
    ?? date('Y-m-d', strtotime('+1 day'));


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $cart) {

    try {

        $pdo->beginTransaction();

        $locked = [];
        $sub = 0;

        $isPre =
            ($_POST['order_type'] ?? 'normal') === 'preorder';

        $method =
            $_POST['delivery_method'] ?? 'local_delivery';


        if ($isPre && $method !== 'pickup') {

            throw new Exception(
                'Pre-orders are pickup only. Choose Farm / Station Pickup.'
            );
        }


        foreach ($cart as $id => $q) {

            $st = $pdo->prepare("
                SELECT *
                FROM shop_products
                WHERE id = ?
                FOR UPDATE
            ");

            $st->execute([$id]);

            $x = $st->fetch();


            if (!$x || $x['status'] !== 'active') {
                throw new Exception('Product unavailable.');
            }


            if ($isPre) {

                if (!$x['preorder_enabled']) {

                    throw new Exception(
                        $x['name'] . ' is not enabled for pre-order.'
                    );
                }


                $c = $pdo->prepare("
                    SELECT *
                    FROM farm_daily_capacity
                    WHERE product_id = ?
                      AND capacity_date = ?
                    FOR UPDATE
                ");

                $c->execute([
                    $id,
                    $preDate
                ]);

                $cap = $c->fetch();


                if (!$cap) {

                    throw new Exception(
                        'No pre-order capacity is active for ' .
                        $x['name'] .
                        ' on ' .
                        $preDate .
                        '.'
                    );
                }


                $r = $pdo->prepare("
                    SELECT COALESCE(SUM(oi.qty), 0)

                    FROM shop_order_items oi

                    JOIN shop_orders o
                        ON o.id = oi.order_id

                    WHERE oi.product_id = ?
                      AND o.preorder = 1
                      AND o.preorder_date = ?
                      AND o.status <> 'cancelled'
                ");

                $r->execute([
                    $id,
                    $preDate
                ]);

                $reserved = (float) $r->fetchColumn();


                if (
                    $reserved + $q >
                    (float) $cap['preorder_limit']
                ) {

                    throw new Exception(
                        $x['name'] .
                        ' exceeds remaining pre-order capacity. Remaining: ' .
                        max(
                            0,
                            $cap['preorder_limit'] - $reserved
                        ) .
                        ' ' .
                        $x['unit']
                    );
                }

            } elseif ((float) $x['stock_qty'] < $q) {

                throw new Exception(
                    $x['name'] .
                    ' has insufficient live stock. Use Pre-order if available.'
                );
            }


            $locked[] = [
                $x,
                $q
            ];

            $sub += $q * $x['price'];
        }


        $charge = $method === 'pickup'
            ? 0
            : (
                $method === 'local_delivery'
                    ? 80
                    : 150
            );

        $total = $sub + $charge;


        $provider =
            $_POST['payment_provider'] ?? 'cash';

        $ref = trim(
            $_POST['payment_reference'] ?? ''
        );


        if (
            in_array(
                $provider,
                ['bkash', 'nagad', 'rocket', 'bank']
            ) &&
            $ref === ''
        ) {

            throw new Exception(
                'Enter transaction/reference number for ' .
                $provider .
                '.'
            );
        }


        $paymentMethod = $provider === 'cash'
            ? 'cash_on_delivery'
            : (
                $provider === 'bank'
                    ? 'bank'
                    : 'mfs'
            );


        $paymentStatus = $provider === 'cash'
            ? 'unpaid'
            : 'paid';


        $pdo->prepare("
            INSERT INTO shop_orders (
                customer_user_id,
                total_amount,
                status,
                delivery_address,
                phone,
                alternate_phone,
                delivery_method,
                delivery_charge,
                payment_method,
                payment_status,
                preorder,
                preorder_date,
                payment_provider,
                payment_reference,
                customer_note
            )
            VALUES (
                ?,
                ?,
                'pending',
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )
        ")->execute([
            $_SESSION['customer']['id'],
            $total,
            trim($_POST['address']),
            trim($_POST['phone']),
            trim($_POST['alternate_phone']),
            $method,
            $charge,
            $paymentMethod,
            $paymentStatus,
            $isPre ? 1 : 0,
            $isPre ? $preDate : null,
            $provider,
            $ref,
            trim($_POST['note'])
        ]);


        $oid = $pdo->lastInsertId();


        foreach ($locked as [$x, $q]) {

            $pdo->prepare("
                INSERT INTO shop_order_items (
                    order_id,
                    product_id,
                    product_name,
                    qty,
                    unit_price,
                    line_total
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ")->execute([
                $oid,
                $x['id'],
                $x['name'],
                $q,
                $x['price'],
                $q * $x['price']
            ]);


            if (!$isPre) {

                $pdo->prepare("
                    UPDATE shop_products
                    SET stock_qty = stock_qty - ?
                    WHERE id = ?
                ")->execute([
                    $q,
                    $x['id']
                ]);
            }
        }


        $pdo->commit();

        $_SESSION['cart'] = [];


        header(
            'Location: ' .
            url_path('customer/dashboard.php?ordered=1')
        );

        exit;

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $err = $e->getMessage();
    }
}


$page_title = 'Checkout';

require __DIR__ . '/../partials/public_header.php';

?>

<div class="login-wrap">

    <div
        class="cardx p-4"
        style="width:min(780px,96vw)"
    >

        <h3>
            Checkout / Pre-order
        </h3>

        <p class="text-muted">
            Normal orders may use delivery or pickup.
            Pre-orders reserve the manager-defined daily capacity
            and must be collected from the farm/station.
        </p>


        <?php if ($err): ?>

            <div class="alert alert-danger">
                <?= htmlspecialchars($err) ?>
            </div>

        <?php endif; ?>


        <form method="post">

            <div class="row g-3">

                <div class="col-md-6">

                    <label>
                        Order Type
                    </label>

                    <select
                        class="form-select"
                        name="order_type"
                        id="orderType"
                    >
                        <option value="normal">
                            Normal Order
                        </option>

                        <option value="preorder">
                            Pre-order
                        </option>
                    </select>

                </div>


                <div
                    class="col-md-6"
                    id="preDate"
                >

                    <label>
                        Pre-order Pickup Date
                    </label>

                    <input
                        class="form-control"
                        type="date"
                        min="<?= date('Y-m-d') ?>"
                        name="preorder_date"
                        value="<?= htmlspecialchars($preDate) ?>"
                    >

                </div>


                <div class="col-md-6">

                    <label>
                        Primary Mobile
                    </label>

                    <input
                        class="form-control"
                        name="phone"
                        required
                    >

                </div>


                <div class="col-md-6">

                    <label>
                        Alternative Mobile
                    </label>

                    <input
                        class="form-control"
                        name="alternate_phone"
                    >

                </div>


                <div class="col-12">

                    <label>
                        Address / Pickup Contact Address
                    </label>

                    <textarea
                        class="form-control"
                        name="address"
                        rows="2"
                        required
                    ></textarea>

                </div>


                <div class="col-md-6">

                    <label>
                        Fulfilment
                    </label>

                    <select
                        class="form-select"
                        name="delivery_method"
                        id="deliveryMethod"
                    >

                        <option value="local_delivery">
                            Local Home Delivery — ৳80
                        </option>

                        <option value="courier">
                            Courier — ৳150
                        </option>

                        <option value="pickup">
                            Farm / Station Pickup — Free
                        </option>

                    </select>

                </div>


                <div class="col-md-6">

                    <label>
                        Payment
                    </label>

                    <select
                        class="form-select"
                        name="payment_provider"
                        id="provider"
                    >

                        <option value="cash">
                            Cash / Pay at Handover
                        </option>

                        <option value="bkash">
                            bKash
                        </option>

                        <option value="nagad">
                            Nagad
                        </option>

                        <option value="rocket">
                            Rocket
                        </option>

                        <option value="bank">
                            Bank
                        </option>

                    </select>

                </div>


                <div
                    class="col-12"
                    id="refBox"
                >

                    <label>
                        Transaction / Reference Number
                    </label>

                    <input
                        class="form-control"
                        name="payment_reference"
                        placeholder="Enter transaction ID/reference for bKash, Nagad, Rocket or Bank"
                    >

                </div>


                <div class="col-12">

                    <label>
                        Order Note
                    </label>

                    <input
                        class="form-control"
                        name="note"
                    >

                </div>

            </div>


            <button class="btn btn-success w-100 mt-3">
                Confirm Order
            </button>

        </form>

    </div>

</div>


<script>

const ot = document.getElementById('orderType');
const dm = document.getElementById('deliveryMethod');
const pd = document.getElementById('preDate');
const pr = document.getElementById('provider');
const rb = document.getElementById('refBox');


function sync() {

    const pre = ot.value === 'preorder';

    pd.style.display = pre
        ? 'block'
        : 'none';


    if (pre) {
        dm.value = 'pickup';
    }


    [...dm.options].forEach(o => {
        o.disabled = pre && o.value !== 'pickup';
    });


    rb.style.display = pr.value === 'cash'
        ? 'none'
        : 'block';
}


ot.onchange = sync;
pr.onchange = sync;

sync();

</script>


<?php require __DIR__ . '/../partials/public_footer.php'; ?>