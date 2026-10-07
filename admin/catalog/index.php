<?php

require __DIR__ . "/../../config/database.php";
require __DIR__ . "/../../config/auth.php";

require_role(['owner']);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $pdo->prepare(
        "INSERT INTO purchase_catalog(
            farm_category,
            purchase_type,
            item_name,
            default_unit,
            stockable,
            status
        )
        VALUES(?,?,?,?,?,'active')"
    )->execute([
        trim($_POST['farm_category']),
        trim($_POST['purchase_type']),
        trim($_POST['item_name']),
        trim($_POST['default_unit']),
        isset($_POST['stockable']) ? 1 : 0
    ]);

    header('Location:index.php');
    exit;
}


$rows = $pdo->query(
    "SELECT *
     FROM purchase_catalog
     ORDER BY farm_category, purchase_type, item_name"
)->fetchAll();


$page_title = 'Purchase Catalog';

require __DIR__ . "/../../partials/header.php";

?>


<div class="container-fluid">

    <div class="row">

        <?php
        require __DIR__ . "/../../partials/admin_sidebar.php";
        ?>


        <div class="col-md-10 p-4">

            <div class="hero-top mb-4">

                <h2>
                    Farm-wise Purchase Catalog
                </h2>

                <p class="mb-0">
                    Only relevant items appear after a farm category is selected.
                </p>

            </div>


            <div class="cardx p-3 mb-4">

                <form
                    method="post"
                    class="row g-2"
                >

                    <div class="col-md-2">

                        <select
                            class="form-select"
                            name="farm_category"
                        >

                            <option>Cow</option>
                            <option>Fish</option>
                            <option>Poultry</option>
                            <option>Rice</option>

                        </select>

                    </div>


                    <div class="col-md-2">

                        <input
                            class="form-control"
                            name="purchase_type"
                            placeholder="Type"
                            required
                        >

                    </div>


                    <div class="col-md-3">

                        <input
                            class="form-control"
                            name="item_name"
                            placeholder="Relevant item"
                            required
                        >

                    </div>


                    <div class="col-md-2">

                        <input
                            class="form-control"
                            name="default_unit"
                            value="KG"
                            required
                        >

                    </div>


                    <div class="col-md-1 form-check mt-3">

                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="stockable"
                            checked
                        >

                        Stock

                    </div>


                    <div class="col-md-2">

                        <button class="btn btn-success w-100">
                            Add Item
                        </button>

                    </div>

                </form>

            </div>


            <div class="cardx p-3">

                <table class="table">

                    <thead>

                        <tr>
                            <th>Farm</th>
                            <th>Type</th>
                            <th>Item</th>
                            <th>Unit</th>
                            <th>Stockable</th>
                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($rows as $r): ?>

                            <tr>

                                <td>
                                    <?= $r['farm_category'] ?>
                                </td>

                                <td>
                                    <?= $r['purchase_type'] ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $r['item_name']
                                    ) ?>
                                </td>

                                <td>
                                    <?= $r['default_unit'] ?>
                                </td>

                                <td>
                                    <?= $r['stockable'] ? 'Yes' : 'No' ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>