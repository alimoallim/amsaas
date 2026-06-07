<template>
  <div class="space-y-4">
    <div>
      <h1>Create Charge Model</h1>
      <p>Define pricing and billing behavior for generated charges.</p>
    </div>
    <div v-if="serverError" class="error-banner">{{ serverError }}</div>
    <ChargeModelForm
      v-model="form"
      :errors="errors"
      :submitting="submitting"
      submit-label="Create Charge Model"
      @submit="submit"
      @cancel="cancel"
    />
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import ChargeModelForm from './ChargeModelForm.vue'
import { defaultChargeModelForm, buildChargeModelPayload, firstValidationMessage } from '@/utils/chargeModelForm'

const router = useRouter()
const submitting = ref(false)
const errors = ref({})
const serverError = ref('')
const form = reactive(defaultChargeModelForm())

async function submit() {
  submitting.value = true
  errors.value = {}
  serverError.value = ''
  try {
    const payload = buildChargeModelPayload(form)
    await api.post('/charge-models', payload)
    router.push({ name: 'ChargeModels' })
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
      serverError.value = firstValidationMessage(errors.value) || 'Please fix the highlighted fields.'
      return
    }
    serverError.value = error.response?.data?.message || 'Failed to create charge model.'
  } finally {
    submitting.value = false
  }
}

function cancel() {
  router.push({ name: 'ChargeModels' })
}
</script>

<style scoped>
h1 { font-size: 22px; font-weight: 700; }
p { color: #6b7280; font-size: 13px; }
.error-banner { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; border-radius: 8px; padding: 10px; font-size: 13px; }
</style>
