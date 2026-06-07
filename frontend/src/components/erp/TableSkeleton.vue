<template>
  <div class="animate-pulse space-y-3 px-6 py-8">
    <div v-for="i in rows" :key="i" class="flex gap-4">
      <div class="h-4 flex-1 rounded bg-slate-200" />
      <div class="h-4 w-24 rounded bg-slate-200" />
      <div class="h-4 w-20 rounded bg-slate-200" />
      <div class="h-4 w-16 rounded bg-slate-200" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  rows: { type: Number, default: 6 },
})
</script>
