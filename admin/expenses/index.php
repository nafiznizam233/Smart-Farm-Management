<?php

require __DIR__ . "/../../config/database.php";
require __DIR__ . "/../../config/auth.php";

require_role(['owner', 'manager']);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $st = $pdo->prepare(
        "INSERT INTO expenses(
            farm_id,
            category,
            amount,
            expense_date,
            note
        )
        VALUES(?,?,?,?,?)"
    );

    $st->execute([
        $_POST['farm_id'] ?: null,
        trim($_POST['category']),
        (float) $_POST['amount'],
        $_POST['expense_date'],
        trim($_POST['note'])
    ]);

    header("Location:index.php");
    exit;
}


$farms = $pdo->query(
    "SELECT
        id,
        name
     FROM farms
     WHERE status='active'
     ORDER BY name"
)->fetchAll();


$rows = $pdo->query(
    "SELECT
        e.*,
        f.name farm_name
     FROM expenses e
     LEFT JOIN farms f
        ON f.id=e.farm_id
     ORDER BY e.id DESC
     LIMIT 100"
)->fetchAll();


$selected = (int) ($_GET['farm_id'] ?? 0);


$page_title = "Expenses";

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
                    Expense Management
                </h2>

            </div>


            <div class="cardx p-3 mb-4">

                <form
                    method="post"
                    class="row g-2"
                >

                    <div class="col-md-3">

                        <select
                            class="form-select"
                            name="farm_id"
                        >

                            <option value="">
                                General / Other
                            </option>


                            <?php foreach ($farms as $f): ?>

                                <option
                                    value="<?= $f['id'] ?>"
                                    <?= $selected === $f['id']
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    <?= htmlspecialchars($f['name']) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="col-md-2">

                        <input
                            class="form-control"
                            name="category"
                            placeholder="Feed/Medicine/Labour"
                            required
                        >

                    </div>


                    <div class="col-md-2">

                        <input
                            class="form-control"
                            type="number"
                            step="0.01"
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
                            required
                        >

                    </div>


                    <div class="col-md-2">

                        <input
                            class="form-control"
                            name="note"
                            placeholder="Note"
                        >

                    </div>


                    <div class="col-md-1">

                        <button class="btn btn-danger w-100">
                            Add
                        </button>

                    </div>

                </form>

            </div>


            <div class="cardx p-3">

                <table class="table">

                    <thead>

                        <tr>
                            <th>Date</th>
                            <th>Farm</th>
                            <th>Category</th>
                            <th>Amount</th>
                            <th>Note</th>
                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($rows as $r): ?>

                            <tr>

                                <td>
                                    <?= $r['expense_date'] ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $r['farm_name'] ?? 'General'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $r['category']
                                    ) ?>
                                </td>

                                <td>
                                    ৳<?= number_format(
                                        $r['amount'],
                                        2
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $r['note']
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