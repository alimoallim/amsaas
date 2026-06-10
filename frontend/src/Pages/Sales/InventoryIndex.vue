<template>
  <WorklistLayout
    eyebrow="Sales"
    title="Property inventory"
    :count="meta.total"
    description="Units listed for sale — filter by building, price, and availability."
  >
    <template #actions>
      <ErpButton variant="secondary" :to="{ name: 'SaleReservations' }">Reservations</ErpButton>
      <ErpButton variant="secondary" :to="{ name: 'Buyers' }">Buyers</ErpButton>
    </template>

    <template #filters>
      <SmartFilterBar :chips="chips" @clear-all="onClearAll" @remove-chip="removeChip">
        <FormField label="Search" span="2">
          <input
            v-model="smartFilters.search"
            type="search"
            class="erp-input"
            placeholder="Unit number…"
            @input="debounceFetch"
          />
        </FormField>
        <FormField label="Status">
          <select v-model="smartFilters.inventory_status" class="erp-select" @change="syncAndFetch">
            <option value="">All listed</option>
            <option value="available">Available</option>
            <option value="reserved">Reserved</option>
          </select>
        </FormField>
        <FormField label="Min price">
          <input v-model="smartFilters.min_price" type="number" min="0" class="erp-input" @change="syncAndFetch" />
        </FormField>
        <FormField label="Max price">
          <input v-model="smartFilters.max_price" type="number" min="0" class="erp-input" @change="syncAndFetch" />
        </FormField>
        <FormField label="Bedrooms (min)">
          <input v-model="smartFilters.bedrooms" type="number" min="0" class="erp-input" @change="syncAndFetch" />
        </FormField>
      </SmartFilterBar>
    </template>

    <template #table>
      <DataTable
        :columns="columns"
        :rows="items"
        :loading="loading"
        :meta="meta"
        empty-title="No sale units"
        empty-description="Add apartments with sale or hybrid listing type."
        @page-change="fetchList"
        @row-click="openUnit"
      >
        <template #cell-actions="{ row }">
          <ErpButton
            v-if="canReserve(row)"
            size="sm"
            variant="secondary"
            @click.stop="openReserve(row)"
          >
            Reserve
          </ErpButton>
        </template>
        <template #cell-unit="{ row }">
          <div>
            <p class="font-medium">{{ row.unit_number || '—' }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ row.building?.name || '—' }}</p>
          </div>
        </template>
        <template #cell-specs="{ row }">
          <span class="text-xs text-slate-600 dark:text-slate-400">
            {{ row.bedrooms ?? '—' }} bed · Floor {{ row.floor ?? '—' }}
          </span>
        </template>
        <template #cell-price="{ row }">
          <span class="font-medium tabular-nums">
            {{ formatMoney(row.market_sale_price, row.currency) }}
          </span>
        </template>
        <template #cell-status="{ row }">
          <StatusBadge
            :status="row.listing?.inventory_status || row.inventory_status || 'inactive'"
            :label="formatStatus(row.listing?.inventory_status || row.inventory_status)"
          />
        </template>
        <template #cell-sellable="{ row }">
          <StatusBadge
            :status="row.controls?.can_be_sold ? 'available' : 'inactive'"
            :label="row.controls?.can_be_sold ? 'Sellable' : 'Blocked'"
            :dot="true"
          />
        </template>
      </DataTable>
    </template>
  </WorklistLayout>

  <SaleReservationFormModal
    :open="reserveOpen"
    :apartment="reserveApartment"
    @close="closeReserve"
    @saved="onReserved"
  />
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useInventory } from '@/composables/useInventory'
import { useSmartFilters } from '@/composables/useSmartFilters'
import { useToastStore } from '@/stores/toast'
import SaleReservationFormModal from '@/components/forms/SaleReservationFormModal.vue'
import {
  WorklistLayout,
  SmartFilterBar,
  DataTable,
  FormField,
  ErpButton,
  StatusBadge,
} from '@/components/erp'

const route = useRoute()
const router = useRouter()
const toast = useToastStore()
const { items, loading, meta, filters, fetchList, resetFilters } = useInventory()

const reserveOpen = ref(false)
const reserveApartment = ref(null)

const { filters: smartFilters, chips, clearAll, removeChip, bindRoute } = useSmartFilters({
  defaults: {
    search: '',
    inventory_status: '',
    min_price: '',
    max_price: '',
    bedrooms: '',
  },
  labels: {
    search: { label: 'Search' },
    inventory_status: { label: 'Status' },
    min_price: { label: 'Min price' },
    max_price: { label: 'Max price' },
    bedrooms: { label: 'Bedrooms' },
  },
})

watch(smartFilters, () => {
  Object.assign(filters, { ...smartFilters, sellable_only: true })
}, { deep: true, immediate: true })

const columns = [
  { key: 'unit', label: 'Unit', emphasis: true },
  { key: 'specs', label: 'Specs' },
  { key: 'price', label: 'Sale price', align: 'right' },
  { key: 'status', label: 'Inventory' },
  { key: 'sellable', label: 'Sellable' },
  { key: 'actions', label: '', align: 'right', type: 'actions' },
]

function canReserve(row) {
  const status = row.listing?.inventory_status || row.inventory_status
  return row.controls?.can_be_sold && status === 'available'
}

function openReserve(row) {
  reserveApartment.value = row
  reserveOpen.value = true
}

function closeReserve() {
  reserveOpen.value = false
  reserveApartment.value = null
}

async function onReserved() {
  toast.show('Reservation created', 'success')
  closeReserve()
  await fetchList(meta.value.current_page)
}

function formatMoney(amount, currency = 'USD') {
  if (amount == null || amount === '') return '—'
  return new Intl.NumberFormat(undefined, { style: 'currency', currency }).format(Number(amount))
}

function formatStatus(s) {
  if (!s) return '—'
  return String(s).replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}

function openUnit(row) {
  if (row.id) router.push({ name: 'ApartmentShow', params: { id: row.id } })
}

let debounceTimer = null
function debounceFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(syncAndFetch, 350)
}

function syncAndFetch() {
  Object.assign(filters, { ...smartFilters, sellable_only: true })
  fetchList(1)
}

function onClearAll() {
  clearAll()
  resetFilters()
  syncAndFetch()
}

onMounted(() => {
  bindRoute(route, router, { debounceMs: 300 })
  fetchList()
})
</script>
