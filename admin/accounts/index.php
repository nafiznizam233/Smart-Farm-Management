<?php

require __DIR__ . "/../../config/database.php";
require __DIR__ . "/../../config/auth.php";

require_role(['owner']);

require __DIR__ . "/../../config/finance.php";

$msg = $err = '';


if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['create'])
) {

    $pdo->prepare(
        "INSERT INTO accounts(
            account_name,
            account_type,
            provider,
            account_number,
            balance
        )
        VALUES(?,?,?,?,?)"
    )->execute([
        trim($_POST['account_name']),
        $_POST['account_type'],
        trim($_POST['provider']),
        trim($_POST['account_number']),
        (float) $_POST['opening_balance']
    ]);

    $msg = 'Account created.';
}


if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['adjust'])
) {

    try {

        $pdo->beginTransaction();

        $amt = (float) $_POST['amount'];
        $id = (int) $_POST['account_id'];

        if ($_POST['direction'] === 'CREDIT') {

            account_credit(
                $pdo,
                $id,
                $amt,
                'manual_adjustment',
                'account',
                $id,
                trim($_POST['note'])
            );

        } else {

            account_debit(
                $pdo,
                $id,
                $amt,
                'manual_adjustment',
                'account',
                $id,
                trim($_POST['note'])
            );
        }

        $pdo->commit();

        $msg = 'Balance updated.';

    } catch (Exception $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $err = $e->getMessage();
    }
}


$rows = $pdo->query(
    "SELECT *
     FROM accounts
     ORDER BY account_type, account_name"
)->fetchAll();


$sum = $pdo->query(
    "SELECT
        account_type,
        COALESCE(SUM(balance),0) total
     FROM accounts
     WHERE status='active'
     GROUP BY account_type"
)->fetchAll(PDO::FETCH_KEY_PAIR);


$total = array_sum(
    array_map('floatval', $sum)
);


$tx = $pdo->query(
    "SELECT
        t.*,
        a.account_name,
        u.name creator
     FROM account_transactions t
     JOIN accounts a
        ON a.id=t.account_id
     LEFT JOIN users u
        ON u.id=t.created_by
     ORDER BY t.id DESC
     LIMIT 30"
)->fetchAll();


$page_title = 'Accounts & Balance';

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
                    Accounts & Balance
                </h2>

                <p class="mb-0">
                    Cash + Bank + MFS controlled from
                    Owner profile → Balance.
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

                <div class="col-md-3">

                    <div class="cardx p-3">

                        <div class="small-muted">
                            Total Balance
                        </div>

                        <div class="metric">
                            ৳<?= number_format($total, 2) ?>
                        </div>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="cardx p-3">

                        <div class="small-muted">
                            Cash
                        </div>

                        <div class="metric">
                            ৳<?= number_format(
                                (float) ($sum['cash'] ?? 0),
                                2
                            ) ?>
                        </div>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="cardx p-3">

                        <div class="small-muted">
                            Bank
                        </div>

                        <div class="metric">
                            ৳<?= number_format(
                                (float) ($sum['bank'] ?? 0),
                                2
                            ) ?>
                        </div>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="cardx p-3">

                        <div class="small-muted">
                            MFS
                        </div>

                        <div class="metric">
                            ৳<?= number_format(
                                (float) ($sum['mfs'] ?? 0),
                                2
                            ) ?>
                        </div>

                    </div>

                </div>

            </div>


            <div class="cardx p-3 mb-4">

                <h5>
                    Add Account
                </h5>

                <form
                    method="post"
                    class="row g-2"
                >

                    <input
                        type="hidden"
                        name="create"
                        value="1"
                    >


                    <div class="col-md-3">

                        <input
                            class="form-control"
                            name="account_name"
                            placeholder="Account name"
                            required
                        >

                    </div>


                    <div class="col-md-2">

                        <select
                            class="form-select"
                            name="account_type"
                        >

                            <option value="cash">
                                Cash
                            </option>

                            <option value="bank">
                                Bank
                            </option>

                            <option value="mfs">
                                MFS
                            </option>

                        </select>

                    </div>


                    <div class="col-md-2">

                        <input
                            class="form-control"
                            name="provider"
                            placeholder="Bank/bKash/Cash"
                        >

                    </div>


                    <div class="col-md-2">

                        <input
                            class="form-control"
                            name="account_number"
                            placeholder="Account/number"
                        >

                    </div>


                    <div class="col-md-2">

                        <input
                            class="form-control"
                            type="number"
                            step=".01"
                            name="opening_balance"
                            value="0"
                        >

                    </div>


                    <div class="col-md-1">

                        <button class="btn btn-success w-100">
                            Add
                        </button>

                    </div>

                </form>

            </div>


            <div class="cardx p-3 mb-4">

                <table class="table">

                    <thead>

                        <tr>
                            <th>Account</th>
                            <th>Type</th>
                            <th>Provider</th>
                            <th>Number</th>
                            <th>Balance</th>
                            <th>Adjust</th>
                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($rows as $a): ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars(
                                        $a['account_name']
                                    ) ?>
                                </td>

                                <td>
                                    <?= strtoupper(
                                        $a['account_type']
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $a['provider']
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $a['account_number']
                                    ) ?>
                                </td>

                                <td>

                                    <b>
                                        ৳<?= number_format(
                                            $a['balance'],
                                            2
                                        ) ?>
                                    </b>

                                </td>


                                <td>

                                    <form
                                        method="post"
                                        class="d-flex gap-1"
                                    >

                                        <input
                                            type="hidden"
                                            name="adjust"
                                            value="1"
                                        >

                                        <input
                                            type="hidden"
                                            name="account_id"
                                            value="<?= $a['id'] ?>"
                                        >


                                        <select
                                            class="form-select form-select-sm"
                                            name="direction"
                                        >

                                            <option value="CREDIT">
                                                Add
                                            </option>

                                            <option value="DEBIT">
                                                Withdraw
                                            </option>

                                        </select>


                                        <input
                                            class="form-control form-control-sm"
                                            type="number"
                                            step=".01"
                                            name="amount"
                                            placeholder="Amount"
                                            required
                                        >


                                        <input
                                            class="form-control form-control-sm"
                                            name="note"
                                            placeholder="Note"
                                        >


                                        <button
                                            class="btn btn-sm btn-outline-primary"
                                        >
                                            Save
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>


            <div class="cardx p-3">

                <h5>
                    Recent Transactions
                </h5>


                <table class="table">

                    <thead>

                        <tr>
                            <th>Time</th>
                            <th>Account</th>
                            <th>Direction</th>
                            <th>Amount</th>
                            <th>Type</th>
                            <th>Note</th>
                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($tx as $t): ?>

                            <tr>

                                <td>
                                    <?= $t['created_at'] ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $t['account_name']
                                    ) ?>
                                </td>

                                <td>
                                    <?= $t['direction'] ?>
                                </td>

                                <td>
                                    ৳<?= number_format(
                                        $t['amount'],
                                        2
                                    ) ?>
                                </td>

                                <td>
                                    <?= $t['transaction_type'] ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $t['note']
                                    ) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>