<?php

require __DIR__ . "/../../config/database.php";
require __DIR__ . "/../../config/auth.php";

require_role(['owner', 'manager']);

if (is_manager() || is_staff()) {
    require_work_checkin($pdo);
}

require __DIR__ . "/../../config/finance.php";

$msg = $err = '';


if (
    is_manager() &&
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['request_cash'])
) {

    $pdo->prepare(
        "INSERT INTO manager_cash_requests(
            manager_user_id,
            amount,
            purpose,
            farm_id,
            status
        )
        VALUES(?,?,?,?,'pending')"
    )->execute([
        current_user_id(),
        (float) $_POST['amount'],
        trim($_POST['purpose']),
        $_POST['farm_id'] ?: null
    ]);

    $msg = 'Cash request sent to Owner.';
}


if (
    is_owner() &&
    isset($_GET['approve'])
) {

    try {

        $pdo->beginTransaction();

        $id = (int) $_GET['approve'];

        $st = $pdo->prepare(
            "SELECT *
             FROM manager_cash_requests
             WHERE id=?
             AND status='pending'
             FOR UPDATE"
        );

        $st->execute([$id]);

        $r = $st->fetch();

        if (!$r) {
            throw new Exception(
                'Request not found or already processed.'
            );
        }

        $acct = (int) ($_GET['account_id'] ?? 0);

        if (!$acct) {
            throw new Exception(
                'Select a Cash account.'
            );
        }

        $st = $pdo->prepare(
            "SELECT account_type
             FROM accounts
             WHERE id=?"
        );

        $st->execute([$acct]);

        if ($st->fetchColumn() !== 'cash') {
            throw new Exception(
                'Cash request must be funded from a Cash account.'
            );
        }

        account_debit(
            $pdo,
            $acct,
            (float) $r['amount'],
            'manager_cash_handover',
            'cash_request',
            $id,
            'Cash handed to manager'
        );

        manager_cash_credit(
            $pdo,
            (int) $r['manager_user_id'],
            (float) $r['amount'],
            'cash_request',
            $id,
            'Owner approved cash request'
        );

        $pdo->prepare(
            "UPDATE manager_cash_requests
             SET
                source_account_id=?,
                status='approved',
                approved_by=?,
                approved_at=NOW()
             WHERE id=?"
        )->execute([
            $acct,
            current_user_id(),
            $id
        ]);

        $pdo->commit();

        $msg = 'Cash request approved.';

    } catch (Exception $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $err = $e->getMessage();
    }
}


if (
    is_owner() &&
    isset($_GET['reject'])
) {

    $pdo->prepare(
        "UPDATE manager_cash_requests
         SET
            status='rejected',
            approved_by=?,
            approved_at=NOW(),
            rejection_reason='Rejected by Owner'
         WHERE id=?
         AND status='pending'"
    )->execute([
        current_user_id(),
        (int) $_GET['reject']
    ]);

    $msg = 'Request rejected.';
}


$farms = $pdo->query(
    "SELECT
        id,
        name
     FROM farms
     WHERE status='active'"
)->fetchAll();


$cash = $pdo->query(
    "SELECT
        id,
        account_name,
        balance
     FROM accounts
     WHERE status='active'
     AND account_type='cash'"
)->fetchAll();


if (is_owner()) {

    $rows = $pdo->query(
        "SELECT
            r.*,
            u.name manager_name,
            f.name farm_name,
            a.account_name
         FROM manager_cash_requests r
         JOIN users u
            ON u.id=r.manager_user_id
         LEFT JOIN farms f
            ON f.id=r.farm_id
         LEFT JOIN accounts a
            ON a.id=r.source_account_id
         ORDER BY r.id DESC"
    )->fetchAll();

} else {

    $st = $pdo->prepare(
        "SELECT
            r.*,
            f.name farm_name,
            a.account_name
         FROM manager_cash_requests r
         LEFT JOIN farms f
            ON f.id=r.farm_id
         LEFT JOIN accounts a
            ON a.id=r.source_account_id
         WHERE r.manager_user_id=?
         ORDER BY r.id DESC"
    );

    $st->execute([
        current_user_id()
    ]);

    $rows = $st->fetchAll();
}


