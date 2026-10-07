<?php

require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../config/auth.php';

require_role(['owner']);


$farm = (int) ($_GET['farm_id'] ?? 0);

$from = $_GET['from'] ?? date('Y-m-01');

$to = $_GET['to'] ?? date('Y-m-d');


$farms = $pdo->query(
    "SELECT
        id,
        name
     FROM farms
     ORDER BY name"
)->fetchAll();


$where = $farm
    ? 'WHERE w.farm_id=?'
    : '';


$p = $farm
    ? [$farm]
    : [];


$q = $pdo->prepare(
    "SELECT
        w.*,
        f.name farm_name,
        u.name manager_name,

        (
            SELECT COUNT(*)
            FROM farm_worker_attendance a
            WHERE a.worker_id=w.id
            AND a.status='present'
            AND a.attendance_date BETWEEN ? AND ?
        ) present_days,

        (
            SELECT COUNT(*)
            FROM farm_worker_attendance a
            WHERE a.worker_id=w.id
            AND a.status='leave'
            AND a.attendance_date BETWEEN ? AND ?
        ) leave_days,

        (
            SELECT COUNT(*)
            FROM farm_worker_attendance a
            WHERE a.worker_id=w.id
            AND a.status='absent'
            AND a.attendance_date BETWEEN ? AND ?
        ) absent_days

     FROM farm_workers w

     JOIN farms f
        ON f.id=w.farm_id

     JOIN users u
        ON u.id=w.manager_user_id

     $where

     ORDER BY
        f.name,
        w.status='active' DESC,
        w.name"
);


$q->execute(
    array_merge(
        [
            $from,
            $to,
            $from,
            $to,
            $from,
            $to
        ],
        $p
    )
);


$workers = $q->fetchAll();


$todayFarm = $pdo->query(
    "SELECT
        f.name,
        COUNT(DISTINCT w.id) active_workers,
        SUM(a.status='present') present_count,
        SUM(a.status='absent') absent_count,
        SUM(a.status='leave') leave_count

     FROM farms f

     LEFT JOIN farm_workers w
        ON w.farm_id=f.id
        AND w.status='active'

     LEFT JOIN farm_worker_attendance a
        ON a.worker_id=w.id
        AND a.attendance_date=CURDATE()

     WHERE f.status='active'

     GROUP BY f.id

     ORDER BY f.name"
)->fetchAll();


$salary = $pdo->prepare(
    "SELECT
        p.*,
        w.name worker_name,
        f.name farm_name,
        u.name manager_name

     FROM farm_worker_payments p

     JOIN farm_workers w
        ON w.id=p.worker_id

     JOIN farms f
        ON f.id=p.farm_id

     JOIN users u
        ON u.id=p.paid_by

     WHERE p.paid_at BETWEEN ?
     AND DATE_ADD(?,INTERVAL 1 DAY)

     ORDER BY p.paid_at DESC"
);


$salary->execute([
    $from,
    $to
]);


$salaryRows = $salary->fetchAll();


$d = $pdo->query(
    "SELECT
        s.*,
        u.name,
        u.phone,
        u.email,
        u.profile_image,
        u.verification_status

     FROM staff s

     JOIN users u
        ON u.id=s.user_id

     WHERE s.staff_type IN(
        'delivery_man',
        'multipurpose'
     )

     ORDER BY
        s.status='active' DESC,
        u.name"
)->fetchAll();


$page_title = 'Farm Workforce';

require __DIR__ . '/../../partials/header.php';

?>


