<template>
  <WorklistLayout
    eyebrow="Sales"
    title="Sale contracts"
    :count="meta.total"
    description="Binding sale agreements from reservation through ownership."
  >
    <template #actions>
      <ErpButton variant="secondary" :to="{ name: 'SaleReservations' }">Reservations</ErpButton>
    </template>

    <template #kpis>
      <KpiStrip class="grid-cols-2 lg:grid-cols-4">
        <KpiCard label="Total" :value="summary.total" />
        <KpiCard label="Active" :value="summary.active" />
        <KpiCard label="Draft" :value="summary.draft" />
        <KpiCard
          label="Active contract value"
          :value="formatMoney(summary.contractValue)"
          variant="accent"
        />
      </KpiStrip>
    </template>

    <template #filters>
      <SmartFilterBar :chips="chips" @clear-all="onClearAll" @remove-chip="removeChip">
        <FormField label="Search" span="2">
          <input
            v-model="smartFilters.search"
            type="search"
            class="erp-input"
            placeholder="Contract #, buyer, unit…"
            @input="debounceFetch"
          />
        </FormField>
        <FormField label="Status">
          <select v-model="smartFilters.status" class="erp-select" @change="syncAndFetch">
            <option value="">All</option>
            <option value="draft">Draft</option>
            <option value="active">Active</option>
            <option value="completed">Completed</option>
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
        empty-title="No sale contracts"
        empty-description="Convert a confirmed reservation to create a contract."
        @row-click="(row) => $router.push({ name: 'SaleAgreementShow', params: { id: row.id } })"
        @page-change="fetchList"
      >
        <template #emptyAction>
          <ErpButton :to="{ name: 'SaleReservations' }">View reservations</ErpButton>
        </template>
        <template #cell-agreement_number="{ row }">
          <code class="text-xs font-medium">{{ row.agreement_number }}</code>
        </template>
        <template #cell-buyer="{ row }">
          <span class="font-medium">{{ row.buyer_name }}</span>
        </template>
        <template #cell-unit="{ row }">
          {{ row.building_name }} · {{ row.unit_number }}
        </template>
        <template #cell-price="{ row }">
          <span class="tabular-nums">{{ formatMoney(row.sale_price, row.currency) }}</span>
        </template>
        <template #cell-type="{ row }">
          <span class="text-xs">{{ row.is_installment_sale ? 'Instalment' : 'Cash' }}</span>
        </template>
        <template #cell-status="{ row }">
          <StatusBadge :status="row.status" :label="row.status_label" />
        </template>
        <template #cell-actions="{ row }">
          <RowActionsMenu :actions="rowActions(row)" />
        </template>
      </DataTable>
    </template>
  </WorklistLayout>
</template>

<script setup>
import { watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useSaleAgreements } from '@/composables/useSaleAgreements'
import { useSmartFilters } from '@/composables/useSmartFilters'
import { viewAction } from '@/composables/useTableActions'
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
const { items, loading, meta, filters, summary, fetchList, resetFilters } = useSaleAgreements()

const { filters: smartFilters, chips, clearAll, removeChip, bindRoute } = useSmartFilters({
  defaults: { search: '', status: '' },
  labels: { search: { label: 'Search' }, status: { label: 'Status' } },
})

watch(smartFilters, () => Object.assign(filters, { ...smartFilters }), { deep: true, immediate: true })

const columns = [
  { key: 'agreement_number', label: 'Contract', mono: true },
  { key: 'buyer', label: 'Buyer', emphasis: true },
  { key: 'unit', label: 'Unit' },
  { key: 'price', label: 'Sale price', align: 'right' },
  { key: 'type', label: 'Type' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: '', align: 'right', type: 'actions' },
]

function formatMoney(amount, currency = 'USD') {
  if (amount == null) return '—'
  return new Intl.NumberFormat(undefined, { style: 'currency', currency }).format(Number(amount))
}

function rowActions(row) {
  return [viewAction('SaleAgreementShow', row.id)]
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
