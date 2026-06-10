import { ref, watch, computed, inject } from 'vue'
import { useConfirm } from '@/composables/useConfirm'

/**
 * Guards modal close when the user has unsaved input.
 * Used by FormModal and ErpModal.
 */
export function useModalCloseGuard({
  open,
  dirty = () => false,
  confirmBeforeClose = () => true,
  onClose,
}) {
  const userTouched = ref(false)
  const { confirm } = useConfirm()

  watch(
    () => (typeof open === 'function' ? open() : open),
    (isOpen) => {
      if (isOpen) userTouched.value = false
    },
  )

  const hasUnsavedChanges = computed(() => {
    const isDirty = typeof dirty === 'function' ? dirty() : dirty
    return Boolean(isDirty) || userTouched.value
  })

  function markTouched(event) {
    if (!event || event.isTrusted) {
      userTouched.value = true
    }
  }

  async function requestClose() {
    const shouldConfirm =
      (typeof confirmBeforeClose === 'function' ? confirmBeforeClose() : confirmBeforeClose)
      && hasUnsavedChanges.value

    if (shouldConfirm) {
      const discard = await confirm({
        title: 'Unsaved changes',
        message: 'You have unsaved changes. Are you sure you want to close this form?',
        confirmLabel: 'Discard changes',
        cancelLabel: 'Continue editing',
        variant: 'danger',
      })
      if (!discard) return false
    }

    onClose?.()
    return true
  }

  return {
    userTouched,
    hasUnsavedChanges,
    markTouched,
    requestClose,
  }
}

/** Injection key for nested forms to request a guarded close */
export const MODAL_REQUEST_CLOSE_KEY = Symbol('modalRequestClose')

export function useModalRequestClose() {
  return inject(MODAL_REQUEST_CLOSE_KEY, null)
}
