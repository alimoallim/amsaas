<template>
  <div class="flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-start sm:justify-between">
    <div class="min-w-0 flex-1">
      <div class="flex flex-wrap items-center gap-3">
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
          {{ title }}
        </h1>
        <StatusBadge v-if="status" :status="status" :label="statusLabel || status" />
      </div>
      <p v-if="subtitle" class="mt-1 text-sm text-slate-600">{{ subtitle }}</p>
      <p v-if="attributes?.length" class="mt-2 text-sm text-slate-500">
        {{ attributes.filter(Boolean).join(' · ') }}
      </p>
    </div>
    <div v-if="$slots.actions" class="flex shrink-0 flex-wrap items-center gap-2">
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup>
import StatusBadge from './StatusBadge.vue'

defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  status: { type: String, default: '' },
  statusLabel: { type: String, default: '' },
  /** Key attributes shown under title (Fiori object header). */
  attributes: { type: Array, default: () => [] },
})
</script>
