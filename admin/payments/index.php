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
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['request_payment'])
) {

    $pdo->prepare(
        "INSERT INTO staff_payments(
            staff_id,
            amount,
            payment_month,
            payment_method,
            status,
            requested_by,
            account_id,
            note
        )
        VALUES(
            ?,?,?,?,
            'pending',
            ?,?,?
        )"
    )->execute([
        (int) $_POST['staff_id'],
        (float) $_POST['amount'],
        $_POST['payment_month'],
        $_POST['payment_method'],
        current_user_id(),
        $_POST['account_id'] ?: null,
        trim($_POST['note'])
    ]);


    $id = (int) $pdo->lastInsertId();


    $pdo->prepare(
        "INSERT INTO approval_log(
            module_name,
            reference_id,
            action,
            action_by,
            note
        )
        VALUES(
            'staff_payment',
            ?,
            'submitted',
            ?,
            'Staff payment request submitted'
        )"
    )->execute([
        $id,
        current_user_id()
    ]);


    $msg = is_owner()
        ? 'Payment request created. It is now Pending.'
        : 'Staff payment request sent to Owner.';
}


if (
    is_owner() &&
    isset($_GET['approve'])
) {

    try {

        $pdo->beginTransaction();


        $r = approve_staff_payment(
            $pdo,
            (int) $_GET['approve'],
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


if (
    is_owner() &&
    isset($_GET['reject'])
) {

    try {

        $pdo->beginTransaction();


        $ok = reject_staff_payment(
            $pdo,
            (int) $_GET['reject'],
            'Rejected by Owner'
        );


        $pdo->commit();


        $msg = $ok
            ? 'Payment rejected.'
            : 'Payment was already processed.';

    } catch (Exception $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $err = $e->getMessage();
    }
}


$staff = $pdo->query(
    "SELECT
        s.id,
        u.name,
        s.monthly_salary,
        s.preferred_payment_method
     FROM staff s
     JOIN users u
        ON u.id=s.user_id
     WHERE s.status='active'
     ORDER BY u.name"
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
        p.*,
        u.name staff_name,
        ru.name requester,
        a.account_name,
        au.name approver
     FROM staff_payments p
     JOIN staff s
        ON s.id=p.staff_id
     JOIN users u
        ON u.id=s.user_id
     LEFT JOIN users ru
        ON ru.id=p.requested_by
     LEFT JOIN users au
        ON au.id=p.approved_by
     LEFT JOIN accounts a
        ON a.id=p.account_id
     ORDER BY p.id DESC
     LIMIT 100"
)->fetchAll();


$page_title = 'Staff Payroll';

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
                    Staff Payment
                </h2>

                <p class="mb-0">
                    One request has one master status. Approving once updates the same record everywhere.
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
                        name="request_payment"
                        value="1"
                    >


                    <div class="col-md-3">

                        <select
                            class="form-select"
                            name="staff_id"
                            required
                        >

                            <option value="">
                                Select Staff
                            </option>


                            <?php foreach ($staff as $s): ?>

                                <option value="<?= $s['id'] ?>">

                                    <?= htmlspecialchars($s['name']) ?>

                                    — Salary ৳<?= number_format(
                                        $s['monthly_salary'],
                                        0
                                    ) ?>

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
                            placeholder="Amount"
                            required
                        >

                    </div>


                    <div class="col-md-2">

                        <select
                            class="form-select"
                            name="payment_method"
                        >

                            <option value="cash">
                                Cash
                            </option>

                            <option value="mfs">
                                MFS
                            </option>

                            <option value="bank">
                                Bank
                            </option>

                        </select>

                    </div>


                    <div class="col-md-2">

                        <select
                            class="form-select"
                            name="account_id"
                        >

                            <option value="">
                                Owner chooses at approval
                            </option>


                            <?php foreach ($accounts as $a): ?>

                                <option value="<?= $a['id'] ?>">
                                    <?= htmlspecialchars($a['account_name']) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="col-md-1">

                        <button class="btn btn-success w-100">

                            <?= is_manager()
                                ? 'Request'
                                : 'Create'
                            ?>

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

                            <th>Staff</th>
                            <th>Month</th>
                            <th>Amount</th>
                            <th>Requested By</th>
                            <th>Status</th>
                            <th>Account</th>
                            <th>Approved By</th>

                            <?php if (is_owner()): ?>
                                <th>Action</th>
                            <?php endif; ?>

                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($rows as $r): ?>

                            <?php
                            $display = payment_status_label(
                                $r['status']
                            );
                            ?>

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

                                    <span
                                        class="badge <?= $display === 'approved'
                                            ? 'text-bg-success'
                                            : (
                                                $display === 'pending'
                                                    ? 'text-bg-warning'
                                                    : 'text-bg-secondary'
                                            )
                                        ?>"
                                    >
                                        <?= htmlspecialchars(
                                            ucfirst($display)
                                        ) ?>
                                    </span>

                                </td>


                                <td>
                                    <?= htmlspecialchars(
                                        $r['account_name'] ?? '-'
                                    ) ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars(
                                        $r['approver'] ?? '-'
                                    ) ?>
                                </td>


                                <?php if (is_owner()): ?>

                                    <td>

                                        <?php if ($display === 'pending'): ?>

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
                                                    Approve
                                                </button>


                                                <a
                                                    class="btn btn-sm btn-danger"
                                                    href="?reject=<?= $r['id'] ?>"
                                                >
                                                    Reject
                                                </a>

                                            </form>

                                        <?php else: ?>

                                            <span class="text-muted">
                                                Final
                                            </span>

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