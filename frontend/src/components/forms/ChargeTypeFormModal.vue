<template>
  <FormModal
    :open="open"
    :title="isEdit ? 'Edit charge type' : 'Add charge type'"
    subtitle="Billing category and calculation rules."
    size="2xl"
    :save-label="isEdit ? 'Save changes' : 'Create'"
    :saving="saving"
    @close="$emit('close')"
    @save="onSave"
  >
    <ChargeTypeFormContent ref="formRef" :entity-id="entityId" @saved="onSaved" />
  </FormModal>
</template>

<script setup>
import { ref, computed } from 'vue'
import { FormModal } from '@/components/erp'
import ChargeTypeFormContent from './ChargeTypeFormContent.vue'

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
