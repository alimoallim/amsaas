# ADR 001: Canonical Invoice Model

**Status:** Accepted (2026-06-04)

**Context:** The codebase had two invoice-related models:

| Model | Scoped | Routes / usage | Line items |
|-------|--------|----------------|------------|
| `MonthlyInvoice` | Yes (`BelongsToCompany`) | `apiResource('invoices', MonthlyInvoiceController)`, payments, PDF | `invoice_line_items.monthly_invoice_id` |
| `Invoice` | Yes (Sprint 0) | `InvoiceConsolidationService` only (drift) | None — wrong FK shape |

`InvoiceConsolidationService` wrote to `Invoice` with columns that do not match `invoice_line_items` (which reference `monthly_invoices`).

## Decision

**Canonical model: `MonthlyInvoice`** (Option A).

- All consolidation, payments, PDF generation, and `/api/v1/invoices` routes use `MonthlyInvoice` + `InvoiceLineItem`.
- `charges.invoice_id` and `billing_items.invoice_id` store **`monthly_invoices.id`** (column name unchanged).
- The `invoices` table and `Invoice` model are **deprecated** until a later migration merges or drops them (no active writes in Sprint 1).

## Consequences

- [x] `InvoiceConsolidationService` refactored to `MonthlyInvoice`
- [x] Duplicate `InvoiceController` routes merged into `MonthlyInvoiceController`
- [ ] Future: migrate historical `invoices` rows → `monthly_invoices` or drop empty table
- [x] Update [DEBT_REGISTER.md](../DEBT_REGISTER.md) H1/H2
- [x] Update [API_CONTRACT.md](../API_CONTRACT.md) invoice section

## Decision record

| Field | Value |
|-------|-------|
| Chosen option | **A — `MonthlyInvoice`** |
| Decided by | Refactoring sprint (G2) |
| Date | 2026-06-04 |
