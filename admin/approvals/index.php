<?php

require __DIR__ . "/../../config/database.php";
require __DIR__ . "/../../config/auth.php";

require_role(['owner']);

require __DIR__ . "/../../config/finance.php";


$msg = $err = '';


if (isset($_GET['approve_payment'])) {

    try {

        $pdo->beginTransaction();

        $r = approve_staff_payment(
            $pdo,
            (int) $_GET['approve_payment'],
            (int) ($_GET['account_id'] ?? 0)
        );

        $pdo->commit();

        $msg = $r['message'];

    } catch (Exception $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $err = $e->getMessage();
    }
}


$p = $pdo->query(
    "SELECT COUNT(*)
     FROM purchases
     WHERE status='pending'"
)->fetchColumn();


$c = $pdo->query(
    "SELECT COUNT(*)
     FROM manager_cash_requests
     WHERE status='pending'"
)->fetchColumn();


$e = $pdo->query(
    "SELECT COUNT(*)
     FROM expense_claims
     WHERE status='pending'"
)->fetchColumn();


$s = $pdo->query(
    "SELECT COUNT(*)
     FROM staff_payments
     WHERE status='pending'"
)->fetchColumn();


$m = $pdo->query(
    "SELECT COUNT(*)
     FROM manager_salary_requests
     WHERE status='pending'"
)->fetchColumn();


$accounts = $pdo->query(
    "SELECT
        id,
        account_name,
        balance
     FROM accounts
     WHERE status='active'"
)->fetchAll();


$pendingPayments = $pdo->query(
    "SELECT
        p.id,
        p.amount,
        p.payment_month,
        u.name staff_name,
        ru.name requester
     FROM staff_payments p
     JOIN staff st
        ON st.id=p.staff_id
     JOIN users u
        ON u.id=st.user_id
     LEFT JOIN users ru
        ON ru.id=p.requested_by
     WHERE p.status='pending'
     ORDER BY p.id DESC
     LIMIT 20"
)->fetchAll();


$page_title = 'Approvals';

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
                    Owner Approval Center
                </h2>

                <p class="mb-0">
                    Single-source approval: approve here or inside a module—the
                    same database record updates everywhere.
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


            <div class="row g-3 mb-4">

                <div class="col-md-4">

                    <a
                        class="text-decoration-none"
                        href="../purchases/index.php"
                    >

                        <div class="cardx p-4">

                            <div class="small-muted">
                                Pending Purchases
                            </div>

                            <div class="metric">
                                <?= $p ?>
                            </div>

                        </div>

                    </a>

                </div>


                <div class="col-md-4">

                    <a
                        class="text-decoration-none"
                        href="../cash_requests/index.php"
                    >

                        <div class="cardx p-4">

                            <div class="small-muted">
                                Cash Requests
                            </div>

                            <div class="metric">
                                <?= $c ?>
                            </div>

                        </div>

                    </a>

                </div>


                <div class="col-md-4">

                    <a
                        class="text-decoration-none"
                        href="../expense_claims/index.php"
                    >

                        <div class="cardx p-4">

                            <div class="small-muted">
                                Bill Claims
                            </div>

                            <div class="metric">
                                <?= $e ?>
                            </div>

                        </div>

                    </a>

                </div>


                <div class="col-md-4">

                    <a
                        class="text-decoration-none"
                        href="../payments/index.php"
                    >

                        <div class="cardx p-4">

                            <div class="small-muted">
                                Staff Payments
                            </div>

                            <div class="metric">
                                <?= $s ?>
                            </div>

                        </div>

                    </a>

                </div>


                <div class="col-md-4">

                    <a
                        class="text-decoration-none"
                        href="../manager_salary/index.php"
                    >

                        <div class="cardx p-4">

                            <div class="small-muted">
                                Manager Salary
                            </div>

                            <div class="metric">
                                <?= $m ?>
                            </div>

                        </div>

                    </a>

                </div>


                <div class="col-md-4">

                    <div class="cardx p-4">

                        <div class="small-muted">
                            Total Pending
                        </div>

                        <div class="metric">
                            <?= $p + $c + $e + $s + $m ?>
                        </div>

                    </div>

                </div>

            </div>


            <div class="cardx p-3">

                <h5>
                    Pending Staff Payments — Quick Approval
                </h5>


                <table class="table">

                    <thead>

                        <tr>
                            <th>Staff</th>
                            <th>Month</th>
                            <th>Amount</th>
                            <th>Requested By</th>
                            <th>Approve</th>
                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($pendingPayments as $r): ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars(
                                        $r['staff_name']
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
                                    <?= htmlspecialchars(
                                        $r['requester'] ?? '-'
                                    ) ?>
                                </td>


                                <td>

                                    <form
                                        method="get"
                                        class="d-flex gap-1"
                                    >

                                        <input
                                            type="hidden"
                                            name="approve_payment"
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
                                            Approve
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endforeach; ?>


                        <?php if (!$pendingPayments): ?>

                            <tr>

                                <td
                                    colspan="5"
                                    class="text-center text-muted"
                                >
                                    No pending staff payments.
                                </td>

                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>