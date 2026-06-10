<template>
  <div v-if="loading" class="py-8 text-center text-sm text-slate-500">Loading…</div>
  <form v-else class="space-y-5" @submit.prevent="submit">
    <AlertBanner v-if="serverError" :message="serverError" variant="error" @dismiss="serverError = ''" />
    <FormSection compact title="Identity">
      <FormGrid>
        <FormField label="Full name" span="2" :error="fieldError('full_name')">
          <input v-model="form.full_name" type="text" class="erp-input" required />
        </FormField>
        <FormField label="National ID" :error="fieldError('national_id')">
          <input v-model="form.national_id" type="text" class="erp-input" />
        </FormField>
        <FormField label="Nationality" :error="fieldError('nationality')">
          <input v-model="form.nationality" type="text" class="erp-input" />
        </FormField>
        <FormField label="Date of birth" :error="fieldError('date_of_birth')">
          <input v-model="form.date_of_birth" type="date" class="erp-input" />
        </FormField>
        <FormField label="Status" :error="fieldError('is_active')">
          <select v-model="form.is_active" class="erp-select">
            <option :value="true">Active</option>
            <option :value="false">Inactive</option>
          </select>
        </FormField>
      </FormGrid>
    </FormSection>
    <FormSection compact title="Contact">
      <FormGrid>
        <FormField label="Email" :error="fieldError('email')">
          <input v-model="form.email" type="email" class="erp-input" />
        </FormField>
        <FormField label="Phone" :error="fieldError('phone')">
          <input v-model="form.phone" type="tel" class="erp-input" />
        </FormField>
        <FormField label="Country" :error="fieldError('country')">
          <input v-model="form.country" type="text" class="erp-input" />
        </FormField>
        <FormField label="City" :error="fieldError('city')">
          <input v-model="form.city" type="text" class="erp-input" />
        </FormField>
        <FormField label="Address" span="2" :error="fieldError('address')">
          <input v-model="form.address" type="text" class="erp-input" />
        </FormField>
        <FormField label="Postal code" :error="fieldError('postal_code')">
          <input v-model="form.postal_code" type="text" class="erp-input" />
        </FormField>
      </FormGrid>
    </FormSection>
    <FormSection compact title="Tenant link" subtitle="Optional — link if this buyer later becomes a tenant.">
      <FormGrid>
        <FormField label="Linked tenant ID" span="2" :error="fieldError('tenant_id')">
          <input v-model="form.tenant_id" type="text" class="erp-input" placeholder="Paste tenant UUID (optional)" />
        </FormField>
        <FormField label="Notes" span="2" :error="fieldError('notes')">
          <textarea v-model="form.notes" rows="3" class="erp-input" />
        </FormField>
      </FormGrid>
    </FormSection>
  </form>
</template>

<script setup>
import { reactive, ref, watch, onMounted } from 'vue'
import api from '@/services/api'
import { FormSection, FormGrid, FormField, AlertBanner } from '@/components/erp'

const props = defineProps({ entityId: { type: [String, Number], default: null } })
const emit = defineEmits(['saved'])

const loading = ref(false)
const serverError = ref('')
const fieldErrors = ref({})

const defaults = () => ({
  full_name: '',
  email: '',
  phone: '',
  national_id: '',
  nationality: '',
  date_of_birth: '',
  country: '',
  city: '',
  address: '',
  postal_code: '',
  notes: '',
  is_active: true,
  tenant_id: '',
})

const form = reactive(defaults())

function fieldError(key) {
  const e = fieldErrors.value[key]
  return Array.isArray(e) ? e[0] : e || ''
}

async function load() {
  if (!props.entityId) {
    Object.assign(form, defaults())
    return
  }
  loading.value = true
  try {
    const { data } = await api.get(`/buyers/${props.entityId}`)
    const b = data.data ?? data
    Object.assign(form, {
      full_name: b.full_name ?? '',
      email: b.email ?? '',
      phone: b.phone ?? '',
      national_id: b.national_id ?? '',
      nationality: b.nationality ?? '',
      date_of_birth: b.date_of_birth ?? '',
      country: b.address?.country ?? b.country ?? '',
      city: b.address?.city ?? b.city ?? '',
      address: b.address?.line ?? b.address ?? '',
      postal_code: b.address?.postal_code ?? b.postal_code ?? '',
      notes: b.notes ?? '',
      is_active: b.is_active ?? true,
      tenant_id: b.tenant_id ?? '',
    })
  } finally {
    loading.value = false
  }
}

async function submit() {
  serverError.value = ''
  fieldErrors.value = {}
  const payload = {
    ...form,
    tenant_id: form.tenant_id || null,
    date_of_birth: form.date_of_birth || null,
  }
  try {
    if (props.entityId) {
      await api.put(`/buyers/${props.entityId}`, payload)
    } else {
      await api.post('/buyers', payload)
    }
    emit('saved')
  } catch (err) {
    if (err.response?.status === 422) {
      fieldErrors.value = err.response.data.errors || {}
      serverError.value = err.response.data.message || 'Validation failed.'
    } else {
      serverError.value = err.response?.data?.message || 'Could not save buyer.'
    }
    throw err
  }
}

defineExpose({ submit })

watch(() => props.entityId, load)
onMounted(load)
</script>
