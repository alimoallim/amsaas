<template>
  <section
    class="border-b border-slate-100 last:border-b-0 dark:border-slate-700"
    :class="[
      compact ? 'px-4 py-4 sm:px-5' : 'px-4 py-5 sm:px-5 sm:py-6',
      { 'bg-slate-50/40 dark:bg-slate-800/30': tinted },
    ]"
  >
    <div class="mb-4">
      <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ title }}</h2>
      <p v-if="description" class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ description }}</p>
    </div>
    <div class="space-y-4">
      <slot />
    </div>
  </section>
</template>

<script setup>
defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '' },
  tinted: { type: Boolean, default: false },
  /** Less padding for modals and nested forms */
  compact: { type: Boolean, default: false },
})
</script>
