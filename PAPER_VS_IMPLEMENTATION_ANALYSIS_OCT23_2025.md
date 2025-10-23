# Fanders LMS: Papers vs Implementation Analysis (2025-10-23)

## Scope and sources

This document compares the required operations from:

- `paper1.txt` (Requirements Specification, Oct 16, 2025)
- `paper2.txt` (Project Plan/Phases)
- `paper3.txt` (Requirements Engineering + Interview, Sep 12, 2025)

…against the current implementation in this repository (`/workspaces/Fanders`).

## Required operations (from papers)

- Roles and access
  - Roles: Administrator, Manager, Cashier, Account Officer (AO)
  - RBAC, session timeout, secure password hashing (bcrypt)
  - Admin powers: user CRUD, activate/deactivate, reset password

- Dashboards
  - Admin/Manager: portfolio value, active loans, overdue counts, real-time cash, alerts
  - Cashier: today’s collections, pending releases, cash blotter summary
  - AO: assigned clients, targets, overdue alerts

- Core loan lifecycle
  - Create loan (FR-001) with business rules:
    - 4 months (17 weeks), 5% monthly interest × 4
    - Insurance fee: ₱425 fixed (FR-009)
    - Auto-calc total, auto-generate 17-week schedule (FR-002, FR-003)
  - Track status: Applied → Approved → Disbursed (Active) → Closed; plus Overdue/Defaulted (FR-005)
  - Disbursement & SLR: produce SLR doc, record cash outflow (FR-007/FR-008)

- Client management
  - Client CRUD: name, email, phone (unique), address, status (active/inactive)
  - View: loans, last payment, outstanding balance
  - Deactivate, not delete

- Transactions (payments & releases)
  - Record weekly payments (FR-004), timestamp + audit (FR-010)
  - Real-time balance calculation (FR-005)
  - Digital Cash Blotter (FR-006): daily inflow/outflow, net cash; payments as inflow, SLR as outflow

- Collection sheets (UR-006/FR-006/FR-007)
  - AO: draft/submit sheets
  - Cashier: review/approve/post → becomes Payment entries; audit; cash blotter updated

- Alerts & overdue (FR-012)
  - Auto-flag overdue if scheduled payment missed
  - Alerts for 1 day overdue, and 1 week before due

- Reporting & export
  - Loans, payments, clients, users, transactions, financial summaries, overdue
  - Export to PDF/Excel

- Security & backup
  - Password hashing, session mgmt, audit trail
  - Automated daily backups to cloud; retention and recovery

- Data model (relational)
  - Tables: users, clients, loans, payments, cash_blotter, transactions (+ slr_documents, collection_sheets implied)
  - Fixed rules: 5%×4, ₱425 insurance, 17-week schedule

## Implementation overview (current codebase)

Key locations:

- Public endpoints: `public/` (auth, dashboard, loans, clients, payments, SLR, collection-sheets, cash-blotter, reports, backups, cron)
- Services: `app/services/` (AuthService, LoanService, LoanCalculationService, PaymentService, CashBlotterService, SLRService, LoanReleaseService, CollectionSheetService, ReportService, BackupService, TransactionService, ClientService, UserService, …)
- Models: `app/models/` (ClientModel, LoanModel, PaymentModel, CashBlotterModel, UserModel, TransactionModel, CollectionSheetModel, CollectionSheetItemModel, …)
- Utilities: `app/utilities/` (PDFGenerator, ExcelExportUtility, CSRF, Session, PasswordHash, FormatUtility, …)
- Templates: `templates/`
- Tests: `tests/`

Highlights:

- Calculations: `app/services/LoanCalculationService.php` implements 5% × 4, ₱425, 17-week schedule with rounding adjustments.
- Payments: `app/services/PaymentService.php` records payments atomically, caps to remaining balance, logs to audit, updates cash blotter, completes loans when fully paid.
- Cash blotter: `app/services/CashBlotterService.php` maintains daily inflow/outflow, recalc, charts; UI in `public/cash-blotter/*`.
- SLR/loan release: `app/services/SLRService.php` and `app/services/LoanReleaseService.php` create PDFs, archive, apply generation rules; UIs in `public/slr/*`.
- Collection sheets: `app/services/CollectionSheetService.php` supports AO draft/submit, Cashier approve/post → posts Payments; auditing; UIs in `public/collection-sheets/*`.
- Reporting: `app/services/ReportService.php` provides loans/payments/clients/users/transactions/overdue/financial summary, with PDF/Excel exports; UIs in `public/reports/*`.
- Audit trail: `app/services/TransactionService.php` logs auth, CRUD, system events (exportable to PDF).
- Auth/security: `app/services/AuthService.php`, `app/core/PasswordHash.php` (bcrypt), `app/utilities/CSRF.php`, `app/utilities/Session.php`.
- Backups: `app/services/BackupService.php` creates/compresses backups, stores metadata, has a placeholder cloud storage, cleanup; cron in `public/cron/*`.
- Dashboards: `public/dashboard/index.php` routes to role templates in `templates/dashboard/*`.

