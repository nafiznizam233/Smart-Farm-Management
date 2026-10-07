<?php

require __DIR__ . "/../../config/database.php";
require __DIR__ . "/../../config/auth.php";

require_role(['owner', 'manager']);


$id = (int) ($_GET['id'] ?? 0);


$st = $pdo->prepare(
    "SELECT *
     FROM farms
     WHERE id=?"
);

$st->execute([$id]);

$farm = $st->fetch();


if (!$farm) {
    die("Farm not found");
}


$st = $pdo->prepare(
    "SELECT COALESCE(SUM(amount),0)
     FROM income
     WHERE farm_id=?"
);

$st->execute([$id]);

$income = (float) $st->fetchColumn();


$st = $pdo->prepare(
    "SELECT COALESCE(SUM(amount),0)
     FROM expenses
     WHERE farm_id=?"
);

$st->execute([$id]);

$expense = (float) $st->fetchColumn();


$profit = $income - $expense;


$page_title = "Farm Details";

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
                    <?= htmlspecialchars($farm['name']) ?>
                </h2>

                <p class="mb-0">

                    <?= htmlspecialchars($farm['category']) ?>

                    ·

                    <?= htmlspecialchars($farm['location']) ?>

                </p>

            </div>


            <div class="row g-3">

                <div class="col-md-5">

                    <div class="cardx p-3">

                        <?php if ($farm['image']): ?>

                            <img
                                class="w-100 rounded"
                                style="height:280px;object-fit:cover"
                                src="/smart_farm_php_v7/uploads/farms/<?= htmlspecialchars($farm['image']) ?>"
                            >

                        <?php else: ?>

                            <div
                                style="
                                    height:280px;
                                    display:flex;
                                    align-items:center;
                                    justify-content:center;
                                    font-size:90px;
                                    background:#eaf6ee
                                "
                            >
                                🌾
                            </div>

                        <?php endif; ?>

                    </div>

                </div>


                <div class="col-md-7">

                    <div class="row g-3">

                        <div class="col-md-4">

                            <div class="cardx p-3">

                                <div class="small-muted">
                                    Revenue
                                </div>

                                <div class="metric">
                                    ৳<?= number_format($income, 2) ?>
                                </div>

                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="cardx p-3">

                                <div class="small-muted">
                                    Cost
                                </div>

                                <div class="metric">
                                    ৳<?= number_format($expense, 2) ?>
                                </div>

                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="cardx p-3">

                                <div class="small-muted">
                                    Profit
                                </div>

                                <div class="metric">
                                    ৳<?= number_format($profit, 2) ?>
                                </div>

                            </div>

                        </div>


                        <div class="col-12">

                            <div class="cardx p-3">

                                <h5>
                                    Quick Actions
                                </h5>


                                <a
                                    class="btn btn-success me-2"
                                    href="../income/index.php?farm_id=<?= $farm['id'] ?>"
                                >
                                    Add Income
                                </a>


                                <a
                                    class="btn btn-danger me-2"
                                    href="../expenses/index.php?farm_id=<?= $farm['id'] ?>"
                                >
                                    Add Expense
                                </a>


                                <a
                                    class="btn btn-outline-primary"
                                    href="../reports/index.php?farm_id=<?= $farm['id'] ?>"
                                >
                                    View Report
                                </a>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>