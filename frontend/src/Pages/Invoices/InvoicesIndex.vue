<template>
  <div class="erp-page">
    <ErpPanel>
      <PageHeader
        eyebrow="Finance"
        title="Billing operations"
        description="Standard close: approve readings & charges → compile invoices → issue → record payments."
      >
        <template #actions>
          <ErpButton variant="ghost" size="sm" :to="{ name: 'MonthlyInvoices' }">
            Monthly invoices
          </ErpButton>
          <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 p-1.5">
            <select v-model="form.month" class="erp-select border-0 bg-transparent py-1.5 shadow-none">
              <option v-for="(name, index) in months" :key="index" :value="index + 1">{{ name }}</option>
            </select>
            <select v-model="form.year" class="erp-select border-0 bg-transparent py-1.5 shadow-none">
              <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
            </select>
            <ErpButton variant="ghost" size="sm" :loading="loading" @click="fetchSummaryMetrics">
              Refresh
            </ErpButton>
          </div>
        </template>
      </PageHeader>
    </ErpPanel>

    <AlertBanner
      v-if="pendingUtilityApprovals > 0"
      variant="warning"
      :dismissible="false"
      class="mb-4"
    >
      <span>
        {{ pendingUtilityApprovals }} utility charge(s) await approval and will be excluded from
        consolidation until approved.
      </span>
      <router-link
        :to="{ name: 'ChargeApproval' }"
        class="ml-2 font-semibold text-amber-900 underline"
      >
        Open approval guide
      </router-link>
    </AlertBanner>

    <div v-if="loading" class="grid gap-4 md:grid-cols-4">
      <div v-for="i in 3" :key="i" class="h-28 animate-pulse rounded-xl bg-slate-200/60" />
    </div>

    <template v-else>
    <AlertBanner
      v-if="!summary.metrics?.has_rent_charge_model"
      variant="error"
      :dismissible="false"
      class="mb-4"
      message="No active Rent charge model found. Create a charge model with strategy “Agreement rent” before compiling."
    />
    <AlertBanner
      v-else-if="(summary.metrics?.agreements_missing_rent_charge ?? 0) > 0"
      variant="warning"
      :dismissible="false"
      class="mb-4"
    >
      <span>
        {{ summary.metrics.agreements_missing_rent_charge }} active lease(s) have no linked rent charge.
        Compile will attempt to sync them automatically.
      </span>
    </AlertBanner>

    <div class="mb-4 grid gap-4 md:grid-cols-2 lg:grid-cols-5">
      <KpiCard
        label="Active leases"
        :value="summary.metrics?.active_rental_agreements ?? 0"
        caption="Eligible for this close"
        variant="accent"
      />
      <KpiCard
        label="Fixed lease items"
        :value="formatMoney(summary.metrics?.fixed_items_revenue)"
        :caption="`${summary.metrics?.fixed_items_count ?? 0} pending lines`"
      />
      <KpiCard
        label="Utilities ready"
        :value="formatMoney(summary.metrics?.utility_items_revenue)"
        :caption="`${summary.metrics?.utility_items_ready ?? 0} approved`"
      />
      <KpiCard
        label="Utilities pending"
        :value="summary.metrics?.utility_items_pending_approval ?? 0"
        caption="Approve before compile"
      />
      <KpiCard
        label="Est. consolidate total"
        :value="formatMoney(summary.metrics?.estimated_total)"
        caption="Approved items only"
        variant="accent"
      />
    </div>
    </template>

    <div v-if="!loading" class="grid gap-5 lg:grid-cols-3">
      <div class="lg:col-span-1">
      <ErpPanel title="Run consolidation" subtitle="Generates rent lines, then draft invoices">
        <AlertBanner
          v-if="!canCompile"
          variant="warning"
          :dismissible="false"
          message="No active rental agreements for this company. Activate a lease first."
        />
        <AlertBanner
          v-else-if="(summary.metrics?.total_pending_rows ?? 0) === 0 && canCompile"
          variant="info"
          :dismissible="false"
          :message="`Ready to run for ${summary.metrics?.active_rental_agreements ?? 0} active lease(s). Compile will generate rent billing lines for ${months[form.month - 1]} ${form.year}, then create draft invoices.`"
        />
        <p class="mb-4 text-sm text-slate-600">
          Step 1: billing run (rent/fees). Step 2: consolidate into one draft invoice per active lease for the selected month.
        </p>
        <ErpButton
          class="w-full"
          :loading="processing"
          :disabled="processing || !canCompile || !summary.metrics?.has_rent_charge_model"
          @click="openConsolidationModal"
        >
          Compile invoices
        </ErpButton>
      </ErpPanel>
      </div>

      <div class="lg:col-span-2">
      <ErpPanel title="Last run" subtitle="Results from the most recent consolidation">
        <div v-if="!lastRunResult && !processing" class="py-8">
          <EmptyState
            title="Ready to run"
            description="Select a period and compile invoices when pending items are available."
          />
        </div>
        <div v-else-if="processing" class="flex flex-col items-center py-12 text-center">
          <div class="mb-3 h-10 w-10 animate-spin rounded-full border-2 border-indigo-600 border-t-transparent" />
          <p class="text-sm font-medium text-indigo-700">Processing agreements and creating invoices…</p>
        </div>
        <template v-else-if="lastRunResult">
          <dl class="divide-y divide-slate-100 rounded-lg border border-slate-200">
            <div class="flex justify-between px-4 py-3 text-sm">
              <dt class="text-slate-500">Period</dt>
              <dd class="font-semibold text-slate-900">{{ lastRunResult.period }}</dd>
            </div>
            <div class="flex justify-between px-4 py-3 text-sm">
              <dt class="text-emerald-700">Invoices created</dt>
              <dd class="font-bold text-emerald-700">{{ (lastRunResult.results?.success ?? 0) - (lastRunResult.results?.appended ?? 0) }}</dd>
            </div>
            <div class="flex justify-between px-4 py-3 text-sm">
              <dt class="text-indigo-700">Charges added to existing</dt>
              <dd class="font-bold text-indigo-700">{{ lastRunResult.results?.appended ?? 0 }}</dd>
            </div>
            <div class="flex justify-between px-4 py-3 text-sm">
              <dt class="text-slate-600">Nothing new to add</dt>
              <dd class="font-bold text-slate-700">{{ lastRunResult.results?.skipped_already_exists ?? 0 }}</dd>
            </div>
            <div class="flex justify-between px-4 py-3 text-sm">
              <dt class="text-amber-700">Skipped (no items)</dt>
              <dd class="font-bold text-amber-700">{{ lastRunResult.results?.skipped_no_items ?? 0 }}</dd>
            </div>
            <div class="flex justify-between px-4 py-3 text-sm">
              <dt class="text-red-700">Failed</dt>
              <dd class="font-bold text-red-700">{{ lastRunResult.results?.failed ?? 0 }}</dd>
            </div>
          </dl>
          <AlertBanner
            v-if="(lastRunResult.results?.errors?.length ?? 0) > 0"
            variant="error"
            :dismissible="false"
            class="mt-4"
          >
            <ul class="list-disc space-y-1 pl-4 text-sm">
              <li v-for="(err, idx) in lastRunResult.results?.errors ?? []" :key="idx">
                <strong>{{ err.agreement_number || err.agreement_id }}</strong>:
                {{ err.message }}
              </li>
            </ul>
          </AlertBanner>
          <AlertBanner
            v-else-if="draftCount > 0 && (lastRunResult.results?.success ?? 0) === 0"
            variant="success"
            :dismissible="false"
            class="mt-4"
            :message="`Draft invoices already exist for this period (${draftCount}). Open the worklist to review and issue them — no need to compile again.`"
          />
          <p
            v-else-if="(lastRunResult.results?.failed ?? 0) > 0"
            class="mt-4 text-sm text-red-700"
          >
            Consolidation failed. Check the error details above or retry after running migrations.
          </p>
          <ErpButton
            v-if="draftCount > 0"
            class="mt-4 w-full"
            :variant="(lastRunResult.results?.success ?? 0) > 0 ? 'secondary' : 'primary'"
            :to="{ name: 'MonthlyInvoices', query: { year: form.year, month: form.month } }"
          >
            Review &amp; issue {{ draftCount }} draft invoice(s)
          </ErpButton>
        </template>
      </ErpPanel>
      </div>
    </div>

    <ErpModal
      :open="consolidationModal"
      title="Compile monthly invoices?"
      :subtitle="`Monthly close for ${months[form.month - 1]} ${form.year}: recurring billing run + draft invoices (approved utilities only).`"
      confirm-label="Compile invoices"
      :loading="processing"
      @close="consolidationModal = false"
      @confirm="runConsolidationAndClose"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useBilling } from '@/composables/useBilling'
