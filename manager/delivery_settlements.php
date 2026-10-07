<?php

require_once __DIR__ . '/../delivery_common.php';

$uid = dc_uid();

if (!$uid) {
    die('Login required');
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = (int) $_POST['id'];

    $st = $_POST['decision'] === 'verify'
        ? 'verified'
        : 'rejected';

    $pdo->beginTransaction();

    try {

        $pdo->prepare("
            UPDATE delivery_settlements
            SET
                status = ?,
                manager_user_id = ?,
                verified_at = IF(? = 'verified', NOW(), NULL)
            WHERE id = ?
              AND status = 'submitted'
        ")->execute([
            $st,
            $uid,
            $st,
            $id
        ]);


        if ($st === 'verified') {

            $q = $pdo->prepare("
                SELECT delivery_user_id
                FROM delivery_settlements
                WHERE id = ?
            ");

            $q->execute([$id]);

            $du = (int) $q->fetchColumn();


            $pdo->prepare("
                UPDATE delivery_collections
                SET
                    handover_status = 'verified',
                    verified_at = NOW()
                WHERE delivery_user_id = ?
                  AND handover_status = 'submitted'
            ")->execute([$du]);
        }


        $pdo->commit();

    } catch (Throwable $e) {

        $pdo->rollBack();
    }
}


$rows = $pdo->query("
    SELECT
        s.*,
        u.name
    FROM delivery_settlements s
    JOIN users u
        ON u.id = s.delivery_user_id
    ORDER BY s.id DESC
")->fetchAll();

?>

<!doctype html>

<html>
<head>

    <meta charset="utf-8">

    <title>
        Delivery Settlements
    </title>

    <style>
        body {
            font-family: Segoe UI;
            background: #f5f7f6;
        }

        .w {
            max-width: 1000px;
            margin: 30px auto;
        }

        .c {
            background: #fff;
            border: 1px solid #dce7e1;
            padding: 16px;
            border-radius: 13px;
            margin: 10px;
        }

        button {
            padding: 8px 12px;
            border: 0;
            border-radius: 7px;
            background: #176b4b;
            color: #fff;
        }
    </style>

</head>

<body>

    <div class="w">

        <a href="<?= htmlspecialchars(dc_back()) ?>">
            ← Back
        </a>

        <h2>
            Delivery Collection Settlements
        </h2>


        <?php foreach ($rows as $r): ?>

            <div class="c">

                <b>
                    <?= htmlspecialchars($r['name']) ?>
                </b>

                · Collection ৳<?= $r['collection_total'] ?>

                · Retained ৳<?= $r['retained_delivery_earning'] ?>

                · Forwarded ৳<?= $r['amount_forwarded'] ?>

                · <?= htmlspecialchars($r['method']) ?>

                · <b><?= $r['status'] ?></b>


                <?php if ($r['status'] === 'submitted'): ?>

                    <form
                        method="post"
                        style="display:inline"
                    >

                        <input
                            type="hidden"
                            name="id"
                            value="<?= $r['id'] ?>"
                        >

                        <button
                            name="decision"
                            value="verify"
                        >
                            Verify
                        </button>

                        <button
                            name="decision"
                            value="reject"
                        >
                            Reject
                        </button>

                    </form>

                <?php endif; ?>

            </div>

        <?php endforeach; ?>

    </div>

</body>
</html>