
AfricaERP / AMSAAS
Real Estate ERP SaaS Platform
PHASED DEVELOPMENT MASTER PLAN

Sequenced task breakdown to eliminate architectural debt,
enforce correct build order, and prevent costly refactoring.

Stack: Laravel 12  |  Vue 3  |  PostgreSQL 16  |  Redis
Classification: Internal — Confidential

00 — How to Read This Document
This plan sequences every remaining development task in strict dependency order. The cardinal rule is simple: never begin a module before all items listed in its 'Must Come After' column are fully production-stable — backend, frontend, and tested.

The project has already paid the cost of re-starting from scratch. This document exists to prevent that from happening again. Each phase is a stable, deployable checkpoint. Do not skip phases, do not begin phase N+1 before phase N is complete and passes its acceptance criteria.

Colour guide — Dependency cells (yellow) = what must be done first. Estimate cells (blue) = working days for 2 senior full-stack developers. Warnings (orange) = architectural guardrails that must not be bypassed.

CRITICAL RULE: No frontend component should be built before its backend API is stable, documented, and returning the correct JSON contract. Building the UI against an incomplete API is the leading cause of rework on this project.

01 — Current State Assessment
The following matrix shows what is production-ready, what is partially done, and what has not started. This is the baseline from which the phased plan begins.

Module
Backend
Frontend
Tests
Action Required
Company & Multi-Tenancy
✓ Done
✓ Done
✓ Done
None — Production Ready
Building Management
✓ Done
✓ Done
Partial
Complete test coverage (Phase 0)
Apartment / Unit
✓ Done
✓ Done
Partial
Complete test coverage (Phase 0)
Tenant Profiles
✓ Done
✓ Done
Partial
Complete test coverage (Phase 0)
Rental Agreements
✓ Done
✓ Done
None
Add tests + vacancy & proration logic (Phase 0)
Meter Management
✓ Done
✓ Done
Partial
Complete test coverage (Phase 0)
Meter Reading & Approval
✓ Done
✓ Done
None
Add tests — feeds billing engine (Phase 0)
Charge Types
✓ Done
✗ None
None
Build frontend + tests (Phase 1)
Charge Models (Pricing Engine)
Model only
✗ None
None
CRITICAL BLOCKER — complete backend + full UI (Phase 1)
Utility Billing Engine (Charges)
75%
✗ None
None
Complete service layer + charge UI (Phase 1)
Invoice Engine
✗ None
✗ None
None
Full build — Phase 2
Payment Engine
✗ None
✗ None
None
Full build — Phase 3
Receivables & Collections
✗ None
✗ None
None
Full build — Phase 4
Apartment Sales Domain
10%
✗ None
None
Full build — Phase 5
General Ledger / Accounting
✗ None
✗ None
None
Full build — Phase 6


02 — Master Dependency Chain
This is the non-negotiable build sequence. Every arrow represents a hard technical dependency. The system cannot function correctly if this order is violated.

Read this as: you cannot start a module until everything to its left / above it is production-stable. This is the map that was missing in the first development attempt.

Domain A — Rental Billing Pipeline (Must complete in order)

Company & Auth
→
Building Apartment Tenant
→
Rental Agreement Meters
→
Charge Types & Charge Models
→
Meter Readings & Charges
→
Invoice Engine
→
Payment Engine

Domain B — Sales Pipeline (Requires Financial Domain first)

Invoice + Payment Engines
→
Property Inventory
→
Reservation & Deposit
→
Sales Contract
→
Instalment Plan Engine
→
Ownership Transfer
→
G/L Acct.


PHASE 0 — Foundation Hardening
Estimated duration: 1 week | Goal: Lock the existing codebase so it is a stable, tested foundation before any new module is added. Phase 0 is entirely about quality, not new features.

Do NOT skip Phase 0. Starting new features on top of untested code is the exact mistake that caused the original restart. Every test added here prevents 3 bugs in later phases.

Phase 0 Task Breakdown

