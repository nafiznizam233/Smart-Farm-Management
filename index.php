<?php

require __DIR__ . '/config/database.php';
require __DIR__ . '/config/app.php';


$products = $pdo->query("
    SELECT *
    FROM shop_products
    WHERE status = 'active'
    ORDER BY
        stock_qty DESC,
        name
    LIMIT 40
")->fetchAll();


$cats = [];

foreach ($products as $p) {

    $c = trim(
        $p['category'] ?? 'Farm Products'
    );

    if ($c && !in_array($c, $cats)) {
        $cats[] = $c;
    }
}


$farms = [];

try {

    $farms = $pdo->query("
        SELECT *
        FROM farms
        ORDER BY id DESC
        LIMIT 4
    ")->fetchAll();

} catch (Throwable $e) {
}


function imgp($p, $fallback)
{
    $v = trim(
        $p['image_path'] ?? ''
    );

    return $v ?: $fallback;
}

?>

<!doctype html>

<html>

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width,initial-scale=1"
    >

    <title>
        Smart Farm Marketplace
    </title>

    <link
        rel="stylesheet"
        href="<?= url_path('assets/css/marketplace-final.css') ?>"
    >

</head>

<body>


<div class="notice">
    Farm fresh delivery in Dhaka • Pre-order available • Support after account login
</div>


<header>

    <a
        class="brand"
        href="<?= url_path('') ?>"
    >

        <img
            class="siteLogo"
            src="<?= asset_path('logo.png') ?>"
            alt="Smart Farm logo"
        >

        <span>

            <b>
                SMART FARM
            </b>

            <small>
                Fresh from our farms
            </small>

        </span>

    </a>


    <form
        class="search"
        id="productSearch"
    >

        <input
            id="searchBox"
            placeholder="Search milk, fish, rice and farm products..."
        >

        <button>
            Search
        </button>

    </form>


    <div class="headlinks">

        <a href="<?= url_path('login.php') ?>">
            Login
        </a>

        <a href="<?= url_path('customer/orders.php') ?>">
            Track Order
        </a>

        <a
            class="cart"
            href="<?= url_path('shop/index.php') ?>"
        >
            Shop / Cart
        </a>

    </div>

</header>


<nav class="nav">

    <a href="#categories">
        Categories
    </a>

    <a href="#top">
        Top Products
    </a>

    <a href="#fresh">
        Fresh Today
    </a>

    <a href="#farms">
        Our Farms
    </a>

    <a href="#delivery">
        Delivery
    </a>

    <a href="<?= url_path('customer/support.php') ?>">
        Support
    </a>

    <a href="<?= url_path('seller_apply.php') ?>">
        Sell With Us
    </a>

</nav>


<main>

    <section class="hero">

        <div class="heroText">

            <span>
                TRACEABLE • FRESH • FARM DIRECT
            </span>

            <h1>
                Everyday farm essentials, delivered with care.
            </h1>

            <p>
                Shop fresh milk, fish, rice and seasonal farm products
                from one marketplace. Live stock is connected with farm
                collection and delivery operations.
            </p>

            <div>

                <a
                    class="primary"
                    href="#top"
                >
                    Shop now
                </a>

                <a
                    class="secondary"
                    href="#farms"
                >
                    See our farms
                </a>

            </div>

            <ul>
                <li>✓ Managed stock</li>
                <li>✓ Pre-order support</li>
                <li>✓ Local & courier delivery</li>
            </ul>

        </div>


        <div class="heroMedia">

            <?php

            $hi = $farms[0]['image_path'] ?? '';

            ?>

            <?php if ($hi): ?>

                <img src="<?= url_path($hi) ?>">

            <?php else: ?>

                <img
                    src="<?= url_path('assets/photos/our-farm.jpg') ?>"
                    alt="Smart Farm"
                >

            <?php endif; ?>

        </div>

    </section>


    <section
        id="categories"
        class="section"
    >

        <div class="heading">

            <div>

                <small>
                    SHOP BY CATEGORY
                </small>

                <h2>
                    Featured Categories
                </h2>

            </div>

            <a href="#top">
                View products →
            </a>

        </div>


        <div class="categories">

            <?php

            $shown = array_slice($cats, 0, 8);

            if (!$shown) {

                $shown = [
                    'Fresh Milk',
                    'Fish',
                    'Rice',
                    'Farm Products'
                ];
            }

            ?>


            <?php foreach ($shown as $i => $c): ?>

                <button
                    class="cat"
                    data-cat="<?= htmlspecialchars(strtolower($c)) ?>"
                >

                    <div class="catPhoto">

                        <?php

                        $match = null;

                        foreach ($products as $pp) {

                            if (
                                strtolower($pp['category'] ?? '') === strtolower($c) &&
                                !empty($pp['image_path'])
                            ) {

                                $match = $pp;
                                break;
                            }
                        }

                        ?>


                        <?php if ($match): ?>

                            <img src="<?= url_path($match['image_path']) ?>">

                        <?php else: ?>

                            <?php

                            $lc = strtolower($c);

                            $cf = strpos($lc, 'fish') !== false
                                ? 'fresh-fish-products.jpg'
                                : (
                                    strpos($lc, 'rice') !== false
                                        ? 'rice-products.jpg'
                                        : (
                                            strpos($lc, 'egg') !== false ||
                                            strpos($lc, 'poultry') !== false
                                                ? 'eggs.jpg'
                                                : (
                                                    strpos($lc, 'veget') !== false
                                                        ? 'fresh-vegetables.jpg'
                                                        : 'milk-products.jpg'
                                                )
                                        )
                                );

                            ?>

                            <img
                                src="<?= url_path('assets/photos/' . $cf) ?>"
                            >

                        <?php endif; ?>

                    </div>


                    <b>
                        <?= htmlspecialchars($c) ?>
                    </b>

                    <small>
                        Explore products
                    </small>

                </button>

            <?php endforeach; ?>

        </div>

    </section>


    <section
        id="top"
        class="section alt"
    >

        <div class="heading">

            <div>

                <small>
                    POPULAR FROM THE FARM
                </small>

                <h2>
                    Top Selling & Available Products
                </h2>

            </div>


            <div class="filter">

                <button
                    class="active"
                    data-filter="all"
                >
                    All
                </button>


                <?php foreach (array_slice($cats, 0, 5) as $c): ?>

                    <button
                        data-filter="<?= htmlspecialchars(strtolower($c)) ?>"
                    >
                        <?= htmlspecialchars($c) ?>
                    </button>

                <?php endforeach; ?>

            </div>

        </div>


        <div
            class="products"
            id="productGrid"
        >

            <?php foreach ($products as $p): ?>

                <article
                    class="product"
                    data-cat="<?= htmlspecialchars(
                        strtolower($p['category'] ?? '')
                    ) ?>"
                    data-search="<?= htmlspecialchars(
                        strtolower(
                            ($p['name'] ?? '') .
                            ' ' .
                            ($p['category'] ?? '')
                        )
                    ) ?>"
                >

                    <div class="pimg">

                        <?php if (!empty($p['image_path'])): ?>

                            <img src="<?= url_path($p['image_path']) ?>">

                        <?php else: ?>

                            <?php

                            $lc = strtolower(
                                ($p['category'] ?? '') .
                                ' ' .
                                ($p['name'] ?? '')
                            );

                            $pf = strpos($lc, 'fish') !== false
                                ? 'fresh-fish-products.jpg'
                                : (
                                    strpos($lc, 'rice') !== false
                                        ? 'rice-products.jpg'
                                        : (
                                            strpos($lc, 'egg') !== false ||
                                            strpos($lc, 'poultry') !== false
                                                ? 'eggs.jpg'
                                                : (
                                                    strpos($lc, 'feed') !== false
                                                        ? 'animal-feed.jpg'
                                                        : (
                                                            strpos($lc, 'medicine') !== false
                                                                ? 'farm-medicine.jpg'
                                                                : (
                                                                    strpos($lc, 'veget') !== false
                                                                        ? 'fresh-vegetables.jpg'
                                                                        : 'milk-products.jpg'
                                                                )
                                                        )
                                                )
                                        )
                                );

                            ?>

                            <img
                                src="<?= url_path('assets/photos/' . $pf) ?>"
                                alt="<?= htmlspecialchars($p['name']) ?>"
                            >

                        <?php endif; ?>


                        <?php if (!empty($p['preorder_enabled'])): ?>

                            <em>
                                PRE-ORDER
                            </em>

                        <?php elseif (($p['stock_qty'] ?? 0) > 0): ?>

                            <em class="green">
                                AVAILABLE
                            </em>

                        <?php endif; ?>

                    </div>


                    <div class="pbody">

                        <small>
                            <?= htmlspecialchars(
                                $p['category'] ?? 'Farm Product'
                            ) ?>
                        </small>

                        <h3>
                            <?= htmlspecialchars($p['name']) ?>
                        </h3>


                        <div class="stock">

                            <?= ($p['stock_qty'] ?? 0) > 0
                                ? 'In stock: ' .
                                    htmlspecialchars(
                                        $p['stock_qty'] .
                                        ' ' .
                                        $p['unit']
                                    )
                                : 'Awaiting next collection'
                            ?>

                        </div>


                        <?php if (!empty($p['available_after'])): ?>

                            <div class="pre">
                                Next collection after
                                <?= date(
                                    'g:i A',
                                    strtotime($p['available_after'])
                                ) ?>
                            </div>

                        <?php endif; ?>


                        <div class="price">

                            <b>
                                ৳<?= number_format(
                                    (float) $p['price'],
                                    0
                                ) ?>
                            </b>

                            <span>
                                / <?= htmlspecialchars($p['unit']) ?>
                            </span>

                        </div>


                        <div class="pactions">

                            <a
                                href="<?= url_path(
                                    'shop/index.php?product=' . $p['id']
                                ) ?>"
                            >
                                Add to cart
                            </a>

                            <a
                                class="buy"
                                href="<?= url_path(
                                    'shop/index.php?product=' . $p['id']
                                ) ?>"
                            >
                                Buy now
                            </a>

                        </div>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    </section>


    <section
        id="fresh"
        class="promo"
    >

        <div>

            <small>
                FRESH PRODUCT DELIVERY
            </small>

            <h2>
                Milk and sensitive products stay local.
            </h2>

            <p>
                Fresh items can be configured for the surrounding 10 km
                service area, while suitable products such as rice can use
                courier delivery. Morning collection items can be pre-ordered.
            </p>

        </div>


        <div class="promoCard">

            <b>
                Morning Milk Collection
            </b>

            <span>
                Pre-order now → wait for collection availability
            </span>

            <strong>
                Local delivery
            </strong>

        </div>

    </section>


    <section
        id="farms"
        class="section"
    >

        <div class="heading">

            <div>

                <small>
                    BEHIND THE MARKETPLACE
                </small>

                <h2>
                    Our Farms
                </h2>

            </div>

        </div>


        <div class="farmGrid">

            <?php if ($farms): ?>

                <?php foreach ($farms as $f): ?>

                    <article>

                        <?php if (!empty($f['image_path'])): ?>

                            <img src="<?= url_path($f['image_path']) ?>">

                        <?php else: ?>

                            <?php

                            $fn = strtolower(
                                $f['name'] ?? ''
                            );

                            $ff = strpos($fn, 'fish') !== false
                                ? 'fresh-fish-farm.jpg'
                                : (
                                    strpos($fn, 'rice') !== false
                                        ? 'rice-field.jpg'
                                        : (
                                            strpos($fn, 'poultry') !== false
                                                ? 'poultry-farm.jpg'
                                                : 'fresh-milk-farm.jpg'
                                        )
                                );

                            ?>

                            <img
                                src="<?= url_path('assets/photos/' . $ff) ?>"
                                alt="<?= htmlspecialchars(
                                    $f['name'] ?? 'Farm'
                                ) ?>"
                            >

                        <?php endif; ?>


                        <div>

                            <h3>
                                <?= htmlspecialchars(
                                    $f['name'] ?? 'Smart Farm'
                                ) ?>
                            </h3>

                            <p>
                                Production, staff activity and stock are
                                managed through the internal Smart Farm system.
                            </p>

                        </div>

                    </article>

                <?php endforeach; ?>

            <?php else: ?>

                <article>

                    <img
                        src="<?= url_path('assets/photos/our-farm.jpg') ?>"
                        alt="Our Farm"
                    >

                    <div>

                        <h3>
                            Your Farm
                        </h3>

                        <p>
                            Manager can add farm information and photography.
                        </p>

                    </div>

                </article>

            <?php endif; ?>

        </div>

    </section>


    <section
        id="delivery"
        class="trust"
    >

        <div>
            <b>Farm Direct</b>
            <span>Collection updates marketplace stock.</span>
        </div>

        <div>
            <b>Managed Delivery</b>
            <span>Assigned staff, chat and order tracking.</span>
        </div>

        <div>
            <b>Flexible Payment</b>
            <span>COD, bKash, Nagad and MFS recording.</span>
        </div>

        <div>
            <b>Customer Support</b>
            <span>Account-based help and product support.</span>
        </div>

    </section>


    <section class="story">

        <div>

            <small>
                WHY SMART FARM
            </small>

            <h2>
                A marketplace connected to real farm operations.
            </h2>

            <p>
                Unlike a standalone shop demo, the customer storefront is
                linked to production collection, inventory, manager pricing,
                delivery assignment, delivery-staff communication, payment
                collection and management reporting.
            </p>

        </div>

        <a
            class="primary"
            href="<?= url_path('customer/register.php') ?>"
        >
            Create customer account
        </a>

    </section>


    <section class="story">

        <div>

            <small>
                FOR LOCAL FARMERS
            </small>

            <h2>
                Have a small farm? Supply through Smart Farm.
            </h2>

            <p>
                Apply to supply products through our marketplace.
                Customers continue to shop from one Smart Farm storefront.
            </p>

        </div>

        <a
            class="primary"
            href="<?= url_path('seller_apply.php') ?>"
        >
            Apply to Sell
        </a>

    </section>

