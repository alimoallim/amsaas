<template>
  <WorklistLayout
    eyebrow="Finance · Tenant billing"
    :title="tenantTitle"
    :count="meta.total"
    description="Invoice history, outstanding balance, and PDF downloads for this tenant."
  >
    <template #actions>
      <ErpButton variant="ghost" size="sm" :to="{ name: 'Tenants' }">Back to tenants</ErpButton>
      <ErpButton
        v-if="summary.outstanding_balance > 0"
        size="sm"
        :to="{ name: 'Payments', query: { tenant_id: tenantId } }"
      >
        Record payment
      </ErpButton>
      <ErpButton variant="ghost" size="sm" :loading="loading" @click="refresh">Refresh</ErpButton>
    </template>

    <template #kpis>
      <AlertBanner
        v-if="pageError"
        variant="error"
        class="mb-4"
        :message="pageError"
        @dismiss="pageError = ''"
      />

      <KpiStrip class="grid-cols-2 lg:grid-cols-4">
        <KpiCard label="Total invoiced" :value="formatMoney(summary.total_invoiced)" />
        <KpiCard label="Total paid" :value="formatMoney(summary.total_paid)" />
        <KpiCard
          label="Outstanding"
          :value="formatMoney(summary.outstanding_balance)"
          variant="accent"
          :caption="summary.outstanding_balance > 0 ? 'Open AR balance' : 'No open balance'"
        />
        <KpiCard label="Invoices" :value="summary.invoice_count" :caption="`${summary.agreement_count} agreement(s)`" />
      </KpiStrip>
    </template>

    <template #filters>
      <div class="mb-3 flex flex-wrap items-end gap-3">
        <FormField label="Year">
          <select v-model="filters.year" class="erp-select w-auto" @change="syncAndFetch">
            <option value="">All years</option>
            <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
          </select>
        </FormField>
        <FormField label="Month">
          <select v-model="filters.month" class="erp-select w-auto" :disabled="!filters.year" @change="syncAndFetch">
            <option value="">All months</option>
            <option v-for="(label, idx) in months" :key="idx" :value="idx + 1">{{ label }}</option>
          </select>
        </FormField>
        <FormField label="Status">
          <select v-model="filters.status" class="erp-select w-auto" @change="syncAndFetch">
            <option value="">All statuses</option>
            <option value="draft">Draft</option>
            <option value="issued">Issued</option>
            <option value="partially_paid">Partially paid</option>
            <option value="paid">Paid</option>
            <option value="overdue">Overdue</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </FormField>
      </div>
    </template>

    <template #table>
      <DataTable
        :columns="columns"
        :rows="invoices"
        :loading="loading"
        :meta="meta"
        empty-title="No invoices"
        empty-description="Invoices appear here after billing close compiles charges for this tenant's agreements."
        @page-change="fetchBilling"
        @row-click="openInvoice"
      >
        <template #cell-period="{ row }">
          <span class="font-medium text-slate-900">{{ row.billing_period }}</span>
        </template>
        <template #cell-unit="{ row }">
          <div class="text-sm">
            <p class="font-medium">{{ row.apartment?.unit_number || '—' }}</p>
            <p v-if="row.building?.name" class="text-xs text-slate-500">{{ row.building.name }}</p>
          </div>
        </template>
        <template #cell-total="{ row }">
          <span class="font-mono text-sm tabular-nums">{{ formatMoney(row.total_amount) }}</span>
        </template>
        <template #cell-balance="{ row }">
          <span class="font-mono text-sm tabular-nums">{{ formatMoney(row.balance_due) }}</span>
        </template>
        <template #cell-status="{ row }">
          <StatusBadge :status="row.status" :label="row.status" />
        </template>
        <template #cell-actions="{ row }">
          <RowActionsMenu :actions="invoiceActions(row)" />
        </template>
      </DataTable>
    </template>
  </WorklistLayout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useTenantBilling } from '@/composables/useTenantBilling'
import { useMonthlyInvoices } from '@/composables/useMonthlyInvoices'
import { compactActions, viewAction } from '@/composables/useTableActions'
import { tenantDisplayName } from '@/utils/tenantDisplayName'
import {
  WorklistLayout,
  DataTable,
  RowActionsMenu,
  FormField,
  ErpButton,
  StatusBadge,
  KpiCard,
  KpiStrip,
  AlertBanner,
} from '@/components/erp'

const route = useRoute()
const router = useRouter()
const tenantId = computed(() => route.params.id)
const pageError = ref('')

const {
  tenant,
  invoices,
  summary,
  loading,
  filters,
  meta,
  fetchBilling,
} = useTenantBilling(tenantId)

const { downloadPdf } = useMonthlyInvoices()

const months = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December',
]

const years = computed(() => {
  const current = new Date().getFullYear()
  return Array.from({ length: 6 }, (_, i) => current - i)
})

const tenantTitle = computed(() => {
  if (tenant.value) {
    return tenantDisplayName(tenant.value) || 'Tenant billing'
  }
  return 'Tenant billing'
})

const columns = [
  { key: 'period', label: 'Period', emphasis: true },
  { key: 'invoice_number', label: 'Invoice #', mono: true },
  { key: 'unit', label: 'Unit' },
  { key: 'total', label: 'Total', align: 'right' },
  { key: 'balance', label: 'Balance', align: 'right' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: '', align: 'right', type: 'actions' },
]

function formatMoney(v) {
  const n = Number(v)
  if (Number.isNaN(n)) {
    return '—'
  }
  return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(n)
}

function invoiceActions(row) {
  const actions = [
    viewAction('InvoiceShow', row.id, 'View'),
  ]

  if (row.controls?.can_download) {
    actions.push({
      key: 'pdf',
      label: 'Download PDF',
      onClick: () => onDownload(row),
    })
  }

  return compactActions(actions)
}

function openInvoice(row) {
  router.push({ name: 'InvoiceShow', params: { id: row.id } })
}

async function onDownload(row) {
  try {
    await downloadPdf(row.id, row.invoice_number || 'invoice')
  } catch {
    pageError.value = 'Failed to download PDF.'
  }
}

function syncAndFetch() {
  fetchBilling(1).catch(() => {
    pageError.value = 'Failed to load tenant billing.'
  })
}

function refresh() {
  fetchBilling(meta.value.current_page).catch(() => {
    pageError.value = 'Failed to load tenant billing.'
  })
}

onMounted(() => {
  fetchBilling(1).catch(() => {
    pageError.value = 'Failed to load tenant billing.'
  })
})
</script>
