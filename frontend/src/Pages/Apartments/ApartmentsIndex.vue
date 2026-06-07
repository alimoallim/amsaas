<template>
  <WorklistLayout
    eyebrow="Operations"
    title="Apartments"
    :count="meta.total"
    description="Unit inventory, occupancy status, and listing configuration."
  >
    <template #actions>
      <ErpButton @click="formModal.openCreate()">New apartment</ErpButton>
    </template>

    <template #kpis>
      <KpiStrip>
        <KpiCard label="Total units" :value="summary.total || meta.total" />
        <KpiCard label="Occupied" :value="summary.occupied || 0" />
        <KpiCard label="Available" :value="summary.available || 0" />
        <KpiCard
          label="Occupancy"
          :value="occupancyPct + '%'"
          :caption="`${summary.occupied || 0} of ${summary.total || 0}`"
        />
      </KpiStrip>
    </template>

    <template #filters>
      <SmartFilterBar :chips="chips" @clear-all="onClearAll" @remove-chip="removeChip">
        <FormField label="Search" span="2">
          <input v-model="smartFilters.search" type="search" class="erp-input" placeholder="Unit, building…" @input="debounceFetch" />
        </FormField>
        <FormField label="Building">
          <select v-model="smartFilters.building_id" class="erp-select" @change="syncAndFetch">
            <option value="">All buildings</option>
            <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
          </select>
        </FormField>
        <FormField label="Listing">
          <select v-model="smartFilters.listing_type" class="erp-select" @change="syncAndFetch">
            <option value="">All</option>
            <option value="rental">Rental</option>
            <option value="sale">Sale</option>
            <option value="hybrid">Hybrid</option>
          </select>
        </FormField>
        <FormField label="Status">
          <select v-model="smartFilters.inventory_status" class="erp-select" @change="syncAndFetch">
            <option value="">All statuses</option>
            <option value="available">Available</option>
            <option value="occupied">Occupied</option>
            <option value="reserved">Reserved</option>
            <option value="maintenance">Maintenance</option>
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
        empty-title="No apartments"
        empty-description="Create a unit record to start leasing."
        @page-change="fetchList"
        @row-click="(row) => $router.push({ name: 'ApartmentShow', params: { id: row.id } })"
      >
        <template #emptyAction>
          <ErpButton @click="formModal.openCreate()">New apartment</ErpButton>
        </template>
        <template #cell-unit="{ row }">
          <div>
            <p class="font-medium">{{ row.unit?.unit_number || '—' }}</p>
            <p v-if="row.unit?.floor != null" class="text-xs text-slate-500">Floor {{ row.unit.floor }}</p>
          </div>
        </template>
        <template #cell-building="{ row }">
          <div>
            <p>{{ row.building?.name || '—' }}</p>
            <p v-if="row.building?.city" class="text-xs text-slate-500">{{ row.building.city }}</p>
          </div>
        </template>
        <template #cell-layout="{ row }">
          <span class="text-xs text-slate-600">
            {{ layoutSummary(row) }}
          </span>
        </template>
        <template #cell-status="{ row }">
          <StatusBadge :status="row.listing?.inventory_status || 'inactive'" :label="formatStatus(row.listing?.inventory_status)" />
        </template>
        <template #cell-occupancy="{ row }">
          <div class="min-w-0">
            <StatusBadge
              v-if="row.occupancy?.has_active_lease"
              status="active"
              :label="row.occupancy?.active_agreement_number || 'Leased'"
            />
            <span v-else class="text-xs text-slate-600">{{ row.occupancy?.hint || '—' }}</span>
          </div>
        </template>
        <template #cell-listing="{ row }">
          <span class="capitalize">{{ row.listing?.listing_type || '—' }}</span>
        </template>
        <template #cell-price="{ row }">
          <span class="tabular-nums">{{ formatMoney(row.pricing?.effective_price, row.pricing?.currency) }}</span>
        </template>
        <template #cell-actions="{ row }">
          <RowActionsMenu :actions="apartmentActions(row)" />
        </template>
      </DataTable>
    </template>
  </WorklistLayout>

  <ApartmentFormModal
    :open="formModal.state.open"
    :entity-id="formModal.state.id"
    @close="formModal.close()"
    @saved="onSaved"
  />
