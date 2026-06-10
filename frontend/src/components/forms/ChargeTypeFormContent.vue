<template>
  <div v-if="loading" class="py-8 text-center text-sm text-slate-500">Loading…</div>
  <form v-else class="space-y-5" @submit.prevent="submit">
    <AlertBanner v-if="serverError" :message="serverError" variant="error" @dismiss="serverError = ''" />
    <FormSection compact title="Identification">
      <FormGrid>
        <FormField label="Name" required :error="fieldError('name')">
          <input v-model="form.name" type="text" class="erp-input" @input="autoCode" />
        </FormField>
        <FormField label="Code" required :error="fieldError('code')">
          <input v-model="form.code" type="text" class="erp-input font-mono uppercase" :readonly="!!entityId" />
        </FormField>
        <FormField label="Category" required :error="fieldError('category')">
          <select v-model="form.category" class="erp-select">
            <option value="rent">Rent</option>
            <option value="utility">Utility</option>
            <option value="fee">Fee</option>
            <option value="deposit">Deposit</option>
            <option value="service">Service</option>
            <option value="miscellaneous">Other</option>
          </select>
        </FormField>
        <FormField label="Status" required :error="fieldError('status')">
          <select v-model="form.status" class="erp-select">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="archived">Archived</option>
          </select>
        </FormField>
        <FormField label="Description" span="2">
          <textarea v-model="form.description" rows="2" class="erp-input" />
        </FormField>
      </FormGrid>
    </FormSection>
    <FormSection compact title="Billing rules">
      <FormGrid>
        <FormField label="Billing behavior" :error="fieldError('billing_behavior')">
          <select v-model="form.billing_behavior" class="erp-select">
            <option value="fixed">Fixed</option>
            <option value="variable">Variable</option>
            <option value="metered">Metered</option>
          </select>
        </FormField>
        <FormField label="Calculation" :error="fieldError('calculation_method')">
          <select v-model="form.calculation_method" class="erp-select">
            <option value="fixed_amount">Fixed amount</option>
            <option value="per_unit">Per unit</option>
            <option value="percentage">Percentage</option>
          </select>
        </FormField>
        <FormField label="Frequency" :error="fieldError('billing_frequency')">
          <select v-model="form.billing_frequency" class="erp-select">
            <option value="one_time">One-time payment</option>
            <option value="daily">Daily</option>
            <option value="weekly">Weekly</option>
            <option value="monthly">Monthly</option>
            <option value="quarterly">Quarterly</option>
            <option value="yearly">Yearly</option>
          </select>
        </FormField>
        <FormField label="Currency" :error="fieldError('default_currency')">
          <input v-model="form.default_currency" maxlength="3" class="erp-input uppercase" />
        </FormField>
      </FormGrid>
    </FormSection>
  </form>
</template>

<script setup>
import { reactive, ref, onMounted, watch } from 'vue'
import api from '@/services/api'
import { FormSection, FormGrid, FormField, AlertBanner } from '@/components/erp'

const props = defineProps({ entityId: { default: null } })
const emit = defineEmits(['saved'])

const loading = ref(false)
const serverError = ref('')
const fieldErrors = ref({})

const defaults = () => ({
  name: '',
  code: '',
  description: '',
  category: 'utility',
  billing_behavior: 'fixed',
  calculation_method: 'fixed_amount',
  billing_frequency: 'monthly',
  financial_classification: 'income',
  default_currency: 'USD',
  default_amount: null,
  is_recurring: true,
  is_metered: false,
  requires_meter_reading: false,
  is_taxable: false,
  is_refundable: false,
  allow_manual_override: true,
  allow_proration: false,
  allow_discount: false,
  allow_penalty: false,
  allow_adjustment: true,
  auto_generate: false,
  affects_occupancy: false,
  status: 'active',
})

const form = reactive(defaults())

watch(
  () => form.billing_frequency,
  (frequency) => {
    form.is_recurring = frequency !== 'one_time'
  },
)

function fieldError(key) {
  const e = fieldErrors.value[key]
  return Array.isArray(e) ? e[0] : e || ''
}

function autoCode() {
  if (!props.entityId && form.name) {
    form.code = form.name.trim().toUpperCase().replace(/[^A-Z0-9\s_]/g, '').replace(/\s+/g, '_')
  }
}

async function load() {
  if (!props.entityId) {
    Object.assign(form, defaults())
    return
  }
  loading.value = true
  try {
    const { data } = await api.get(`/charge-types/${props.entityId}`)
    Object.assign(form, { ...defaults(), ...(data.data ?? data) })
  } finally {
    loading.value = false
  }
}

async function submit() {
  fieldErrors.value = {}
  serverError.value = ''
  const payload = { ...form, code: String(form.code).toUpperCase().trim() }
  try {
    if (props.entityId) {
      await api.put(`/charge-types/${props.entityId}`, payload)
    } else {
      await api.post('/charge-types', payload)
    }
    emit('saved')
  } catch (e) {
    if (e.response?.status === 422) {
      fieldErrors.value = e.response.data.errors || {}
      serverError.value = 'Please fix the highlighted fields.'
    } else {
      serverError.value = e.response?.data?.message || 'Save failed.'
    }
  }
}

onMounted(load)
defineExpose({ submit })
</script>
