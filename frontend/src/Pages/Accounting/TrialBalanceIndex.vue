<template>
  <WorklistLayout
    eyebrow="Accounting"
    title="Trial balance"
    :count="report?.rows?.length"
    description="Period debit and credit totals per account with balance validation."
  >
    <template #actions>
      <ErpButton
        variant="ghost"
        size="sm"
        :disabled="!report?.rows?.length"
        :loading="exporting"
        @click="onExport"
      >
        Export CSV
      </ErpButton>
      <ErpButton
        variant="ghost"
        size="sm"
        :loading="loading"
        @click="loadReport"
      >
        Refresh
      </ErpButton>
      <ErpButton
        v-if="report?.controls?.can_close_period"
        variant="primary"
        size="sm"
        :loading="closing"
        @click="onClosePeriod"
      >
        Close period
      </ErpButton>
    </template>

    <template #filters>
      <AlertBanner v-if="pageError" class="mb-0" :message="pageError" @dismiss="pageError = ''" />
      <SmartFilterBar :chips="chips" @clear-all="onClearAll" @remove-chip="removeChip">
        <FormField label="From">
          <ErpDateInput v-model="filters.from" @change="loadReport" />
        </FormField>
        <FormField label="To">
          <ErpDateInput v-model="filters.to" @change="loadReport" />
        </FormField>
      </SmartFilterBar>
    </template>

    <template #kpis>
      <KpiStrip v-if="report?.totals" class="mb-0 grid-cols-2 lg:grid-cols-4">
        <KpiCard label="Balance debits" :value="formatMoney(report.totals.balance_debit)" />
        <KpiCard label="Balance credits" :value="formatMoney(report.totals.balance_credit)" />
        <KpiCard
          label="Variance"
          :value="formatMoney(report.totals.variance)"
          :variant="report.totals.balanced ? 'accent' : 'default'"
        />
        <KpiCard
          label="Status"
          :value="statusLabel"
          :variant="statusVariant"
        />
      </KpiStrip>
    </template>

    <template #table>
      <DataTable
        :columns="columns"
        :rows="report?.rows || []"
        :loading="loading"
        empty-title="No trial balance activity"
        empty-description="No posted journal activity in this period."
      >
        <template #cell-code="{ row }">
          <code class="text-xs text-slate-600 dark:text-slate-400">{{ row.code }}</code>
        </template>

        <template #cell-period_debits="{ row }">
          <span class="font-mono text-sm">
            {{ row.period_debits > 0 ? formatMoney(row.period_debits) : '—' }}
          </span>
        </template>

        <template #cell-period_credits="{ row }">
          <span class="font-mono text-sm">
            {{ row.period_credits > 0 ? formatMoney(row.period_credits) : '—' }}
          </span>
        </template>

        <template #cell-balance_debit="{ row }">
          <span class="font-mono text-sm">
            {{ row.balance_debit > 0 ? formatMoney(row.balance_debit) : '—' }}
          </span>
        </template>

        <template #cell-balance_credit="{ row }">
          <span class="font-mono text-sm">
            {{ row.balance_credit > 0 ? formatMoney(row.balance_credit) : '—' }}
          </span>
        </template>
      </DataTable>
    </template>
  </WorklistLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useTrialBalance } from '@/composables/useTrialBalance'
import { useSmartFilters } from '@/composables/useSmartFilters'
import {
  WorklistLayout,
  ErpButton,
  DataTable,
  SmartFilterBar,
  FormField,
  ErpDateInput,
  AlertBanner,
  KpiStrip,
  KpiCard,
} from '@/components/erp'

const route = useRoute()
const router = useRouter()
const pageError = ref('')
const exporting = ref(false)

const {
  report,
  loading,
  closing,
  filters,
  defaultPeriod,
  fetchReport,
  exportCsv,
  closePeriod,
  formatMoney,
} = useTrialBalance()

const { chips, clearAll, removeChip, bindRoute } = useSmartFilters({
  defaults: { from: '', to: '' },
  labels: {
    from: { label: 'From' },
    to: { label: 'To' },
  },
})

const columns = [
  { key: 'code', label: 'Code', mono: true },
  { key: 'name', label: 'Account', emphasis: true, truncate: true },
  { key: 'type', label: 'Type', hideBelow: 'md' },
  { key: 'period_debits', label: 'Period debits', shortLabel: 'Per. DR', align: 'right', mono: true, hideBelow: 'lg' },
  { key: 'period_credits', label: 'Period credits', shortLabel: 'Per. CR', align: 'right', mono: true, hideBelow: 'lg' },
  { key: 'balance_debit', label: 'Balance debit', shortLabel: 'Bal. DR', align: 'right', mono: true },
  { key: 'balance_credit', label: 'Balance credit', shortLabel: 'Bal. CR', align: 'right', mono: true },
]

const statusLabel = computed(() => {
  if (!report.value) return '—'
  if (report.value.period_close?.is_closed) return 'Period closed'
  return report.value.totals?.balanced ? 'Balanced' : 'Out of balance'
})

const statusVariant = computed(() => {
  if (!report.value) return 'default'
  if (report.value.period_close?.is_closed || report.value.totals?.balanced) return 'accent'
  return 'default'
})

async function loadReport() {
  pageError.value = ''
  try {
    await fetchReport({
      from: filters.from,
      to: filters.to,
    })
    syncRoute()
  } catch (e) {
    pageError.value = e.response?.data?.message || 'Could not load trial balance.'
  }
}

async function onExport() {
  exporting.value = true
  try {
    await exportCsv({
      from: filters.from,
      to: filters.to,
    })
  } catch (e) {
    pageError.value = e.response?.data?.message || 'Could not export trial balance.'
  } finally {
    exporting.value = false
  }
}

async function onClosePeriod() {
  if (!report.value?.period) return
  pageError.value = ''
  try {
    await closePeriod({
      fiscalYear: report.value.period.fiscal_year,
      fiscalMonth: report.value.period.fiscal_month,
    })
  } catch (e) {
    pageError.value = e.response?.data?.message || 'Could not close accounting period.'
  }
}

function syncRoute() {
  router.replace({
    query: {
      from: filters.from || undefined,
      to: filters.to || undefined,
    },
  })
}

function applyRouteQuery() {
  const period = defaultPeriod()
  filters.from = (route.query.from || period.from).toString()
  filters.to = (route.query.to || period.to).toString()
}

function onClearAll() {
  const period = defaultPeriod()
  clearAll()
  filters.from = period.from
  filters.to = period.to
  syncRoute()
  loadReport()
}

onMounted(async () => {
  const period = defaultPeriod()
  filters.from = period.from
  filters.to = period.to
  bindRoute(route, router, { debounceMs: 0 })
  applyRouteQuery()
  await loadReport()
})
</script>
