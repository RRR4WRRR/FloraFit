# Dev Log: Bouquet Ordering System SMS Integration

## [2026-04-09] - Initial Design & Architecture
### Progress
- **Project Discovery:** Initial discussion on adding an SMS notification feature to an HTML/JS/CSS bouquet ordering system.
- **Architectural Decision:** Selected a **Node.js/Express** backend for security reasons (to avoid exposing Twilio credentials in the browser).
- **Service Recommendation:** Recommended **Twilio** for its reliability and developer-friendly SDK.
- **Trial Strategy:** Discussed the limitations of Twilio's free trial (specifically "Verified Caller IDs") and how to bypass them for testing (by notifying the business owner instead of the customer).

## [2026-04-09] - Shift to Telegram & Database Stability
### Progress
- **Database Fixes:** Imported `florafit_db.sql` and fixed table schema issues (Inventory, Orders).
- **Authentication:** Created Admin and Florist initialization scripts.
- **Service Pivot:** Due to regional restrictions on Twilio/SMS Trial accounts in the Philippines, pivoted to **Telegram Bot API** for real-time notifications.
- **Backend Refactor:** Moved notification logic directly into PHP (`save_order.php`) to reduce infrastructure complexity.
- **Testing:** Verified Telegram notifications for new orders (Order #, Customer, Total, Items).

### Key Decisions
- [x] Use Telegram Bot API for free, instant notifications.
- [x] Handle notifications in PHP using cURL to avoid extra middleware.
- [x] Automate database table checks during order placement to ensure reliability.

## [2026-04-09] - Architectural Cleanup & Reorganization
### Progress
- **Directory Restructuring:** Created a professional directory system (`/admin`, `/api`, `/assets`, `/florist`, `/includes`, `/scripts`, `/database`) to organize scattered files.
- **Dependency Updates:** Updated PHP `include`/`require` paths across all migrated files to point to the new `/includes` directory.
- **Asset Migration:** Moved all CSS, JS, and static images into `/assets` and updated root HTML/PHP references.
- **API Consolidation:** Moved 40+ PHP backend scripts to the `/api` directory for better separation of concerns.
- **Security:** Updated the `.env` path in `chatbot.php` to securely reference the root directory from its new subfolder.