<div class="app-shell">

    <?php
    require __DIR__ . '/../../partials/admin_sidebar.php';
    ?>


    <main class="app-main">

        <div class="hero-top mb-3">

            <h2>
                Farm Workforce Overview
            </h2>

            <p>
                Owner view of farm workers, attendance history and delivery personnel.
            </p>

        </div>


        <form class="cardx p-3 row g-2 mb-3">

            <div class="col-md-3">

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
                            <?= $farm === $f['id']
                                ? 'selected'
                                : ''
                            ?>
                        >
                            <?= htmlspecialchars($f['name']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="col-md-3">

                <input
                    class="form-control"
                    type="date"
                    name="from"
                    value="<?= $from ?>"
                >

            </div>


            <div class="col-md-3">

                <input
                    class="form-control"
                    type="date"
                    name="to"
                    value="<?= $to ?>"
                >

            </div>


            <div class="col-md-3">

                <button class="btn btn-success w-100">
                    View Attendance
                </button>

            </div>

        </form>


        <div class="cardx p-3 mb-4">

            <h5>
                Today Attendance by Farm
            </h5>


            <table class="table">

                <tr>
                    <th>Farm</th>
                    <th>Active Workers</th>
                    <th>Present</th>
                    <th>Absent</th>
                    <th>Daily Leave</th>
                </tr>


                <?php foreach ($todayFarm as $t): ?>

                    <tr>

                        <td>
                            <b>
                                <?= htmlspecialchars($t['name']) ?>
                            </b>
                        </td>


                        <td>
                            <?= $t['active_workers'] ?>
                        </td>


                        <td>
                            <?= $t['present_count'] ?: 0 ?>
                        </td>


                        <td>
                            <?= $t['absent_count'] ?: 0 ?>
                        </td>


                        <td>
                            <?= $t['leave_count'] ?: 0 ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

            </table>

        </div>


        <div class="cardx p-3 mb-4">

            <h5>
                Worker Salary Payments — Selected Date Range
            </h5>


            <table class="table">

                <tr>
                    <th>Date</th>
                    <th>Farm</th>
                    <th>Worker</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Paid By</th>
                </tr>


                <?php foreach ($salaryRows as $x): ?>

                    <tr>

                        <td>
                            <?= $x['paid_at'] ?>
                        </td>


                        <td>
                            <?= htmlspecialchars($x['farm_name']) ?>
                        </td>


                        <td>
                            <?= htmlspecialchars($x['worker_name']) ?>
                        </td>


                        <td>
                            <b>
                                ৳<?= number_format($x['amount'], 2) ?>
                            </b>
                        </td>


                        <td>
                            <?= $x['payment_method'] ?>
                        </td>


                        <td>
                            <?= htmlspecialchars($x['manager_name']) ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

            </table>

        </div>


        <div class="cardx p-3 mb-4">

            <h5>
                Farm Workers
            </h5>


            <table class="table align-middle">

                <tr>
                    <th>Photo</th>
                    <th>Farm / Manager</th>
                    <th>Worker</th>
                    <th>Pay</th>
                    <th>Present Days</th>
                    <th>Absent</th>
                    <th>Daily Leave</th>
                    <th>Status</th>
                </tr>


                <?php foreach ($workers as $r): ?>

                    <tr>

                        <td>

                            <?php if ($r['profile_image']): ?>

                                <img
                                    src="<?= url_path($r['profile_image']) ?>"
                                    style="width:46px;height:46px;border-radius:50%;object-fit:cover"
                                >

                            <?php else: ?>

                                👤

                            <?php endif; ?>

                        </td>


                        <td>

                            <b>
                                <?= htmlspecialchars($r['farm_name']) ?>
                            </b>

                            <br>

                            <small>
                                Manager:
                                <?= htmlspecialchars($r['manager_name']) ?>
                            </small>

                        </td>


                        <td>

                            <b>
                                <?= htmlspecialchars($r['name']) ?>
                            </b>

                            <br>

                            <small>
                                <?= $r['phone'] ?>
                                ·
                                <?= $r['worker_code'] ?>
                            </small>

                        </td>


                        <td>
                            <?= ucfirst($r['pay_type']) ?>
                            ·
                            ৳<?= number_format($r['pay_rate'], 0) ?>
                        </td>


                        <td>
                            <b>
                                <?= $r['present_days'] ?>
                            </b>
                        </td>


                        <td>
                            <?= $r['absent_days'] ?>
                        </td>


                        <td>
                            <?= $r['leave_days'] ?>
                        </td>


                        <td>

                            <?= $r['status'] ?>


                            <?php if ($r['leave_date']): ?>

                                <br>

                                <small>
                                    Ended <?= $r['leave_date'] ?>
                                </small>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

            </table>

        </div>


        <div class="cardx p-3">

            <h5>
                Delivery Personnel
            </h5>

            <p class="text-muted">
                Delivery-only and multipurpose delivery staff remain visible to Owner for order fulfilment.
            </p>


            <table class="table align-middle">

                <tr>
                    <th>Photo</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Mobile</th>
                    <th>Verification</th>
                    <th>Status</th>
                </tr>


                <?php foreach ($d as $r): ?>

                    <tr>

                        <td>

                            <?php if ($r['profile_image']): ?>

                                <img
                                    src="<?= url_path($r['profile_image']) ?>"
                                    style="width:46px;height:46px;border-radius:50%;object-fit:cover"
                                >

                            <?php else: ?>

                                🚚

                            <?php endif; ?>

                        </td>


                        <td>

                            <b>
                                <?= htmlspecialchars($r['name']) ?>
                            </b>

                            <br>

                            <small>
                                <?= htmlspecialchars($r['email']) ?>
                            </small>

                        </td>


                        <td>
                            <?= htmlspecialchars($r['staff_type']) ?>
                        </td>


                        <td>
                            <?= htmlspecialchars($r['phone']) ?>
                        </td>


                        <td>
                            <?= $r['verification_status'] ?>
                        </td>


                        <td>
                            <?= $r['status'] ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

            </table>

        </div>

    </main>

</div>