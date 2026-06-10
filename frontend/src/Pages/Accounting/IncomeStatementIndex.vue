<template>
  <WorklistLayout
    eyebrow="Accounting"
    title="Income statement"
    :description="periodDescription"
  >
    <template #actions>
      <ErpButton
        variant="ghost"
        size="sm"
        :disabled="!report"
        :loading="exportingPdf"
        @click="onExportPdf"
      >
        Export PDF
      </ErpButton>
      <ErpButton
        variant="ghost"
        size="sm"
        :disabled="!report"
        :loading="exportingCsv"
        @click="onExportCsv"
      >
        Export CSV
      </ErpButton>
      <ErpButton variant="ghost" size="sm" :loading="loading" @click="loadReport">
        Refresh
      </ErpButton>
    </template>

    <template #filters>
      <AlertBanner v-if="pageError" class="mb-0" :message="pageError" @dismiss="pageError = ''" />
      <SmartFilterBar :chips="chips" @clear-all="onClearAll" @remove-chip="removeChip">
        <FormField label="Billing year" span="1">
          <input
            v-model.number="filters.billingYear"
            type="number"
            min="2020"
            max="2050"
            class="erp-input"
            @change="onBillingPeriodChange"
          />
        </FormField>
        <FormField label="Billing month" span="1">
          <select v-model.number="filters.billingMonth" class="erp-select" @change="onBillingPeriodChange">
            <option v-for="(name, idx) in monthNames" :key="idx" :value="idx + 1">
              {{ name }}
            </option>
          </select>
        </FormField>
      </SmartFilterBar>
    </template>

    <template #kpis>
      <KpiStrip v-if="report?.totals" class="mb-0">
        <KpiCard label="Gross revenue" :value="formatMoney(report.totals.gross_revenue)" />
        <KpiCard label="Total expenses" :value="formatMoney(report.totals.total_expenses)" />
        <KpiCard
          label="Net income"
          :value="formatMoney(report.totals.net_income)"
          variant="accent"
        />
      </KpiStrip>
    </template>

    <template #table>
      <div v-if="loading" class="py-10 text-center text-sm text-slate-500">Loading report…</div>

      <div v-else-if="report" class="space-y-8">
        <p
          v-if="isEmptyReport"
          class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900"
        >
          No revenue in this billing month. Revenue is recorded when invoices are <strong>issued</strong>
          (not when payments are received). Check that June invoices are issued and that the chart of
          accounts is seeded for your company.
        </p>

        <section>
          <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Revenue</h2>
          <DataTable
            :columns="columns"
            :rows="report.sections.revenue.rows"
            empty-title="No revenue"
            :empty-description="emptyRevenueHint"
          >
            <template #cell-code="{ row }">
              <code class="text-xs text-slate-600 dark:text-slate-400">{{ row.code }}</code>
            </template>
            <template #cell-amount="{ row }">
              <span class="font-mono text-sm">{{ formatMoney(row.amount) }}</span>
            </template>
          </DataTable>
          <div class="mt-2 flex justify-end border-t border-slate-200 pt-2 text-sm font-semibold text-slate-900 dark:border-slate-700 dark:text-slate-100">
            Total revenue: {{ formatMoney(report.sections.revenue.total) }}
          </div>
        </section>

        <section>
          <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Expenses</h2>
          <DataTable
            :columns="columns"
            :rows="report.sections.expenses.rows"
            empty-title="No expenses"
            empty-description="No expense accounts had activity in this billing month."
          >
            <template #cell-code="{ row }">
              <code class="text-xs text-slate-600 dark:text-slate-400">{{ row.code }}</code>
            </template>
            <template #cell-amount="{ row }">
              <span class="font-mono text-sm">{{ formatMoney(row.amount) }}</span>
            </template>
          </DataTable>
          <div class="mt-2 flex justify-end border-t border-slate-200 pt-2 text-sm font-semibold text-slate-900 dark:border-slate-700 dark:text-slate-100">
            Total expenses: {{ formatMoney(report.sections.expenses.total) }}
          </div>
        </section>
      </div>
    </template>
  </WorklistLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useIncomeStatement } from '@/composables/useIncomeStatement'
