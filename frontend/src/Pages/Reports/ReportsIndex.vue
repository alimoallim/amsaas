<template>
  <WorklistLayout
    eyebrow="Finance · Collections"
    title="Collections"
    :count="(report?.buckets?.total?.count ?? 0) + (delinquency?.total ?? 0)"
    description="A/R aging and delinquency tracking — overdue flags update nightly via collections:flag-overdue."
  >
    <template #actions>
      <ErpButton variant="ghost" size="sm" :to="{ name: 'MonthlyInvoices' }">Monthly invoices</ErpButton>
      <ErpButton variant="ghost" size="sm" :to="{ name: 'Payments' }">Payments</ErpButton>
      <ErpButton variant="ghost" size="sm" :loading="loading" @click="refresh">Refresh</ErpButton>
      <ErpButton variant="secondary" :loading="exporting" @click="onExport">Export CSV</ErpButton>
    </template>

    <template #kpis>
      <AlertBanner
        v-if="pageError"
        variant="error"
        class="mb-4"
        :message="pageError"
        @dismiss="pageError = ''"
      />

      <div class="mb-4 grid grid-cols-1 gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:flex lg:flex-wrap lg:items-end dark:border-slate-700 dark:bg-slate-900">
        <FormField label="As of date" class="w-full lg:min-w-[10rem] lg:w-auto">
          <ErpDateInput v-model="filters.as_of" @change="refresh" />
        </FormField>
        <FormField label="Building" class="w-full lg:min-w-[14rem] lg:w-auto">
          <ErpSearchSelect
            v-model="filters.building_id"
            :options="buildingOptions"
            :loading="buildingsLoading"
            remote
            placeholder="All buildings"
            search-placeholder="Search building…"
            @search="onBuildingSearch"
            @update:model-value="refresh"
          />
        </FormField>
        <FormField label="Group by" class="w-full lg:min-w-[10rem] lg:w-auto">
          <select v-model="filters.group_by" class="erp-input" @change="refresh">
            <option value="tenant">Tenant</option>
            <option value="building">Building</option>
            <option value="invoice">Invoice</option>
          </select>
        </FormField>
      </div>

      <KpiStrip v-if="report" class="grid-cols-2 lg:grid-cols-6">
        <KpiCard label="Total open" :value="formatMoney(report.buckets.total.amount)" variant="accent" />
        <KpiCard label="Current" :value="formatMoney(report.buckets.current.amount)" />
        <KpiCard label="1–30 days" :value="formatMoney(report.buckets.days_1_30.amount)" />
        <KpiCard label="31–60 days" :value="formatMoney(report.buckets.days_31_60.amount)" />
        <KpiCard label="61–90 days" :value="formatMoney(report.buckets.days_61_90.amount)" />
        <KpiCard
          label="90+ days"
          :value="formatMoney(report.buckets.days_over_90.amount)"
          :variant="report.buckets.days_over_90.amount > 0 ? 'warning' : 'default'"
        />
      </KpiStrip>

      <section v-if="report" class="mt-4 rounded-xl border border-slate-200 bg-white p-4">
        <h2 class="text-sm font-semibold text-slate-800">Aging distribution</h2>
        <div class="mt-3 space-y-2">
          <div v-for="bar in agingBars" :key="bar.key" class="flex items-center gap-3 text-sm">
            <span class="w-24 shrink-0 text-slate-600">{{ bar.label }}</span>
            <div class="h-3 flex-1 overflow-hidden rounded-full bg-slate-100">
              <div
                class="h-full rounded-full transition-all"
                :class="bar.color"
                :style="{ width: `${bar.pct}%` }"
              />
            </div>
            <span class="w-28 shrink-0 text-right font-mono tabular-nums text-slate-700">
              {{ formatMoney(bar.amount) }}
            </span>
          </div>
        </div>
      </section>
    </template>

    <template #table>
      <DataTable
        :columns="tableColumns"
        :rows="tableRows"
        :loading="loading"
        empty-title="No open receivables"
        empty-description="Issued invoices with a balance due appear here."
      >
        <template #cell-tenant_name="{ row }">
          <span class="font-medium">{{ row.tenant_name || row.tenant?.display_name || '—' }}</span>
        </template>
        <template #cell-building_name="{ row }">
          <span>{{ row.building_name || row.building?.name || '—' }}</span>
        </template>
        <template #cell-total_balance="{ row }">
          <span class="font-mono tabular-nums font-medium">{{ formatMoney(row.total_balance) }}</span>
        </template>
        <template #cell-balance_due="{ row }">
          <span class="font-mono tabular-nums font-medium">{{ formatMoney(row.balance_due) }}</span>
        </template>
        <template #cell-bucket="{ row }">
          <span class="text-xs font-medium uppercase tracking-wide text-slate-600">
            {{ bucketLabel(row.bucket) }}
          </span>
        </template>
        <template #cell-invoice_number="{ row }">
          <RouterLink
            v-if="row.invoice_id"
            :to="{ name: 'InvoiceShow', params: { id: row.invoice_id } }"
            class="font-mono text-xs text-blue-700 hover:underline"
          >
            {{ row.invoice_number }}
          </RouterLink>
          <code v-else class="text-xs font-mono">{{ row.invoice_number || '—' }}</code>
        </template>
        <template #cell-days_overdue="{ row }">
          <span class="tabular-nums">{{ row.days_overdue ?? 0 }}</span>
        </template>
      </DataTable>

      <section class="mt-8 border-t border-slate-200 pt-6">
        <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
          <div class="min-w-0">
            <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Delinquency queue</h2>
            <p class="text-sm text-slate-600 dark:text-slate-400">
              Past-due issued invoices flagged for collection — escalation advances automatically.
            </p>
          </div>
          <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:flex-wrap sm:items-end">
            <ErpButton
              variant="secondary"
              size="sm"
              class="w-full sm:w-auto"
              :disabled="!selectedFlagIds.length"
              :loading="reminding"
              @click="onRemindSelected"
            >
              Send reminder ({{ selectedFlagIds.length }})
            </ErpButton>
          <FormField label="Escalation stage" class="w-full sm:min-w-[12rem] sm:w-auto">
            <select v-model="escalationFilter" class="erp-input" @change="refreshDelinquency">
              <option value="">All stages</option>
              <option value="first_notice">1st notice</option>
              <option value="second_notice">2nd notice</option>
              <option value="legal_handoff">Legal handoff</option>
            </select>
          </FormField>
          </div>
        </div>

      <AlertBanner
        v-if="pageSuccess"
        variant="success"
        class="mb-4"
        :message="pageSuccess"
        @dismiss="pageSuccess = ''"
      />

        <KpiStrip v-if="delinquency" class="mb-4 grid-cols-2 lg:grid-cols-4">
          <KpiCard label="Active flags" :value="delinquency.total" variant="accent" />
          <KpiCard label="1st notice" :value="delinquency.counts_by_stage.first_notice" />
          <KpiCard label="2nd notice" :value="delinquency.counts_by_stage.second_notice" variant="warning" />
          <KpiCard label="Legal handoff" :value="delinquency.counts_by_stage.legal_handoff" variant="warning" />
        </KpiStrip>

        <DataTable
          :columns="delinquencyColumns"
          :rows="delinquencyRows"
          :loading="delinquencyLoading"
          selectable
          multi-select
          :selected-ids="selectedFlagIds"
          row-key="id"
          empty-title="No delinquent invoices"
          empty-description="Run collections:flag-overdue or wait for the nightly job after invoices pass due date."
          @update:selected-ids="selectedFlagIds = $event"
        >
          <template #cell-invoice_number="{ row }">
            <RouterLink
              :to="{ name: 'InvoiceShow', params: { id: row.monthly_invoice_id } }"
              class="font-mono text-xs text-blue-700 hover:underline"
            >
              {{ row.invoice_number }}
            </RouterLink>
          </template>
          <template #cell-tenant="{ row }">
            <span class="font-medium">{{ row.tenant?.display_name || '—' }}</span>
          </template>
          <template #cell-building="{ row }">
            <span>{{ row.building?.name || '—' }}</span>
          </template>
          <template #cell-balance_due="{ row }">
            <span class="font-mono tabular-nums font-medium">{{ formatMoney(row.balance_due) }}</span>
          </template>
          <template #cell-escalation_label="{ row }">
            <StatusBadge :status="row.escalation_stage" :label="row.escalation_label" />
          </template>
          <template #cell-actions="{ row }">
            <div class="flex items-center gap-1">
              <ErpButton size="sm" variant="ghost" :loading="reminding" @click="onRemindOne(row.id)">
                Remind
              </ErpButton>
              <ErpButton
                size="sm"
                variant="ghost"
                :loading="generatingNotice"
                @click="onDownloadNotice(row.id)"
              >
                Notice PDF
              </ErpButton>
            </div>
          </template>
        </DataTable>
      </section>
    </template>
  </WorklistLayout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useAgingReport } from '@/composables/useAgingReport'
