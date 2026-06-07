<template>
  <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4" :class="gridClass">
    <slot />
  </div>
</template>

<script setup>
defineProps({
  gridClass: { type: String, default: '' },
})
</script>
