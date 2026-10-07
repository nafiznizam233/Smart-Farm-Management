<?php

require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../config/auth.php';

require_role(['owner']);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $pdo->prepare(
        "UPDATE marketplace_seller_applications
         SET
            status=?,
            reviewed_by=?,
            reviewed_at=NOW()
         WHERE id=?"
    )->execute([
        $_POST['status'],
        $_SESSION['user']['id'],
        (int) $_POST['id']
    ]);


    header('Location:index.php');
    exit;
}


$rows = $pdo->query(
    "SELECT *
     FROM marketplace_seller_applications
     ORDER BY id DESC"
)->fetchAll();


$page_title = 'Marketplace Suppliers';

require __DIR__ . '/../../partials/header.php';

?>


<div class="app-shell">

    <?php
    require __DIR__ . '/../../partials/admin_sidebar.php';
    ?>


    <main class="app-main">

        <h2>
            External Farm Applications
        </h2>


        <div class="cardx p-3">

            <table class="table">

                <tr>
                    <th>Farm</th>
                    <th>Applicant</th>
                    <th>Products</th>
                    <th>Status</th>
                    <th>Review</th>
                </tr>


                <?php foreach ($rows as $r): ?>

                    <tr>

                        <td>
                            <?= htmlspecialchars($r['farm_name']) ?>
                        </td>


                        <td>

                            <?= htmlspecialchars($r['applicant_name']) ?>

                            <br>

                            <?= $r['phone'] ?>

                        </td>


                        <td>
                            <?= htmlspecialchars($r['product_types']) ?>
                        </td>


                        <td>
                            <?= $r['status'] ?>
                        </td>


                        <td>

                            <form
                                method="post"
                                class="d-flex gap-1"
                            >

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= $r['id'] ?>"
                                >


                                <select
                                    class="form-select"
                                    name="status"
                                >

                                    <option>
                                        under_review
                                    </option>

                                    <option>
                                        approved
                                    </option>

                                    <option>
                                        rejected
                                    </option>

                                </select>


                                <button class="btn btn-success">
                                    Save
                                </button>

                            </form>

                        </td>

                    </tr>

                <?php endforeach; ?>

            </table>

        </div>

    </main>

</div>