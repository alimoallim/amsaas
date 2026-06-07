<template>
  <WorklistLayout
    eyebrow="Finance"
    title="Charge types"
    :count="meta?.total"
    description="Define billing categories, utility codes, and ledger mappings used across agreements and invoices."
  >
    <template #actions>
      <ErpButton @click="formModal.openCreate()">Add charge type</ErpButton>
    </template>

    <template #filters>
      <AlertBanner
        v-if="serverError"
        class="mb-0"
        :message="serverError"
        @dismiss="serverError = ''"
      />
      <SmartFilterBar
        :chips="chips"
        @clear-all="onClearAll"
        @remove-chip="removeChip"
      >
        <FormField label="Search" span="2">
          <input
            v-model="smartFilters.search"
            type="search"
            class="erp-input"
            placeholder="Name or code…"
            @input="debounceSearch"
          />
        </FormField>
        <FormField label="Category">
          <select
            v-model="smartFilters.category"
            class="erp-select"
            @change="syncAndFetch"
          >
            <option value="">All categories</option>
            <option value="UTILITY">Utility</option>
            <option value="FEE">Fee / surcharge</option>
            <option value="DEPOSIT">Deposit</option>
            <option value="OTHER">Other</option>
          </select>
        </FormField>
      </SmartFilterBar>
    </template>

    <template #table>
      <DataTable
        :columns="columns"
        :rows="chargeTypes"
        :loading="loading"
        :meta="meta"
        empty-title="No charge types"
        empty-description="Create your first charge type to start configuring billing."
        @page-change="changePage"
        @row-click="onRowClick"
      >
        <template #emptyAction>
          <ErpButton @click="formModal.openCreate()">Add charge type</ErpButton>
        </template>

        <template #cell-name="{ row }">
          <span class="font-medium text-slate-900">{{ row.name }}</span>
        </template>

        <template #cell-code="{ row }">
          <code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-700">{{ row.code }}</code>
        </template>

        <template #cell-category="{ row }">
          <StatusBadge :status="categoryTone(row.category)" :label="formatCategory(row.category)" :dot="false" />
        </template>

        <template #cell-billing="{ row }">
          <span class="text-xs text-slate-600 capitalize">{{ row.billing_frequency || row.calculation_method || '—' }}</span>
        </template>

        <template #cell-ledger="{ row }">
          <code v-if="row.ledger_account_code" class="text-xs">{{ row.ledger_account_code }}</code>
          <span v-else class="text-xs text-slate-400">—</span>
        </template>

        <template #cell-status="{ row }">
          <button
            type="button"
            class="rounded-full focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
            @click.stop="openStatusModal(row)"
          >
            <StatusBadge
              :status="isChargeTypeActive(row) ? 'active' : 'inactive'"
              :label="isChargeTypeActive(row) ? 'Active' : 'Inactive'"
            />
          </button>
        </template>

        <template #cell-actions="{ row }">
          <RowActionsMenu :actions="chargeTypeActions(row)" />
        </template>
      </DataTable>
    </template>
  </WorklistLayout>

  <ErpModal
    :open="statusModal.open"
    :title="statusModal.title"
    :subtitle="statusModal.subtitle"
    :confirm-label="statusModal.confirmLabel"
    :confirm-variant="statusModal.variant"
    :loading="statusModal.loading"
    @close="statusModal.open = false"
    @confirm="confirmStatusChange"
  />

  <ChargeTypeFormModal
    :open="formModal.state.open"
    :entity-id="formModal.state.id"
    @close="formModal.close()"
    @saved="fetchChargeTypes(meta?.current_page || 1)"
  />
</template>

<script setup>
import { reactive, ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useChargeTypes } from '@/composables/useChargeTypes'
import { useSmartFilters } from '@/composables/useSmartFilters'
import { useFormModal } from '@/composables/useFormModal'
import { compactActions, editAction } from '@/composables/useTableActions'
import ChargeTypeFormModal from '@/components/forms/ChargeTypeFormModal.vue'
import {
  WorklistLayout,
  ErpButton,
  DataTable,
  RowActionsMenu,
  SmartFilterBar,
  FormField,
  StatusBadge,
  AlertBanner,
  ErpModal,
} from '@/components/erp'

