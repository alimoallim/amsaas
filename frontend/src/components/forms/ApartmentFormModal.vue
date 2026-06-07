<template>
  <FormModal
    :open="open"
    :title="isEdit ? 'Edit apartment' : 'New apartment'"
    subtitle="Unit inventory and listing configuration."
    size="xl"
    :save-label="isEdit ? 'Save changes' : 'Create apartment'"
    :saving="saving"
    @close="$emit('close')"
    @save="onSave"
  >
    <ApartmentFormContent ref="formRef" :entity-id="entityId" @saved="onSaved" />
  </FormModal>
</template>

<script setup>
import { ref, computed } from 'vue'
import { FormModal } from '@/components/erp'
import ApartmentFormContent from './ApartmentFormContent.vue'

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
