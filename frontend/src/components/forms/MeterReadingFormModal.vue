<template>
  <FormModal
    :open="open"
    :title="isEdit ? 'Edit reading' : 'Capture reading'"
    subtitle="Record utility consumption for billing."
    size="xl"
    :save-label="isEdit ? 'Save changes' : 'Submit reading'"
    :saving="saving"
    @close="$emit('close')"
    @save="onSave"
  >
    <MeterReadingFormContent
      ref="formRef"
      :entity-id="entityId"
      @saved="onSaved"
      @edit-existing="(id) => emit('edit-existing', id)"
    />
  </FormModal>
</template>

<script setup>
import { ref, computed } from 'vue'
import { FormModal } from '@/components/erp'
import MeterReadingFormContent from './MeterReadingFormContent.vue'

const props = defineProps({ open: Boolean, entityId: { default: null } })
const emit = defineEmits(['close', 'saved', 'edit-existing'])
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
