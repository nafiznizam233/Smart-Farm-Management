<?php

require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../config/auth.php';

require_role(['owner', 'manager']);


if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['create_job'])
) {

    $st = $pdo->prepare(
        "INSERT INTO jobs(
            title,
            job_type,
            description,
            requirements,
            application_deadline,
            status,
            created_by
        )
        VALUES(
            ?,?,?,?,?,
            'open',
            ?
        )"
    );

    $st->execute([
        trim($_POST['title']),
        trim($_POST['job_type']),
        trim($_POST['description']),
        trim($_POST['requirements']),
        $_POST['deadline'],
        $_SESSION['user']['id']
    ]);


    header('Location: index.php');
    exit;
}


if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_app'])
) {

    $st = $pdo->prepare(
        "UPDATE job_applications
         SET
            status=?,
            interview_at=?,
            interview_note=?,
            reviewed_by=?
         WHERE id=?"
    );


    $at = $_POST['interview_at'] ?: null;


    $st->execute([
        $_POST['status'],
        $at,
        trim($_POST['interview_note']),
        $_SESSION['user']['id'],
        (int) $_POST['application_id']
    ]);


    header('Location: index.php');
    exit;
}


$jobs = $pdo->query(
    "SELECT *
     FROM jobs
     ORDER BY id DESC"
)->fetchAll();


$apps = $pdo->query(
    "SELECT
        a.*,
        j.title job_title,
        u.name,
        u.email,
        u.phone
     FROM job_applications a
     JOIN jobs j
        ON j.id=a.job_id
     JOIN users u
        ON u.id=a.applicant_user_id
     ORDER BY a.applied_at DESC"
)->fetchAll();


$page_title = 'Jobs & Applicants';

require __DIR__ . '/../../partials/header.php';

?>


<div class="app-shell">

    <?php

    if (is_owner()) {
        require __DIR__ . '/../../partials/admin_sidebar.php';
    } else {
        require __DIR__ . '/../../partials/manager_sidebar.php';
    }

    ?>


    <main class="app-main">

        <div class="hero-top mb-4">

            <h2>
                Jobs & Applicants
            </h2>

            <p class="mb-0">
                Post openings, review applications and schedule interviews.
            </p>

        </div>


        <div class="cardx p-3 mb-4">

            <h5>
                Create Job Opening
            </h5>


            <form
                method="post"
                class="row g-2"
            >

                <input
                    type="hidden"
                    name="create_job"
                    value="1"
                >


                <div class="col-md-3">

                    <input
                        class="form-control"
                        name="title"
                        placeholder="Job title"
                        required
                    >

                </div>


                <div class="col-md-2">

                    <input
                        class="form-control"
                        name="job_type"
                        placeholder="Farm Worker"
                        required
                    >

                </div>


                <div class="col-md-3">

                    <input
                        class="form-control"
                        name="description"
                        placeholder="Description"
                        required
                    >

                </div>


                <div class="col-md-2">

                    <input
                        class="form-control"
                        name="requirements"
                        placeholder="Requirements"
                    >

                </div>


                <div class="col-md-2">

                    <input
                        class="form-control"
                        type="date"
                        name="deadline"
                        required
                    >

                </div>


                <div class="col-12">

                    <button class="btn btn-success">
                        Publish Job
                    </button>

                </div>

            </form>

        </div>


        <div class="cardx p-3">

            <h5>
                Applications
            </h5>


            <div class="table-wrap">

                <table class="table">

                    <thead>

                        <tr>
                            <th>Applicant</th>
                            <th>Job</th>
                            <th>Applied</th>
                            <th>CV</th>
                            <th>Status / Interview</th>
                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($apps as $a): ?>

                            <tr>

                                <td>

                                    <b>
                                        <?= htmlspecialchars($a['name']) ?>
                                    </b>

                                    <div class="small-muted">

                                        <?= htmlspecialchars($a['email']) ?>

                                        ·

                                        <?= htmlspecialchars($a['phone']) ?>

                                    </div>

                                </td>


                                <td>
                                    <?= htmlspecialchars($a['job_title']) ?>
                                </td>


                                <td>
                                    <?= $a['applied_at'] ?>
                                </td>


                                <td>

                                    <a
                                        href="<?= url_path(
                                            'uploads/cv/' .
                                            rawurlencode($a['cv_file'])
                                        ) ?>"
                                        target="_blank"
                                    >
                                        View CV
                                    </a>

                                </td>


                                <td>

                                    <form
                                        method="post"
                                        class="d-flex flex-wrap gap-1"
                                    >

                                        <input
                                            type="hidden"
                                            name="update_app"
                                            value="1"
                                        >

                                        <input
                                            type="hidden"
                                            name="application_id"
                                            value="<?= $a['id'] ?>"
                                        >


                                        <select
                                            class="form-select form-select-sm"
                                            name="status"
                                            style="width:150px"
                                        >

                                            <?php foreach (
                                                [
                                                    'submitted',
                                                    'under_review',
                                                    'shortlisted',
                                                    'interview_scheduled',
                                                    'selected',
                                                    'rejected'
                                                ] as $s
                                            ): ?>

                                                <option
                                                    value="<?= $s ?>"
                                                    <?= $a['status'] === $s
                                                        ? 'selected'
                                                        : ''
                                                    ?>
                                                >
                                                    <?= ucwords(
                                                        str_replace(
                                                            '_',
                                                            ' ',
                                                            $s
                                                        )
                                                    ) ?>
                                                </option>

                                            <?php endforeach; ?>

                                        </select>


                                        <input
                                            class="form-control form-control-sm"
                                            type="datetime-local"
                                            name="interview_at"
                                            value="<?= $a['interview_at']
                                                ? date(
                                                    'Y-m-d\TH:i',
                                                    strtotime($a['interview_at'])
                                                )
                                                : ''
                                            ?>"
                                            style="width:185px"
                                        >


                                        <input
                                            class="form-control form-control-sm"
                                            name="interview_note"
                                            value="<?= htmlspecialchars(
                                                $a['interview_note'] ?? ''
                                            ) ?>"
                                            placeholder="Interview note"
                                            style="width:180px"
                                        >


                                        <button class="btn btn-sm btn-success">
                                            Save
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </main>

</div>