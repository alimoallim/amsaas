<template>
  <WorklistLayout
    eyebrow="Utilities"
    title="Meters"
    :count="meta.total"
    description="Utility meter registry — location, readings, and operational status."
  >
    <template #actions>
      <ErpButton variant="ghost" size="sm" :loading="loading" @click="fetchList(meta.current_page)">Refresh</ErpButton>
      <ErpButton @click="formModal.openCreate()">Register meter</ErpButton>
    </template>

    <template #kpis>
      <KpiStrip class="grid-cols-2 sm:grid-cols-3 lg:grid-cols-6">
        <KpiCard label="Total" :value="summary.total || 0" />
        <KpiCard label="Active" :value="summary.active || 0" />
        <KpiCard label="Faulty" :value="summary.faulty || 0" />
        <KpiCard label="Maintenance" :value="summary.maintenance || 0" />
        <KpiCard label="Smart" :value="summary.smart || 0" />
        <KpiCard label="Shared" :value="summary.shared || 0" />
      </KpiStrip>
    </template>

    <template #filters>
      <SmartFilterBar :chips="chips" @clear-all="onClearAll" @remove-chip="removeChip">
        <FormField label="Search" span="2">
          <input v-model="smartFilters.search" type="search" class="erp-input" placeholder="Meter number, serial…" @input="debounceFetch" />
        </FormField>
        <FormField label="Utility">
          <select v-model="smartFilters.utility_type" class="erp-select" @change="syncAndFetch">
            <option value="">All utilities</option>
            <option value="electricity">Electricity</option>
            <option value="water">Water</option>
            <option value="gas">Gas</option>
            <option value="solar">Solar</option>
          </select>
        </FormField>
        <FormField label="Status">
          <select v-model="smartFilters.status" class="erp-select" @change="syncAndFetch">
            <option value="">All</option>
            <option value="active">Active</option>
            <option value="faulty">Faulty</option>
            <option value="under_maintenance">Maintenance</option>
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
        empty-title="No meters"
        empty-description="Register a utility meter to begin tracking."
        @page-change="fetchList"
        @row-click="(row) => $router.push({ name: 'MeterShow', params: { id: row.id } })"
      >
        <template #emptyAction>
          <ErpButton @click="formModal.openCreate()">Register meter</ErpButton>
        </template>
        <template #cell-meter="{ row }">
          <div>
            <p class="font-mono text-xs font-medium">{{ row.meter_number }}</p>
            <p class="text-xs text-slate-500">{{ row.serial_number || '—' }}</p>
          </div>
        </template>
        <template #cell-utility="{ row }">
          <div>
            <p class="capitalize">{{ row.utility_type?.label || row.utility_type?.value || '—' }}</p>
            <p v-if="row.meter_type?.label" class="text-xs text-slate-500">{{ row.meter_type.label }}</p>
          </div>
        </template>
        <template #cell-ownership="{ row }">
          <span class="text-xs capitalize">{{ row.ownership_type?.label || '—' }}</span>
        </template>
        <template #cell-location="{ row }">
          <span class="text-sm">{{ row.building?.name || '—' }}</span>
          <span v-if="row.apartment" class="text-xs text-slate-500"> · Unit {{ row.apartment.unit_number }}</span>
        </template>
        <template #cell-reading="{ row }">
          <span class="tabular-nums">{{ formatReading(row.readings?.current_reading) }}</span>
        </template>
        <template #cell-status="{ row }">
          <StatusBadge :status="row.status?.value || 'inactive'" :label="row.status?.label || '—'" />
        </template>
        <template #cell-actions="{ row }">
          <RowActionsMenu :actions="meterActions(row)" />
        </template>
      </DataTable>
    </template>
  </WorklistLayout>

  <MeterFormModal
    :open="formModal.state.open"
    :entity-id="formModal.state.id"
    @close="formModal.close()"
    @saved="onSaved"
  />
</template>

<script setup>
import { watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useMeters } from '@/composables/useMeters'
import { useSmartFilters } from '@/composables/useSmartFilters'
import { useFormModal } from '@/composables/useFormModal'
import { compactActions, viewAction, editAction } from '@/composables/useTableActions'
import MeterFormModal from '@/components/forms/MeterFormModal.vue'
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
} from '@/components/erp'

const route = useRoute()
const router = useRouter()
const formModal = useFormModal()
const { items, summary, loading, meta, filters, fetchList, resetFilters } = useMeters()

const { filters: smartFilters, chips, clearAll, removeChip, bindRoute } = useSmartFilters({
  defaults: { search: '', utility_type: '', status: '' },
  labels: { search: { label: 'Search' }, utility_type: { label: 'Utility' }, status: { label: 'Status' } },
})

watch(smartFilters, () => Object.assign(filters, { ...smartFilters }), { deep: true, immediate: true })

const columns = [
  { key: 'meter', label: 'Meter', mono: true },
  { key: 'utility', label: 'Utility' },
  { key: 'ownership', label: 'Ownership' },
  { key: 'location', label: 'Location' },
  { key: 'reading', label: 'Reading', align: 'right' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: '', align: 'right', type: 'actions' },
]

function meterActions(row) {
  return compactActions([
    viewAction('MeterShow', row.id),
    editAction(() => formModal.openEdit(row.id)),
  ])
}

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
