<template>
  <WorklistLayout
    eyebrow="Finance · Monthly close"
    title="Monthly invoices"
    :count="meta.total"
    description="Exception-first worklist: review drafts, issue in bulk, then collect payments."
  >
    <template #actions>
      <ErpButton variant="ghost" size="sm" :to="{ name: 'InvoiceCreate' }">Create invoice</ErpButton>
      <ErpButton variant="ghost" size="sm" :to="{ name: 'Invoices' }">Billing close</ErpButton>
      <ErpButton variant="ghost" size="sm" :to="{ name: 'ChargeApproval' }">Approve charges</ErpButton>
      <ErpButton variant="ghost" size="sm" :loading="loading" @click="refresh">Refresh</ErpButton>
      <ErpButton
        variant="secondary"
        :disabled="!selectedIds.length || issuing"
        :loading="issuing"
        @click="onBulkIssueSelected"
      >
        Issue selected ({{ selectedIds.length }})
      </ErpButton>
      <ErpButton
        :disabled="!periodSummary.can_bulk_issue || issuing"
        :loading="issuing"
        @click="onBulkIssueAll"
      >
        Issue all drafts ({{ periodSummary.counts.draft }})
      </ErpButton>
    </template>

    <template #kpis>
      <div
        v-if="periodSummary.counts.total > 0"
        class="mb-4 flex flex-wrap items-center gap-2 rounded-lg border border-slate-200 bg-slate-50/80 px-4 py-3 text-sm"
      >
        <span class="font-medium text-slate-700">Close loop:</span>
        <span :class="workflowStepClass(1)">① Review drafts</span>
        <span class="text-slate-300">→</span>
        <span :class="workflowStepClass(2)">② Issue invoices</span>
        <span class="text-slate-300">→</span>
        <span :class="workflowStepClass(3)">③ Record payments</span>
        <ErpButton
          v-if="periodSummary.can_bulk_issue"
          size="sm"
          class="ml-auto"
          :loading="issuing"
          @click="onBulkIssueAll"
        >
          Issue all ({{ periodSummary.counts.draft }})
        </ErpButton>
        <ErpButton
          v-else-if="readyForPayments"
          size="sm"
          variant="primary"
          class="ml-auto"
          :to="paymentsLink"
        >
          Record payments
        </ErpButton>
      </div>

      <AlertBanner
        v-if="readiness.active_rental_agreements > 0 && periodSummary.counts.draft === 0 && periodSummary.counts.total === 0"
        variant="info"
        class="mb-4"
        :dismissible="false"
      >
        <span>
          {{ readiness.active_rental_agreements }} active lease(s) found, but no invoices for
          {{ months[filters.month - 1] }} {{ filters.year }} yet.
        </span>
        <router-link
          :to="{ name: 'Invoices', query: { year: filters.year, month: filters.month } }"
          class="ml-2 font-semibold text-blue-900 underline"
        >
          Run billing close
        </router-link>
      </AlertBanner>

      <KpiStrip class="grid-cols-2 lg:grid-cols-5">
        <KpiCard
          label="Drafts (needs issue)"
          :value="periodSummary.counts.draft"
          variant="accent"
          caption="Attention view default"
        />
        <KpiCard label="Issued" :value="periodSummary.counts.issued" />
        <KpiCard label="Partially paid" :value="periodSummary.counts.partially_paid" />
        <KpiCard label="Paid" :value="periodSummary.counts.paid" />
        <KpiCard
          label="Open AR"
          :value="formatMoney(periodSummary.amounts.open_balance)"
          :caption="openArCaption"
        />
      </KpiStrip>
    </template>

    <template #filters>
      <div class="mb-3 flex flex-wrap items-center gap-2">
        <select v-model="filters.year" class="erp-select w-auto" @change="syncAndFetch">
          <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
        </select>
        <select v-model="filters.month" class="erp-select w-auto" @change="syncAndFetch">
          <option v-for="(name, index) in months" :key="index" :value="index + 1">{{ name }}</option>
        </select>
        <div class="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-0.5">
          <button
            type="button"
            class="rounded-md px-3 py-1.5 text-xs font-medium transition"
            :class="filters.view === 'attention' ? 'bg-white text-indigo-700 shadow-sm' : 'text-slate-600'"
            @click="setView('attention')"
          >
            Needs attention
          </button>
          <button
            type="button"
            class="rounded-md px-3 py-1.5 text-xs font-medium transition"
            :class="filters.view === 'all' ? 'bg-white text-indigo-700 shadow-sm' : 'text-slate-600'"
            @click="setView('all')"
          >
            All statuses
          </button>
        </div>
      </div>

      <SmartFilterBar :chips="chips" @clear-all="onClearAll" @remove-chip="removeChip">
        <FormField label="Search" span="2">
          <input
            v-model="smartFilters.search"
            type="search"
            class="erp-input"
            placeholder="Invoice #, unit, tenant, building…"
            @input="debounceFetch"
          />
        </FormField>
        <FormField label="Status" v-if="filters.view === 'all'">
          <select v-model="smartFilters.status" class="erp-select" @change="syncAndFetch">
            <option value="">All</option>
            <option value="draft">Draft</option>
            <option value="issued">Issued</option>
            <option value="partially_paid">Partially paid</option>
            <option value="paid">Paid</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </FormField>
        <FormField label="Building">
          <ErpSearchSelect
            v-model="smartFilters.building_id"
            :options="buildingOptions"
            :loading="buildingsLoading"
            remote
            clearable
            placeholder="All buildings"
            @search="onBuildingSearch"
            @update:model-value="syncAndFetch"
          />
        </FormField>
      </SmartFilterBar>
    </template>

    <template #table>
      <AlertBanner
        v-if="pageMessage"
        variant="success"
        class="mb-4"
        :message="pageMessage"
        @dismiss="pageMessage = ''"
      />
      <AlertBanner
        v-if="pageError"
        variant="error"
        class="mb-4"
        :message="pageError"
        @dismiss="pageError = ''"
      />

      <DataTable
        selectable
        multi-select
        v-model:selected-ids="selectedIds"
        :columns="columns"
        :rows="items"
        :loading="loading"
        :meta="meta"
        :empty-title="emptyTitle"
        :empty-description="emptyDescription"
        @page-change="fetchList"
        @row-click="(row) => $router.push({ name: 'InvoiceShow', params: { id: row.id } })"
      >
        <template #emptyAction>
          <ErpButton :to="{ name: 'Invoices' }">Run billing close</ErpButton>
        </template>
        <template #cell-invoice_number="{ row }">
          <code class="text-xs font-mono">{{ row.invoice_number }}</code>
        </template>
        <template #cell-unit="{ row }">
          <div>
            <p class="text-sm font-medium text-slate-900">
              {{ row.apartment?.unit_number || '—' }}
            </p>
            <p class="text-xs text-slate-500">{{ row.building?.name || '' }}</p>
          </div>
        </template>
        <template #cell-tenant="{ row }">
          <span v-if="tenantLabel(row)" class="text-sm">{{ tenantLabel(row) }}</span>
          <span v-else class="text-sm text-slate-400">No tenant on lease</span>
        </template>
        <template #cell-total="{ row }">
          <span class="font-mono text-sm tabular-nums">{{ formatMoney(row.total_amount) }}</span>
        </template>
        <template #cell-balance="{ row }">
          <span class="font-mono text-sm tabular-nums">{{ formatMoney(row.balance_due) }}</span>
        </template>
        <template #cell-status="{ row }">
          <StatusBadge :status="row.status" :label="row.status" />
        </template>
        <template #cell-actions="{ row }">
          <RowActionsMenu :actions="rowActions(row)" />
        </template>
      </DataTable>
    </template>
  </WorklistLayout>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import api from '@/services/api'
