<template>
  <FormModal
    :open="open"
    title="Reserve unit for sale"
    subtitle="Hold the unit and optionally collect a deposit."
    size="lg"
    save-label="Create reservation"
    :saving="saving"
    @close="$emit('close')"
    @save="onSave"
  >
    <SaleReservationFormContent
      v-if="apartment"
      ref="formRef"
      :apartment="apartment"
      @saved="onSaved"
    />
  </FormModal>
</template>

<script setup>
import { ref } from 'vue'
import { FormModal } from '@/components/erp'
import SaleReservationFormContent from './SaleReservationFormContent.vue'

defineProps({
  open: Boolean,
  apartment: { type: Object, default: null },
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

function onSaved() {
  emit('saved')
  emit('close')
}
</script>
