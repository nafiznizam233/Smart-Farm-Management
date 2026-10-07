<?php

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';

$fid = require_manager_farm($pdo);
$uid = $_SESSION['user']['id'];


if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['add_worker'])
) {

    $code = 'WRK-' .
        date('y') .
        '-' .
        str_pad(
            (string) random_int(1, 99999),
            5,
            '0',
            STR_PAD_LEFT
        );

    $photo = null;


    if (!empty($_FILES['photo']['name'])) {

        $ext = strtolower(
            pathinfo(
                $_FILES['photo']['name'],
                PATHINFO_EXTENSION
            )
        );


        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {

            $dir = __DIR__ . '/../uploads/workers';


            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }


            $photo =
                'uploads/workers/' .
                uniqid('worker_') .
                '.' .
                $ext;


            move_uploaded_file(
                $_FILES['photo']['tmp_name'],
                __DIR__ . '/../' . $photo
            );
        }
    }


    $pdo->prepare("
        INSERT INTO farm_workers (
            farm_id,
            manager_user_id,
            worker_code,
            name,
            phone,
            nid_number,
            identification_number,
            address,
            profile_image,
            pay_type,
            pay_rate,
            join_date,
            notes
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ")->execute([
        $fid,
        $uid,
        $code,
        trim($_POST['name']),
        trim($_POST['phone']),
        trim($_POST['nid']),
        trim($_POST['other_id']),
        trim($_POST['address']),
        $photo,
        $_POST['pay_type'],
        (float) $_POST['pay_rate'],
        $_POST['join_date'],
        trim($_POST['notes'])
    ]);


    header('Location:workers.php');
    exit;
}


if (isset($_GET['end'])) {

    $pdo->prepare("
        UPDATE farm_workers
        SET
            status = 'left',
            leave_date = CURDATE()
        WHERE id = ?
          AND farm_id = ?
    ")->execute([
        (int) $_GET['end'],
        $fid
    ]);


    header('Location:workers.php');
    exit;
}


$q = $pdo->prepare("
    SELECT
        w.*,

        (
            SELECT COUNT(*)
            FROM farm_worker_attendance a
            WHERE a.worker_id = w.id
              AND a.status = 'present'
        ) worked_days,

        (
            SELECT status
            FROM farm_worker_attendance a
            WHERE a.worker_id = w.id
              AND a.attendance_date = CURDATE()
        ) today_status

    FROM farm_workers w

    WHERE w.farm_id = ?

    ORDER BY
        w.status = 'active' DESC,
        w.id DESC
");

$q->execute([$fid]);

$rows = $q->fetchAll();


$page_title = 'Farm Workers';

require __DIR__ . '/../partials/header.php';

?>

<div class="app-shell">

    <?php require __DIR__ . '/../partials/manager_sidebar.php'; ?>

    <main class="app-main">

        <div class="hero-top mb-3">

            <h2>
                Farm Workers
            </h2>

            <p>
                Workers have no login. Daily Leave is handled from Attendance;
                End Employment is only for a worker who permanently leaves
                the farm.
            </p>

        </div>


        <form
            method="post"
            enctype="multipart/form-data"
            class="cardx p-3 row g-2 mb-3"
        >

            <input
                type="hidden"
                name="add_worker"
                value="1"
            >


            <div class="col-md-3">

                <input
                    class="form-control"
                    name="name"
                    placeholder="Worker name"
                    required
                >

            </div>


            <div class="col-md-2">

                <input
                    class="form-control"
                    name="phone"
                    placeholder="Mobile"
                    required
                >

            </div>


            <div class="col-md-2">

                <input
                    class="form-control"
                    name="nid"
                    placeholder="NID"
                >

            </div>


            <div class="col-md-2">

                <input
                    class="form-control"
                    name="other_id"
                    placeholder="Other ID"
                >

            </div>


            <div class="col-md-3">

                <input
                    class="form-control"
                    name="address"
                    placeholder="Address"
                >

            </div>


            <div class="col-md-2">

                <select
                    class="form-select"
                    name="pay_type"
                >
                    <option value="daily">
                        Daily
                    </option>

                    <option value="monthly">
                        Monthly
                    </option>
                </select>

            </div>


            <div class="col-md-2">

                <input
                    class="form-control"
                    type="number"
                    step=".01"
                    name="pay_rate"
                    value="500"
                    required
                >

            </div>


            <div class="col-md-2">

                <input
                    class="form-control"
                    type="date"
                    name="join_date"
                    value="<?= date('Y-m-d') ?>"
                    required
                >

            </div>


            <div class="col-md-3">

                <input
                    class="form-control"
                    type="file"
                    name="photo"
                    accept=".jpg,.jpeg,.png,.webp"
                >

            </div>


            <div class="col-md-2">

                <input
                    class="form-control"
                    name="notes"
                    placeholder="Note"
                >

            </div>


            <div class="col-md-1">

                <button class="btn btn-success w-100">
                    Add
                </button>

            </div>

        </form>


        <div class="cardx p-3">

            <table class="table align-middle">

                <tr>
                    <th>Photo</th>
                    <th>ID / Worker</th>
                    <th>Identity</th>
                    <th>Pay</th>
                    <th>Worked</th>
                    <th>Today</th>
                    <th>Employment</th>
                    <th></th>
                </tr>


                <?php foreach ($rows as $r): ?>

                    <tr>

                        <td>

                            <?php if ($r['profile_image']): ?>

                                <img
                                    src="<?= url_path($r['profile_image']) ?>"
                                    style="
                                        width:48px;
                                        height:48px;
                                        border-radius:50%;
                                        object-fit:cover;
                                    "
                                >

                            <?php else: ?>

                                <div
                                    style="
                                        width:48px;
                                        height:48px;
                                        border-radius:50%;
                                        background:#eaf3ed;
                                        display:grid;
                                        place-items:center;
                                    "
                                >
                                    👤
                                </div>

                            <?php endif; ?>

                        </td>


                        <td>

                            <b>
                                <?= $r['worker_code'] ?>
                            </b>

                            <br>

                            <?= htmlspecialchars($r['name']) ?>

                            <br>

                            <small>
                                <?= $r['phone'] ?>
                                ·
                                <?= htmlspecialchars($r['address']) ?>
                            </small>

                        </td>


                        <td>

                            NID:
                            <?= htmlspecialchars(
                                $r['nid_number'] ?: '-'
                            ) ?>

                            <br>

                            <small>
                                ID:
                                <?= htmlspecialchars(
                                    $r['identification_number'] ?: '-'
                                ) ?>
                            </small>

                        </td>


                        <td>
                            <?= ucfirst($r['pay_type']) ?>
                            ·
                            ৳<?= number_format($r['pay_rate'], 0) ?>
                        </td>


                        <td>
                            <b>
                                <?= $r['worked_days'] ?> days
                            </b>
                        </td>


                        <td>
                            <?= htmlspecialchars(
                                $r['today_status'] ?: 'Not marked'
                            ) ?>
                        </td>


                        <td>
                            <?= $r['status'] ?>
                        </td>


                        <td>

                            <?php if ($r['status'] === 'active'): ?>

                                <a
                                    class="btn btn-sm btn-outline-danger"
                                    href="?end=<?= $r['id'] ?>"
                                    onclick="return confirm('This is permanent. End this worker employment?')"
                                >
                                    End Employment
                                </a>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

            </table>

        </div>

    </main>

</div>