<?php

require __DIR__ . "/../../config/database.php";
require __DIR__ . "/../../config/auth.php";

require_role(['owner', 'manager', 'staff']);

if (is_manager() || is_staff()) {
    require_work_checkin($pdo);
}

require __DIR__ . "/../../config/finance.php";


$msg = $err = '';

$up = __DIR__ . "/../../uploads/claims/";

if (!is_dir($up)) {
    mkdir($up, 0777, true);
}


function claim_doc($up)
{
    if (
        empty($_FILES['document']['name']) ||
        $_FILES['document']['error'] !== UPLOAD_ERR_OK
    ) {
        return null;
    }

    $ext = strtolower(
        pathinfo(
            $_FILES['document']['name'],
            PATHINFO_EXTENSION
        )
    );

    if (!in_array(
        $ext,
        ['jpg', 'jpeg', 'png', 'webp', 'pdf'],
        true
    )) {
        return null;
    }

    $n =
        time() . '_' .
        bin2hex(random_bytes(4)) .
        '.' .
        $ext;

    move_uploaded_file(
        $_FILES['document']['tmp_name'],
        $up . $n
    );

    return $n;
}


if (
    !is_owner() &&
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['submit_claim'])
) {

    try {

        $doc = claim_doc($up);

        if (!$doc) {
            throw new Exception(
                'Bill/receipt document is mandatory.'
            );
        }

        $source = is_manager()
            ? $_POST['payment_source']
            : 'account';


        $pdo->prepare(
            "INSERT INTO expense_claims(
                submitted_by,
                farm_id,
                category,
                title,
                amount,
                expense_date,
                document,
                notes,
                payment_source,
                account_id,
                status
            )
            VALUES(
                ?,?,?,?,?,?,?,?,?,?,
                'pending'
            )"
        )->execute([
            current_user_id(),
            $_POST['farm_id'] ?: null,
            trim($_POST['category']),
            trim($_POST['title']),
            (float) $_POST['amount'],
            $_POST['expense_date'],
            $doc,
            trim($_POST['notes']),
            $source,
            $_POST['account_id'] ?: null
        ]);

        $msg = 'Bill submitted for Owner approval.';

    } catch (Exception $e) {

        $err = $e->getMessage();
    }
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
             FROM expense_claims
             WHERE id=?
             AND status='pending'
             FOR UPDATE"
        );

        $st->execute([$id]);

        $c = $st->fetch();


        if (!$c) {
            throw new Exception(
                'Claim already processed.'
            );
        }


        $amt = (float) $c['amount'];


        if ($c['payment_source'] === 'manager_cash') {

            manager_cash_debit(
                $pdo,
                (int) $c['submitted_by'],
                $amt,
                'expense_claim',
                $id,
                'Approved bill: ' . $c['title']
            );

        } else {

            $account = (int) (
                $_GET['account_id'] ??
                $c['account_id']
            );


            if (!$account) {
                throw new Exception(
                    'Select payment account.'
                );
            }


            account_debit(
                $pdo,
                $account,
                $amt,
                'expense_claim',
                'expense_claim',
                $id,
                'Approved bill: ' . $c['title']
            );


            $pdo->prepare(
                "UPDATE expense_claims
                 SET account_id=?
                 WHERE id=?"
            )->execute([
                $account,
                $id
            ]);
        }


        $pdo->prepare(
            "INSERT INTO expenses(
                farm_id,
                category,
                amount,
                expense_date,
                note,
                claim_id,
                approval_status,
                approved_by,
                approved_at
            )
            VALUES(
                ?,?,?,?,?,
                ?,
                'approved',
                ?,
                NOW()
            )"
        )->execute([
            $c['farm_id'] ?: null,
            $c['category'],
            $amt,
            $c['expense_date'],
            'Approved bill: ' . $c['title'],
            $id,
            current_user_id()
        ]);


        $pdo->prepare(
            "UPDATE expense_claims
             SET
                status='approved',
                approved_by=?,
                approved_at=NOW()
             WHERE id=?"
        )->execute([
            current_user_id(),
            $id
        ]);


        $pdo->commit();

        $msg = 'Bill approved and posted to expense.';

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
        "UPDATE expense_claims
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

    $msg = 'Bill rejected.';
}


$farms = $pdo->query(
    "SELECT
        id,
        name
     FROM farms
     WHERE status='active'"
)->fetchAll();


$accounts = $pdo->query(
    "SELECT
        id,
        account_name,
        balance
     FROM accounts
     WHERE status='active'"
)->fetchAll();