Module / Task
Key Implementation Tasks
Must Come After
Definition of Done
Est.
0.1 — Test Suite Baseline
Run full PHPUnit suite. Document all failing or missing tests. Set up CI pipeline to fail on <80% coverage. Enforce PHPStan Level 6+.
Nothing — start here
CI pipeline runs on every push. Coverage report generated. No unchecked failures.
2 days
0.2 — Multi-Tenancy Tests
Write cross-tenant isolation tests for Building, Apartment, Tenant, Meter, Agreement models. Verify GlobalScope fires on all queries. Test that no endpoint returns data across company_id boundaries.
0.1 CI pipeline
Automated tests prove zero cross-tenant data leakage on all existing models.
2 days
0.3 — Agreement State Machine
Implement and test full state machine: DRAFT → PENDING → ACTIVE → TERMINATED / EXPIRED. Add ProrateRentService for mid-month agreements. Add vacancy tracking for gap periods. Reject billing calls against non-ACTIVE agreements.
Building, Apartment, Tenant modules stable
All state transitions tested. ProrateRentService returns correct amount for any start/end date. Billing against non-ACTIVE agreement throws BusinessRuleException.
3 days
0.4 — Meter Reading Tests
Write feature tests: reading submission, anomaly detection flag, approval transition (DRAFT → APPROVED is irreversible). Verify the GenerateChargeService event dispatch fires on approval.
0.3 Agreement state machine
MeterReading approval tested end-to-end. Event fires confirmed in test assertions.
2 days
0.5 — Financial Precision Audit
Audit all existing monetary columns: verify decimal(14,4) not float. Audit all financial calculations: replace any float arithmetic with BCMath. Add database constraints: no negative balances on posted records.
0.1 CI pipeline
Zero float columns on financial tables. All monetary arithmetic uses bcadd/bcmul/bcdiv. Migration added where needed.
1 day

Phase 0 Exit Criteria: All existing modules pass automated tests. CI/CD pipeline operational. No float arithmetic in financial code. Multi-tenancy isolation verified.


PHASE 1 — Charge Engine (Critical Blocker)
Estimated duration: 2.5 weeks | Goal: Make the billing engine fully operational. This phase unblocks all financial modules. Without it, the system cannot generate any financial output.

SINGLE HIGHEST PRIORITY: The Invoice Engine, Payment Engine, and ALL financial testing are blocked until Phase 1 is complete. Build nothing in Phase 2 or beyond until this phase passes all acceptance criteria.

Phase 1A — Charge Types (Frontend)
The backend is complete. ChargeTypes define the financial taxonomy (Electricity, Water, Rent, Service Fee, etc.). They must be configurable in the UI before ChargeModels can reference them.

Module / Task
Key Implementation Tasks
Must Come After
Definition of Done
Est.
1A.1 — ChargeType API Resources
Build ChargeTypeResource with controls object (can_edit, can_delete). Build ChargeTypeCollection. Ensure API returns correct JSON envelope. Write feature tests: index, store, show, update, destroy, cross-tenant isolation.
Phase 0 complete
All 5 CRUD endpoints return correct JSON contract. 90%+ feature test coverage. Controls object present in every response.
1 day
1A.2 — ChargeType Vue UI
Build ChargeTypeIndex.vue (table with category filter, status badge). Build ChargeTypeCreate.vue (name, category selector, description, is_active toggle). Build ChargeTypeEdit.vue (pre-populated form). Add to navigation. Implement loading, empty, and error states on all components.
1A.1 API stable
All CRUD operations work in UI. Destructive actions use confirmation modal. Toast notifications on all async actions.
2 days

Phase 1B — Charge Models (Critical Path)
This is the most complex UI in Phase 1. ChargeModels define pricing rules — the billing engine reads nothing else. The dynamic form must handle all 5 pricing strategies with proper validation.

Module / Task
Key Implementation Tasks
Must Come After
Definition of Done
Est.
1B.1 — ChargeModel Backend Completion
Build ChargeModelController (index, store, show, update, destroy). Build ChargeModelStoreRequest and ChargeModelUpdateRequest with full validation for all pricing strategies. Build ChargeModelResource with nested strategy fields and controls object. Write comprehensive unit tests for each pricing strategy calculation.
Phase 0 complete ChargeTypes backend (1A.1)
All endpoints stable. Validation rejects invalid tiered rate configurations. Each pricing strategy (Fixed, Metered, Tiered, Percentage, Formula) has dedicated unit tests. 95%+ coverage.
3 days
1B.2 — ChargeModelIndex.vue
Filterable table by utility_type, pricing_type, status. Status badges (active/inactive/expired). Quick clone action for duplicating similar models. Pagination. Loading skeletons, empty state with CTA.
1B.1 API stable
All filter combinations work. Clone creates a draft copy correctly. Empty state renders with add-model CTA.
1 day
1B.3 — ChargeModelCreate.vue
Multi-section form with strategy selector. Dynamic fields per strategy: Fixed (flat rate), Metered (unit price + multiplier), Tiered (dynamic row table for consumption bands), Percentage (rate + base charge selector), Formula (expression editor with variable preview). Tax rule section. Effective date range. Billing frequency. Late fee config. Validation: tiered bands must be contiguous and non-overlapping.
1B.2 Index complete
All 5 pricing strategies create valid ChargeModel records. Tiered band validation prevents overlaps. Form rejects submission if effective_to is before effective_from.
4 days
1B.4 — ChargeModelEdit.vue
Pre-populated form identical to Create. CRITICAL: editing an active model must create a new version with future effective_date — never mutate an in-use model. Show version history panel listing previous configurations.
1B.3 Create complete
Editing active model creates new version. Original model unchanged. Version history displays correctly.
2 days
1B.5 — ChargeModelShow.vue
Read-only detail view. Visual pricing breakdown per strategy. Tier visualisation table for tiered models. List of charges generated from this model. Activate / deactivate controls. Link to associated readings.
1B.4 Edit complete
All pricing strategy types display correctly. Controls respect API controls object.
1 day

