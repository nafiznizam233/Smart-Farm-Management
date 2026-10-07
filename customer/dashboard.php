<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

session_start();

if (empty($_SESSION['customer'])) {
    header(
        'Location: ' . url_path('customer/login.php')
    );
    exit;
}

$st = $pdo->prepare(
    "SELECT
        o.*,
        du.name AS delivery_name,
        du.phone AS delivery_phone
     FROM shop_orders o
     LEFT JOIN staff ds
        ON ds.id = o.assigned_staff_id
     LEFT JOIN users du
        ON du.id = ds.user_id
     WHERE o.customer_user_id = ?
     ORDER BY o.id DESC"
);

$st->execute([
    $_SESSION['customer']['id']
]);

$orders = $st->fetchAll();

$page_title = 'My Orders';

require __DIR__ . '/../partials/public_header.php';
?>

<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h2 class="fw-bold mb-1">
                Hello, <?= htmlspecialchars($_SESSION['customer']['name']) ?>
            </h2>

            <div class="text-muted">
                Your farm shop orders, assigned delivery person and support
            </div>
        </div>

        <a
            class="btn btn-success"
            href="<?= url_path('shop/index.php') ?>"
        >
            Continue Shopping
        </a>

        <a
            class="btn btn-outline-success"
            href="<?= url_path('customer/support.php') ?>"
        >
            Support Chat
        </a>

    </div>

    <div class="cardx p-3">

        <div class="table-wrap">

            <table class="table">

                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Address</th>
                        <th>Delivery Staff</th>
                        <th>Date</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (!$orders): ?>
                        <tr>
                            <td
                                colspan="6"
                                class="text-muted"
                            >
                                No orders yet.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($orders as $o): ?>

                        <tr>

                            <td>
                                #<?= $o['id'] ?>
                            </td>

                            <td>
                                ৳<?= number_format($o['total_amount'], 2) ?>
                            </td>

                            <td>
                                <span class="badge-soft">
                                    <?= htmlspecialchars(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $o['status']
                                        )
                                    ) ?>
                                </span>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $o['delivery_address']
                                ) ?>
                            </td>

                            <td>

                                <?php if ($o['delivery_name']): ?>

                                    <b>
                                        <?= htmlspecialchars(
                                            $o['delivery_name']
                                        ) ?>
                                    </b>

                                    <small class="d-block text-muted">
                                        <?= htmlspecialchars(
                                            $o['delivery_phone'] ?? ''
                                        ) ?>
                                    </small>

                                <?php else: ?>

                                    <span class="text-muted">
                                        Not assigned yet
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>
                                <?= $o['created_at'] ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<?php require __DIR__ . '/../partials/public_footer.php'; ?>