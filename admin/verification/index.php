<?php

require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../config/auth.php';

require_role(['manager', 'owner']);


if (is_manager()) {
    require_work_checkin($pdo);
}


if (
    isset($_GET['action'], $_GET['id']) &&
    in_array(
        $_GET['action'],
        [
            'verified',
            'rejected',
            'pending'
        ],
        true
    )
) {

    $pdo->prepare(
        "UPDATE users
         SET verification_status=?
         WHERE id=?
         AND role='staff'"
    )->execute([
        $_GET['action'],
        (int) $_GET['id']
    ]);


    header('Location: index.php');
    exit;
}


$rows = $pdo->query(
    "SELECT
        u.*,
        s.staff_type
     FROM users u
     JOIN staff s
        ON s.user_id=u.id
     WHERE u.role='staff'
     ORDER BY
        FIELD(
            u.verification_status,
            'pending',
            'unverified',
            'rejected',
            'verified'
        ),
        u.id DESC"
)->fetchAll();


$page_title = 'Staff Verification';

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
                Staff Verification
            </h2>

            <p class="mb-0">
                Routine NID verification is handled by Manager.
            </p>

        </div>


        <div class="cardx p-3">

            <div class="table-wrap">

                <table class="table">

                    <thead>

                        <tr>
                            <th>Staff</th>
                            <th>Type</th>
                            <th>Mobile</th>
                            <th>NID</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($rows as $r): ?>

                            <tr>

                                <td>

                                    <b>
                                        <?= htmlspecialchars($r['name']) ?>
                                    </b>

                                    <div class="small-muted">
                                        <?= htmlspecialchars($r['email']) ?>
                                    </div>

                                </td>


                                <td>
                                    <?= $r['staff_type'] ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars($r['phone']) ?>
                                </td>


                                <td>

                                    <?php if ($r['nid_front']): ?>

                                        <a
                                            target="_blank"
                                            href="<?= upload_path(
                                                'profiles/' .
                                                rawurlencode($r['nid_front'])
                                            ) ?>"
                                        >
                                            Front
                                        </a>

                                    <?php endif; ?>


                                    <?php if ($r['nid_back']): ?>

                                        ·

                                        <a
                                            target="_blank"
                                            href="<?= upload_path(
                                                'profiles/' .
                                                rawurlencode($r['nid_back'])
                                            ) ?>"
                                        >
                                            Back
                                        </a>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <span class="badge-soft">
                                        <?= htmlspecialchars(
                                            $r['verification_status']
                                        ) ?>
                                    </span>

                                </td>


                                <td>

                                    <a
                                        class="btn btn-sm btn-success"
                                        href="?action=verified&id=<?= $r['id'] ?>"
                                    >
                                        Verify
                                    </a>


                                    <a
                                        class="btn btn-sm btn-outline-danger"
                                        href="?action=rejected&id=<?= $r['id'] ?>"
                                    >
                                        Reject
                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </main>

</div>