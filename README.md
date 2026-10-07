# Smart Farm Management — Full Complete Stable Package

This version fixes the design/path problems from V6/V6.1 and consolidates the requested workflow.

## Main fixes
- CSS, logo and JS are local files; the dashboard no longer depends on internet/CDN.
- Shared header/sidebar design is consistent across Owner and Manager pages.
- Public professional landing page.
- One unified login page with Owner / Manager / Staff / Applicant login type.
- Applicant registration, open jobs, CV upload, application status and interview schedule.
- Owner sidebar grouped into Finance, Farm & Stock, Management and Reports.
- Owner dashboard: staff totals, present, verified/unverified, active managers, today income/expense, monthly expense, total balance, pending approvals.
- Date-range Income vs Expense chart with Print and CSV Download.
- Manager handles routine staff verification.
- Manager and Staff must Check In before operational actions.
- Owner can see active manager/staff users.
- Existing payment single-source approval sync from V6.1 remains.

## Recommended install (fresh)
1. Extract exactly as: `C:\xampp\htdocs\smart_farm_management_complete`
2. Start Apache and MySQL.
3. Import `database/smart_farm.sql` into phpMyAdmin.
4. Open `http://localhost/smart_farm_management_complete/setup_owner.php` once.
5. Open `http://localhost/smart_farm_management_complete/` — this is now the Landing Page.
6. Owner login: choose Owner from unified Login page.
   - Email: owner@farm.local
   - Password: 123456

## Upgrade from V6.1 without deleting data
1. Copy V7 files to a new folder.
2. Import `database/upgrade_v6_1_to_v7.sql` into the existing `smart_farm` database.
3. Do not re-import the full fresh SQL if you want to retain old data.

## Important
The app now has `config/app.php` and uses a shared base path for new core pages. Local Bootstrap is bundled in `assets/vendor`, so missing internet will not remove dashboard styling.
Group 2 
Section J
