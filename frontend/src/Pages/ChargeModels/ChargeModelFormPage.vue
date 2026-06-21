<template>
  <TransactionalFormLayout
    eyebrow="Finance"
    :breadcrumbs="breadcrumbs"
    :title="title"
    :description="description"
    :state="state"
    :primary-label="primaryLabel"
    :show-save-draft="false"
    :saving="submitting"
    :error="serverError"
    @primary="submit"
    @cancel="onCancel"
    @dismiss-error="serverError = ''"
  >
    <div v-if="loading" class="py-12 text-center text-sm text-slate-500">Loading charge model…</div>
    <template v-else>
      <ChargeModelForm
        v-model="form"
        :errors="errors"
        :submitting="submitting"
        transactional
        :submit-label="primaryLabel"
        @submit="submit"
      />
    </template>
  </TransactionalFormLayout>
</template>

<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/services/api'
import { TransactionalFormLayout } from '@/components/erp'
import ChargeModelForm from './ChargeModelForm.vue'
import { useTransactionalEntityPage } from '@/composables/useTransactionalEntityPage'
import {
  defaultChargeModelForm,
  buildChargeModelPayload,
  firstValidationMessage,
} from '@/utils/chargeModelForm'

const route = useRoute()

const {
  entityId,
  isEdit,
  breadcrumbs,
  onCancel,
  onSaved,
  state,
} = useTransactionalEntityPage({
  listRouteName: 'ChargeModels',
  moduleLabel: 'Charge models',
  createTitle: 'Add charge model',
  editTitle: 'Edit charge model',
  createDescription: 'Pricing strategy and billing behavior.',
  editDescription: 'Update charge model configuration.',
  primaryLabelCreate: 'Create charge model',
  primaryLabelEdit: 'Save changes',
})

const title = computed(() => (isEdit.value ? 'Edit charge model' : 'Add charge model'))
const description = computed(() =>
  isEdit.value ? 'Update charge model configuration.' : 'Pricing strategy and billing behavior.',
)
const primaryLabel = computed(() => (isEdit.value ? 'Save changes' : 'Create charge model'))

const loading = ref(true)
const submitting = ref(false)
const serverError = ref('')
const errors = ref({})
const form = reactive(defaultChargeModelForm())

async function load() {
  loading.value = true
  try {
    if (entityId.value) {
      const { data } = await api.get(`/charge-models/${entityId.value}`)
      const record = data.data ?? data
      Object.assign(form, record)
      form.charge_type_id = record.charge_type_id ?? record.charge_type?.id ?? ''
      if (!Array.isArray(form.tier_configuration)) form.tier_configuration = []
    } else {
      Object.assign(form, defaultChargeModelForm())
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
    if (entityId.value) {
      const { data } = await api.put(`/charge-models/${entityId.value}`, payload)
      onSaved(data.data ?? data)
    } else {
      const { data } = await api.post('/charge-models', payload)
      onSaved(data.data ?? data)
    }
  } catch (e) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors || {}
      serverError.value =
        firstValidationMessage(errors.value) || 'Please fix the highlighted fields.'
    } else {
      serverError.value = e.response?.data?.message || 'Save failed.'
    }
  } finally {
    submitting.value = false
  }
}

onMounted(load)
</script>
