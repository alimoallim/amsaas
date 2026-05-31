<template>
    <div
        class="min-h-screen bg-slate-50 dark:bg-slate-950 transition-colors duration-300"
    >
        <!-- Loading State -->
        <DashboardSkeleton
            v-if="loading"
        />

        <template v-else>

            <!-- Header -->
            <DashboardHeader
                :user="dashboard.user"
            />

            <!-- Alerts -->
            <AlertPanel
                :alerts="dashboard.alerts"
            />

            <!-- Executive KPIs -->
            <ExecutiveKpiCards
                :portfolio="dashboard.portfolio"
            />

            <!-- Financial KPIs -->
            <FinancialKpiCards
                :financials="dashboard.financials"
            />

            <!-- Quick Actions -->
            <QuickActions />

        </template>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '@/services/api'

import DashboardHeader from '../components/DashboardHeader.vue'
import ExecutiveKpiCards from '../components/ExecutiveKpiCards.vue'
import FinancialKpiCards from '../components/FinancialKpiCards.vue'
import AlertPanel from '../components/AlertPanel.vue'
import DashboardSkeleton from '../components/DashboardSkeleton.vue'
import QuickActions from '../components/QuickActions.vue'

const loading = ref(true)

const dashboard = ref({

    user: {},

    alerts: [],

    portfolio: {},

    financials: {}
})

const fetchDashboard = async () => {

    loading.value = true

    try {

        const { data } = await api.get(
            '/dashboard'
        )

        dashboard.value = data
    }
    catch (error) {

        console.error(
            'Dashboard load failed',
            error
        )
    }
    finally {

        loading.value = false
    }
}

onMounted(() => {

    fetchDashboard()
})
</script>