<template>
  <FormModal
    :open="open"
    :title="isEdit ? 'Edit account' : 'Add account'"
    subtitle="General ledger account for journal posting."
    size="lg"
    :save-label="isEdit ? 'Save changes' : 'Create'"
    :saving="saving"
    @close="$emit('close')"
    @save="onSave"
  >
    <AccountFormContent ref="formRef" :entity-id="entityId" @saved="onSaved" />
  </FormModal>
</template>

<script setup>
import { ref, computed } from 'vue'
import { FormModal } from '@/components/erp'
import AccountFormContent from './AccountFormContent.vue'

const props = defineProps({ open: Boolean, entityId: { default: null } })
const emit = defineEmits(['close', 'saved'])
const formRef = ref(null)
const saving = ref(false)
const isEdit = computed(() => props.entityId != null)

async function onSave() {
  saving.value = true
  try {
    await formRef.value?.submit()
  } finally {
    saving.value = false
  }
}

function onSaved() {
  emit('saved')
  emit('close')
}
</script>