const route = useRoute()
const router = useRouter()
const formModal = useFormModal()
const serverError = ref('')

const {
  items: chargeTypes,
  loading,
  meta,
  filters,
  fetchList: fetchChargeTypes,
  updateStatus,
  isActive: isChargeTypeActive,
} = useChargeTypes()

const { filters: smartFilters, chips, clearAll, removeChip, bindRoute } = useSmartFilters({
  defaults: { search: '', category: '' },
  labels: {
    search: { label: 'Search' },
    category: {
      label: 'Category',
      format: (v) => (v === 'FEE' ? 'Fee' : v.charAt(0) + v.slice(1).toLowerCase()),
    },
  },
})

watch(
  smartFilters,
  () => {
    filters.search = smartFilters.search
    filters.category = smartFilters.category
  },
  { deep: true, immediate: true }
)

const columns = [
  { key: 'name', label: 'Name', emphasis: true },
  { key: 'code', label: 'Code', mono: true },
  { key: 'category', label: 'Category' },
  { key: 'billing', label: 'Billing' },
  { key: 'ledger', label: 'GL', mono: true },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: '', align: 'right', type: 'actions' },
]

function chargeTypeActions(row) {
  const active = isChargeTypeActive(row)
  return compactActions([
    row.controls?.can_edit !== false && editAction(() => formModal.openEdit(row.id)),
    {
      key: 'toggle-status',
      label: active ? 'Deactivate' : 'Activate',
      variant: active ? 'warning' : 'success',
      onClick: () => openStatusModal(row),
    },
  ])
}

const statusModal = reactive({
  open: false,
  loading: false,
  row: null,
  targetStatus: '',
  title: '',
  subtitle: '',
  confirmLabel: '',
  variant: 'primary',
})

let searchDebounceTimer = null

function onRowClick(row) {
  if (row.controls?.can_edit !== false) {
    formModal.openEdit(row.id)
  }
}

function openStatusModal(type) {
  const activating = !isChargeTypeActive(type)
  statusModal.row = type
  statusModal.targetStatus = activating ? 'active' : 'inactive'
  statusModal.title = activating ? 'Activate charge type?' : 'Deactivate charge type?'
  statusModal.subtitle = activating
    ? `${type.name} will be available for new billing configurations.`
    : `${type.name} will no longer be selectable for new charges.`
  statusModal.confirmLabel = activating ? 'Activate' : 'Deactivate'
  statusModal.variant = activating ? 'primary' : 'danger'
  statusModal.open = true
}

async function confirmStatusChange() {
  if (!statusModal.row) return
  statusModal.loading = true
  try {
    await updateStatus(statusModal.row, statusModal.targetStatus)
    statusModal.open = false
  } catch {
    serverError.value = 'Failed to update status.'
  } finally {
    statusModal.loading = false
  }
}

function debounceSearch() {
  clearTimeout(searchDebounceTimer)
  searchDebounceTimer = setTimeout(() => fetchChargeTypes(1), 350)
}

function syncAndFetch() {
  filters.search = smartFilters.search
  filters.category = smartFilters.category
  fetchChargeTypes(1)
}

function changePage(page) {
  if (page >= 1 && page <= meta.value.last_page) fetchChargeTypes(page)
}

function onClearAll() {
  clearAll()
  syncAndFetch()
}

function categoryTone(category) {
  const map = { UTILITY: 'pending', FEE: 'draft', DEPOSIT: 'pending', OTHER: 'inactive' }
  return map[category] || 'inactive'
}

function formatCategory(category) {
  if (!category) return '—'
  if (category === 'FEE') return 'Fee'
  return category.charAt(0) + category.slice(1).toLowerCase()
}

onMounted(() => {
  bindRoute(route, router, { debounceMs: 300 })
  formModal.syncFromRoute(route, router)
  fetchChargeTypes()
})
</script>
