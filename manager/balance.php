<?php

require __DIR__ . "/../config/database.php";
require __DIR__ . "/../config/auth.php";

require_role(['manager']);

require __DIR__ . "/../config/finance.php";

$balance = manager_cash_balance(
    $pdo,
    current_user_id()
);

$st = $pdo->prepare("
    SELECT *
    FROM manager_cash_ledger
    WHERE manager_user_id = ?
    ORDER BY id DESC
    LIMIT 100
");

$st->execute([
    current_user_id()
]);

$rows = $st->fetchAll();

$page_title = 'My Cash Balance';

require __DIR__ . "/../partials/header.php";
?>

<div class="container-fluid">
    <div class="row">

        <?php require __DIR__ . "/../partials/manager_sidebar.php"; ?>

        <div class="col-md-10 p-4">

            <div class="hero-top mb-4">
                <h2>My Cash Balance</h2>

                <p class="mb-0">
                    Cash approved and handed over by Owner minus approved spending.
                </p>
            </div>

            <div class="cardx p-4 mb-4">
                <div class="small-muted">
                    Cash Currently With Me
                </div>

                <div class="metric">
                    ৳<?= number_format($balance, 2) ?>
                </div>

                <a
                    class="btn btn-success mt-2"
                    href="<?= url_path('admin/cash_requests/index.php') ?>"
                >
                    Request More Cash
                </a>
            </div>

            <div class="cardx p-3">
                <table class="table">

                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Direction</th>
                            <th>Amount</th>
                            <th>Reference</th>
                            <th>Note</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td>
                                    <?= $r['created_at'] ?>
                                </td>

                                <td>
                                    <?= $r['direction'] ?>
                                </td>

                                <td>
                                    ৳<?= number_format($r['amount'], 2) ?>
                                </td>

                                <td>
                                    <?= $r['reference_type'] ?>
                                    #<?= $r['reference_id'] ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($r['note']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            </div>

        </div>
    </div>
</div>