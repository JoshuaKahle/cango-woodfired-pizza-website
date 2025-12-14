# AwardSpace Deployment Guide (Demo)

This guide documents how to deploy the **Cango Woodfired Pizza** site to **AwardSpace** for a temporary demo.

## Known AwardSpace Details (from your screenshots)

- **Document root (web root):** `/home/www/cangowoodfiredpizza.atwebpages.com/`
- **PHP version (AwardSpace):** PHP **8.3.x**

### Database (MySQL)

Use the values shown in AwardSpace’s database list:

- **DB host:** `fdb1032.awardspace.net`
- **DB name:** `4717274_cangowoodfiredb`
- **DB user:** `4717274_cangowoodfiredb`
- **DB password:** `ForestGlade#10`
- **DB port:** `3306` (usually not required in the PDO DSN)

> Note: AwardSpace can open phpMyAdmin via SSO (no manual login). Even if you didn’t type a username, the DB user is still the one listed in AwardSpace (often identical to the DB name).

## Important Notes (PHP 8.3 vs PHP 7.4 Target)

- The project is designed for **PHP 7.4** shared hosting.
- AwardSpace is using **PHP 8.3**, which often still works, but can be stricter and emit warnings/notices.
- If AwardSpace offers switching to **PHP 8.0/8.1**, it can reduce surprises vs 8.3.
- If you see JSON-related admin API issues, they’re commonly caused by warnings/notices being output.

## 1) Upload Files to AwardSpace

### Upload destination

Upload the *contents* of your local `public_html/` folder into:

- `/home/www/cangowoodfiredpizza.atwebpages.com/`

So that files like these exist in the document root:

- `index.php`
- `menu.php`
- `specials.php`
- `dashboard.php`
- `api/` (folder)
- `assets/` (folder)
- `includes/` (folder)
- `config/` (folder)
- `vendor/` (folder) **(required for PDF generation)**

### Critical: include `/vendor`

The “Update PDF” feature uses Dompdf (Composer). You must upload:

- `/home/www/cangowoodfiredpizza.atwebpages.com/vendor/`

If `vendor/` is missing, PDF generation will fail.

### File size limit

AwardSpace shows a **15 MiB** file size limit. If uploads fail via File Manager:

- Prefer **FTP** for large folders, or
- Upload a `.zip` and extract (if AwardSpace supports extracting), or
- Upload `vendor/` in smaller chunks.

## 2) Configure Database Connection

Edit this file in your project:

- `public_html/config/db.php`

Set the DB connection to:

- Host: `fdb1032.awardspace.net`
- Database: `4717274_cangowoodfiredb`
- User: `4717274_cangowoodfiredb`
- Password: `ForestGlade#10`

> Security note: Avoid committing hosting passwords to git. For a demo you can hardcode in the uploaded copy if needed, but keep it out of version control.

## 3) Import Database Schema and Seed Data (Order Matters)

In phpMyAdmin, select database `4717274_cangowoodfiredb`, then go to **Import**.

### Import order

1. `schema.sql`
2. `seed_menu.sql`
3. `seed_specials.sql`

Why:

- `schema.sql` creates tables and constraints.
- `seed_menu.sql` populates `size_definitions`, `settings`, and menu categories/items/variants.
- `seed_specials.sql` populates specials categories/items/variants.

If any import fails, capture the exact error text and which file caused it.

## 4) Smoke Test URLs

After upload + DB imports:

- Public Home: `https://cangowoodfiredpizza.atwebpages.com/index.php`
- Menu: `https://cangowoodfiredpizza.atwebpages.com/menu.php`
- Specials: `https://cangowoodfiredpizza.atwebpages.com/specials.php`
- Admin: `https://cangowoodfiredpizza.atwebpages.com/dashboard.php`

Admin credentials:

- Username: `admin`
- Password: `password`

## 5) Verify PDF Generation

In `dashboard.php`:

- Go to Settings (or Menu Manager)
- Click **Update PDF**

Expected outcome:

- A success toast
- PDF is generated and saved where the PHP endpoint writes it

If it fails:

- If you see “Error updating PDF (200)” again, it often means PHP output (warnings/notices) corrupted the JSON response.
- Check DevTools → Network → the `api/generate_menu_pdf.php` response text.

## 6) File Permissions (Common Shared Hosting Issue)

If PDF generation succeeds but saving fails:

- The output folder must be writable by PHP.
- If the code writes to something like `assets/menu.pdf` or `generated/menu.pdf`, the folder may need adjusted permissions.

If you hit a permissions error, share the exact error message and the file path it tried to write.

## Minimal Deployment Sequence (Quick Reference)

1. Upload `public_html/*` to `/home/www/cangowoodfiredpizza.atwebpages.com/`
2. Configure `public_html/config/db.php` with AwardSpace credentials
3. Import DB: `schema.sql` → `seed_menu.sql` → `seed_specials.sql`
4. Test `index.php`, `menu.php`, `specials.php`
5. Login `dashboard.php`
6. Test **Update PDF**
