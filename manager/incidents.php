<?php

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';

require_role(['manager', 'owner']);

require __DIR__ . '/../config/smart_ops.php';


if (isset($_POST['status'])) {

    $id = (int) $_POST['id'];
    $status = $_POST['status'];

    $pdo->prepare("
        UPDATE incident_reports
        SET
            status = ?,
            manager_user_id = ?
        WHERE id = ?
    ")->execute([
        $status,
        $_SESSION['user']['id'],
        $id
    ]);


    $s = $pdo->prepare("
        SELECT
            reporter_user_id,
            title
        FROM incident_reports
        WHERE id = ?
    ");

    $s->execute([$id]);


    if ($r = $s->fetch()) {

        notify_user(
            $pdo,
            (int) $r['reporter_user_id'],
            'incident_update',
            'Incident updated: ' . $r['title'],
            'Manager changed status to ' . str_replace('_', ' ', $status),
            'staff/report_incident.php',
            'incident',
            $id
        );
    }
}


$rows = $pdo->query("
    SELECT
        i.*,
        u.name reporter,
        f.name farm_name
    FROM incident_reports i
    JOIN users u
        ON u.id = i.reporter_user_id
    LEFT JOIN farms f
        ON f.id = i.farm_id
    ORDER BY
        FIELD(
            i.status,
            'reported',
            'action_required',
            'reviewing',
            'resolved',
            'closed'
        ),
        i.id DESC
")->fetchAll();


$page_title = 'Incident Center';

require __DIR__ . '/../partials/header.php';

?>

<div class="container py-4">

    <div class="hero-top">

        <h2>
            🧠 Smart Incident Center
        </h2>

        <p class="mb-0">
            Staff field reports arrive here with photos and structured
            initial-response guidance.
        </p>

    </div>


    <div class="incident-grid mt-4">

        <?php foreach ($rows as $r): ?>

            <article class="incident-card">

                <?php if ($r['image_path']): ?>

                    <img
                        src="<?= url_path($r['image_path']) ?>"
                        alt="Incident photo"
                    >

                <?php endif; ?>


                <div class="p-3">

                    <div class="d-flex justify-content-between">

                        <span class="badge-soft">
                            <?= htmlspecialchars($r['incident_type']) ?>
                        </span>

                        <b>
                            <?= strtoupper($r['urgency']) ?>
                        </b>

                    </div>


                    <h4 class="mt-2">
                        <?= htmlspecialchars($r['title']) ?>
                    </h4>


                    <small>
                        <?= htmlspecialchars($r['reporter']) ?>
                        ·
                        <?= htmlspecialchars($r['farm_name'] ?? 'General') ?>
                    </small>


                    <p class="mt-2">
                        <?= nl2br(htmlspecialchars($r['description'])) ?>
                    </p>


                    <div class="smart-advice">

                        <b>
                            Initial guidance
                        </b>

                        <p>
                            <?= htmlspecialchars($r['ai_initial_steps']) ?>
                        </p>

                    </div>


                    <form
                        method="post"
                        class="d-flex gap-2 mt-3"
                    >

                        <input
                            type="hidden"
                            name="id"
                            value="<?= $r['id'] ?>"
                        >


                        <select
                            class="form-select"
                            name="status"
                        >
                            <option>reported</option>
                            <option>reviewing</option>
                            <option>action_required</option>
                            <option>resolved</option>
                            <option>closed</option>
                        </select>


                        <button class="btn btn-success">
                            Update
                        </button>

                    </form>

                </div>

            </article>

        <?php endforeach; ?>

    </div>

</div>