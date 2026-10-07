<?php

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';

require_role(['applicant']);


$uid = $_SESSION['user']['id'];

$msg = '';


$dir = __DIR__ . '/../uploads/cv/';


if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}


if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['apply'])
) {

    $jid = (int) $_POST['job_id'];

    $cv = null;


    if (
        !empty($_FILES['cv']['name']) &&
        $_FILES['cv']['error'] === UPLOAD_ERR_OK
    ) {

        $ext = strtolower(
            pathinfo(
                $_FILES['cv']['name'],
                PATHINFO_EXTENSION
            )
        );


        if (
            in_array(
                $ext,
                [
                    'pdf',
                    'doc',
                    'docx'
                ],
                true
            )
        ) {

            $cv =
                time() .
                '_' .
                bin2hex(random_bytes(3)) .
                '.' .
                $ext;


            move_uploaded_file(
                $_FILES['cv']['tmp_name'],
                $dir . $cv
            );
        }
    }


    if (!$cv) {

        $msg = 'CV/document is required.';

    } else {

        try {

            $st = $pdo->prepare(
                "INSERT INTO job_applications(
                    job_id,
                    applicant_user_id,
                    cover_note,
                    cv_file,
                    status,
                    applied_at
                )
                VALUES(
                    ?,?,?,?,
                    'submitted',
                    NOW()
                )"
            );


            $st->execute([
                $jid,
                $uid,
                trim($_POST['cover_note']),
                $cv
            ]);


            $msg = 'Application submitted successfully.';

        } catch (Throwable $e) {

            $msg = 'You have already applied for this position.';
        }
    }
}


$jobs = $pdo->query(
    "SELECT *
     FROM jobs
     WHERE status='open'
     AND application_deadline>=CURDATE()
     ORDER BY application_deadline"
)->fetchAll();


$st = $pdo->prepare(
    "SELECT
        a.*,
        j.title,
        j.application_deadline

     FROM job_applications a

     JOIN jobs j
        ON j.id=a.job_id

     WHERE a.applicant_user_id=?

     ORDER BY a.applied_at DESC"
);


$st->execute([$uid]);


$apps = $st->fetchAll();


$page_title = 'Applicant Portal';

require __DIR__ . '/../partials/header.php';

?>


<div class="container py-4">

    <div class="hero-top mb-4">

        <h2>
            Applicant Portal
        </h2>

        <p class="mb-0">
            Browse openings, apply and track interview schedules.
        </p>

    </div>


    <?php if ($msg): ?>

        <div class="alert alert-info">
            <?= htmlspecialchars($msg) ?>
        </div>

    <?php endif; ?>


    <div class="row g-4">

        <div class="col-lg-7">

            <div class="cardx p-3">

                <h5>
                    Open Jobs
                </h5>


                <?php foreach ($jobs as $j): ?>

                    <div class="job-card mb-2">

                        <div class="d-flex justify-content-between">

                            <h6>
                                <?= htmlspecialchars($j['title']) ?>
                            </h6>


                            <span class="badge-soft">
                                <?= htmlspecialchars($j['job_type']) ?>
                            </span>

                        </div>


                        <p class="small text-muted">
                            <?= nl2br(
                                htmlspecialchars($j['description'])
                            ) ?>
                        </p>


                        <div class="small-muted">
                            Deadline: <?= $j['application_deadline'] ?>
                        </div>


                        <form
                            method="post"
                            enctype="multipart/form-data"
                            class="mt-3"
                        >

                            <input
                                type="hidden"
                                name="apply"
                                value="1"
                            >


                            <input
                                type="hidden"
                                name="job_id"
                                value="<?= $j['id'] ?>"
                            >


                            <textarea
                                class="form-control mb-2"
                                name="cover_note"
                                placeholder="Short note about your skills"
                            ></textarea>


                            <input
                                class="form-control mb-2"
                                type="file"
                                name="cv"
                                accept=".pdf,.doc,.docx"
                                required
                            >


                            <button class="btn btn-success btn-sm">
                                Apply
                            </button>

                        </form>

                    </div>

                <?php endforeach; ?>


                <?php if (!$jobs): ?>

                    <div class="text-muted">
                        No open jobs right now.
                    </div>

                <?php endif; ?>

            </div>

        </div>


        <div class="col-lg-5">

            <div class="cardx p-3">

                <h5>
                    My Applications
                </h5>


                <?php foreach ($apps as $a): ?>

                    <div class="border-bottom py-3">

                        <b>
                            <?= htmlspecialchars($a['title']) ?>
                        </b>


                        <div>

                            <span
                                class="badge-soft <?= $a['status'] === 'rejected'
                                    ? 'badge-rejected'
                                    : (
                                        $a['status'] === 'interview_scheduled'
                                            ? 'badge-blue'
                                            : ''
                                    )
                                ?>"
                            >
                                <?= htmlspecialchars(
                                    str_replace(
                                        '_',
                                        ' ',
                                        ucfirst($a['status'])
                                    )
                                ) ?>
                            </span>

                        </div>


                        <?php if ($a['interview_at']): ?>

                            <div class="small mt-2">

                                <b>
                                    Interview:
                                </b>

                                <?= $a['interview_at'] ?>

                            </div>


                            <div class="small-muted">
                                <?= htmlspecialchars(
                                    $a['interview_note'] ?? ''
                                ) ?>
                            </div>

                        <?php endif; ?>

                    </div>

                <?php endforeach; ?>


                <?php if (!$apps): ?>

                    <div class="text-muted">
                        No applications yet.
                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>

</div>