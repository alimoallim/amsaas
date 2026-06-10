<template>
  <div class="erp-page">
    <ErpPanel>
      <PageHeader
        :eyebrow="eyebrow"
        :title="displayTitle"
        :description="description"
      >
        <template #actions>
          <slot name="actions" />
        </template>
      </PageHeader>
    </ErpPanel>

    <slot name="kpis" />

    <slot name="filters" />

    <div
      v-if="selectionToolbar && hasSelection"
      class="flex flex-wrap items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50/80 px-4 py-2 text-sm text-indigo-900 dark:border-indigo-800/50 dark:bg-indigo-950/40 dark:text-indigo-200"
    >
      <span class="font-medium">{{ selectionLabel }}</span>
      <slot name="selection-actions" />
    </div>

    <slot name="table" />

    <slot name="detail" />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import ErpPanel from './ErpPanel.vue'
import PageHeader from './PageHeader.vue'

const props = defineProps({
  title: { type: String, required: true },
  count: { type: [Number, String], default: null },
  eyebrow: { type: String, default: '' },
  description: { type: String, default: '' },
  selectionToolbar: { type: Boolean, default: false },
  selectedCount: { type: Number, default: 0 },
})

const displayTitle = computed(() => {
  if (props.count == null || props.count === '') {
    return props.title
  }
  return `${props.title} (${props.count})`
})

const hasSelection = computed(() => props.selectedCount > 0)

const selectionLabel = computed(() => {
  const n = props.selectedCount
  return n === 1 ? '1 item selected' : `${n} items selected`
})
</script>
