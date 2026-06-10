<template>
  <WorklistLayout
    eyebrow="Finance"
    title="Payments"
    :count="meta.total"
    description="Newest payments first. FIFO applies to oldest open issued invoice per tenant."
  >
    <template #actions>
      <ErpButton variant="ghost" size="sm" :to="monthlyInvoicesLink">Monthly invoices</ErpButton>
      <ErpButton variant="ghost" size="sm" :to="{ name: 'Invoices' }">Billing close</ErpButton>
      <ErpButton variant="ghost" size="sm" :loading="loading" @click="refreshAll">
        Refresh
      </ErpButton>
      <ErpButton @click="openRecordModal">Record payment</ErpButton>
    </template>

    <template #kpis>
      <AlertBanner
        v-if="showCollectBanner"
        variant="info"
        :dismissible="true"
        class="mb-4"
        @dismiss="collectBannerDismissed = true"
      >
        <span>
          Invoices issued for {{ periodLabel }}. Record each tenant payment — allocation is
          <strong>FIFO</strong> (oldest open invoice first).
        </span>
        <button
          type="button"
          class="ml-2 font-semibold text-blue-900 underline"
          @click="openRecordModal"
        >
          Record payment
        </button>
      </AlertBanner>

      <KpiStrip class="grid-cols-2 lg:grid-cols-5">
        <KpiCard label="Payments recorded" :value="meta.total" />
        <KpiCard
          label="Open AR (June)"
          :value="formatMoney(collections.open_balance)"
          :caption="collections.open_balance > 0 ? 'Balance still due on issued invoices' : 'All caught up for period'"
          variant="accent"
        />
        <KpiCard
          v-if="totalUnallocatedCredit > 0.009"
          label="Tenant credit"
          :value="formatMoney(totalUnallocatedCredit)"
          caption="Unallocated on this page — applies FIFO when balance opens"
          variant="warning"
        />
        <KpiCard
          label="Latest receipt"
          :value="latestReceipt"
          caption="Most recent on this page"
        />
        <KpiCard
          label="Allocation"
          value="FIFO"
          caption="Oldest open invoice first"
        />
      </KpiStrip>
    </template>

    <template #table>
      <AlertBanner
        v-if="pageSuccess"
        :variant="pageSuccessVariant"
        class="mb-4"
        :message="pageSuccess"
        @dismiss="pageSuccess = ''"
      />
      <AlertBanner
        v-if="pageError"
        variant="error"
        class="mb-4"
        :message="pageError"
        @dismiss="pageError = ''"
      />

      <DataTable
        :columns="columns"
        :rows="items"
        :loading="loading"
        :meta="meta"
        empty-title="No payments yet"
        empty-description="Record a tenant payment to allocate against open invoices."
        @page-change="fetchList"
      >
        <template #emptyAction>
          <ErpButton @click="openRecordModal">Record payment</ErpButton>
        </template>
        <template #cell-receipt_number="{ row }">
          <RouterLink
            :to="{ name: 'PaymentShow', params: { id: row.id } }"
            class="font-mono text-xs text-blue-700 hover:underline"
          >
            {{ row.receipt_number }}
          </RouterLink>
        </template>
        <template #cell-tenant="{ row }">
          <span class="font-medium">{{ tenantDisplayName(row.tenant) || '—' }}</span>
        </template>
        <template #cell-amount="{ row }">
          <span class="tabular-nums font-medium">{{ formatMoney(row.amount) }}</span>
        </template>
        <template #cell-method="{ row }">
          <span class="capitalize text-sm">{{ formatMethod(row.payment_method) }}</span>
        </template>
        <template #cell-allocations="{ row }">
          <div v-if="row.allocations?.length" class="space-y-0.5">
            <p
              v-for="alloc in row.allocations"
              :key="alloc.id"
              class="text-xs text-slate-600"
            >
              <code class="font-mono">{{ alloc.invoice_number || 'INV' }}</code>
              {{ formatMoney(alloc.amount_allocated) }}
            </p>
            <p
              v-if="unallocatedAmount(row) > 0.009"
              class="text-xs font-medium text-amber-700"
            >
              {{ formatMoney(unallocatedAmount(row)) }} not applied (no open invoice balance)
            </p>
          </div>
          <span v-else class="text-xs text-amber-700">Nothing allocated — check issued invoices</span>
        </template>
      </DataTable>
    </template>
  </WorklistLayout>

  <ErpModal
    :open="recordModal"
    title="Record payment"
    :subtitle="recordModalSubtitle"
    confirm-label="Record payment"
    :loading="saving"
    @close="closeRecordModal"
    @confirm="submitPayment"
  >
    <div class="mt-3 space-y-4">
      <FormField label="Payment type" required :error="fieldError('payment_purpose')">
        <select v-model="form.payment_purpose" class="erp-select">
          <option value="rent">Rent (FIFO to open invoices)</option>
          <option value="security_deposit">Security deposit received</option>
          <option value="deposit_refund">Security deposit refund</option>
        </select>
      </FormField>
      <FormField label="Building" required :error="fieldError('building_id')">
        <ErpSearchSelect
          v-model="form.building_id"
          :options="buildingOptions"
          :loading="buildingsLoading"
          remote
          placeholder="Select building…"
          search-placeholder="Search building name…"
          @search="onBuildingSearch"
        />
      </FormField>
      <FormField label="Tenant" required :error="fieldError('tenant_id')">
        <ErpSearchSelect
          v-model="form.tenant_id"
          :options="tenantOptions"
          :loading="tenantsLoading"
          :disabled="!form.building_id"
          remote
          :placeholder="form.building_id ? 'Select tenant…' : 'Select building first…'"
          search-placeholder="Search name or code…"
          @search="onTenantSearch"
        />
      </FormField>

      <FormField
        v-if="isDepositPayment"
        label="Rental agreement"
        required
        :error="fieldError('agreement_id')"
      >
        <select
          v-model="form.agreement_id"
          class="erp-select"
          :disabled="!form.tenant_id || agreementsLoading"
        >
          <option value="">
            {{ agreementsLoading ? 'Loading agreements…' : 'Select agreement…' }}
          </option>
          <option v-for="a in tenantAgreements" :key="a.id" :value="a.id">
            {{ a.agreement_number }}
            <template v-if="a.financials?.security_deposit">
              · deposit {{ formatMoney(a.financials.security_deposit) }}
            </template>
          </option>
        </select>
        <p
          v-if="selectedAgreementLedger && !agreementsLoading"
          class="mt-1 text-xs text-slate-500"
        >
          Available deposit: {{ formatMoney(selectedAgreementLedger.available) }}
          <span v-if="selectedAgreementLedger.required > 0">
            · required {{ formatMoney(selectedAgreementLedger.required) }}
          </span>
        </p>
      </FormField>

      <div
        v-if="form.tenant_id && !isDepositPayment"
        class="rounded-lg border border-slate-200 bg-slate-50/80 px-4 py-3"
      >
        <div class="flex flex-wrap items-center justify-between gap-2">
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Balance due (issued)</p>
            <p v-if="balanceLoading" class="text-sm text-slate-500">Loading…</p>
            <p v-else class="text-lg font-semibold tabular-nums text-slate-900">
              {{ formatMoney(tenantBalance?.open_balance ?? 0) }}
            </p>
            <p
              v-if="!balanceLoading && tenantBalance?.amounts?.utilities_on_invoices > 0"
              class="text-xs text-slate-600"
            >
              Includes {{ formatMoney(tenantBalance.amounts.utilities_on_invoices) }} utilities on open invoice(s)
            </p>
            <p
              v-if="!balanceLoading && pendingUtilityTotal > 0"
              class="text-xs font-medium text-amber-700"
            >
              + {{ formatMoney(pendingUtilityTotal) }} approved utilities awaiting invoice sync
            </p>
            <p
              v-if="!balanceLoading && draftUtilityTotal > 0"
              class="text-xs font-medium text-amber-700"
            >
              + {{ formatMoney(draftUtilityTotal) }} utilities on draft invoice(s) — issue to collect
            </p>
            <p v-if="!balanceLoading && tenantBalance" class="text-xs text-slate-500">
              {{ tenantBalance.invoice_count }} open invoice(s)
              <span v-if="tenantBalance.invoices?.length">
                · oldest first (FIFO)
              </span>
            </p>
          </div>
          <ErpButton
            v-if="!balanceLoading && tenantBalance?.open_balance > 0"
            size="sm"
            variant="secondary"
            @click="fillFullBalance"
          >
            Pay full balance
          </ErpButton>
        </div>
        <ul
          v-if="!balanceLoading && tenantBalance?.invoices?.length"
          class="mt-3 space-y-1 border-t border-slate-200 pt-3"
        >
          <li
            v-for="inv in tenantBalance.invoices"
            :key="inv.id"
            class="rounded-md border border-slate-100 bg-white px-2 py-1.5 text-xs text-slate-600"
          >
            <div class="flex flex-wrap items-center justify-between gap-2">
              <span>
                <code class="font-mono">{{ inv.invoice_number }}</code>
                <span v-if="inv.unit_number" class="text-slate-400"> · {{ inv.unit_number }}</span>
              </span>
              <span class="tabular-nums font-medium text-slate-900">
                {{ formatMoney(inv.balance_due) }}
              </span>
            </div>
            <p v-if="invoiceBreakdown(inv)" class="mt-0.5 text-[11px] text-slate-500">
              {{ invoiceBreakdown(inv) }}
            </p>
          </li>
        </ul>
        <p
          v-else-if="!balanceLoading && tenantBalance && tenantBalance.open_balance <= 0"
          class="mt-2 text-xs text-emerald-700"
        >
          No open balance for this tenant in the selected building.
        </p>
      </div>

      <div class="grid gap-4 sm:grid-cols-2">
        <FormField label="Amount" required :error="fieldError('amount')">
          <input
            v-model="form.amount"
            type="number"
            step="0.01"
            min="0.01"
            class="erp-input tabular-nums"
            placeholder="0.00"
          />
        </FormField>
        <FormField label="Payment date" required :error="fieldError('payment_date')">
          <ErpDateInput v-model="form.payment_date" />
        </FormField>
      </div>
      <FormField label="Payment method" required :error="fieldError('payment_method')">
        <select v-model="form.payment_method" class="erp-select">
          <option value="bank_transfer">Bank transfer</option>
          <option value="cash">Cash</option>
          <option value="mobile_money">Mobile money</option>
          <option value="cheque">Cheque</option>
        </select>
      </FormField>
      <FormField
        label="Receipt account"
        :hint="receiptAccountHint"
        :error="fieldError('receipt_account_code')"
      >
        <p v-if="!form.receipt_account_override" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
          {{ defaultReceiptAccountLabel }}
        </p>
        <select
          v-else
          v-model="form.receipt_account_code"
          class="erp-select"
        >
          <option value="">Select account…</option>
          <option
            v-for="account in receiptAccounts"
            :key="account.code"
            :value="account.code"
          >
            {{ account.code }} — {{ account.name }}
          </option>
        </select>
        <label class="mt-2 flex items-center gap-2 text-sm text-slate-600">
          <input
            v-model="form.receipt_account_override"
            type="checkbox"
            class="rounded border-slate-300"
          />
          Override default receipt account
        </label>
      </FormField>
      <FormField label="Reference" :error="fieldError('reference_number')">
        <input v-model="form.reference_number" type="text" class="erp-input" placeholder="Transfer ref, cheque #…" />
      </FormField>
      <FormField label="Notes" :error="fieldError('notes')">
        <textarea v-model="form.notes" rows="2" class="erp-input" placeholder="Optional" />
      </FormField>
    </div>
  </ErpModal>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import api from '@/services/api'
