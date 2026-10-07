<?php

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';

require_role(['owner']);


$from = $_GET['from'] ?? date('Y-m-01');

$to = $_GET['to'] ?? date('Y-m-d');


function scalar($pdo, $sql, $params = [])
{
    $st = $pdo->prepare($sql);

    $st->execute($params);

    return $st->fetchColumn();
}


$totalStaff = (int) scalar(
    $pdo,
    "SELECT COUNT(*)
     FROM staff
     WHERE status='active'"
);


$farmWorkers = (int) scalar(
    $pdo,
    "SELECT COUNT(*)
     FROM farm_workers
     WHERE status='active'"
);


$farmPresent = (int) scalar(
    $pdo,
    "SELECT COUNT(*)
     FROM farm_worker_attendance a
     JOIN farm_workers w
        ON w.id=a.worker_id
     WHERE a.attendance_date=CURDATE()
     AND a.status='present'
     AND w.status='active'"
);


$verified = (int) scalar(
    $pdo,
    "SELECT COUNT(*)
     FROM staff s
     JOIN users u
        ON u.id=s.user_id
     WHERE s.status='active'
     AND u.verification_status='verified'"
);


$unverified = $totalStaff - $verified;


$present = (int) scalar(
    $pdo,
    "SELECT COUNT(*)
     FROM work_attendance wa
     JOIN users u
        ON u.id=wa.user_id
     WHERE u.role='staff'
     AND wa.work_date=CURDATE()
     AND wa.check_in IS NOT NULL"
);


$managerActive = (int) scalar(
    $pdo,
    "SELECT COUNT(*)
     FROM work_attendance wa
     JOIN users u
        ON u.id=wa.user_id
     WHERE u.role='manager'
     AND wa.work_date=CURDATE()
     AND wa.check_in IS NOT NULL
     AND wa.check_out IS NULL"
);


$todayIncome = (float) scalar(
    $pdo,
    "SELECT COALESCE(SUM(amount),0)
     FROM income
     WHERE income_date=CURDATE()"
);


$todayExpense = (float) scalar(
    $pdo,
    "SELECT COALESCE(SUM(amount),0)
     FROM expenses
     WHERE expense_date=CURDATE()"
);


$monthlyExpense = (float) scalar(
    $pdo,
    "SELECT COALESCE(SUM(amount),0)
     FROM expenses
     WHERE YEAR(expense_date)=YEAR(CURDATE())
     AND MONTH(expense_date)=MONTH(CURDATE())"
);


$rangeIncome = (float) scalar(
    $pdo,
    "SELECT COALESCE(SUM(amount),0)
     FROM income
     WHERE income_date BETWEEN ? AND ?",
    [
        $from,
        $to
    ]
);


$rangeExpense = (float) scalar(
    $pdo,
    "SELECT COALESCE(SUM(amount),0)
     FROM expenses
     WHERE expense_date BETWEEN ? AND ?",
    [
        $from,
        $to
    ]
);


$balances = $pdo->query(
    "SELECT
        account_type,
        COALESCE(SUM(balance),0) total
     FROM accounts
     WHERE status='active'
     GROUP BY account_type"
)->fetchAll(PDO::FETCH_KEY_PAIR);


$totalBalance = array_sum(
    array_map(
        'floatval',
        $balances
    )
);


$pending = (int) scalar(
    $pdo,
    "SELECT
        (SELECT COUNT(*) FROM purchases WHERE status='pending')
        +
        (SELECT COUNT(*) FROM manager_cash_requests WHERE status='pending')
        +
        (SELECT COUNT(*) FROM expense_claims WHERE status='pending')
        +
        (SELECT COUNT(*) FROM staff_payments WHERE status='pending')
        +
        (SELECT COUNT(*) FROM manager_salary_requests WHERE status='pending')"
);


$st = $pdo->prepare(
    "SELECT
        d.day,
        COALESCE(i.inc,0) income,
        COALESCE(e.exp,0) expense

     FROM (
        SELECT income_date day
        FROM income
        WHERE income_date BETWEEN ? AND ?

        UNION

        SELECT expense_date day
        FROM expenses
        WHERE expense_date BETWEEN ? AND ?
     ) d

     LEFT JOIN (
        SELECT
            income_date day,
            SUM(amount) inc
        FROM income
        WHERE income_date BETWEEN ? AND ?
        GROUP BY income_date
     ) i
        ON i.day=d.day

     LEFT JOIN (
        SELECT
            expense_date day,
            SUM(amount) exp
        FROM expenses
        WHERE expense_date BETWEEN ? AND ?
        GROUP BY expense_date
     ) e
        ON e.day=d.day

     ORDER BY d.day"
);


$st->execute([
    $from,
    $to,
    $from,
    $to,
    $from,
    $to,
    $from,
    $to
]);


$chart = $st->fetchAll();


