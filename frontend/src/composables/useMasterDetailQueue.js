import { ref, computed, watch } from 'vue'

/**
 * Fiori FCL — selection + advance after approve/reject.
 */
export function useMasterDetailQueue(itemsRef) {
  const selectedId = ref(null)

  const selectedIndex = computed(() => {
    const items = itemsRef.value || []
    return items.findIndex((item) => item.id === selectedId.value)
  })

  const selectedItem = computed(() => {
    const items = itemsRef.value || []
    const idx = selectedIndex.value
    return idx >= 0 ? items[idx] : null
  })

  function select(item) {
    if (item?.id != null) {
      selectedId.value = item.id
    }
  }

  function selectFirst() {
    const items = itemsRef.value || []
    if (items.length && selectedId.value == null) {
      selectedId.value = items[0].id
    }
  }

  function selectNext() {
    const items = itemsRef.value || []
    const idx = selectedIndex.value
    if (idx >= 0 && idx < items.length - 1) {
      selectedId.value = items[idx + 1].id
      return items[idx + 1]
    }
    if (items.length) {
      selectedId.value = items[items.length - 1].id
      return items[items.length - 1]
    }
    selectedId.value = null
    return null
  }

  function advanceAfterAction() {
    const items = itemsRef.value || []
    const idx = selectedIndex.value
    if (idx >= 0 && idx < items.length - 1) {
      selectedId.value = items[idx + 1].id
    } else if (items.length) {
      selectedId.value = items[0]?.id ?? null
    } else {
      selectedId.value = null
    }
  }

  function moveSelection(delta) {
    const items = itemsRef.value || []
    if (!items.length) return
    let idx = selectedIndex.value
    if (idx < 0) idx = 0
    else idx = Math.max(0, Math.min(items.length - 1, idx + delta))
    selectedId.value = items[idx].id
  }

  watch(
    itemsRef,
    (items) => {
      if (!items?.length) {
        selectedId.value = null
        return
      }
      if (!items.some((i) => i.id === selectedId.value)) {
        selectedId.value = items[0].id
      }
    },
    { deep: true }
  )

  return {
    selectedId,
    selectedIndex,
    selectedItem,
    select,
    selectFirst,
    selectNext,
    advanceAfterAction,
    moveSelection,
  }
}
