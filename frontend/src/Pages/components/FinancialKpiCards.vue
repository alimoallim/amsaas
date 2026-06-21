<template>
  <DashboardMetricGrid :items="cards" :columns="5" value-tone="money" />
</template>

<script setup>
import { computed } from 'vue'
import DashboardMetricGrid from './DashboardMetricGrid.vue'

const props = defineProps({
  financials: { type: Object, default: () => ({}) },
  formatMoney: { type: Function, default: null },
  formatPercent: { type: Function, default: null },
})

function money(value) {
  if (props.formatMoney) return props.formatMoney(value)
  if (value == null) return '$0'
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number(value))
}

function percent(value) {
  if (props.formatPercent) return props.formatPercent(value)
  if (value == null) return '0%'
  return `${Number(value)}%`
}

const cards = computed(() => [
  { key: 'rent_revenue', label: 'Rent revenue', value: money(props.financials?.rent_revenue) },
  { key: 'utility_revenue', label: 'Utility revenue', value: money(props.financials?.utility_revenue) },
  { key: 'collected', label: 'Collected', value: money(props.financials?.collected), tone: 'positive' },
  { key: 'outstanding', label: 'Outstanding', value: money(props.financials?.outstanding) },
  {
    key: 'collection_rate',
    label: 'Collection rate',
    value: percent(props.financials?.collection_rate),
    tone: 'accent',
  },
])
</script>
