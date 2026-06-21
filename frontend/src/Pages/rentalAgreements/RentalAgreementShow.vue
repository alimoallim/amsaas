<template>
  <div class="erp-page">
    <AlertBanner
      v-if="pageError"
      variant="error"
      :message="pageError"
      class="mb-4"
      @dismiss="pageError = ''"
    />
    <AlertBanner
      v-if="pageSuccess"
      variant="success"
      :message="pageSuccess"
      class="mb-4"
      @dismiss="pageSuccess = ''"
    />

    <div v-if="loading" class="flex flex-col items-center justify-center py-24 text-center">
      <div class="mb-4 h-10 w-10 animate-spin rounded-full border-2 border-indigo-600 border-t-transparent" />
      <p class="text-sm text-slate-500">Loading rental agreement…</p>
    </div>

    <ObjectPageLayout
      v-else-if="agreement"
      :breadcrumbs="breadcrumbs"
      :title="agreement.agreement_number"
      :subtitle="agreementSubtitle"
      :status="agreement.status?.value"
      :status-label="agreement.status?.label"
      :attributes="headerAttributes"
      :tabs="tabs"
      initial-tab="overview"
    >
      <template #actions>
        <ErpButton variant="ghost" size="sm" :to="{ name: 'RentalAgreementIndex' }">Back</ErpButton>
        <ErpButton
          v-if="controls.can_edit"
          variant="secondary"
          size="sm"
          :to="{ name: 'RentalAgreementEdit', params: { id: agreement.id } }"
        >
          Edit
        </ErpButton>
        <ErpButton
          v-if="controls.can_approve"
          variant="secondary"
          size="sm"
          :loading="actionLoading"
          @click="onApprove"
        >
          Approve
        </ErpButton>
        <ErpButton
          v-if="controls.can_activate"
          variant="success"
          size="sm"
          :loading="actionLoading"
          @click="onActivate"
        >
          Activate
        </ErpButton>
        <ErpButton variant="ghost" size="sm" @click="onPrint">Print</ErpButton>
        <ErpButton
          v-if="agreement.status?.value === 'active'"
          variant="primary"
          size="sm"
          :loading="generatingInvoice"
          @click="openGenerateModal"
        >
          Sync to invoice
        </ErpButton>
        <ErpButton
          v-if="controls.can_record_deposit"
          variant="secondary"
          size="sm"
          @click="openFinancialModal('security_deposit')"
        >
          Receive deposit
        </ErpButton>
        <ErpButton
          v-if="controls.can_apply_deposit && unpaidInvoices.length"
          variant="secondary"
          size="sm"
          @click="openApplyDepositModal"
        >
          Apply deposit
        </ErpButton>
        <ErpButton
          v-if="controls.can_refund_deposit"
          variant="secondary"
          size="sm"
          @click="openFinancialModal('deposit_refund')"
        >
          Refund deposit
        </ErpButton>
        <ErpButton
          v-if="controls.can_record_payment && unpaidInvoices.length"
          variant="success"
          size="sm"
          @click="openFinancialModal('rent')"
        >
          Record rent payment
        </ErpButton>
        <ErpButton
          v-if="controls.can_terminate"
          variant="danger"
          size="sm"
          @click="openTerminateModal"
        >
          End lease
        </ErpButton>
        <ErpButton
          v-if="controls.can_delete"
          variant="danger"
          size="sm"
          @click="openDeleteModal"
        >
          Delete
        </ErpButton>
      </template>

      <template #overview>
        <KpiStrip class="mb-6 grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
          <KpiCard label="Monthly rent" :value="formatMoney(agreement.financials?.monthly_rent, currency)" />
          <KpiCard
            label="Security deposit"
            :value="formatMoney(agreement.financials?.security_deposit, currency)"
            :caption="depositLedgerCaption"
          />
          <KpiCard
            v-if="depositLedger"
            label="Deposit on hand"
            :value="formatMoney(depositLedger.available, currency)"
            :caption="depositOnHandCaption"
            variant="accent"
          />
          <KpiCard
            v-if="depositOutstanding > 0.009"
            label="Deposit to collect"
            :value="formatMoney(depositOutstanding, currency)"
            caption="Contractual minus received"
            variant="warning"
          />
          <KpiCard
            v-for="usage in billingSummary.utilityUsage"
            :key="usage.utility_type"
            :label="usage.utility_label"
            :value="formatConsumption(usage)"
            :caption="utilityUsageCaption(usage)"
          />
          <KpiCard
            label="Outstanding"
            :value="formatMoney(outstandingBalance, currency)"
            variant="accent"
          />
          <KpiCard
            label="Billing total"
            :value="billingSummary.formatMoney(billingSummary.fixedMonthlyTotal)"
            :caption="billingTotalCaption"
          />
        </KpiStrip>

        <div class="grid gap-6 lg:grid-cols-3">
          <div class="space-y-6 lg:col-span-2">
            <ErpPanel
              v-if="billingSummary.utilityUsage.length"
              title="Utility consumption"
              subtitle="Latest meter readings for this unit (consumption × rate)"
            >
              <div class="overflow-x-auto -mx-1">
                <table class="min-w-full text-sm">
                  <thead>
                    <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                      <th class="px-3 py-2">Utility</th>
                      <th class="px-3 py-2">Reading date</th>
                      <th class="px-3 py-2 text-right">Consumption</th>
                      <th class="px-3 py-2 text-right">Rate</th>
                      <th class="px-3 py-2 text-right">Amount</th>
                      <th class="px-3 py-2">Status</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100">
                    <tr v-for="row in billingSummary.utilityUsage" :key="row.reading_id || row.utility_type">
                      <td class="px-3 py-3 font-medium text-slate-900">
                        {{ row.utility_label }}
                        <span v-if="row.meter_number" class="block font-mono text-xs font-normal text-slate-500">
                          {{ row.meter_number }}
                        </span>
                      </td>
                      <td class="px-3 py-3 text-slate-600">{{ row.reading_date || '—' }}</td>
                      <td class="px-3 py-3 text-right font-mono tabular-nums text-slate-900">
                        {{ formatConsumption(row) }}
                      </td>
                      <td class="px-3 py-3 text-right tabular-nums text-slate-600">
                        {{
                          row.unit_rate != null
                            ? `${formatMoney(row.unit_rate, currency)} / ${row.measurement_unit || 'unit'}`
                            : '—'
                        }}
                      </td>
                      <td class="px-3 py-3 text-right font-medium tabular-nums text-slate-900">
                        <template v-if="row.amount != null">
                          {{ formatMoney(row.amount, currency) }}
                          <span v-if="row.amount_is_estimated" class="text-xs font-normal text-amber-700">est.</span>
                        </template>
                        <span v-else class="text-slate-400">—</span>
                      </td>
                      <td class="px-3 py-3">
                        <StatusBadge
                          :status="row.reading_status"
                          :label="row.reading_status_label"
                        />
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <p
                v-if="billingSummary.utilityUsage.some((u) => u.reading_status !== 'approved')"
                class="mt-3 text-xs text-amber-800"
              >
                Approve readings in
                <router-link :to="{ name: 'MeterReadings' }" class="font-medium underline">
                  Meter readings
                </router-link>
                to post utility charges to billing.
              </p>
            </ErpPanel>

            <ErpPanel title="Agreement overview" subtitle="Contract terms and lifecycle">
              <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <DetailField label="Agreement type" :value="capitalize(agreement.agreement_type)" />
                <DetailField label="Status" :value="agreement.status?.label" />
                <DetailField label="Start date" :value="agreement.dates?.start_date" />
                <DetailField label="End date" :value="agreement.dates?.end_date || 'Open'" />
                <DetailField label="Signed at" :value="agreement.dates?.signed_at || '—'" />
                <DetailField label="Approved at" :value="agreement.dates?.approved_at || '—'" />
                <DetailField label="Billing cycle" :value="agreement.financials?.billing_cycle" />
                <DetailField label="Payment due day" :value="agreement.financials?.payment_due_day" />
                <DetailField label="Currency" :value="currency" />
                <DetailField
                  label="Auto renew"
                  :value="agreement.renewal?.auto_renew ? 'Enabled' : 'Disabled'"
                />
                <div
                  v-if="agreement.termination?.termination_reason"
                  class="sm:col-span-2 lg:col-span-3"
                >
                  <DetailField
                    label="Termination reason"
                    :value="agreement.termination.termination_reason"
                  />
                </div>
              </dl>
            </ErpPanel>
          </div>

          <div class="space-y-6">
            <ErpPanel title="Tenant" subtitle="Occupant on this lease">
              <div class="flex items-center gap-3">
                <div
                  class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-sm font-bold text-indigo-700"
                >
                  {{ tenantInitials }}
                </div>
                <div class="min-w-0">
                  <p class="font-semibold text-slate-900">{{ tenantDisplayName(agreement.tenant) || '—' }}</p>
                  <p class="text-sm text-slate-500">{{ agreement.tenant?.phone || '—' }}</p>
                </div>
              </div>
              <dl class="mt-4 grid gap-3">
                <DetailField label="Tenant code" :value="agreement.tenant?.tenant_code" />
                <DetailField label="Email" :value="agreement.tenant?.email" />
              </dl>
              <ErpButton
                v-if="agreement.tenant?.id"
                variant="ghost"
                size="sm"
                class="mt-4"
                :to="{ name: 'TenantEdit', params: { id: agreement.tenant.id } }"
              >
                Open tenant
              </ErpButton>
            </ErpPanel>

            <ErpPanel title="Property" subtitle="Unit linked to this agreement">
              <dl class="grid gap-3">
                <DetailField label="Building" :value="agreement.apartment?.building?.name" />
                <DetailField label="Unit" :value="agreement.apartment?.unit_number" />
                <DetailField label="Floor" :value="agreement.apartment?.floor" />
                <DetailField label="Inventory" :value="agreement.apartment?.inventory_status" />
                <DetailField label="Bedrooms" :value="agreement.apartment?.bedrooms" />
                <DetailField label="Bathrooms" :value="agreement.apartment?.bathrooms" />
              </dl>
              <ErpButton
                v-if="agreement.apartment?.id"
                variant="ghost"
                size="sm"
                class="mt-4"
                :to="{ name: 'ApartmentShow', params: { id: agreement.apartment.id } }"
              >
                Open unit
              </ErpButton>
            </ErpPanel>
          </div>
        </div>
      </template>

      <template #billing>
        <ErpPanel>
          <template #header>
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div>
                <h2 class="text-sm font-semibold text-slate-900">Billing configuration</h2>
                <p class="mt-0.5 text-xs text-slate-500">
                  Linked charge models and expected amounts (read-only)
                </p>
              </div>
              <ErpButton
                v-if="controls.can_edit"
                variant="ghost"
                size="sm"
                :to="{ name: 'RentalAgreementEdit', params: { id: agreement.id } }"
              >
                Edit billing
              </ErpButton>
            </div>
          </template>

          <EmptyState
            v-if="!billingSummary.lines.length"
            title="No recurring charges"
            description="Configure rent and recurring charges on the agreement."
          >
            <template v-if="controls.can_edit" #action>
              <ErpButton :to="{ name: 'RentalAgreementEdit', params: { id: agreement.id } }">
                Configure billing
              </ErpButton>
            </template>
          </EmptyState>

          <div v-else class="overflow-x-auto -mx-1">
            <table class="min-w-full text-sm">
              <thead>
                <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                  <th class="px-3 py-2">Charge</th>
                  <th class="px-3 py-2">Policy</th>
                  <th class="px-3 py-2 text-right">Expected</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="line in billingSummary.lines" :key="line.id">
                  <td class="px-3 py-3 align-top">
                    <div class="font-medium text-slate-900">{{ line.name }}</div>
                    <div v-if="line.code" class="font-mono text-xs text-slate-500">{{ line.code }}</div>
                    <p v-if="line.note" class="mt-1 text-xs text-slate-500">{{ line.note }}</p>
                  </td>
                  <td class="px-3 py-3 align-top">
                    <span class="inline-flex rounded-md bg-violet-50 px-2 py-0.5 text-xs font-medium text-violet-800">
                      {{ line.policyLabel }}
                    </span>
                  </td>
                  <td class="px-3 py-3 align-top text-right font-medium tabular-nums text-slate-900">
                    {{ line.expectedDisplay }}
                  </td>
                </tr>
              </tbody>
              <tfoot>
                <tr class="border-t border-slate-200 bg-slate-50/80">
                  <td colspan="2" class="px-3 py-3 font-semibold text-slate-700">
                    Estimated billing total
                    <span class="mt-0.5 block text-xs font-normal text-slate-500">
                      Rent, recurring fees, and metered utilities from latest readings
                      <template v-if="billingSummary.hasOneTimeCharges"> · one-time charges (e.g. deposit) excluded</template>
                      <template v-if="billingSummary.hasEstimatedAmounts"> · includes estimated utility amounts</template>
                      <template v-else-if="billingSummary.hasVariableCharges"> · some metered lines need a unit rate on the charge model</template>
                    </span>
                  </td>
                  <td class="px-3 py-3 text-right text-base font-bold tabular-nums text-slate-900">
                    {{ billingSummary.formatMoney(billingSummary.estimatedMonthlyTotal) }}
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>

        </ErpPanel>
      </template>

      <template #invoices>
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <p class="text-sm text-slate-600">
            Monthly invoices for this unit and lease.
            <router-link :to="{ name: 'Invoices' }" class="font-medium text-indigo-600 hover:text-indigo-800">
              Billing operations
            </router-link>
          </p>
          <input
            v-model="invoiceSearch"
            type="search"
            class="erp-input max-w-xs"
            placeholder="Search invoice number…"
          />
        </div>

        <DataTable
          :columns="invoiceColumns"
          :rows="filteredInvoices"
          :loading="invoicesLoading"
          empty-title="No invoices yet"
          :empty-description="
            agreement.status?.value === 'active'
              ? 'Generate a monthly invoice or run billing consolidation.'
              : 'Invoices appear once the agreement is active and billing runs.'
          "
        >
          <template v-if="agreement.status?.value === 'active'" #emptyAction>
            <ErpButton size="sm" @click="openGenerateModal">Sync to invoice</ErpButton>
          </template>
          <template #cell-invoice_number="{ row }">
            <code class="text-xs font-medium">{{ row.invoice_number }}</code>
          </template>
          <template #cell-period="{ row }">
            <span class="text-xs text-slate-600">{{ row.periodLabel }}</span>
          </template>
          <template #cell-total_amount="{ row }">
            <span class="tabular-nums">{{ formatMoney(row.total_amount, currency) }}</span>
          </template>
          <template #cell-balance_due="{ row }">
            <span class="tabular-nums font-medium" :class="row.balance_due > 0 ? 'text-red-700' : 'text-slate-600'">
              {{ formatMoney(row.balance_due, currency) }}
            </span>
          </template>
          <template #cell-status="{ row }">
            <StatusBadge :status="row.status" :label="invoiceStatusLabel(row.status)" />
          </template>
          <template #cell-actions="{ row }">
            <RowActionsMenu :actions="invoiceActions(row)" />
          </template>
        </DataTable>
      </template>
    </ObjectPageLayout>

    <div v-else class="py-16 text-center">
      <EmptyState title="Agreement not found" description="It may have been removed or you lack access.">
        <template #action>
          <ErpButton :to="{ name: 'RentalAgreementIndex' }">Back to list</ErpButton>
        </template>
      </EmptyState>
    </div>

    <ErpModal
      :open="confirm.open"
      :title="confirm.title"
      :subtitle="confirm.subtitle"
      :confirm-label="confirm.confirmLabel"
      :confirm-variant="confirm.variant"
      :loading="confirm.loading"
      @close="confirm.open = false"
      @confirm="runConfirm"
    >
      <FormField
        v-if="confirm.action === 'terminate'"
        label="Termination reason"
        required
        class="mt-2"
      >
        <textarea
          v-model="confirm.terminationReason"
          class="erp-input min-h-[88px]"
          placeholder="Required — e.g. lease ended, tenant vacated…"
        />
      </FormField>
      <div v-else-if="confirm.action === 'generate'" class="mt-3 grid grid-cols-2 gap-3">
        <FormField label="Billing year">
          <select v-model.number="generateForm.year" class="erp-select">
            <option v-for="y in billingYears" :key="y" :value="y">{{ y }}</option>
          </select>
        </FormField>
        <FormField label="Billing month">
          <select v-model.number="generateForm.month" class="erp-select">
            <option v-for="(name, idx) in monthNames" :key="idx" :value="idx + 1">{{ name }}</option>
          </select>
        </FormField>
      </div>
      <div v-else-if="confirm.action === 'apply-deposit'" class="mt-3 space-y-4">
        <p class="text-sm text-slate-600">
          Available deposit:
          <span class="font-semibold tabular-nums text-slate-900">
            {{ formatMoney(depositLedger?.available, currency) }}
          </span>
          <span class="text-slate-500"> — posts DR 2120 · CR 1120 (no cash)</span>
        </p>
        <FormField label="Invoice" required>
          <select v-model="depositApplyForm.monthly_invoice_id" class="erp-select">
            <option value="">Select invoice…</option>
            <option v-for="inv in unpaidInvoices" :key="inv.id" :value="inv.id">
              {{ inv.invoice_number }} · {{ inv.periodLabel }} · {{ formatMoney(inv.balance_due, currency) }} due
            </option>
          </select>
        </FormField>
        <FormField label="Amount to apply" required>
          <input
            v-model="depositApplyForm.amount"
            type="number"
            step="0.01"
            min="0.01"
            class="erp-input tabular-nums"
            :placeholder="suggestedDepositApplyAmount"
          />
        </FormField>
        <FormField label="Notes">
          <textarea v-model="depositApplyForm.notes" rows="2" class="erp-input" placeholder="Optional" />
        </FormField>
      </div>
      <div v-else-if="confirm.action === 'financial'" class="mt-3 space-y-4">
        <FormField label="Transaction type" required>
          <select v-model="financialForm.payment_purpose" class="erp-select">
            <option value="rent">Rent (FIFO to open invoices)</option>
            <option value="security_deposit">Security deposit received</option>
            <option value="deposit_refund">Security deposit refund</option>
          </select>
        </FormField>
        <p class="text-xs text-slate-500">{{ financialModalHint }}</p>
        <div
          v-if="financialForm.payment_purpose === 'rent'"
          class="rounded-lg border border-slate-200 bg-slate-50/80 px-3 py-2 text-sm"
        >
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Outstanding on this lease</p>
          <p class="text-lg font-semibold tabular-nums text-slate-900">
            {{ formatMoney(outstandingBalance, currency) }}
          </p>
          <p class="text-xs text-slate-500">Allocated oldest invoice first (FIFO)</p>
        </div>
        <div
          v-else
          class="rounded-lg border border-slate-200 bg-slate-50/80 px-3 py-2 text-sm text-slate-700"
        >
          <p v-if="financialForm.payment_purpose === 'security_deposit'">
            Required {{ formatMoney(depositLedger?.required, currency) }}
            · received {{ formatMoney(depositLedger?.received, currency) }}
            <span v-if="depositOutstanding > 0.009">
              · still to collect {{ formatMoney(depositOutstanding, currency) }}
            </span>
          </p>
          <p v-else>
            Available to refund:
            <span class="font-semibold tabular-nums">{{ formatMoney(depositLedger?.available, currency) }}</span>
          </p>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
          <FormField label="Amount" required>
            <input
              v-model="financialForm.amount"
              type="number"
              step="0.01"
              min="0.01"
              class="erp-input tabular-nums"
            />
          </FormField>
          <FormField label="Payment date" required>
            <input v-model="financialForm.payment_date" type="date" class="erp-input" />
          </FormField>
        </div>
        <FormField label="Payment method" required>
          <select v-model="financialForm.payment_method" class="erp-select">
            <option value="bank_transfer">Bank transfer</option>
            <option value="cash">Cash</option>
            <option value="mobile_money">Mobile money</option>
            <option value="cheque">Cheque</option>
          </select>
        </FormField>
        <FormField label="Reference">
          <input v-model="financialForm.reference_number" type="text" class="erp-input" placeholder="Transfer ref, cheque #…" />
        </FormField>
        <FormField label="Notes">
          <textarea v-model="financialForm.notes" rows="2" class="erp-input" placeholder="Optional" />
        </FormField>
      </div>
    </ErpModal>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted, defineComponent, h } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import { useRentalAgreements } from '@/composables/useRentalAgreements'