import { useBuildingPicker } from '@/composables/useBuildingPicker'
import {
  WorklistLayout,
  DataTable,
  ErpButton,
  KpiCard,
  KpiStrip,
  AlertBanner,
  FormField,
  ErpDateInput,
  ErpSearchSelect,
  StatusBadge,
} from '@/components/erp'

const {
  report,
  delinquency,
  loading,
  delinquencyLoading,
  exporting,
  filters,
  fetchReport,
  fetchDelinquency,
  sendReminders,
  downloadNotice,
  reminding,
  generatingNotice,
  exportCsv,
} = useAgingReport()
const { buildings, loading: buildingsLoading, fetchBuildings, buildingToOption } = useBuildingPicker()

const pageError = ref('')
const pageSuccess = ref('')
const escalationFilter = ref('')
const selectedFlagIds = ref([])

const buildingOptions = computed(() => [
  { value: '', label: 'All buildings' },
  ...buildings.value.map((b) => buildingToOption(b)),
])

const bucketDefs = [
  { key: 'current', label: 'Current', color: 'bg-emerald-500' },
  { key: 'days_1_30', label: '1–30 days', color: 'bg-blue-500' },
  { key: 'days_31_60', label: '31–60 days', color: 'bg-amber-500' },
  { key: 'days_61_90', label: '61–90 days', color: 'bg-orange-500' },
  { key: 'days_over_90', label: '90+ days', color: 'bg-red-500' },
]

