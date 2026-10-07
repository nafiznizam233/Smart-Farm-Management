<?php

function account_debit(
    PDO $pdo,
    int $accountId,
    float $amount,
    string $type,
    string $refType,
    int $refId,
    string $note = ''
) {
    $st = $pdo->prepare(
        "SELECT balance, status
         FROM accounts
         WHERE id = ?
         FOR UPDATE"
    );

    $st->execute([$accountId]);
    $a = $st->fetch();

    if (!$a || $a['status'] !== 'active') {
        throw new Exception('Payment account is not active.');
    }

    if ((float) $a['balance'] < $amount) {
        throw new Exception('Insufficient account balance.');
    }

    $pdo->prepare(
        "UPDATE accounts
         SET balance = balance - ?
         WHERE id = ?"
    )->execute([
        $amount,
        $accountId
    ]);

    $pdo->prepare(
        "INSERT INTO account_transactions(
            account_id,
            direction,
            amount,
            transaction_type,
            reference_type,
            reference_id,
            note,
            created_by
        )
        VALUES (?, 'DEBIT', ?, ?, ?, ?, ?, ?)"
    )->execute([
        $accountId,
        $amount,
        $type,
        $refType,
        $refId,
        $note,
        current_user_id()
    ]);
}

function account_credit(
    PDO $pdo,
    int $accountId,
    float $amount,
    string $type,
    string $refType,
    int $refId,
    string $note = ''
) {
    $pdo->prepare(
        "UPDATE accounts
         SET balance = balance + ?
         WHERE id = ?
         AND status = 'active'"
    )->execute([
        $amount,
        $accountId
    ]);

    $pdo->prepare(
        "INSERT INTO account_transactions(
            account_id,
            direction,
            amount,
            transaction_type,
            reference_type,
            reference_id,
            note,
            created_by
        )
        VALUES (?, 'CREDIT', ?, ?, ?, ?, ?, ?)"
    )->execute([
        $accountId,
        $amount,
        $type,
        $refType,
        $refId,
        $note,
        current_user_id()
    ]);
}

function manager_cash_balance(
    PDO $pdo,
    int $managerUserId
): float {
    $st = $pdo->prepare(
        "SELECT COALESCE(
            SUM(
                CASE
                    WHEN direction = 'CREDIT' THEN amount
                    ELSE -amount
                END
            ),
            0
        )
        FROM manager_cash_ledger
        WHERE manager_user_id = ?"
    );

    $st->execute([$managerUserId]);

    return (float) $st->fetchColumn();
}

function manager_cash_credit(
    PDO $pdo,
    int $managerUserId,
    float $amount,
    string $refType,
    int $refId,
    string $note = ''
) {
    $pdo->prepare(
        "INSERT INTO manager_cash_ledger(
            manager_user_id,
            direction,
            amount,
            reference_type,
            reference_id,
            note,
            created_by
        )
        VALUES (?, 'CREDIT', ?, ?, ?, ?, ?)"
    )->execute([
        $managerUserId,
        $amount,
        $refType,
        $refId,
        $note,
        current_user_id()
    ]);
}

function manager_cash_debit(
    PDO $pdo,
    int $managerUserId,
    float $amount,
    string $refType,
    int $refId,
    string $note = ''
) {
    if (manager_cash_balance($pdo, $managerUserId) < $amount) {
        throw new Exception(
            'Manager cash balance is insufficient.'
        );
    }

    $pdo->prepare(
        "INSERT INTO manager_cash_ledger(
            manager_user_id,
            direction,
            amount,
            reference_type,
            reference_id,
            note,
            created_by
        )
        VALUES (?, 'DEBIT', ?, ?, ?, ?, ?)"
    )->execute([
        $managerUserId,
        $amount,
        $refType,
        $refId,
        $note,
        current_user_id()
    ]);
}