import { usePayments } from '@/composables/usePayments'
import { buildBillingSummary } from '@/utils/rentalAgreementBilling'
import { tenantDisplayName } from '@/utils/tenantDisplayName'
import { compactActions } from '@/composables/useTableActions'
import {
  ObjectPageLayout,
  ErpButton,
  ErpPanel,
  ErpModal,
  FormField,
  KpiCard,
  KpiStrip,
  DataTable,
  RowActionsMenu,
  StatusBadge,
  AlertBanner,
  EmptyState,
} from '@/components/erp'

const DetailField = defineComponent({
  name: 'DetailField',
  props: {
    label: { type: String, required: true },
    value: { type: [String, Number], default: '' },
  },
  setup(props) {
    return () =>
      h('div', { class: props.class }, [
        h('dt', { class: 'text-xs font-semibold uppercase tracking-wide text-slate-500' }, props.label),
        h('dd', { class: 'mt-0.5 text-sm font-medium text-slate-900' }, props.value ?? '—'),
      ])
  },
})

const route = useRoute()
const router = useRouter()
const { fetchOne, approve, activate, terminate, remove } = useRentalAgreements()
const { recordPayment, saving: paymentSaving } = usePayments()

const loading = ref(true)
const invoicesLoading = ref(false)
const actionLoading = ref(false)
const generatingInvoice = ref(false)
const agreement = ref(null)
const invoices = ref([])
const invoiceSearch = ref('')
const pageError = ref('')
const pageSuccess = ref('')