Phase 1C — Charge Generation & Approval

Module / Task
Key Implementation Tasks
Must Come After
Definition of Done
Est.
1C.1 — Complete Billing Engine Services
Finalise GenerateChargeService: complete all 5 pricing strategy handlers. Add idempotency guard (unique constraint on meter_reading_id + charge_model_id). Complete CalculateChargeService with BCMath precision. Add ApplyTaxRules. Write unit tests for every pricing strategy including edge cases: zero consumption, anomalous readings, tiered boundary values.
1B.1 ChargeModel backend Phase 0 meter reading tests
95%+ unit test coverage on all calculation paths. Zero consumption produces zero charge. Duplicate charge prevention confirmed by test.
3 days
1C.2 — Charge Review Frontend
Build ChargeIndex.vue: list of generated charges per apartment/period. Status filters (draft, approved, rejected). Link back to source meter reading. Build charge approval workflow: Approve / Reject with mandatory reason on rejection. Batch approve capability.
1C.1 services complete 1B ChargeModel UI done
Charges visible after meter reading approval. Approve/reject actions work with confirmation modal. Batch operations work correctly.
2 days
1C.3 — Charge State Machine Tests
Write 100% state transition coverage: draft → approved (irreversible), draft → rejected, approved charge cannot be re-rejected. Verify no charge created against non-ACTIVE agreement. Write integration test: full pipeline from meter reading approval → GenerateChargeService → Charge record.
1C.1 and 1C.2
All state transitions tested. Integration test passes end-to-end.
1 day

Phase 1 Exit Criteria: ChargeModel UI fully functional for all 5 pricing strategies. Meter reading approval generates correct charge records. Charges visible and approvable in UI. Billing engine has 95%+ test coverage.


PHASE 2 — Invoice Engine
Estimated duration: 3 weeks | Goal: Convert approved charges into formal, numbered, immutable tenant invoices. This is the central financial output of the entire platform.

Phase 2 may not begin until Phase 1 is complete and passing. The invoice engine depends on stable charge records, charge models, and a working billing pipeline.

Phase 2A — Invoice Backend

Module / Task
Key Implementation Tasks
Must Come After
Definition of Done
Est.
2A.1 — Invoice Data Model & Migration
Create invoices table: invoice_number (unique, company-prefixed), status enum (DRAFT, POSTED, PAID, PARTIAL, OVERDUE, VOID), billing_period_start/end, subtotal, tax_total, grand_total (all decimal(14,4)), due_date, outstanding_balance. Create invoice_lines table: invoice_id, charge_id, description, quantity, unit_price, tax_rate, line_total. Add SoftDeletes to both. Add StatusEnum class. Add unique constraint preventing duplicate invoice per tenant+period.
Phase 1 complete
Migration runs cleanly. No float columns. SoftDeletes on both tables. StatusEnum covers all states.
1 day
2A.2 — InvoiceNumberService
Implement sequential, non-restarting, company-prefixed invoice numbering (e.g. AMS-2025-00142). Numbers must never be reused, even after void. Use database-level sequence or advisory lock to prevent race conditions under concurrent generation. Write concurrency tests.
2A.1 model done
Sequential numbers generated correctly. Concurrent generation test (10 simultaneous requests) produces no duplicates.
1 day
2A.3 — InvoiceGenerationService
Aggregate approved charges by tenant + billing period into invoice + line items. Must be idempotent: calling twice for same period must not create duplicates. Validate all charges reference ACTIVE agreement. Compute totals from line items using BCMath. Set due_date from rental agreement payment terms.
2A.2 number service 1C approved charges
Idempotency test: calling twice creates one invoice. All totals verified with BCMath. Charges from inactive agreements rejected.
2 days
2A.4 — InvoicePostingService
Transition invoice DRAFT → POSTED. Validate all line items, lock financial amounts (immutable after posting). Update tenant outstanding_balance. Transition must be atomic (DB transaction). Reject posting if any line item charge is not in approved status.
2A.3 generation service
Posted invoices cannot be edited. Outstanding balance updated correctly. Posting non-approved charge throws BusinessRuleException. 100% state transition test coverage.
2 days
2A.5 — InvoiceVoidService
Void workflow: POSTED → VOID (only, never delete). Voided invoices remain in audit trail. Reverse outstanding_balance adjustment. Require reason. Create credit note if payment was already allocated. Write reversal tests.
2A.4 posting service
Void preserves record in DB. Balance reversed correctly. Credit note created when required.
1 day
2A.6 — PDF Invoice Generation
Invoice PDF template: company branding, invoice number, billing period, tenant details, line items table, tax summary, total due, payment instructions, payment methods (EVC Plus, bank transfer). Use DOMPDF or similar. Queue PDF generation asynchronously. Store in S3-compatible storage.
2A.4 posting service
PDF generated for every posted invoice. Company logo and currency display correctly. PDF queued and stored, not blocking the HTTP response.
3 days
2A.7 — Email Dispatch Queue
Queue job: send invoice PDF to tenant email on posting. Include payment instructions in email body. Retry logic for failed sends. Log dispatch status on invoice record. Configurable: per-company opt-out for auto-send.
2A.6 PDF service
Email queued on invoice posting. Failed jobs retried. dispatch_status updated on invoice.
1 day
2A.8 — Invoice API & Resources
InvoiceController: index (paginated, filterable by status/period/tenant), show, store (manual), post action, void action. InvoiceResource: all fields, line items, controls object (can_post, can_void, can_edit). InvoiceCollection with balance summary in meta. Write 90%+ feature test coverage.
2A.1–2A.7 complete
All endpoints return correct contract. Controls object drives UI button visibility. 90%+ feature test coverage.
2 days

