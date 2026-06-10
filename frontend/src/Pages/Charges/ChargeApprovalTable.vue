<template>
  <WorklistLayout
    eyebrow="Finance"
    title="Charge queue"
    :count="meta.total"
    description="Select pending lines and approve in bulk, or act on individual rows."
  >
    <template #actions>
      <ErpButton
        variant="primary"
        :disabled="!selectedIds.length"
        @click="onBulkApprove"
      >
        Approve selected ({{ selectedIds.length }})
      </ErpButton>
      <ErpButton variant="ghost" size="sm" :loading="loading" @click="refresh">
        Refresh
      </ErpButton>
    </template>

    <template #kpis>
      <AlertBanner
        v-if="error"
        variant="error"
        class="mb-4"
        :message="error?.response?.data?.message || error?.message || 'Failed to load charges.'"
        @dismiss="error = null"
      />
      <KpiStrip class="grid-cols-2 lg:grid-cols-3">
        <KpiCard label="Pending (company)" :value="companySummary.pending" />
        <KpiCard label="Ready to invoice" :value="companySummary.approved_ready" />
        <KpiCard label="On this page" :value="summary.pending" caption="Pending rows shown" />
      </KpiStrip>
    </template>

    <template #filters>
      <SmartFilterBar :chips="chips" @clear-all="onClearAll" @remove-chip="removeChip">
        <FormField label="Search" span="2">
          <input
            v-model="smartFilters.search"
            type="search"
            class="erp-input"
            placeholder="Charge #, tenant, unit…"
            @input="debounceFetch"
          />
        </FormField>
        <FormField label="Status">
          <select v-model="smartFilters.status" class="erp-select" @change="syncAndFetch">
            <option value="">All</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="cancelled">Cancelled</option>
            <option value="invoiced">Invoiced</option>
          </select>
        </FormField>
      </SmartFilterBar>
    </template>

    <template #table>
      <DataTable
        selectable
        multi-select
        v-model:selected-ids="selectedIds"
        :columns="columns"
        :rows="items"
        :loading="loading"
        :meta="meta"
        empty-title="No charges"
        empty-description="Approve meter readings to generate utility charges."
        @page-change="fetchList"
      >
        <template #emptyAction>
          <ErpButton :to="{ name: 'MeterReadings' }">Open meter readings</ErpButton>
        </template>
        <template #cell-charge_number="{ row }">
          <code class="text-xs font-mono">{{ row.charge_number }}</code>
        </template>
        <template #cell-tenant="{ row }">
          <div>
            <p class="text-sm font-medium text-slate-900">{{ row.snapshots?.tenant || '—' }}</p>
            <p class="text-xs text-slate-500">{{ row.snapshots?.apartment || '' }}</p>
          </div>
        </template>
        <template #cell-model="{ row }">
          <span class="text-xs text-slate-600">{{ row.charge_model?.name || '—' }}</span>
        </template>
        <template #cell-consumption="{ row }">
          <span class="tabular-nums text-sm">{{ formatNum(row.meter?.consumption) }}</span>
        </template>
        <template #cell-total="{ row }">
          <span class="font-mono text-sm tabular-nums">
            {{ formatMoney(row.amounts?.total, row.currency) }}
          </span>
        </template>
        <template #cell-status="{ row }">
          <StatusBadge :status="row.status?.value || row.status" :label="row.status?.label || row.status" />
        </template>
        <template #cell-actions="{ row }">
          <RowActionsMenu :actions="chargeActions(row)" />
        </template>
      </DataTable>
    </template>
  </WorklistLayout>

  <ErpModal
    :open="rejectModal.open"
    title="Reject charge"
    subtitle="Cancelled charges will not be invoiced."
    confirm-label="Reject"
    confirm-variant="danger"
    :loading="rejectModal.loading"
    @close="rejectModal.open = false"
    @confirm="submitReject"
  >
    <FormField label="Reason" required class="mt-2">
      <textarea v-model="rejectModal.reason" class="erp-input min-h-[88px]" placeholder="Required…" />
    </FormField>
  </ErpModal>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCharges } from '@/composables/useCharges'
import { useSmartFilters } from '@/composables/useSmartFilters'
import { useConfirm } from '@/composables/useConfirm'
import { useToastStore } from '@/stores/toast'
import { compactActions } from '@/composables/useTableActions'
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
  ErpModal,
  AlertBanner,
} from '@/components/erp'

