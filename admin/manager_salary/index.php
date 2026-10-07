<?php

require __DIR__ . "/../../config/database.php";
require __DIR__ . "/../../config/auth.php";

require_role(['owner']);

require __DIR__ . "/../../config/finance.php";


$msg = $err = '';


if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['create_salary'])
) {

    try {

        $pdo->prepare(
            "INSERT INTO manager_salary_requests(
                manager_user_id,
                payment_month,
                amount,
                account_id,
                status,
                note
            )
            VALUES(
                ?,?,?,?,
                'pending',
                ?
            )"
        )->execute([
            (int) $_POST['manager_user_id'],
            $_POST['payment_month'],
            (float) $_POST['amount'],
            $_POST['account_id'] ?: null,
            trim($_POST['note'])
        ]);


        $msg = 'Manager salary added to pending approval.';

    } catch (Exception $e) {

        $err = 'Salary for this manager/month may already exist.';
    }
}


if (isset($_GET['approve'])) {

    try {

        $pdo->beginTransaction();


        $id = (int) $_GET['approve'];


        $st = $pdo->prepare(
            "SELECT *
             FROM manager_salary_requests
             WHERE id=?
             AND status='pending'
             FOR UPDATE"
        );

        $st->execute([$id]);

        $r = $st->fetch();


        if (!$r) {
            throw new Exception(
                'Already processed.'
            );
        }


        $acct = (int) (
            $_GET['account_id'] ??
            $r['account_id']
        );


        if (!$acct) {
            throw new Exception(
                'Select payment account.'
            );
        }


        account_debit(
            $pdo,
            $acct,
            (float) $r['amount'],
            'manager_salary',
            'manager_salary',
            $id,
            'Manager salary ' . $r['payment_month']
        );


        $pdo->prepare(
            "UPDATE manager_salary_requests
             SET
                account_id=?,
                status='paid',
                approved_by=?,
                approved_at=NOW(),
                paid_at=NOW()
             WHERE id=?"
        )->execute([
            $acct,
            current_user_id(),
            $id
        ]);


        $pdo->commit();

        $msg = 'Manager salary approved and paid.';

    } catch (Exception $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $err = $e->getMessage();
    }
}


if (isset($_GET['reject'])) {

    $pdo->prepare(
        "UPDATE manager_salary_requests
         SET
            status='rejected',
            approved_by=?,
            approved_at=NOW()
         WHERE id=?
         AND status='pending'"
    )->execute([
        current_user_id(),
        (int) $_GET['reject']
    ]);


    $msg = 'Salary request rejected.';
}


$mgr = $pdo->query(
    "SELECT
        id,
        name,
        email
     FROM users
     WHERE role='manager'
     AND status='active'
     ORDER BY name"
)->fetchAll();


$accounts = $pdo->query(
    "SELECT
        id,
        account_name,
        balance
     FROM accounts
     WHERE status='active'"
)->fetchAll();


$rows = $pdo->query(
    "SELECT
        r.*,
        u.name manager_name,
        a.account_name
     FROM manager_salary_requests r
     JOIN users u
        ON u.id=r.manager_user_id
     LEFT JOIN accounts a
        ON a.id=r.account_id
     ORDER BY r.id DESC"
)->fetchAll();


$page_title = 'Manager Salary';

require __DIR__ . "/../../partials/header.php";

?>


<div class="container-fluid">

    <div class="row">

        <?php
        require __DIR__ . "/../../partials/admin_sidebar.php";
        ?>


        <div class="col-md-10 p-4">

            <div class="hero-top mb-4">

                <h2>
                    Manager Salary Approval
                </h2>

                <p class="mb-0">
                    Manager salary is controlled only from Owner side.
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


            <div class="cardx p-3 mb-4">

                <form
                    method="post"
                    class="row g-2"
                >

                    <input
                        type="hidden"
                        name="create_salary"
                        value="1"
                    >


                    <div class="col-md-3">

                        <select
                            class="form-select"
                            name="manager_user_id"
                            required
                        >

                            <option value="">
                                Manager
                            </option>


                            <?php foreach ($mgr as $m): ?>

                                <option value="<?= $m['id'] ?>">
                                    <?= htmlspecialchars($m['name']) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="col-md-2">

                        <input
                            class="form-control"
                            type="month"
                            name="payment_month"
                            value="<?= date('Y-m') ?>"
                            required
                        >

                    </div>


                    <div class="col-md-2">

                        <input
                            class="form-control"
                            type="number"
                            step=".01"
                            name="amount"
                            placeholder="Salary"
                            required
                        >

                    </div>


                    <div class="col-md-3">

                        <select
                            class="form-select"
                            name="account_id"
                        >

                            <option value="">
                                Select at approval
                            </option>


                            <?php foreach ($accounts as $a): ?>

                                <option value="<?= $a['id'] ?>">
                                    <?= htmlspecialchars($a['account_name']) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="col-md-2">

                        <button class="btn btn-success w-100">
                            Create Pending
                        </button>

                    </div>


                    <div class="col-md-12">

                        <input
                            class="form-control"
                            name="note"
                            placeholder="Note"
                        >

                    </div>

                </form>

            </div>


            <div class="cardx p-3">

                <table class="table">

                    <thead>

                        <tr>
                            <th>Manager</th>
                            <th>Month</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Account</th>
                            <th>Action</th>
                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($rows as $r): ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars(
                                        $r['manager_name']
                                    ) ?>
                                </td>


                                <td>
                                    <?= $r['payment_month'] ?>
                                </td>


                                <td>
                                    ৳<?= number_format(
                                        $r['amount'],
                                        2
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
                                                    Account
                                                </option>


                                                <?php foreach ($accounts as $a): ?>

                                                    <option
                                                        value="<?= $a['id'] ?>"
                                                        <?= $r['account_id'] == $a['id']
                                                            ? 'selected'
                                                            : ''
                                                        ?>
                                                    >

                                                        <?= htmlspecialchars(
                                                            $a['account_name']
                                                        ) ?>

                                                        ৳<?= number_format(
                                                            $a['balance'],
                                                            0
                                                        ) ?>

                                                    </option>

                                                <?php endforeach; ?>

                                            </select>


                                            <button
                                                class="btn btn-sm btn-success"
                                            >
                                                Approve & Pay
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

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>