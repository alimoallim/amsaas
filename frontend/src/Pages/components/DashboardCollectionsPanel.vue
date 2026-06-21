<template>
  <DashboardMetricGrid :items="buckets" :columns="6" value-tone="money" compact />
</template>

<script setup>
import { computed } from 'vue'
import DashboardMetricGrid from './DashboardMetricGrid.vue'

const props = defineProps({
  buckets: { type: Object, default: () => ({}) },
  formatMoney: { type: Function, required: true },
})

const bucketDefs = [
  { key: 'current', label: 'Current' },
  { key: 'days_1_30', label: '1–30 days' },
  { key: 'days_31_60', label: '31–60 days' },
  { key: 'days_61_90', label: '61–90 days' },
  { key: 'days_over_90', label: '90+ days' },
  { key: 'total', label: 'Total open' },
]

const buckets = computed(() =>
  bucketDefs.map((def) => ({
    ...def,
    value: props.formatMoney(props.buckets?.[def.key]?.amount ?? 0),
    caption: `${props.buckets?.[def.key]?.count ?? 0} open`,
  })),
)
</script>