import { useRoute, useRouter } from 'vue-router'
import { useMonthlyInvoices } from '@/composables/useMonthlyInvoices'
import { useSmartFilters } from '@/composables/useSmartFilters'
import { useConfirm } from '@/composables/useConfirm'
import { useBuildingPicker } from '@/composables/useBuildingPicker'
import { compactActions } from '@/composables/useTableActions'
import { tenantDisplayName } from '@/utils/tenantDisplayName'
import {
  WorklistLayout,
  SmartFilterBar,
  DataTable,
  RowActionsMenu,
  FormField,
  ErpButton,
  StatusBadge,
  KpiCard,
  KpiStrip,
  ErpSearchSelect,
  AlertBanner,
} from '@/components/erp'

const months = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December',
]
const currentYear = new Date().getFullYear()
const years = [currentYear - 1, currentYear, currentYear + 1]

const route = useRoute()
const router = useRouter()
const { confirm } = useConfirm()
const selectedIds = ref([])
const acting = ref(null)
const pageError = ref('')
const pageMessage = ref('')

const {
  items,
  loading,
  issuing,
  meta,
  periodSummary,
  filters,
  fetchList,
  issueOne,
  bulkIssue,
} = useMonthlyInvoices()

const readiness = reactive({
  active_rental_agreements: 0,
})