if (is_owner()) {

    $rows = $pdo->query(
        "SELECT
            c.*,
            u.name submitter,
            f.name farm_name,
            a.account_name
         FROM expense_claims c
         JOIN users u
            ON u.id=c.submitted_by
         LEFT JOIN farms f
            ON f.id=c.farm_id
         LEFT JOIN accounts a
            ON a.id=c.account_id
         ORDER BY c.id DESC"
    )->fetchAll();

} else {

    $st = $pdo->prepare(
        "SELECT
            c.*,
            u.name submitter,
            f.name farm_name,
            a.account_name
         FROM expense_claims c
         JOIN users u
            ON u.id=c.submitted_by
         LEFT JOIN farms f
            ON f.id=c.farm_id
         LEFT JOIN accounts a
            ON a.id=c.account_id
         WHERE c.submitted_by=?
         ORDER BY c.id DESC"
    );

    $st->execute([
        current_user_id()
    ]);

    $rows = $st->fetchAll();
}


$page_title = 'Bill / Expense Claims';

require __DIR__ . "/../../partials/header.php";

?>


<div class="container-fluid">

    <div class="row">

        <?php

        if (is_owner()) {

            require __DIR__ . "/../../partials/admin_sidebar.php";

        } elseif (is_manager()) {

            require __DIR__ . "/../../partials/manager_sidebar.php";
        }

        ?>


        <div class="<?= is_staff() ? 'container py-4' : 'col-md-10 p-4' ?>">

            <div class="hero-top mb-4">

                <h2>
                    Bill / Expense Approval
                </h2>

                <p class="mb-0">
                    Staff/Manager submit document.
                    Only Owner can approve financial posting.
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


            <?php if (!is_owner()): ?>

                <div class="cardx p-3 mb-4">

                    <form
                        method="post"
                        enctype="multipart/form-data"
                        class="row g-2"
                    >

                        <input
                            type="hidden"
                            name="submit_claim"
                            value="1"
                        >


                        <div class="col-md-2">

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


                        <div class="col-md-2">

                            <input
                                class="form-control"
                                name="category"
                                placeholder="Category"
                                required
                            >

                        </div>


                        <div class="col-md-2">

                            <input
                                class="form-control"
                                name="title"
                                placeholder="What was purchased/paid"
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

                            <input
                                class="form-control"
                                type="date"
                                name="expense_date"
                                value="<?= date('Y-m-d') ?>"
                                required
                            >

                        </div>


                        <div class="col-md-2">

                            <input
                                class="form-control"
                                type="file"
                                name="document"
                                required
                            >

                        </div>


                        <?php if (is_manager()): ?>

                            <div class="col-md-3">

                                <select
                                    class="form-select"
                                    name="payment_source"
                                >

                                    <option value="manager_cash">
                                        My Approved Cash
                                    </option>

                                    <option value="account">
                                        Farm Account
                                    </option>

                                </select>

                            </div>

                        <?php else: ?>

                            <input
                                type="hidden"
                                name="payment_source"
                                value="account"
                            >

                        <?php endif; ?>


                        <div class="col-md-3">

                            <select
                                class="form-select"
                                name="account_id"
                            >

                                <option value="">
                                    Account selected by Owner if needed
                                </option>

                                <?php foreach ($accounts as $a): ?>

                                    <option value="<?= $a['id'] ?>">
                                        <?= htmlspecialchars(
                                            $a['account_name']
                                        ) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="col-md-4">

                            <input
                                class="form-control"
                                name="notes"
                                placeholder="Notes"
                            >

                        </div>


                        <div class="col-md-2">

                            <button class="btn btn-success w-100">
                                Submit Bill
                            </button>

                        </div>

                    </form>

                </div>

            <?php endif; ?>


            <div class="cardx p-3">

                <table class="table">

                    <thead>

                        <tr>

                            <th>By</th>
                            <th>Date</th>
                            <th>Farm</th>
                            <th>Bill</th>
                            <th>Amount</th>
                            <th>Document</th>
                            <th>Status</th>

                            <?php if (is_owner()): ?>

                                <th>Owner Action</th>

                            <?php endif; ?>

                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($rows as $r): ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars(
                                        $r['submitter']
                                    ) ?>
                                </td>


                                <td>
                                    <?= $r['expense_date'] ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars(
                                        $r['farm_name'] ?? '-'
                                    ) ?>
                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $r['title']
                                    ) ?>

                                    <div class="small-muted">
                                        <?= htmlspecialchars(
                                            $r['category']
                                        ) ?>
                                    </div>

                                </td>


                                <td>
                                    ৳<?= number_format(
                                        $r['amount'],
                                        2
                                    ) ?>
                                </td>


                                <td>

                                    <a
                                        target="_blank"
                                        href="/smart_farm_php_v7/uploads/claims/<?= htmlspecialchars($r['document']) ?>"
                                    >
                                        View Document
                                    </a>

                                </td>


                                <td>
                                    <?= $r['status'] ?>
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


                                                <?php if ($r['payment_source'] === 'account'): ?>

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

                                                <?php endif; ?>


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