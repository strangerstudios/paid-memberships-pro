# Paid Memberships Pro - Blockonomics Bitcoin Gateway

This add-on registers a `blockonomics` gateway for Paid Memberships Pro. It supports one-time membership checkouts, generates a unique Bitcoin address through the Blockonomics Payments API, stores the expected BTC amount on the PMPro order, and completes checkout when Blockonomics sends a confirmed callback.

## Setup

1. Install and activate Paid Memberships Pro.
2. Install this folder as a separate WordPress plugin and activate it.
3. In Memberships > Settings > Payment Gateway, choose `Blockonomics Bitcoin`.
4. Save the Blockonomics API key and callback secret.
5. Copy the generated HTTP callback URL into the Blockonomics merchant settings.

Recurring memberships are intentionally rejected because this gateway only receives one-time Bitcoin payments.
