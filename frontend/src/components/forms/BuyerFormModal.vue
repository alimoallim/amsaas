<template>
  <FormModal
    :open="open"
    :title="isEdit ? 'Edit buyer' : 'New buyer'"
    subtitle="Buyer identity and contact — distinct from tenant records."
    size="xl"
    :save-label="isEdit ? 'Save changes' : 'Create buyer'"
    :saving="saving"
    @close="$emit('close')"
    @save="onSave"
  >
    <BuyerFormContent ref="formRef" :entity-id="entityId" @saved="onSaved" />
  </FormModal>
</template>

<script setup>
import { ref, computed } from 'vue'
import { FormModal } from '@/components/erp'
import BuyerFormContent from './BuyerFormContent.vue'

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
