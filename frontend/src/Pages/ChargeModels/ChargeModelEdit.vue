<template>
  <div class="space-y-4">
    <div>
      <h1>Edit Charge Model</h1>
      <p class="warn">
        Editing active models should create a future-dated version to preserve billing audit integrity.
      </p>
    </div>
    <div v-if="loading" class="card">Loading charge model...</div>
    <template v-else>
      <div v-if="serverError" class="error-banner">{{ serverError }}</div>
      <ChargeModelForm
        v-model="form"
        :errors="errors"
        :submitting="submitting"
        submit-label="Update Charge Model"
        @submit="submit"
        @cancel="cancel"
      />
    </template>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import ChargeModelForm from './ChargeModelForm.vue'
import { buildChargeModelPayload, firstValidationMessage } from '@/utils/chargeModelForm'

const route = useRoute()
const router = useRouter()
const loading = ref(false)
const submitting = ref(false)
const errors = ref({})
const serverError = ref('')
const form = reactive({})

async function load() {
  loading.value = true
  try {
    const response = await api.get(`/charge-models/${route.params.id}`)
    const record = response.data.data || response.data
    Object.assign(form, record)
    form.charge_type_id = record.charge_type_id ?? record.charge_type?.id ?? ''
    if (!Array.isArray(form.tier_configuration)) {
      form.tier_configuration = []
    }
  } finally {
    loading.value = false
  }
}

async function submit() {
  submitting.value = true
  errors.value = {}
  serverError.value = ''
  try {
    const payload = buildChargeModelPayload(form)
    await api.put(`/charge-models/${route.params.id}`, payload)
    router.push({ name: 'ChargeModelShow', params: { id: route.params.id } })
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
      serverError.value = firstValidationMessage(errors.value) || 'Please fix the highlighted fields.'
      return
    }
    serverError.value = error.response?.data?.message || 'Failed to update charge model.'
  } finally {
    submitting.value = false
  }
}

function cancel() {
  router.push({ name: 'ChargeModels' })
}

onMounted(() => load())
</script>

<style scoped>
h1 { font-size: 22px; font-weight: 700; }
.warn { color: #92400e; background: #fef3c7; border: 1px solid #fde68a; border-radius: 8px; padding: 8px 10px; font-size: 12px; }
.card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 14px; }
.error-banner { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; border-radius: 8px; padding: 10px; font-size: 13px; }
</style>
