<template>
  <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-4" :class="gridClass">
    <slot />
  </div>
</template>

<script setup>
defineProps({
  gridClass: { type: String, default: '' },
})
</script>
