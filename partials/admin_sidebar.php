<aside class="app-sidebar no-print">

    <div class="side-title">
        OWNER PANEL
    </div>

    <a href="<?= url_path('admin/dashboard.php') ?>">
        🏠 <span>Dashboard</span>
    </a>


    <details open>

        <summary>
            💰 Finance
        </summary>

        <a href="<?= url_path('admin/accounts/index.php') ?>">
            Accounts & Balance
        </a>

        <a href="<?= url_path('admin/approvals/index.php') ?>">
            Approval Center
        </a>

        <a href="<?= url_path('admin/income/index.php') ?>">
            Income
        </a>

        <a href="<?= url_path('admin/expenses/index.php') ?>">
            Expenses
        </a>

        <a href="<?= url_path('admin/purchases/index.php') ?>">
            Purchases
        </a>

        <a href="<?= url_path('admin/cash_requests/index.php') ?>">
            Cash Requests
        </a>

        <a href="<?= url_path('admin/manager_salary/index.php') ?>">
            Manager Salary
        </a>

    </details>


    <details>

        <summary>
            🌾 Farm & Stock
        </summary>

        <a href="<?= url_path('admin/farms/index.php') ?>">
            Farms
        </a>

        <a href="<?= url_path('admin/stores/index.php') ?>">
            Stores
        </a>

        <a href="<?= url_path('admin/inventory/index.php') ?>">
            Inventory
        </a>

        <a href="<?= url_path('admin/catalog/index.php') ?>">
            Purchase Catalog
        </a>

        <a href="<?= url_path('admin/operations/feed.php') ?>">
            Feed Log
        </a>

        <a href="<?= url_path('admin/operations/medicine.php') ?>">
            Medicine
        </a>

        <a href="<?= url_path('admin/stocking/index.php') ?>">
            Farm Stocking
        </a>

    </details>


    <details>

        <summary>
            👥 Management
        </summary>

        <a href="<?= url_path('admin/managers/index.php') ?>">
            Managers
        </a>

        <a href="<?= url_path('manager/support.php') ?>">
            Customer Support
        </a>

        <a href="<?= url_path('admin/deliveries/index.php') ?>">
            Delivery Control
        </a>

        <a href="<?= url_path('admin/workforce/index.php') ?>">
            Farm Workforce & Attendance
        </a>

        <a href="<?= url_path('admin/sellers/index.php') ?>">
            Marketplace Suppliers
        </a>

    </details>


    <details>

        <summary>
            📊 Reports
        </summary>

        <a href="<?= url_path('admin/reports/index.php') ?>">
            Farm Reports
        </a>

    </details>

</aside>