function apply_purchase(
    PDO $pdo,
    int $purchaseId
) {
    $st = $pdo->prepare(
        "SELECT *
         FROM purchases
         WHERE id = ?
         FOR UPDATE"
    );

    $st->execute([$purchaseId]);
    $p = $st->fetch();

    if (!$p) {
        throw new Exception('Purchase not found.');
    }

    if ($p['status'] === 'approved') {
        return;
    }

    if ($p['status'] === 'rejected') {
        throw new Exception(
            'Rejected purchase cannot be approved.'
        );
    }

    $amount = (float) $p['total_amount'];

    if ($p['payment_source'] === 'manager_cash') {
        manager_cash_debit(
            $pdo,
            (int) $p['created_by'],
            $amount,
            'purchase',
            $purchaseId,
            'Approved purchase: ' . $p['item_name']
        );
    } else {
        if (empty($p['account_id'])) {
            throw new Exception(
                'Select a payment account before approval.'
            );
        }

        account_debit(
            $pdo,
            (int) $p['account_id'],
            $amount,
            'purchase',
            'purchase',
            $purchaseId,
            'Purchase: ' . $p['item_name']
        );
    }

    $pdo->prepare(
        "INSERT INTO expenses(
            farm_id,
            category,
            amount,
            expense_date,
            note,
            purchase_id,
            approval_status,
            approved_by,
            approved_at
        )
        VALUES (?, ?, ?, ?, ?, ?, 'approved', ?, NOW())"
    )->execute([
        $p['farm_id'] ?: null,
        $p['purchase_type'],
        $amount,
        $p['purchase_date'],
        'Purchase: ' . $p['item_name'],
        $purchaseId,
        current_user_id()
    ]);

    if (!empty($p['inventory_item_id'])) {
        $pdo->prepare(
            "UPDATE inventory_items
             SET current_qty = current_qty + ?,
                 unit_cost = ?
             WHERE id = ?"
        )->execute([
            (float) $p['qty'],
            (float) $p['unit_price'],
            (int) $p['inventory_item_id']
        ]);

        $pdo->prepare(
            "INSERT INTO inventory_movements(
                inventory_item_id,
                movement_type,
                qty,
                reference_type,
                reference_id,
                movement_date,
                note
            )
            VALUES (?, 'IN', ?, 'purchase', ?, ?, 'Approved purchase')"
        )->execute([
            (int) $p['inventory_item_id'],
            (float) $p['qty'],
            $purchaseId,
            $p['purchase_date']
        ]);
    }

    if (
        in_array(
            $p['purchase_type'],
            ['Livestock', 'Poultry', 'Fish Seed'],
            true
        )
        && !empty($p['farm_id'])
    ) {
        $pdo->prepare(
            "INSERT INTO farm_stocking(
                farm_id,
                stock_type,
                species,
                quantity,
                unit,
                purchase_id,
                stock_date,
                notes
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        )->execute([
            (int) $p['farm_id'],
            $p['purchase_type'],
            $p['item_name'],
            (float) $p['qty'],
            $p['unit'],
            $purchaseId,
            $p['purchase_date'],
            $p['notes']
        ]);
    }

    $pdo->prepare(
        "UPDATE purchases
         SET status = 'approved',
             approved_by = ?,
             approved_at = NOW()
         WHERE id = ?"
    )->execute([
        current_user_id(),
        $purchaseId
    ]);
}

function approve_staff_payment(
    PDO $pdo,
    int $paymentId,
    int $accountId
): array {
    $st = $pdo->prepare(
        "SELECT *
         FROM staff_payments
         WHERE id = ?
         FOR UPDATE"
    );

    $st->execute([$paymentId]);
    $p = $st->fetch();

    if (!$p) {
        throw new Exception(
            'Staff payment request not found.'
        );
    }

    // One request = one final approval. Legacy "paid" is treated as already approved.
    if (in_array(
        $p['status'],
        ['approved', 'paid'],
        true
    )) {
        return [
            'changed' => false,
            'message' => 'Payment is already approved.'
        ];
    }

    if ($p['status'] === 'rejected') {
        throw new Exception(
            'Rejected payment cannot be approved.'
        );
    }

    if ($p['status'] === 'failed') {
        throw new Exception(
            'Failed payment cannot be approved directly.'
        );
    }

    if ($p['status'] !== 'pending') {
        throw new Exception(
            'Payment request is not pending.'
        );
    }

    if ($accountId <= 0) {
        throw new Exception(
            'Select a payment account.'
        );
    }

    // Debit and finalize in the SAME database transaction.
    account_debit(
        $pdo,
        $accountId,
        (float) $p['amount'],
        'staff_payroll',
        'staff_payment',
        $paymentId,
        'Staff payment ' . $p['payment_month']
    );

    $pdo->prepare(
        "UPDATE staff_payments
         SET account_id = ?,
             status = 'approved',
             approved_by = ?,
             approved_at = NOW(),
             paid_at = NOW()
         WHERE id = ?
         AND status = 'pending'"
    )->execute([
        $accountId,
        current_user_id(),
        $paymentId
    ]);

    $pdo->prepare(
        "INSERT INTO approval_log(
            module_name,
            reference_id,
            action,
            action_by,
            note
        )
        VALUES (
            'staff_payment',
            ?,
            'approved',
            ?,
            'Single-step payment approval'
        )"
    )->execute([
        $paymentId,
        current_user_id()
    ]);

    return [
        'changed' => true,
        'message' => 'Staff payment approved successfully.'
    ];
}

function reject_staff_payment(
    PDO $pdo,
    int $paymentId,
    string $reason = ''
): bool {
    $st = $pdo->prepare(
        "UPDATE staff_payments
         SET status = 'rejected',
             approved_by = ?,
             approved_at = NOW(),
             note = CONCAT(
                 COALESCE(note, ''),
                 CASE
                     WHEN COALESCE(note, '') = '' THEN ''
                     ELSE ' | '
                 END,
                 ?
             )
         WHERE id = ?
         AND status = 'pending'"
    );

    $st->execute([
        current_user_id(),
        $reason,
        $paymentId
    ]);

    if ($st->rowCount()) {
        $pdo->prepare(
            "INSERT INTO approval_log(
                module_name,
                reference_id,
                action,
                action_by,
                note
            )
            VALUES (
                'staff_payment',
                ?,
                'rejected',
                ?,
                ?
            )"
        )->execute([
            $paymentId,
            current_user_id(),
            $reason ?: 'Rejected by Owner'
        ]);

        return true;
    }

    return false;
}

function payment_status_label(
    string $status
): string {
    return $status === 'paid'
        ? 'approved'
        : $status;
}

?>