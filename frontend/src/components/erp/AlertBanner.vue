<template>
  <div
    v-if="show"
    class="flex items-start gap-3 rounded-lg border px-4 py-3 text-sm"
    :class="variantClass"
    role="alert"
  >
    <slot>{{ message }}</slot>
    <button
      v-if="dismissible"
      type="button"
      class="ml-auto shrink-0 opacity-70 hover:opacity-100"
      aria-label="Dismiss"
      @click="$emit('dismiss')"
    >
      ✕
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  message: { type: String, default: '' },
  variant: { type: String, default: 'error' },
  show: { type: Boolean, default: true },
  dismissible: { type: Boolean, default: true },
})

defineEmits(['dismiss'])

const variantClass = computed(() => ({
  error: 'border-red-200 bg-red-50 text-red-800',
  warning: 'border-amber-200 bg-amber-50 text-amber-900',
  info: 'border-blue-200 bg-blue-50 text-blue-900',
  success: 'border-emerald-200 bg-emerald-50 text-emerald-900',
}[props.variant] || 'border-red-200 bg-red-50 text-red-800'))
</script>
