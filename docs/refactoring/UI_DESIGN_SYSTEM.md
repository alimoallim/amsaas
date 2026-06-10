# AMSAAS UI Design System (ERP / SaaS)

**Interaction model (canonical):** [`UI_DESIGN_MODEL.md`](./UI_DESIGN_MODEL.md) — five SAP Fiori patterns for AMSAAS.

**Rollout & industry context:** [`ERP_UI_STRATEGY.md`](./ERP_UI_STRATEGY.md).

Built on **Vue 3**, **Tailwind 4**, and `frontend/src/components/erp/`.

## Fiori patterns → components

| Fiori pattern | Component(s) | Composable |
|---------------|--------------|------------|
| **Worklist** | `WorklistLayout`, `DataTable` (selectable) | — |
| **Object Page** | `ObjectPageLayout`, `ObjectPageHeader`, `ObjectPageTab` | — |
| **Master-Detail (FCL)** | `MasterDetailLayout` | `useMasterDetailQueue` |
| **Smart Filter Bar** | `SmartFilterBar`, `FilterBar` | `useSmartFilters` |
| **Transactional** | `TransactionalFormLayout`, `FormPageLayout` | — |
| **Popup forms (worklist)** | `FormModal`, `*FormModal.vue` in `components/forms/` | `useFormModal` |
| **Shared** | `PageHeader`, `ErpPanel`, `StatusBadge`, `Breadcrumbs`, … | `useConfirm` |

## Principles

1. **Fiori behavior first** — follow [`UI_DESIGN_MODEL.md`](./UI_DESIGN_MODEL.md); do not invent per-page list/detail layouts.
2. **Clarity over decoration** — plain labels; no per-page font imports.
3. **Controls from API** — actions only when `controls.can_*` allows.
4. **Modals for consequences** — `ConfirmModal` / `ErpModal`, never `window.confirm`.
5. **Loading & empty states** — skeletons, empty CTAs, disabled primaries when nothing to do.
6. **Create/edit in modals** — index pages open `FormModal` via `useFormModal`; legacy `/create` and `/edit` routes redirect with `FormRouteRedirect` (`?form=create|edit&id=`).

## Component library

| Component | Use for |
|-----------|---------|
| `WorklistLayout` | Index pages: title + count, filters slot, table, optional selection toolbar |
| `ObjectPageLayout` | Show pages: breadcrumb, header, tabs, sections |
| `MasterDetailLayout` | Approval queues (readings, charges) |
| `SmartFilterBar` | Filters + active chips + clear all |
| `TransactionalFormLayout` | Create/edit with draft badge + sticky footer |
| `PageHeader` | Title; use `:count` for worklist titles |
| `Breadcrumbs` | Object page / form trail |
| `DataTable` | Lists; `selectable`, `@row-click` |
| `FilterBar` | Simple filter grid (prefer `SmartFilterBar` on index pages) |
| `FormField` / `FormSection` | Forms and object page sections |
| `FormPageLayout` | Simple forms without draft workflow |
| `FormModal` | Popup create/edit on worklists; pairs with `useFormModal` |
| `FormGrid` | Two-column field grid inside forms/modals (`FormField` + `span`) |
| `ErpDateInput` | Calendar date picker (ISO `YYYY-MM-DD`); use instead of `type="date"` |
| `ErpButton` | primary / secondary / danger / ghost |
| `StatusBadge` | draft, active, posted, overdue, … |
| `ErpModal` / `ConfirmModal` | Confirms and multi-step flows |
| `KpiCard` / `AlertBanner` | Dashboard and inline alerts |

```js
import {
  WorklistLayout,
  SmartFilterBar,
  DataTable,
  ObjectPageLayout,
  TransactionalFormLayout,
} from '@/components/erp'
```

## Page templates

### Worklist (index)

