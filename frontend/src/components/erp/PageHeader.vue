<template>
  <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div class="min-w-0">
      <p v-if="eyebrow" class="text-xs font-semibold uppercase tracking-wider text-indigo-600">
        {{ eyebrow }}
      </p>
      <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
        {{ title }}
      </h1>
      <p v-if="description" class="mt-1 max-w-2xl text-sm text-slate-500">
        {{ description }}
      </p>
    </div>
    <div v-if="$slots.actions" class="flex shrink-0 flex-wrap items-center gap-2">
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '' },
  eyebrow: { type: String, default: '' },
})
</script>
