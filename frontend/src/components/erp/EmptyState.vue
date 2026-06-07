<template>
  <div class="flex flex-col items-center justify-center px-6 py-14 text-center">
    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
      <slot name="icon">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V7a2 2 0 00-2-2h-3l-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2h6M6 7h12M10 11h4" />
        </svg>
      </slot>
    </div>
    <h3 class="text-sm font-semibold text-slate-900">{{ title }}</h3>
    <p v-if="description" class="mt-1 max-w-sm text-sm text-slate-500">{{ description }}</p>
    <div v-if="$slots.action" class="mt-5">
      <slot name="action" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '' },
})
</script>
