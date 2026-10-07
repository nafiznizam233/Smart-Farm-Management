<?php

require __DIR__ . "/../../config/database.php";
require __DIR__ . "/../../config/auth.php";

require_role(['owner', 'manager']);


$staffList = $pdo->query(
    "SELECT
        s.id,
        u.name,
        s.staff_type
     FROM staff s
     JOIN users u
        ON u.id=s.user_id
     WHERE s.status='active'
     ORDER BY u.name"
)->fetchAll();


$sid = (int) ($_GET['staff_id'] ?? 0);

$from = $_GET['from'] ?? date('Y-m-01');

$to = $_GET['to'] ?? date('Y-m-t');


$person = null;

$tasks = $att = $payments = [];


if ($sid) {

    $st = $pdo->prepare(
        "SELECT
            s.*,
            u.name,
            u.email,
            u.phone,
            u.verification_status
         FROM staff s
         JOIN users u
            ON u.id=s.user_id
         WHERE s.id=?"
    );

    $st->execute([$sid]);

    $person = $st->fetch();


    $st = $pdo->prepare(
        "SELECT
            t.*,
            f.name farm_name
         FROM staff_tasks t
         LEFT JOIN farms f
            ON f.id=t.farm_id
         WHERE t.staff_id=?
         AND t.task_date BETWEEN ? AND ?
         ORDER BY t.task_date"
    );

    $st->execute([
        $sid,
        $from,
        $to
    ]);

    $tasks = $st->fetchAll();


    $st = $pdo->prepare(
        "SELECT *
         FROM attendance
         WHERE staff_id=?
         AND attendance_date BETWEEN ? AND ?
         ORDER BY attendance_date"
    );

    $st->execute([
        $sid,
        $from,
        $to
    ]);

    $att = $st->fetchAll();


    $st = $pdo->prepare(
        "SELECT *
         FROM staff_payments
         WHERE staff_id=?
         AND DATE(
            COALESCE(
                paid_at,
                created_at
            )
         ) BETWEEN ? AND ?
         ORDER BY id DESC"
    );

    $st->execute([
        $sid,
        $from,
        $to
    ]);

    $payments = $st->fetchAll();
}


$page_title = 'Staff Report';

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

            <div class="hero-top mb-4 no-print">

                <h2>
                    Printable Staff Full Report
                </h2>

            </div>


            <div class="cardx p-3 mb-4 no-print">

                <form
                    method="get"
                    class="row g-2"
                >

                    <div class="col-md-4">

                        <select
                            class="form-select"
                            name="staff_id"
                            required
                        >

                            <option value="">
                                Select Staff
                            </option>


                            <?php foreach ($staffList as $s): ?>

                                <option
                                    value="<?= $s['id'] ?>"
                                    <?= $sid === $s['id']
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    <?= htmlspecialchars($s['name']) ?>
                                    - <?= $s['staff_type'] ?>
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


                    <div class="col-md-2">

                        <button class="btn btn-success w-100">
                            Generate
                        </button>

                    </div>

                </form>

            </div>


            <?php if ($person): ?>

                <div class="text-end mb-2 no-print">

                    <button
                        class="btn btn-dark"
                        onclick="window.print()"
                    >
                        <i class="fa-solid fa-print"></i>
                        Print Report
                    </button>

                </div>


                <div class="cardx p-4">

                    <h2>
                        Staff Full Report
                    </h2>


                    <p>

                        <b>
                            <?= htmlspecialchars($person['name']) ?>
                        </b>

                        |

                        <?= $person['staff_type'] ?>

                        |

                        <?= htmlspecialchars($person['phone']) ?>

                        |

                        Verification:

                        <?= $person['verification_status'] ?>

                    </p>


                    <p>
                        Period: <?= $from ?> to <?= $to ?>
                    </p>


                    <h5>
                        Tasks
                    </h5>


                    <table class="table table-bordered">

                        <thead>

                            <tr>
                                <th>Date</th>
                                <th>Farm</th>
                                <th>Task</th>
                                <th>Status</th>
                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($tasks as $t): ?>

                                <tr>

                                    <td>
                                        <?= $t['task_date'] ?>
                                    </td>


                                    <td>
                                        <?= htmlspecialchars(
                                            $t['farm_name'] ?? '-'
                                        ) ?>
                                    </td>


                                    <td>
                                        <?= htmlspecialchars($t['title']) ?>
                                    </td>


                                    <td>
                                        <?= $t['status'] ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>


                    <h5>
                        Attendance
                    </h5>


                    <table class="table table-bordered">

                        <thead>

                            <tr>
                                <th>Date</th>
                                <th>In</th>
                                <th>Out</th>
                                <th>Status</th>
                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($att as $a): ?>

                                <tr>

                                    <td>
                                        <?= $a['attendance_date'] ?>
                                    </td>


                                    <td>
                                        <?= $a['check_in'] ?>
                                    </td>


                                    <td>
                                        <?= $a['check_out'] ?>
                                    </td>


                                    <td>
                                        <?= $a['status'] ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>


                    <h5>
                        Payments
                    </h5>


                    <table class="table table-bordered">

                        <thead>

                            <tr>
                                <th>Month</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($payments as $p): ?>

                                <tr>

                                    <td>
                                        <?= $p['payment_month'] ?>
                                    </td>


                                    <td>
                                        ৳<?= number_format(
                                            $p['amount'],
                                            2
                                        ) ?>
                                    </td>


                                    <td>
                                        <?= strtoupper(
                                            $p['payment_method']
                                        ) ?>
                                    </td>


                                    <td>
                                        <?= $p['status'] ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>