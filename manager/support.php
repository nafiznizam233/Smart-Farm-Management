<?php

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';

require_role(['manager', 'owner']);

$tid = (int) ($_GET['thread'] ?? 0);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $tid = (int) $_POST['thread_id'];
    $m = trim($_POST['message']);


    if ($m !== '') {

        $pdo->prepare("
            INSERT INTO live_chat_messages (
                thread_id,
                sender_user_id,
                message
            )
            VALUES (?, ?, ?)
        ")->execute([
            $tid,
            $_SESSION['user']['id'],
            $m
        ]);


        $s = $pdo->prepare("
            SELECT customer_user_id
            FROM live_chat_threads
            WHERE id = ?
        ");

        $s->execute([$tid]);


        if ($cid = $s->fetchColumn()) {

            $pdo->prepare("
                INSERT INTO notifications (
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
                    'Support replied',
                    ?,
                    'customer/support.php',
                    'chat',
                    ?
                )
            ")->execute([
                $cid,
                'Smart Farm support replied to your message',
                $tid
            ]);
        }
    }


    header(
        'Location: ' . url_path(
            'manager/support.php?thread=' . $tid
        )
    );

    exit;
}


$threads = $pdo->query("
    SELECT
        t.*,
        u.name,
        u.phone,

        (
            SELECT message
            FROM live_chat_messages
            WHERE thread_id = t.id
            ORDER BY id DESC
            LIMIT 1
        ) last_message

    FROM live_chat_threads t

    JOIN users u
        ON u.id = t.customer_user_id

    ORDER BY t.id DESC
")->fetchAll();


if (!$tid && $threads) {
    $tid = (int) $threads[0]['id'];
}


$messages = [];


if ($tid) {

    $q = $pdo->prepare("
        SELECT
            m.*,
            u.name,
            u.role
        FROM live_chat_messages m
        JOIN users u
            ON u.id = m.sender_user_id
        WHERE m.thread_id = ?
        ORDER BY m.id
    ");

    $q->execute([$tid]);

    $messages = $q->fetchAll();
}


$page_title = 'Customer Support';

require __DIR__ . '/../partials/header.php';

?>

<div class="app-shell">

    <?php
    if (is_owner()) {
        require __DIR__ . '/../partials/admin_sidebar.php';
    } else {
        require __DIR__ . '/../partials/manager_sidebar.php';
    }
    ?>


    <main class="app-main">

        <div class="page-head">

            <div>
                <h2>
                    Customer Support Inbox
                </h2>

                <p>
                    Live-style product and delivery conversations.
                </p>
            </div>

        </div>


        <div class="support-grid">

            <div class="cardx support-list">

                <?php foreach ($threads as $t): ?>

                    <a
                        class="support-thread <?= $tid == $t['id'] ? 'active' : '' ?>"
                        href="?thread=<?= $t['id'] ?>"
                    >

                        <b>
                            <?= htmlspecialchars($t['name']) ?>
                        </b>

                        <small>
                            <?= htmlspecialchars($t['phone'] ?? '') ?>
                        </small>

                        <span>
                            <?= htmlspecialchars(
                                mb_strimwidth(
                                    $t['last_message'] ?? 'No message',
                                    0,
                                    55,
                                    '…'
                                )
                            ) ?>
                        </span>

                    </a>

                <?php endforeach; ?>

            </div>


            <div class="cardx">

                <div class="chat-messages">

                    <?php foreach ($messages as $r): ?>

                        <?php
                        $mine =
                            (int) $r['sender_user_id'] ===
                            (int) $_SESSION['user']['id'];
                        ?>

                        <div class="chat-row <?= $mine ? 'mine' : 'team' ?>">

                            <div>

                                <b>
                                    <?= htmlspecialchars($r['name']) ?>
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


                <?php if ($tid): ?>

                    <form
                        method="post"
                        class="chat-compose"
                    >

                        <input
                            type="hidden"
                            name="thread_id"
                            value="<?= $tid ?>"
                        >

                        <textarea
                            class="form-control"
                            name="message"
                            required
                            placeholder="Reply to customer..."
                        ></textarea>

                        <button class="btn btn-success">
                            Reply
                        </button>

                    </form>

                <?php endif; ?>

            </div>

        </div>

    </main>

</div>