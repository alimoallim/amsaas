<template>
  <AlertBanner
    v-if="pageError"
    variant="error"
    class="erp-page mb-4"
    :message="pageError"
    @dismiss="pageError = ''"
  />
  <AlertBanner
    v-if="pageMessage"
    variant="success"
    class="erp-page mb-4"
    :message="pageMessage"
    @dismiss="pageMessage = ''"
  />

  <div v-if="loading" class="erp-page py-12 text-center text-slate-500">Loading invoice…</div>

  <ObjectPageLayout
    v-else-if="invoice"
    :breadcrumbs="breadcrumbs"
    :title="invoice.invoice_number"
    :subtitle="invoiceSubtitle"
    :status="invoice.status"
    :status-label="invoice.status"
    :attributes="attributes"
    :tabs="tabs"
    initial-tab="overview"
  >
    <template #actions>
      <ErpButton variant="ghost" size="sm" :to="{ name: 'MonthlyInvoices' }">Back to list</ErpButton>
      <ErpButton
        v-if="invoice.controls?.can_issue"
        :loading="acting === 'issue'"
        @click="onIssue"
      >
        Issue invoice
      </ErpButton>
      <ErpButton
        v-if="invoice.controls?.can_download"
        variant="secondary"
        size="sm"
        @click="onDownload"
      >
        Download PDF
      </ErpButton>
      <ErpButton
        v-if="invoice.controls?.can_void"
        variant="danger"
        size="sm"
        :loading="acting === 'void'"
        @click="openVoidModal"
      >
        Void
      </ErpButton>
      <ErpButton
        v-if="invoice.controls?.can_credit_note"
        variant="danger"
        size="sm"
        :loading="acting === 'credit_note'"
        @click="openCreditNoteModal"
      >
        Credit note
      </ErpButton>
    </template>

    <template #overview>
      <KpiStrip class="mb-6 grid-cols-2 lg:grid-cols-4">
        <KpiCard label="Total" :value="formatMoney(invoice.total_amount)" />
        <KpiCard label="Paid" :value="formatMoney(invoice.paid_amount)" />
        <KpiCard label="Balance due" :value="formatMoney(invoice.balance_due)" variant="accent" />
        <KpiCard label="Billing period" :value="invoice.billing_period || '—'" />
      </KpiStrip>

      <div class="grid gap-4 lg:grid-cols-3">
        <FormSection title="Billing context" class="lg:col-span-1">
          <dl class="space-y-2 text-sm">
            <div class="flex justify-between gap-3">
              <dt class="text-slate-500">Issue date</dt>
              <dd class="font-medium">{{ invoice.issue_date || '—' }}</dd>
            </div>
            <div class="flex justify-between gap-3">
              <dt class="text-slate-500">Due date</dt>
              <dd class="font-medium">{{ invoice.due_date || '—' }}</dd>
            </div>
            <div class="flex justify-between gap-3">
              <dt class="text-slate-500">Unit</dt>
              <dd class="font-medium">{{ invoice.apartment?.unit_number || '—' }}</dd>
            </div>
            <div class="flex justify-between gap-3">
              <dt class="text-slate-500">Building</dt>
              <dd class="font-medium">{{ invoice.building?.name || '—' }}</dd>
            </div>
            <div class="flex justify-between gap-3">
              <dt class="text-slate-500">Tenant</dt>
              <dd class="font-medium">{{ tenantLabel }}</dd>
            </div>
          </dl>
          <div
            v-if="invoice.void_reason"
            class="mt-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-800 dark:border dark:border-red-800/50 dark:bg-red-950/40 dark:text-red-300"
          >
            <p class="text-xs font-semibold uppercase tracking-wide">Void reason</p>
            <p class="mt-1">{{ invoice.void_reason }}</p>
          </div>
          <div
            v-if="invoice.credit_note_reason"
            class="mt-4 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border dark:border-amber-800/50 dark:bg-amber-950/40 dark:text-amber-200"
          >
            <p class="text-xs font-semibold uppercase tracking-wide">Credit note</p>
            <p v-if="invoice.credit_note_number" class="mt-1 font-mono text-xs">{{ invoice.credit_note_number }}</p>
            <p class="mt-1">{{ invoice.credit_note_reason }}</p>
          </div>
          <div v-if="invoice.agreement?.id" class="mt-4">
            <ErpButton
              variant="ghost"
              size="sm"
              :to="{ name: 'RentalAgreementShow', params: { id: invoice.agreement.id } }"
            >
              View rental agreement
            </ErpButton>
          </div>
        </FormSection>

        <FormSection title="Line items" class="lg:col-span-2">
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

          <div class="mt-4 grid gap-1 border-t border-slate-200 pt-4 text-sm dark:border-slate-700">
            <div class="flex justify-between text-slate-600">
              <span>Rent</span>
              <span class="font-mono">{{ formatMoney(invoice.subtotal_rent) }}</span>
            </div>
            <div class="flex justify-between text-slate-600">
              <span>Utilities</span>
              <span class="font-mono">{{ formatMoney(invoice.subtotal_utilities) }}</span>
            </div>
            <div class="flex justify-between text-slate-600">
              <span>Services</span>
              <span class="font-mono">{{ formatMoney(invoice.subtotal_services) }}</span>
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
        </FormSection>
      </div>
    </template>

    <template #payments>
      <DataTable
        v-if="invoice.payment_allocations?.length"
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
          <span class="text-sm">{{ row.payment?.payment_date || '—' }}</span>
        </template>
        <template #cell-payment_method="{ row }">
          <span class="text-sm">{{ row.payment?.payment_method || '—' }}</span>
        </template>
        <template #cell-amount_allocated="{ row }">
          <span class="font-mono text-sm tabular-nums">{{ formatMoney(row.amount_allocated) }}</span>
        </template>
      </DataTable>
      <p v-else class="text-sm text-slate-500">No payments allocated to this invoice yet.</p>
    </template>
  </ObjectPageLayout>

  <div v-else class="erp-page py-12 text-center text-slate-500">Invoice not found.</div>

  <ErpModal
    :open="voidModal.open"
    title="Void invoice"
    subtitle="Linked charges will be released for re-billing. This cannot be undone."
    confirm-label="Void invoice"
    confirm-variant="danger"
    :loading="acting === 'void'"
    @close="voidModal.open = false"
    @confirm="onVoid"
  >
    <FormField label="Reason" required class="mt-2">
      <textarea
        v-model="voidModal.reason"
        class="erp-input min-h-[88px]"
        placeholder="Required — at least 3 characters"
      />
    </FormField>
  </ErpModal>

  <ErpModal
    :open="creditNoteModal.open"
    title="Issue credit note"
    subtitle="Reverses revenue and AR in the ledger. Use when an issued invoice must be corrected."
    confirm-label="Issue credit note"
    confirm-variant="danger"
    :loading="acting === 'credit_note'"
    @close="creditNoteModal.open = false"
    @confirm="onCreditNote"
  >
    <FormField label="Reason" required class="mt-2">
      <textarea
        v-model="creditNoteModal.reason"
        class="erp-input min-h-[88px]"
        placeholder="Required — at least 3 characters"
      />
    </FormField>
  </ErpModal>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useMonthlyInvoices } from '@/composables/useMonthlyInvoices'