async function fetchReadiness() {
  try {
    const { data } = await api.get('/billing/summary', {
      params: { year: filters.year, month: filters.month },
    })
    readiness.active_rental_agreements = data.metrics?.active_rental_agreements ?? 0
  } catch {
    /* ignore */
  }
}

const { buildings, loading: buildingsLoading, fetchBuildings, buildingToOption } = useBuildingPicker()
const buildingOptions = computed(() => buildings.value.map((b) => buildingToOption(b)))

const { filters: smartFilters, chips, clearAll, removeChip, bindRoute } = useSmartFilters({
  defaults: { search: '', status: '', building_id: '' },
  labels: {
    search: { label: 'Search' },
    status: { label: 'Status' },
    building_id: { label: 'Building', format: (id) => buildings.value.find((b) => b.id === id)?.name || id },
  },
})

watch(smartFilters, () => {
  filters.search = smartFilters.search
  filters.status = smartFilters.status
  filters.building_id = smartFilters.building_id
}, { deep: true })

watch(
  () => [filters.year, filters.month],
  () => {
    router.replace({
      query: {
        ...route.query,
        year: String(filters.year),
        month: String(filters.month),
      },
    })
  }
)

const columns = [
  { key: 'invoice_number', label: 'Invoice #', mono: true },
  { key: 'unit', label: 'Unit / building' },
  { key: 'tenant', label: 'Tenant' },
  { key: 'total', label: 'Total', align: 'right' },
  { key: 'balance', label: 'Balance', align: 'right' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: '', align: 'right', type: 'actions' },
]

const emptyTitle = computed(() =>
  filters.view === 'attention' ? 'No draft invoices' : 'No invoices for this period'
)
const emptyDescription = computed(() =>
  filters.view === 'attention'
    ? 'Compile monthly invoices from Billing close, or switch to All statuses.'
    : 'Change period or filters, or run billing close to create drafts.'
)

const openArCaption = computed(() => {
  const draftBal = Number(periodSummary.amounts?.draft_balance ?? 0)
  if (draftBal > 0) {
    return `Issued only · ${formatMoney(draftBal)} still in drafts`
  }
  return 'Issued invoices — collect via Payments'
})

function tenantLabel(row) {
  return tenantDisplayName(row.tenant)
}

let debounceTimer = null
function debounceFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(syncAndFetch, 350)
}

function setView(view) {
  filters.view = view
  if (view === 'attention') {
    smartFilters.status = ''
    filters.status = ''
  }
  syncAndFetch()
}

function syncAndFetch() {
  fetchList(1)
  fetchReadiness()
}

function onClearAll() {
  clearAll()
  filters.search = ''
  filters.status = ''
  filters.building_id = ''
  syncAndFetch()
}

async function refresh() {
  await Promise.all([fetchList(meta.value.current_page), fetchReadiness()])
}

function rowActions(row) {
  const busy = acting.value === row.id
  return compactActions([
    {
      key: 'view',
      label: 'View invoice',
      to: { name: 'InvoiceShow', params: { id: row.id } },
    },
    row.controls?.can_issue && {
      key: 'issue',
      label: 'Issue',
      variant: 'success',
      disabled: busy,
      onClick: () => onIssueOne(row),
    },
    row.agreement?.id && {
      key: 'agreement',
      label: 'View agreement',
      to: { name: 'RentalAgreementShow', params: { id: row.agreement.id } },
    },
  ])
}

