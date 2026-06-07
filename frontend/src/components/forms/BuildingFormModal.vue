<template>
  <FormModal
    :open="open"
    :title="isEdit ? 'Edit building' : 'Add building'"
    :subtitle="isEdit ? 'Update property details and operating settings.' : 'Register a new building in your portfolio.'"
    size="xl"
    :save-label="isEdit ? 'Save changes' : 'Create building'"
    :saving="saving"
    @close="$emit('close')"
    @save="onSave"
  >
    <BuildingFormContent
      ref="formRef"
      :entity-id="entityId"
      @saved="onSaved"
    />
  </FormModal>
</template>

<script setup>
import { ref, computed } from 'vue'
import { FormModal } from '@/components/erp'
import BuildingFormContent from './BuildingFormContent.vue'

const props = defineProps({
  open: { type: Boolean, default: false },
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
