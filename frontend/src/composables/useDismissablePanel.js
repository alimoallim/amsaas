import { onBeforeUnmount, watch } from 'vue'

/**
 * Close a teleported dropdown/panel on outside pointer down.
 * Avoids @vueuse/onClickOutside "subTree" errors with Teleport targets.
 */
export function useDismissablePanel(open, rootRef, panelRef, onClose) {
  function onPointerDown(event) {
    if (!open.value) return
    const target = event.target
    if (!(target instanceof Node)) return
    if (rootRef.value?.contains(target)) return
    if (panelRef.value?.contains(target)) return
    onClose()
  }

  watch(open, (isOpen) => {
    if (isOpen) {
      document.addEventListener('pointerdown', onPointerDown, true)
    } else {
      document.removeEventListener('pointerdown', onPointerDown, true)
    }
  })

  onBeforeUnmount(() => {
    document.removeEventListener('pointerdown', onPointerDown, true)
    onClose()
  })
}
