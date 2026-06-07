<template>
  <component
    :is="tag"
    :type="tag === 'button' ? nativeType : undefined"
    :to="to"
    :disabled="disabled || loading"
    class="inline-flex items-center justify-center gap-2 rounded-lg text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:pointer-events-none disabled:opacity-50"
    :class="variantClass"
  >
    <svg
      v-if="loading"
      class="h-4 w-4 animate-spin"
      fill="none"
      viewBox="0 0 24 24"
    >
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
    </svg>
    <slot />
  </component>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'

const props = defineProps({
  variant: { type: String, default: 'primary' },
  size: { type: String, default: 'md' },
  nativeType: { type: String, default: 'button' },
  to: { type: [String, Object], default: null },
  disabled: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
})

const tag = computed(() => (props.to ? RouterLink : 'button'))

const variantClass = computed(() => {
  const size = props.size === 'sm' ? 'px-3 py-1.5 text-xs' : props.size === 'lg' ? 'px-5 py-2.5' : 'px-4 py-2'
  const map = {
    primary: 'bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-indigo-500',
    secondary: 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 focus:ring-slate-400',
    ghost: 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus:ring-slate-400',
    danger: 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
    success: 'bg-emerald-600 text-white hover:bg-emerald-700 focus:ring-emerald-500',
  }
  return `${size} ${map[props.variant] || map.primary}`
})
</script>