const route = useRoute()
const router = useRouter()
const { confirm } = useConfirm()
const toast = useToastStore()
const selectedIds = ref([])
const acting = ref(null)

const {
  items,
  loading,
  error,
  meta,
  summary,
  companySummary,
  filters,
  fetchList,
  fetchCompanySummary,
  approve,
  reject,
  bulkApprove,
  resetFilters,
} = useCharges()

const { filters: smartFilters, chips, clearAll, removeChip, bindRoute } = useSmartFilters({
  defaults: { search: '', status: 'pending', category: 'utility' },
  labels: {
    search: { label: 'Search' },
    status: { label: 'Status' },
  },
})

watch(smartFilters, () => Object.assign(filters, { ...smartFilters }), { deep: true, immediate: true })

if (route.query.meter_reading_id) {
  filters.meter_reading_id = String(route.query.meter_reading_id)
}

const columns = [
  { key: 'charge_number', label: 'Charge #', mono: true },
  { key: 'tenant', label: 'Tenant / unit' },
  { key: 'model', label: 'Model' },
  { key: 'consumption', label: 'Consumption', align: 'right' },
  { key: 'total', label: 'Total', align: 'right' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: '', align: 'right', type: 'actions' },
]

function chargeStatus(row) {
  return row.status?.value || row.status
}

function chargeActions(row) {
  const controls = row.controls || {}
  const busy = acting.value === row.id
  return compactActions([
    controls.can_view_reading &&
      row.meter_reading_id && {
        key: 'reading',
        label: 'View reading',
        to: { name: 'MeterReadingShow', params: { id: row.meter_reading_id } },
      },
    controls.can_approve && {
      key: 'approve',
      label: 'Approve',
      variant: 'success',
      disabled: busy,
      onClick: () => onApprove(row),
    },
    controls.can_reject && {
      key: 'reject',
      label: 'Reject',
      variant: 'danger',
      onClick: () => openReject(row),
    },
  ])
}

const rejectModal = reactive({ open: false, loading: false, row: null, reason: '' })

let debounceTimer = null
function debounceFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(syncAndFetch, 350)
}

function syncAndFetch() {
  Object.assign(filters, { ...smartFilters })
  fetchList(1)
}

async function refresh() {
  await fetchList(meta.value.current_page)
  await fetchCompanySummary()
}

function onClearAll() {
  clearAll()
  resetFilters()
  smartFilters.status = 'pending'
  syncAndFetch()
}

async function onApprove(row) {
  const ok = await confirm({
    title: 'Approve charge',
    message: `Approve ${row.charge_number} for ${formatMoney(row.amounts?.total, row.currency)}?`,
    confirmLabel: 'Approve',
    variant: 'primary',
  })
  if (!ok) return
  acting.value = row.id
  try {
    const { message } = await approve(row)
    if (message) toast.show(message, 'success')
    await refresh()
    selectedIds.value = selectedIds.value.filter((id) => id !== row.id)
  } finally {
    acting.value = null
  }
}

function openReject(row) {
  rejectModal.row = row
  rejectModal.reason = ''
  rejectModal.open = true
}

async function submitReject() {
  if (!rejectModal.reason.trim() || !rejectModal.row) return
  rejectModal.loading = true
  try {
    await reject(rejectModal.row, rejectModal.reason.trim())
    rejectModal.open = false
    await refresh()
  } finally {
    rejectModal.loading = false
  }
}

async function onBulkApprove() {
  if (!selectedIds.value.length) return
  const ok = await confirm({
    title: 'Approve charges',
    message: `Approve ${selectedIds.value.length} selected charge(s)?`,
    confirmLabel: 'Approve all',
  })
  if (!ok) return
  const { message } = await bulkApprove(selectedIds.value)
  if (message) toast.show(message, 'success')
  selectedIds.value = []
  await refresh()
}

function formatNum(v) {
  if (v == null || v === '') return '—'
  return Number(v).toFixed(4)
}

function formatMoney(amount, currency = 'USD') {
  if (amount == null) return '—'
  return new Intl.NumberFormat(undefined, { style: 'currency', currency: currency || 'USD' }).format(
    Number(amount)
  )
}

onMounted(() => {
  bindRoute(route, router, { debounceMs: 300 })
  syncAndFetch()
})
</script>
