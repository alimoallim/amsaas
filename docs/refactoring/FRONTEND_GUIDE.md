# Frontend Refactoring Guide (Vue 3)

Align UI with [STANDARDS.md](./STANDARDS.md) and [API_CONTRACT.md](./API_CONTRACT.md). Stack: Vue 3, Composition API, Axios via `services/api.js`.

---

## 1. Target structure

```
frontend/src/
  composables/
    useChargeTypes.js
    useChargeModels.js
    useMeters.js
    useBilling.js
    useConfirm.js
  components/
    common/
      ConfirmModal.vue
      EmptyState.vue
      TableSkeleton.vue
  services/
    api.js          # single axios instance
  Pages/
    ...             # thin: composable + template
```

Migrate **incrementally** — pilot pages first (Charge Types, Meters index).

---

## 2. API client rules

### Use shared instance only

```js
import api from '@/services/api.js'

const { data } = await api.get('/charge-types')
await api.post('/billing/generate', { period: '2026-06' })
```

### Fix `api.js` (Sprint 0)

- [ ] Remove second `interceptors.request` block (lines ~114–136)
- [ ] Remove `console.log('TOKEN:', ...)` and auth header logging
- [ ] Keep 401 redirect behavior once

### Forbidden

```js
import axios from 'axios'
axios.post('/api/billing/generate', ...)  // no Bearer, wrong base path
```

**Known violations:** `InvoicesIndex.vue`, `BulkInvoiceManager.vue`, `BatchInvoiceDashboard.vue`.

---

## 3. Composable pattern

`composables/useChargeTypes.js`:

```js
import { ref } from 'vue'
import api from '@/services/api.js'

export function useChargeTypes() {
  const items = ref([])
  const loading = ref(false)
  const error = ref(null)

  async function fetchList(params = {}) {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get('/charge-types', { params })
      items.value = data.data ?? data
    } catch (e) {
      error.value = e
      throw e
    } finally {
      loading.value = false
    }
  }

  return { items, loading, error, fetchList }
}
```

Page:

```vue
<script setup>
import { onMounted } from 'vue'
import { useChargeTypes } from '@/composables/useChargeTypes'

const { items, loading, error, fetchList } = useChargeTypes()
onMounted(() => fetchList())
</script>
```

---

## 4. Controls-driven UI

**Good** (`MeterDetail.vue` pattern — prefer this):

```vue
<button v-if="meter.controls?.can_approve" @click="approve">
  Approve
</button>
```

**Bad** (replace during refactor):

```vue
<button v-if="meter.status === 'DRAFT'">Approve</button>
```

Audit pages:

| Page | Action |
|------|--------|
| `MeterShow.vue` | Use `controls` from API |
| `RentalAgreementIndex.vue` | Use `controls` |
| `ChargeModelShow.vue` | After backend adds `controls` |

---

## 5. Error & loading UX

| Status | UI behavior |
|--------|-------------|
| Loading | `TableSkeleton` or spinner; disable submit buttons |
| Empty list | `EmptyState` with CTA (e.g. "Create charge type") |
| 422 | Map `errors` to form fields |
| 500 | Dismissible error banner |
| Network | Retry button |

Composable helper:

```js
export function mapValidationErrors(err) {
  return err.response?.status === 422
    ? err.response.data.errors
    : null
}
```

---

## 6. Confirmation modal

Replace all `window.confirm`:

```js
// composables/useConfirm.js
import { ref } from 'vue'

const state = ref({ open: false, title: '', message: '', onConfirm: null })

export function useConfirm() {
  function confirm({ title, message, onConfirm }) {
    state.value = { open: true, title, message, onConfirm }
  }
  return { state, confirm }
}
```

Pages to update: Meters, Apartments, Buildings, Rental agreements.

---

## 7. Currency display

```js
export function formatMoney(amount, currency = 'TZS') {
  return new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency,
    minimumFractionDigits: 2,
  }).format(Number(amount))
}
```

Source `operating_currency` from company/building context when API provides it.

---

## 8. Router & navigation (Sprint 0)

### Charge Types — add routes

```js
{
  path: '/charge-types',
  name: 'charge-types.index',
  component: () => import('@/Pages/ChargeTypes/ChargeTypeIndex.vue'),
},
// create, edit similarly
```

### Sidebar

Add entry in `DashboardLayout.vue` near Charge Models.

### Field alignment

| UI field (wrong) | API field (correct) |
|------------------|---------------------|
| `is_active` | `status` |

---

## 9. Auth layout

`DashboardLayout.vue`:

- [ ] Logout calls `authStore.logout()` (clear token, redirect `/login`)
- [ ] Display name/email from store — remove hardcoded placeholders
- [ ] `routeTitles` map kept in sync with router names

---

## 10. Pages to quarantine until backend ready

Do not link in nav until API exists and auth is correct:

- `BulkInvoiceManager.vue`
- `BatchInvoiceDashboard.vue`
- Payments placeholder (Phase 3)

Optional: move to `Pages/_draft/` or guard routes with feature flag.

---

## 11. Pilot migration order

1. `ChargeTypeIndex.vue` — composable + `status` + routes
2. `ChargeModelIndex.vue` — composable + controls when API ready
3. `MeterReadingIndex.vue` — error mapping for 422
4. `InvoicesIndex.vue` — fix generate endpoint + composable

---

## 12. Build verification

```bash
cd frontend
npm ci
npm run build
# optional: npx vue-tsc --noEmit if TS added later
```

If npm registry/proxy fails, document environment fix — do not skip build before Phase 1 UI sign-off.
