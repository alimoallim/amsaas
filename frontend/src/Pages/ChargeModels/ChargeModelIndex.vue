<template>
  <WorklistLayout
    eyebrow="Finance"
    title="Charge models"
    :count="tableMeta.total"
    description="Pricing rules for utilities, rent components, and recurring fees."
  >
    <template #actions>
      <ErpButton @click="formModal.openCreate()">Add charge model</ErpButton>
    </template>

    <template #filters>
      <SmartFilterBar :chips="chips" @clear-all="onClearAll" @remove-chip="removeChip">
        <FormField label="Search" span="2">
          <input v-model="smartFilters.search" type="search" class="erp-input" placeholder="Name or code…" @keydown.enter="syncAndFetch" />
        </FormField>
        <FormField label="Policy">
          <select v-model="smartFilters.pricing_strategy" class="erp-select" @change="syncAndFetch">
            <option value="">All policies</option>
            <option value="agreement_rent">Rent from agreement</option>
            <option value="metered">Metered utility</option>
            <option value="flat_fee">Flat service fee</option>
            <option value="tiered">Tiered</option>
            <option value="percentage">Percentage</option>
            <option value="fixed">Fixed (legacy)</option>
          </select>
        </FormField>
        <FormField label="Status">
          <select v-model="smartFilters.status" class="erp-select" @change="syncAndFetch">
            <option value="">All statuses</option>
            <option value="draft">Draft</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="archived">Archived</option>
          </select>
        </FormField>
      </SmartFilterBar>
    </template>

    <template #table>
      <DataTable
        :columns="columns"
        :rows="rows"
        :loading="loading"
        :meta="tableMeta"
        empty-title="No charge models"
        empty-description="Configure pricing models linked to charge types and meters."
        @page-change="fetchRows"
        @row-click="(row) => $router.push({ name: 'ChargeModelShow', params: { id: row.id } })"
      >
        <template #emptyAction>
          <ErpButton @click="formModal.openCreate()">Create charge model</ErpButton>
        </template>
        <template #cell-pricing_strategy="{ row }">
          <span class="text-sm text-slate-700">{{ pricingPolicyLabel(row.pricing_strategy) }}</span>
        </template>
        <template #cell-status="{ row }">
          <StatusBadge :status="row.status" :label="row.status" />
        </template>
        <template #cell-currency="{ row }">
          <span class="font-mono text-xs">{{ row.currency || '—' }}</span>
        </template>
        <template #cell-effective="{ row }">
          <span class="text-xs text-slate-600">{{ row.effective_from }} → {{ row.effective_to || 'Open' }}</span>
        </template>
        <template #cell-actions="{ row }">
          <RowActionsMenu :actions="chargeModelActions(row)" />
        </template>
      </DataTable>
    </template>
  </WorklistLayout>

  <ChargeModelFormModal
    :open="formModal.state.open"
    :entity-id="formModal.state.id"
    @close="formModal.close()"
    @saved="onSaved"
  />
</template>

<script setup>
import { computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useChargeModels } from '@/composables/useChargeModels'
import { useSmartFilters } from '@/composables/useSmartFilters'
import { useFormModal } from '@/composables/useFormModal'
import { pricingPolicyLabel } from '@/utils/chargeModelForm'
import api from '@/services/api'
import { compactActions, viewAction, editAction } from '@/composables/useTableActions'
import ChargeModelFormModal from '@/components/forms/ChargeModelFormModal.vue'
import {
  WorklistLayout,
  SmartFilterBar,
  DataTable,
  RowActionsMenu,
  FormField,
  ErpButton,
  StatusBadge,
} from '@/components/erp'

const route = useRoute()
const router = useRouter()
const formModal = useFormModal()
const { items: rows, loading, meta, filters, fetchList: fetchRows, resetFilters } = useChargeModels()

const { filters: smartFilters, chips, clearAll, removeChip, bindRoute } = useSmartFilters({
  defaults: { search: '', pricing_strategy: '', status: '' },
  labels: {
    search: { label: 'Search' },
    pricing_strategy: { label: 'Strategy' },
    status: { label: 'Status' },
  },
})

watch(smartFilters, () => Object.assign(filters, { ...smartFilters }), { deep: true, immediate: true })

const columns = [
  { key: 'code', label: 'Code', mono: true },
  { key: 'name', label: 'Name', emphasis: true },
  { key: 'pricing_strategy', label: 'Strategy' },
  { key: 'currency', label: 'Curr', mono: true },
  { key: 'status', label: 'Status' },
  { key: 'effective', label: 'Effective period' },
  { key: 'actions', label: '', align: 'right', type: 'actions' },
]

async function cloneModel(row) {
  await api.post(`/charge-models/${row.id}/clone`)
  await fetchRows(meta.value.current_page)
}

function chargeModelActions(row) {
  return compactActions([
    viewAction('ChargeModelShow', row.id),
    row.controls?.can_edit !== false && editAction(() => formModal.openEdit(row.id)),
    row.controls?.can_clone !== false && {
      key: 'clone',
      label: 'Clone',
      onClick: () => cloneModel(row),
    },
  ])
}

const tableMeta = computed(() => ({
  current_page: meta.value.current_page,
  last_page: meta.value.last_page,
  total: meta.value.total ?? 0,
  from: meta.value.from ?? 0,
  to: meta.value.to ?? 0,
  per_page: 15,
}))

function syncAndFetch() {
  Object.assign(filters, { ...smartFilters })
  fetchRows(1)
}
function onClearAll() {
  clearAll()
  resetFilters()
  syncAndFetch()
}

async function onSaved() {
  await fetchRows(meta.value.current_page)
}

onMounted(() => {
  bindRoute(route, router, { debounceMs: 300 })
  formModal.syncFromRoute(route, router)
  fetchRows()
})
</script>