const monthNames = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December',
]

const tabs = [
  { id: 'overview', label: 'Overview' },
  { id: 'billing', label: 'Billing' },
  { id: 'invoices', label: 'Invoices' },
]

const invoiceColumns = [
  { key: 'invoice_number', label: 'Invoice', mono: true },
  { key: 'period', label: 'Period' },
  { key: 'due_date', label: 'Due' },
  { key: 'total_amount', label: 'Total', align: 'right' },
  { key: 'balance_due', label: 'Balance', align: 'right' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: '', align: 'right', type: 'actions' },
]

const confirm = reactive({
  open: false,
  loading: false,
  action: null,
  title: '',
  subtitle: '',
  confirmLabel: '',
  variant: 'primary',
  terminationReason: '',
})

const generateForm = reactive({
  year: new Date().getFullYear(),
  month: new Date().getMonth() + 1,
})

const financialForm = reactive({
  payment_purpose: 'rent',
  amount: '',
  payment_date: new Date().toISOString().split('T')[0],
  payment_method: 'bank_transfer',
  reference_number: '',
  notes: '',
})

const depositApplyForm = reactive({
  monthly_invoice_id: '',
  amount: '',
  notes: '',
})

const billingYears = computed(() => {
  const y = new Date().getFullYear()
  return [y - 1, y, y + 1]
})

