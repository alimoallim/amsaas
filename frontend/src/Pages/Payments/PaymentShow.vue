<template>
  <WorklistLayout
    eyebrow="Finance · Payment"
    :title="payment?.receipt_number || 'Payment receipt'"
    description="Allocation breakdown and receipt details."
  >
    <template #actions>
      <ErpButton variant="ghost" size="sm" :to="{ name: 'Payments' }">Back to payments</ErpButton>
    </template>

    <template #kpis>
      <AlertBanner v-if="pageError" variant="error" class="mb-4" :message="pageError" @dismiss="pageError = ''" />

      <div v-if="loading" class="py-10 text-center text-sm text-slate-500">Loading payment…</div>

      <template v-else-if="payment">
        <KpiStrip class="grid-cols-2 lg:grid-cols-4">
          <KpiCard label="Received" :value="formatMoney(payment.amount)" />
          <KpiCard label="Allocated" :value="formatMoney(payment.allocated_amount)" />
          <KpiCard
            label="Unallocated"
            :value="formatMoney(payment.unallocated_amount)"
            :variant="payment.unallocated_amount > 0.009 ? 'warning' : 'default'"
          />
          <KpiCard label="Status">
            <StatusBadge :status="payment.status" :label="payment.status" />
          </KpiCard>
        </KpiStrip>

        <div class="mt-6 grid gap-4 lg:grid-cols-3">
          <section class="rounded-xl border border-slate-200 bg-white p-4 lg:col-span-1">
            <h2 class="text-sm font-semibold text-slate-800">Receipt details</h2>
            <dl class="mt-3 space-y-2 text-sm text-slate-600">
              <div class="flex justify-between gap-3">
                <dt>Payment date</dt>
                <dd class="font-medium text-slate-900">{{ payment.payment_date || '—' }}</dd>
              </div>
              <div class="flex justify-between gap-3">
                <dt>Method</dt>
                <dd class="capitalize">{{ formatMethod(payment.payment_method) }}</dd>
              </div>
              <div v-if="payment.posting?.receipt_account_code" class="flex justify-between gap-3">
                <dt>Receipt account</dt>
                <dd class="text-right text-slate-800">
                  <span class="font-medium">{{ payment.posting.receipt_account_name || '—' }}</span>
                  <span class="ml-1 font-mono text-xs text-slate-500">{{ payment.posting.receipt_account_code }}</span>
                  <span
                    v-if="payment.posting.receipt_account_overridden"
                    class="ml-1 text-xs text-amber-700"
                  >
                    (override)
                  </span>
                </dd>
              </div>
              <div v-if="payment.reference_number" class="flex justify-between gap-3">
                <dt>Reference</dt>
                <dd class="font-mono text-xs">{{ payment.reference_number }}</dd>
              </div>
              <div v-if="payment.tenant" class="flex justify-between gap-3">
                <dt>Tenant</dt>
                <dd class="font-medium text-slate-900">{{ tenantLabel }}</dd>
              </div>
              <div v-if="payment.buyer" class="flex justify-between gap-3">
                <dt>Buyer</dt>
                <dd class="font-medium text-slate-900">{{ payment.buyer.display_name || '—' }}</dd>
              </div>
              <div v-if="payment.recorded_by?.name" class="flex justify-between gap-3">
                <dt>Recorded by</dt>
                <dd>{{ payment.recorded_by.name }}</dd>
              </div>
              <div v-if="payment.recorded_at" class="flex justify-between gap-3">
                <dt>Recorded at</dt>
                <dd class="text-xs">{{ formatTimestamp(payment.recorded_at) }}</dd>
              </div>
            </dl>
            <p v-if="payment.notes" class="mt-4 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700">
              {{ payment.notes }}
            </p>
          </section>

          <section class="rounded-xl border border-slate-200 bg-white p-4 lg:col-span-2">
            <h2 class="text-sm font-semibold text-slate-800">Invoice allocations (FIFO)</h2>
            <DataTable
              class="mt-3"
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
                  class="font-mono text-xs text-blue-700 hover:underline"
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
              class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900"
            >
              {{ formatMoney(payment.unallocated_amount) }} held as tenant credit — applies automatically
              when the next invoice is issued (FIFO).
            </p>
          </section>

          <section
            v-if="payment.journal_entries?.length"
            class="rounded-xl border border-slate-200 bg-white p-4 lg:col-span-3"
          >
            <h2 class="text-sm font-semibold text-slate-800">Ledger postings</h2>
            <p class="mt-1 text-xs text-slate-500">
              Journal entries created from this payment (receipt account and AR / deposit liability).
            </p>
            <div class="mt-4 space-y-4">
              <div
                v-for="entry in payment.journal_entries"
                :key="entry.id"
                class="rounded-lg border border-slate-100 bg-slate-50/80 p-3"
              >
                <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                  <code class="text-xs text-slate-600">{{ entry.entry_number }}</code>
                  <span class="text-xs text-slate-500">{{ entry.entry_date }}</span>
                </div>
                <p class="mt-1 text-sm text-slate-700">{{ entry.description }}</p>
                <table class="mt-3 w-full text-sm">
                  <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                      <th class="pb-1">Account</th>
                      <th class="pb-1 text-right">Debit</th>
                      <th class="pb-1 text-right">Credit</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="line in entry.lines" :key="line.id" class="border-t border-slate-100">
                      <td class="py-1.5">
                        <code class="text-xs">{{ line.account_code }}</code>
                        <span class="ml-2 text-slate-700">{{ line.account_name }}</span>
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
          </section>
        </div>
      </template>
    </template>
  </WorklistLayout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { usePayments } from '@/composables/usePayments'
import { tenantDisplayName } from '@/utils/tenantDisplayName'
import {
  WorklistLayout,
  DataTable,
  ErpButton,
  StatusBadge,
  KpiCard,
  KpiStrip,
  AlertBanner,
} from '@/components/erp'

const route = useRoute()
const { fetchOne } = usePayments()

const payment = ref(null)
const loading = ref(false)
const pageError = ref('')

const tenantLabel = computed(() => tenantDisplayName(payment.value?.tenant) || '—')

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
