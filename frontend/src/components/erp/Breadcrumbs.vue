<template>
  <nav aria-label="Breadcrumb" class="mb-4 text-sm text-slate-500 dark:text-slate-400">
    <ol class="flex flex-wrap items-center gap-1">
      <li v-for="(item, index) in items" :key="index" class="flex items-center gap-1">
        <span v-if="index > 0" class="text-slate-300 dark:text-slate-600" aria-hidden="true">›</span>
        <RouterLink
          v-if="item.to && index < items.length - 1"
          :to="item.to"
          class="font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300"
        >
          {{ item.label }}
        </RouterLink>
        <span
          v-else
          class="font-medium"
          :class="index === items.length - 1 ? 'text-slate-900 dark:text-slate-100' : 'text-slate-600 dark:text-slate-400'"
          aria-current="page"
        >
          {{ item.label }}
        </span>
      </li>
    </ol>
  </nav>
</template>

<script setup>
import { RouterLink } from 'vue-router'

defineProps({
  /** @type {{ label: string, to?: string|object }[]} */
  items: { type: Array, required: true },
})
</script>
