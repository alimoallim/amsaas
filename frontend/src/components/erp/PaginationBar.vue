<template>
  <div
    v-if="meta?.total > 0"
    class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50/60 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5"
  >
    <p class="text-xs text-slate-500 sm:text-sm">
      Showing
      <span class="font-semibold text-slate-800">{{ meta.from || 0 }}</span>
      –
      <span class="font-semibold text-slate-800">{{ meta.to || 0 }}</span>
      of
      <span class="font-semibold text-slate-800">{{ meta.total }}</span>
    </p>
    <div class="flex items-center justify-end gap-2">
      <ErpButton
        variant="secondary"
        size="sm"
        :disabled="meta.current_page <= 1 || loading"
        @click="$emit('page-change', meta.current_page - 1)"
      >
        Previous
      </ErpButton>
      <span class="px-2 text-xs font-medium text-slate-600">
        Page {{ meta.current_page }} / {{ meta.last_page }}
      </span>
      <ErpButton
        variant="secondary"
        size="sm"
        :disabled="meta.current_page >= meta.last_page || loading"
        @click="$emit('page-change', meta.current_page + 1)"
      >
        Next
      </ErpButton>
    </div>
  </div>
</template>

<script setup>
import ErpButton from './ErpButton.vue'

defineProps({
  meta: { type: Object, required: true },
  loading: { type: Boolean, default: false },
})

defineEmits(['page-change'])
</script>
