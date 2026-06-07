import { reactive, computed } from 'vue'

/**
 * Fiori-style popup forms — open create/edit without leaving the worklist.
 */
export function useFormModal() {
  const state = reactive({
    open: false,
    mode: 'create',
    id: null,
  })

  const isEdit = computed(() => state.mode === 'edit' && state.id != null)

  function openCreate() {
    state.mode = 'create'
    state.id = null
    state.open = true
  }

  function openEdit(id) {
    state.mode = 'edit'
    state.id = id
    state.open = true
  }

  function close() {
    state.open = false
  }

  function syncFromRoute(route, router) {
    const form = route.query.form
    if (form === 'create') {
      openCreate()
      router.replace({ query: { ...route.query, form: undefined, id: undefined } })
    } else if (form === 'edit' && route.query.id) {
      openEdit(route.query.id)
      router.replace({ query: { ...route.query, form: undefined, id: undefined } })
    }
  }

  return {
    state,
    isEdit,
    openCreate,
    openEdit,
    close,
    syncFromRoute,
  }
}
