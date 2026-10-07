<?php

require __DIR__ . '/config/database.php';
require __DIR__ . '/config/auth.php';

require_login();

require __DIR__ . '/config/smart_ops.php';


$uid = (int) $_SESSION['user']['id'];


if (isset($_GET['open'])) {

    $id = (int) $_GET['open'];


    $s = $pdo->prepare("
        SELECT *
        FROM notifications
        WHERE id = ?
          AND user_id = ?
    ");

    $s->execute([
        $id,
        $uid
    ]);

    $n = $s->fetch();


    if ($n) {

        $pdo->prepare("
            UPDATE notifications
            SET
                status = IF(
                    status = 'unread',
                    'read',
                    status
                ),
                read_at = COALESCE(
                    read_at,
                    NOW()
                )
            WHERE id = ?
        ")->execute([
            $id
        ]);


        if ($n['link_url']) {

            header(
                'Location: ' .
                url_path($n['link_url'])
            );

            exit;
        }
    }
}


$st = $pdo->prepare("
    SELECT *
    FROM notifications
    WHERE user_id = ?
    ORDER BY id DESC
    LIMIT 100
");

$st->execute([
    $uid
]);

$rows = $st->fetchAll();


$page_title = 'Notifications';

require __DIR__ . '/partials/header.php';

?>


<div class="container py-4">

    <div class="hero-top">

        <h2>
            🔔 Notification Center
        </h2>

        <p class="mb-0">
            Incidents, delivery updates and workflow alerts in one place.
        </p>

    </div>


    <div class="cardx p-3 mt-3">

        <?php foreach ($rows as $n): ?>

            <div class="notif-list-row">

                <div>

                    <b>
                        <?= htmlspecialchars($n['title']) ?>
                    </b>

                    <div class="text-muted">
                        <?= htmlspecialchars($n['message']) ?>
                    </div>

                    <small>
                        <?= $n['created_at'] ?>
                    </small>

                </div>


                <span class="badge-soft">
                    <?= htmlspecialchars($n['status']) ?>
                </span>

            </div>

        <?php endforeach; ?>

    </div>

</div>