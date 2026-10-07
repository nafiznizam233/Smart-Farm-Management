<?php

require __DIR__ . "/../config/database.php";
require __DIR__ . "/../config/auth.php";

require_login();

if (!in_array(
    $_SESSION['user']['role'],
    ['owner', 'manager', 'staff'],
    true
)) {
    die(
        'Profile settings are not available for this account.'
    );
}

$uid = (int) $_SESSION['user']['id'];

$dir = __DIR__ . "/../uploads/profiles/";

if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}


function saveup($f, $dir)
{
    if (
        empty($_FILES[$f]['name']) ||
        $_FILES[$f]['error'] !== UPLOAD_ERR_OK
    ) {
        return null;
    }

    $ext = strtolower(
        pathinfo(
            $_FILES[$f]['name'],
            PATHINFO_EXTENSION
        )
    );

    if (!in_array(
        $ext,
        ['jpg', 'jpeg', 'png', 'webp'],
        true
    )) {
        return null;
    }

    $n =
        time() . '_' .
        bin2hex(random_bytes(4)) .
        '_' .
        $f .
        '.' .
        $ext;

    move_uploaded_file(
        $_FILES[$f]['tmp_name'],
        $dir . $n
    );

    return $n;
}


$tab = ($_GET['tab'] ?? 'profile') === 'settings'
    ? 'settings'
    : 'profile';

$msg = '';


/* Theme Settings */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['save_theme'])
) {

    $themes = [
        'green',
        'blue',
        'purple',
        'teal',
        'orange',
        'rose'
    ];

    $theme = in_array(
        $_POST['ui_theme'] ?? '',
        $themes,
        true
    )
        ? $_POST['ui_theme']
        : 'green';

    $mode = in_array(
        $_POST['ui_mode'] ?? '',
        ['light', 'dark'],
        true
    )
        ? $_POST['ui_mode']
        : 'light';

    $pdo->prepare(
        "UPDATE users
         SET ui_theme=?, ui_mode=?
         WHERE id=?"
    )->execute([
        $theme,
        $mode,
        $uid
    ]);

    $_SESSION['user']['ui_theme'] = $theme;
    $_SESSION['user']['ui_mode'] = $mode;

    $msg = 'Theme settings updated successfully.';

    $tab = 'settings';
}


/* Profile Settings */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['save_profile'])
) {

    $st = $pdo->prepare(
        "SELECT
            profile_image,
            nid_front,
            nid_back
         FROM users
         WHERE id=?"
    );

    $st->execute([$uid]);

    $o = $st->fetch();

    $pimg = saveup(
        'profile_image',
        $dir
    ) ?: $o['profile_image'];

    $nf = saveup(
        'nid_front',
        $dir
    ) ?: $o['nid_front'];

    $nb = saveup(
        'nid_back',
        $dir
    ) ?: $o['nid_back'];

    $vs = ($nf || $nb)
        ? 'pending'
        : 'unverified';

    $pdo->prepare(
        "UPDATE users
         SET
            profile_image=?,
            nid_front=?,
            nid_back=?,
            verification_status=?
         WHERE id=?"
    )->execute([
        $pimg,
        $nf,
        $nb,
        $vs,
        $uid
    ]);

    $msg = 'Profile updated successfully.';
}


/* Load User */

$st = $pdo->prepare(
    "SELECT *
     FROM users
     WHERE id=?"
);

$st->execute([$uid]);

$u = $st->fetch();

$page_title = 'Profile & Settings';

require __DIR__ . "/../partials/header.php";


$dash = is_owner()
    ? url_path('admin/dashboard.php')
    : (
        is_manager()
            ? url_path('manager/dashboard.php')
            : url_path('staff/dashboard.php')
    );

?>


