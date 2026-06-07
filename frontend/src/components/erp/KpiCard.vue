<template>
  <div
    class="relative overflow-hidden rounded-xl border p-5 shadow-sm transition hover:shadow-md"
    :class="variant === 'accent' ? 'border-indigo-200 bg-gradient-to-br from-indigo-600 to-indigo-800 text-white' : 'border-slate-200 bg-white'"
  >
    <div class="flex items-start justify-between gap-3">
      <div>
        <p
          class="text-xs font-semibold uppercase tracking-wider"
          :class="variant === 'accent' ? 'text-indigo-200' : 'text-slate-400'"
        >
          {{ label }}
        </p>
        <p
          class="mt-2 text-2xl font-bold tracking-tight tabular-nums"
          :class="variant === 'accent' ? 'text-white' : 'text-slate-900'"
        >
          {{ value }}
        </p>
        <p
          v-if="caption"
          class="mt-1 text-sm"
          :class="variant === 'accent' ? 'text-indigo-100' : 'text-slate-500'"
        >
          {{ caption }}
        </p>
      </div>
      <div
        v-if="$slots.icon"
        class="rounded-lg p-2.5"
        :class="variant === 'accent' ? 'bg-white/10 text-white' : 'bg-indigo-50 text-indigo-600'"
      >
        <slot name="icon" />
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  label: { type: String, required: true },
  value: { type: [String, Number], required: true },
  caption: { type: String, default: '' },
  variant: { type: String, default: 'default' },
})
</script>