Phase 2B — Invoice Frontend

Module / Task
Key Implementation Tasks
Must Come After
Definition of Done
Est.
2B.1 — InvoiceIndex.vue
Paginated table with status filter (DRAFT/POSTED/PAID/PARTIAL/OVERDUE/VOID), date range filter, tenant search, building filter. Balance due summary cards at top. Color-coded status badges. Loading skeletons. Empty state.
2A.8 API stable
All filters work. Balance summary accurate. Status badges correct colours.
2 days
2B.2 — InvoiceShow.vue
Full invoice detail view. Line items table with tax breakdown. Outstanding balance display. PDF preview panel (iframe). Action bar: Post Invoice button (with confirmation modal stating consequence), Void button, Resend Email button. All actions respect controls object from API. Payment history section.
2B.1 Index done
PDF renders in preview. Post/Void/Resend respect controls. Confirmation modal shows exact financial consequence.
2 days
2B.3 — InvoiceCreate.vue (Manual)
Manual invoice creation form: tenant selector, billing period, line item entry (description, quantity, unit price, tax rate). Line totals computed in real-time. Add/remove line items. Validation: at least one line item, positive amounts, future or current due date.
2B.2 Show done
Manual invoice creates correctly. Real-time total calculation works. Validation prevents zero-amount invoices.
2 days
2B.4 — Tenant Billing View
Tenant-facing invoice history: all invoices for a specific tenant across all their agreements. Outstanding balance total. Invoice status timeline. Download PDF link per invoice.
2B.1–2B.3 done
Correct invoices shown per tenant. Outstanding balance matches server calculation.
1 day

Phase 2 Exit Criteria: Invoices generated from approved charges. PDF produced and emailed. Invoice state machine fully tested (100%). InvoiceIndex, Show, Create, and Tenant views operational. Manual invoice creation works.


PHASE 3 — Payment Engine
Estimated duration: 2.5 weeks | Goal: Record and allocate tenant payments. Close the financial loop: Charge → Invoice → Payment → Paid.

Phase 3 may not begin until Phase 2 is complete. The payment engine allocates against invoices — it requires the invoice engine to be fully operational and tested.

Phase 3A — Payment Backend