import { useConfirm } from '@/composables/useConfirm'
import { tenantDisplayName } from '@/utils/tenantDisplayName'
import {
  ObjectPageLayout,
  DataTable,
  ErpButton,
  KpiCard,
  KpiStrip,
  FormSection,
  FormField,
  ErpModal,
  AlertBanner,
} from '@/components/erp'

const route = useRoute()
const { confirm } = useConfirm()
const { fetchOne, issueOne, voidInvoice, creditNoteInvoice, downloadPdf } = useMonthlyInvoices()

const invoice = ref(null)
const loading = ref(false)
const acting = ref('')
const pageError = ref('')
const pageMessage = ref('')
const voidModal = reactive({ open: false, reason: '' })
const creditNoteModal = reactive({ open: false, reason: '' })

const tenantLabel = computed(() => tenantDisplayName(invoice.value?.tenant) || '—')

const breadcrumbs = computed(() => [
  { label: 'Invoices', to: { name: 'MonthlyInvoices' } },
  { label: invoice.value?.invoice_number || '…' },
])

const invoiceSubtitle = computed(() => {
  if (!invoice.value) return ''
  const parts = [tenantLabel.value]
  if (invoice.value.building?.name) parts.push(invoice.value.building.name)
  return parts.filter(Boolean).join(' · ')
})

const attributes = computed(() => {
  if (!invoice.value) return []
  return [
    invoice.value.billing_period,
    invoice.value.apartment?.unit_number,
    invoice.value.building?.name,
  ].filter(Boolean)
})

const tabs = [
  { id: 'overview', label: 'Overview' },
  { id: 'payments', label: 'Payments' },
]

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
    message: `Issue ${invoice.value.invoice_number} for ${formatMoney(invoice.value.total_amount)}? A PDF will be generated for download.`,
    confirmLabel: 'Issue',
    variant: 'primary',
  })
  if (!ok) return

  acting.value = 'issue'
  try {
    await issueOne(invoice.value)
    pageMessage.value = 'Invoice issued. PDF is ready for download.'
    await load()
  } catch (e) {
    pageError.value = e.response?.data?.message || 'Could not issue invoice.'
  } finally {
    acting.value = ''
  }
}

function openVoidModal() {
  voidModal.reason = ''
  voidModal.open = true
}

function openCreditNoteModal() {
  creditNoteModal.reason = ''
  creditNoteModal.open = true
}

async function onCreditNote() {
  const reason = creditNoteModal.reason.trim()
  if (reason.length < 3) {
    pageError.value = 'A credit note reason of at least 3 characters is required.'
    return
  }

  const ok = await confirm({
    title: 'Issue credit note',
    message: `Issue credit note for ${invoice.value.invoice_number}? GL entries will be reversed.`,
    confirmLabel: 'Issue credit note',
    variant: 'danger',
  })
  if (!ok) return

  acting.value = 'credit_note'
  try {
    invoice.value = await creditNoteInvoice(invoice.value.id, reason)
    creditNoteModal.open = false
    pageMessage.value = 'Credit note issued.'
  } catch (e) {
    pageError.value = e.response?.data?.message || e.response?.data?.errors?.reason?.[0] || 'Could not issue credit note.'
  } finally {
    acting.value = ''
  }
}

async function onVoid() {
  const reason = voidModal.reason.trim()
  if (reason.length < 3) {
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
    invoice.value = await voidInvoice(invoice.value.id, reason)
    voidModal.open = false
    pageMessage.value = 'Invoice voided.'
  } catch (e) {
    pageError.value = e.response?.data?.message || e.response?.data?.errors?.reason?.[0] || 'Could not void invoice.'
  } finally {
    acting.value = ''
  }
}

async function onDownload() {
  pageError.value = ''
  try {
    await downloadPdf(invoice.value.id, invoice.value.invoice_number)
  } catch (e) {
    const blob = e.response?.data
    if (blob instanceof Blob) {
      try {
        const text = await blob.text()
        const parsed = JSON.parse(text)
        pageError.value = parsed.message || 'Could not download invoice PDF.'
        return
      } catch {
        /* fall through */
      }
    }
    pageError.value = e.response?.data?.message || 'Could not download invoice PDF.'
  }
}

onMounted(() => load())
</script>
