<template>
  <ErpPanel title="Filters" subtitle="Refine the list below">
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
      <slot />
      <div v-if="showClear" class="flex items-end justify-end lg:col-span-1">
        <ErpButton variant="ghost" size="sm" @click="$emit('clear')">
          Clear filters
        </ErpButton>
      </div>
    </div>
  </ErpPanel>
</template>

<script setup>
import ErpPanel from './ErpPanel.vue'
import ErpButton from './ErpButton.vue'

defineProps({
  showClear: { type: Boolean, default: true },
})

defineEmits(['clear'])
</script>