Module / Task
Key Implementation Tasks
Must Come After
Definition of Done
Est.
3A.1 — Payment Data Model
Create payments table: amount, payment_method enum (cash, bank_transfer, mobile_money, evc_plus, mpesa, refund), reference, received_date, received_by, allocated_amount, unallocated_amount, status. Create payment_allocations table: payment_id, invoice_id, allocated_amount. SoftDeletes on both. decimal(14,4) on all monetary columns.
Phase 2 complete
Migration clean. No float columns. Payment method enum covers all planned methods.
1 day
3A.2 — PaymentGatewayInterface
Define PaymentGatewayInterface: initiate(), verify(), refund() methods. Build concrete implementation for manual recording. Build abstract base for mobile money gateways. Ensure PaymentEngine never contains provider-specific code.
3A.1 model done
Interface defined. Manual implementation passes all tests. Gateway swap requires no changes to PaymentEngine.
1 day
3A.3 — PaymentAllocationService
FIFO allocation: oldest unpaid invoice first by default. Partial payment: invoice status → PARTIAL, outstanding_balance decremented. Overpayment: excess held as tenant credit (unallocated_amount on payment). All in DB::transaction(). Balance never goes negative. Test: partial payment, overpayment, multi-invoice allocation.
3A.2 interface done Phase 2 invoice engine
FIFO allocation verified by test. Partial payment leaves correct outstanding balance. Overpayment credited, not lost. 95%+ coverage.
3 days
3A.4 — EVC Plus Webhook Integration
Receive and validate EVC Plus payment notification webhook. Signature verification. Idempotent processing (duplicate webhook produces no duplicate payment). Map webhook payload to payment record. Trigger PaymentAllocationService. Rate limited (30/min per architecture spec). Write webhook processing tests with simulated payloads.
3A.3 allocation service
Duplicate webhook test passes. Signature validation rejects tampered payloads. Allocation triggered automatically.
3 days
3A.5 — Refund Workflow
Refund as reverse payment record (type: REFUND). Approval-gated: requires manager sign-off. Reversal journal entry created. Never delete a payment record. Test: refund reverses outstanding balance correctly.
3A.3 allocation service
Refund record created. Approval required. Balance reversed. Original payment record intact.
2 days
3A.6 — Payment API & Resources
PaymentController: index, store, show, allocate action, refund action. PaymentResource: all fields, allocations nested, controls (can_refund, can_reallocate). Feature tests: success paths, over-allocation prevention, cross-tenant isolation. 90%+ coverage.
3A.1–3A.5 complete
All endpoints tested. Over-allocation prevented by service. Cross-tenant test passes.
2 days

Phase 3B — Payment Frontend

Module / Task
Key Implementation Tasks
Must Come After
Definition of Done
Est.
3B.1 — PaymentIndex.vue
Payment register: list of all payments with method, amount, reference, allocated status. Filter by method, date range, status. Unallocated balance warning indicator. Loading skeletons, empty state.
3A.6 API stable
All filters work. Unallocated balance clearly flagged.
1 day
3B.2 — RecordPayment.vue
Record payment form: tenant selector (with outstanding balance shown), amount, payment method selector, reference number, received date, received by. Manual invoice selection or auto-FIFO toggle. Real-time allocation preview showing which invoices will be paid/partial. Confirmation step showing exact allocation breakdown.
3B.1 Index done
Auto-FIFO and manual selection both work. Allocation preview matches actual allocation. Overpayment clearly communicated.
3 days
3B.3 — PaymentReceipt.vue
Payment receipt view: payment details, allocated invoices, remaining credit if overpayment. PDF receipt download. Print-friendly layout. EVC Plus payment reference display.
3B.2 Record done
Receipt generates correctly. PDF downloadable. EVC Plus reference visible.
1 day
3B.4 — Tenant Balance Dashboard
Per-tenant financial summary: total invoiced, total paid, outstanding balance, credit balance, payment history timeline. Outstanding invoices list with Pay Now shortcut.
3B.1–3B.3 done
Balance calculation matches server. Payment history accurate. Pay Now opens pre-filled form.
1 day

Phase 3 Exit Criteria: Payments recorded and allocated against invoices. Invoice status updates to PAID/PARTIAL automatically. EVC Plus webhooks processed. Refund workflow operational. Tenant balance dashboard accurate.


PHASE 4 — Receivables & Collections
Estimated duration: 1.5 weeks | Goal: Visibility and action on unpaid balances. Convert financial data into operational intelligence for collections staff.

Module / Task
Key Implementation Tasks
Must Come After
Definition of Done
Est.
4.1 — Aging Report Service
AgingReceivablesService: bucket outstanding invoices into current, 30-day, 60-day, 90-day, 90+ day buckets. Real-time via DB aggregation (no cached balances). Group by building, tenant, or portfolio view. Export to CSV/PDF.
Phase 3 complete
Aging buckets calculated correctly vs. due_date. Report exportable. No stale cached data.
2 days
4.2 — Delinquency Tracking
Scheduled job: flag invoices as OVERDUE when past due_date. DelinquencyFlag record created with first_overdue_date. Escalation stages: 1st notice, 2nd notice, legal handoff. Status badge update triggers on flag creation.
4.1 aging report
Scheduled job tested with simulated dates. Overdue flag fires correctly. Escalation stages configurable per company.
2 days
4.3 — SMS/Email Reminder System
Configurable reminder schedule per company (e.g. 7 days before due, day of due, 3 days overdue, 7 days overdue). Queue-based dispatch. Tenant opt-out support. Log all reminders sent on invoice/tenant record.
4.2 delinquency tracking
Reminder fires at correct intervals. Opt-out respected. Dispatch log on tenant record accurate.
2 days
4.4 — Collections Dashboard (Frontend)
AgingReport.vue: visual aging chart + tabular breakdown. Delinquency list with escalation stage filter. Bulk action: send reminder to selected tenants. Individual tenant detail: full collection history, notes field. Legal handoff flag action.
4.1–4.3 backend done
Aging chart matches service calculation. Bulk reminder triggers queued correctly. Legal handoff flag saves to record.
3 days
4.5 — Notice Generation
PDF notice templates: 1st reminder notice, formal demand letter, legal handoff notice. Company branding applied. Queue-based generation. Linked to collection workflow stage.
4.4 dashboard done
All 3 notice types generate correct PDF. Company name and tenant details populated.
2 days

