<template>
  <span
    class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium capitalize"
    :class="toneClass"
  >
    <span v-if="dot" class="h-1.5 w-1.5 rounded-full" :class="dotClass" />
    <slot>{{ label }}</slot>
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: { type: String, default: 'default' },
  label: { type: String, default: '' },
  dot: { type: Boolean, default: true },
})

const toneClass = computed(() => {
  const s = (props.status || '').toLowerCase()
  const map = {
    active: 'border-emerald-200 bg-emerald-50 text-emerald-800',
    inactive: 'border-slate-200 bg-slate-100 text-slate-600',
    draft: 'border-amber-200 bg-amber-50 text-amber-800',
    archived: 'border-red-200 bg-red-50 text-red-800',
    pending: 'border-blue-200 bg-blue-50 text-blue-800',
    posted: 'border-emerald-200 bg-emerald-50 text-emerald-800',
    overdue: 'border-red-200 bg-red-50 text-red-800',
    faulty: 'border-red-200 bg-red-50 text-red-800',
    approved: 'border-emerald-200 bg-emerald-50 text-emerald-800',
    available: 'border-emerald-200 bg-emerald-50 text-emerald-800',
    occupied: 'border-blue-200 bg-blue-50 text-blue-800',
    reserved: 'border-amber-200 bg-amber-50 text-amber-800',
    maintenance: 'border-orange-200 bg-orange-50 text-orange-800',
    terminated: 'border-slate-200 bg-slate-100 text-slate-600',
    pending_approval: 'border-blue-200 bg-blue-50 text-blue-800',
    partially_paid: 'border-amber-200 bg-amber-50 text-amber-800',
    paid: 'border-emerald-200 bg-emerald-50 text-emerald-800',
    finalized: 'border-indigo-200 bg-indigo-50 text-indigo-800',
    expired: 'border-slate-200 bg-slate-100 text-slate-600',
    rejected: 'border-red-200 bg-red-50 text-red-800',
    verified: 'border-emerald-200 bg-emerald-50 text-emerald-800',
  }
  return map[s] || 'border-slate-200 bg-slate-50 text-slate-700'
})

const dotClass = computed(() => {
  const s = (props.status || '').toLowerCase()
  if (['active', 'approved', 'posted'].includes(s)) return 'bg-emerald-500'
  if (['draft', 'pending'].includes(s)) return 'bg-amber-500'
  if (['inactive', 'archived', 'faulty', 'overdue'].includes(s)) return 'bg-slate-400'
  return 'bg-indigo-500'
})
</script>
