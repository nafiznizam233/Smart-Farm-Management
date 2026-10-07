<?php

require __DIR__ . '/config/database.php';
require __DIR__ . '/config/app.php';


$ok = false;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $pdo->prepare("
        INSERT INTO marketplace_seller_applications (
            farm_name,
            applicant_name,
            phone,
            email,
            address,
            product_types,
            description,
            nid_or_trade
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ")->execute([
        trim($_POST['farm']),
        trim($_POST['name']),
        trim($_POST['phone']),
        trim($_POST['email']),
        trim($_POST['address']),
        trim($_POST['products']),
        trim($_POST['description']),
        trim($_POST['idno'])
    ]);


    $ok = true;
}

?>

<!doctype html>

<html>

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width"
    >

    <title>
        Sell With Us
    </title>

    <link
        rel="stylesheet"
        href="<?= asset_path('vendor/bootstrap.min.css') ?>"
    >

    <link
        rel="stylesheet"
        href="<?= asset_path('css/style.css') ?>"
    >

</head>


<body>


<div
    class="container py-5"
    style="max-width:800px"
>

    <a href="<?= url_path() ?>">
        ← Marketplace
    </a>


    <div class="cardx p-4 mt-3">

        <h2>
            Sell Through Smart Farm
        </h2>

        <p>
            Small farms can apply to supply products through our marketplace.
            Customers continue to buy through Smart Farm.
        </p>


        <?php if ($ok): ?>

            <div class="alert alert-success">
                Application submitted for review.
            </div>

        <?php endif; ?>


        <form
            method="post"
            class="row g-3"
        >

            <div class="col-md-6">

                <input
                    class="form-control"
                    name="farm"
                    placeholder="Farm name"
                    required
                >

            </div>


            <div class="col-md-6">

                <input
                    class="form-control"
                    name="name"
                    placeholder="Applicant name"
                    required
                >

            </div>


            <div class="col-md-6">

                <input
                    class="form-control"
                    name="phone"
                    placeholder="Mobile"
                    required
                >

            </div>


            <div class="col-md-6">

                <input
                    class="form-control"
                    type="email"
                    name="email"
                    placeholder="Email"
                >

            </div>


            <div class="col-12">

                <input
                    class="form-control"
                    name="address"
                    placeholder="Farm address"
                    required
                >

            </div>


            <div class="col-12">

                <input
                    class="form-control"
                    name="products"
                    placeholder="Milk, fish, rice, vegetables..."
                    required
                >

            </div>


            <div class="col-md-6">

                <input
                    class="form-control"
                    name="idno"
                    placeholder="NID / Trade / ID number"
                >

            </div>


            <div class="col-12">

                <textarea
                    class="form-control"
                    name="description"
                    placeholder="Tell us about the farm"
                ></textarea>

            </div>


            <div class="col-12">

                <button class="btn btn-success">
                    Apply
                </button>

            </div>

        </form>

    </div>

</div>


</body>

</html>