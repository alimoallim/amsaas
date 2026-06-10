# Phase 5 — Apartment Sales Progress

**Status: COMPLETE** — 5A through 5E done

**Gate:** Phase 3 payment MVP + Phase 4 collections · **Goal:** Full property sales lifecycle (inventory → contract → payment plan → ownership)

| Stream | Status | Notes |
|--------|--------|-------|
| **5A.1** Unit availability state | Done | `lock_version`, `apartment_inventory_status_logs`, sale guards + optimistic transitions |
| **5A.2** Property inventory module | Done | `GET /inventory/available`, history endpoint, `InventoryIndex.vue` |
| **5A.3** Buyer profile | Done | Buyer CRUD API + `BuyerIndex.vue`, optional `tenant_id` link, `buyer_id` on agreements |
| **5B.1** Reservation engine | Done | `ReservationService`, deposit via `PaymentService::recordBuyerPayment`, expiry command |
| **5B.2** Sales contract | Done | `SaleAgreementService`, execute → `under_contract`, price lock |
| **5B.3** Cash sale workflow | Done | `SaleAgreementPostingService`, allocations, completion certificate PDF |
| **5C** *(retired)* Fixed monthly instalments | Superseded | Replaced by 5E agreement-based payment plan |
| **5D** Ownership transfer + docs | Done | Approval chain, `OwnershipTransferService`, legal PDFs, title deed, UI |
| **5E** Agreement-based payment plan | Done | Flexible payments, running balance, progress %, plan term dates |

## Phase 5 exit criteria

- Full sales pipeline operational (cash and payment plan)
- Payment plans collect flexibly against running balance (no fixed monthly invoices)
- Ownership transfer requires legal / finance / manager approval before `ownership_transferred`
- Completion, ownership transfer, sales contract, and payment plan statement PDFs downloadable
- Per-unit ownership history on apartment detail

## Payment plan model (5E)

- `financed_amount` = sale price − down payment
- Agreement `start_date` / `end_date` from duration (years + months) or explicit end date
- Unified `POST .../record-payment` for cash and payment plan contracts
- `progress_percent` = paid / sale price × 100
- Retired: `installment_schedules` generation, `sales:post-installment-invoices`, per-line instalment payments

## API

```bash
GET    /api/v1/sale-agreements
POST   /api/v1/sale-agreements          # is_payment_plan, plan_duration_years/months, agreement_end_date
POST   /api/v1/sale-agreements/{id}/record-payment
POST   /api/v1/sale-agreements/{id}/approve-ownership
POST   /api/v1/sale-agreements/{id}/issue-title-deed
GET    /api/v1/sale-agreements/{id}/completion-certificate
GET    /api/v1/sale-agreements/{id}/sales-contract
GET    /api/v1/sale-agreements/{id}/installment-schedule   # payment plan statement PDF
GET    /api/v1/apartments/{apartment}/ownership-history
php artisan sales:expire-reservations --as-of=
```

Legacy endpoints (`record-installment-payment`, `generate-schedule`) remain but delegate or return retired messaging.

## Migrations

- `add_payment_plan_fields_to_sale_agreements` — `financed_amount`, `plan_duration_years`, `plan_duration_months`
- `create_ownership_transfer_tables` — ownership approvals + history

## Tests

```bash
docker compose exec -T laravel-engine php artisan migrate --force
docker compose exec -T laravel-engine php artisan test --filter=Sales
```
