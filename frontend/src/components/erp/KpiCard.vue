<template>
  <div
    class="relative overflow-hidden rounded-xl border p-4 shadow-sm transition hover:shadow-md sm:p-5"
    :class="variant === 'accent' ? 'border-indigo-200 bg-gradient-to-br from-indigo-600 to-indigo-800 text-white dark:border-indigo-700' : 'border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900'"
  >
    <div class="flex items-start justify-between gap-3">
      <div>
        <p
          class="text-xs font-semibold uppercase tracking-wider"
          :class="variant === 'accent' ? 'text-indigo-200' : 'text-slate-400 dark:text-slate-500'"
        >
          {{ label }}
        </p>
        <p
          class="mt-1.5 text-xl font-bold tracking-tight tabular-nums sm:mt-2 sm:text-2xl"
          :class="variant === 'accent' ? 'text-white' : 'text-slate-900 dark:text-slate-100'"
        >
          {{ value }}
        </p>
        <p
          v-if="caption"
          class="mt-1 text-sm"
          :class="variant === 'accent' ? 'text-indigo-100' : 'text-slate-500 dark:text-slate-400'"
        >
          {{ caption }}
        </p>
      </div>
      <div
        v-if="$slots.icon"
        class="rounded-lg p-2.5"
        :class="variant === 'accent' ? 'bg-white/10 text-white' : 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400'"
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
