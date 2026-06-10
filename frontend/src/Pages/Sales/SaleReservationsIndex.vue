<template>
  <WorklistLayout
    eyebrow="Sales"
    title="Reservations"
    :count="meta.total"
    description="Sale holds with deposit tracking and expiry."
  >
    <template #actions>
      <ErpButton variant="secondary" :to="{ name: 'SalesInventory' }">Inventory</ErpButton>
      <ErpButton variant="secondary" :to="{ name: 'SaleAgreements' }">Contracts</ErpButton>
    </template>

    <template #kpis>
      <KpiStrip>
        <KpiCard label="Total" :value="summary.total" />
        <KpiCard label="Pending deposit" :value="summary.pending" />
        <KpiCard label="Confirmed" :value="summary.confirmed" />
        <KpiCard label="Expired" :value="summary.expired" />
      </KpiStrip>
    </template>

    <template #filters>
      <SmartFilterBar :chips="chips" @clear-all="onClearAll" @remove-chip="removeChip">
        <FormField label="Search" span="2">
          <input
            v-model="smartFilters.search"
            type="search"
            class="erp-input"
            placeholder="Reservation #, buyer, unit…"
            @input="debounceFetch"
          />
        </FormField>
        <FormField label="Status">
          <select v-model="smartFilters.status" class="erp-select" @change="syncAndFetch">
            <option value="">All</option>
            <option value="pending_deposit">Pending deposit</option>
            <option value="confirmed">Confirmed</option>
            <option value="expired">Expired</option>
            <option value="cancelled">Cancelled</option>
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
        empty-title="No reservations"
        empty-description="Reserve a unit from Property inventory."
        @page-change="fetchList"
      >
        <template #cell-number="{ row }">
          <code class="text-xs">{{ row.reservation_number }}</code>
        </template>
        <template #cell-unit="{ row }">
          <span class="text-sm font-medium">{{ row.apartment?.unit_number || '—' }}</span>
        </template>
        <template #cell-buyer="{ row }">
          <span>{{ row.buyer?.full_name || '—' }}</span>
        </template>
        <template #cell-deposit="{ row }">
          <span class="tabular-nums">{{ formatMoney(row.deposit_amount, row.currency) }}</span>
        </template>
        <template #cell-expiry="{ row }">
          <span class="text-xs">{{ row.expiry_date || '—' }}</span>
        </template>
        <template #cell-status="{ row }">
          <StatusBadge :status="statusBadge(row.status)" :label="formatStatus(row.status)" />
        </template>
        <template #cell-actions="{ row }">
          <RowActionsMenu :actions="rowActions(row)" />
        </template>
      </DataTable>
    </template>
  </WorklistLayout>

  <SaleContractFormModal
    :open="contractOpen"
    :reservation="contractReservation"
    @close="closeContract"
    @saved="onContractSaved"
  />
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useSaleReservations } from '@/composables/useSaleReservations'
import { useSmartFilters } from '@/composables/useSmartFilters'
import { useConfirm } from '@/composables/useConfirm'
import { useToastStore } from '@/stores/toast'
import SaleContractFormModal from '@/components/forms/SaleContractFormModal.vue'
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
const { confirm } = useConfirm()
const toast = useToastStore()
const { items, loading, meta, filters, summary, fetchList, cancelReservation, resetFilters } = useSaleReservations()

const contractOpen = ref(false)
const contractReservation = ref(null)

const { filters: smartFilters, chips, clearAll, removeChip, bindRoute } = useSmartFilters({
  defaults: { search: '', status: '' },
  labels: {
    search: { label: 'Search' },
    status: { label: 'Status' },
  },
})

watch(smartFilters, () => {
  Object.assign(filters, { ...smartFilters })
}, { deep: true, immediate: true })

const columns = [
  { key: 'number', label: 'Reservation', mono: true },
  { key: 'unit', label: 'Unit' },
  { key: 'buyer', label: 'Buyer' },
  { key: 'deposit', label: 'Deposit', align: 'right' },
  { key: 'expiry', label: 'Expires' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: '', align: 'right', type: 'actions' },
]

function formatMoney(amount, currency = 'USD') {
  if (amount == null) return '—'
  return new Intl.NumberFormat(undefined, { style: 'currency', currency }).format(Number(amount))
}

function formatStatus(s) {
  if (!s) return '—'
  return String(s).replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}

function statusBadge(s) {
  const map = {
    pending_deposit: 'pending',
    confirmed: 'approved',
    expired: 'expired',
    cancelled: 'terminated',
  }
  return map[s] || 'inactive'
}

function rowActions(row) {
  const actions = []
  if (row.controls?.can_create_contract) {
    actions.push({
      label: 'Create contract',
      onClick: () => openContract(row),
    })
  }
  if (row.controls?.can_cancel) {
    actions.push({
      label: 'Cancel',
      variant: 'danger',
      onClick: () => onCancel(row),
    })
  }
  if (row.apartment?.id) {
    actions.push({
      label: 'View unit',
      onClick: () => router.push({ name: 'ApartmentShow', params: { id: row.apartment.id } }),
    })
  }
  return actions
}

function openContract(row) {
  contractReservation.value = row
  contractOpen.value = true
}

function closeContract() {
  contractOpen.value = false
  contractReservation.value = null
}

async function onContractSaved(contract) {
  toast.show('Sale contract created', 'success')
  closeContract()
  await fetchList(meta.value.current_page)
  if (contract?.id) {
    router.push({ name: 'SaleAgreementShow', params: { id: contract.id } })
  }
}

async function onCancel(row) {
  const ok = await confirm({
    title: 'Cancel reservation',
    message: `Release unit ${row.apartment?.unit_number || ''} and cancel ${row.reservation_number}?`,
    confirmLabel: 'Cancel reservation',
    variant: 'danger',
  })
  if (!ok) return
  await cancelReservation(row.id, 'Cancelled from reservations list')
  toast.show('Reservation cancelled', 'success')
  await fetchList(meta.value.current_page)
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

onMounted(() => {
  bindRoute(route, router, { debounceMs: 300 })
  fetchList()
})
</script>
