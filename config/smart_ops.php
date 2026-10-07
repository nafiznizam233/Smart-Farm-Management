<?php

function ensure_public_id(PDO $pdo, array $u): string
{
    if (!empty($u['public_id'])) {
        return $u['public_id'];
    }

    $prefix = [
        'owner' => 'OWN',
        'manager' => 'MGR',
        'staff' => 'STF',
        'customer' => 'CUS',
        'applicant' => 'APP'
    ][$u['role']] ?? 'USR';

    $id = $prefix
        . '-'
        . date('y')
        . '-'
        . str_pad(
            (string) $u['id'],
            5,
            '0',
            STR_PAD_LEFT
        );

    $pdo->prepare(
        "UPDATE users
         SET public_id = ?
         WHERE id = ?"
    )->execute([
        $id,
        $u['id']
    ]);

    return $id;
}

function notify_user(
    PDO $pdo,
    int $uid,
    string $type,
    string $title,
    string $message,
    string $link = '',
    string $refType = '',
    ?int $refId = null
): void {
    $pdo->prepare(
        "INSERT INTO notifications(
            user_id,
            notification_type,
            title,
            message,
            link_url,
            reference_type,
            reference_id
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)"
    )->execute([
        $uid,
        $type,
        $title,
        $message,
        $link,
        $refType,
        $refId
    ]);
}

function notify_managers(
    PDO $pdo,
    string $type,
    string $title,
    string $message,
    string $link = '',
    string $refType = '',
    ?int $refId = null
): void {
    $ids = $pdo->query(
        "SELECT id
         FROM users
         WHERE role = 'manager'
         AND status = 'active'"
    )->fetchAll(PDO::FETCH_COLUMN);

    foreach ($ids as $id) {
        notify_user(
            $pdo,
            (int) $id,
            $type,
            $title,
            $message,
            $link,
            $refType,
            $refId
        );
    }
}

function incident_initialization(
    string $type,
    string $description,
    string $urgency
): array {
    $base = "Keep the affected area safe, record clear observations, avoid unapproved treatment, and inform the manager.";

    $steps = [
        'animal_health' =>
            "Separate the sick animal when practical, note eating/drinking, movement, temperature or visible symptoms, keep clean water available, and contact a qualified veterinarian for diagnosis.",

        'fish_health' =>
            "Check visible fish behaviour, mortality, water colour/odour and recent feed changes; pause any unapproved chemical treatment and escalate to the manager or fisheries professional.",

        'crop_problem' =>
            "Photograph affected leaves/area, note spread and recent irrigation/fertilizer/pesticide use; isolate the affected batch where practical and wait for manager/agronomy review.",

        'product_problem' =>
            "Separate the affected product/batch, stop sale or dispatch of that batch, record quantity and visible issue, and wait for manager quality review.",

        'equipment' =>
            "Stop using unsafe equipment, isolate power/fuel where safe, mark it unavailable and request maintenance review.",

        'delivery' =>
            "Record the order/delivery issue, keep the product secure, contact the manager and do not mark delivered until resolved."
    ];

    $initial = $steps[$type] ?? $base;

    $summary = "Reported "
        . str_replace('_', ' ', $type)
        . " issue ("
        . strtoupper($urgency)
        . "). Staff note: "
        . mb_substr(
            trim($description),
            0,
            280
        );

    return [
        $summary,
        $initial
    ];
}

?>