import { usePayments } from '@/composables/usePayments'
import { useTenantPicker } from '@/composables/useTenantPicker'
import { useBuildingPicker } from '@/composables/useBuildingPicker'
import {
  WorklistLayout,
  DataTable,
  ErpButton,
  KpiCard,
  KpiStrip,
  ErpModal,
  FormField,
  ErpDateInput,
  ErpSearchSelect,
  AlertBanner,
} from '@/components/erp'
import { tenantDisplayName } from '@/utils/tenantDisplayName'

const {
  items,
  loading,
  saving,
  meta,
  form,
  tenantBalance,
  balanceLoading,
  resetForm,
  fetchList,
  recordPayment,
  fetchTenantBalance,
} = usePayments()

const { tenants, loading: tenantsLoading, fetchTenants, tenantToOption } = useTenantPicker()
const { buildings, loading: buildingsLoading, fetchBuildings, buildingToOption } = useBuildingPicker()

const route = useRoute()
const recordModal = ref(false)
const pageError = ref('')
const pageSuccess = ref('')
const pageSuccessVariant = ref('success')
const fieldErrors = ref({})
const collectBannerDismissed = ref(false)
const rentalAgreements = ref([])
const agreementsLoading = ref(false)
const receiptAccounts = ref([])
const receiptDefaultsByMethod = ref({})
const receiptOptionsLoading = ref(false)