Phase 4 Exit Criteria: Aging report accurate and exportable. Overdue invoices automatically flagged. Reminder system dispatches at correct intervals. Collections dashboard operational.


PHASE 5 — Apartment Sales Domain
Estimated duration: 7 weeks | Goal: Full property sales lifecycle from inventory to ownership transfer, including instalment plan engine. This is an independent domain that shares only Apartment, Company, and Financial modules.

ARCHITECTURE DECISION: SaleAgreement is a separate model from RentalAgreement. Do not reuse or extend RentalAgreement — the state machines, financial rules, and lifecycle are fundamentally different. Model contamination here creates irreversible architectural debt.

Phase 5A — Property Inventory (Week 1)

Module / Task
Key Implementation Tasks
Must Come After
Definition of Done
Est.
5A.1 — Unit Availability State
Define authoritative apartment status enum: AVAILABLE, RESERVED, UNDER_CONTRACT, UNDER_RENTAL, SOLD, MAINTENANCE. Single source of truth — both Rental and Sales domains must read/write through this. Prevent concurrent reservations with optimistic locking (version column). Prevent sale of an UNDER_RENTAL unit.
Phase 3 complete
Concurrent reservation test (race condition) produces exactly one reservation. Unit under rental agreement cannot be reserved for sale.
2 days
5A.2 — Property Inventory Module
InventoryController: list available units (filterable by building, floor, bedrooms, price range). Unit detail with full specification. Status history log. API and Vue frontend: InventoryIndex.vue, UnitAvailabilityCard.vue.
5A.1 status enum
Available units list correct. Status history displays all transitions. Filter combinations work.
3 days
5A.3 — Buyer Profile
Buyer model: distinct from Tenant. Separate table. Link field: tenant_id (nullable) for buyers who later become tenants. Identity fields, contact, address (same JSON structure as Tenant for consistency). API + Vue CRUD.
5A.1 inventory done
Buyer record creates independently of Tenant. Link to Tenant record works. No data from Tenant bleeds into Buyer by default.
2 days

Phase 5B — Reservation & Contract (Week 2)

Module / Task
Key Implementation Tasks
Must Come After
Definition of Done
Est.
5B.1 — Reservation Engine
ReservationService: create reservation, collect deposit (via PaymentEngine). Set expiry_date. ReservationExpiryJob: scheduled daily, returns expired unpaid reservations to AVAILABLE. Refund policy config per company. Unit status → RESERVED atomically.
5A complete Phase 3 Payment Engine
Expiry job tested with simulated dates. Concurrent reservation test passes. Deposit recorded as payment.
3 days
5B.2 — Sales Contract
SaleAgreementModel: buyer_id, apartment_id, total_price (immutable after execution), payment_type (CASH / INSTALMENT), down_payment_percentage, instalment_count, contract_date, status enum. SaleAgreementController + Resource. Total price locked on execution. Unit status → UNDER_CONTRACT.
5B.1 reservation
Total price cannot be changed after contract execution. Status transition tested. Duplicate contract for same unit prevented.
3 days
5B.3 — Cash Sale Workflow
Full payment → SaleAgreementPostingService → OwnershipTransferService. Payment allocated against contract. Completion certificate PDF generated. Unit status → SOLD.
5B.2 contract Phase 3 Payment Engine
Full cash sale pipeline tested end-to-end. Certificate PDF generated. Unit shows SOLD in inventory.
2 days

Phase 5C — Instalment Plan Engine (Weeks 3–4)

