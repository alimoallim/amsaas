<template>
  <div v-if="loading" class="py-8 text-center text-sm text-slate-500">Loading…</div>
  <form v-else class="space-y-5" @submit.prevent="submit">
    <AlertBanner v-if="serverError" :message="serverError" variant="error" @dismiss="serverError = ''" />
    <FormSection compact title="Identity">
      <FormGrid>
        <FormField label="Type" :error="fieldError('tenant_type')">
          <select v-model="form.tenant_type" class="erp-select">
            <option value="individual">Individual</option>
            <option value="company">Company</option>
            <option value="government">Government</option>
            <option value="ngo">NGO</option>
          </select>
        </FormField>
        <FormField label="Status" :error="fieldError('status')">
          <select v-model="form.status" class="erp-select">
            <option value="active">Active</option>
            <option value="pending">Pending</option>
            <option value="inactive">Inactive</option>
            <option value="blacklisted">Blacklisted</option>
          </select>
        </FormField>
        <FormField label="First name" :error="fieldError('first_name')">
          <input v-model="form.first_name" type="text" class="erp-input" />
        </FormField>
        <FormField label="Last name" :error="fieldError('last_name')">
          <input v-model="form.last_name" type="text" class="erp-input" />
        </FormField>
        <FormField label="Display name" span="2" :error="fieldError('display_name')">
          <input v-model="form.display_name" type="text" class="erp-input" />
        </FormField>
        <FormField label="Company name" span="2" :error="fieldError('company_name')">
          <input v-model="form.company_name" type="text" class="erp-input" />
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
        <FormField label="National ID" :error="fieldError('national_id')">
          <input v-model="form.national_id" type="text" class="erp-input" />
        </FormField>
        <FormField label="Nationality" :error="fieldError('nationality')">
          <input v-model="form.nationality" type="text" class="erp-input" />
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
  tenant_type: 'individual',
  status: 'active',
  first_name: '',
  last_name: '',
  display_name: '',
  company_name: '',
  email: '',
  phone: '',
  national_id: '',
  nationality: '',
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
    const { data } = await api.get(`/tenants/${props.entityId}`)
    const t = data.data ?? data
    Object.assign(form, {
      tenant_type: t.tenant_type?.value ?? t.tenant_type ?? 'individual',
      status: t.status?.value ?? t.status ?? 'active',
      first_name: t.first_name ?? '',
      last_name: t.last_name ?? '',
      display_name: t.display_name ?? '',
      company_name: t.company_name ?? '',
      email: t.email ?? '',
      phone: t.phone ?? '',
      national_id: t.national_id ?? '',
      nationality: t.nationality ?? '',
    })
  } finally {
    loading.value = false
  }
}

async function submit() {
  fieldErrors.value = {}
  serverError.value = ''
  try {
    if (props.entityId) {
      await api.put(`/tenants/${props.entityId}`, { ...form })
    } else {
      await api.post('/tenants', { ...form })
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

watch(() => props.entityId, load, { immediate: true })
onMounted(load)
defineExpose({ submit })
</script>
