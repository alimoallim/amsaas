<template>
  <WorklistLayout
    eyebrow="Utilities"
    title="Meter readings"
    :count="meta.total"
    description="Capture, review, and approve utility consumption readings."
  >
    <template #actions>
      <ErpButton variant="ghost" size="sm" :loading="loading" @click="fetchList(meta.current_page)">Refresh</ErpButton>
      <ErpButton @click="formModal.openCreate()">Capture reading</ErpButton>
    </template>

    <template #kpis>
      <KpiStrip class="grid-cols-2 lg:grid-cols-4">
        <KpiCard label="Total" :value="summary.total" />
        <KpiCard label="Approved" :value="summary.approved" />
        <KpiCard label="Pending" :value="summary.pending" />
        <KpiCard label="Anomalies" :value="summary.anomalies" />
      </KpiStrip>
    </template>

    <template #filters>
      <SmartFilterBar :chips="chips" @clear-all="onClearAll" @remove-chip="removeChip">
        <FormField label="Search" span="2">
          <input v-model="smartFilters.search" type="search" class="erp-input" placeholder="Meter, reference…" @input="debounceFetch" />
        </FormField>
        <FormField label="Status">
          <select v-model="smartFilters.status" class="erp-select" @change="syncAndFetch">
            <option value="">All</option>
            <option value="draft">Draft</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
          </select>
        </FormField>
        <FormField label="Utility">
          <select v-model="smartFilters.utility_type" class="erp-select" @change="syncAndFetch">
            <option value="">All</option>
            <option value="electricity">Electricity</option>
            <option value="water">Water</option>
            <option value="gas">Gas</option>
          </select>
        </FormField>
        <FormField label="Anomalies">
          <select v-model="smartFilters.anomalies_only" class="erp-select" @change="syncAndFetch">
            <option value="">All readings</option>
            <option value="1">Anomalies only</option>
          </select>
        </FormField>
      </SmartFilterBar>
    </template>

    <template #table>
      <DataTable
        :columns="columns"
        :rows="items"
        :loading="loading"
        :meta="meta"
        empty-title="No readings"
        empty-description="Capture a meter reading or change filters."
        @page-change="fetchList"
        @row-click="(row) => $router.push({ name: 'MeterReadingShow', params: { id: row.id } })"
      >
        <template #cell-reference="{ row }">
          <code class="text-xs">{{ row.reference || row.id?.slice(0, 8) }}</code>
        </template>
        <template #cell-meter="{ row }">
          <div>
            <p class="font-mono text-xs">{{ row.meter?.meter_number || '—' }}</p>
            <p v-if="row.meter?.utility_type?.label" class="text-xs text-slate-500">{{ row.meter.utility_type.label }}</p>
          </div>
        </template>
        <template #cell-period="{ row }">
          <span class="text-xs text-slate-600">
            {{ formatDate(row.reading?.previous_reading_date) }} → {{ formatDate(row.reading?.reading_date || row.reading_date) }}
          </span>
        </template>
        <template #cell-consumption="{ row }">
          <span class="tabular-nums">{{ formatReading(row.reading?.consumption ?? row.consumption) }}</span>
          <span v-if="row.anomaly?.detected" class="ml-2 text-xs font-medium text-amber-700">Anomaly</span>
        </template>
        <template #cell-status="{ row }">
          <StatusBadge :status="row.status?.value || row.status" :label="row.status?.label || row.status" />
        </template>
        <template #cell-actions="{ row }">
          <RowActionsMenu :actions="readingActions(row)" />
        </template>
      </DataTable>
    </template>
  </WorklistLayout>

  <MeterReadingFormModal
    :open="formModal.state.open"
    :entity-id="formModal.state.id"
    @close="formModal.close()"
    @saved="onSaved"
  />

  <ErpModal
    :open="rejectModal.open"
    title="Reject reading"
    subtitle="Provide a reason for rejection."
    confirm-label="Reject"
    confirm-variant="danger"
    :loading="rejectModal.loading"
    @close="rejectModal.open = false"
    @confirm="submitReject"
  >
    <FormField label="Reason" class="mt-2">
      <textarea v-model="rejectModal.reason" class="erp-input min-h-[88px]" placeholder="Required…" />
    </FormField>
  </ErpModal>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useMeterReadings } from '@/composables/useMeterReadings'
import { useSmartFilters } from '@/composables/useSmartFilters'
import { useFormModal } from '@/composables/useFormModal'
import { compactActions, viewAction } from '@/composables/useTableActions'
import MeterReadingFormModal from '@/components/forms/MeterReadingFormModal.vue'
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
} from '@/components/erp'

const route = useRoute()
const router = useRouter()
const formModal = useFormModal()
const approving = ref(null)
const { items, loading, meta, filters, summary, fetchList, approve, reject, resetFilters } = useMeterReadings()

const { filters: smartFilters, chips, clearAll, removeChip, bindRoute } = useSmartFilters({
  defaults: { search: '', status: '', utility_type: '', anomalies_only: '' },
  labels: {
    search: { label: 'Search' },
    status: { label: 'Status' },
    utility_type: { label: 'Utility' },
    anomalies_only: { label: 'Anomalies', format: (v) => (v === '1' ? 'Yes' : v) },
  },
})

watch(smartFilters, () => Object.assign(filters, { ...smartFilters }), { deep: true, immediate: true })

const columns = [
  { key: 'reference', label: 'Ref', mono: true },
  { key: 'meter', label: 'Meter' },
  { key: 'period', label: 'Period' },
  { key: 'consumption', label: 'Consumption', align: 'right' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: '', align: 'right', type: 'actions' },
]

function readingStatus(row) {
  return row.status?.value || row.status
}

function readingActions(row) {
  const status = readingStatus(row)
  const busy = approving.value === row.id
  return compactActions([
    viewAction('MeterReadingShow', row.id),
    status !== 'approved' && {
      key: 'approve',
      label: 'Approve',
      variant: 'success',
      disabled: busy,
      onClick: () => onApprove(row),
    },
    status !== 'approved' && {
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
function onClearAll() {
  clearAll()
  resetFilters()
  syncAndFetch()
}
async function onApprove(row) {
  approving.value = row.id
  try {
    await approve(row)
  } finally {
    approving.value = null
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
  } finally {
    rejectModal.loading = false
  }
}
function formatDate(d) {
  if (!d) return '—'
  try {
    return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
  } catch {
    return d
  }
}
function formatReading(val) {
  if (val == null || val === '') return '—'
  return Number(val).toLocaleString(undefined, { maximumFractionDigits: 4 })
}

async function onSaved() {
  await fetchList(meta.value.current_page)
}

onMounted(() => {
  bindRoute(route, router, { debounceMs: 300 })
  formModal.syncFromRoute(route, router)
  fetchList()
})
</script>