</main>


<footer>

    <div>

        <h3>
            Smart Farm
        </h3>

        <p>
            Farm fresh marketplace and integrated farm management.
        </p>

        <p>
            Mirpur 11, Dhaka, Bangladesh
        </p>

    </div>


    <div>

        <b>
            Information
        </b>

        <a href="#farms">
            Our Farms
        </a>

        <a href="#delivery">
            Delivery
        </a>

        <a href="<?= url_path('seller_apply.php') ?>">
            Sell With Us
        </a>

    </div>


    <div>

        <b>
            Customer
        </b>

        <a href="<?= url_path('customer/register.php') ?>">
            Create Account
        </a>

        <a href="<?= url_path('customer/login.php') ?>">
            Sign In
        </a>

        <a href="<?= url_path('customer/orders.php') ?>">
            Order Tracking
        </a>

    </div>


    <div>

        <b>
            Support
        </b>

        <a href="<?= url_path('customer/support.php') ?>">
            Support Center
        </a>

        <a href="<?= url_path('shop/index.php') ?>">
            Shop
        </a>

    </div>


    <div class="footbottom">
        © 2026 Smart Farm Management System
    </div>

</footer>


<script>

const ps = [
    ...document.querySelectorAll('.product')
];

const fs = [
    ...document.querySelectorAll('[data-filter]')
];

const q = document.getElementById('searchBox');

let cf = 'all';


function apply()
{
    let s = (q.value || '').toLowerCase();

    ps.forEach(p => {

        p.style.display =
            (
                (cf === 'all' || p.dataset.cat === cf) &&
                p.dataset.search.includes(s)
            )
                ? ''
                : 'none';
    });
}


q.oninput = apply;


document.getElementById('productSearch').onsubmit = e => {

    e.preventDefault();

    apply();

    document
        .getElementById('top')
        .scrollIntoView({
            behavior: 'smooth'
        });

    q.focus();
};


fs.forEach(b => {

    b.onclick = () => {

        fs.forEach(x => {
            x.classList.remove('active');
        });

        b.classList.add('active');

        cf = b.dataset.filter;

        apply();
    };
});


document.querySelectorAll('.cat').forEach(b => {

    b.onclick = () => {

        cf = b.dataset.cat;

        document
            .getElementById('top')
            .scrollIntoView();

        apply();
    };
});

</script>

</body>

</html>