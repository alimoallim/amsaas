<template>
  <FormModal
    :open="open"
    :title="isEdit ? 'Edit meter' : 'Register meter'"
    subtitle="Utility type, measurement unit, property assignment, and readings."
    size="full"
    :save-label="isEdit ? 'Save changes' : 'Register'"
    :saving="saving"
    @close="$emit('close')"
    @save="onSave"
  >
    <MeterFormContent ref="formRef" :entity-id="entityId" @saved="onSaved" />
  </FormModal>
</template>

<script setup>
import { ref, computed } from 'vue'
import { FormModal } from '@/components/erp'
import MeterFormContent from './MeterFormContent.vue'

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
