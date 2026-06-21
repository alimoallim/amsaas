import { computed, ref, unref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useConfirm } from '@/composables/useConfirm'
import { TRANSACTIONAL_FORM_META } from '@/config/transactionalFormRegistry'

/**
 * Shared state for TransactionalFormLayout create/edit pages.
 */
export function useTransactionalEntityPage({
  registryKey = null,
  listRouteName = null,
  moduleLabel = null,
  createTitle = null,
  editTitle = null,
  createDescription = '',
  editDescription = '',
  primaryLabelCreate = 'Save',
  primaryLabelEdit = 'Save changes',
  showRouteName = null,
  idParam = 'id',
} = {}) {
  const route = useRoute()
  const router = useRouter()
  const { confirm } = useConfirm()

  const formRef = ref(null)
  const saving = ref(false)
  const dirty = ref(false)
  const serverError = ref('')

  const config = computed(() => {
    const key = unref(registryKey)
    if (key && TRANSACTIONAL_FORM_META[key]) {
      return TRANSACTIONAL_FORM_META[key]
    }
    return {
      listRouteName,
      moduleLabel,
      createTitle,
      editTitle,
      createDescription,
      editDescription,
      primaryLabelCreate,
      primaryLabelEdit,
      showRouteName: showRouteName || route.meta?.showRouteName,
    }
  })

  const entityId = computed(() => {
    const raw = route.params[idParam]
    return raw != null && raw !== '' ? String(raw) : null
  })

  const isEdit = computed(() => entityId.value != null)

  const breadcrumbs = computed(() => [
    { label: config.value.moduleLabel, to: { name: config.value.listRouteName } },
    { label: isEdit.value ? 'Edit' : 'New' },
  ])

  const title = computed(() => (isEdit.value ? config.value.editTitle : config.value.createTitle))
  const description = computed(() =>
    isEdit.value ? config.value.editDescription : config.value.createDescription,
  )
  const primaryLabel = computed(() =>
    isEdit.value ? config.value.primaryLabelEdit : config.value.primaryLabelCreate,
  )
  const state = computed(() => (isEdit.value ? undefined : 'draft'))

  async function onSubmit() {
    saving.value = true
    serverError.value = ''
    try {
      await formRef.value?.submit?.()
    } catch (e) {
      serverError.value = e?.message || 'Save failed.'
    } finally {
      saving.value = false
    }
  }

  async function onCancel() {
    if (dirty.value) {
      const ok = await confirm({
        title: 'Discard changes?',
        message: 'Unsaved changes will be lost.',
        confirmLabel: 'Leave',
        variant: 'danger',
      })
      if (!ok) return
    }
    router.push({ name: config.value.listRouteName })
  }

  function onSaved(payload) {
    dirty.value = false
    const record = payload?.id ? payload : null
    const showRoute = config.value.showRouteName || route.meta?.showRouteName
    if (record?.id && showRoute) {
      router.push({ name: showRoute, params: { id: record.id } })
      return
    }
    router.push({ name: config.value.listRouteName })
  }

  function markDirty() {
    dirty.value = true
  }

  return {
    formRef,
    saving,
    dirty,
    serverError,
    entityId,
    isEdit,
    breadcrumbs,
    title,
    description,
    primaryLabel,
    state,
    onSubmit,
    onCancel,
    onSaved,
    markDirty,
  }
}
