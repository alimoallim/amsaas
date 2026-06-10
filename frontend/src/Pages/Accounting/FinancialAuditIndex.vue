<template>
  <WorklistLayout
    eyebrow="Accounting"
    title="Financial audit log"
    :count="meta.total"
    description="Immutable trail of payments, invoices, journal postings, and chart-of-accounts changes."
  >
    <template #actions>
      <ErpButton
        variant="ghost"
        size="sm"
        :disabled="!rows.length"
        :loading="exporting"
        @click="onExport"
      >
        Export CSV
      </ErpButton>
      <ErpButton variant="ghost" size="sm" :loading="loading" @click="loadTimeline">
        Refresh
      </ErpButton>
    </template>

    <template #filters>
      <AlertBanner v-if="pageError" class="mb-0" :message="pageError" @dismiss="pageError = ''" />
      <SmartFilterBar :chips="chips" @clear-all="onClearAll" @remove-chip="removeChip">
        <FormField label="From">
          <ErpDateInput v-model="filters.from" @change="onFilterChange" />
        </FormField>
        <FormField label="To">
          <ErpDateInput v-model="filters.to" @change="onFilterChange" />
        </FormField>
        <FormField label="Entity">
          <select v-model="filters.entityType" class="erp-select" @change="onFilterChange">
            <option value="">All financial entities</option>
            <option value="payment">Payments</option>
            <option value="monthly_invoice">Invoices</option>
            <option value="journal_entry">Journal entries</option>
            <option value="account">Accounts</option>
          </select>
        </FormField>
        <FormField label="Action">
          <select v-model="filters.action" class="erp-select" @change="onFilterChange">
            <option value="">All actions</option>
            <option value="created">Created</option>
            <option value="updated">Updated</option>
            <option value="posted">Posted</option>
            <option value="deleted">Deleted</option>
          </select>
        </FormField>
      </SmartFilterBar>
    </template>

    <template #table>
      <DataTable
        :columns="columns"
        :rows="rows"
        :loading="loading"
        empty-title="No audit events"
        empty-description="No financial mutations recorded in this period."
      >
        <template #cell-occurred_at="{ row }">
          <span class="text-xs text-slate-600">{{ formatTimestamp(row.occurred_at) }}</span>
        </template>

        <template #cell-action="{ row }">
          <StatusBadge :status="row.action" :label="row.action" />
        </template>

        <template #cell-entity_type="{ row }">
          <span class="text-sm capitalize">{{ row.entity_type?.replace(/_/g, ' ') }}</span>
        </template>

        <template #cell-summary="{ row }">
          <span class="text-sm text-slate-800">{{ row.summary }}</span>
        </template>

        <template #cell-user="{ row }">
          <span class="text-sm">{{ row.user?.name || '—' }}</span>
        </template>
      </DataTable>

      <div
        v-if="meta.last_page > 1"
        class="mt-4 flex flex-col gap-3 text-sm text-slate-600 sm:flex-row sm:items-center sm:justify-between dark:text-slate-400"
      >
        <span class="text-center sm:text-left">
          Page {{ meta.current_page }} of {{ meta.last_page }} ({{ meta.total }} events)
        </span>
        <div class="flex items-center justify-center gap-2 sm:justify-end">
          <ErpButton
            variant="ghost"
            size="sm"
            :disabled="meta.current_page <= 1"
            @click="goPage(meta.current_page - 1)"
          >
            Previous
          </ErpButton>
          <ErpButton
            variant="ghost"
            size="sm"
            :disabled="meta.current_page >= meta.last_page"
            @click="goPage(meta.current_page + 1)"
          >
            Next
          </ErpButton>
        </div>
      </div>
    </template>
  </WorklistLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useFinancialAudit } from '@/composables/useFinancialAudit'
import { useSmartFilters } from '@/composables/useSmartFilters'
import {
  WorklistLayout,
  ErpButton,
  DataTable,
  SmartFilterBar,
  FormField,
  ErpDateInput,
  AlertBanner,
  StatusBadge,
} from '@/components/erp'

const route = useRoute()
const router = useRouter()
const pageError = ref('')
const exporting = ref(false)

const {
  rows,
  meta,
  loading,
  filters,
  defaultPeriod,
  fetchTimeline,
  exportCsv,
  formatTimestamp,
} = useFinancialAudit()

const { chips, clearAll, removeChip, bindRoute } = useSmartFilters({
  defaults: { from: '', to: '', entityType: '', action: '' },
  labels: {
    from: { label: 'From' },
    to: { label: 'To' },
    entityType: { label: 'Entity' },
    action: { label: 'Action' },
  },
})

const columns = [
  { key: 'occurred_at', label: 'When' },
  { key: 'action', label: 'Action' },
  { key: 'entity_type', label: 'Entity', hideBelow: 'md' },
  { key: 'summary', label: 'Summary', emphasis: true, truncate: true },
  { key: 'user', label: 'User', hideBelow: 'sm' },
]

function reportParams() {
  return {
    from: filters.from,
    to: filters.to,
    entityType: filters.entityType,
    action: filters.action,
    page: filters.page,
  }
}

async function loadTimeline() {
  pageError.value = ''
  try {
    await fetchTimeline(reportParams())
    syncRoute()
  } catch (e) {
    pageError.value = e.response?.data?.message || 'Could not load audit log.'
  }
}

function onFilterChange() {
  filters.page = 1
  loadTimeline()
}

function goPage(page) {
  filters.page = page
  loadTimeline()
}

async function onExport() {
  exporting.value = true
  try {
    await exportCsv(reportParams())
  } catch (e) {
    pageError.value = e.response?.data?.message || 'Could not export audit log.'
  } finally {
    exporting.value = false
  }
}

function syncRoute() {
  router.replace({
    query: {
      from: filters.from || undefined,
      to: filters.to || undefined,
      entity_type: filters.entityType || undefined,
      action: filters.action || undefined,
      page: filters.page > 1 ? filters.page : undefined,
    },
  })
}

function applyRouteQuery() {
  const period = defaultPeriod()
  filters.from = (route.query.from || period.from).toString()
  filters.to = (route.query.to || period.to).toString()
  filters.entityType = (route.query.entity_type || '').toString()
  filters.action = (route.query.action || '').toString()
  filters.page = Number(route.query.page || 1)
}

function onClearAll() {
  const period = defaultPeriod()
  clearAll()
  filters.from = period.from
  filters.to = period.to
  filters.entityType = ''
  filters.action = ''
  filters.page = 1
  syncRoute()
  loadTimeline()
}

onMounted(async () => {
  const period = defaultPeriod()
  filters.from = period.from
  filters.to = period.to
  bindRoute(route, router, { debounceMs: 0 })
  applyRouteQuery()
  await loadTimeline()
})
</script>