</template>

<script setup>
import { computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApartments } from '@/composables/useApartments'
import { useSmartFilters } from '@/composables/useSmartFilters'
import { useFormModal } from '@/composables/useFormModal'
import { compactActions, viewAction, editAction } from '@/composables/useTableActions'
import ApartmentFormModal from '@/components/forms/ApartmentFormModal.vue'
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
const {
  items,
  buildings,
  loading,
  meta,
  summary,
  filters,
  fetchList,
  fetchBuildings,
  fetchSummary,
  resetFilters,
} = useApartments()

const { filters: smartFilters, chips, clearAll, removeChip, bindRoute } = useSmartFilters({
  defaults: { search: '', building_id: '', listing_type: '', inventory_status: '' },
  labels: {
    search: { label: 'Search' },
    building_id: { label: 'Building', format: (v) => buildings.value.find((b) => String(b.id) === String(v))?.name || v },
    listing_type: { label: 'Listing' },
    inventory_status: { label: 'Status', format: (v) => formatStatus(v) },
  },
})

watch(smartFilters, () => {
  filters.search = smartFilters.search
  filters.building_id = smartFilters.building_id
  filters.listing_type = smartFilters.listing_type
  filters.inventory_status = smartFilters.inventory_status
}, { deep: true, immediate: true })

const occupancyPct = computed(() => {
  const t = summary.value.total || 0
  if (!t) return 0
  return Math.round(((summary.value.occupied || 0) / t) * 100)
})

const columns = [
  { key: 'unit', label: 'Unit', emphasis: true },
  { key: 'building', label: 'Building' },
  { key: 'layout', label: 'Layout' },
  { key: 'status', label: 'Inventory' },
  { key: 'occupancy', label: 'Occupancy' },
  { key: 'listing', label: 'Listing' },
  { key: 'price', label: 'Price', align: 'right' },
  { key: 'actions', label: '', align: 'right', type: 'actions' },
]

function apartmentActions(row) {
  return compactActions([
    viewAction('ApartmentShow', row.id),
    row.controls?.can_edit !== false && editAction(() => formModal.openEdit(row.id)),
  ])
}

function layoutSummary(row) {
  const parts = []
  if (row.layout?.bedrooms != null) parts.push(`${row.layout.bedrooms} bed`)
  if (row.layout?.bathrooms != null) parts.push(`${row.layout.bathrooms} bath`)
  if (row.layout?.area_sqm) parts.push(`${row.layout.area_sqm} m²`)
  if (row.unit?.property_type) parts.push(row.unit.property_type)
  return parts.length ? parts.join(' · ') : '—'
}

let debounceTimer = null
function debounceFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(syncAndFetch, 350)
}
function syncAndFetch() {
  Object.assign(filters, {
    search: smartFilters.search,
    building_id: smartFilters.building_id,
    listing_type: smartFilters.listing_type,
    inventory_status: smartFilters.inventory_status,
  })
  fetchList(1)
}
function onClearAll() {
  clearAll()
  resetFilters()
  syncAndFetch()
}
function formatStatus(s) {
  return s ? String(s).replaceAll('_', ' ') : '—'
}
function formatMoney(v, c = 'USD') {
  if (v == null || v === '') return '—'
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: c || 'USD' }).format(v)
}

async function onSaved() {
  await fetchList(meta.value.current_page)
  await fetchSummary()
}

onMounted(async () => {
  bindRoute(route, router, { debounceMs: 300 })
  formModal.syncFromRoute(route, router)
  await fetchBuildings()
  await fetchSummary()
  if (route.query.building_id) smartFilters.building_id = String(route.query.building_id)
  await fetchList()
})
</script>
