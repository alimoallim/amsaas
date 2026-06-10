<template>
  <div class="erp-page">
    <AlertBanner
      v-if="pageError"
      variant="error"
      :message="pageError"
      class="mb-4"
      @dismiss="pageError = ''"
    />

    <div v-if="loading" class="flex flex-col items-center justify-center py-24 text-center">
      <div class="mb-4 h-10 w-10 animate-spin rounded-full border-2 border-indigo-600 border-t-transparent" />
      <p class="text-sm text-slate-500 dark:text-slate-400">Loading sale contract…</p>
    </div>

    <ObjectPageLayout
      v-else-if="contract"
      :breadcrumbs="breadcrumbs"
      :title="contract.agreement_number"
      :subtitle="pageSubtitle"
      :status="contract.status?.value"
      :status-label="contract.status?.label"
      :attributes="headerAttributes"
      :tabs="tabs"
      initial-tab="overview"
    >
      <template #actions>
        <ErpButton variant="ghost" size="sm" :to="{ name: 'SaleAgreements' }">Back</ErpButton>
        <ErpButton
          v-if="controls.can_execute"
          size="sm"
          :loading="actionLoading"
          @click="onExecute"
        >
          Execute contract
        </ErpButton>
        <ErpButton
          v-if="canApplyReservationDeposit"
          variant="secondary"
          size="sm"
          :loading="depositApplySaving"
          @click="openDepositApplyModal"
        >
          Apply reservation deposit
        </ErpButton>
        <ErpButton
          v-if="controls.can_record_payment"
          size="sm"
          :loading="actionLoading"
          @click="openPaymentModal"
        >
          Record payment
        </ErpButton>
        <ErpButton
          v-if="controls.can_download_completion_certificate"
          variant="secondary"
          size="sm"
          :loading="actionLoading"
          @click="onDownloadCertificate"
        >
          Completion certificate
        </ErpButton>
        <ErpButton
          v-if="controls.can_download_ownership_transfer_certificate"
          variant="secondary"
          size="sm"
          :loading="actionLoading"
          @click="onDownloadOwnershipCertificate"
        >
          Transfer certificate
        </ErpButton>
        <ErpButton
          v-if="controls.can_download_sales_contract"
          variant="secondary"
          size="sm"
          :loading="actionLoading"
          @click="onDownloadSalesContract"
        >
          Sales contract
        </ErpButton>
        <ErpButton
          v-if="controls.can_download_payment_plan_statement || controls.can_download_installment_schedule"
          variant="secondary"
          size="sm"
          :loading="actionLoading"
          @click="onDownloadPaymentPlanStatement"
        >
          Payment plan PDF
        </ErpButton>
        <ErpButton
          v-if="controls.can_cancel"
          variant="danger"
          size="sm"
          :loading="actionLoading"
          @click="onCancel"
        >
          Cancel draft
        </ErpButton>
      </template>

      <template #overview>
        <KpiStrip class="mb-6 grid-cols-2 md:grid-cols-4 lg:grid-cols-5">
          <KpiCard label="Sale price" :value="formatMoney(financials.sale_price, currency)" />
          <KpiCard label="Paid" :value="formatMoney(financials.paid_amount, currency)" />
          <KpiCard
            label="Running balance"
            :value="formatMoney(financials.balance_due ?? financials.remaining_balance, currency)"
          />
          <KpiCard
            v-if="depositLedger?.has_reservation"
            label="Reservation deposit"
            :value="formatMoney(depositLedger.available, currency)"
            :caption="`Held ${formatMoney(depositLedger.reservation_deposit, currency)}`"
            variant="accent"
          />
          <KpiCard
            :label="isPaymentPlan ? 'Progress' : 'Payment type'"
            :value="isPaymentPlan
              ? `${financials.progress_percent ?? 0}%`
              : 'Cash sale'"
          />
        </KpiStrip>

        <div class="grid gap-6 lg:grid-cols-2">
          <FormSection title="Parties">
            <dl class="space-y-4 text-sm">
              <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Buyer</dt>
                <dd class="mt-1 font-medium text-slate-900 dark:text-slate-100">{{ buyer.full_name || '—' }}</dd>
                <p v-if="buyer.email" class="mt-0.5 text-xs text-slate-500">{{ buyer.email }}</p>
              </div>
              <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Unit</dt>
                <dd class="mt-1 font-medium text-slate-900 dark:text-slate-100">
                  {{ apartment.building?.name || '—' }} · Unit {{ apartment.unit_number || '—' }}
                </dd>
                <RouterLink
                  v-if="apartment.id"
                  :to="{ name: 'ApartmentShow', params: { id: apartment.id } }"
                  class="mt-1 inline-block text-xs font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
                >
                  View unit
                </RouterLink>
              </div>
            </dl>
          </FormSection>

          <FormSection title="Contract status">
            <dl class="space-y-4 text-sm">
              <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Lifecycle</dt>
                <dd class="mt-1 font-medium">{{ contract.status?.label || '—' }}</dd>
              </div>
              <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Unit inventory</dt>
                <dd class="mt-1 font-medium">{{ formatLabel(apartment.inventory_status) }}</dd>
              </div>
              <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Price locked</dt>
                <dd class="mt-1 font-medium">{{ financials.price_locked ? 'Yes' : 'No (draft)' }}</dd>
              </div>
            </dl>
          </FormSection>
        </div>
      </template>

      <template #financials>
        <FormSection title="Commercial terms">
          <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Sale price</dt>
              <dd class="mt-1 font-medium tabular-nums">{{ formatMoney(financials.sale_price, currency) }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Down payment</dt>
              <dd class="mt-1 font-medium tabular-nums">{{ formatMoney(financials.down_payment, currency) }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Remaining balance</dt>
              <dd class="mt-1 font-medium tabular-nums">{{ formatMoney(financials.remaining_balance, currency) }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Currency</dt>
              <dd class="mt-1 font-medium">{{ currency }}</dd>
            </div>
            <div v-if="brokerage.broker_name">
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Broker</dt>
              <dd class="mt-1 font-medium">{{ brokerage.broker_name }}</dd>
            </div>
            <div v-if="brokerage.broker_commission">
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Commission</dt>
              <dd class="mt-1 font-medium tabular-nums">{{ formatMoney(brokerage.broker_commission, currency) }}</dd>
            </div>
          </dl>
        </FormSection>

        <FormSection v-if="payments.length" title="Payments received" class="mt-6">
          <div class="overflow-x-auto">
            <table class="erp-table w-full text-sm">
              <thead>
                <tr>
                  <th class="erp-table-head text-left">Date</th>
                  <th class="erp-table-head text-left">Receipt</th>
                  <th class="erp-table-head text-left">Method</th>
                  <th class="erp-table-head text-right">Amount</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in payments" :key="row.id">
                  <td class="erp-table-cell">{{ row.payment_date || '—' }}</td>
                  <td class="erp-table-cell">{{ row.receipt_number || '—' }}</td>
                  <td class="erp-table-cell">{{ formatLabel(row.payment_method) }}</td>
                  <td class="erp-table-cell text-right tabular-nums">
                    {{ formatMoney(row.amount, currency) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </FormSection>

        <FormSection v-if="isPaymentPlan" title="Payment plan terms" class="mt-6">
          <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Financed amount</dt>
              <dd class="mt-1 font-medium tabular-nums">{{ formatMoney(financials.financed_amount, currency) }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Plan term</dt>
              <dd class="mt-1 font-medium">{{ dates.start_date || '—' }} → {{ dates.end_date || '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Duration</dt>
              <dd class="mt-1 font-medium">{{ planDurationLabel }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Progress</dt>
              <dd class="mt-1 font-medium tabular-nums">{{ financials.progress_percent ?? 0 }}%</dd>
            </div>
          </dl>
        </FormSection>
      </template>

      <template #payment-plan>
        <KpiStrip class="mb-6 grid-cols-2 md:grid-cols-4">
          <KpiCard label="Total price" :value="formatMoney(financials.sale_price, currency)" />
          <KpiCard label="Down payment" :value="formatMoney(financials.down_payment, currency)" />
          <KpiCard label="Financed amount" :value="formatMoney(financials.financed_amount, currency)" />
          <KpiCard label="Running balance" :value="formatMoney(financials.balance_due, currency)" />
        </KpiStrip>

        <div class="mb-6">
          <div class="mb-2 flex items-center justify-between text-sm">
            <span class="text-slate-600 dark:text-slate-400">Collection progress</span>
            <span class="font-medium tabular-nums text-slate-900 dark:text-slate-100">
              {{ financials.progress_percent ?? 0 }}%
            </span>
          </div>
          <div class="h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
            <div
              class="h-full rounded-full bg-indigo-600 transition-all duration-300"
              :style="{ width: `${financials.progress_percent ?? 0}%` }"
            />
          </div>
          <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
            {{ formatMoney(financials.paid_amount, currency) }} collected of
            {{ formatMoney(financials.sale_price, currency) }} total
            <span v-if="paymentPlan.is_term_overdue" class="text-amber-600 dark:text-amber-400">
              · Term ended with outstanding balance
            </span>
            <span v-else-if="paymentPlan.days_remaining != null">
              · {{ paymentPlan.days_remaining }} days remaining
            </span>
          </p>
        </div>

        <FormSection title="Agreement term">
          <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Start date</dt>
              <dd class="mt-1 font-medium">{{ dates.start_date || '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">End date</dt>
              <dd class="mt-1 font-medium">{{ dates.end_date || '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Duration</dt>
              <dd class="mt-1 font-medium">{{ planDurationLabel }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Financed balance</dt>
              <dd class="mt-1 font-medium tabular-nums">{{ formatMoney(paymentPlan.financed_balance, currency) }}</dd>
            </div>
          </dl>
        </FormSection>

        <FormSection v-if="payments.length" title="Payments received" class="mt-6">
          <div class="overflow-x-auto">
            <table class="erp-table w-full text-sm">
              <thead>
                <tr>
                  <th class="erp-table-head text-left">Date</th>
                  <th class="erp-table-head text-left">Receipt</th>
                  <th class="erp-table-head text-left">Method</th>
                  <th class="erp-table-head text-right">Amount</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in payments" :key="row.id">
                  <td class="erp-table-cell">{{ row.payment_date || '—' }}</td>
                  <td class="erp-table-cell">{{ row.receipt_number || '—' }}</td>
                  <td class="erp-table-cell">{{ formatLabel(row.payment_method) }}</td>
                  <td class="erp-table-cell text-right tabular-nums">{{ formatMoney(row.amount, currency) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </FormSection>
        <EmptyState
          v-else
          title="No payments yet"
          description="Record flexible payments against the running balance at any time during the plan term."
          class="mt-6"
        >
          <template v-if="controls.can_record_payment" #action>
            <ErpButton size="sm" @click="openPaymentModal">Record payment</ErpButton>
          </template>
        </EmptyState>
      </template>

      <template #ownership>
        <KpiStrip class="mb-6 grid-cols-2 md:grid-cols-4">
          <KpiCard
            label="Transfer status"
            :value="ownership.ownership_transferred ? 'Transferred' : 'Pending approvals'"
          />
          <KpiCard
            label="Title deed"
            :value="ownership.title_deed_issued ? (ownership.title_deed_number || 'Issued') : 'Not issued'"
          />
          <KpiCard
            label="Transfer date"
            :value="dates.ownership_transfer_date || '—'"
          />
          <KpiCard
            label="Approvals"
            :value="`${ownershipApprovals.length} / 3`"
          />
        </KpiStrip>

        <FormSection title="Approval chain">
          <p class="mb-4 text-sm text-slate-600 dark:text-slate-400">
            Legal ownership transfer requires sign-off from Legal, Finance, and Management after the sale is fully settled.
          </p>
          <div class="space-y-3">
            <div
              v-for="step in ownershipSteps"
              :key="step.id"
              class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 px-4 py-3 dark:border-slate-700"
            >
              <div>
                <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ step.label }}</p>
                <p v-if="step.approval" class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                  Approved {{ step.approval.approved_at }}
                  <span v-if="step.approval.approved_by?.name"> · {{ step.approval.approved_by.name }}</span>
                </p>
                <p v-else class="mt-0.5 text-xs text-amber-600 dark:text-amber-400">Awaiting approval</p>
              </div>
              <div class="flex items-center gap-2">
                <StatusBadge
                  v-if="step.approval"
                  status="paid"
                  label="Approved"
                  :dot="false"
                />
                <ErpButton
                  v-else-if="step.canApprove"
                  size="sm"
                  :loading="actionLoading"
                  @click="onApproveOwnership(step.id)"
                >
                  Approve
                </ErpButton>
              </div>
            </div>
          </div>
        </FormSection>

        <FormSection v-if="controls.can_issue_title_deed" title="Title deed" class="mt-6">
          <p class="mb-4 text-sm text-slate-600 dark:text-slate-400">
            Record the official title deed number once ownership transfer is finalized.
          </p>
          <div class="grid max-w-lg gap-4">
            <FormField label="Title deed number" required>
              <input v-model="titleDeedForm.title_deed_number" type="text" class="erp-input w-full" />
            </FormField>
            <FormField label="Notes">
              <textarea v-model="titleDeedForm.notes" rows="2" class="erp-input w-full" />
            </FormField>
            <div>
              <ErpButton size="sm" :loading="titleDeedSaving" @click="onIssueTitleDeed">
                Record title deed
              </ErpButton>
            </div>
          </div>
        </FormSection>

        <FormSection title="Legal documents" class="mt-6">
          <div class="flex flex-wrap gap-2">
            <ErpButton
              v-if="controls.can_download_completion_certificate"
              variant="secondary"
              size="sm"
              :loading="actionLoading"
              @click="onDownloadCertificate"
            >
              Completion certificate
            </ErpButton>
            <ErpButton
              v-if="controls.can_download_ownership_transfer_certificate"
              variant="secondary"
              size="sm"
              :loading="actionLoading"
              @click="onDownloadOwnershipCertificate"
            >
              Ownership transfer certificate
            </ErpButton>
            <ErpButton
              v-if="controls.can_download_sales_contract"
              variant="secondary"
              size="sm"
              :loading="actionLoading"
              @click="onDownloadSalesContract"
            >
              Sales contract PDF
            </ErpButton>
            <ErpButton
              v-if="controls.can_download_payment_plan_statement || controls.can_download_installment_schedule"
              variant="secondary"
              size="sm"
              :loading="actionLoading"
              @click="onDownloadPaymentPlanStatement"
            >
              Payment plan PDF
            </ErpButton>
          </div>
        </FormSection>
      </template>

      <template #notes>
        <FormSection title="Notes & terms">
          <p
            v-if="notes.agreement_notes"
            class="whitespace-pre-wrap text-sm leading-relaxed text-slate-600 dark:text-slate-400"
          >
            {{ notes.agreement_notes }}
          </p>
          <p
            v-if="notes.special_terms"
            class="mt-4 whitespace-pre-wrap text-sm leading-relaxed text-slate-600 dark:text-slate-400"
          >
            <span class="font-medium text-slate-700 dark:text-slate-300">Special terms:</span>
            {{ notes.special_terms }}
          </p>
          <EmptyState
            v-if="!notes.agreement_notes && !notes.special_terms"
            title="No notes"
            description="Contract notes and special terms appear here."
          />
        </FormSection>

        <FormSection title="Key dates" class="mt-6">
          <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Contract date</dt>
              <dd class="mt-1 font-medium">{{ dates.start_date || '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Signed</dt>
              <dd class="mt-1 font-medium">{{ dates.signed_at || '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Approved</dt>
              <dd class="mt-1 font-medium">{{ dates.approved_at || '—' }}</dd>
            </div>
          </dl>
        </FormSection>
      </template>
    </ObjectPageLayout>

    <EmptyState
      v-else
      title="Contract not found"
      description="This sale agreement may have been removed or you do not have access."
    >
      <template #action>
        <ErpButton :to="{ name: 'SaleAgreements' }">Back to contracts</ErpButton>
      </template>
    </EmptyState>

    <FormModal
      :open="depositApplyModalOpen"
      title="Apply reservation deposit"
      save-label="Apply deposit"
      :saving="depositApplySaving"
      @close="depositApplyModalOpen = false"
      @save="onSubmitDepositApply"
    >
      <AlertBanner
        v-if="depositApplyError"
        variant="error"
        :message="depositApplyError"
        class="mb-4"
        @dismiss="depositApplyError = ''"
      />
      <p class="mb-4 text-sm text-slate-600 dark:text-slate-400">
        Available reservation deposit:
        <span class="font-medium tabular-nums text-slate-900 dark:text-slate-100">
          {{ formatMoney(depositLedger?.available, currency) }}
        </span>
        <span v-if="depositLedger?.reservation_number" class="text-slate-500">
          · {{ depositLedger.reservation_number }}
        </span>
      </p>
      <p class="mb-4 text-xs text-slate-500 dark:text-slate-400">
        Posts DR 2120 Customer Deposits · CR 1120 AR — reduces contract balance without new cash.
      </p>
      <div class="space-y-4">
        <FormField label="Amount to apply" :error="depositApplyFieldError('amount')" required>
          <input
            v-model="depositApplyForm.amount"
            type="number"
            min="0.01"
            step="0.01"
            class="erp-input w-full tabular-nums"
            :placeholder="suggestedSaleDepositApply"
          />
        </FormField>
        <FormField label="Notes" :error="depositApplyFieldError('notes')">
          <textarea v-model="depositApplyForm.notes" rows="2" class="erp-input w-full" />
        </FormField>
      </div>
    </FormModal>

    <FormModal
      ref="paymentModalRef"
      :open="paymentModalOpen"
      title="Record sale payment"
      save-label="Record payment"
      :saving="paymentSaving"
      @close="paymentModalOpen = false"
      @save="onSubmitPayment"
    >
      <AlertBanner
        v-if="paymentError"
        variant="error"
        :message="paymentError"
        class="mb-4"
        @dismiss="paymentError = ''"
      />
      <p class="mb-4 text-sm text-slate-600 dark:text-slate-400">
        Outstanding balance:
        <span class="font-medium tabular-nums text-slate-900 dark:text-slate-100">
          {{ formatMoney(financials.balance_due, currency) }}
        </span>
      </p>
      <div class="space-y-4">
        <FormField label="Amount" :error="paymentFieldError('amount')" required>
          <input
            v-model="paymentForm.amount"
            type="number"
            min="0.01"
            step="0.01"
            class="erp-input w-full"
            :placeholder="String(financials.balance_due || '')"
          />
        </FormField>
        <FormField label="Payment date" :error="paymentFieldError('payment_date')" required>
          <input v-model="paymentForm.payment_date" type="date" class="erp-input w-full" />
        </FormField>
        <FormField label="Payment method" :error="paymentFieldError('payment_method')" required>
          <select v-model="paymentForm.payment_method" class="erp-select w-full">
            <option value="cash">Cash</option>
            <option value="bank_transfer">Bank transfer</option>
            <option value="mobile_money">Mobile money</option>
            <option value="cheque">Cheque</option>
          </select>
        </FormField>
        <FormField label="Reference" :error="paymentFieldError('reference_number')">
          <input v-model="paymentForm.reference_number" type="text" class="erp-input w-full" />
        </FormField>
        <FormField label="Notes" :error="paymentFieldError('notes')">
          <textarea v-model="paymentForm.notes" rows="2" class="erp-input w-full" />
        </FormField>
      </div>
    </FormModal>
  </div>
</template>

<script setup>
import { computed, ref, reactive, onMounted } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { useSaleAgreements } from '@/composables/useSaleAgreements'
import { useConfirm } from '@/composables/useConfirm'
import { useToastStore } from '@/stores/toast'
import {
  ObjectPageLayout,
  ErpButton,
  FormSection,
  FormField,
  FormModal,
  KpiCard,
  KpiStrip,
  AlertBanner,
  EmptyState,
  StatusBadge,
} from '@/components/erp'

const route = useRoute()
const toast = useToastStore()
const { confirm } = useConfirm()
const {
  fetchOne,
  executeContract,
  cancelContract,
  recordPayment,
  applyReservationDeposit,
  downloadCompletionCertificate,
  downloadOwnershipTransferCertificate,
  downloadSalesContract,
  downloadPaymentPlanStatement,
  approveOwnership,
  issueTitleDeed,
} = useSaleAgreements()

const contractId = route.params.id
const loading = ref(true)
const actionLoading = ref(false)
const pageError = ref('')
const contract = ref(null)
const paymentModalOpen = ref(false)
const paymentModalRef = ref(null)
const paymentSaving = ref(false)
const paymentError = ref('')
const paymentValidation = ref({})
const depositApplyModalOpen = ref(false)
const depositApplySaving = ref(false)
const depositApplyError = ref('')
const depositApplyValidation = ref({})
const depositApplyForm = reactive({
  amount: '',
  notes: '',
})
const paymentForm = reactive({
  amount: '',
  payment_date: new Date().toISOString().split('T')[0],
  payment_method: 'bank_transfer',
  reference_number: '',
  notes: '',
})
const titleDeedForm = reactive({
  title_deed_number: '',
  notes: '',
})
const titleDeedSaving = ref(false)

const buyer = computed(() => contract.value?.buyer || {})
const apartment = computed(() => contract.value?.apartment || {})
const financials = computed(() => contract.value?.financials || {})
const depositLedger = computed(() => financials.value.deposit_ledger ?? null)
const paymentPlan = computed(() => contract.value?.payment_plan || {})
const isPaymentPlan = computed(
  () => paymentPlan.value.mode === 'payment_plan' || contract.value?.installments?.is_payment_plan,
)
const brokerage = computed(() => contract.value?.brokerage || {})
const notes = computed(() => contract.value?.notes || {})
const dates = computed(() => contract.value?.dates || {})
const controls = computed(() => contract.value?.controls || {})
const canApplyReservationDeposit = computed(() => {
  const available = Number(depositLedger.value?.available ?? 0)
  const balance = Number(financials.value.balance_due ?? 0)
  return available > 0.009
    && balance > 0.009
    && controls.value.can_record_payment
})
const suggestedSaleDepositApply = computed(() => {
  const available = Number(depositLedger.value?.available ?? 0)
  const balance = Number(financials.value.balance_due ?? 0)
  if (available <= 0 || balance <= 0) return ''
  return Math.min(available, balance).toFixed(2)
})
const ownership = computed(() => contract.value?.ownership || {})
const ownershipApprovals = computed(() => ownership.value.approvals || [])
const ownershipSteps = computed(() => {
  const approvalsByStep = Object.fromEntries(
    ownershipApprovals.value.map((row) => [row.step, row]),
  )
  return [
    { id: 'legal', label: 'Legal', canApprove: controls.value.can_approve_legal },
    { id: 'finance', label: 'Finance', canApprove: controls.value.can_approve_finance },
    { id: 'manager', label: 'Manager', canApprove: controls.value.can_approve_manager },
  ].map((step) => ({
    ...step,
    approval: approvalsByStep[step.id] || null,
  }))
})
const payments = computed(() => contract.value?.payments || [])
const currency = computed(() => financials.value.currency || 'USD')
const planDurationLabel = computed(() => {
  const years = paymentPlan.value.plan_duration_years ?? 0
  const months = paymentPlan.value.plan_duration_months ?? 0
  const parts = []
  if (years) parts.push(`${years} year${years === 1 ? '' : 's'}`)
  if (months) parts.push(`${months} month${months === 1 ? '' : 's'}`)
  return parts.length ? parts.join(' ') : '—'
})

const pageSubtitle = computed(() => {
  const parts = [buyer.value.full_name, apartment.value.unit_number ? `Unit ${apartment.value.unit_number}` : null]
    .filter(Boolean)
  return parts.join(' · ') || 'Sale agreement'
})

const breadcrumbs = computed(() => [
  { label: 'Sale contracts', to: '/sales/contracts' },
  { label: contract.value?.agreement_number || 'Contract' },
])

const headerAttributes = computed(() => {
  const attrs = []
  if (isPaymentPlan.value) attrs.push('Payment plan')
  else attrs.push('Cash')
  if (apartment.value.inventory_status) attrs.push(formatLabel(apartment.value.inventory_status))
  return attrs
})

const tabs = computed(() => {
  const base = [
    { id: 'overview', label: 'Overview' },
    { id: 'financials', label: 'Financials' },
  ]
  if (isPaymentPlan.value) {
    base.push({ id: 'payment-plan', label: 'Payment plan' })
  }
  if (contract.value?.status?.is_completed) {
    base.push({ id: 'ownership', label: 'Ownership' })
  }
  base.push({ id: 'notes', label: 'Notes' })
  return base
})

function formatLabel(value) {
  if (!value) return '—'
  return String(value).replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}

function formatMoney(value, curr = 'USD') {
  if (value === null || value === undefined || value === '') return '—'
  return new Intl.NumberFormat(undefined, { style: 'currency', currency: curr }).format(Number(value))
}

async function loadContract() {
  loading.value = true
  pageError.value = ''
  try {
    contract.value = await fetchOne(contractId)
  } catch (err) {
    pageError.value = err.response?.data?.message || 'Could not load sale contract.'
    contract.value = null
  } finally {
    loading.value = false
  }
}

async function onExecute() {
  const ok = await confirm({
    title: 'Execute sale contract',
    message: 'This locks the sale price and marks the unit as under contract. Continue?',
    confirmLabel: 'Execute',
  })
  if (!ok) return

  actionLoading.value = true
  try {
    contract.value = await executeContract(contractId)
    toast.show('Sale contract executed', 'success')
  } catch (err) {
    pageError.value = err.response?.data?.message || 'Could not execute contract.'
  } finally {
    actionLoading.value = false
  }
}

function resetPaymentForm() {
  paymentForm.amount = financials.value.balance_due ? String(financials.value.balance_due) : ''
  paymentForm.payment_date = new Date().toISOString().split('T')[0]
  paymentForm.payment_method = 'bank_transfer'
  paymentForm.reference_number = ''
  paymentForm.notes = ''
  paymentError.value = ''
  paymentValidation.value = {}
}

function paymentFieldError(field) {
  const errors = paymentValidation.value[field]
  return Array.isArray(errors) ? errors[0] : undefined
}

function resetDepositApplyForm() {
  depositApplyForm.amount = suggestedSaleDepositApply.value
  depositApplyForm.notes = ''
  depositApplyError.value = ''
  depositApplyValidation.value = {}
}

function depositApplyFieldError(field) {
  const errors = depositApplyValidation.value[field]
  return Array.isArray(errors) ? errors[0] : undefined
}

function openDepositApplyModal() {
  resetDepositApplyForm()
  depositApplyModalOpen.value = true
}

function openPaymentModal() {
  resetPaymentForm()
  paymentModalOpen.value = true
}

async function onSubmitDepositApply() {
  depositApplySaving.value = true
  depositApplyError.value = ''
  depositApplyValidation.value = {}
  try {
    const result = await applyReservationDeposit(contractId, depositApplyForm)
    contract.value = result.contract
    depositApplyModalOpen.value = false
    toast.show(result.message || 'Reservation deposit applied', result.completed ? 'success' : 'info')
  } catch (err) {
    depositApplyError.value = err.response?.data?.message || 'Could not apply reservation deposit.'
    depositApplyValidation.value = err.response?.data?.errors || {}
  } finally {
    depositApplySaving.value = false
  }
}

async function onSubmitPayment() {
  paymentSaving.value = true
  paymentError.value = ''
  paymentValidation.value = {}
  try {
    const result = await recordPayment(contractId, paymentForm)
    contract.value = result.contract
    paymentModalOpen.value = false
    toast.show(result.message || 'Payment recorded', result.completed ? 'success' : 'info')
  } catch (err) {
    paymentError.value = err.response?.data?.message || 'Could not record payment.'
    paymentValidation.value = err.response?.data?.errors || {}
  } finally {
    paymentSaving.value = false
  }
}

async function onDownloadCertificate() {
  actionLoading.value = true
  try {
    await downloadCompletionCertificate(contractId, contract.value?.agreement_number)
  } catch (err) {
    pageError.value = err.response?.data?.message || 'Could not download completion certificate.'
  } finally {
    actionLoading.value = false
  }
}

async function onDownloadOwnershipCertificate() {
  actionLoading.value = true
  try {
    await downloadOwnershipTransferCertificate(contractId, contract.value?.agreement_number)
  } catch (err) {
    pageError.value = err.response?.data?.message || 'Could not download ownership transfer certificate.'
  } finally {
    actionLoading.value = false
  }
}

async function onDownloadSalesContract() {
  actionLoading.value = true
  try {
    await downloadSalesContract(contractId, contract.value?.agreement_number)
  } catch (err) {
    pageError.value = err.message || err.response?.data?.message || 'Could not download sales contract.'
  } finally {
    actionLoading.value = false
  }
}

async function onDownloadPaymentPlanStatement() {
  actionLoading.value = true
  try {
    await downloadPaymentPlanStatement(contractId, contract.value?.agreement_number)
  } catch (err) {
    pageError.value = err.response?.data?.message || 'Could not download payment plan statement.'
  } finally {
    actionLoading.value = false
  }
}

async function onApproveOwnership(step) {
  const ok = await confirm({
    title: 'Approve ownership transfer',
    message: `Record ${step} approval for this completed sale?`,
    confirmLabel: 'Approve',
  })
  if (!ok) return

  actionLoading.value = true
  try {
    const result = await approveOwnership(contractId, step)
    contract.value = result.contract
    toast.show(result.message, result.finalized ? 'success' : 'info')
  } catch (err) {
    pageError.value = err.response?.data?.message || 'Could not record approval.'
  } finally {
    actionLoading.value = false
  }
}

async function onIssueTitleDeed() {
  if (!titleDeedForm.title_deed_number.trim()) {
    pageError.value = 'Enter a title deed number.'
    return
  }

  titleDeedSaving.value = true
  pageError.value = ''
  try {
    const result = await issueTitleDeed(contractId, titleDeedForm)
    contract.value = result.contract
    titleDeedForm.title_deed_number = ''
    titleDeedForm.notes = ''
    toast.show(result.message || 'Title deed recorded', 'success')
  } catch (err) {
    pageError.value = err.response?.data?.message || 'Could not record title deed.'
  } finally {
    titleDeedSaving.value = false
  }
}

async function onCancel() {
  const ok = await confirm({
    title: 'Cancel draft contract',
    message: 'Cancel this draft sale agreement?',
    confirmLabel: 'Cancel draft',
    variant: 'danger',
  })
  if (!ok) return

  actionLoading.value = true
  try {
    contract.value = await cancelContract(contractId, 'Cancelled from contract detail')
    toast.show('Draft contract cancelled', 'success')
  } catch (err) {
    pageError.value = err.response?.data?.message || 'Could not cancel contract.'
  } finally {
    actionLoading.value = false
  }
}

onMounted(loadContract)
</script>