import { useSmartFilters } from '@/composables/useSmartFilters'
import { calendarMonthPeriod } from '@/utils/localDate.js'
import {
  WorklistLayout,
  ErpButton,
  DataTable,
  SmartFilterBar,
  FormField,
  AlertBanner,
  KpiStrip,
  KpiCard,
} from '@/components/erp'

const route = useRoute()
const router = useRouter()
const pageError = ref('')
const exportingCsv = ref(false)
const exportingPdf = ref(false)

const monthNames = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December',
]

const {
  report,
  loading,
  filters,
  defaultPeriod,
  fetchReport,
  exportCsv,
  exportPdf,
  formatMoney,
} = useIncomeStatement()

const { chips, clearAll, removeChip, bindRoute } = useSmartFilters({
  defaults: { billingYear: '', billingMonth: '' },
  labels: {
    billingYear: { label: 'Year' },
    billingMonth: {
      label: 'Month',
      format: (m) => monthNames[Number(m) - 1] || m,
    },
  },
})

const columns = [
  { key: 'code', label: 'Code', mono: true },
  { key: 'name', label: 'Account', emphasis: true },
  { key: 'amount', label: 'Amount', align: 'right', mono: true },
]

const periodDescription = computed(() => {
  if (!report.value?.period) {
    return 'Revenue and expense breakdown by billing month (P&L).'
  }
  const { from, to, billing_year: year, billing_month: month } = report.value.period
  if (year && month) {
    return `P&L for billing month ${monthNames[month - 1]} ${year} (posted journals with fiscal month ${month}/${year}).`
  }
  return `P&L for ${from} to ${to}.`
})

const isEmptyReport = computed(() =>
  report.value
  && report.value.totals.gross_revenue <= 0
  && report.value.totals.total_expenses <= 0,
)

const emptyRevenueHint = computed(() =>
  `No issued-invoice revenue posted for ${monthNames[filters.billingMonth - 1]} ${filters.billingYear}.`,
)

function reportParams() {
  return {
    billingYear: filters.billingYear,
    billingMonth: filters.billingMonth,
    from: filters.from,
    to: filters.to,
  }
}

async function loadReport() {
  pageError.value = ''
  try {
    await fetchReport(reportParams())
    syncRoute()
  } catch (e) {
    pageError.value = e.response?.data?.message || 'Could not load income statement.'
  }
}

async function onExportCsv() {
  exportingCsv.value = true
  try {
    await exportCsv(reportParams())
  } catch (e) {
    pageError.value = e.response?.data?.message || 'Could not export CSV.'
  } finally {
    exportingCsv.value = false
  }
}

async function onExportPdf() {
  exportingPdf.value = true
  try {
    await exportPdf(reportParams())
  } catch (e) {
    pageError.value = e.response?.data?.message || 'Could not export PDF.'
  } finally {
    exportingPdf.value = false
  }
}

function onBillingPeriodChange() {
  const period = calendarMonthPeriod(new Date(filters.billingYear, filters.billingMonth - 1, 1))
  filters.from = period.from
  filters.to = period.to
  loadReport()
}

function syncRoute() {
  router.replace({
    query: {
      billing_year: filters.billingYear || undefined,
      billing_month: filters.billingMonth || undefined,
    },
  })
}

function applyRouteQuery() {
  const period = defaultPeriod()
  filters.billingYear = Number(route.query.billing_year || period.billingYear)
  filters.billingMonth = Number(route.query.billing_month || period.billingMonth)
  filters.from = period.from
  filters.to = period.to
}

function onClearAll() {
  const period = defaultPeriod()
  clearAll()
  filters.billingYear = period.billingYear
  filters.billingMonth = period.billingMonth
  filters.from = period.from
  filters.to = period.to
  syncRoute()
  loadReport()
}

onMounted(async () => {
  const period = defaultPeriod()
  filters.billingYear = period.billingYear
  filters.billingMonth = period.billingMonth
  filters.from = period.from
  filters.to = period.to
  bindRoute(route, router, { debounceMs: 0 })
  applyRouteQuery()
  await loadReport()
})
</script>
