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
  error: 'border-red-200 bg-red-50 text-red-800 dark:border-red-800/50 dark:bg-red-950/40 dark:text-red-300',
  warning: 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-800/50 dark:bg-amber-950/40 dark:text-amber-300',
  info: 'border-blue-200 bg-blue-50 text-blue-900 dark:border-blue-800/50 dark:bg-blue-950/40 dark:text-blue-300',
  success: 'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-800/50 dark:bg-emerald-950/40 dark:text-emerald-300',
}[props.variant] || 'border-red-200 bg-red-50 text-red-800 dark:border-red-800/50 dark:bg-red-950/40 dark:text-red-300'))
</script>
