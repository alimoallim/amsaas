<template>
  <div class="animate-pulse space-y-6">
    <div class="space-y-2">
      <div class="h-7 w-56 rounded-lg bg-slate-200 dark:bg-slate-800" />
      <div class="h-4 w-72 max-w-full rounded-lg bg-slate-200 dark:bg-slate-800" />
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 xl:grid-cols-4">
      <div
        v-for="i in 4"
        :key="`kpi-${i}`"
        class="h-[5.5rem] rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
      >
        <div class="h-3 w-20 rounded bg-slate-200 dark:bg-slate-800" />
        <div class="mt-4 h-7 w-28 rounded bg-slate-200 dark:bg-slate-800" />
      </div>
    </div>

    <div
      v-for="section in 4"
      :key="`section-${section}`"
      class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900"
    >
      <div class="border-b border-slate-100 px-5 py-3 dark:border-slate-800">
        <div class="h-4 w-36 rounded bg-slate-200 dark:bg-slate-800" />
        <div class="mt-2 h-3 w-48 rounded bg-slate-200 dark:bg-slate-800" />
      </div>
      <div class="grid grid-cols-2 gap-px bg-slate-200 p-px dark:bg-slate-700 sm:grid-cols-3 xl:grid-cols-6">
        <div
          v-for="i in 6"
          :key="`${section}-tile-${i}`"
          class="h-[5.25rem] bg-white px-4 py-3 dark:bg-slate-900"
        >
          <div class="h-3 w-16 rounded bg-slate-200 dark:bg-slate-800" />
          <div class="mt-3 h-6 w-20 rounded bg-slate-200 dark:bg-slate-800" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
// Dashboard loading skeleton
</script>