const controls = computed(() => agreement.value?.controls || {})
const currency = computed(() => agreement.value?.financials?.currency || 'USD')
const depositLedger = computed(() => agreement.value?.financials?.deposit_ledger ?? null)
const depositOutstanding = computed(() => {
  const ledger = depositLedger.value
  if (!ledger) return 0
  return Math.max(0, Number(ledger.required ?? 0) - Number(ledger.received ?? 0))
})

const depositOnHandCaption = computed(() => {
  const ledger = depositLedger.value
  if (!ledger) return ''
  const parts = [`Received ${formatMoney(ledger.received, currency.value)}`]
  if (ledger.applied > 0) {
    parts.push(`applied ${formatMoney(ledger.applied, currency.value)}`)
  }
  if (ledger.refunded > 0) {
    parts.push(`refunded ${formatMoney(ledger.refunded, currency.value)}`)
  }
  return parts.join(' · ')
})

const depositLedgerCaption = computed(() => {
  const ledger = depositLedger.value
  if (!ledger || ledger.received <= 0) {
    if (depositOutstanding.value > 0.009) {
      return `Collect ${formatMoney(depositOutstanding.value, currency.value)}`
    }
    return 'Contractual amount at lease start'
  }
  return depositOnHandCaption.value
})

const financialModalHint = computed(() => {
  if (financialForm.payment_purpose === 'security_deposit') {
    return 'Posts to customer deposits liability (2120). Does not reduce rent invoices.'
  }
  if (financialForm.payment_purpose === 'deposit_refund') {
    return 'Releases deposit liability (2120) back to the receipt account.'
  }
  return 'Payment is allocated to this tenant’s oldest open invoices (FIFO).'
})

