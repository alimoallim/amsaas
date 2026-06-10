<template>
  <WorklistLayout
    eyebrow="Accounting"
    title="Chart of accounts"
    :count="meta?.total"
    description="Company ledger accounts used for double-entry journal posting."
  >
    <template #actions>
      <ErpButton variant="ghost" size="sm" :loading="loading" @click="fetchList(meta?.current_page || 1)">
        Refresh
      </ErpButton>
      <ErpButton @click="formModal.openCreate()">Add account</ErpButton>
    </template>

    <template #filters>
      <AlertBanner v-if="serverError" class="mb-0" :message="serverError" @dismiss="serverError = ''" />
      <SmartFilterBar :chips="chips" @clear-all="onClearAll" @remove-chip="removeChip">
        <FormField label="Search" span="2">
          <input
            v-model="smartFilters.search"
            type="search"
            class="erp-input"
            placeholder="Name or code…"
            @input="debounceSearch"
          />
        </FormField>
        <FormField label="Type">
          <select v-model="smartFilters.type" class="erp-select" @change="syncAndFetch">
            <option value="">All types</option>
            <option value="asset">Asset</option>
            <option value="liability">Liability</option>
            <option value="equity">Equity</option>
            <option value="revenue">Revenue</option>
            <option value="expense">Expense</option>
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
        empty-title="No accounts"
        empty-description="Default accounts are seeded on company setup. Add custom accounts as needed."
        @page-change="changePage"
        @row-click="onRowClick"
      >
        <template #emptyAction>
          <ErpButton @click="formModal.openCreate()">Add account</ErpButton>
        </template>

        <template #cell-code="{ row }">
          <code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ row.code }}</code>
        </template>

        <template #cell-name="{ row }">
          <span class="font-medium text-slate-900 dark:text-slate-100">{{ row.name }}</span>
          <span v-if="row.is_system" class="ml-2 text-xs text-slate-400">System</span>
        </template>

        <template #cell-type="{ row }">
          <StatusBadge :status="typeTone(row.type)" :label="typeLabel(row.type)" :dot="false" />
        </template>

        <template #cell-status="{ row }">
          <StatusBadge :status="row.status === 'active' ? 'active' : 'inactive'" :label="row.status" />
        </template>

        <template #cell-actions="{ row }">
          <RowActionsMenu :actions="accountActions(row)" />
        </template>
      </DataTable>
    </template>
  </WorklistLayout>

  <AccountFormModal
    :open="formModal.state.open"
    :entity-id="formModal.state.id"
    @close="formModal.close()"
    @saved="fetchList(meta?.current_page || 1)"
  />
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAccounts } from '@/composables/useAccounts'
import { useSmartFilters } from '@/composables/useSmartFilters'
import { useFormModal } from '@/composables/useFormModal'
import { useConfirm } from '@/composables/useConfirm'
import { compactActions, editAction, deleteAction } from '@/composables/useTableActions'
import AccountFormModal from '@/components/forms/AccountFormModal.vue'
import {
  WorklistLayout,
  ErpButton,
  DataTable,
  RowActionsMenu,
  SmartFilterBar,
  FormField,
  StatusBadge,
  AlertBanner,
} from '@/components/erp'

const route = useRoute()
const router = useRouter()
const formModal = useFormModal()
const { confirm } = useConfirm()
const serverError = ref('')

const {
  items,
  loading,
  meta,
  filters,
  fetchList,
  remove,
  typeLabel,
  typeTone,
} = useAccounts()

const { filters: smartFilters, chips, clearAll, removeChip, bindRoute } = useSmartFilters({
  defaults: { search: '', type: '' },
  labels: {
    search: { label: 'Search' },
    type: {
      label: 'Type',
      format: (v) => typeLabel(v),
    },
  },
})

watch(
  smartFilters,
  () => {
    filters.search = smartFilters.search
    filters.type = smartFilters.type
  },
  { deep: true, immediate: true },
)

const columns = [
  { key: 'code', label: 'Code', mono: true },
  { key: 'name', label: 'Name', emphasis: true },
  { key: 'type', label: 'Type' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: '', align: 'right', type: 'actions' },
]

function accountActions(row) {
  return compactActions([
    {
      key: 'ledger',
      label: 'View ledger',
      onClick: () => router.push({ name: 'GeneralLedger', query: { account_id: row.id } }),
    },
    editAction(() => formModal.openEdit(row.id)),
    row.controls?.can_delete && deleteAction(() => onDelete(row)),
  ])
}

function onRowClick(row) {
  formModal.openEdit(row.id)
}

async function onDelete(row) {
  const ok = await confirm({
    title: 'Delete account?',
    message: `${row.code} — ${row.name} will be removed from the chart.`,
    confirmLabel: 'Delete',
    variant: 'danger',
  })
  if (!ok) return
  try {
    await remove(row.id)
    await fetchList(meta.value?.current_page || 1)
  } catch (e) {
    serverError.value = e.response?.data?.message || 'Could not delete account.'
  }
}

let searchDebounceTimer = null

function debounceSearch() {
  clearTimeout(searchDebounceTimer)
  searchDebounceTimer = setTimeout(() => fetchList(1), 350)
}

function syncAndFetch() {
  filters.search = smartFilters.search
  filters.type = smartFilters.type
  fetchList(1)
}

function changePage(page) {
  if (page >= 1 && page <= meta.value.last_page) fetchList(page)
}

function onClearAll() {
  clearAll()
  syncAndFetch()
}

onMounted(() => {
  bindRoute(route, router, { debounceMs: 300 })
  formModal.syncFromRoute(route, router)
  fetchList()
})
</script>
