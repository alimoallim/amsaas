<template>
  <WorklistLayout
    eyebrow="Finance · Invoice"
    :title="invoice?.invoice_number || 'Invoice'"
    description="Line items, balances, and invoice lifecycle actions."
  >
    <template #actions>
      <ErpButton variant="ghost" size="sm" :to="{ name: 'MonthlyInvoices' }">Back to list</ErpButton>
      <ErpButton
        v-if="invoice?.controls?.can_issue"
        :loading="acting === 'issue'"
        @click="onIssue"
      >
        Issue invoice
      </ErpButton>
      <ErpButton
        v-if="invoice?.controls?.can_download"
        variant="secondary"
        size="sm"
        @click="onDownload"
      >
        Download PDF
      </ErpButton>
      <ErpButton
        v-if="invoice?.controls?.can_void"
        variant="ghost"
        size="sm"
        class="text-red-700 dark:text-red-400"
        :loading="acting === 'void'"
        @click="onVoid"
      >
        Void
      </ErpButton>
    </template>

    <template #kpis>
      <AlertBanner v-if="pageError" variant="error" class="mb-4" :message="pageError" @dismiss="pageError = ''" />
      <AlertBanner v-if="pageMessage" variant="success" class="mb-4" :message="pageMessage" @dismiss="pageMessage = ''" />

      <div v-if="loading" class="py-10 text-center text-sm text-slate-500 dark:text-slate-400">Loading invoice…</div>

      <template v-else-if="invoice">
        <KpiStrip class="grid-cols-2 lg:grid-cols-4">
          <KpiCard label="Total" :value="formatMoney(invoice.total_amount)" />
          <KpiCard label="Paid" :value="formatMoney(invoice.paid_amount)" />
          <KpiCard label="Balance due" :value="formatMoney(invoice.balance_due)" variant="accent" />
          <div class="relative overflow-hidden rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5 dark:border-slate-700 dark:bg-slate-900 dark:shadow-black/20">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Status</p>
            <div class="mt-2">
              <StatusBadge :status="invoice.status" :label="invoice.status" />
            </div>
          </div>
        </KpiStrip>

        <div class="mt-6 grid gap-4 lg:grid-cols-3">
          <ErpPanel title="Billing context" class="lg:col-span-1">
            <dl class="space-y-2 text-sm text-slate-600 dark:text-slate-400">
              <div class="flex justify-between gap-3">
                <dt>Period</dt>
                <dd class="font-medium text-slate-900 dark:text-slate-100">{{ invoice.billing_period }}</dd>
              </div>
              <div class="flex justify-between gap-3">
                <dt>Issue date</dt>
                <dd class="text-slate-700 dark:text-slate-300">{{ invoice.issue_date || '—' }}</dd>
              </div>
              <div class="flex justify-between gap-3">
                <dt>Due date</dt>
                <dd class="text-slate-700 dark:text-slate-300">{{ invoice.due_date || '—' }}</dd>
              </div>
              <div class="flex justify-between gap-3">
                <dt>Unit</dt>
                <dd class="text-slate-700 dark:text-slate-300">{{ invoice.apartment?.unit_number || '—' }}</dd>
              </div>
              <div class="flex justify-between gap-3">
                <dt>Building</dt>
                <dd class="text-slate-700 dark:text-slate-300">{{ invoice.building?.name || '—' }}</dd>
              </div>
              <div class="flex justify-between gap-3">
                <dt>Tenant</dt>
                <dd class="text-slate-700 dark:text-slate-300">{{ tenantLabel }}</dd>
              </div>
              <div
                v-if="invoice.void_reason"
                class="rounded-lg bg-red-50 px-3 py-2 text-red-800 dark:border dark:border-red-800/50 dark:bg-red-950/40 dark:text-red-300"
              >
                <p class="text-xs font-semibold uppercase tracking-wide">Void reason</p>
                <p class="mt-1">{{ invoice.void_reason }}</p>
              </div>
            </dl>
            <div v-if="invoice.agreement?.id" class="mt-4">
              <ErpButton
                variant="ghost"
                size="sm"
                :to="{ name: 'RentalAgreementShow', params: { id: invoice.agreement.id } }"
              >
                View rental agreement
              </ErpButton>
            </div>
          </ErpPanel>

          <ErpPanel title="Line items" :no-padding="true" class="lg:col-span-2">
            <DataTable
              :columns="lineColumns"
              :rows="invoice.line_items || []"
              :loading="false"
              empty-title="No line items"
              empty-description="Run billing close to compile charges into this invoice."
            >
              <template #cell-quantity="{ row }">
                <span class="font-mono text-xs tabular-nums">{{ row.quantity }}</span>
              </template>
              <template #cell-unit_price="{ row }">
                <span class="font-mono text-xs tabular-nums">{{ formatMoney(row.unit_price) }}</span>
              </template>
              <template #cell-amount="{ row }">
                <span class="font-mono text-sm tabular-nums">{{ formatMoney(row.amount) }}</span>
              </template>
            </DataTable>

            <template #footer>
              <div class="grid gap-1 text-sm text-slate-600 dark:text-slate-400">
                <div class="flex justify-between">
                  <span>Rent</span>
                  <span class="font-mono text-slate-800 dark:text-slate-200">{{ formatMoney(invoice.subtotal_rent) }}</span>
                </div>
                <div class="flex justify-between">
                  <span>Utilities</span>
                  <span class="font-mono text-slate-800 dark:text-slate-200">{{ formatMoney(invoice.subtotal_utilities) }}</span>
                </div>
                <div class="flex justify-between">
                  <span>Services</span>
                  <span class="font-mono text-slate-800 dark:text-slate-200">{{ formatMoney(invoice.subtotal_services) }}</span>
                </div>
                <div
                  v-if="Number(invoice.discount_amount) > 0"
                  class="flex justify-between text-emerald-700 dark:text-emerald-400"
                >
                  <span>Discount</span>
                  <span class="font-mono">−{{ formatMoney(invoice.discount_amount) }}</span>
                </div>
                <div class="flex justify-between border-t border-slate-200 pt-2 font-semibold text-slate-900 dark:border-slate-700 dark:text-slate-100">
                  <span>Total</span>
                  <span class="font-mono">{{ formatMoney(invoice.total_amount) }}</span>
                </div>
              </div>
            </template>
          </ErpPanel>
        </div>

        <ErpPanel
          v-if="invoice.payment_allocations?.length"
          title="Payment history"
          :no-padding="true"
          class="mt-4"
        >
          <DataTable
            :columns="paymentColumns"
            :rows="invoice.payment_allocations"
            :loading="false"
            empty-title="No payments"
          >
            <template #cell-receipt="{ row }">
              <code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs font-mono text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                {{ row.payment?.receipt_number || '—' }}
              </code>
            </template>
            <template #cell-payment_date="{ row }">
              <span class="text-sm text-slate-700 dark:text-slate-300">{{ row.payment?.payment_date || '—' }}</span>
            </template>
            <template #cell-payment_method="{ row }">
              <span class="text-sm text-slate-700 dark:text-slate-300">{{ row.payment?.payment_method || '—' }}</span>
            </template>
            <template #cell-amount_allocated="{ row }">
              <span class="font-mono text-sm tabular-nums text-slate-800 dark:text-slate-200">{{ formatMoney(row.amount_allocated) }}</span>
            </template>
          </DataTable>
        </ErpPanel>
      </template>
    </template>
  </WorklistLayout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useMonthlyInvoices } from '@/composables/useMonthlyInvoices'
