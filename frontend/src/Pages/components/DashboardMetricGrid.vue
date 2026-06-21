<template>
  <div
    class="grid gap-px overflow-hidden rounded-xl border border-slate-200 bg-slate-200 dark:border-slate-700 dark:bg-slate-700"
    :class="gridClass"
  >
    <div
      v-for="item in items"
      :key="item.key ?? item.label"
      class="flex min-h-[5.25rem] min-w-0 flex-col justify-center bg-white px-4 py-3.5 dark:bg-slate-900 sm:min-h-[5.75rem] sm:px-5 sm:py-4"
    >
      <span class="truncate text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
        {{ item.label }}
      </span>
      <span
        class="mt-1.5 truncate text-lg font-bold leading-tight tracking-tight tabular-nums sm:text-xl"
        :class="toneClass(item)"
      >
        {{ item.value }}
      </span>
      <span
        v-if="item.caption"
        class="mt-1 truncate text-xs text-slate-500 dark:text-slate-400"
      >
        {{ item.caption }}
      </span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  items: { type: Array, required: true },
  columns: { type: Number, default: 0 },
  valueTone: { type: String, default: 'default' },
  compact: { type: Boolean, default: false },
})

const gridClass = computed(() => {
  const count = props.columns > 0 ? props.columns : props.items.length

  if (props.compact) {
    if (count <= 4) return 'grid-cols-2'
    return 'grid-cols-2 sm:grid-cols-3'
  }

  if (count <= 2) return 'grid-cols-1 sm:grid-cols-2'
  if (count === 3) return 'grid-cols-1 sm:grid-cols-3'
  if (count === 4) return 'grid-cols-2 lg:grid-cols-4'
  if (count === 5) return 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-5'
  return 'grid-cols-2 sm:grid-cols-3 xl:grid-cols-6'
})

function toneClass(item) {
  const tone = item.tone ?? props.valueTone
  if (tone === 'positive' || tone === 'money') {
    return 'text-emerald-600 dark:text-emerald-400'
  }
  if (tone === 'accent') {
    return 'text-indigo-600 dark:text-indigo-400'
  }
  return 'text-slate-900 dark:text-slate-100'
}
</script>