const breadcrumbs = computed(() => [
  { label: 'Rental agreements', to: { name: 'RentalAgreementIndex' } },
  { label: agreement.value?.agreement_number || 'Agreement' },
])

const agreementSubtitle = computed(() => {
  const t = tenantDisplayName(agreement.value?.tenant)
  const u = agreement.value?.apartment?.unit_number
  const b = agreement.value?.apartment?.building?.name
  if (!t && !u) return 'Lease operational hub'
  return [t, b && u ? `${b} · ${u}` : b || u].filter(Boolean).join(' — ')
})

const headerAttributes = computed(() => {
  const a = agreement.value
  if (!a) return []
  return [
    a.apartment?.building?.name,
    a.apartment?.unit_number && `Unit ${a.apartment.unit_number}`,
    a.dates?.start_date && `${a.dates.start_date} → ${a.dates.end_date || 'Open'}`,
  ].filter(Boolean)
})

const billingSummary = computed(() => buildBillingSummary(agreement.value))

const billingTotalCaption = computed(() => {
  const n = billingSummary.value.utilityUsage?.length ?? 0
  if (n === 0 && billingSummary.value.hasVariableCharges) {
    return 'Includes unpriced metered lines'
  }
  if (n > 0) {
    return `Includes ${n} utility reading${n === 1 ? '' : 's'}`
  }
  return 'Rent and recurring fees'
})

