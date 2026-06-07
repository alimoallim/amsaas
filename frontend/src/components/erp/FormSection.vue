<template>
  <section
    class="border-b border-slate-100 last:border-b-0"
    :class="[
      compact ? 'py-4' : 'px-5 py-6',
      { 'bg-slate-50/40': tinted },
    ]"
  >
    <div class="mb-4">
      <h2 class="text-sm font-semibold text-slate-900">{{ title }}</h2>
      <p v-if="description" class="mt-0.5 text-xs text-slate-500">{{ description }}</p>
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