async function onIssueOne(row) {
  const ok = await confirm({
    title: 'Issue invoice',
    message: `Issue ${row.invoice_number} for ${formatMoney(row.total_amount)}?`,
    confirmLabel: 'Issue',
    variant: 'primary',
  })
  if (!ok) return

  acting.value = row.id
  pageError.value = ''
  try {
    await issueOne(row)
    pageMessage.value = `${row.invoice_number} issued. PDF generation queued.`
    selectedIds.value = selectedIds.value.filter((id) => id !== row.id)
    await refresh()
  } catch (e) {
    pageError.value = e.response?.data?.message || 'Could not issue invoice.'
  } finally {
    acting.value = null
  }
}

async function onBulkIssueSelected() {
  if (!selectedIds.value.length) return
  await runBulkIssue(selectedIds.value)
}

async function onBulkIssueAll() {
  const count = periodSummary.counts.draft
  if (!count) return
  const ok = await confirm({
    title: 'Issue all draft invoices',
    message: `Issue ${count} draft invoice(s) for ${months[filters.month - 1]} ${filters.year}? PDFs will be queued.`,
    confirmLabel: `Issue ${count}`,
    variant: 'primary',
  })
  if (!ok) return
  await runBulkIssue(null)
}

const readyForPayments = computed(
  () =>
    !periodSummary.can_bulk_issue
    && (periodSummary.counts.issued > 0 || periodSummary.counts.partially_paid > 0)
    && (periodSummary.amounts.open_balance ?? 0) > 0
)

const paymentsLink = computed(() => ({
  name: 'Payments',
  query: {
    year: String(filters.year),
    month: String(filters.month),
    collect: '1',
  },
}))

function workflowStepClass(step) {
  const draft = periodSummary.counts.draft ?? 0
  const issued = (periodSummary.counts.issued ?? 0) + (periodSummary.counts.partially_paid ?? 0)
  const active =
    (step === 1 && draft > 0)
    || (step === 2 && draft > 0)
    || (step === 3 && issued > 0 && draft === 0)
  const done =
    (step === 1 && draft === 0 && periodSummary.counts.total > 0)
    || (step === 2 && draft === 0 && issued > 0)
  if (active) return 'rounded-md bg-indigo-100 px-2 py-0.5 font-semibold text-indigo-800'
  if (done) return 'text-emerald-700'
  return 'text-slate-500'
}

async function runBulkIssue(ids) {
  pageError.value = ''
  try {
    const result = await bulkIssue(ids)
    const issued = result.issued ?? 0
    pageMessage.value = `Issued ${issued} invoice(s).`
    if (result.failed > 0) {
      pageMessage.value += ` ${result.failed} failed.`
    }
    selectedIds.value = []
    await refresh()

    if (issued > 0) {
      const goPay = await confirm({
        title: 'Record tenant payments',
        message: `${issued} invoice(s) issued for ${months[filters.month - 1]} ${filters.year}. Open Payments to record FIFO allocations for AYUB, ALI, and other tenants?`,
        confirmLabel: 'Go to Payments',
        cancelLabel: 'Stay on invoices',
        variant: 'primary',
      })
      if (goPay) {
        router.push(paymentsLink.value)
        return
      }
      setView('all')
      smartFilters.status = 'issued'
      syncAndFetch()
    }
  } catch (e) {
    pageError.value = e.response?.data?.message || 'Bulk issue failed.'
  }
}

function formatMoney(v) {
  return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(Number(v) || 0)
}

let buildingSearchTimer = null
function onBuildingSearch(q) {
  clearTimeout(buildingSearchTimer)
  buildingSearchTimer = setTimeout(
    () => fetchBuildings(q, { ensureId: smartFilters.building_id || undefined }),
    280
  )
}

onMounted(() => {
  if (route.query.year) filters.year = Number(route.query.year)
  if (route.query.month) filters.month = Number(route.query.month)
  bindRoute(route, router, { debounceMs: 300 })
  fetchBuildings('')
  fetchReadiness()
  syncAndFetch()
})
</script>
