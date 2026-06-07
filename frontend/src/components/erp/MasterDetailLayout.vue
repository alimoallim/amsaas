<template>
  <div
    class="grid min-h-[480px] gap-0 overflow-hidden rounded-xl border border-slate-200 bg-white lg:grid-cols-[minmax(280px,360px)_1fr]"
  >
    <aside
      class="flex max-h-[70vh] flex-col border-b border-slate-200 lg:max-h-none lg:border-b-0 lg:border-r"
      aria-label="Worklist"
    >
      <div v-if="$slots['list-header']" class="border-b border-slate-100 px-4 py-3">
        <slot name="list-header" />
      </div>
      <div class="flex-1 overflow-y-auto">
        <slot name="list" />
      </div>
    </aside>

    <section class="flex flex-col bg-slate-50/50" aria-label="Detail">
      <div v-if="!$slots.detail" class="flex flex-1 items-center justify-center p-8 text-sm text-slate-500">
        {{ emptyDetailLabel }}
      </div>
      <div v-else class="flex flex-1 flex-col overflow-y-auto p-5">
        <slot name="detail" />
      </div>
    </section>
  </div>
</template>

<script setup>
defineProps({
  emptyDetailLabel: { type: String, default: 'Select an item from the list' },
})
</script>
