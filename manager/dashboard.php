<?php

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';

require_role(['manager']);

$fid = manager_farm_id($pdo);
$uid = (int) $_SESSION['user']['id'];

$page_title = 'Farm Manager';

require __DIR__ . '/../partials/header.php';


if (!$fid) {
    ?>
    <div class="app-shell">

        <?php require __DIR__ . '/../partials/manager_sidebar.php'; ?>

        <main class="app-main">

            <div
                class="cardx p-5 text-center"
                style="max-width:760px; margin:50px auto"
            >
                <img
                    src="<?= asset_path('logo.png') ?>"
                    style="
                        width:78px;
                        height:78px;
                        border-radius:50%;
                        object-fit:cover;
                    "
                >

                <h2 class="mt-3">
                    Manager Account Ready
                </h2>

                <p class="text-muted">
                    Your login is working, but the Owner has not assigned
                    a farm to this Manager account yet.
                </p>

                <div class="alert alert-info">
                    Ask the Owner to open
                    <b>Owner Dashboard → Managers</b>,
                    assign a farm and save your access permissions.
                    Then refresh this page.
                </div>

                <a
                    class="btn btn-outline-secondary"
                    href="<?= url_path('logout.php') ?>"
                >
                    Logout
                </a>
            </div>

        </main>
    </div>

    <?php
    exit;
}


$q = $pdo->prepare("
    SELECT *
    FROM farms
    WHERE id = ?
      AND status = 'active'
");

$q->execute([$fid]);

$farm = $q->fetch();


if (!$farm) {
    ?>
    <div class="app-shell">

        <?php require __DIR__ . '/../partials/manager_sidebar.php'; ?>

        <main class="app-main">
            <div class="alert alert-warning m-4">
                The assigned farm is inactive or unavailable.
                Please contact the Owner.
            </div>
        </main>

    </div>

    <?php
    exit;
}


function ms($pdo, $sql, $p = [])
{
    $s = $pdo->prepare($sql);
    $s->execute($p);

    return $s->fetchColumn();
}


$workers = (int) ms(
    $pdo,
    "
        SELECT COUNT(*)
        FROM farm_workers
        WHERE farm_id = ?
          AND status = 'active'
    ",
    [$fid]
);


$present = (int) ms(
    $pdo,
    "
        SELECT COUNT(*)
        FROM farm_worker_attendance a
        JOIN farm_workers w
            ON w.id = a.worker_id
        WHERE w.farm_id = ?
          AND a.attendance_date = CURDATE()
          AND a.status = 'present'
    ",
    [$fid]
);


$stores = (int) ms(
    $pdo,
    "
        SELECT COUNT(*)
        FROM stores
        WHERE farm_id = ?
          AND status = 'active'
    ",
    [$fid]
);


$expense = (float) ms(
    $pdo,
    "
        SELECT COALESCE(SUM(amount), 0)
        FROM expenses
        WHERE farm_id = ?
          AND expense_date = CURDATE()
    ",
    [$fid]
);


$income = (float) ms(
    $pdo,
    "
        SELECT COALESCE(SUM(amount), 0)
        FROM income
        WHERE farm_id = ?
          AND income_date = CURDATE()
    ",
    [$fid]
);

?>

<div class="app-shell">

    <?php require __DIR__ . '/../partials/manager_sidebar.php'; ?>

    <main class="app-main">

        <div class="hero-top mb-4">

            <h2>
                <?= htmlspecialchars($farm['name']) ?>
            </h2>

            <p>
                <?= htmlspecialchars($farm['category']) ?>
                · Manager:
                <?= htmlspecialchars($_SESSION['user']['name']) ?>
            </p>

        </div>


        <div class="row g-3 mb-4">

            <?php
            foreach (
                [
                    ['Active Workers', $workers],
                    ['Present Today', $present],
                    ['Stores', $stores],
                    ["Today's Income", '৳' . number_format($income)],
                    ["Today's Expense", '৳' . number_format($expense)]
                ] as $c
            ):
                ?>

                <div class="col-md">
                    <div class="cardx p-3">

                        <div class="kpi-label">
                            <?= $c[0] ?>
                        </div>

                        <div class="metric">
                            <?= $c[1] ?>
                        </div>

                    </div>
                </div>

            <?php endforeach; ?>

        </div>


        <div class="smart-shortcuts">

            <a
                class="smart-tile"
                href="workers.php"
            >
                <b>Farm Workers</b>
                <span>Worker records</span>
            </a>

            <a
                class="smart-tile"
                href="worker_attendance.php"
            >
                <b>Attendance</b>
                <span>Daily attendance</span>
            </a>

            <a
                class="smart-tile"
                href="worker_payroll.php"
            >
                <b>Salary</b>
                <span>Daily/monthly payment</span>
            </a>

            <a
                class="smart-tile"
                href="preorders.php"
            >
                <b>Pre-order</b>
                <span>Pickup capacity</span>
            </a>

            <a
                class="smart-tile"
                href="shop/index.php"
            >
                <b>Products</b>
                <span>Farm stock and pricing</span>
            </a>

        </div>

    </main>

</div>