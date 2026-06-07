<template>
  <ErpPanel :title="title" :subtitle="subtitle">
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
      <slot />
    </div>

    <div
      v-if="chips?.length"
      class="mt-3 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-3"
    >
      <span class="text-xs font-medium uppercase tracking-wide text-slate-500">Active</span>
      <button
        v-for="chip in chips"
        :key="chip.key"
        type="button"
        class="inline-flex items-center gap-1 rounded-full border border-indigo-200 bg-indigo-50 px-2.5 py-0.5 text-xs font-medium text-indigo-800 transition hover:bg-indigo-100"
        @click="$emit('remove-chip', chip.key)"
      >
        <span>{{ chip.label }}: {{ chip.value }}</span>
        <span aria-hidden="true">×</span>
      </button>
      <ErpButton variant="ghost" size="sm" @click="$emit('clear-all')">Clear all</ErpButton>
    </div>
  </ErpPanel>
</template>

<script setup>
import ErpPanel from './ErpPanel.vue'
import ErpButton from './ErpButton.vue'

defineProps({
  title: { type: String, default: 'Filters' },
  subtitle: { type: String, default: 'Results update as you change filters' },
  chips: { type: Array, default: () => [] },
})

defineEmits(['clear-all', 'remove-chip'])
</script>
