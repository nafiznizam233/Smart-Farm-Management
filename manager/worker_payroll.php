<?php

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';

$fid = require_manager_farm($pdo);
$uid = $_SESSION['user']['id'];

$month = $_GET['month'] ?? date('Y-m');


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $q = $pdo->prepare("
        SELECT *
        FROM farm_workers
        WHERE id = ?
          AND farm_id = ?
    ");

    $q->execute([
        (int) $_POST['worker_id'],
        $fid
    ]);

    $x = $q->fetch();


    if ($x) {

        $days = (float) $_POST['days'];

        $amt = $x['pay_type'] === 'daily'
            ? $days * $x['pay_rate']
            : $x['pay_rate'];


        $pdo->prepare("
            INSERT INTO farm_worker_payments (
                worker_id,
                farm_id,
                pay_period,
                days_count,
                amount,
                payment_method,
                reference_no,
                paid_by
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $x['id'],
            $fid,
            $month,
            $days,
            $amt,
            $_POST['method'],
            trim($_POST['ref']),
            $uid
        ]);


        $pdo->prepare("
            INSERT INTO expenses (
                farm_id,
                category,
                amount,
                expense_date,
                note,
                approved_by,
                approved_at
            )
            VALUES (
                ?,
                'Worker Salary',
                ?,
                CURDATE(),
                ?,
                ?,
                NOW()
            )
        ")->execute([
            $fid,
            $amt,
            'Salary: ' . $x['name'] . ' ' . $month,
            $uid
        ]);
    }


    header(
        'Location:worker_payroll.php?month=' . $month
    );

    exit;
}


$q = $pdo->prepare("
    SELECT
        w.*,
        SUM(
            CASE
                WHEN a.status = 'present'
                THEN 1
                ELSE 0
            END
        ) days

    FROM farm_workers w

    LEFT JOIN farm_worker_attendance a
        ON a.worker_id = w.id
       AND DATE_FORMAT(a.attendance_date, '%Y-%m') = ?

    WHERE w.farm_id = ?
      AND w.status = 'active'

    GROUP BY w.id
");

$q->execute([
    $month,
    $fid
]);

$rows = $q->fetchAll();


$page_title = 'Worker Salary';

require __DIR__ . '/../partials/header.php';

?>

<div class="app-shell">

    <?php require __DIR__ . '/../partials/manager_sidebar.php'; ?>

    <main class="app-main">

        <h2>
            Worker Salary
        </h2>

        <p>
            Daily pay uses payable days; monthly pay uses the monthly rate.
            Payment is added to farm expense for Owner reporting.
        </p>


        <form>

            <input
                type="month"
                name="month"
                value="<?= $month ?>"
            >

            <button class="btn btn-outline-success">
                Open
            </button>

        </form>


        <div class="cardx p-3 mt-3">

            <table class="table">

                <tr>
                    <th>Worker</th>
                    <th>Type / Rate</th>
                    <th>Present</th>
                    <th>Payment</th>
                </tr>


                <?php foreach ($rows as $r): ?>

                    <tr>

                        <td>
                            <?= htmlspecialchars($r['name']) ?>
                        </td>

                        <td>
                            <?= $r['pay_type'] ?>
                            ·
                            ৳<?= $r['pay_rate'] ?>
                        </td>

                        <td>
                            <?= $r['days'] ?>
                        </td>

                        <td>

                            <form
                                method="post"
                                class="d-flex gap-1"
                            >

                                <input
                                    type="hidden"
                                    name="worker_id"
                                    value="<?= $r['id'] ?>"
                                >

                                <input
                                    class="form-control"
                                    style="width:80px"
                                    type="number"
                                    name="days"
                                    value="<?= $r['days'] ?>"
                                >

                                <select
                                    class="form-select"
                                    name="method"
                                >
                                    <option>cash</option>
                                    <option>mfs</option>
                                    <option>bank</option>
                                </select>

                                <input
                                    class="form-control"
                                    name="ref"
                                    placeholder="Reference"
                                >

                                <button class="btn btn-success">
                                    Pay
                                </button>

                            </form>

                        </td>

                    </tr>

                <?php endforeach; ?>

            </table>

        </div>

    </main>

</div>