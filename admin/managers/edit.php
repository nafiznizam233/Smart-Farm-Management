<?php

require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../config/auth.php';

require_role(['owner']);


$id = (int) ($_GET['id'] ?? 0);


$st = $pdo->prepare(
    "SELECT *
     FROM users
     WHERE id=?
     AND role='manager'"
);

$st->execute([$id]);

$m = $st->fetch();


if (!$m) {
    die('Manager not found');
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $pdo->prepare(
        "UPDATE users
         SET
            name=?,
            email=?,
            phone=?,
            verification_status=?,
            status=?
         WHERE id=?"
    )->execute([
        trim($_POST['name']),
        trim($_POST['email']),
        trim($_POST['phone']),
        $_POST['verification_status'],
        $_POST['status'],
        $id
    ]);


    if (!empty($_POST['password'])) {

        $pdo->prepare(
            "UPDATE users
             SET password=?
             WHERE id=?"
        )->execute([
            password_hash($_POST['password'], PASSWORD_DEFAULT),
            $id
        ]);
    }

    header('Location: /admin/managers');
    exit;
}