<div class="container py-4">

    <div class="d-flex justify-content-end mb-3">

        <a
            class="btn btn-success"
            href="<?= htmlspecialchars($dash) ?>"
        >
            ← Back to Dashboard
        </a>

    </div>


    <div class="hero-top mb-4">

        <h2>
            Profile & Settings
        </h2>

        <p class="mb-0">
            Manage your account profile, dashboard theme
            and light/dark appearance.
        </p>

    </div>


    <?php if ($msg): ?>

        <div class="alert alert-success">
            <?= htmlspecialchars($msg) ?>
        </div>

    <?php endif; ?>


    <ul class="nav nav-pills profile-tabs mb-3">

        <li class="nav-item">

            <a
                class="nav-link <?= $tab === 'profile' ? 'active' : '' ?>"
                href="?tab=profile"
            >
                My Profile
            </a>

        </li>

        <li class="nav-item">

            <a
                class="nav-link <?= $tab === 'settings' ? 'active' : '' ?>"
                href="?tab=settings"
            >
                ⚙ Settings
            </a>

        </li>

    </ul>


    <?php if ($tab === 'profile'): ?>

        <div class="cardx p-4">

            <div class="row g-4">

                <div class="col-md-4 text-center">

                    <?php if ($u['profile_image']): ?>

                        <img
                            class="profile-avatar"
                            src="<?= url_path(
                                'uploads/profiles/' .
                                rawurlencode($u['profile_image'])
                            ) ?>"
                        >

                    <?php else: ?>

                        <div
                            class="
                                profile-avatar
                                mx-auto
                                d-flex
                                align-items-center
                                justify-content-center
                                fs-1
                            "
                        >
                            👤
                        </div>

                    <?php endif; ?>


                    <h5 class="mt-3">
                        <?= htmlspecialchars($u['name']) ?>
                    </h5>


                    <span
                        class="verify-pill v-<?= $u['verification_status'] ?>"
                    >
                        <?= ucfirst($u['verification_status']) ?>
                    </span>

                </div>


                <div class="col-md-8">

                    <form
                        method="post"
                        enctype="multipart/form-data"
                    >

                        <input
                            type="hidden"
                            name="save_profile"
                            value="1"
                        >


                        <div class="mb-3">

                            <label>
                                Email
                            </label>

                            <input
                                class="form-control"
                                value="<?= htmlspecialchars($u['email']) ?>"
                                disabled
                            >

                        </div>


                        <div class="mb-3">

                            <label>
                                Mobile Number
                            </label>

                            <input
                                class="form-control"
                                value="<?= htmlspecialchars($u['phone']) ?>"
                                disabled
                            >

                        </div>


                        <div class="mb-3">

                            <label>
                                Profile Picture
                            </label>

                            <input
                                class="form-control"
                                type="file"
                                name="profile_image"
                                accept=".jpg,.jpeg,.png,.webp"
                            >

                        </div>


                        <div class="mb-3">

                            <label>
                                NID Front
                            </label>

                            <input
                                class="form-control"
                                type="file"
                                name="nid_front"
                                accept=".jpg,.jpeg,.png,.webp"
                            >

                        </div>


                        <div class="mb-3">

                            <label>
                                NID Back
                            </label>

                            <input
                                class="form-control"
                                type="file"
                                name="nid_back"
                                accept=".jpg,.jpeg,.png,.webp"
                            >

                        </div>


                        <button class="btn btn-success">
                            Save Profile
                        </button>

                    </form>

                </div>

            </div>

        </div>


    <?php else: ?>

        <div class="cardx p-4">

            <div
                class="
                    d-flex
                    justify-content-between
                    align-items-start
                    flex-wrap
                    gap-2
                    mb-4
                "
            >

                <div>

                    <h4 class="mb-1">
                        Appearance Settings
                    </h4>

                    <p class="small-muted mb-0">
                        Your choice is saved to your account
                        and follows you after login.
                    </p>

                </div>


                <span class="badge-soft">

                    Current:
                    <?= ucfirst($u['ui_theme'] ?? 'green') ?>
                    ·
                    <?= ucfirst($u['ui_mode'] ?? 'light') ?>

                </span>

            </div>


            <form method="post">

                <input
                    type="hidden"
                    name="save_theme"
                    value="1"
                >


                <h6>
                    Choose Dashboard Theme
                </h6>


                <div class="theme-grid mb-4">

                    <?php

                    foreach (
                        [
                            'green'  => 'Farm Green',
                            'blue'   => 'Ocean Blue',
                            'purple' => 'Royal Purple',
                            'teal'   => 'Modern Teal',
                            'orange' => 'Harvest Orange',
                            'rose'   => 'Rose'
                        ] as $v => $label
                    ):

                    ?>

                        <label
                            class="theme-choice theme-<?= $v ?>"
                        >

                            <input
                                type="radio"
                                name="ui_theme"
                                value="<?= $v ?>"
                                <?= ($u['ui_theme'] ?? 'green') === $v
                                    ? 'checked'
                                    : '' ?>
                            >

                            <span class="theme-swatch"></span>

                            <b>
                                <?= $label ?>
                            </b>

                        </label>

                    <?php endforeach; ?>

                </div>


                <h6>
                    Display Mode
                </h6>


                <div class="mode-grid mb-4">

                    <label class="mode-choice">

                        <input
                            type="radio"
                            name="ui_mode"
                            value="light"
                            <?= ($u['ui_mode'] ?? 'light') === 'light'
                                ? 'checked'
                                : '' ?>
                        >

                        <span>☀️</span>

                        <b>
                            Light Mode
                        </b>

                    </label>


                    <label class="mode-choice">

                        <input
                            type="radio"
                            name="ui_mode"
                            value="dark"
                            <?= ($u['ui_mode'] ?? 'light') === 'dark'
                                ? 'checked'
                                : '' ?>
                        >

                        <span>🌙</span>

                        <b>
                            Dark Mode
                        </b>

                    </label>

                </div>


                <button class="btn btn-success px-4">
                    Save & Apply Theme
                </button>

            </form>

        </div>

    <?php endif; ?>

</div>