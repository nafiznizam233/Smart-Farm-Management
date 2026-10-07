<?php

require_once __DIR__ . '/../delivery_common.php';

$uid = dc_uid();

if (!$uid) {
    header('Location: ' . url_path('customer/login.php'));
    exit;
}

$orderId = (int) ($_GET['order_id'] ?? 0);

$q = $pdo->prepare(
    "SELECT
        o.*,
        u.name AS delivery_name,
        u.phone AS delivery_phone
     FROM shop_orders o
     LEFT JOIN users u
        ON u.id = o.assigned_staff_id
     WHERE o.id = ?
     AND o.customer_user_id = ?"
);

$q->execute([
    $orderId,
    $uid
]);

$o = $q->fetch();

if (!$o) {
    die('Order not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        isset($_POST['message'])
        && trim($_POST['message']) !== ''
    ) {
        $pdo->prepare(
            "INSERT INTO delivery_chat_messages(
                order_id,
                sender_user_id,
                message
            )
            VALUES (?, ?, ?)"
        )->execute([
            $orderId,
            $uid,
            trim($_POST['message'])
        ]);
    }

    if (isset($_POST['offer_id'])) {
        $id = (int) $_POST['offer_id'];

        $st = ($_POST['decision'] ?? '') === 'accept'
            ? 'accepted'
            : 'declined';

        $pdo->prepare(
            "UPDATE delivery_product_offers
             SET status = ?,
                 responded_at = NOW()
             WHERE id = ?
             AND order_id = ?
             AND customer_user_id = ?"
        )->execute([
            $st,
            $id,
            $orderId,
            $uid
        ]);
    }

    if (
        isset($_POST['tip_amount'])
        && $o['assigned_staff_id']
    ) {
        $a = max(
            0,
            (float) $_POST['tip_amount']
        );

        if ($a > 0) {
            $pdo->prepare(
                "INSERT INTO delivery_tips(
                    order_id,
                    customer_user_id,
                    delivery_user_id,
                    amount,
                    payment_method,
                    reference_no
                )
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    amount = VALUES(amount),
                    payment_method = VALUES(payment_method),
                    reference_no = VALUES(reference_no)"
            )->execute([
                $orderId,
                $uid,
                $o['assigned_staff_id'],
                $a,
                $_POST['tip_method'] ?? 'cash',
                trim($_POST['tip_ref'] ?? '')
            ]);
        }
    }

    header(
        "Location: delivery_room.php?order_id=" . $orderId
    );
    exit;
}

$m = $pdo->prepare(
    "SELECT
        m.*,
        u.name AS sender_name
     FROM delivery_chat_messages m
     JOIN users u
        ON u.id = m.sender_user_id
     WHERE order_id = ?
     ORDER BY m.id"
);

$m->execute([$orderId]);

$msgs = $m->fetchAll();

$of = $pdo->prepare(
    "SELECT
        x.*,
        p.name,
        p.image_path
     FROM delivery_product_offers x
     JOIN shop_products p
        ON p.id = x.product_id
     WHERE x.order_id = ?
     ORDER BY x.id DESC"
);

$of->execute([$orderId]);

$offers = $of->fetchAll();

?>
<!doctype html>

<html>

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width"
    >

    <title>Delivery Room</title>

    <style>
        body {
            font-family: Segoe UI;
            background: #f4f7f5;
            margin: 0;
            color: #17372b;
        }

        .wrap {
            max-width: 850px;
            margin: auto;
            padding: 24px;
        }

        .top,
        .card {
            background: white;
            border: 1px solid #dfe9e3;
            border-radius: 16px;
            padding: 18px;
            margin-bottom: 14px;
        }

        .back {
            color: #176b4b;
        }

        .driver {
            display: flex;
            justify-content: space-between;
        }

        .chat {
            height: 300px;
            overflow: auto;
            background: #f7faf8;
            padding: 12px;
            border-radius: 12px;
        }

        .msg {
            padding: 9px 12px;
            background: white;
            border-radius: 10px;
            margin: 7px 0;
            border: 1px solid #e5ece8;
        }

        .msg.mine {
            margin-left: 15%;
            background: #e5f6ec;
        }

        .row {
            display: flex;
            gap: 8px;
        }

        input,
        select,
        button {
            padding: 11px;
            border: 1px solid #ccd9d1;
            border-radius: 9px;
        }

        input {
            flex: 1;
        }

        button {
            background: #176b4b;
            color: white;
            border: 0;
        }

        .offer {
            border-left: 4px solid #176b4b;
            padding: 10px;
            margin: 8px 0;
            background: #f5fbf7;
        }
    </style>