Module / Task
Key Implementation Tasks
Must Come After
Definition of Done
Est.
5C.1 — InstallmentPlanGeneratorService
Compute full schedule: (total_price - down_payment) / instalment_count. Each instalment = future-dated Charge + Invoice record. Schedule immutable after generation. Support: early repayment (lump sum with balance recalculation), grace period config, late fee charges via ChargeModel.
5B.2 contract Phase 2 Invoice Engine
Generated schedule totals match contract amount. Early repayment recalculates remaining correctly. Late fee charge fires after grace period.
4 days
5C.2 — Monthly Instalment Billing
Scheduled billing job: on each instalment due date, post the invoice. Integrate with existing PaymentEngine for payment receipt. Outstanding balance updated per instalment. Late fee trigger if not paid within grace period.
5C.1 plan generator
Billing job posts correct invoice on due date. Late fee fires on configured grace period + 1 day.
3 days
5C.3 — Instalment UI
InstalmentPlanView.vue: visual payment schedule (timeline + table). Paid/pending/overdue status per instalment. Record payment shortcut per line. Outstanding balance progress bar. Early repayment modal with recalculated schedule preview.
5C.1–5C.2 backend
Schedule displays all instalments. Status badges update after payment. Early repayment preview shows correct new schedule.
3 days

Phase 5D — Ownership Transfer (Week 5)

Module / Task
Key Implementation Tasks
Must Come After
Definition of Done
Est.
5D.1 — OwnershipTransferService
Trigger: final instalment cleared (outstanding_balance = 0). Approval chain: configurable (legal / finance / manager). OwnershipHistory record: buyer_id, apartment_id, transfer_date, contract_id. Unit status → SOLD. Buyer → Owner. All future communications routed to owner profile.
5C fully settled
Transfer only initiates at zero balance. Approval chain blocks transfer without sign-off. OwnershipHistory record created.
2 days
5D.2 — Legal Document Generation
PDF templates: Sales Contract, Instalment Payment Schedule, Completion Certificate, Ownership Transfer Certificate. Company branding. Queued generation. Downloadable from SaleAgreement detail view.
5D.1 transfer service
All 4 document types generate correctly. Contract amounts match SaleAgreement record.
3 days
5D.3 — Sales Domain Frontend (Full)
SalesIndex.vue: inventory browser with availability filter. SaleAgreementIndex.vue: all contracts by status. SaleAgreementShow.vue: full lifecycle view (reservation → contract → instalments → transfer). OwnershipHistory.vue: per-unit ownership log.
5D.1–5D.2 complete
Full lifecycle navigable in UI. Ownership history visible per unit.
4 days

Phase 5 Exit Criteria: Full sales pipeline operational (cash and instalment). Instalment schedule generated, billed, and paid correctly. Ownership transfer requires approval and produces correct documents. Inventory availability authoritative.


PHASE 6 — General Ledger & Accounting
Estimated duration: 4 weeks | Goal: Double-entry accounting layer that auto-posts from all financial events. This is the last phase — it requires the entire financial domain to be stable first.

Phase 6 must not begin until Phases 1–5 are complete and stable. The accounting layer is a read-and-post consumer of all financial events. Building it on incomplete financial data produces an unusable ledger.

Module / Task
Key Implementation Tasks
Must Come After
Definition of Done
Est.
6.1 — Chart of Accounts
Account model: code, name, type (Asset, Liability, Equity, Revenue, Expense). Company-configurable. Seed default accounts for: Rent Revenue, Utility Revenue, Accounts Receivable, Cash, Deferred Revenue, Security Deposits. API + Vue CRUD.
Phase 5 complete
Default chart seeded on company creation. Account types validate correctly. No duplicate account codes per company.
2 days
6.2 — Journal Entry Engine
JournalEntryService: auto-post double-entry from: invoice posting (Dr AR, Cr Revenue), payment allocation (Dr Cash, Cr AR), refund (Dr AR, Cr Cash), void (reversal entries). Each entry: debit_account_id, credit_account_id, amount, description, source_type, source_id. Always balanced (debit = credit).
6.1 chart of accounts
Every financial event in Phases 1–3 produces a balanced journal entry. Debit total always equals credit total. Tested with known financial scenarios.
5 days
6.3 — General Ledger
Per-account transaction history with running balance. Period filtering. Export to CSV. GL summary: opening balance + transactions + closing balance per period. API + GeneralLedger.vue with account selector and date range.
6.2 journal entries
Running balance matches sum of entries. Export produces correct CSV.
3 days
6.4 — Trial Balance
TrialBalanceService: debit and credit totals per account for a period. Must balance (total debits = total credits). Period-close validation flag. TrialBalance.vue with period selector and balance check indicator.
6.3 general ledger
Trial balance balances for any complete period. Unbalanced periods produce clear error indicator.
2 days
6.5 — Income Statement
Revenue vs. expense summary for a period. Revenue breakdown: rent, utility, sales. Net income calculation. Per-building and consolidated views. Export to PDF.
6.4 trial balance
Revenue figures match invoice totals for period. Net income mathematically correct.
3 days
6.6 — Balance Sheet
Assets (AR, cash, deposits), Liabilities (deferred revenue, security deposits payable), Equity. Point-in-time snapshot. Must balance (Assets = Liabilities + Equity). Export to PDF.
6.5 income statement
Balance sheet balances. AR matches outstanding invoice total.
3 days
6.7 — Audit Log Dashboard
AuditLog viewer: all financial record create/update/void actions with user, timestamp, before/after values. Filter by entity type, user, date range. Non-deletable records. AuditLog.vue.
6.1–6.6 complete
Every financial mutation has an audit entry. No gaps in audit trail. Filter combinations work.
2 days

