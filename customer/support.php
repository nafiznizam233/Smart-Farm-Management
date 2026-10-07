<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

session_start();

if (empty($_SESSION['customer'])) {
    header(
        'Location: ' . url_path('customer/login.php')
    );
    exit;
}

$uid = (int) $_SESSION['customer']['id'];
$msg = '';

$st = $pdo->prepare(
    "SELECT *
     FROM live_chat_threads
     WHERE customer_user_id = ?
     AND status = 'open'
     ORDER BY id DESC
     LIMIT 1"
);

$st->execute([$uid]);

$thread = $st->fetch();

if (!$thread) {
    $pdo->prepare(
        "INSERT INTO live_chat_threads(
            customer_user_id,
            status
        )
        VALUES (?, 'open')"
    )->execute([
        $uid
    ]);

    $thread = [
        'id' => (int) $pdo->lastInsertId()
    ];
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && !empty(trim($_POST['message'] ?? ''))
) {
    $pdo->prepare(
        "INSERT INTO live_chat_messages(
            thread_id,
            sender_user_id,
            message
        )
        VALUES (?, ?, ?)"
    )->execute([
        $thread['id'],
        $uid,
        trim($_POST['message'])
    ]);

    $managers = $pdo->query(
        "SELECT id
         FROM users
         WHERE role IN ('manager', 'owner')
         AND status = 'active'"
    )->fetchAll(PDO::FETCH_COLUMN);

    foreach ($managers as $mid) {
        $pdo->prepare(
            "INSERT INTO notifications(
                user_id,
                notification_type,
                title,
                message,
                link_url,
                reference_type,
                reference_id
            )
            VALUES (
                ?,
                'support',
                'Customer support message',
                ?,
                'manager/support.php',
                'chat',
                ?
            )"
        )->execute([
            $mid,
            $_SESSION['customer']['name']
                . ' sent a support message',
            $thread['id']
        ]);
    }

    header(
        'Location: ' . url_path('customer/support.php')
    );
    exit;
}

$q = $pdo->prepare(
    "SELECT
        m.*,
        u.name,
        u.role
     FROM live_chat_messages m
     JOIN users u
        ON u.id = m.sender_user_id
     WHERE m.thread_id = ?
     ORDER BY m.id"
);

$q->execute([
    $thread['id']
]);

$rows = $q->fetchAll();

$page_title = 'Support Chat';

require __DIR__ . '/../partials/public_header.php';

?>

<div class="container py-5">

    <div class="page-head">

        <div>

            <h2>
                Customer Support Chat
            </h2>

            <p>
                Ask about products, stock, pre-orders or delivery.
            </p>

        </div>

        <a
            class="btn btn-outline-success"
            href="<?= url_path('shop/index.php') ?>"
        >
            Continue Shopping
        </a>

    </div>

    <div class="chat-shell cardx">

        <div class="chat-messages">

            <?php if (!$rows): ?>

                <div class="chat-empty">
                    Start a conversation with Smart Farm support.
                </div>

            <?php endif; ?>

            <?php foreach ($rows as $r): ?>

                <?php
                $mine = (int) $r['sender_user_id'] === $uid;
                ?>

                <div class="chat-row <?= $mine ? 'mine' : 'team' ?>">

                    <div>

                        <b>
                            <?= $mine
                                ? 'You'
                                : htmlspecialchars($r['name'])
                                    . ' · '
                                    . ucfirst($r['role']) ?>
                        </b>

                        <p>
                            <?= nl2br(
                                htmlspecialchars($r['message'])
                            ) ?>
                        </p>

                        <small>
                            <?= $r['created_at'] ?>
                        </small>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

        <form
            method="post"
            class="chat-compose"
        >

            <textarea
                class="form-control"
                name="message"
                rows="2"
                required
                placeholder="Type your message..."
            ></textarea>

            <button class="btn btn-success">
                Send Message
            </button>

        </form>

    </div>

</div>

<?php require __DIR__ . '/../partials/public_footer.php'; ?>