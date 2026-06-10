<template>
  <WorklistLayout
    eyebrow="Accounting"
    title="Balance sheet"
    :description="sheetDescription"
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
        <FormField label="As of date">
          <ErpDateInput v-model="filters.asOf" @change="loadReport" />
        </FormField>
      </SmartFilterBar>
    </template>

    <template #kpis>
      <KpiStrip v-if="report?.totals" class="mb-0 grid-cols-2 lg:grid-cols-4">
        <KpiCard label="Total assets" :value="formatMoney(report.totals.assets)" />
        <KpiCard label="Liabilities + equity" :value="formatMoney(report.totals.liabilities_and_equity)" />
        <KpiCard
          label="Variance"
          :value="formatMoney(report.totals.variance)"
          :variant="report.totals.balanced ? 'accent' : 'default'"
        />
        <KpiCard
          label="Equation"
          :value="report.totals.balanced ? 'Balanced' : 'Out of balance'"
          :variant="report.totals.balanced ? 'accent' : 'default'"
        />
      </KpiStrip>
    </template>

    <template #table>
      <div v-if="loading" class="py-10 text-center text-sm text-slate-500">Loading balance sheet…</div>

      <div v-else-if="report" class="space-y-8">
        <section v-for="section in sheetSections" :key="section.key">
          <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">
            {{ section.label }}
          </h2>
          <DataTable
            :columns="columns"
            :rows="report.sections[section.key].rows"
            :empty-title="`No ${section.label.toLowerCase()}`"
            :empty-description="`No ${section.label.toLowerCase()} balances as of this date.`"
          >
            <template #cell-code="{ row }">
              <code class="text-xs text-slate-600 dark:text-slate-400">{{ row.code }}</code>
            </template>
            <template #cell-balance="{ row }">
              <span class="font-mono text-sm">{{ formatMoney(row.balance) }}</span>
            </template>
          </DataTable>
          <div class="mt-2 flex justify-end border-t border-slate-200 pt-2 text-sm font-semibold text-slate-900">
            Total {{ section.label.toLowerCase() }}: {{ formatMoney(report.sections[section.key].total) }}
          </div>
        </section>
      </div>
    </template>
  </WorklistLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useBalanceSheet } from '@/composables/useBalanceSheet'
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
const exportingCsv = ref(false)
const exportingPdf = ref(false)

const {
  report,
  loading,
  filters,
  defaultAsOf,
  fetchReport,
  exportCsv,
  exportPdf,
  formatMoney,
} = useBalanceSheet()

const { chips, clearAll, removeChip, bindRoute } = useSmartFilters({
  defaults: { asOf: '' },
  labels: { asOf: { label: 'As of' } },
})

const columns = [
  { key: 'code', label: 'Code', mono: true },
  { key: 'name', label: 'Account', emphasis: true },
  { key: 'balance', label: 'Balance', align: 'right', mono: true },
]

const sheetSections = [
  { key: 'assets', label: 'Assets' },
  { key: 'liabilities', label: 'Liabilities' },
  { key: 'equity', label: 'Equity' },
]

const sheetDescription = computed(() =>
  report.value?.as_of
    ? `Point-in-time snapshot as of ${report.value.as_of}. Retained earnings closes cumulative P&L into equity.`
    : 'Assets, liabilities, and equity as of a selected date.',
)

async function loadReport() {
  pageError.value = ''
  try {
    await fetchReport({ asOf: filters.asOf })
    syncRoute()
  } catch (e) {
    pageError.value = e.response?.data?.message || 'Could not load balance sheet.'
  }
}

async function onExportCsv() {
  exportingCsv.value = true
  try {
    await exportCsv({ asOf: filters.asOf })
  } catch (e) {
    pageError.value = e.response?.data?.message || 'Could not export CSV.'
  } finally {
    exportingCsv.value = false
  }
}

async function onExportPdf() {
  exportingPdf.value = true
  try {
    await exportPdf({ asOf: filters.asOf })
  } catch (e) {
    pageError.value = e.response?.data?.message || 'Could not export PDF.'
  } finally {
    exportingPdf.value = false
  }
}

function syncRoute() {
  router.replace({
    query: { as_of: filters.asOf || undefined },
  })
}

function applyRouteQuery() {
  filters.asOf = (route.query.as_of || defaultAsOf()).toString()
}

function onClearAll() {
  clearAll()
  filters.asOf = defaultAsOf()
  syncRoute()
  loadReport()
}

onMounted(async () => {
  filters.asOf = defaultAsOf()
  bindRoute(route, router, { debounceMs: 0 })
  applyRouteQuery()
  await loadReport()
})
</script>