</head>

<body>

    <div class="wrap">

        <a
            class="back"
            href="<?= htmlspecialchars(dc_back()) ?>"
        >
            ← Back
        </a>

        <div class="top">

            <h2>
                Order #<?= $orderId ?> · Delivery Room
            </h2>

            <?php if ($o['assigned_staff_id']): ?>

                <div class="driver">

                    <b>
                        Delivery:
                        <?= htmlspecialchars(
                            $o['delivery_name'] ?: 'Assigned staff'
                        ) ?>
                    </b>

                    <span>
                        <?= htmlspecialchars(
                            $o['delivery_phone'] ?: 'Phone unavailable'
                        ) ?>
                    </span>

                </div>

            <?php else: ?>

                <p>
                    Waiting for manager to assign a delivery staff.
                </p>

            <?php endif; ?>

        </div>

        <div class="card">

            <h3>Chat with delivery staff</h3>

            <div class="chat">

                <?php foreach ($msgs as $x): ?>

                    <div
                        class="msg <?= $x['sender_user_id'] == $uid
                            ? 'mine'
                            : '' ?>"
                    >

                        <b>
                            <?= htmlspecialchars(
                                $x['sender_name']
                            ) ?>
                        </b>

                        <br>

                        <?= nl2br(
                            htmlspecialchars(
                                $x['message']
                            )
                        ) ?>

                    </div>

                <?php endforeach; ?>

            </div>

            <form
                method="post"
                class="row"
            >

                <input
                    name="message"
                    required
                    placeholder="Ask how long it will take, product availability..."
                >

                <button>Send</button>

            </form>

        </div>

        <div class="card">

            <h3>
                Product offers from your delivery staff
            </h3>

            <?php if (!$offers): ?>

                <p>No offer yet.</p>

            <?php endif; ?>

            <?php foreach ($offers as $x): ?>

                <div class="offer">

                    <b>
                        <?= htmlspecialchars($x['name']) ?>
                    </b>

                    —
                    <?= $x['qty'] ?>
                    ×
                    ৳<?= number_format($x['unit_price'], 2) ?>

                    <b>
                        [<?= htmlspecialchars($x['status']) ?>]
                    </b>

                    <?php if ($x['status'] === 'offered'): ?>

                        <form method="post">

                            <input
                                type="hidden"
                                name="offer_id"
                                value="<?= $x['id'] ?>"
                            >

                            <button
                                name="decision"
                                value="accept"
                            >
                                Accept
                            </button>

                            <button
                                name="decision"
                                value="decline"
                            >
                                Decline
                            </button>

                        </form>

                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        </div>

        <?php if ($o['assigned_staff_id']): ?>

            <div class="card">

                <h3>Tip your delivery staff</h3>

                <form
                    method="post"
                    class="row"
                >

                    <input
                        type="number"
                        min="1"
                        name="tip_amount"
                        placeholder="e.g. 10"
                    >

                    <select name="tip_method">

                        <option value="cash">
                            Cash
                        </option>

                        <option value="bkash">
                            bKash
                        </option>

                        <option value="nagad">
                            Nagad
                        </option>

                        <option value="other_mfs">
                            Other MFS
                        </option>

                    </select>

                    <input
                        name="tip_ref"
                        placeholder="Transaction ID (if MFS)"
                    >

                    <button>Save Tip</button>

                </form>

            </div>

        <?php endif; ?>

    </div>

</body>

</html>