$page_title = 'Cash Requests';

require __DIR__ . "/../../partials/header.php";

?>


<div class="container-fluid">

    <div class="row">

        <?php

        if (is_owner()) {
            require __DIR__ . "/../../partials/admin_sidebar.php";
        } else {
            require __DIR__ . "/../../partials/manager_sidebar.php";
        }

        ?>


        <div class="col-md-10 p-4">

            <div class="hero-top mb-4">

                <h2>
                    Manager Cash Requests
                </h2>

                <p class="mb-0">
                    Manager cannot take office cash without Owner approval.
                </p>

            </div>


            <?php if ($msg): ?>

                <div class="alert alert-success">
                    <?= $msg ?>
                </div>

            <?php endif; ?>


            <?php if ($err): ?>

                <div class="alert alert-danger">
                    <?= $err ?>
                </div>

            <?php endif; ?>


            <?php if (is_manager()): ?>

                <div class="cardx p-3 mb-4">

                    <form
                        method="post"
                        class="row g-2"
                    >

                        <input
                            type="hidden"
                            name="request_cash"
                            value="1"
                        >


                        <div class="col-md-3">

                            <input
                                class="form-control"
                                type="number"
                                step=".01"
                                name="amount"
                                placeholder="Cash amount"
                                required
                            >

                        </div>


                        <div class="col-md-3">

                            <select
                                class="form-select"
                                name="farm_id"
                            >

                                <option value="">
                                    General
                                </option>


                                <?php foreach ($farms as $f): ?>

                                    <option value="<?= $f['id'] ?>">
                                        <?= htmlspecialchars($f['name']) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="col-md-4">

                            <input
                                class="form-control"
                                name="purpose"
                                placeholder="Purpose / why cash is needed"
                                required
                            >

                        </div>


                        <div class="col-md-2">

                            <button class="btn btn-success w-100">
                                Request Owner
                            </button>

                        </div>

                    </form>

                </div>

            <?php endif; ?>


            <div class="cardx p-3">

                <table class="table">

                    <thead>

                        <tr>

                            <th>Manager</th>
                            <th>Amount</th>
                            <th>Purpose</th>
                            <th>Farm</th>
                            <th>Status</th>
                            <th>Account</th>

                            <?php if (is_owner()): ?>

                                <th>Approval</th>

                            <?php endif; ?>

                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($rows as $r): ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars(
                                        $r['manager_name'] ??
                                        $_SESSION['user']['name']
                                    ) ?>
                                </td>


                                <td>
                                    ৳<?= number_format(
                                        $r['amount'],
                                        2
                                    ) ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars(
                                        $r['purpose']
                                    ) ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars(
                                        $r['farm_name'] ?? '-'
                                    ) ?>
                                </td>


                                <td>
                                    <?= $r['status'] ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars(
                                        $r['account_name'] ?? '-'
                                    ) ?>
                                </td>


                                <?php if (is_owner()): ?>

                                    <td>

                                        <?php if ($r['status'] === 'pending'): ?>

                                            <form
                                                method="get"
                                                class="d-flex gap-1"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="approve"
                                                    value="<?= $r['id'] ?>"
                                                >


                                                <select
                                                    class="form-select form-select-sm"
                                                    name="account_id"
                                                    required
                                                >

                                                    <option value="">
                                                        Cash account
                                                    </option>


                                                    <?php foreach ($cash as $a): ?>

                                                        <option
                                                            value="<?= $a['id'] ?>"
                                                        >
                                                            <?= htmlspecialchars(
                                                                $a['account_name']
                                                            ) ?>
                                                            (৳<?= number_format(
                                                                $a['balance'],
                                                                0
                                                            ) ?>)
                                                        </option>

                                                    <?php endforeach; ?>

                                                </select>


                                                <button
                                                    class="btn btn-sm btn-success"
                                                >
                                                    Approve
                                                </button>


                                                <a
                                                    class="btn btn-sm btn-danger"
                                                    href="?reject=<?= $r['id'] ?>"
                                                >
                                                    Reject
                                                </a>

                                            </form>

                                        <?php endif; ?>

                                    </td>

                                <?php endif; ?>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>