```vue
<WorklistLayout title="Invoices" :count="meta?.total" :primary-disabled="!selectedId">
  <template #actions>
    <ErpButton :to="{ name: 'InvoiceCreate' }">Create</ErpButton>
  </template>
  <template #filters>
    <SmartFilterBar :chips="chips" @clear-all="clearAll">...</SmartFilterBar>
  </template>
  <template #table>
    <DataTable selectable v-model:selected="selected" @row-click="onRowClick" ... />
  </template>
</WorklistLayout>
```

### Object page (show)

```vue
<ObjectPageLayout :breadcrumbs="crumbs" :title="record.name" :status="record.status">
  <template #actions>...</template>
  <!-- tab panels via default slot + ObjectPageTab -->
</ObjectPageLayout>
```

### Transactional (create/edit)

```vue
<TransactionalFormLayout
  :breadcrumbs="crumbs"
  title="New invoice"
  state="draft"
  :dirty="dirty"
  primary-label="Generate invoice"
  @save-draft="saveDraft"
  @primary="submit"
/>
```

## Worklist data tables

### Show backend fields

Index tables should surface the most useful fields from API resources (not only name + status). Prefer nested paths (`row.contact.email`, `row.layout.bedrooms`) when the resource is grouped. Use column slots for compound cells (name + city subline, units as `registered / capacity`).

### Column options (`DataTable`)

| Option | Purpose |
|--------|---------|
| `emphasis` | Primary column styling |
| `mono` | Codes, references |
| `align: 'right'` | Numbers, money |
| `truncate` | Long text with ellipsis |
| `wrap` | Multi-line cells (default is nowrap) |
| `type: 'actions'` | Narrow sticky actions column |

### Row actions menu

When a row has more than two actions (view, edit, approve, delete, etc.), use **`RowActionsMenu`** instead of a button row:

```vue
<template #cell-actions="{ row }">
  <RowActionsMenu :actions="buildingActions(row)" />
</template>
```

Build actions with `compactActions()` from `@/composables/useTableActions` (omit falsy entries). Each action: `{ key, label, to?, onClick?, variant?, disabled?, hidden? }`. Variants: `primary`, `success`, `warning`, `danger`.

```js
function buildingActions(row) {
  return compactActions([
    viewAction('BuildingShow', row.id),
    editAction(() => formModal.openEdit(row.id)),
    row.controls?.can_delete && deleteAction(() => onDelete(row)),
  ])
}
```

Row click still navigates to detail; the menu uses `@click.stop` on the trigger.

### Dependent selects (building → unit)

For large unit lists (e.g. rental agreements), **select building first**, then a searchable unit picker scoped to that building:

1. Load buildings (`GET /buildings?per_page=200`).
2. On building change, load apartments with `building_id`, `listing_type=rental`, and optional `search` (debounced).
3. Use **`ErpSearchSelect`** with `remote` + `@search` for server-side filter; on create, pass `inventory_status=available`.

```vue
<select v-model="selectedBuildingId" @change="onBuildingChange">…</select>
<ErpSearchSelect
  v-model="form.apartment_id"
  :options="apartmentOptions"
  remote
  :disabled="!selectedBuildingId"
  @search="onApartmentSearch"
/>
```

Composable: `useBuildingApartments()` for units; `useBuildingPicker()` for searchable buildings (`GET /buildings?search=`).

Use the same pattern on **meter registration** (building + unit pickers).

## Rollout order

| Wave | Scope | Status |
|------|--------|--------|
| **0** | Fiori layouts + `useSmartFilters` + composables | Done |
| **1** | Worklist indexes: Buildings, Apartments, Tenants, Agreements, Meters, Meter readings, Charge types/models, Dashboard, Reports, Settings | Done |
| **2** | Object pages (Show): Building (done), Tenant, Apartment, Agreement, Meter, Invoice | In progress |
| **3** | Create/edit → `TransactionalFormLayout` | Pending |
| **4** | Meter reading FCL (master-detail approval queue) | Done — `/meter-readings/queue` |

## CSS

- `erp-page`, `erp-input`, `erp-select`, `erp-label` — `frontend/src/styles/erp.css`
- Object page tabs: `erp-tabs`, `erp-tab-panel` — same file
