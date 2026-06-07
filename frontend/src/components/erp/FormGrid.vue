<template>
  <div class="erp-form-grid" :class="cols === 3 ? 'erp-form-grid--3' : ''">
    <slot />
  </div>
</template>

<script setup>
defineProps({
  /** 2 (default) or 3 columns on large screens */
  cols: { type: Number, default: 2 },
})
</script>
