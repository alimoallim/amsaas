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

  <div v-if="loading" class="erp-page py-12 text-center text-slate-500">Loading payment…</div>

  <ObjectPageLayout
    v-else-if="payment"
    :breadcrumbs="breadcrumbs"
    :title="payment.receipt_number"
    :subtitle="receiptSubtitle"
    :status="payment.status"
    :status-label="payment.status"
    :attributes="attributes"
    :tabs="tabs"
    initial-tab="overview"
  >
    <template #actions>
      <ErpButton variant="ghost" size="sm" :to="{ name: 'Payments' }">Back to payments</ErpButton>
      <ErpButton
        v-if="payment.controls?.can_refund"
        variant="danger"
        size="sm"
        :loading="refunding"
        @click="openRefundModal"
      >
        Refund
      </ErpButton>
    </template>

    <template #overview>
      <KpiStrip class="mb-6 grid-cols-2 lg:grid-cols-4">
        <KpiCard label="Received" :value="formatMoney(payment.amount)" />
        <KpiCard label="Allocated" :value="formatMoney(payment.allocated_amount)" />
        <KpiCard
          label="Unallocated"
          :value="formatMoney(payment.unallocated_amount)"
          :variant="payment.unallocated_amount > 0.009 ? 'warning' : 'default'"
        />
        <KpiCard label="Payment date" :value="payment.payment_date || '—'" />
      </KpiStrip>

      <div class="grid gap-4 lg:grid-cols-3">
        <FormSection title="Receipt details" class="lg:col-span-1">
          <dl class="space-y-2 text-sm">
            <div class="flex justify-between gap-3">
              <dt class="text-slate-500">Method</dt>
              <dd class="font-medium capitalize">{{ formatMethod(payment.payment_method) }}</dd>
            </div>
            <div v-if="payment.posting?.receipt_account_code" class="flex justify-between gap-3">
              <dt class="text-slate-500">Receipt account</dt>
              <dd class="text-right">
                <span class="font-medium">{{ payment.posting.receipt_account_name || '—' }}</span>
                <span class="ml-1 font-mono text-xs text-slate-500">{{ payment.posting.receipt_account_code }}</span>
                <span v-if="payment.posting.receipt_account_overridden" class="ml-1 text-xs text-amber-700">(override)</span>
              </dd>
            </div>
            <div v-if="payment.reference_number" class="flex justify-between gap-3">
              <dt class="text-slate-500">Reference</dt>
              <dd class="font-mono text-xs">{{ payment.reference_number }}</dd>
            </div>
            <div v-if="payment.tenant" class="flex justify-between gap-3">
              <dt class="text-slate-500">Tenant</dt>
              <dd class="font-medium">{{ tenantLabel }}</dd>
            </div>
            <div v-if="payment.buyer" class="flex justify-between gap-3">
              <dt class="text-slate-500">Buyer</dt>
              <dd class="font-medium">{{ payment.buyer.display_name || '—' }}</dd>
            </div>
            <div v-if="payment.recorded_by?.name" class="flex justify-between gap-3">
              <dt class="text-slate-500">Recorded by</dt>
              <dd>{{ payment.recorded_by.name }}</dd>
            </div>
            <div v-if="payment.recorded_at" class="flex justify-between gap-3">
              <dt class="text-slate-500">Recorded at</dt>
              <dd class="text-xs">{{ formatTimestamp(payment.recorded_at) }}</dd>
            </div>
          </dl>
          <p v-if="payment.notes" class="mt-4 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700 dark:bg-slate-800 dark:text-slate-300">
            {{ payment.notes }}
          </p>
          <div
            v-if="payment.refund_reason"
            class="mt-4 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border dark:border-amber-800/50 dark:bg-amber-950/40 dark:text-amber-200"
          >
            <p class="text-xs font-semibold uppercase tracking-wide">Refund</p>
            <p class="mt-1">{{ formatMoney(payment.refunded_amount) }} — {{ payment.refund_reason }}</p>
          </div>
        </FormSection>

        <FormSection title="Invoice allocations (FIFO)" class="lg:col-span-2">
          <DataTable
            :columns="allocationColumns"
            :rows="payment.allocations || []"
            :loading="false"
            empty-title="Nothing allocated"
            empty-description="Payment was recorded but no open invoice balance was available."
          >
            <template #cell-invoice_number="{ row }">
              <RouterLink
                v-if="row.monthly_invoice_id"
                :to="{ name: 'InvoiceShow', params: { id: row.monthly_invoice_id } }"
                class="font-mono text-xs text-indigo-600 hover:underline"
              >
                {{ row.invoice_number || 'INV' }}
              </RouterLink>
              <code v-else class="text-xs font-mono">{{ row.invoice_number || '—' }}</code>
            </template>
            <template #cell-amount_allocated="{ row }">
              <span class="font-mono text-sm tabular-nums">{{ formatMoney(row.amount_allocated) }}</span>
            </template>
          </DataTable>

          <p
            v-if="payment.unallocated_amount > 0.009"
            class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-800/50 dark:bg-amber-950/30 dark:text-amber-200"
          >
            {{ formatMoney(payment.unallocated_amount) }} held as tenant credit — applies automatically
            when the next invoice is issued (FIFO).
          </p>
        </FormSection>
      </div>
    </template>

    <template #ledger>
      <div v-if="payment.journal_entries?.length" class="space-y-4">
        <div
          v-for="entry in payment.journal_entries"
          :key="entry.id"
          class="rounded-lg border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-800/50"
        >
          <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
            <code class="text-xs text-slate-600 dark:text-slate-400">{{ entry.entry_number }}</code>
            <span class="text-xs text-slate-500">{{ entry.entry_date }}</span>
          </div>
          <p class="mt-1 text-sm text-slate-700 dark:text-slate-300">{{ entry.description }}</p>
          <table class="mt-3 w-full text-sm">
            <thead>
              <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                <th class="pb-1">Account</th>
                <th class="pb-1 text-right">Debit</th>
                <th class="pb-1 text-right">Credit</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="line in entry.lines" :key="line.id" class="border-t border-slate-100 dark:border-slate-700">
                <td class="py-1.5">
                  <code class="text-xs">{{ line.account_code }}</code>
                  <span class="ml-2 text-slate-700 dark:text-slate-300">{{ line.account_name }}</span>
                </td>
                <td class="py-1.5 text-right font-mono tabular-nums">
                  {{ line.debit_amount > 0 ? formatMoney(line.debit_amount) : '—' }}
                </td>
                <td class="py-1.5 text-right font-mono tabular-nums">
                  {{ line.credit_amount > 0 ? formatMoney(line.credit_amount) : '—' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <p v-else class="text-sm text-slate-500">No journal entries linked to this payment.</p>
    </template>
  </ObjectPageLayout>

  <div v-else class="erp-page py-12 text-center text-slate-500">Payment not found.</div>

  <ErpModal
    :open="refundModal.open"
    title="Refund payment"
    subtitle="Posts a journal reversal and restores invoice balances. The original receipt is preserved."
    confirm-label="Refund payment"
    confirm-variant="danger"
    :loading="refunding"
    @close="refundModal.open = false"
    @confirm="onRefund"
  >
    <FormField label="Amount" required class="mt-2">
      <input
        v-model="refundModal.amount"
        type="number"
        min="0.01"
        step="0.01"
        class="erp-input"
        :max="payment?.refundable_amount"
      />
      <p v-if="payment?.refundable_amount" class="mt-1 text-xs text-slate-500">
        Refundable balance: {{ formatMoney(payment.refundable_amount) }}
      </p>
    </FormField>
    <FormField label="Reason" required class="mt-2">
      <textarea
        v-model="refundModal.reason"
        class="erp-input min-h-[88px]"
        placeholder="Required — at least 3 characters"
      />
    </FormField>
  </ErpModal>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { usePayments } from '@/composables/usePayments'
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
const { fetchOne, refundPayment } = usePayments()

const payment = ref(null)
const loading = ref(false)
const refunding = ref(false)
const pageError = ref('')
const pageMessage = ref('')
const refundModal = reactive({ open: false, amount: '', reason: '' })

const tenantLabel = computed(() => tenantDisplayName(payment.value?.tenant) || '—')

const breadcrumbs = computed(() => [
  { label: 'Payments', to: { name: 'Payments' } },
  { label: payment.value?.receipt_number || '…' },
])

const receiptSubtitle = computed(() => {
  if (!payment.value) return ''
  const parts = [formatMethod(payment.value.payment_method)]
  if (tenantLabel.value !== '—') parts.push(tenantLabel.value)
  return parts.join(' · ')
})

const attributes = computed(() => {
  if (!payment.value) return []
  return [
    formatMethod(payment.value.payment_method),
    payment.value.payment_date,
    payment.value.posting?.receipt_account_code,
  ].filter(Boolean)
})

const tabs = [
  { id: 'overview', label: 'Overview' },
  { id: 'ledger', label: 'Ledger' },
]

const allocationColumns = [
  { key: 'invoice_number', label: 'Invoice', mono: true },
  { key: 'amount_allocated', label: 'Allocated', align: 'right' },
]

function formatMoney(v) {
  return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(Number(v) || 0)
}

function formatMethod(m) {
  return String(m || '').replace(/_/g, ' ')
}

function formatTimestamp(iso) {
  try {
    return new Date(iso).toLocaleString()
  } catch {
    return iso
  }
}

function openRefundModal() {
  refundModal.amount = String(payment.value?.refundable_amount ?? '')
  refundModal.reason = ''
  refundModal.open = true
}

async function onRefund() {
  const amount = Number(refundModal.amount)
  const reason = refundModal.reason.trim()
  if (!amount || amount <= 0) {
    pageError.value = 'Enter a valid refund amount.'
    return
  }
  if (reason.length < 3) {
    pageError.value = 'A refund reason of at least 3 characters is required.'
    return
  }

  const ok = await confirm({
    title: 'Refund payment',
    message: `Refund ${formatMoney(amount)} on ${payment.value.receipt_number}?`,
    confirmLabel: 'Refund',
    variant: 'danger',
  })
  if (!ok) return

  refunding.value = true
  try {
    const result = await refundPayment(payment.value.id, { amount, reason })
    payment.value = result.payment
    refundModal.open = false
    pageMessage.value = result.message || 'Payment refunded.'
  } catch (e) {
    pageError.value = e.response?.data?.message || e.response?.data?.errors?.amount?.[0] || 'Could not refund payment.'
  } finally {
    refunding.value = false
  }
}

async function load() {
  loading.value = true
  pageError.value = ''
  try {
    payment.value = await fetchOne(route.params.id)
  } catch (e) {
    pageError.value = e.response?.data?.message || 'Payment not found.'
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>
