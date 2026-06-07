<template>
  <WorklistLayout
    eyebrow="Operations"
    title="Buildings"
    :count="meta.total"
    description="Property portfolio — locations, floors, and operating currency."
  >
    <template #actions>
      <ErpButton variant="ghost" size="sm" :loading="loading" @click="fetchList(meta.current_page)">
        Refresh
      </ErpButton>
      <ErpButton @click="formModal.openCreate()">Add building</ErpButton>
    </template>

    <template #kpis>
      <KpiStrip class="mb-0">
        <KpiCard label="Total" :value="summary.total" />
        <KpiCard label="Active" :value="summary.active" caption="Operational" />
        <KpiCard label="Inactive" :value="summary.inactive" />
        <KpiCard label="Total floors" :value="summary.floors" />
      </KpiStrip>
    </template>

    <template #filters>
      <AlertBanner v-if="error" :message="error" @dismiss="error = ''" />
      <SmartFilterBar :chips="chips" @clear-all="onClearAll" @remove-chip="removeChip">
        <FormField label="Search" span="2">
          <input
            v-model="smartFilters.search"
            type="search"
            class="erp-input"
            placeholder="Name, city, code…"
            @input="debounceFetch"
          />
        </FormField>
        <FormField label="Status">
          <select v-model="smartFilters.status" class="erp-select" @change="syncAndFetch">
            <option value="">All</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
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
        empty-title="No buildings"
        empty-description="Register your first property to get started."
        @page-change="fetchList"
        @row-click="onRowClick"
      >
        <template #emptyAction>
          <ErpButton @click="formModal.openCreate()">Add building</ErpButton>
        </template>
        <template #cell-name="{ row }">
          <div>
            <p class="font-medium text-slate-900">{{ row.name }}</p>
            <p class="text-xs text-slate-500">
              {{ [row.city, row.country].filter(Boolean).join(', ') || '—' }}
            </p>
          </div>
        </template>
        <template #cell-code="{ row }">
          <code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs">{{ row.code || '—' }}</code>
        </template>
        <template #cell-type="{ row }">
          <span class="capitalize">{{ row.type || '—' }}</span>
        </template>
        <template #cell-units="{ row }">
          <span class="tabular-nums" :title="`Registered: ${row.apartments_count ?? 0}, capacity: ${row.total_units ?? '—'}`">
            {{ row.apartments_count ?? 0 }}<span class="text-slate-400"> / </span>{{ row.total_units ?? '—' }}
          </span>
        </template>
        <template #cell-location="{ row }">
          <span class="block max-w-[14rem] truncate text-xs text-slate-600" :title="row.address || ''">
            {{ row.address || '—' }}
          </span>
        </template>
        <template #cell-timezone="{ row }">
          <span class="font-mono text-xs">{{ row.timezone || '—' }}</span>
        </template>
        <template #cell-currency="{ row }">{{ row.operating_currency || 'USD' }}</template>
        <template #cell-status="{ row }">
          <StatusBadge :status="row.is_active ? 'active' : 'inactive'" :label="row.is_active ? 'Active' : 'Inactive'" />
        </template>
        <template #cell-actions="{ row }">
          <RowActionsMenu :actions="buildingActions(row)" />
        </template>
      </DataTable>
    </template>
  </WorklistLayout>

  <BuildingFormModal
    :open="formModal.state.open"
    :entity-id="formModal.state.id"
    @close="formModal.close()"
    @saved="fetchList(meta.current_page)"
  />
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useBuildings } from '@/composables/useBuildings'
import { useSmartFilters } from '@/composables/useSmartFilters'
import { useFormModal } from '@/composables/useFormModal'
import { useConfirm } from '@/composables/useConfirm'
import { compactActions, viewAction, editAction, deleteAction } from '@/composables/useTableActions'
import BuildingFormModal from '@/components/forms/BuildingFormModal.vue'
import {
  WorklistLayout,
  SmartFilterBar,
  DataTable,
  RowActionsMenu,
  FormField,
  ErpButton,
  StatusBadge,
  AlertBanner,
  KpiCard,
  KpiStrip,
} from '@/components/erp'

const router = useRouter()
const route = useRoute()
const formModal = useFormModal()
const error = ref('')
const { items, loading, meta, filters, summary, fetchList, remove, resetFilters } = useBuildings()

const { filters: smartFilters, chips, clearAll, removeChip, bindRoute } = useSmartFilters({
  defaults: { search: '', status: '' },
  labels: { search: { label: 'Search' }, status: { label: 'Status' } },
})

watch(smartFilters, () => {
  filters.search = smartFilters.search
  filters.status = smartFilters.status
}, { deep: true, immediate: true })

const columns = [
  { key: 'name', label: 'Building', emphasis: true },
  { key: 'code', label: 'Code', mono: true },
  { key: 'type', label: 'Type' },
  { key: 'units', label: 'Units', align: 'right' },
  { key: 'location', label: 'Address', truncate: true },
  { key: 'timezone', label: 'TZ', mono: true },
  { key: 'currency', label: 'Curr' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: '', align: 'right', type: 'actions' },
]

function buildingActions(row) {
  return compactActions([
    viewAction('BuildingShow', row.id),
    editAction(() => formModal.openEdit(row.id)),
    row.controls?.can_delete && deleteAction(() => onDelete(row)),
  ])
}

let debounceTimer = null
function debounceFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => syncAndFetch(), 350)
}
function syncAndFetch() {
  filters.search = smartFilters.search
  filters.status = smartFilters.status
  fetchList(1)
}
function onClearAll() {
  clearAll()
  resetFilters()
  syncAndFetch()
}
function onRowClick(row) {
  router.push({ name: 'BuildingShow', params: { id: row.id } })
}
async function onDelete(row) {
  const { confirm } = useConfirm()
  if (!(await confirm({ title: 'Delete building', message: `Delete "${row.name}"?`, confirmLabel: 'Delete', variant: 'danger' }))) return
  try {
    await remove(row)
  } catch {
    error.value = 'Failed to delete building.'
  }
}

onMounted(() => {
  bindRoute(route, router, { debounceMs: 300 })
  formModal.syncFromRoute(route, router)
  fetchList()
})
</script>
