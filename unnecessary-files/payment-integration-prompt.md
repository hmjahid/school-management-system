Prompt — Payment Integration for Modern School Management System (Bangladesh Focused)

Goal:
Integrate a secure, unified payment system into the existing Laravel + React + Tailwind School Management System to handle student fees, registration charges, exam fees, and donations.
The system should support Bangladeshi banks, local wallets (bKash, Nagad, Rocket, Upay, Cellfin) and international cards (Visa, MasterCard, American Express, UnionPay, Google Pay).

🎯 Objectives

Allow students, parents, or admins to pay any school-related fees online.

Support both domestic and international payment methods.

Provide instant payment confirmation, transaction history, and refund handling.

Integrate with a popular payment aggregator that connects to all major Bangladeshi gateways.

Ensure security, PCI-DSS compliance, and proper webhook handling.

All payments must be recorded in the system’s accounting module (fees, invoices, receipts).

⚙️ Technology Stack & Tools

Backend: Laravel 10+ (API)

Frontend: React + Tailwind CSS

Payment SDKs:

SSLCommerz API (Primary – supports Visa, MasterCard, AmEx, UnionPay, bKash, Nagad, Rocket, Upay, Cellfin, etc.)

Alternative fallback: AamarPay or ShurjoPay (optional)

Webhook handling: Laravel event listeners or jobs

Notifications: Laravel Mail + in-app notifications

Database: MySQL

PDF Invoices: laravel-dompdf or Snappy

💡 Required Features
1. Payment Modules

Tuition Fees

Auto-generate monthly invoices.

Pay online or mark as paid manually (admin override).

Partial payment support.

Admission / Registration Fees

One-time payment at admission form submission.

Instant approval after successful payment.

Exam Fees

Auto-generated exam-wise invoices (admin configurable).

Miscellaneous

Donations, event tickets, library fines, hostel fees, etc.

2. Payment Gateways Integration
a. SSLCommerz (Primary)

Use SSLCommerz Hosted Checkout (redirect method) for PCI compliance.

API Endpoints:

POST /api/payments/initiate

POST /api/payments/verify

POST /api/payments/webhook

Capture all response data: tran_id, amount, currency, card_type, store_id, val_id, etc.

Save payment log in DB (payments table).

b. Alternative Gateways (Optional)

Integrate AamarPay or ShurjoPay as backup gateways.

Allow switching between gateways from admin settings.

3. Payment Flow
Step 1: Generate Invoice

Admin or system auto-generates an invoice → stored in invoices table with status = pending.

Step 2: Initiate Payment

User (student/parent) clicks “Pay Now” → React frontend calls POST /api/payments/initiate with invoice ID.

Step 3: Redirect to Gateway

Laravel backend creates transaction record and redirects to SSLCommerz hosted page.

Step 4: Payment Confirmation

After successful payment, SSLCommerz calls the callback URL → Laravel verifies via val_id and updates the invoice as paid.

Step 5: Frontend Updates

React dashboard refreshes → shows success message, downloadable receipt, and sends an email confirmation.

Step 6: Webhook Events

Handle webhook for asynchronous confirmations and reconciliation.

4. Database Design (New Tables)

payments

Column	Type	Description
id	bigint	Primary key
invoice_id	bigint	Linked to fees or exam invoices
user_id	bigint	Student/parent who paid
gateway	varchar(50)	SSLCommerz, AamarPay, etc.
transaction_id	varchar(100)	Provided by gateway
amount	decimal(10,2)	Paid amount
currency	varchar(10)	Default BDT
status	enum('pending','success','failed','cancelled','refunded')	
card_type	varchar(50)	Visa, MasterCard, etc.
payment_method	varchar(50)	bKash, Nagad, etc.
response_payload	JSON	Store raw response
created_at	timestamp	—
updated_at	timestamp	—
5. Admin Dashboard Features

Payment history table with filters (date, user, method, status).

Export reports to CSV / PDF.

Manual payment addition (for cash/bank deposits).

Refund and reconciliation options.

Gateway settings UI (SSLCommerz credentials, mode: sandbox/live).

6. Frontend (React + Tailwind)

Payment screen (/payments/:invoiceId)

Show invoice details, amount, due date, “Pay Now” button.

After redirect and return:

/payment-success → show success message + “Download Receipt” button.

/payment-failed → show error message + retry option.

React hooks for fetching invoices & payment history.

Mobile-friendly payment confirmation screen.

Multi-language (English + বাংলা).

7. Security

Validate all transactions server-side via SSLCommerz validation API.

Use tran_id + val_id double verification.

Log failed or suspicious transactions.

Prevent duplicate payment attempts.

Support refunds via admin console (if gateway supports).

8. Notifications

On payment success:

Email receipt (Laravel Mail)

In-app notification on student/parent dashboard.

On payment failure:

Notify user to retry.

On refund:

Email + in-app alert.

9. Reporting

Daily, weekly, and monthly revenue charts (React + Chart.js).

Breakdown by gateway, method, and fee type.

Exportable financial reports (CSV/PDF).

10. API Endpoints Example
POST   /api/payments/initiate           # start payment
POST   /api/payments/verify             # verify manually (optional)
POST   /api/payments/webhook            # webhook for gateway callback
GET    /api/payments                    # list payments (admin)
GET    /api/payments/{id}               # view specific payment
GET    /api/invoices/{id}/receipt       # generate receipt PDF
PUT    /api/payments/{id}/refund        # issue refund

11. Example Laravel Controllers

PaymentController (initiate, verify, webhook)

InvoiceController (create, list, attach to payments)

ReportController (summaries, export)

GatewaySettingsController (admin credentials & test/live toggle)

12. Example React Components

InvoiceList.jsx

PaymentButton.jsx

PaymentStatus.jsx

ReceiptView.jsx

AdminPaymentDashboard.jsx

PaymentSettingsForm.jsx

13. Optional Enhancements

QR Code payment (for bKash/Nagad).

Auto SMS confirmation (Nagad SMS API).

Installment plan support.

Webhook retry & signature verification.

Multi-school support (for SaaS version).

✅ Deliverables

Laravel backend integrated with SSLCommerz sandbox & live.

React frontend payment UI (responsive + bilingual).

Webhook and admin reconciliation features.

Test suite (mock gateway responses).

Documentation with setup steps for live deployment.

Docker-compose with SSLCommerz environment variables.