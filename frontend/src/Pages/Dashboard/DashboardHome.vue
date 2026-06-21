<template>
  <div class="erp-page dashboard-home space-y-6">
    <PageHeader
      eyebrow="Overview"
      :title="dashboard.user?.name ? `Welcome, ${dashboard.user.name}` : 'Dashboard'"
      :description="headerDescription"
    >
      <template #actions>
        <ErpButton variant="ghost" size="sm" :loading="loading" @click="refresh">Refresh</ErpButton>
        <ErpButton variant="ghost" size="sm" :to="{ name: 'Reports' }">Collections</ErpButton>
        <ErpButton variant="ghost" size="sm" :to="{ name: 'Invoices' }">Billing close</ErpButton>
      </template>
    </PageHeader>

    <DashboardSkeleton v-if="loading && !dashboard.portfolio?.buildings" />

    <template v-else>
      <AlertBanner
        v-if="pageError"
        variant="error"
        :message="pageError"
        @dismiss="pageError = ''"
      />

      <section v-if="dashboard.portfolio" aria-label="Key metrics">
        <KpiStrip grid-class="sm:grid-cols-2 xl:grid-cols-4">
          <KpiCard
            label="Buildings"
            :value="dashboard.portfolio.buildings_count ?? dashboard.portfolio.buildings ?? '—'"
            caption="In portfolio"
          />
          <KpiCard
            label="Occupancy"
            :value="formatPercent(dashboard.portfolio.occupancy_rate)"
            caption="Portfolio average"
          />
          <KpiCard
            label="Outstanding"
            :value="formatMoney(dashboard.financials?.outstanding_receivables)"
            caption="Total receivables"
            variant="accent"
          />
          <KpiCard
            label="Collected (MTD)"
            :value="formatMoney(dashboard.financials?.collected_mtd)"
            :caption="dashboard.period?.display || 'Month to date'"
          />
        </KpiStrip>
      </section>

      <section v-if="dashboard.alerts?.length" class="space-y-2" aria-label="Alerts">
        <div
          v-for="(alert, i) in dashboard.alerts"
          :key="`${alert.title}-${i}`"
          class="flex items-center gap-3 rounded-xl border px-4 py-3 text-sm"
          :class="alertClass(alert.type)"
          role="alert"
        >
          <p class="min-w-0 flex-1 leading-snug">{{ alert.message || alert.title }}</p>
          <RouterLink
            v-if="alert.href"
            :to="alert.href"
            class="shrink-0 text-sm font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
          >
            View
          </RouterLink>
        </div>
      </section>

      <ErpPanel
        v-if="dashboard.portfolio"
        title="Portfolio overview"
        subtitle="Units, occupancy, and active leases"
      >
        <ExecutiveKpiCards :portfolio="dashboard.portfolio" />
      </ErpPanel>

      <ErpPanel
        title="Financial performance"
        :subtitle="financialSubtitle"
      >
        <FinancialKpiCards
          :financials="dashboard.financials"
          :format-money="formatMoney"
          :format-percent="formatPercent"
        />
      </ErpPanel>

      <div class="grid gap-5 xl:grid-cols-2">
        <ErpPanel>
          <template #header>
            <div class="flex items-center justify-between gap-3">
              <div class="min-w-0">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Collections snapshot</h2>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Open receivables aging</p>
              </div>
              <ErpButton variant="ghost" size="sm" :to="{ name: 'Reports' }">Full report</ErpButton>
            </div>
          </template>
          <DashboardCollectionsPanel
            :buckets="dashboard.collections?.aging_buckets"
            :format-money="formatMoney"
          />
          <p
            v-if="dashboard.collections?.delinquency?.total"
            class="mt-4 text-sm leading-snug text-amber-700 dark:text-amber-300"
          >
            {{ dashboard.collections.delinquency.total }} delinquent account(s) flagged for follow-up.
          </p>
        </ErpPanel>

        <ErpPanel>
          <template #header>
            <div class="flex items-center justify-between gap-3">
              <div class="min-w-0">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Billing pipeline</h2>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Work in progress for the current period</p>
              </div>
              <ErpButton variant="ghost" size="sm" :to="{ name: 'Invoices' }">Billing close</ErpButton>
            </div>
          </template>
          <DashboardOperationsPanel :operations="dashboard.operations" />
        </ErpPanel>
      </div>

      <ErpPanel title="Quick actions" subtitle="Common operational tasks">
        <QuickActions />
      </ErpPanel>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { PageHeader, ErpPanel, KpiCard, KpiStrip, AlertBanner, ErpButton } from '@/components/erp'
import { useDashboard } from '@/composables/useDashboard'
import ExecutiveKpiCards from '../components/ExecutiveKpiCards.vue'
import FinancialKpiCards from '../components/FinancialKpiCards.vue'
import DashboardSkeleton from '../components/DashboardSkeleton.vue'
import DashboardCollectionsPanel from '../components/DashboardCollectionsPanel.vue'
import DashboardOperationsPanel from '../components/DashboardOperationsPanel.vue'
import QuickActions from '../components/QuickActions.vue'

const { dashboard, loading, fetchDashboard, formatMoney, formatPercent } = useDashboard()
const pageError = ref('')

const headerDescription = computed(() => {
  const company = dashboard.value.user?.company
  const period = dashboard.value.period?.display
  if (company && period) {
    return `${company} — ${period} operational snapshot`
  }
  return 'Operational snapshot — portfolio health, financials, and items needing attention.'
})

const financialSubtitle = computed(() =>
  dashboard.value.period?.display
    ? `Billing and collections — ${dashboard.value.period.display}`
    : 'Billing and collections',
)

function alertClass(type) {
  if (type === 'error') {
    return 'border-red-200 bg-red-50 text-red-800 dark:border-red-800/50 dark:bg-red-950/40 dark:text-red-300'
  }
  if (type === 'warning') {
    return 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-800/50 dark:bg-amber-950/40 dark:text-amber-300'
  }
  return 'border-blue-200 bg-blue-50 text-blue-900 dark:border-blue-800/50 dark:bg-blue-950/40 dark:text-blue-300'
}

async function refresh() {
  pageError.value = ''
  try {
    await fetchDashboard()
  } catch (e) {
    pageError.value = e?.response?.data?.message || 'Unable to load dashboard. Please try again.'
  }
}

onMounted(refresh)
</script>
