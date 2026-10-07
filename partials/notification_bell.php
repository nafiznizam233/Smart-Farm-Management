<?php

require_once __DIR__ . '/../config/smart_ops.php';

$uid = (int) ($_SESSION['user']['id'] ?? 0);

$unread = 0;
$notes = [];


if ($uid) {

    $st = $pdo->prepare("
        SELECT *
        FROM notifications
        WHERE user_id = ?
        ORDER BY id DESC
        LIMIT 8
    ");

    $st->execute([$uid]);

    $notes = $st->fetchAll();


    $st = $pdo->prepare("
        SELECT COUNT(*)
        FROM notifications
        WHERE user_id = ?
          AND status = 'unread'
    ");

    $st->execute([$uid]);

    $unread = (int) $st->fetchColumn();
}

?>

<div class="notif-wrap">

    <button
        class="notif-btn"
        type="button"
        onclick="document.getElementById('notifPanel').classList.toggle('show')"
    >
        🔔

        <?php if ($unread): ?>

            <span>
                <?= $unread ?>
            </span>

        <?php endif; ?>

    </button>


    <div
        id="notifPanel"
        class="notif-panel"
    >

        <div class="notif-head">

            <b>
                Notifications
            </b>

            <a href="<?= url_path('notifications.php') ?>">
                View all
            </a>

        </div>


        <?php if (!$notes): ?>

            <div class="notif-empty">
                No notifications yet.
            </div>

        <?php endif; ?>


        <?php foreach ($notes as $n): ?>

            <a
                class="notif-item <?= $n['status'] === 'unread' ? 'new' : '' ?>"
                href="<?= url_path('notifications.php?open=' . $n['id']) ?>"
            >

                <b>
                    <?= htmlspecialchars($n['title']) ?>
                </b>

                <small>
                    <?= htmlspecialchars($n['message']) ?>
                </small>

                <em>
                    <?= htmlspecialchars(
                        strtoupper($n['status'])
                    ) ?>
                </em>

            </a>

        <?php endforeach; ?>

    </div>

</div>