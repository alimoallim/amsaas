<template>
  <DashboardMetricGrid :items="cards" :columns="6" />
</template>

<script setup>
import { computed } from 'vue'
import DashboardMetricGrid from './DashboardMetricGrid.vue'

const props = defineProps({
  portfolio: { type: Object, default: () => ({}) },
})

const cards = computed(() => [
  { key: 'buildings', label: 'Buildings', value: props.portfolio?.buildings ?? 0 },
  { key: 'apartments', label: 'Apartments', value: props.portfolio?.apartments ?? 0 },
  { key: 'occupied', label: 'Occupied', value: props.portfolio?.occupied ?? 0, tone: 'positive' },
  { key: 'vacant', label: 'Vacant', value: props.portfolio?.vacant ?? 0 },
  { key: 'tenants', label: 'Tenants', value: props.portfolio?.tenants ?? 0 },
  { key: 'leases', label: 'Leases', value: props.portfolio?.leases ?? 0 },
])
</script>