Phase 6 Exit Criteria: Every financial event auto-posts to double-entry journal. Trial balance balances. Income statement and balance sheet exportable. Audit trail complete and non-deletable.


07 — Non-Negotiable Standards (All Phases)
These rules apply to every task in every phase. They are not guidelines — they are architectural constraints. Any pull request that violates them must be rejected.

Backend — Every Task Must

Rule
Implementation Requirement
Thin Controllers
Controllers only: validate via FormRequest, call Service, return Resource. Zero business logic.
DB Transactions
Any Service writing to more than one table must use DB::transaction(). No partial-write states.
BCMath Only
All monetary arithmetic uses bcadd/bcmul/bcdiv/bccomp. Float/double arithmetic is a critical defect.
Decimal(14,4) Columns
Every monetary database column must be decimal(14,4). No float, no double.
SoftDeletes on Financial
Charges, Invoices, Payments, SaleAgreements: SoftDeletes mandatory. Hard delete is prohibited.
State Machine Enums
Status fields use StatusEnum classes. Invalid transitions throw BusinessRuleException (not 200 + error message).
Idempotency
Charge generation and invoice creation must be idempotent. Duplicate calls produce no duplicate records.
API Resource Wrapping
Every response wrapped in API Resource. Resources include a controls object for UI conditional rendering.
Multi-Tenancy Scope
All models carrying company-owned data must use CompanyScope global scope. Verified by automated test.

Frontend — Every Task Must

Pattern
Implementation Requirement
Controls Object
Button visibility (Approve, Post, Edit, Delete) must come from API controls object — never hardcoded status strings.
Loading States
Every async fetch shows loading indicator. Tables show skeleton rows. Buttons disable during processing.
Empty States
Every list/table has designed empty state with contextual message and primary action CTA.
Error Handling
422 errors mapped to field-level messages. 500 errors show dismissible banner. Network failures offer retry.
Confirmation Modals
All destructive or irreversible actions use professional modal — never window.confirm(). Modal states exact consequence.
Currency Formatting
All monetary values use Intl.NumberFormat with operating_currency from building/company context. Never raw decimals.
Composition API Only
All components use <script setup>. Options API is prohibited. State: reactive() for objects, ref() for primitives.
Composables for API
API calls isolated in composables (useChargeModels, useInvoices, etc.) — not inline fetch calls in templates.

Testing Requirements

Module Type
Coverage Target
Required Test Types
Financial Services (billing, payments, invoicing)
95%+
Unit tests for all pricing strategies, edge cases (zero consumption, negative, boundary values), BCMath precision.
State Machines (invoice, charge, agreement)
100%
All valid transitions, all invalid transition rejections, concurrent transition handling.
API Endpoints
90%+
Success paths, validation failures, unauthorised access, cross-tenant isolation, 404 handling.
Queue Jobs / Webhooks
90%+
Idempotency, failure retry, duplicate payload handling.
Multi-Tenancy Isolation
100%
Per-model test: authenticated company A cannot see company B data through any endpoint.


08 — Phase Summary & Timeline

Phase
Goal
Key Deliverable
Blocks Next Phase?
Est. (2 devs)
0
Foundation Hardening
Tested, stable existing codebase
YES — nothing starts without this
1 week
1
Charge Engine
ChargeModels, billing engine, charge UI
YES — Phases 2–6 all blocked
2.5 weeks
2
Invoice Engine
Invoice generation, PDF, email, UI
YES — Payment Engine blocked
3 weeks
3
Payment Engine
Payment recording, FIFO allocation, EVC Plus
YES — Collections & Sales blocked
2.5 weeks
4
Receivables & Collections
Aging report, delinquency, reminders
Partial — Sales can run parallel
1.5 weeks
5
Apartment Sales Domain
Full sales + instalment pipeline
YES — Accounting needs all financial data
7 weeks
6
General Ledger / Accounting
Double-entry journal, financial statements
No — final phase
4 weeks
TOTAL
Full Platform — Financial ERP Grade
All domains operational
—
~21.5 weeks

With 4 developers (2 backend + 2 frontend running in parallel within each phase), total calendar time reduces to approximately 13–14 weeks. Phases 4 and 5 can run in parallel after Phase 3 is complete.

AMSAAS / AfricaERP — Phased Development Master Plan  |  Internal Confidential  |  Engineering Team Only