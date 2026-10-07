<?php

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';

$fid = require_manager_farm($pdo);
$uid = $_SESSION['user']['id'];

$date = $_GET['date'] ?? date('Y-m-d');


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    foreach ($_POST['status'] ?? [] as $id => $st) {

        if (in_array($st, ['present', 'absent', 'leave'])) {

            $ck = $pdo->prepare("
                SELECT id
                FROM farm_workers
                WHERE id = ?
                  AND farm_id = ?
                  AND status = 'active'
            ");

            $ck->execute([
                (int) $id,
                $fid
            ]);


            if ($ck->fetchColumn()) {

                $pdo->prepare("
                    INSERT INTO farm_worker_attendance (
                        worker_id,
                        attendance_date,
                        status,
                        marked_by
                    )
                    VALUES (?, ?, ?, ?)

                    ON DUPLICATE KEY UPDATE
                        status = VALUES(status),
                        marked_by = VALUES(marked_by)
                ")->execute([
                    (int) $id,
                    $date,
                    $st,
                    $uid
                ]);
            }
        }
    }


    header(
        'Location:worker_attendance.php?date=' . $date
    );

    exit;
}


$q = $pdo->prepare("
    SELECT
        w.*,
        a.status today

    FROM farm_workers w

    LEFT JOIN farm_worker_attendance a
        ON a.worker_id = w.id
       AND a.attendance_date = ?

    WHERE w.farm_id = ?
      AND w.status = 'active'
      AND w.join_date <= ?

    ORDER BY w.name
");

$q->execute([
    $date,
    $fid,
    $date
]);

$rows = $q->fetchAll();


$h = $pdo->prepare("
    SELECT
        a.attendance_date,
        SUM(a.status = 'present') present_count,
        SUM(a.status = 'absent') absent_count,
        SUM(a.status = 'leave') leave_count,
        COUNT(*) marked_count

    FROM farm_worker_attendance a

    JOIN farm_workers w
        ON w.id = a.worker_id

    WHERE w.farm_id = ?

    GROUP BY a.attendance_date

    ORDER BY a.attendance_date DESC

    LIMIT 31
");

$h->execute([$fid]);

$history = $h->fetchAll();


$page_title = 'Attendance';

require __DIR__ . '/../partials/header.php';

?>

<div class="app-shell">

    <?php require __DIR__ . '/../partials/manager_sidebar.php'; ?>

    <main class="app-main">

        <div class="hero-top mb-3">

            <h2>
                Worker Attendance
            </h2>

            <p>
                <b>Daily Leave</b> means the worker is off only for
                the selected date. It does not remove the worker.
            </p>

        </div>


        <form class="cardx p-3 mb-3">

            <label class="me-2">
                Attendance Date
            </label>

            <input
                type="date"
                name="date"
                value="<?= $date ?>"
            >

            <button class="btn btn-outline-success">
                Open Date
            </button>

        </form>


        <form
            method="post"
            class="cardx p-3"
        >

            <table class="table align-middle">

                <tr>
                    <th>Worker</th>
                    <th>Pay</th>
                    <th>Status for <?= $date ?></th>
                </tr>


                <?php foreach ($rows as $r): ?>

                    <tr>

                        <td>
                            <b>
                                <?= $r['worker_code'] ?>
                                ·
                                <?= htmlspecialchars($r['name']) ?>
                            </b>
                        </td>

                        <td>
                            <?= $r['pay_type'] ?>
                            ·
                            ৳<?= $r['pay_rate'] ?>
                        </td>

                        <td>

                            <select
                                class="form-select"
                                name="status[<?= $r['id'] ?>]"
                            >

                                <option
                                    value="present"
                                    <?= $r['today'] === 'present' ? 'selected' : '' ?>
                                >
                                    Present
                                </option>

                                <option
                                    value="absent"
                                    <?= $r['today'] === 'absent' ? 'selected' : '' ?>
                                >
                                    Absent
                                </option>

                                <option
                                    value="leave"
                                    <?= $r['today'] === 'leave' ? 'selected' : '' ?>
                                >
                                    Daily Leave
                                </option>

                            </select>

                        </td>

                    </tr>

                <?php endforeach; ?>

            </table>


            <button class="btn btn-success">
                Save Attendance
            </button>

        </form>


        <div class="cardx p-3 mt-3">

            <h5>
                Previous Attendance — Last 31 Recorded Days
            </h5>

            <table class="table">

                <tr>
                    <th>Date</th>
                    <th>Present</th>
                    <th>Absent</th>
                    <th>Daily Leave</th>
                    <th>Total Marked</th>
                    <th></th>
                </tr>


                <?php foreach ($history as $x): ?>

                    <tr>

                        <td>
                            <?= $x['attendance_date'] ?>
                        </td>

                        <td>
                            <?= $x['present_count'] ?>
                        </td>

                        <td>
                            <?= $x['absent_count'] ?>
                        </td>

                        <td>
                            <?= $x['leave_count'] ?>
                        </td>

                        <td>
                            <?= $x['marked_count'] ?>
                        </td>

                        <td>
                            <a href="?date=<?= $x['attendance_date'] ?>">
                                View / Edit
                            </a>
                        </td>

                    </tr>

                <?php endforeach; ?>

            </table>

        </div>

    </main>

</div>