<template>
  <div class="erp-page">
    <ErpPanel>
      <PageHeader
        eyebrow="Overview"
        :title="dashboard.user?.name ? `Welcome, ${dashboard.user.name}` : 'Dashboard'"
        description="Operational snapshot — portfolio health, financials, and items needing attention."
      />
    </ErpPanel>

    <DashboardSkeleton v-if="loading" />

    <template v-else>
      <KpiStrip v-if="dashboard.portfolio">
        <KpiCard
          label="Buildings"
          :value="dashboard.portfolio.buildings_count ?? '—'"
          caption="In portfolio"
        />
        <KpiCard
          label="Occupancy"
          :value="(dashboard.portfolio.occupancy_rate != null ? dashboard.portfolio.occupancy_rate + '%' : '—')"
          caption="Portfolio average"
        />
        <KpiCard
          label="Outstanding"
          :value="formatMoney(dashboard.financials?.outstanding_receivables)"
          caption="Receivables"
          variant="accent"
        />
        <KpiCard
          label="Collected (MTD)"
          :value="formatMoney(dashboard.financials?.collected_mtd)"
          caption="Month to date"
        />
      </KpiStrip>

      <AlertBanner
        v-for="(alert, i) in dashboard.alerts"
        :key="i"
        :message="alert.message || alert.title"
        :variant="alert.type === 'error' ? 'error' : alert.type === 'warning' ? 'warning' : 'info'"
        :dismissible="false"
        class="mb-0"
      />

      <div class="grid gap-5 lg:grid-cols-2">
        <ErpPanel title="Executive KPIs" subtitle="Portfolio metrics">
          <ExecutiveKpiCards :portfolio="dashboard.portfolio" />
        </ErpPanel>
        <ErpPanel title="Financial KPIs" subtitle="Billing and collections">
          <FinancialKpiCards :financials="dashboard.financials" />
        </ErpPanel>
      </div>

      <ErpPanel title="Quick actions">
        <QuickActions />
      </ErpPanel>
    </template>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '@/services/api'
import { PageHeader, ErpPanel, KpiCard, KpiStrip, AlertBanner } from '@/components/erp'
import ExecutiveKpiCards from '../components/ExecutiveKpiCards.vue'
import FinancialKpiCards from '../components/FinancialKpiCards.vue'
import DashboardSkeleton from '../components/DashboardSkeleton.vue'
import QuickActions from '../components/QuickActions.vue'

const loading = ref(true)
const dashboard = ref({ user: {}, alerts: [], portfolio: {}, financials: {} })

function formatMoney(v) {
  if (v == null) return '—'
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number(v))
}

const fetchDashboard = async () => {
  loading.value = true
  try {
    const { data } = await api.get('/dashboard')
    dashboard.value = data
  } catch (error) {
    console.error('Dashboard load failed', error)
  } finally {
    loading.value = false
  }
}

onMounted(fetchDashboard)
</script>