function formatConsumption(usage) {
  if (usage?.consumption == null) return '—'
  const unit = usage.measurement_unit ? ` ${usage.measurement_unit}` : ''
  return `${Number(usage.consumption).toFixed(2)}${unit}`
}

function utilityUsageCaption(usage) {
  if (usage?.amount != null) {
    return formatMoney(usage.amount, currency.value) + (usage.amount_is_estimated ? ' (est.)' : '')
  }
  return usage?.reading_status_label || 'No charge yet'
}

const tenantInitials = computed(() => {
  const name = tenantDisplayName(agreement.value?.tenant)
  if (!name) return '—'
  return name
    .split(' ')
    .map((n) => n[0])
    .join('')
    .slice(0, 2)
    .toUpperCase()
})

const outstandingBalance = computed(() =>
  invoices.value.reduce((sum, inv) => sum + Number(inv.balance_due || 0), 0)
)

const unpaidInvoices = computed(() =>
  invoices.value.filter((inv) => Number(inv.balance_due) > 0 && inv.status !== 'paid')
)

const selectedApplyInvoice = computed(() =>
  unpaidInvoices.value.find((inv) => inv.id === depositApplyForm.monthly_invoice_id) ?? null,
)

const suggestedDepositApplyAmount = computed(() => {
  const available = Number(depositLedger.value?.available ?? 0)
  const balance = Number(selectedApplyInvoice.value?.balance_due ?? 0)
  if (available <= 0 || balance <= 0) return ''
  return Math.min(available, balance).toFixed(2)
})

const filteredInvoices = computed(() => {
  const q = invoiceSearch.value.trim().toLowerCase()
  if (!q) return invoices.value
  return invoices.value.filter((inv) =>
    String(inv.invoice_number || '').toLowerCase().includes(q)
  )
})

function mapInvoice(raw) {
  const periodLabel = raw.billing_year && raw.billing_month
    ? `${monthNames[raw.billing_month - 1]} ${raw.billing_year}`
    : '—'
  return {
    id: raw.id,
    invoice_number: raw.invoice_number,
    due_date: raw.due_date,
    total_amount: raw.total_amount,
    paid_amount: raw.paid_amount,
    balance_due: raw.balance_due,
    status: raw.status,
    periodLabel,
    billing_year: raw.billing_year,
    billing_month: raw.billing_month,
  }
}

async function loadAgreement() {
  loading.value = true
  pageError.value = ''
  try {
    agreement.value = await fetchOne(route.params.id)
  } catch (err) {
    agreement.value = null
    pageError.value = err.response?.data?.message || 'Failed to load rental agreement.'
  } finally {
    loading.value = false
  }
}

async function loadInvoices() {
  const aptId = agreement.value?.apartment?.id
  const contractId = agreement.value?.id
  if (!aptId) {
    invoices.value = []
    return
  }
  invoicesLoading.value = true
  try {
    const { data } = await api.get('/invoices', {
      params: {
        apartment_id: aptId,
        contract_id: contractId,
        contract_type: 'rental',
        per_page: 50,
      },
    })
    const rows = data.data || []
    invoices.value = (Array.isArray(rows) ? rows : []).map(mapInvoice)
  } catch {
    invoices.value = []
  } finally {
    invoicesLoading.value = false
  }
}

async function refreshAll() {
  await loadAgreement()
  if (agreement.value) {
    await loadInvoices()
  }
}

const agreementRef = () => ({ id: agreement.value.id })

async function onApprove() {
  actionLoading.value = true
  pageError.value = ''
  try {
    await approve(agreementRef(), { refreshList: false })
    pageSuccess.value = 'Agreement approved.'
    await refreshAll()
  } catch (err) {
    pageError.value = err.response?.data?.message || 'Approval failed.'
  } finally {
    actionLoading.value = false
  }
}