const isDepositPayment = computed(
  () => form.payment_purpose === 'security_deposit' || form.payment_purpose === 'deposit_refund',
)

const defaultReceiptAccount = computed(() => {
  const code = receiptDefaultsByMethod.value[form.payment_method]
  return receiptAccounts.value.find((account) => account.code === code) ?? null
})

const defaultReceiptAccountLabel = computed(() => {
  const account = defaultReceiptAccount.value
  if (!account) return 'Loading receipt account…'
  return `${account.code} — ${account.name}`
})

const receiptAccountHint = computed(() => {
  if (form.receipt_account_override) {
    return 'Journal debit will post to the selected asset account instead of the method default.'
  }
  return 'Mapped from payment method. Enable override to choose a different receipt account.'
})

const recordModalSubtitle = computed(() => {
  if (form.payment_purpose === 'security_deposit') {
    return 'Posts to customer deposits liability (2120). Does not reduce rent invoices.'
  }
  if (form.payment_purpose === 'deposit_refund') {
    return 'Releases deposit liability (2120) back to the receipt account.'
  }
  return "Payment is allocated to the tenant's oldest open invoices (FIFO)."
})

const tenantAgreements = computed(() =>
  rentalAgreements.value.filter((a) => a.tenant?.id === form.tenant_id),
)