import {
  PageHeader,
  ErpPanel,
  ErpButton,
  KpiCard,
  AlertBanner,
  EmptyState,
  ErpModal,
} from '@/components/erp'

const months = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December',
]
const currentYear = new Date().getFullYear()
const years = [currentYear - 1, currentYear, currentYear + 1]

const {
  loading,
  processing,
  summary,
  lastRunResult,
  period: form,
  fetchSummary: fetchSummaryMetrics,
  runConsolidation,
} = useBilling()

const route = useRoute()
const router = useRouter()
const consolidationModal = ref(false)

const pendingUtilityApprovals = computed(
  () => summary.metrics?.utility_items_pending_approval ?? 0
)

const canCompile = computed(
  () => summary.metrics?.can_compile ?? (summary.metrics?.active_rental_agreements ?? 0) > 0
)

const draftCount = computed(
  () =>
    lastRunResult.value?.results?.draft_invoices_for_period
    ?? lastRunResult.value?.pipeline?.stages?.invoices?.draft
    ?? summary.pipeline?.stages?.invoices?.draft
    ?? 0
)

watch(
  () => [form.month, form.year],
  () => fetchSummaryMetrics()
)

function formatMoney(value) {
  return new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
  }).format(Number(value || 0))
}

function openConsolidationModal() {
  consolidationModal.value = true
}

async function runConsolidationAndClose() {
  const result = await runConsolidation()
  consolidationModal.value = false
  const created = result?.results?.success ?? 0
  const drafts =
    result?.results?.draft_invoices_for_period
    ?? result?.pipeline?.stages?.invoices?.draft
    ?? 0
  if (created > 0 || drafts > 0) {
    router.push({
      name: 'MonthlyInvoices',
      query: { year: String(form.year), month: String(form.month) },
    })
  }
}

onMounted(() => {
  if (route.query.year) form.year = Number(route.query.year)
  if (route.query.month) form.month = Number(route.query.month)
  fetchSummaryMetrics()
})
</script>