async function onActivate() {
  actionLoading.value = true
  pageError.value = ''
  try {
    await activate(agreementRef(), { refreshList: false })
    pageSuccess.value = 'Agreement activated.'
    await refreshAll()
  } catch (err) {
    pageError.value = err.response?.data?.message || 'Activation failed.'
  } finally {
    actionLoading.value = false
  }
}

function openTerminateModal() {
  confirm.action = 'terminate'
  confirm.terminationReason = ''
  confirm.title = 'Terminate agreement?'
  confirm.subtitle = `${agreement.value.agreement_number} will be ended and the unit released.`
  confirm.confirmLabel = 'Terminate'
  confirm.variant = 'danger'
  confirm.open = true
}

function openDeleteModal() {
  confirm.action = 'delete'
  confirm.title = 'Delete draft?'
  confirm.subtitle = `${agreement.value.agreement_number} will be permanently removed.`
  confirm.confirmLabel = 'Delete'
  confirm.variant = 'danger'
  confirm.open = true
}

function openGenerateModal() {
  const now = new Date()
  generateForm.year = now.getFullYear()
  generateForm.month = now.getMonth() + 1
  confirm.action = 'generate'
  confirm.title = 'Sync billing to invoice?'
  confirm.subtitle =
    'Adds approved rent, fees, and utility charges to the monthly invoice for this period. '
    + 'If an invoice already exists, new charges are appended (electricity, steam, etc.).'
  confirm.confirmLabel = 'Sync billing'
  confirm.variant = 'primary'
  confirm.open = true
}

function openApplyDepositModal() {
  const first = unpaidInvoices.value[0]
  depositApplyForm.monthly_invoice_id = first?.id || ''
  const available = Number(depositLedger.value?.available ?? 0)
  const balance = Number(first?.balance_due ?? 0)
  depositApplyForm.amount = available > 0 && balance > 0
    ? Math.min(available, balance).toFixed(2)
    : ''
  depositApplyForm.notes = ''
  confirm.action = 'apply-deposit'
  confirm.title = 'Apply security deposit'
  confirm.subtitle = 'Apply held deposit liability to an open invoice (no new cash receipt).'
  confirm.confirmLabel = 'Apply deposit'
  confirm.variant = 'primary'
  confirm.open = true
}

function suggestedFinancialAmount(purpose) {
  if (purpose === 'rent') {
    const balance = outstandingBalance.value
    return balance > 0 ? balance.toFixed(2) : ''
  }
  if (purpose === 'security_deposit') {
    return depositOutstanding.value > 0.009 ? depositOutstanding.value.toFixed(2) : ''
  }
  if (purpose === 'deposit_refund') {
    const available = Number(depositLedger.value?.available ?? 0)
    return available > 0.009 ? available.toFixed(2) : ''
  }
  return ''
}

function openFinancialModal(purpose = 'rent', amount = '') {
  financialForm.payment_purpose = purpose
  financialForm.payment_date = new Date().toISOString().split('T')[0]
  financialForm.payment_method = 'bank_transfer'
  financialForm.reference_number = ''
  financialForm.notes = ''
  financialForm.amount = amount || suggestedFinancialAmount(purpose)
  confirm.action = 'financial'
  confirm.title = purpose === 'deposit_refund'
    ? 'Refund security deposit'
    : purpose === 'security_deposit'
      ? 'Receive security deposit'
      : 'Record rent payment'
  confirm.subtitle = financialModalHint.value
  confirm.confirmLabel = purpose === 'deposit_refund' ? 'Refund deposit' : 'Record payment'
  confirm.variant = purpose === 'deposit_refund' ? 'danger' : 'success'
  confirm.open = true
}

watch(
  () => financialForm.payment_purpose,
  (purpose) => {
    if (confirm.action === 'financial') {
      financialForm.amount = suggestedFinancialAmount(purpose)
      confirm.title = purpose === 'deposit_refund'
        ? 'Refund security deposit'
        : purpose === 'security_deposit'
          ? 'Receive security deposit'
          : 'Record rent payment'
      confirm.subtitle = financialModalHint.value
      confirm.confirmLabel = purpose === 'deposit_refund' ? 'Refund deposit' : 'Record payment'
      confirm.variant = purpose === 'deposit_refund' ? 'danger' : 'success'
    }
  },
)

watch(
  () => depositApplyForm.monthly_invoice_id,
  () => {
    if (confirm.action === 'apply-deposit') {
      depositApplyForm.amount = suggestedDepositApplyAmount.value
    }
  },
)

