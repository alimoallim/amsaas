<template>
  <FormModal
    :open="open"
    title="Create sale contract"
    subtitle="Convert a confirmed reservation into a binding sale agreement."
    size="lg"
    save-label="Create contract"
    :saving="saving"
    @close="$emit('close')"
    @save="onSave"
  >
    <SaleContractFormContent
      v-if="reservation"
      ref="formRef"
      :reservation="reservation"
      @saved="onSaved"
    />
  </FormModal>
</template>

<script setup>
import { ref } from 'vue'
import { FormModal } from '@/components/erp'
import SaleContractFormContent from './SaleContractFormContent.vue'

defineProps({
  open: Boolean,
  reservation: { type: Object, default: null },
})

const emit = defineEmits(['close', 'saved'])

const formRef = ref(null)
const saving = ref(false)

async function onSave() {
  saving.value = true
  try {
    await formRef.value?.submit()
  } finally {
    saving.value = false
  }
}

function onSaved(contract) {
  emit('saved', contract)
  emit('close')
}
</script>
