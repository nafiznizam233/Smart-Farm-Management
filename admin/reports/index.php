<?php

require __DIR__ . "/../../config/database.php";
require __DIR__ . "/../../config/auth.php";

require_role(['owner', 'manager']);


$from = $_GET['from'] ?? date('Y-m-01');

$to = $_GET['to'] ?? date('Y-m-t');

$farmFilter = (int) ($_GET['farm_id'] ?? 0);


$paramsIncome = [
    $from,
    $to
];

$paramsExpense = [
    $from,
    $to
];


$farmSql = "";


if ($farmFilter > 0) {

    $farmSql = " AND farm_id=? ";

    $paramsIncome[] = $farmFilter;

    $paramsExpense[] = $farmFilter;
}


$st = $pdo->prepare(
    "SELECT COALESCE(SUM(amount),0)
     FROM income
     WHERE income_date BETWEEN ? AND ?
     $farmSql"
);

$st->execute($paramsIncome);

$totalIncome = (float) $st->fetchColumn();


$st = $pdo->prepare(
    "SELECT COALESCE(SUM(amount),0)
     FROM expenses
     WHERE expense_date BETWEEN ? AND ?
     $farmSql"
);

$st->execute($paramsExpense);

$totalExpense = (float) $st->fetchColumn();


$salary = 0;


if ($farmFilter === 0) {

    $st = $pdo->prepare(
        "SELECT COALESCE(SUM(amount),0)
         FROM staff_payments
         WHERE status IN ('approved','paid')
         AND DATE(paid_at) BETWEEN ? AND ?"
    );

    $st->execute([
        $from,
        $to
    ]);

    $salary = (float) $st->fetchColumn();
}


$totalCost = $totalExpense + $salary;

$net = $totalIncome - $totalCost;


$farms = $pdo->query(
    "SELECT
        id,
        name
     FROM farms
     WHERE status='active'
     ORDER BY name"
)->fetchAll();


$st = $pdo->prepare(
    "SELECT
        f.id,
        f.name,
        f.category,

        COALESCE(
            (
                SELECT SUM(i.amount)
                FROM income i
                WHERE i.farm_id=f.id
                AND i.income_date BETWEEN ? AND ?
            ),
            0
        ) revenue,

        COALESCE(
            (
                SELECT SUM(e.amount)
                FROM expenses e
                WHERE e.farm_id=f.id
                AND e.expense_date BETWEEN ? AND ?
            ),
            0
        ) cost

     FROM farms f

     WHERE f.status='active'

     ORDER BY f.name"
);


$st->execute([
    $from,
    $to,
    $from,
    $to
]);


$reportRows = $st->fetchAll();


$page_title = "Reports";

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
                    <i class="fa-solid fa-chart-column me-2"></i>
                    Reports
                </h2>

                <p class="mb-0">
                    Farm-wise and overall financial report with date filtering.
                </p>

            </div>


            <div class="cardx p-3 mb-4">

                <form
                    method="get"
                    class="row g-2"
                >

                    <div class="col-md-3">

                        <label class="form-label">
                            From
                        </label>

                        <input
                            class="form-control"
                            type="date"
                            name="from"
                            value="<?= htmlspecialchars($from) ?>"
                        >

                    </div>


                    <div class="col-md-3">

                        <label class="form-label">
                            To
                        </label>

                        <input
                            class="form-control"
                            type="date"
                            name="to"
                            value="<?= htmlspecialchars($to) ?>"
                        >

                    </div>


                    <div class="col-md-4">

                        <label class="form-label">
                            Farm
                        </label>

                        <select
                            class="form-select"
                            name="farm_id"
                        >

                            <option value="0">
                                All Farms
                            </option>


                            <?php foreach ($farms as $f): ?>

                                <option
                                    value="<?= $f['id'] ?>"
                                    <?= $farmFilter === $f['id']
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    <?= htmlspecialchars($f['name']) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="col-md-2 d-flex align-items-end">

                        <button class="btn btn-success w-100">
                            Apply Filter
                        </button>

                    </div>

                </form>

            </div>


            <div class="row g-3 mb-4">

                <div class="col-md-4">

                    <div class="cardx report-card p-3">

                        <div class="small-muted">
                            Revenue
                        </div>

                        <div class="metric text-success">
                            ৳<?= number_format($totalIncome, 2) ?>
                        </div>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="cardx report-card p-3">

                        <div class="small-muted">
                            Total Cost
                        </div>

                        <div class="metric text-danger">
                            ৳<?= number_format($totalCost, 2) ?>
                        </div>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="cardx report-card p-3">

                        <div class="small-muted">
                            Net Profit
                        </div>

                        <div
                            class="metric <?= $net >= 0
                                ? 'text-success'
                                : 'text-danger'
                            ?>"
                        >
                            ৳<?= number_format($net, 2) ?>
                        </div>

                    </div>

                </div>

            </div>


            <div class="cardx p-3">

                <h5>
                    Farm-wise Summary
                </h5>


                <table class="table">

                    <thead>

                        <tr>
                            <th>Farm</th>
                            <th>Category</th>
                            <th>Revenue</th>
                            <th>Cost</th>
                            <th>Profit</th>
                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($reportRows as $r): ?>

                            <?php

                            $profit =
                                (float) $r['revenue'] -
                                (float) $r['cost'];


                            if (
                                $farmFilter > 0 &&
                                $farmFilter !== (int) $r['id']
                            ) {
                                continue;
                            }

                            ?>


                            <tr>

                                <td>
                                    <?= htmlspecialchars($r['name']) ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars($r['category']) ?>
                                </td>


                                <td>
                                    ৳<?= number_format(
                                        $r['revenue'],
                                        2
                                    ) ?>
                                </td>


                                <td>
                                    ৳<?= number_format(
                                        $r['cost'],
                                        2
                                    ) ?>
                                </td>


                                <td
                                    class="<?= $profit >= 0
                                        ? 'text-success'
                                        : 'text-danger'
                                    ?> fw-bold"
                                >
                                    ৳<?= number_format(
                                        $profit,
                                        2
                                    ) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>


                <?php if ($farmFilter === 0): ?>

                    <div class="small-muted">
                        Staff salary paid in this date range is included in Total Cost above, but not assigned to a specific farm.
                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>

</div>