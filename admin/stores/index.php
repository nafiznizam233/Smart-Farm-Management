<?php

require __DIR__ . "/../../config/database.php";
require __DIR__ . "/../../config/auth.php";

require_role(['owner', 'manager']);


if (is_manager() || is_staff()) {
    require_work_checkin($pdo);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $pdo->prepare(
        "INSERT INTO stores(
            name,
            purpose,
            farm_id,
            location,
            notes,
            status
        )
        VALUES(
            ?,?,?,?,?,
            'active'
        )"
    )->execute([
        trim($_POST['name']),
        $_POST['purpose'],
        $_POST['farm_id'] ?: null,
        trim($_POST['location']),
        trim($_POST['notes'])
    ]);


    header('Location:index.php');
    exit;
}


$farms = $pdo->query(
    "SELECT
        id,
        name
     FROM farms
     WHERE status='active'"
)->fetchAll();


$stores = $pdo->query(
    "SELECT
        st.*,
        f.name farm_name,

        (
            SELECT COUNT(*)
            FROM inventory_items i
            WHERE i.store_id=st.id
        ) items

     FROM stores st

     LEFT JOIN farms f
        ON f.id=st.farm_id

     ORDER BY st.id DESC"
)->fetchAll();


$page_title = 'Stores';

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
                    Stores / Warehouses
                </h2>

                <p class="mb-0">
                    Create multiple stores for fish feed, rice seed, medicine, livestock supplies and more.
                </p>

            </div>


            <div class="cardx p-3 mb-4">

                <form
                    method="post"
                    class="row g-2"
                >

                    <div class="col-md-2">

                        <input
                            class="form-control"
                            name="name"
                            placeholder="Store name"
                            required
                        >

                    </div>


                    <div class="col-md-2">

                        <select
                            class="form-select"
                            name="purpose"
                        >

                            <option>
                                General
                            </option>

                            <option>
                                Fish Feed
                            </option>

                            <option>
                                Medicine
                            </option>

                            <option>
                                Rice Seed
                            </option>

                            <option>
                                Livestock Feed
                            </option>

                            <option>
                                Poultry Feed
                            </option>

                        </select>

                    </div>


                    <div class="col-md-2">

                        <select
                            class="form-select"
                            name="farm_id"
                        >

                            <option value="">
                                All / General
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
                            name="location"
                            placeholder="Location"
                        >

                    </div>


                    <div class="col-md-3">

                        <input
                            class="form-control"
                            name="notes"
                            placeholder="Notes"
                        >

                    </div>


                    <div class="col-md-1">

                        <button class="btn btn-success w-100">
                            Add
                        </button>

                    </div>

                </form>

            </div>


            <div class="row g-3">

                <?php foreach ($stores as $s): ?>

                    <div class="col-md-4">

                        <div class="cardx store-card p-3">

                            <h5>
                                <?= htmlspecialchars($s['name']) ?>
                            </h5>


                            <span class="badge-soft">
                                <?= htmlspecialchars($s['purpose']) ?>
                            </span>


                            <div class="small-muted mt-2">

                                <?= htmlspecialchars(
                                    $s['farm_name'] ?? 'General'
                                ) ?>

                                ·

                                <?= htmlspecialchars($s['location']) ?>

                            </div>


                            <div class="mt-3">

                                <b>
                                    <?= $s['items'] ?>
                                </b>

                                inventory items

                            </div>


                            <a
                                class="btn btn-sm btn-outline-success mt-2"
                                href="../inventory/index.php?store_id=<?= $s['id'] ?>"
                            >
                                Open Inventory
                            </a>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>

    </div>

</div>