const agingBars = computed(() => {
  const buckets = report.value?.buckets
  if (!buckets) return []
  const total = Number(buckets.total?.amount ?? 0) || 1
  return bucketDefs.map((def) => {
    const amount = Number(buckets[def.key]?.amount ?? 0)
    return {
      ...def,
      amount,
      pct: Math.max(2, Math.round((amount / total) * 100)),
    }
  })
})

const tenantColumns = [
  { key: 'tenant_name', label: 'Tenant', emphasis: true },
  { key: 'building_name', label: 'Building' },
  { key: 'invoice_count', label: 'Invoices', align: 'right' },
  { key: 'total_balance', label: 'Balance due', align: 'right' },
]

const buildingColumns = [
  { key: 'building_name', label: 'Building', emphasis: true },
  { key: 'invoice_count', label: 'Invoices', align: 'right' },
  { key: 'total_balance', label: 'Balance due', align: 'right' },
]

const invoiceColumns = [
  { key: 'invoice_number', label: 'Invoice', mono: true },
  { key: 'tenant_name', label: 'Tenant', emphasis: true },
  { key: 'building_name', label: 'Building' },
  { key: 'due_date', label: 'Due' },
  { key: 'days_overdue', label: 'Days overdue', align: 'right' },
  { key: 'bucket', label: 'Bucket' },
  { key: 'balance_due', label: 'Balance', align: 'right' },
]

const tableColumns = computed(() => {
  if (filters.group_by === 'building') return buildingColumns
  if (filters.group_by === 'invoice') return invoiceColumns
  return tenantColumns
})

const tableRows = computed(() => {
  const rows = report.value?.rows ?? []
  if (filters.group_by !== 'invoice') return rows
  return rows.map((row) => ({
    ...row,
    tenant_name: row.tenant?.display_name,
    building_name: row.building?.name,
  }))
})

const delinquencyColumns = [
  { key: 'invoice_number', label: 'Invoice', mono: true },
  { key: 'tenant', label: 'Tenant', emphasis: true, truncate: true },
  { key: 'building', label: 'Building', hideBelow: 'md' },
  { key: 'due_date', label: 'Due', hideBelow: 'lg' },
  { key: 'days_overdue', label: 'Days overdue', shortLabel: 'Days', align: 'right' },
  { key: 'escalation_label', label: 'Stage', hideBelow: 'sm' },
  { key: 'balance_due', label: 'Balance', align: 'right', mono: true },
  { key: 'actions', label: '', type: 'actions' },
]

const delinquencyRows = computed(() => delinquency.value?.rows ?? [])

function formatMoney(v) {
  return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(Number(v) || 0)
}

function bucketLabel(key) {
  return bucketDefs.find((b) => b.key === key)?.label ?? key
}

let buildingSearchTimer = null
function onBuildingSearch(q) {
  clearTimeout(buildingSearchTimer)
  buildingSearchTimer = setTimeout(
    () => fetchBuildings(q, { ensureId: filters.building_id || undefined }),
    280,
  )
}

async function refreshDelinquency() {
  try {
    await fetchDelinquency({ escalation_stage: escalationFilter.value || undefined })
  } catch (e) {
    pageError.value = e.response?.data?.message || 'Could not load delinquency queue.'
  }
}

async function refresh() {
  pageError.value = ''
  const errors = []

  try {
    await fetchReport()
  } catch (e) {
    errors.push(e.response?.data?.message || 'Could not load aging report.')
  }

  try {
    await fetchDelinquency({ escalation_stage: escalationFilter.value || undefined })
  } catch (e) {
    errors.push(e.response?.data?.message || 'Could not load delinquency queue.')
  }

  if (errors.length) {
    pageError.value = errors.join(' ')
  }
}

async function onExport() {
  pageError.value = ''
  try {
    await exportCsv()
  } catch (e) {
    pageError.value = e.response?.data?.message || 'Could not export report.'
  }
}

async function onRemindSelected() {
  if (!selectedFlagIds.value.length) return
  await runRemind(selectedFlagIds.value)
}

async function onRemindOne(flagId) {
  await runRemind([flagId])
}

async function runRemind(flagIds) {
  pageError.value = ''
  pageSuccess.value = ''
  try {
    const { message } = await sendReminders(flagIds)
    pageSuccess.value = message || 'Reminder(s) queued.'
    selectedFlagIds.value = []
  } catch (e) {
    pageError.value = e.response?.data?.message || 'Could not queue reminder(s).'
  }
}

async function onDownloadNotice(flagId) {
  pageError.value = ''
  pageSuccess.value = ''
  try {
    await downloadNotice(flagId)
    pageSuccess.value = 'Collection notice downloaded.'
  } catch (e) {
    pageError.value = e.response?.data?.message || 'Could not generate notice PDF.'
  }
}

onMounted(() => {
  fetchBuildings('')
  refresh()
})
</script>
