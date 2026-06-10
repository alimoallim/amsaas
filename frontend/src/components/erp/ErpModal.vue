<template>
  <Teleport to="body">
    <Transition name="erp-fade">
      <div
        v-if="open"
        class="fixed inset-0 z-[210] flex items-end justify-center p-0 sm:items-center sm:p-4"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="titleId"
      >
        <div class="absolute inset-0 bg-slate-900/55 backdrop-blur-[2px]" @click="onBackdrop" />

        <div
          class="relative flex max-h-[90vh] w-full flex-col overflow-hidden rounded-t-2xl border border-slate-200 bg-white shadow-2xl sm:max-w-lg sm:rounded-xl dark:border-slate-700 dark:bg-slate-900"
          :class="sizeClass"
        >
          <header class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4 dark:border-slate-700">
            <div>
              <h2 :id="titleId" class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ title }}</h2>
              <p v-if="subtitle" class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">{{ subtitle }}</p>
            </div>
            <button
              type="button"
              class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-200"
              aria-label="Close"
              @click="requestClose"
            >
              <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </header>

          <div
            class="flex-1 overflow-y-auto px-5 py-4"
            @input.capture="markTouched"
            @change.capture="markTouched"
          >
            <slot />
          </div>

          <footer
            v-if="$slots.footer || showDefaultFooter"
            class="flex flex-col-reverse gap-2 border-t border-slate-100 bg-slate-50/80 px-5 py-4 sm:flex-row sm:justify-end dark:border-slate-700 dark:bg-slate-800/50"
          >
            <slot name="footer">
              <ErpButton variant="secondary" @click="requestClose">{{ cancelLabel }}</ErpButton>
              <ErpButton
                :variant="confirmVariant"
                :loading="loading"
                @click="$emit('confirm')"
              >
                {{ confirmLabel }}
              </ErpButton>
            </slot>
          </footer>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, useId, watch, onUnmounted } from 'vue'
import ErpButton from './ErpButton.vue'
import { useModalCloseGuard } from '@/composables/useModalCloseGuard'
import { useConfirmState } from '@/composables/useConfirm'

const props = defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  size: { type: String, default: 'md' },
  cancelLabel: { type: String, default: 'Cancel' },
  confirmLabel: { type: String, default: 'Confirm' },
  confirmVariant: { type: String, default: 'primary' },
  loading: { type: Boolean, default: false },
  showDefaultFooter: { type: Boolean, default: true },
  closeOnBackdrop: { type: Boolean, default: false },
  closeOnEscape: { type: Boolean, default: true },
  confirmBeforeClose: { type: Boolean, default: true },
  dirty: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'confirm'])

const titleId = useId()
const { state: confirmState } = useConfirmState()

const { markTouched, requestClose } = useModalCloseGuard({
  open: () => props.open,
  dirty: () => props.dirty,
  confirmBeforeClose: () => props.confirmBeforeClose,
  onClose: () => emit('close'),
})

defineExpose({ requestClose })

const sizeClass = computed(() => ({
  sm: 'sm:max-w-md',
  md: 'sm:max-w-lg',
  lg: 'sm:max-w-2xl',
  xl: 'sm:max-w-4xl',
}[props.size] || 'sm:max-w-lg'))

function onBackdrop() {
  if (props.closeOnBackdrop) requestClose()
}

function onEscapeKey(event) {
  if (!props.open || !props.closeOnEscape || event.key !== 'Escape') return
  if (confirmState.open) return
  event.preventDefault()
  requestClose()
}

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      document.addEventListener('keydown', onEscapeKey)
    } else {
      document.removeEventListener('keydown', onEscapeKey)
    }
  },
  { immediate: true },
)

onUnmounted(() => {
  document.removeEventListener('keydown', onEscapeKey)
})
</script>
