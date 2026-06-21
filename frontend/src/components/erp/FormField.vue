<template>
  <div class="erp-form-field min-w-0" :class="colSpan">
    <label v-if="label" :for="id" class="erp-label">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    <p v-if="hint && !error" class="mb-1 text-xs text-slate-500">{{ hint }}</p>
    <div class="erp-form-field__control">
      <slot :id="id" :invalid="!!error" />
    </div>
    <p v-if="error" class="mt-1 text-xs text-red-600">{{ error }}</p>
  </div>
</template>

<script setup>
import { computed, useId } from 'vue'

const props = defineProps({
  label: { type: String, default: '' },
  hint: { type: String, default: '' },
  error: { type: String, default: '' },
  required: { type: Boolean, default: false },
  /** Stable DOM id for inputs (recommended on auth forms for mobile browsers). */
  fieldId: { type: String, default: '' },
  /** 1 | 2 | 3 | full — span within FormGrid (2- or 3-column) */
  span: { type: String, default: '1' },
})

const autoId = useId()
const id = computed(() => props.fieldId || autoId)

const colSpan = computed(() => {
  const map = {
    1: '',
    2: 'sm:col-span-2',
    3: 'sm:col-span-2 lg:col-span-3',
    full: 'sm:col-span-2 lg:col-span-3',
  }
  return map[props.span] || ''
})
</script>
