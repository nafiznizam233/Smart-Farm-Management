<?php

require __DIR__ . "/../../config/database.php";
require __DIR__ . "/../../config/auth.php";

require_role(['owner', 'manager']);


$rows = $pdo->query(
    "SELECT
        fs.*,
        f.name farm_name,
        p.total_amount
     FROM farm_stocking fs
     JOIN farms f
        ON f.id=fs.farm_id
     LEFT JOIN purchases p
        ON p.id=fs.purchase_id
     ORDER BY fs.id DESC"
)->fetchAll();


$page_title = 'Livestock & Stocking';

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
                    Livestock / Poultry / Fish Stocking
                </h2>

                <p class="mb-0">
                    Automatically created from relevant purchases.
                </p>

            </div>


            <div class="cardx p-3">

                <table class="table">

                    <thead>

                        <tr>
                            <th>Date</th>
                            <th>Farm</th>
                            <th>Type</th>
                            <th>Species/Item</th>
                            <th>Quantity</th>
                            <th>Purchase Cost</th>
                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($rows as $r): ?>

                            <tr>

                                <td>
                                    <?= $r['stock_date'] ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars($r['farm_name']) ?>
                                </td>


                                <td>
                                    <?= $r['stock_type'] ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars($r['species']) ?>
                                </td>


                                <td>
                                    <?= $r['quantity'] . ' ' . $r['unit'] ?>
                                </td>


                                <td>
                                    ৳<?= number_format(
                                        $r['total_amount'] ?? 0,
                                        2
                                    ) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>