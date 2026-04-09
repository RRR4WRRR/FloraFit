# Roadmap: Order Notification & Business Logic

## [x] Phase 1: Environment Setup
1. [x] Create a Telegram Bot via @BotFather.
2. [x] Configure `.env` with Bot Token and Chat ID.
3. [x] Import `florafit_db.sql` to MySQL.
4. [x] Ensure PHP cURL extension is enabled.

## [x] Phase 2: Database Stability & Account Setup
1. [x] Create `create_admin.php` for admin account initialization.
2. [x] Create `quick_create_florist.php` for florist account testing.
3. [x] Implement schema auto-check in `save_order.php` (Auto-create missing tables).
4. [x] Fix Inventory table issues (Category constraints).

## [x] Phase 3: Telegram Notification Integration (PHP)
1. [x] Create `sendTelegramNotification` helper in `stock_sms_helper.php`.
2. [x] Update `save_order.php` to trigger notifications on successful payment.
3. [x] Format notifications with Order ID, Customer, Items, and Total.

## [ ] Phase 4: Architectural Cleanup & Reorganization
1. [x] Create directory structure (`admin/`, `api/`, `assets/`, `florist/`, `includes/`, `scripts/`, `database/`).
2. [x] Move all scattered files into their respective folders.
3. [x] Update PHP `include`/`require` paths to point to `/includes`.
4. [x] Update root HTML/PHP files to reference `/assets` and `/admin` or `/florist` dashboards.
5. [ ] Update `fetch()` calls in JavaScript to use `/api` paths.
6. [ ] Update internal dashboard links and cross-file redirects.
7. [ ] Perform a full system sanity check.

## [ ] Phase 5: Business Logic Enhancements
1. [ ] Implement **Florist Commission** (5% of custom bouquet subtotal).
2. [ ] Display commissions on the Florist and Admin dashboards.
3. [ ] (Optional) Upgrade Telegram logic for customer notifications (Once live).

## [ ] Phase 6: Testing & Deployment
1. [ ] Final end-to-end testing (Order -> DB -> Telegram).
2. [ ] Deploy project to a live environment (Optional).
