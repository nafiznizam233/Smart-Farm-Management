<?php

require __DIR__ . "/../../config/database.php";
require __DIR__ . "/../../config/auth.php";

require_role(['owner', 'manager']);


$id = (int) ($_GET['id'] ?? 0);


$st = $pdo->prepare(
    "SELECT *
     FROM farms
     WHERE id=?"
);

$st->execute([$id]);

$farm = $st->fetch();


if (!$farm) {
    die("Farm not found");
}


$uploadDir = __DIR__ . "/../../uploads/farms/";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $image = $farm['image'];


    if (
        !empty($_FILES['image']['name']) &&
        $_FILES['image']['error'] === UPLOAD_ERR_OK
    ) {

        $ext = strtolower(
            pathinfo(
                $_FILES['image']['name'],
                PATHINFO_EXTENSION
            )
        );


        if (in_array(
            $ext,
            ['jpg', 'jpeg', 'png', 'webp'],
            true
        )) {

            $image =
                time() . "_" .
                bin2hex(random_bytes(4)) .
                "." .
                $ext;


            move_uploaded_file(
                $_FILES['image']['tmp_name'],
                $uploadDir . $image
            );
        }
    }


    $st = $pdo->prepare(
        "UPDATE farms
         SET
            name=?,
            category=?,
            location=?,
            start_date=?,
            image=?
         WHERE id=?"
    );


    $st->execute([
        trim($_POST['name']),
        trim($_POST['category']),
        trim($_POST['location']),
        $_POST['start_date'] ?: null,
        $image,
        $id
    ]);


    header("Location:index.php");
    exit;
}


$page_title = "Edit Farm";

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
                    Edit Farm
                </h2>

            </div>


            <div class="cardx p-4">

                <form
                    method="post"
                    enctype="multipart/form-data"
                >

                    <div class="row g-3">

                        <div class="col-md-6">

                            <label class="form-label">
                                Farm Name
                            </label>

                            <input
                                class="form-control"
                                name="name"
                                value="<?= htmlspecialchars($farm['name']) ?>"
                                required
                            >

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Category
                            </label>

                            <input
                                class="form-control"
                                name="category"
                                value="<?= htmlspecialchars($farm['category']) ?>"
                                required
                            >

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Location
                            </label>

                            <input
                                class="form-control"
                                name="location"
                                value="<?= htmlspecialchars($farm['location']) ?>"
                            >

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Start Date
                            </label>

                            <input
                                class="form-control"
                                type="date"
                                name="start_date"
                                value="<?= $farm['start_date'] ?>"
                            >

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Change Farm Photo
                            </label>

                            <input
                                class="form-control"
                                type="file"
                                name="image"
                                accept=".jpg,.jpeg,.png,.webp"
                            >

                        </div>


                        <div class="col-md-6">

                            <?php if ($farm['image']): ?>

                                <img
                                    class="preview-img"
                                    src="/smart_farm_php_v7/uploads/farms/<?= htmlspecialchars($farm['image']) ?>"
                                >

                            <?php endif; ?>

                        </div>

                    </div>


                    <button class="btn btn-success mt-3">
                        Save Changes
                    </button>


                    <a
                        class="btn btn-secondary mt-3"
                        href="index.php"
                    >
                        Back
                    </a>

                </form>

            </div>

        </div>

    </div>

</div>