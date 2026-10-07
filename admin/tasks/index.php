<?php

require __DIR__ . "/../../config/database.php";
require __DIR__ . "/../../config/auth.php";

require_role(['owner', 'manager']);


if (is_manager() || is_staff()) {
    require_work_checkin($pdo);
}


$err = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $sid = (int) $_POST['staff_id'];

    $date = $_POST['task_date'];

    $start = $_POST['task_start'];

    $end = $_POST['task_end'];


    $st = $pdo->prepare(
        "SELECT staff_type
         FROM staff
         WHERE id=?"
    );

    $st->execute([$sid]);

    $type = $st->fetchColumn();


    if ($type === 'delivery_man') {

        $err = 'Delivery Only staff cannot receive farm tasks.';

    } else {

        $st = $pdo->prepare(
            "SELECT COUNT(*)
             FROM delivery_assignments
             WHERE staff_id=?
             AND delivery_date=?
             AND status<>'delivered'
             AND NOT (
                end_time<=?
                OR start_time>=?
             )"
        );

        $st->execute([
            $sid,
            $date,
            $start,
            $end
        ]);


        if ($st->fetchColumn() > 0) {

            $err = 'Staff unavailable: delivery is already scheduled in this period and has priority.';

        } else {

            $pdo->prepare(
                "INSERT INTO staff_tasks(
                    staff_id,
                    farm_id,
                    title,
                    details,
                    task_date,
                    task_start,
                    task_end,
                    status,
                    created_by
                )
                VALUES(
                    ?,?,?,?,?,?,?,
                    'pending',
                    ?
                )"
            )->execute([
                $sid,
                $_POST['farm_id'] ?: null,
                trim($_POST['title']),
                trim($_POST['details']),
                $date,
                $start,
                $end,
                $_SESSION['user']['id']
            ]);


            header('Location:index.php');
            exit;
        }
    }
}


$staff = $pdo->query(
    "SELECT
        s.id,
        u.name,
        s.staff_type
     FROM staff s
     JOIN users u
        ON u.id=s.user_id
     WHERE s.staff_type IN(
        'farm_worker',
        'multipurpose'
     )
     AND s.status='active'
     ORDER BY u.name"
)->fetchAll();


$farms = $pdo->query(
    "SELECT
        id,
        name
     FROM farms
     WHERE status='active'"
)->fetchAll();


$tasks = $pdo->query(
    "SELECT
        t.*,
        u.name staff_name,
        f.name farm_name
     FROM staff_tasks t
     JOIN staff s
        ON s.id=t.staff_id
     JOIN users u
        ON u.id=s.user_id
     LEFT JOIN farms f
        ON f.id=t.farm_id
     ORDER BY t.id DESC"
)->fetchAll();


$page_title = 'Tasks';

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
                    Task Assignment & Schedule Conflict
                </h2>

            </div>


            <?php if ($err): ?>

                <div class="alert alert-danger">
                    <?= $err ?>
                </div>

            <?php endif; ?>


            <div class="cardx p-3 mb-4">

                <form
                    method="post"
                    class="row g-2"
                >

                    <div class="col-md-2">

                        <select
                            class="form-select"
                            name="staff_id"
                            required
                        >

                            <option value="">
                                Staff
                            </option>


                            <?php foreach ($staff as $s): ?>

                                <option value="<?= $s['id'] ?>">

                                    <?= htmlspecialchars($s['name']) ?>

                                    (<?= $s['staff_type'] ?>)

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="col-md-2">

                        <select
                            class="form-select"
                            name="farm_id"
                        >

                            <option value="">
                                No Farm
                            </option>


                            <?php foreach ($farms as $f): ?>

                                <option value="<?= $f['id'] ?>">
                                    <?= htmlspecialchars($f['name']) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="col-md-2">

                        <input
                            class="form-control"
                            name="title"
                            placeholder="Task title"
                            required
                        >

                    </div>


                    <div class="col-md-2">

                        <input
                            class="form-control"
                            name="details"
                            placeholder="Details"
                        >

                    </div>


                    <div class="col-md-2">

                        <input
                            class="form-control"
                            type="date"
                            name="task_date"
                            value="<?= date('Y-m-d') ?>"
                        >

                    </div>


                    <div class="col-md-1">

                        <input
                            class="form-control"
                            type="time"
                            name="task_start"
                            required
                        >

                    </div>


                    <div class="col-md-1">

                        <input
                            class="form-control"
                            type="time"
                            name="task_end"
                            required
                        >

                    </div>


                    <div class="col-md-12">

                        <button class="btn btn-success">
                            Assign Task
                        </button>

                    </div>

                </form>

            </div>


            <div class="cardx p-3">

                <table class="table">

                    <thead>

                        <tr>
                            <th>Date</th>
                            <th>Staff</th>
                            <th>Farm</th>
                            <th>Task</th>
                            <th>Schedule</th>
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
                                    <?= htmlspecialchars($t['staff_name']) ?>
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
                                    <?= $t['task_start'] . ' - ' . $t['task_end'] ?>
                                </td>


                                <td>
                                    <?= $t['status'] ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>