async function runConfirm() {
  if (confirm.action === 'terminate' && !confirm.terminationReason.trim()) {
    return
  }
  if (confirm.action === 'apply-deposit') {
    if (!depositApplyForm.monthly_invoice_id) {
      pageError.value = 'Select an invoice to apply the deposit to.'
      return
    }
    if (!depositApplyForm.amount || Number(depositApplyForm.amount) <= 0) {
      pageError.value = 'Enter a valid amount.'
      return
    }
  }
  if (confirm.action === 'financial') {
    if (!financialForm.amount || Number(financialForm.amount) <= 0) {
      pageError.value = 'Enter a valid amount.'
      return
    }
    if (financialForm.payment_purpose === 'deposit_refund') {
      const available = Number(depositLedger.value?.available ?? 0)
      if (Number(financialForm.amount) > available + 0.009) {
        pageError.value = 'Refund amount exceeds available deposit balance.'
        return
      }
    }
  }

  confirm.loading = true
  pageError.value = ''
  try {
    if (confirm.action === 'terminate') {
      await terminate(agreementRef(), confirm.terminationReason.trim(), { refreshList: false })
      pageSuccess.value = 'Agreement terminated.'
      confirm.open = false
      await refreshAll()
    } else if (confirm.action === 'delete') {
      await remove(agreementRef(), { refreshList: false })
      confirm.open = false
      router.push({ name: 'RentalAgreementIndex' })
    } else if (confirm.action === 'generate') {
      generatingInvoice.value = true
      const { data } = await api.post(
        `/rental-agreements/${agreement.value.id}/consolidate-billing`,
        {
          year: generateForm.year,
          month: generateForm.month,
        },
      )
      pageSuccess.value = data.message || 'Billing synced to invoice.'
      confirm.open = false
      await Promise.all([loadInvoices(), refreshAll()])
    } else if (confirm.action === 'apply-deposit') {
      const { data } = await api.post(
        `/rental-agreements/${agreement.value.id}/apply-deposit`,
        {
          monthly_invoice_id: depositApplyForm.monthly_invoice_id,
          amount: Number(depositApplyForm.amount),
          notes: depositApplyForm.notes || undefined,
        },
      )
      pageSuccess.value = data.message || 'Deposit applied to invoice.'
      confirm.open = false
      await refreshAll()
    } else if (confirm.action === 'financial') {
      const tenantId = agreement.value?.tenant?.id
      if (!tenantId) {
        pageError.value = 'Tenant is required to record a payment.'
        return
      }
      const { message } = await recordPayment({
        tenant_id: tenantId,
        agreement_id: agreement.value.id,
        payment_purpose: financialForm.payment_purpose,
        amount: Number(financialForm.amount),
        payment_date: financialForm.payment_date,
        payment_method: financialForm.payment_method,
        reference_number: financialForm.reference_number || undefined,
        notes: financialForm.notes || undefined,
      })
      pageSuccess.value = message || 'Payment recorded.'
      confirm.open = false
      await refreshAll()
    }
  } catch (err) {
    pageError.value = err.response?.data?.message || 'Action failed.'
  } finally {
    confirm.loading = false
    generatingInvoice.value = false
  }
}

function onPrint() {
  window.print()
}

function formatMoney(value, cur = 'USD') {
  const n = Number(value)
  if (Number.isNaN(n)) return '—'
  try {
    return new Intl.NumberFormat(undefined, { style: 'currency', currency: cur || 'USD' }).format(n)
  } catch {
    return `${cur} ${n.toFixed(2)}`
  }
}

function capitalize(s) {
  if (!s) return '—'
  return String(s).replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}

function invoiceStatusLabel(status) {
  const map = {
    draft: 'Draft',
    finalized: 'Finalized',
    partially_paid: 'Partial',
    paid: 'Paid',
    overdue: 'Overdue',
    cancelled: 'Cancelled',
  }
  return map[status] || status
}

function invoiceActions(row) {
  return compactActions([
    {
      key: 'download',
      label: 'Download PDF',
      onClick: () => downloadInvoicePdf(row),
    },
    row.status === 'draft' && {
      key: 'finalize',
      label: 'Finalize',
      variant: 'primary',
      onClick: () => finalizeInvoice(row),
    },
    Number(row.balance_due) > 0 && row.status !== 'paid' && controls.value.can_record_payment && {
      key: 'pay',
      label: 'Record payment',
      variant: 'success',
      onClick: () => openFinancialModal('rent', Number(row.balance_due).toFixed(2)),
    },
  ])
}

async function downloadInvoicePdf(row) {
  pageError.value = ''
  try {
    const res = await api.get(`/invoices/${row.id}/download`, { responseType: 'blob' })
    const blob = new Blob([res.data], { type: res.headers['content-type'] || 'application/pdf' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `${row.invoice_number || 'invoice'}.pdf`
    link.click()
    URL.revokeObjectURL(url)
  } catch (err) {
    const msg = err.response?.data?.message
    pageError.value = typeof msg === 'string' ? msg : 'Invoice PDF is not available yet.'
  }
}

async function finalizeInvoice(row) {
  pageError.value = ''
  try {
    await api.post(`/invoices/${row.id}/finalize`)
    pageSuccess.value = 'Invoice finalized.'
    await loadInvoices()
  } catch (err) {
    pageError.value = err.response?.data?.message || 'Could not finalize invoice.'
  }
}

onMounted(async () => {
  await loadAgreement()
  if (agreement.value) {
    await loadInvoices()
  }
})
</script>

<style scoped>
@media print {
  .erp-page :deep(.erp-tabs),
  .erp-page :deep([class*='actions']) {
    display: none !important;
  }
}
</style>
