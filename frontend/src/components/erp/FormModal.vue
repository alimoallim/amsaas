<template>
  <Teleport to="body">
    <Transition name="erp-fade">
      <div
        v-if="open"
        class="fixed inset-0 z-[220] flex items-end justify-center p-0 sm:items-center sm:p-4"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="titleId"
      >
        <div class="absolute inset-0 bg-slate-900/55 backdrop-blur-[2px]" @click="onBackdrop" />

        <div
          class="relative flex max-h-[92vh] w-full flex-col overflow-hidden rounded-t-2xl border border-slate-200 bg-white shadow-2xl sm:rounded-xl"
          :class="sizeClass"
        >
          <header class="flex shrink-0 items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <h2 :id="titleId" class="text-lg font-semibold text-slate-900">{{ title }}</h2>
                <StatusBadge v-if="state" :status="state" :label="state" />
              </div>
              <p v-if="subtitle" class="mt-0.5 text-sm text-slate-500">{{ subtitle }}</p>
            </div>
            <button
              type="button"
              class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
              aria-label="Close"
              @click="requestClose"
            >
              <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </header>

          <div class="erp-form-modal-body min-h-0 flex-1 overflow-y-auto px-5 py-4">
            <slot />
          </div>

          <footer
            v-if="showFooter"
            class="flex shrink-0 flex-col-reverse gap-2 border-t border-slate-100 bg-slate-50/90 px-5 py-4 sm:flex-row sm:justify-end"
          >
            <slot name="footer">
              <ErpButton variant="secondary" type="button" @click="requestClose">{{ cancelLabel }}</ErpButton>
              <ErpButton
                v-if="showSaveDraft"
                variant="ghost"
                type="button"
                :loading="savingDraft"
                :disabled="saving || savingDraft"
                @click="$emit('save-draft')"
              >
                Save draft
              </ErpButton>
              <ErpButton
                variant="primary"
                type="button"
                :loading="saving"
                :disabled="saving || savingDraft"
                @click="$emit('save')"
              >
                {{ saveLabel }}
              </ErpButton>
            </slot>
          </footer>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, useId } from 'vue'
import ErpButton from './ErpButton.vue'
import StatusBadge from './StatusBadge.vue'

const props = defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  state: { type: String, default: '' },
  size: { type: String, default: 'lg' },
  cancelLabel: { type: String, default: 'Cancel' },
  saveLabel: { type: String, default: 'Save' },
  saving: { type: Boolean, default: false },
  savingDraft: { type: Boolean, default: false },
  showFooter: { type: Boolean, default: true },
  showSaveDraft: { type: Boolean, default: false },
  closeOnBackdrop: { type: Boolean, default: true },
  dirty: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'save', 'save-draft'])

const titleId = useId()

const sizeClass = computed(
  () =>
    ({
      md: 'sm:max-w-md',
      lg: 'sm:max-w-lg',
      xl: 'sm:max-w-2xl',
      '2xl': 'sm:max-w-4xl',
      full: 'sm:max-w-6xl',
    })[props.size] || 'sm:max-w-2xl'
)

function requestClose() {
  emit('close')
}

function onBackdrop() {
  if (props.closeOnBackdrop) requestClose()
}
</script>
