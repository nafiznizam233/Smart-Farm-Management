<?php

require __DIR__ . "/../../config/database.php";
require __DIR__ . "/../../config/auth.php";

require_role(['owner', 'manager']);


$uploadDir = __DIR__ . "/../../uploads/farms/";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}


if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['add'])
) {

    $imageName = null;


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

        $allowed = [
            'jpg',
            'jpeg',
            'png',
            'webp'
        ];


        if (in_array($ext, $allowed, true)) {

            $imageName =
                time() . "_" .
                bin2hex(random_bytes(4)) .
                "." .
                $ext;


            move_uploaded_file(
                $_FILES['image']['tmp_name'],
                $uploadDir . $imageName
            );
        }
    }


    $st = $pdo->prepare(
        "INSERT INTO farms(
            name,
            category,
            location,
            start_date,
            image,
            status
        )
        VALUES(
            ?,?,?,?,?,
            'active'
        )"
    );


    $st->execute([
        trim($_POST['name']),
        trim($_POST['category']),
        trim($_POST['location']),
        $_POST['start_date'] ?: null,
        $imageName
    ]);


    header("Location:index.php");
    exit;
}


if (isset($_GET['delete'])) {

    $pdo->prepare(
        "UPDATE farms
         SET status='inactive'
         WHERE id=?"
    )->execute([
        (int) $_GET['delete']
    ]);


    header("Location:index.php");
    exit;
}


$farms = $pdo->query(
    "SELECT *
     FROM farms
     ORDER BY id DESC"
)->fetchAll();


$page_title = "Farms";

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
                    <i class="fa-solid fa-tractor me-2"></i>
                    Dynamic Farm Management
                </h2>

                <p class="mb-0">
                    Add any farm type and upload its own photo.
                </p>

            </div>


            <div class="cardx p-3 mb-4">

                <form
                    method="post"
                    enctype="multipart/form-data"
                    class="row g-2"
                >

                    <input
                        type="hidden"
                        name="add"
                        value="1"
                    >


                    <div class="col-md-3">

                        <input
                            class="form-control"
                            name="name"
                            placeholder="Farm name"
                            required
                        >

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
                            name="location"
                            placeholder="Location"
                        >

                    </div>


                    <div class="col-md-2">

                        <input
                            class="form-control"
                            type="date"
                            name="start_date"
                        >

                    </div>


                    <div class="col-md-2">

                        <input
                            class="form-control"
                            type="file"
                            name="image"
                            accept=".jpg,.jpeg,.png,.webp"
                        >

                    </div>


                    <div class="col-md-2">

                        <button
                            class="btn btn-success w-100 fw-bold"
                            type="submit"
                        >
                            <i class="fa-solid fa-plus me-1"></i>
                            Add Farm
                        </button>

                    </div>

                </form>

            </div>


            <div class="row g-3">

                <?php foreach ($farms as $f): ?>

                    <div class="col-lg-4 col-md-6">

                        <div class="cardx farm-card">

                            <?php if ($f['image']): ?>

                                <img
                                    src="<?= url_path(
                                        'uploads/farms/' .
                                        rawurlencode($f['image'])
                                    ) ?>"
                                    alt="<?= htmlspecialchars($f['name']) ?>"
                                >

                            <?php else: ?>

                                <div class="farm-photo-required">

                                    <b>
                                        No farm photo uploaded
                                    </b>

                                    <small>
                                        Use Edit to add a real JPG/PNG/WEBP farm photo.
                                    </small>

                                </div>

                            <?php endif; ?>


                            <div class="farm-card-body">

                                <div class="d-flex justify-content-between align-items-start">

                                    <div>

                                        <h5 class="mb-1">
                                            <?= htmlspecialchars($f['name']) ?>
                                        </h5>

                                        <div class="small-muted">

                                            <?= htmlspecialchars($f['category']) ?>

                                            ·

                                            <?= htmlspecialchars($f['location']) ?>

                                        </div>

                                    </div>


                                    <span
                                        class="badge text-bg-<?= $f['status'] === 'active'
                                            ? 'success'
                                            : 'secondary'
                                        ?>"
                                    >
                                        <?= $f['status'] ?>
                                    </span>

                                </div>


                                <a
                                    class="btn btn-sm btn-outline-success mt-3"
                                    href="view.php?id=<?= $f['id'] ?>"
                                >
                                    View
                                </a>


                                <a
                                    class="btn btn-sm btn-outline-primary mt-3"
                                    href="edit.php?id=<?= $f['id'] ?>"
                                >
                                    Edit
                                </a>


                                <?php if ($f['status'] === 'active'): ?>

                                    <a
                                        class="btn btn-sm btn-outline-danger mt-3"
                                        href="?delete=<?= $f['id'] ?>"
                                        onclick="return confirm('Deactivate farm?')"
                                    >
                                        Remove
                                    </a>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>

    </div>

</div>