$active = $pdo->query(
    "SELECT
        u.name,
        u.role,
        u.profile_image,
        wa.check_in

     FROM work_attendance wa

     JOIN users u
        ON u.id=wa.user_id

     WHERE wa.work_date=CURDATE()
     AND wa.check_in IS NOT NULL
     AND wa.check_out IS NULL
     AND u.role IN(
        'manager',
        'staff'
     )

     ORDER BY wa.check_in DESC

     LIMIT 10"
)->fetchAll();


$page_title = 'Owner Dashboard';

require __DIR__ . '/../partials/header.php';

?>


<div class="app-shell">

    <?php
    require __DIR__ . '/../partials/admin_sidebar.php';
    ?>


    <main class="app-main">

        <div class="hero-top mb-4">

            <div class="d-flex justify-content-between align-items-center">

                <div>

                    <h2>
                        Owner Dashboard
                    </h2>

                    <p class="mb-0">
                        A single view of staff, finance, approvals and farm activity.
                    </p>

                </div>


                <img
                    src="<?= asset_path('logo.png') ?>"
                    style="width:76px;height:76px;border-radius:50%;object-fit:cover;background:#fff"
                    alt="Logo"
                >

            </div>

        </div>


        <div class="row g-3 mb-3">

            <?php

            $cards = [
                [
                    'Farm Workers',
                    $farmWorkers,
                    '🧑‍🌾'
                ],
                [
                    'Farm Present Today',
                    $farmPresent,
                    '✅'
                ],
                [
                    'Delivery/Legacy Staff',
                    $totalStaff,
                    '🚚'
                ],
                [
                    'Present Today',
                    $present,
                    '✅'
                ],
                [
                    'Verified Staff',
                    $verified,
                    '🪪'
                ],
                [
                    'Unverified Staff',
                    $unverified,
                    '⚠️'
                ],
                [
                    'Managers Active',
                    $managerActive,
                    '👔'
                ],
                [
                    'Pending Approvals',
                    $pending,
                    '⏳'
                ],
                [
                    "Today's Income",
                    '৳' . number_format($todayIncome, 0),
                    '↗️'
                ],
                [
                    "Today's Expenses",
                    '৳' . number_format($todayExpense, 0),
                    '↘️'
                ],
                [
                    'Monthly Expenses',
                    '৳' . number_format($monthlyExpense, 0),
                    '📅'
                ],
                [
                    'Total Balance',
                    '৳' . number_format($totalBalance, 0),
                    '💰'
                ]
            ];

            ?>


            <?php foreach ($cards as $c): ?>

                <div class="col-6 col-md-4 col-xl">

                    <div class="cardx p-3 h-100">

                        <div class="d-flex justify-content-between">

                            <div>

                                <div class="kpi-label">
                                    <?= $c[0] ?>
                                </div>

                                <div class="metric">
                                    <?= $c[1] ?>
                                </div>

                            </div>


                            <div class="kpi-icon">
                                <?= $c[2] ?>
                            </div>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>


        <div class="cardx p-3 mb-4">

            <form class="row g-2 align-items-end no-print">

                <div class="col-md-3">

                    <label class="small-muted">
                        From Date
                    </label>

                    <input
                        class="form-control"
                        type="date"
                        name="from"
                        value="<?= htmlspecialchars($from) ?>"
                    >

                </div>


                <div class="col-md-3">

                    <label class="small-muted">
                        To Date
                    </label>

                    <input
                        class="form-control"
                        type="date"
                        name="to"
                        value="<?= htmlspecialchars($to) ?>"
                    >

                </div>


                <div class="col-md-2">

                    <button class="btn btn-success w-100">
                        Update Chart
                    </button>

                </div>


                <div class="col-md-2">

                    <a
                        class="btn btn-outline-success w-100"
                        href="<?= url_path(
                            'admin/reports/financial_export.php?from=' .
                            urlencode($from) .
                            '&to=' .
                            urlencode($to)
                        ) ?>"
                    >
                        Download CSV
                    </a>

                </div>


                <div class="col-md-2">

                    <button
                        type="button"
                        class="btn btn-outline-dark w-100"
                        onclick="window.print()"
                    >
                        Print
                    </button>

                </div>

            </form>


            <div class="row mt-3">

                <div class="col-md-4">

                    <div class="small-muted">
                        Range Income
                    </div>

                    <div class="metric">
                        ৳<?= number_format($rangeIncome, 0) ?>
                    </div>

                </div>


                <div class="col-md-4">

                    <div class="small-muted">
                        Range Expense
                    </div>

                    <div class="metric">
                        ৳<?= number_format($rangeExpense, 0) ?>
                    </div>

                </div>


                <div class="col-md-4">

                    <div class="small-muted">
                        Net
                    </div>

                    <div
                        class="metric <?= ($rangeIncome - $rangeExpense) >= 0
                            ? 'status-good'
                            : 'status-bad'
                        ?>"
                    >
                        ৳<?= number_format(
                            $rangeIncome - $rangeExpense,
                            0
                        ) ?>
                    </div>

                </div>

            </div>


            <div class="chart-box mt-3">

                <canvas id="financeChart"></canvas>

            </div>

        </div>


        <div class="row g-3">

            <div class="col-lg-7">

                <div class="cardx p-3">

                    <h5>
                        Currently Active
                    </h5>


                    <div class="table-wrap">

                        <table class="table">

                            <thead>

                                <tr>
                                    <th>User</th>
                                    <th>Role</th>
                                    <th>Checked In</th>
                                    <th>Status</th>
                                </tr>

                            </thead>


                            <tbody>

                                <?php foreach ($active as $a): ?>

                                    <tr>

                                        <td>
                                            <b>
                                                <?= htmlspecialchars($a['name']) ?>
                                            </b>
                                        </td>


                                        <td>
                                            <?= ucfirst($a['role']) ?>
                                        </td>


                                        <td>
                                            <?= date(
                                                'h:i A',
                                                strtotime($a['check_in'])
                                            ) ?>
                                        </td>


                                        <td>
                                            <span class="live-dot"></span>
                                            Active
                                        </td>

                                    </tr>

                                <?php endforeach; ?>


                                <?php if (!$active): ?>

                                    <tr>

                                        <td
                                            colspan="4"
                                            class="text-muted"
                                        >
                                            No manager or staff currently checked in.
                                        </td>

                                    </tr>

                                <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>


            <div class="col-lg-5">

                <div class="cardx p-3">

                    <h5>
                        Account Balance
                    </h5>


                    <div class="py-2 border-bottom">

                        Cash

                        <b class="float-end">
                            ৳<?= number_format(
                                $balances['cash'] ?? 0,
                                0
                            ) ?>
                        </b>

                    </div>


                    <div class="py-2 border-bottom">

                        Bank

                        <b class="float-end">
                            ৳<?= number_format(
                                $balances['bank'] ?? 0,
                                0
                            ) ?>
                        </b>

                    </div>


                    <div class="py-2">

                        MFS

                        <b class="float-end">
                            ৳<?= number_format(
                                $balances['mfs'] ?? 0,
                                0
                            ) ?>
                        </b>

                    </div>


                    <a
                        class="btn btn-outline-success w-100 mt-3"
                        href="<?= url_path('admin/accounts/index.php') ?>"
                    >
                        Open Balance
                    </a>

                </div>

            </div>

        </div>


        <div class="cardx p-3 mb-4">

            <a
                class="btn btn-success"
                href="<?= url_path('admin/workforce/index.php') ?>"
            >
                Open Farm Workforce & Attendance
            </a>


            <a
                class="btn btn-outline-success"
                href="<?= url_path('admin/deliveries/index.php') ?>"
            >
                Delivery Control
            </a>

        </div>


        <script>

            const rows = <?= json_encode(
                $chart,
                JSON_UNESCAPED_UNICODE
            ) ?>;


            const c = document.getElementById('financeChart');

            const x = c.getContext('2d');


            function draw() {

                const r = c.getBoundingClientRect();

                c.width = r.width * devicePixelRatio;

                c.height = r.height * devicePixelRatio;


                x.scale(
                    devicePixelRatio,
                    devicePixelRatio
                );


                const w = r.width;

                const h = r.height;

                const p = 38;


                const max = Math.max(
                    1,
                    ...rows.flatMap(
                        v => [
                            +v.income,
                            +v.expense
                        ]
                    )
                );


                x.clearRect(
                    0,
                    0,
                    w,
                    h
                );


                x.strokeStyle = '#dfe9e3';


                for (let i = 0; i < 5; i++) {

                    let y =
                        p +
                        (h - p * 2) *
                        i /
                        4;


                    x.beginPath();

                    x.moveTo(
                        p,
                        y
                    );

                    x.lineTo(
                        w - p,
                        y
                    );

                    x.stroke();
                }


                function line(key, color) {

                    x.strokeStyle = color;

                    x.lineWidth = 3;

                    x.beginPath();


                    rows.forEach(
                        (v, i) => {

                            let xx =
                                p +
                                (
                                    rows.length <= 1
                                        ? 0
                                        : (w - p * 2) *
                                          i /
                                          (rows.length - 1)
                                );


                            let yy =
                                h -
                                p -
                                (
                                    +v[key] /
                                    max
                                ) *
                                (h - p * 2);


                            if (i) {

                                x.lineTo(
                                    xx,
                                    yy
                                );

                            } else {

                                x.moveTo(
                                    xx,
                                    yy
                                );
                            }
                        }
                    );


                    x.stroke();
                }


                line(
                    'income',
                    '#16865b'
                );


                line(
                    'expense',
                    '#c85050'
                );


                x.fillStyle = '#16865b';

                x.fillText(
                    'Income',
                    p,
                    16
                );


                x.fillStyle = '#c85050';

                x.fillText(
                    'Expense',
                    p + 70,
                    16
                );
            }


            draw();


            addEventListener(
                'resize',
                draw
            );

        </script>

    </main>

</div>