import { useConfirm } from '@/composables/useConfirm'
import { tenantDisplayName } from '@/utils/tenantDisplayName'
import {
  WorklistLayout,
  DataTable,
  ErpButton,
  StatusBadge,
  KpiCard,
  KpiStrip,
  ErpPanel,
  AlertBanner,
} from '@/components/erp'

const route = useRoute()
const { confirm } = useConfirm()
const { fetchOne, issueOne, voidInvoice, downloadPdf } = useMonthlyInvoices()

const invoice = ref(null)
const loading = ref(false)
const acting = ref('')
const pageError = ref('')
const pageMessage = ref('')

const tenantLabel = computed(() => tenantDisplayName(invoice.value?.tenant) || '—')

const lineColumns = [
  { key: 'line_type', label: 'Type' },
  { key: 'description', label: 'Description', emphasis: true },
  { key: 'quantity', label: 'Qty', align: 'right' },
  { key: 'unit_price', label: 'Unit', align: 'right' },
  { key: 'amount', label: 'Amount', align: 'right' },
]

const paymentColumns = [
  { key: 'receipt', label: 'Receipt', mono: true },
  { key: 'payment_date', label: 'Date' },
  { key: 'payment_method', label: 'Method' },
  { key: 'amount_allocated', label: 'Allocated', align: 'right' },
]

function formatMoney(v) {
  return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(Number(v) || 0)
}

async function load() {
  loading.value = true
  pageError.value = ''
  try {
    invoice.value = await fetchOne(route.params.id)
  } catch (e) {
    pageError.value = e.response?.data?.message || 'Invoice not found.'
  } finally {
    loading.value = false
  }
}

async function onIssue() {
  const ok = await confirm({
    title: 'Issue invoice',
    message: `Issue ${invoice.value.invoice_number} for ${formatMoney(invoice.value.total_amount)}? PDF generation will be queued.`,
    confirmLabel: 'Issue',
    variant: 'primary',
  })
  if (!ok) return

  acting.value = 'issue'
  try {
    await issueOne(invoice.value)
    pageMessage.value = 'Invoice issued. PDF generation queued.'
    await load()
  } catch (e) {
    pageError.value = e.response?.data?.message || 'Could not issue invoice.'
  } finally {
    acting.value = ''
  }
}

async function onVoid() {
  const reason = window.prompt('Reason for voiding this invoice (required):')
  if (!reason || reason.trim().length < 3) {
    pageError.value = 'A void reason of at least 3 characters is required.'
    return
  }

  const ok = await confirm({
    title: 'Void invoice',
    message: `Void ${invoice.value.invoice_number}? Linked charges will be released for re-billing. This cannot be undone.`,
    confirmLabel: 'Void invoice',
    variant: 'danger',
  })
  if (!ok) return

  acting.value = 'void'
  try {
    invoice.value = await voidInvoice(invoice.value.id, reason.trim())
    pageMessage.value = 'Invoice voided.'
  } catch (e) {
    pageError.value = e.response?.data?.message || e.response?.data?.errors?.reason?.[0] || 'Could not void invoice.'
  } finally {
    acting.value = ''
  }
}

async function onDownload() {
  try {
    await downloadPdf(invoice.value.id, invoice.value.invoice_number)
  } catch {
    pageError.value = 'PDF not available yet. Issue the invoice and wait for generation.'
  }
}

onMounted(() => load())
</script>