const selectedAgreementLedger = computed(() => {
  const match = tenantAgreements.value.find((a) => a.id === form.agreement_id)
  return match?.financials?.deposit_ledger ?? null
})

const billingPeriod = reactive({
  year: Number(route.query.year) || new Date().getFullYear(),
  month: Number(route.query.month) || new Date().getMonth() + 1,
})

const collections = reactive({
  open_balance: 0,
  issued_count: 0,
})

const months = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December',
]

const periodLabel = computed(() => `${months[billingPeriod.month - 1]} ${billingPeriod.year}`)

const monthlyInvoicesLink = computed(() => ({
  name: 'MonthlyInvoices',
  query: { year: String(billingPeriod.year), month: String(billingPeriod.month) },
}))

const showCollectBanner = computed(
  () =>
    !collectBannerDismissed.value
    && (route.query.collect === '1' || collections.open_balance > 0)
    && collections.issued_count > 0
)

const tenantOptions = computed(() => tenants.value.map((t) => tenantToOption(t)))
const buildingOptions = computed(() => buildings.value.map((b) => buildingToOption(b)))

const latestReceipt = computed(() => items.value[0]?.receipt_number ?? '—')

const totalUnallocatedCredit = computed(() =>
  items.value.reduce((sum, row) => {
    const unallocated = Number(row.unallocated_amount ?? unallocatedAmount(row))
    return sum + (unallocated > 0.009 ? unallocated : 0)
  }, 0),
)

