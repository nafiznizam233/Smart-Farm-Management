<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

session_start();

$_SESSION['cart'] = $_SESSION['cart'] ?? [];


if (isset($_POST['add'])) {

    $id = (int) $_POST['product_id'];
    $q = max(.01, (float) $_POST['qty']);

    $_SESSION['cart'][$id] =
        ($_SESSION['cart'][$id] ?? 0) + $q;


    header(
        'Location: ' . url_path('shop/cart.php')
    );

    exit;
}


if (isset($_GET['remove'])) {

    unset(
        $_SESSION['cart'][(int) $_GET['remove']]
    );


    header(
        'Location: ' . url_path('shop/cart.php')
    );

    exit;
}


$items = [];
$total = 0;


foreach ($_SESSION['cart'] as $id => $q) {

    $st = $pdo->prepare("
        SELECT *
        FROM shop_products
        WHERE id = ?
    ");

    $st->execute([$id]);


    if ($x = $st->fetch()) {

        $x['qty'] = $q;
        $x['line'] = $q * $x['price'];

        $total += $x['line'];

        $items[] = $x;
    }
}


$page_title = 'Shopping Cart';

require __DIR__ . '/../partials/public_header.php';

?>

<div class="container py-5">

    <h2 class="fw-bold">
        Shopping Cart
    </h2>


    <div class="cardx p-3 mt-3">

        <div class="table-wrap">

            <table class="table">

                <thead>

                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total</th>
                        <th></th>
                    </tr>

                </thead>


                <tbody>

                    <?php foreach ($items as $x): ?>

                        <tr>

                            <td>
                                <?= $x['icon'] ?>
                                <?= htmlspecialchars($x['name']) ?>
                            </td>

                            <td>
                                <?= $x['qty'] ?>
                                <?= $x['unit'] ?>
                            </td>

                            <td>
                                ৳<?= number_format($x['price'], 2) ?>
                            </td>

                            <td>
                                ৳<?= number_format($x['line'], 2) ?>
                            </td>

                            <td>

                                <a
                                    class="btn btn-sm btn-outline-danger"
                                    href="?remove=<?= $x['id'] ?>"
                                >
                                    Remove
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>


                    <tr>

                        <th colspan="3">
                            Grand Total
                        </th>

                        <th>
                            ৳<?= number_format($total, 2) ?>
                        </th>

                        <th></th>

                    </tr>

                </tbody>

            </table>

        </div>

    </div>


    <div class="mt-3 d-flex gap-2">

        <a
            class="btn btn-outline-success"
            href="<?= url_path('shop/index.php') ?>"
        >
            Continue Shopping
        </a>


        <?php if ($items): ?>

            <a
                class="btn btn-success"
                href="<?= url_path('shop/checkout.php') ?>"
            >
                Checkout
            </a>

        <?php endif; ?>

    </div>

</div>


<?php require __DIR__ . '/../partials/public_footer.php'; ?>