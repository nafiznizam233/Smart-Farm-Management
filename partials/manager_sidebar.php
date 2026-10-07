<?php

$mpdo = $GLOBALS['pdo'] ?? null;

$can = function ($k) use ($mpdo) {
    return !$mpdo || manager_has_permission($mpdo, $k);
};

?>

<aside class="app-sidebar no-print">

    <div class="side-title">
        FARM MANAGER
    </div>


    <a href="<?= url_path('manager/dashboard.php') ?>">
        🏠 My Farm
    </a>


    <?php if (
        $can('workers') ||
        $can('attendance') ||
        $can('payroll')
    ): ?>

        <details open>

            <summary>
                Workers
            </summary>


            <?php if ($can('workers')): ?>

                <a href="<?= url_path('manager/workers.php') ?>">
                    Worker Records
                </a>

            <?php endif; ?>


            <?php if ($can('attendance')): ?>

                <a href="<?= url_path('manager/worker_attendance.php') ?>">
                    Attendance
                </a>

            <?php endif; ?>


            <?php if ($can('payroll')): ?>

                <a href="<?= url_path('manager/worker_payroll.php') ?>">
                    Salary
                </a>

            <?php endif; ?>

        </details>

    <?php endif; ?>


    <?php if (
        $can('products') ||
        $can('preorders') ||
        $can('stores') ||
        $can('inventory')
    ): ?>

        <details open>

            <summary>
                Farm Operations
            </summary>


            <?php if ($can('products')): ?>

                <a href="<?= url_path('manager/shop/index.php') ?>">
                    Products & Stock
                </a>

            <?php endif; ?>


            <?php if ($can('preorders')): ?>

                <a href="<?= url_path('manager/preorders.php') ?>">
                    Pre-order Capacity
                </a>

            <?php endif; ?>


            <?php if ($can('stores')): ?>

                <a href="<?= url_path('admin/stores/index.php') ?>">
                    Stores
                </a>

            <?php endif; ?>


            <?php if ($can('inventory')): ?>

                <a href="<?= url_path('admin/inventory/index.php') ?>">
                    Inventory
                </a>

            <?php endif; ?>

        </details>

    <?php endif; ?>


    <?php if (
        $can('delivery') ||
        $can('support')
    ): ?>

        <details>

            <summary>
                Commerce
            </summary>


            <?php if ($can('delivery')): ?>

                <a href="<?= url_path('manager/deliveries.php') ?>">
                    Delivery
                </a>

            <?php endif; ?>


            <?php if ($can('support')): ?>

                <a href="<?= url_path('manager/support.php') ?>">
                    Customer Support
                </a>

            <?php endif; ?>

        </details>

    <?php endif; ?>


    <a href="<?= url_path('account/profile.php') ?>">
        My Profile
    </a>

</aside>