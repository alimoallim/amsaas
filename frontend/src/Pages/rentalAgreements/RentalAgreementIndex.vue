<template>
  <WorklistLayout
    eyebrow="Operations"
    title="Rental agreements"
    :count="meta.total"
    description="Lease lifecycle, occupancy, and contract operations."
  >
    <template #actions>
      <ErpButton variant="ghost" size="sm" :loading="loading" @click="fetchList">Refresh</ErpButton>
      <ErpButton @click="formModal.openCreate()">Create agreement</ErpButton>
    </template>

    <template #kpis>
      <KpiStrip class="grid-cols-2 lg:grid-cols-4">
        <KpiCard label="Total" :value="summary.total" />
        <KpiCard label="Active" :value="summary.active" />
        <KpiCard label="Draft" :value="summary.draft" />
        <KpiCard
          label="Active rent / mo"
          :value="formatMoney(summary.monthlyRevenue)"
          variant="accent"
        />
      </KpiStrip>
    </template>

    <template #filters>
      <SmartFilterBar :chips="chips" @clear-all="onClearAll" @remove-chip="removeChip">
        <FormField label="Search" span="2">
          <input v-model="smartFilters.search" type="search" class="erp-input" placeholder="Agreement, tenant, unit…" />
        </FormField>
        <FormField label="Status">
          <select v-model="smartFilters.status" class="erp-select">
            <option value="">All</option>
            <option value="active">Active</option>
            <option value="draft">Draft</option>
            <option value="approved">Approved</option>
            <option value="terminated">Terminated</option>
            <option value="expired">Expired</option>
          </select>
        </FormField>
        <FormField label="Building">
          <input v-model="smartFilters.building" type="text" class="erp-input" placeholder="Building name…" />
        </FormField>
      </SmartFilterBar>
    </template>

    <template #table>
      <DataTable
        :columns="columns"
        :rows="filteredItems"
        :loading="loading"
        :meta="meta"
        empty-title="No agreements"
        empty-description="Create a rental agreement or adjust filters."
        @row-click="(row) => $router.push({ name: 'RentalAgreementShow', params: { id: row.id } })"
      >
        <template #emptyAction>
          <ErpButton @click="formModal.openCreate()">Create agreement</ErpButton>
        </template>
        <template #cell-agreement_number="{ row }">
          <code class="text-xs font-medium">{{ row.agreement_number }}</code>
        </template>
        <template #cell-tenant="{ row }">
          <span class="font-medium">{{ row.tenant_name }}</span>
        </template>
        <template #cell-unit="{ row }">
          {{ row.building_name }} · {{ row.unit_number }}
        </template>
        <template #cell-rent="{ row }">
          <span class="tabular-nums">{{ formatMoney(row.monthly_rent) }}</span>
        </template>
        <template #cell-period="{ row }">
          <span class="text-xs text-slate-600">{{ row.start_date }} → {{ row.end_date || 'Open' }}</span>
        </template>
        <template #cell-status="{ row }">
          <StatusBadge :status="row.status" :label="row.status_label" />
        </template>
        <template #cell-actions="{ row }">
          <RowActionsMenu :actions="agreementActions(row)" />
        </template>
      </DataTable>
    </template>
  </WorklistLayout>

  <RentalAgreementFormModal
    :open="formModal.state.open"
    :entity-id="formModal.state.id"
    @close="formModal.close()"
    @saved="onSaved"
  />

  <ErpModal
    :open="confirm.open"
    :title="confirm.title"
    :subtitle="confirm.subtitle"
    :confirm-label="confirm.confirmLabel"
    :confirm-variant="confirm.variant"
    :loading="confirm.loading"
    @close="confirm.open = false"
    @confirm="runConfirm"
  >
    <FormField
      v-if="confirm.action === 'terminate'"
      label="Termination reason"
      required
      class="mt-2"
    >
      <textarea
        v-model="confirm.terminationReason"
        class="erp-input min-h-[88px]"
        placeholder="Required — e.g. lease ended, tenant vacated…"
      />
    </FormField>
  </ErpModal>
</template>

