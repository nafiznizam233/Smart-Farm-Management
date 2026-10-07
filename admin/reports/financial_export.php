<?php

require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../config/auth.php';

require_role(['owner']);


$from = $_GET['from'] ?? date('Y-m-01');

$to = $_GET['to'] ?? date('Y-m-d');


header(
    'Content-Type: text/csv; charset=UTF-8'
);

header(
    'Content-Disposition: attachment; filename="financial_report_' .
    $from .
    '_to_' .
    $to .
    '.csv"'
);


$o = fopen(
    'php://output',
    'w'
);


fputcsv(
    $o,
    [
        'Date',
        'Type',
        'Description',
        'Amount'
    ]
);


$st = $pdo->prepare(
    "SELECT
        income_date d,
        'Income' t,
        source descr,
        amount
     FROM income
     WHERE income_date BETWEEN ? AND ?

     UNION ALL

     SELECT
        expense_date d,
        'Expense' t,
        category descr,
        amount
     FROM expenses
     WHERE expense_date BETWEEN ? AND ?

     ORDER BY d"
);


$st->execute([
    $from,
    $to,
    $from,
    $to
]);


while ($r = $st->fetch()) {

    fputcsv(
        $o,
        [
            $r['d'],
            $r['t'],
            $r['descr'],
            $r['amount']
        ]
    );
}


fclose($o);

exit;