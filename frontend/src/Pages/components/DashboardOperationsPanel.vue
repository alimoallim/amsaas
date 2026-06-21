<template>
  <DashboardMetricGrid :items="stages" :columns="4" compact />
</template>

<script setup>
import { computed } from 'vue'
import DashboardMetricGrid from './DashboardMetricGrid.vue'

const props = defineProps({
  operations: { type: Object, default: () => ({}) },
})

const stages = computed(() => [
  {
    key: 'meter_readings',
    label: 'Meter readings',
    value: props.operations?.meter_readings_pending ?? 0,
    caption: 'Pending approval',
  },
  {
    key: 'utility_charges',
    label: 'Utility charges',
    value: props.operations?.pending_utility_charges ?? 0,
    caption: 'Awaiting approval',
  },
  {
    key: 'draft_invoices',
    label: 'Draft invoices',
    value: props.operations?.draft_invoices ?? 0,
    caption: 'Ready for issue',
  },
  {
    key: 'issued_unpaid',
    label: 'Issued unpaid',
    value: props.operations?.issued_unpaid ?? 0,
    caption: 'Awaiting payment',
    tone: 'accent',
  },
])
</script>