## Mapping: papers → implementation

What matches (fully or substantially):

- RBAC, sessions, bcrypt: Implemented
- Client management: Implemented (CRUD, status, uniqueness)
- Loan creation and calculations: Implemented per business rules; schedule generation present
- Payment recording, completion, balance updates: Implemented with transactions and audit logs
- SLR generation and loan release docs: Implemented with PDFs, archiving, generation rules; bulk generation
- Collection sheets: Implemented (AO draft/submit; Cashier approve/post) with audit logging
- Cash blotter: Implemented (daily inflow/outflow, recalc, summaries, charts) + UI
- Reporting and exports: Implemented (PDF/Excel) across major domains
- Audit trail: Implemented broadly
- Backup automation: Implemented (scheduled/manual, compression, retention cleanup placeholder)
- Dashboards: Implemented role-based views

Partially implemented or different:

- Overdue alerts (FR-012): Overdue reporting exists, but explicit alert timing rules (1-day overdue, 1-week advance) and notification delivery are not fully wired. Needs scheduled job + UI notifications and/or email/SMS.
- Savings component: Interview notes savings with payments; not present in code. Would require fields, business rules, and reports.
- Branch dimension: Papers mention cash blotter per branch; code is single-branch (no branch_id on blotter/loans/payments). Add branch model and scoping if multi-branch is required.
- Fixed term enforcement: Service allows 4–52 weeks though default is 17. If strictly 17, lock via UI/service/DB constraints.
- AO “assigned clients”: Collection sheets use `officer_id`, but global enforcement that AO only sees assigned clients is not fully visible. Add client-officer assignments and enforce across endpoints.
- Backups cloud integration/alerts: Uses placeholder cloud path; add real S3/GCS integration and failure alerts; surface status on dashboards.
- Security extras: Beyond passwords, encryption-at-rest for PII is not implemented (optional depending on scope).

## Entities and tables (observed/used)

- Core: `users`, `clients`, `loans`, `payments`, `cash_blotter`, `transactions`
- Phase 2/3: `slr_documents`, `slr_access_log`, `slr_generation_rules`, `collection_sheets`, `collection_sheet_items`, `system_backups`

These align with the papers; SLR and backups are present as per later phases.

## Recommended next steps (to close gaps)

1. Overdue alerts

- Add a daily scheduler to compute due/overdue vs. generated schedules.
- Implement “1 week in advance” and “1 day overdue” notifications (UI badges, optional email/SMS).
- Persist alerts (notifications table) and show on dashboards.

1. Savings tracking

- Add savings components to payments/schedules (or a savings ledger).
- Update calculations, SLR/schedules and client summaries; reflect in reports.

1. Branch support

- Introduce `branches` and add `branch_id` to loans, payments, cash_blotter, and optionally users.
- Scope dashboards, blotter, and reports by branch; add role-based branch access.

1. Enforce 17-week term (if strictly required)

- Lock term in UI, validate in service, and (optionally) constrain in DB.

1. AO client assignment enforcement

- Add `client_officer_assignments`; enforce filters across clients/loans/payments/reports; update permissions.

1. Production backups

- Integrate S3/GCS and add alerting; show last-success/fail on admin dashboard; finalize retention policy.

1. Tests

- Add tests for overdue calculations, collection sheet posting flows, cash blotter summaries, and backup cloud integration error paths.

## Notable files (for quick reference)

- Calculations and schedule: `app/services/LoanCalculationService.php`
- Payments and completion: `app/services/PaymentService.php`
- Cash blotter: `app/services/CashBlotterService.php` and `public/cash-blotter/*`
- SLR: `app/services/SLRService.php`, `app/services/LoanReleaseService.php`, `public/slr/*`
- Collection sheets: `app/services/CollectionSheetService.php`, `public/collection-sheets/*`
- Reporting: `app/services/ReportService.php`, `public/reports/*`
- Backups: `app/services/BackupService.php`, `public/cron/*`
- Audit: `app/services/TransactionService.php`
- Auth/session/security: `app/services/AuthService.php`, `app/core/PasswordHash.php`, `app/utilities/CSRF.php`, `app/utilities/Session.php`
- Dashboards: `public/dashboard/index.php`, `templates/dashboard/*`

## Conclusion

- The codebase substantially matches the proposed LMS in the papers across core operations (auth, clients, loans, calculations, payments, SLR, cash blotter, collection sheets, reporting, audit, backups, dashboards).
- Gaps are focused on alerting (timed overdue notifications), savings handling, potential branch scoping, stricter term rules, AO assignment enforcement, and production-grade backups/alerts.
- Prioritizing overdue alerts and backup reliability next will deliver the most visible operational gains.