const pendingUtilityTotal = computed(() =>
  Number(tenantBalance.value?.amounts?.pending_utilities ?? 0),
)

const draftUtilityTotal = computed(() =>
  (tenantBalance.value?.draft_utility_invoices ?? []).reduce(
    (sum, inv) => sum + Number(inv.balance_due ?? inv.subtotal_utilities ?? 0),
    0,
  ),
)

function invoiceBreakdown(inv) {
  const parts = []
  const rent = Number(inv.subtotal_rent ?? 0)
  const utilities = Number(inv.subtotal_utilities ?? 0)
  const services = Number(inv.subtotal_services ?? 0)
  if (rent > 0) parts.push(`Rent ${formatMoney(rent)}`)
  if (utilities > 0) parts.push(`Utilities ${formatMoney(utilities)}`)
  if (services > 0) parts.push(`Services ${formatMoney(services)}`)
  if (!parts.length) return ''
  const total = Number(inv.total_amount ?? 0)
  const paid = Number(inv.paid_amount ?? 0)
  if (paid > 0) {
    return `${parts.join(' · ')} · Total ${formatMoney(total)} (${formatMoney(paid)} paid)`
  }
  return `${parts.join(' · ')} · Total ${formatMoney(total)}`
}

const columns = [
  { key: 'receipt_number', label: 'Receipt', mono: true },
  { key: 'payment_date', label: 'Date' },
  { key: 'tenant', label: 'Tenant', emphasis: true },
  { key: 'amount', label: 'Received', align: 'right' },
  { key: 'method', label: 'Method' },
  { key: 'allocations', label: 'Applied to invoices' },
]

function fieldError(key) {
  const e = fieldErrors.value[key]
  return Array.isArray(e) ? e[0] : e || ''
}

function formatMoney(v) {
  return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(Number(v) || 0)
}

function formatMethod(m) {
  return String(m || '').replace(/_/g, ' ')
}

function allocatedTotal(row) {
  return (row.allocations ?? []).reduce((sum, a) => sum + Number(a.amount_allocated || 0), 0)
}

function unallocatedAmount(row) {
  return Number(row.amount || 0) - allocatedTotal(row)
}

let buildingSearchTimer = null
function onBuildingSearch(q) {
  clearTimeout(buildingSearchTimer)
  buildingSearchTimer = setTimeout(
    () => fetchBuildings(q, { ensureId: form.building_id || undefined }),
    280,
  )
}

let tenantSearchTimer = null
function onTenantSearch(q) {
  if (!form.building_id) return
  clearTimeout(tenantSearchTimer)
  tenantSearchTimer = setTimeout(
    () =>
      fetchTenants(q, {
        ensureId: form.tenant_id || undefined,
        buildingId: form.building_id,
      }),
    280,
  )
}

function fillFullBalance() {
  const balance = Number(tenantBalance.value?.open_balance ?? 0)
  if (balance > 0) {
    form.amount = balance.toFixed(2)
  }
}

watch(
  () => form.building_id,
  (buildingId, prev) => {
    if (buildingId === prev) return
    form.tenant_id = ''
    tenants.value = []
    if (buildingId) {
      fetchTenants('', { buildingId })
    }
  },
)

async function loadRentalAgreements() {
  agreementsLoading.value = true
  try {
    const { data } = await api.get('/rental-agreements', { params: { per_page: 100 } })
    rentalAgreements.value = data.data ?? []
  } catch {
    rentalAgreements.value = []
  } finally {
    agreementsLoading.value = false
  }
}

