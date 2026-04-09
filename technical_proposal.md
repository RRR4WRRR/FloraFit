# Technical Proposal: Bouquet Ordering System SMS Integration

## Overview
This proposal outlines the integration of an SMS notification system into an existing HTML/JS/CSS bouquet ordering platform. The goal is to provide real-time alerts to the business owner (or customer) when an order is placed.

## Proposed Architecture
- **Frontend:** HTML5, CSS3, and Vanilla JavaScript.
- **Backend:** Node.js with the Express framework.
- **SMS Gateway:** Twilio API (via the `twilio` npm package).
- **Environment Management:** `dotenv` for secure credential storage.
- **Cross-Origin Resource Sharing (CORS):** `cors` middleware to allow the frontend to communicate with the backend.

## Security Considerations
1. **Credential Safety:** All Twilio API keys (Account SID, Auth Token) must be stored in a `.env` file and **never** exposed in the frontend JavaScript.
2. **CORS Policy:** The backend should eventually be restricted to only allow requests from the official frontend domain.
3. **Validation:** The backend will validate order data before attempting to trigger an SMS.

## SMS Strategy (Trial Account)
- **Primary Method:** Twilio Trial Account.
- **Restriction Handling:** Use **Verified Caller IDs** in the Twilio Console to send notifications to the business owner's phone number as a "New Order" alert.
- **Scalability:** Upgrade to a Twilio Pay-As-You-Go plan ($20 deposit) to remove recipient verification requirements for customer-facing texts.
