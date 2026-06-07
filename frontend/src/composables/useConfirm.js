import { reactive } from 'vue'

const state = reactive({
  open: false,
  title: 'Confirm',
  message: '',
  confirmLabel: 'Confirm',
  cancelLabel: 'Cancel',
  variant: 'danger',
})

let resolvePromise = null

/**
 * Global confirm dialog (pair with ConfirmModal in App.vue).
 * @returns {Promise<boolean>}
 */
export function confirmAction(options) {
  const {
    title = 'Confirm',
    message = 'Are you sure?',
    confirmLabel = 'Confirm',
    cancelLabel = 'Cancel',
    variant = 'danger',
  } = typeof options === 'string' ? { message: options } : options

  state.title = title
  state.message = message
  state.confirmLabel = confirmLabel
  state.cancelLabel = cancelLabel
  state.variant = variant
  state.open = true

  return new Promise((resolve) => {
    resolvePromise = resolve
  })
}

export function resolveConfirm(value) {
  state.open = false
  resolvePromise?.(value)
  resolvePromise = null
}

export function useConfirmState() {
  return { state, resolveConfirm }
}

export function useConfirm() {
  return { confirm: confirmAction }
}
