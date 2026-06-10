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

const d = (light, dark) => `${light} ${dark}`

const toneClass = computed(() => {
  const s = (props.status || '').toLowerCase()
  const map = {
    active: d('border-emerald-200 bg-emerald-50 text-emerald-800', 'dark:border-emerald-800/50 dark:bg-emerald-950/40 dark:text-emerald-300'),
    inactive: d('border-slate-200 bg-slate-100 text-slate-600', 'dark:border-slate-600 dark:bg-slate-800 dark:text-slate-400'),
    draft: d('border-amber-200 bg-amber-50 text-amber-800', 'dark:border-amber-800/50 dark:bg-amber-950/40 dark:text-amber-300'),
    archived: d('border-red-200 bg-red-50 text-red-800', 'dark:border-red-800/50 dark:bg-red-950/40 dark:text-red-300'),
    pending: d('border-blue-200 bg-blue-50 text-blue-800', 'dark:border-blue-800/50 dark:bg-blue-950/40 dark:text-blue-300'),
    posted: d('border-emerald-200 bg-emerald-50 text-emerald-800', 'dark:border-emerald-800/50 dark:bg-emerald-950/40 dark:text-emerald-300'),
    overdue: d('border-red-200 bg-red-50 text-red-800', 'dark:border-red-800/50 dark:bg-red-950/40 dark:text-red-300'),
    faulty: d('border-red-200 bg-red-50 text-red-800', 'dark:border-red-800/50 dark:bg-red-950/40 dark:text-red-300'),
    approved: d('border-emerald-200 bg-emerald-50 text-emerald-800', 'dark:border-emerald-800/50 dark:bg-emerald-950/40 dark:text-emerald-300'),
    available: d('border-emerald-200 bg-emerald-50 text-emerald-800', 'dark:border-emerald-800/50 dark:bg-emerald-950/40 dark:text-emerald-300'),
    occupied: d('border-blue-200 bg-blue-50 text-blue-800', 'dark:border-blue-800/50 dark:bg-blue-950/40 dark:text-blue-300'),
    reserved: d('border-amber-200 bg-amber-50 text-amber-800', 'dark:border-amber-800/50 dark:bg-amber-950/40 dark:text-amber-300'),
    maintenance: d('border-orange-200 bg-orange-50 text-orange-800', 'dark:border-orange-800/50 dark:bg-orange-950/40 dark:text-orange-300'),
    terminated: d('border-slate-200 bg-slate-100 text-slate-600', 'dark:border-slate-600 dark:bg-slate-800 dark:text-slate-400'),
    pending_approval: d('border-blue-200 bg-blue-50 text-blue-800', 'dark:border-blue-800/50 dark:bg-blue-950/40 dark:text-blue-300'),
    partially_paid: d('border-amber-200 bg-amber-50 text-amber-800', 'dark:border-amber-800/50 dark:bg-amber-950/40 dark:text-amber-300'),
    paid: d('border-emerald-200 bg-emerald-50 text-emerald-800', 'dark:border-emerald-800/50 dark:bg-emerald-950/40 dark:text-emerald-300'),
    finalized: d('border-indigo-200 bg-indigo-50 text-indigo-800', 'dark:border-indigo-800/50 dark:bg-indigo-950/40 dark:text-indigo-300'),
    expired: d('border-slate-200 bg-slate-100 text-slate-600', 'dark:border-slate-600 dark:bg-slate-800 dark:text-slate-400'),
    rejected: d('border-red-200 bg-red-50 text-red-800', 'dark:border-red-800/50 dark:bg-red-950/40 dark:text-red-300'),
    verified: d('border-emerald-200 bg-emerald-50 text-emerald-800', 'dark:border-emerald-800/50 dark:bg-emerald-950/40 dark:text-emerald-300'),
  }
  return map[s] || d('border-slate-200 bg-slate-50 text-slate-700', 'dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300')
})

const dotClass = computed(() => {
  const s = (props.status || '').toLowerCase()
  if (['active', 'approved', 'posted', 'paid', 'available', 'verified'].includes(s)) return 'bg-emerald-500'
  if (['draft', 'pending', 'reserved', 'partially_paid', 'pending_approval'].includes(s)) return 'bg-amber-500'
  if (['inactive', 'archived', 'faulty', 'overdue', 'terminated', 'expired', 'rejected'].includes(s)) return 'bg-slate-400'
  return 'bg-indigo-500'
})
</script>
