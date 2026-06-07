# AMSAAS UI Design Model — SAP Fiori Interaction Patterns

**Primary interaction model:** [SAP Fiori](https://experience.sap.com/fiori-design-web/) floorplans (behavior only — not UI5/SAP branding).

**Stack:** Vue 3 + Vite + Tailwind 4 + `frontend/src/components/erp/`. PrimeVue may be added later for advanced grid features; patterns below are framework-agnostic.

**Implementation guide:** [`UI_DESIGN_SYSTEM.md`](./UI_DESIGN_SYSTEM.md) · [`ERP_UI_STRATEGY.md`](./ERP_UI_STRATEGY.md)

---

## Why Fiori for property ERP

Fiori encodes 20+ years of enterprise list/detail/approval UX. We adopt **how screens behave**, not the SAP UI5 toolkit.

| Fiori pattern | AMSAAS use | Layout component |
|---------------|------------|------------------|
| **Worklist** | Index pages requiring action | `WorklistLayout` |
| **Object Page** | Show/detail for every entity | `ObjectPageLayout` |
| **Flexible Column Layout (FCL)** | Approval queues | `MasterDetailLayout` |
| **Smart Filter Bar** | All index pages | `SmartFilterBar` + `useSmartFilters` |
| **Transactional** | Create/edit with draft lifecycle | `TransactionalFormLayout` |

---

## 1. Worklist → Index pages

Fiori **Worklist** is not a plain table. It optimizes **actionable queues**.

### Behavior rules

| Rule | Requirement |
|------|-------------|
| Filter bar | Always **above** the table (never sidebar-only) |
| Title | Entity name + **count**: `Invoices (24)` |
| Sort | Most-actionable first (overdue, pending approval) where API supports it |
| Row click | **Does not navigate** on worklist variants — opens **preview panel** (master-detail) |
| Row click (standard) | Navigates to Object Page when `mode="navigate"` |
| Selection | Checkbox column; **primary toolbar action** enabled only when row(s) selected |
| Pagination | Bottom of list; preserve filters in URL |

### Wireframe

```
┌─────────────────────────────────────────────────────┐
│  Invoices (24)                      [+ Create New]  │
│  ─────────────────────────────────────────────────  │
│  Status: [All ▾]  Period: [Jun 2025 ▾]  [Search…]  │
│  [Status: Posted ×]  [Period: Jun 2025 ×]  Clear  │  ← filter chips
│  ─────────────────────────────────────────────────  │
│  ☐  AGR-001  Tenant A   $1,250.00   POSTED   Jun 1  │ ← selected
│  ☐  AGR-002  Tenant B   $  800.00   OVERDUE  May 1  │
│  ─────────────────────────────────────────────────  │
│  Showing 1–20 of 24               [< 1 2 >]         │
└─────────────────────────────────────────────────────┘
```

### AMSAAS modules

| Module | Route | Worklist mode |
|--------|-------|---------------|
| Invoices | `/invoices` | `navigate` or `split` for batch review |
| Charge types / models | `/charge-types`, `/charge-models` | `navigate` |
| Meter readings (approval) | `/meter-readings` | **`split`** (FCL) |
| Buildings, Apartments, Tenants, Agreements | `*/index` | `navigate` |
| Billing charges (future) | — | `split` for approval |

### Vue usage

```vue
<WorklistLayout
  title="Invoices"
  :count="meta.total"
  mode="navigate"
  @primary="onCreate"
>
  <template #filters>
    <SmartFilterBar :filters="filterDefs" v-model="filters" />
  </template>
  <template #table>
    <DataTable selectable ... @row-click="openInvoice" />
  </template>
</WorklistLayout>
```

---

## 2. Object Page → Show / detail pages

Every **Show** screen uses the same skeleton. Developers fill sections; they do not redesign the chrome.

### Anatomy

```
┌─────────────────────────────────────────────────────┐
│ ← Buildings › Block A                               │  Breadcrumb
│  Block A                          [Edit] [More ▾]   │  ObjectPageHeader
│  Commercial · 5 Floors · Mogadishu                │  Key attributes
│  ● Active                                         │  StatusBadge
│  ─────────────────────────────────────────────────  │
│  [Overview] [Apartments] [Meters] [Documents]       │  Anchor tabs
│  ─────────────────────────────────────────────────  │
│  GENERAL INFORMATION          FINANCIAL DETAILS   │  FormSection (2 col)
│  APARTMENTS ─────────────────────────────────────   │  Section + embedded table
└─────────────────────────────────────────────────────┘
```

### Module → tabs mapping

| Show page | Header attributes | Tabs |
|-----------|-------------------|------|
| **BuildingShow** | Type, city, floors | Overview, Apartments, Meters, Documents |
| **TenantShow** | Type, phone, status | Profile, Agreements, Invoices, Notes |
| **AgreementShow** | Apartment, dates, rent | Overview, Charges, Invoices, Documents |
| **InvoiceShow** | Tenant, period, amount | Line items, Payments, History |
| **MeterShow** | Type, unit, building | Overview, Readings, Charges |
| **ChargeModelShow** | Utility, status | Overview, Rules / tiers |

### Vue usage

```vue
<ObjectPageLayout
  :breadcrumbs="[{ label: 'Buildings', to: '/buildings' }, { label: building.name }]"
  :title="building.name"
  :status="building.status"
  :attributes="['Commercial', '5 floors', building.city]"
>
  <template #actions>
    <ErpButton v-if="controls.can_update" :to="editRoute">Edit</ErpButton>
  </template>
  <template #tabs>
    <ObjectPageTab id="overview" label="Overview" />
    ...
  </template>
  <template #overview>...</template>
</ObjectPageLayout>
```

---

## 3. Master-Detail split (FCL) → Approval workflows

**Flexible Column Layout:** process many items without leaving the list.

### When to use

- Meter readings pending approval
- Charges pending approval (future)
- Reservation review (future)

### Behavior rules

| Rule | Requirement |
|------|-------------|
| Left column | Scrollable worklist, filters, status badges |
| Right column | Detail + actions for selected row |
| Navigation | **No full page route** on row click |
| After action | Approve/Reject **advances to next** pending item |
| Optimistic UI | Left list updates badge immediately |
| Keyboard | `j` / `k` (or ↑/↓) move selection; `Enter` focus detail actions |

### Wireframe

```
┌───────────────────┬─────────────────────────────────┐
│  Pending Readings │  Reading #MR-2025-00142         │
│  ► MR-142  [flag] │  Consumption: 65 m³             │
│    MR-141         │  ⚠ Anomaly flagged              │
│                   │  [Reject]        [Approve]      │
└───────────────────┴─────────────────────────────────┘
```

### Vue usage

```vue
<MasterDetailLayout>
  <template #list>...</template>
  <template #detail>...</template>
</MasterDetailLayout>
```

Composable: `useMasterDetailQueue` — `selectNext`, `advanceAfterAction`.

---

## 4. Smart Filter Bar → All index pages

Replaces ad-hoc filters with one pattern.

### Behavior rules

| Rule | Requirement |
|------|-------------|
| Apply | **Immediate** on change — no "Apply" button |
| Chips | Active filters shown as **dismissible chips** under the bar |
| Adapt filters | (Phase 2) Dialog to show/hide filter fields |
| Persistence | State in **URL query params** — shareable, bookmarkable |
| Clear | "Clear all" resets to module defaults |

### Filter fields per module

| Module | Default filters |
|--------|-----------------|
| **Buildings** | Search, status, city |
| **Apartments** | Building, status, floor |
| **Tenants** | Search, status |
| **Rental agreements** | Building, status (draft/active/ended), date range |
| **Charge types** | Search, category |
| **Charge models** | Utility type, status |
| **Invoices** | Status, period (month/year), tenant search |
| **Meter readings** | Building, status (pending/approved), period |

### Vue usage

```js
const { filters, chips, clearAll, bindRoute } = useSmartFilters({
  defaults: { status: '', search: '' },
  schema: { status: 'string', search: 'string' },
})
bindRoute(router, route)
```

```vue
<SmartFilterBar :chips="chips" @clear-all="clearAll">
  <FormField label="Status">...</FormField>
</SmartFilterBar>
```

---

## 5. Transactional screen → Create / edit

Governed form flow with explicit **draft vs active** states.

### Draft activation model

| State | Meaning |
|-------|---------|
| **Draft** | Editing; no business impact; autosave (30s) when API supports; URL contains draft id |
| **Active** | User submits; full validation; record becomes real |

### Footer rules (sticky action bar)

| Action | Behavior |
|--------|----------|
| **Cancel** | Return to list; confirm only if dirty |
| **Save draft** | Persist without full business validation |
| **Primary** | Full validation + state transition (`Generate Invoice`, `Post`, `Submit`) |

### Wireframe

```
┌──────────────────────────────────────────────────────┐
│ ← Invoices › New Invoice                   [DRAFT]  │
│  TENANT & PERIOD                                     │
│  CHARGES TO INCLUDE                                  │
│  [Cancel]  [Save Draft]          [Generate Invoice →]│
└──────────────────────────────────────────────────────┘
```

### Vue usage

```vue
<TransactionalFormLayout
  state="draft"
  :dirty="dirty"
  primary-label="Generate Invoice"
  @cancel="onCancel"
  @save-draft="onSaveDraft"
  @primary="onSubmit"
>
  ...
</TransactionalFormLayout>
```

---

## Gap analysis (current pilots vs model)

| Pattern | Status | Gap |
|---------|--------|-----|
| Worklist | Partial | Title count, row selection, split mode |
| Object Page | Missing | `ObjectPageLayout`, tabs, breadcrumbs |
| FCL | Missing | `MasterDetailLayout`, keyboard nav |
| Smart Filter | Partial | No URL sync, no chips |
| Transactional | Partial | `FormPageLayout` lacks draft/save-draft split |

**Wave 0 deliverable:** components above in `components/erp/`.

---

## References

- Fiori floorplans: https://experience.sap.com/fiori-design-web/floorplans/
- Worklist: https://experience.sap.com/fiori-design-web/explore/worklist/
- Object page: https://experience.sap.com/fiori-design-web/explore/object-page/
- Flexible column layout: https://experience.sap.com/fiori-design-web/floorplans/flexible-column-layout/
