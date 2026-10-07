<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

session_start();


$p = $pdo->query("
    SELECT *
    FROM shop_products
    WHERE status = 'active'
    ORDER BY
        (stock_qty > 0) DESC,
        category,
        name
")->fetchAll();


$page_title = 'Farm Shop';

require __DIR__ . '/../partials/public_header.php';

?>

<section class="shop-hero">

    <div>

        <span class="eyebrow">
            FRESH FROM THE FARM
        </span>

        <h1>
            Shop farm products
        </h1>

        <p>
            Clear availability, simple ordering and delivery tracking
            from your customer account.
        </p>

    </div>


    <a
        class="cart-pill"
        href="<?= url_path('shop/cart.php') ?>"
    >
        🛒 Cart

        <b>
            <?= array_sum($_SESSION['cart'] ?? []) ?>
        </b>
    </a>

</section>


<?php if (!empty($_SESSION['customer'])): ?>

    <div class="container pt-4">

        <a
            class="support-banner"
            href="<?= url_path('customer/support.php') ?>"
        >

            <b>
                Need help with a product?
            </b>

            <span>
                Chat with Smart Farm Support →
            </span>

        </a>

    </div>

<?php endif; ?>


<div class="container py-5">

    <div class="shop-toolbar">

        <div>

            <h3>
                Available Products
            </h3>

            <p>
                Stock shown here is connected to farm production collection.
            </p>

        </div>

    </div>


    <div class="row g-4">

        <?php if (!$p): ?>

            <div class="col-12">

                <div class="empty-state">

                    🌱

                    <h4>
                        Products are being prepared
                    </h4>

                    <p>
                        Check again soon for fresh farm stock.
                    </p>

                </div>

            </div>

        <?php endif; ?>


        <?php foreach ($p as $x): ?>

            <?php

            $available = (float) $x['stock_qty'] > 0;
            $canPre = !empty($x['preorder_enabled']);

            ?>

            <div class="col-sm-6 col-lg-4 col-xl-3">

                <article class="product-card">

                    <div class="product-media">

                        <?php if (!empty($x['image_path'])): ?>

                            <img
                                src="<?= url_path($x['image_path']) ?>"
                                alt="<?= htmlspecialchars($x['name']) ?>"
                            >

                        <?php else: ?>

                            <div class="product-photo-required">

                                <b>
                                    Real product photo required
                                </b>

                                <small>
                                    Manager can upload from Shop Management
                                </small>

                            </div>

                        <?php endif; ?>


                        <span
                            class="stock-tag <?= $available ? 'in' : 'out' ?>"
                        >
                            <?= $available
                                ? 'In stock'
                                : ($canPre ? 'Pre-order' : 'Out of stock')
                            ?>
                        </span>

                    </div>


                    <div class="product-body">

                        <small>
                            <?= htmlspecialchars($x['category']) ?>
                        </small>


                        <h4>
                            <?= htmlspecialchars($x['name']) ?>
                        </h4>


                        <p>
                            <?= htmlspecialchars(
                                $x['description']
                                ?: 'Fresh farm product managed through Smart Farm.'
                            ) ?>
                        </p>


                        <div class="price-row">

                            <b>
                                ৳<?= number_format($x['price'], 2) ?>
                            </b>

                            <span>
                                / <?= htmlspecialchars($x['unit']) ?>
                            </span>

                        </div>


                        <div class="availability">

                            Available:

                            <b>
                                <?= $x['stock_qty'] ?>
                                <?= htmlspecialchars($x['unit']) ?>
                            </b>

                        </div>


                        <?php if ($available || $canPre): ?>

                            <form
                                method="post"
                                action="<?= url_path('shop/cart.php') ?>"
                                class="buy-row"
                            >

                                <input
                                    type="hidden"
                                    name="product_id"
                                    value="<?= $x['id'] ?>"
                                >


                                <input
                                    class="form-control"
                                    type="number"
                                    min=".01"
                                    step=".01"
                                    <?= $available
                                        ? 'max="' . $x['stock_qty'] . '"'
                                        : ''
                                    ?>
                                    name="qty"
                                    value="1"
                                    required
                                >


                                <button
                                    class="btn btn-success"
                                    name="add"
                                >
                                    <?= $available
                                        ? 'Add to Cart'
                                        : 'Add for Pre-order'
                                    ?>
                                </button>

                            </form>

                        <?php else: ?>

                            <button
                                class="btn btn-secondary w-100"
                                disabled
                            >
                                Currently unavailable
                            </button>

                        <?php endif; ?>

                    </div>

                </article>

            </div>

        <?php endforeach; ?>

    </div>

</div>


<?php require __DIR__ . '/../partials/public_footer.php'; ?>