watch(
  () => form.payment_purpose,
  (purpose) => {
    if (purpose === 'security_deposit' || purpose === 'deposit_refund') {
      form.agreement_id = ''
      loadRentalAgreements()
    } else {
      form.agreement_id = ''
    }
  },
)

watch(
  () => form.tenant_id,
  (tenantId) => {
    if (!tenantId) {
      tenantBalance.value = null
      form.agreement_id = ''
      return
    }
    if (!isDepositPayment.value) {
      fetchTenantBalance({
        tenantId,
        buildingId: form.building_id || undefined,
      }).catch(() => {
        /* balance panel stays empty */
      })
    }
    if (isDepositPayment.value && tenantAgreements.value.length === 1) {
      form.agreement_id = tenantAgreements.value[0].id
    }
  },
)

async function loadReceiptAccountOptions() {
  receiptOptionsLoading.value = true
  try {
    const { data } = await api.get('/payments/receipt-account-options')
    const payload = data.data ?? data
    receiptAccounts.value = payload.accounts ?? []
    receiptDefaultsByMethod.value = payload.defaults_by_method ?? {}
  } catch {
    receiptAccounts.value = []
    receiptDefaultsByMethod.value = {}
  } finally {
    receiptOptionsLoading.value = false
  }
}

watch(
  () => form.payment_method,
  () => {
    if (!form.receipt_account_override) {
      form.receipt_account_code = ''
    }
  },
)

watch(
  () => form.receipt_account_override,
  (enabled) => {
    if (!enabled) {
      form.receipt_account_code = ''
      return
    }
    form.receipt_account_code = defaultReceiptAccount.value?.code ?? ''
  },
)

function openRecordModal() {
  resetForm()
  fieldErrors.value = {}
  pageError.value = ''
  recordModal.value = true
  fetchBuildings('', { ensureId: form.building_id || undefined })
  loadReceiptAccountOptions()
}

function closeRecordModal() {
  recordModal.value = false
}

async function fetchCollections() {
  try {
    const { data } = await api.get('/invoices/summary', {
      params: { year: billingPeriod.year, month: billingPeriod.month },
    })
    const s = data.data ?? data
    collections.open_balance = s.amounts?.open_balance ?? 0
    collections.issued_count = (s.counts?.issued ?? 0) + (s.counts?.partially_paid ?? 0)
  } catch {
    /* keep prior */
  }
}

async function refreshAll() {
  await Promise.all([fetchList(meta.value.current_page), fetchCollections()])
}

async function submitPayment() {
  fieldErrors.value = {}
  pageError.value = ''
  pageSuccess.value = ''
  if (!form.building_id) {
    fieldErrors.value = { building_id: ['Select a building.'] }
    return
  }
  if (!form.tenant_id) {
    fieldErrors.value = { tenant_id: ['Select a tenant.'] }
    return
  }
  if (isDepositPayment.value && !form.agreement_id) {
    fieldErrors.value = { agreement_id: ['Select a rental agreement.'] }
    return
  }
  if (!form.amount || Number(form.amount) <= 0) {
    fieldErrors.value = { amount: ['Enter a valid amount.'] }
    return
  }
  if (form.receipt_account_override && !form.receipt_account_code) {
    fieldErrors.value = { receipt_account_code: ['Select a receipt account.'] }
    return
  }
  try {
    const { payment, message } = await recordPayment({ ...form })
    closeRecordModal()
    const unallocated = Number(payment?.unallocated_amount ?? 0)
    pageSuccessVariant.value = unallocated > 0.009 ? 'warning' : 'success'
    pageSuccess.value = message || 'Payment recorded.'
    await refreshAll()
  } catch (e) {
    if (e.response?.status === 422) {
      fieldErrors.value = e.response.data.errors || {}
      pageError.value = 'Please fix the highlighted fields.'
    } else {
      pageError.value = e.response?.data?.message || 'Could not record payment.'
    }
  }
}

onMounted(() => {
  fetchCollections()
  fetchList()
})
</script>