<script setup>
import { reactive, ref, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useRentalAgreements } from '@/composables/useRentalAgreements'
import { useSmartFilters } from '@/composables/useSmartFilters'
import { useFormModal } from '@/composables/useFormModal'
import { compactActions, viewAction, editAction, deleteAction } from '@/composables/useTableActions'
import RentalAgreementFormModal from '@/components/forms/RentalAgreementFormModal.vue'
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
  ErpModal,
} from '@/components/erp'

const route = useRoute()
const router = useRouter()
const formModal = useFormModal()
const actionLoading = ref(null)

const {
  filteredItems,
  loading,
  meta,
  filters,
  summary,
  fetchList,
  terminate,
  approve,
  activate,
  remove,
  resetFilters,
} = useRentalAgreements()

const { filters: smartFilters, chips, clearAll, removeChip } = useSmartFilters({
  defaults: { search: '', status: '', building: '' },
  labels: { search: { label: 'Search' }, status: { label: 'Status' }, building: { label: 'Building' } },
})

watch(smartFilters, () => Object.assign(filters, { ...smartFilters }), { deep: true, immediate: true })

const columns = [
  { key: 'agreement_number', label: 'Agreement', mono: true },
  { key: 'tenant', label: 'Tenant', emphasis: true },
  { key: 'unit', label: 'Location' },
  { key: 'rent', label: 'Rent/mo', align: 'right' },
  { key: 'period', label: 'Period' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: '', align: 'right', type: 'actions' },
]

function agreementActions(row) {
  const busy = actionLoading.value === row.id
  const c = row.controls || {}
  return compactActions([
    viewAction('RentalAgreementShow', row.id),
    c.can_edit && editAction(() => formModal.openEdit(row.id)),
    c.can_approve && {
      key: 'approve',
      label: 'Approve',
      variant: 'primary',
      disabled: busy,
      onClick: () => onApprove(row),
    },
    c.can_activate && {
      key: 'activate',
      label: 'Activate',
      variant: 'success',
      disabled: busy,
      onClick: () => onActivate(row),
    },
    c.can_terminate && {
      key: 'terminate',
      label: 'End lease',
      variant: 'warning',
      onClick: () => promptTerminate(row),
    },
    c.can_delete && deleteAction(() => promptDelete(row)),
  ])
}

const confirm = reactive({
  open: false,
  loading: false,
  action: null,
  row: null,
  title: '',
  subtitle: '',
  confirmLabel: '',
  variant: 'primary',
  terminationReason: '',
})

function promptTerminate(row) {
  confirm.row = row
  confirm.action = 'terminate'
  confirm.terminationReason = ''
  confirm.title = 'Terminate agreement?'
  confirm.subtitle = `${row.agreement_number} will be ended and the unit released.`
  confirm.confirmLabel = 'Terminate'
  confirm.variant = 'danger'
  confirm.open = true
}

function promptDelete(row) {
  confirm.row = row
  confirm.action = 'delete'
  confirm.title = 'Delete draft?'
  confirm.subtitle = `${row.agreement_number} will be permanently removed.`
  confirm.confirmLabel = 'Delete'
  confirm.variant = 'danger'
  confirm.open = true
}

async function onApprove(row) {
  actionLoading.value = row.id
  try {
    await approve(row)
  } finally {
    actionLoading.value = null
  }
}

async function onActivate(row) {
  actionLoading.value = row.id
  try {
    await activate(row)
  } finally {
    actionLoading.value = null
  }
}

async function runConfirm() {
  if (confirm.action === 'terminate' && !confirm.terminationReason.trim()) {
    return
  }
  confirm.loading = true
  try {
    if (confirm.action === 'terminate') {
      await terminate(confirm.row, confirm.terminationReason.trim())
    } else {
      await remove(confirm.row)
    }
    confirm.open = false
  } finally {
    confirm.loading = false
  }
}

function onClearAll() {
  clearAll()
  resetFilters()
}

function formatMoney(v) {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number(v) || 0)
}

async function onSaved() {
  await fetchList()
}

onMounted(() => {
  formModal.syncFromRoute(route, router)
  fetchList()
})
</script>
