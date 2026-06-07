<template>
  <FormModal
    :open="open"
    :title="isEdit ? 'Edit tenant' : 'New tenant'"
    subtitle="Tenant identity and contact information."
    size="xl"
    :save-label="isEdit ? 'Save changes' : 'Create tenant'"
    :saving="saving"
    @close="$emit('close')"
    @save="onSave"
  >
    <TenantFormContent ref="formRef" :entity-id="entityId" @saved="onSaved" />
  </FormModal>
</template>

<script setup>
import { ref, computed } from 'vue'
import { FormModal } from '@/components/erp'
import TenantFormContent from './TenantFormContent.vue'

const props = defineProps({
  open: Boolean,
  entityId: { type: [String, Number], default: null },
})
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
