<template>
  <WorklistLayout
    eyebrow="Operations"
    title="Tenants"
    :count="meta.total"
    description="Tenant records, contact details, and operational status."
  >
    <template #actions>
      <ErpButton @click="formModal.openCreate()">New tenant</ErpButton>
    </template>

    <template #kpis>
      <KpiStrip>
        <KpiCard label="Total" :value="summary.total" />
        <KpiCard label="Active" :value="summary.active" />
        <KpiCard label="Pending" :value="summary.pending" />
        <KpiCard label="Blacklisted" :value="summary.blacklisted" />
      </KpiStrip>
    </template>

    <template #filters>
      <SmartFilterBar :chips="chips" @clear-all="onClearAll" @remove-chip="removeChip">
        <FormField label="Search" span="2">
          <input v-model="smartFilters.search" type="search" class="erp-input" placeholder="Name, code, email…" @input="debounceFetch" />
        </FormField>
        <FormField label="Status">
          <select v-model="smartFilters.status" class="erp-select" @change="syncAndFetch">
            <option value="">All</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="pending">Pending</option>
            <option value="blacklisted">Blacklisted</option>
          </select>
        </FormField>
        <FormField label="Type">
          <select v-model="smartFilters.tenant_type" class="erp-select" @change="syncAndFetch">
            <option value="">All types</option>
            <option value="individual">Individual</option>
            <option value="company">Company</option>
            <option value="government">Government</option>
            <option value="ngo">NGO</option>
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
        empty-title="No tenants"
        empty-description="Create a tenant or adjust filters."
        @page-change="fetchList"
        @row-click="(row) => formModal.openEdit(row.id)"
      >
        <template #emptyAction>
          <ErpButton @click="formModal.openCreate()">New tenant</ErpButton>
        </template>
        <template #cell-name="{ row }">
          <span class="font-medium">{{ tenantDisplayName(row) || '—' }}</span>
        </template>
        <template #cell-code="{ row }">
          <code class="text-xs">{{ row.tenant_code || '—' }}</code>
        </template>
        <template #cell-type="{ row }">
          <span class="capitalize">{{ row.tenant_type?.value || row.tenant_type || '—' }}</span>
        </template>
        <template #cell-contact="{ row }">
          <div class="min-w-0 text-xs">
            <p v-if="tenantEmail(row)" class="truncate text-slate-700" :title="tenantEmail(row)">{{ tenantEmail(row) }}</p>
            <p v-if="tenantPhone(row)" class="text-slate-500">{{ tenantPhone(row) }}</p>
            <span v-if="!tenantEmail(row) && !tenantPhone(row)">—</span>
          </div>
        </template>
        <template #cell-location="{ row }">
          <span class="text-xs text-slate-600">
            {{ [row.address?.city, row.address?.country].filter(Boolean).join(', ') || '—' }}
          </span>
        </template>
        <template #cell-status="{ row }">
          <StatusBadge :status="tenantStatus(row)" :label="formatStatus(tenantStatus(row))" />
        </template>
        <template #cell-actions="{ row }">
          <RowActionsMenu :actions="tenantActions(row)" />
        </template>
      </DataTable>
    </template>
  </WorklistLayout>

  <TenantFormModal
    :open="formModal.state.open"
    :entity-id="formModal.state.id"
    @close="formModal.close()"
    @saved="onSaved"
  />
</template>

<script setup>
import { watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useTenants } from '@/composables/useTenants'
import { useSmartFilters } from '@/composables/useSmartFilters'
import { useFormModal } from '@/composables/useFormModal'
import { compactActions, editAction } from '@/composables/useTableActions'
import TenantFormModal from '@/components/forms/TenantFormModal.vue'
import { tenantDisplayName } from '@/utils/tenantDisplayName'
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
const { items, loading, meta, filters, summary, fetchList, resetFilters } = useTenants()

const { filters: smartFilters, chips, clearAll, removeChip, bindRoute } = useSmartFilters({
  defaults: { search: '', status: '', tenant_type: '' },
  labels: {
    search: { label: 'Search' },
    status: { label: 'Status' },
    tenant_type: { label: 'Type' },
  },
})

watch(smartFilters, () => {
  Object.assign(filters, { ...smartFilters })
}, { deep: true, immediate: true })

const columns = [
  { key: 'name', label: 'Tenant', emphasis: true },
  { key: 'code', label: 'Code', mono: true },
  { key: 'type', label: 'Type' },
  { key: 'contact', label: 'Contact' },
  { key: 'location', label: 'Location' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: '', align: 'right', type: 'actions' },
]

function tenantActions(row) {
  return compactActions([editAction(() => formModal.openEdit(row.id), 'Edit')])
}

function tenantEmail(row) {
  return row.contact?.email || row.email || ''
}

function tenantPhone(row) {
  return row.contact?.phone || row.phone || ''
}

function tenantStatus(row) {
  return row.status?.value ?? row.status ?? 'inactive'
}
function formatStatus(s) {
  return s ? String(s).charAt(0).toUpperCase() + String(s).slice(1) : '—'
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
