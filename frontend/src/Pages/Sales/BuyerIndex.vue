<template>
  <WorklistLayout
    eyebrow="Sales"
    title="Buyers"
    :count="meta.total"
    description="Prospective and active property buyers — separate from tenant records."
  >
    <template #actions>
      <ErpButton @click="formModal.openCreate()">New buyer</ErpButton>
    </template>

    <template #kpis>
      <KpiStrip>
        <KpiCard label="Total" :value="summary.total" />
        <KpiCard label="Active" :value="summary.active" />
        <KpiCard label="Linked to tenant" :value="summary.linked" />
      </KpiStrip>
    </template>

    <template #filters>
      <SmartFilterBar :chips="chips" @clear-all="onClearAll" @remove-chip="removeChip">
        <FormField label="Search" span="2">
          <input
            v-model="smartFilters.search"
            type="search"
            class="erp-input"
            placeholder="Name, code, email…"
            @input="debounceFetch"
          />
        </FormField>
        <FormField label="Status">
          <select v-model="smartFilters.is_active" class="erp-select" @change="syncAndFetch">
            <option value="">All</option>
            <option value="true">Active</option>
            <option value="false">Inactive</option>
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
        empty-title="No buyers"
        empty-description="Register a buyer to start the sales pipeline."
        @page-change="fetchList"
        @row-click="(row) => formModal.openEdit(row.id)"
      >
        <template #emptyAction>
          <ErpButton @click="formModal.openCreate()">New buyer</ErpButton>
        </template>
        <template #cell-name="{ row }">
          <span class="font-medium">{{ row.full_name || '—' }}</span>
        </template>
        <template #cell-code="{ row }">
          <code class="text-xs">{{ row.buyer_code || '—' }}</code>
        </template>
        <template #cell-contact="{ row }">
          <div class="min-w-0 text-xs">
            <p v-if="row.email" class="truncate text-slate-700 dark:text-slate-300">{{ row.email }}</p>
            <p v-if="row.phone" class="text-slate-500 dark:text-slate-400">{{ row.phone }}</p>
            <span v-if="!row.email && !row.phone">—</span>
          </div>
        </template>
        <template #cell-location="{ row }">
          <span class="text-xs text-slate-600 dark:text-slate-400">
            {{ [row.address?.city, row.address?.country].filter(Boolean).join(', ') || '—' }}
          </span>
        </template>
        <template #cell-tenant="{ row }">
          <span v-if="row.tenant?.display_name" class="text-xs">{{ row.tenant.display_name }}</span>
          <span v-else class="text-xs text-slate-400">—</span>
        </template>
        <template #cell-status="{ row }">
          <StatusBadge :status="row.is_active ? 'active' : 'inactive'" :label="row.is_active ? 'Active' : 'Inactive'" />
        </template>
        <template #cell-actions="{ row }">
          <RowActionsMenu :actions="buyerActions(row)" />
        </template>
      </DataTable>
    </template>
  </WorklistLayout>

  <BuyerFormModal
    :open="formModal.state.open"
    :entity-id="formModal.state.id"
    @close="formModal.close()"
    @saved="onSaved"
  />
</template>

<script setup>
import { watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useBuyers } from '@/composables/useBuyers'
import { useSmartFilters } from '@/composables/useSmartFilters'
import { useFormModal } from '@/composables/useFormModal'
import { compactActions, editAction } from '@/composables/useTableActions'
import BuyerFormModal from '@/components/forms/BuyerFormModal.vue'
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
const { items, loading, meta, filters, summary, fetchList, resetFilters } = useBuyers()

const { filters: smartFilters, chips, clearAll, removeChip, bindRoute } = useSmartFilters({
  defaults: { search: '', is_active: '' },
  labels: {
    search: { label: 'Search' },
    is_active: { label: 'Status', format: (v) => (v === 'true' ? 'Active' : 'Inactive') },
  },
})

watch(smartFilters, () => {
  Object.assign(filters, { ...smartFilters })
}, { deep: true, immediate: true })

const columns = [
  { key: 'name', label: 'Buyer', emphasis: true },
  { key: 'code', label: 'Code', mono: true },
  { key: 'contact', label: 'Contact' },
  { key: 'location', label: 'Location' },
  { key: 'tenant', label: 'Tenant link' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: '', align: 'right', type: 'actions' },
]

function buyerActions(row) {
  return compactActions([
    editAction(() => formModal.openEdit(row.id), 'Edit'),
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

async function onSaved() {
  await fetchList(meta.value.current_page)
}

onMounted(() => {
  bindRoute(route, router, { debounceMs: 300 })
  formModal.syncFromRoute(route, router)
  fetchList()
})
</script>
