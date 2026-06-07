<template>
  <div v-if="loading" class="py-8 text-center text-sm text-slate-500">Loading…</div>
  <form v-else class="space-y-5" @submit.prevent="submit">
    <AlertBanner v-if="serverError" :message="serverError" variant="error" @dismiss="serverError = ''" />
    <FormSection compact title="Building details">
      <FormGrid>
        <FormField label="Name" required :error="fieldError('name')">
          <input v-model="form.name" type="text" class="erp-input" />
        </FormField>
        <FormField label="Code" :error="fieldError('code')">
          <input v-model="form.code" type="text" class="erp-input font-mono uppercase" />
        </FormField>
        <FormField label="City" :error="fieldError('city')">
          <input v-model="form.city" type="text" class="erp-input" />
        </FormField>
        <FormField label="Country" :error="fieldError('country')">
          <input v-model="form.country" type="text" class="erp-input" />
        </FormField>
        <FormField label="Floors" :error="fieldError('total_floors')">
          <input v-model.number="form.total_floors" type="number" min="0" class="erp-input" />
        </FormField>
        <FormField label="Currency" :error="fieldError('operating_currency')">
          <input v-model="form.operating_currency" type="text" maxlength="3" class="erp-input uppercase" />
        </FormField>
        <FormField label="Timezone" span="2" :error="fieldError('timezone')">
          <input v-model="form.timezone" type="text" class="erp-input" />
        </FormField>
        <FormField label="Address" span="2" :error="fieldError('address')">
          <textarea v-model="form.address" rows="2" class="erp-input" />
        </FormField>
        <FormField label="Status">
          <select v-model="form.is_active" class="erp-select">
            <option :value="true">Active</option>
            <option :value="false">Inactive</option>
          </select>
        </FormField>
      </FormGrid>
    </FormSection>
  </form>
</template>

<script setup>
import { reactive, ref, watch, onMounted } from 'vue'
import api from '@/services/api'
import { FormSection, FormGrid, FormField, AlertBanner } from '@/components/erp'

const props = defineProps({
  entityId: { type: [String, Number], default: null },
})

const emit = defineEmits(['saved', 'cancel'])

const loading = ref(false)
const serverError = ref('')
const fieldErrors = ref({})

const defaults = () => ({
  name: '',
  code: '',
  total_floors: 1,
  address: '',
  city: '',
  country: 'Somalia',
  timezone: 'Africa/Mogadishu',
  operating_currency: 'USD',
  is_active: true,
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
    const { data } = await api.get(`/buildings/${props.entityId}`)
    const b = data.data ?? data
    Object.assign(form, {
      name: b.name ?? '',
      code: b.code ?? '',
      total_floors: b.total_floors ?? 1,
      address: b.address ?? '',
      city: b.city ?? '',
      country: b.country ?? '',
      timezone: b.timezone ?? 'Africa/Mogadishu',
      operating_currency: b.operating_currency ?? 'USD',
      is_active: b.is_active ?? true,
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
      await api.put(`/buildings/${props.entityId}`, { ...form })
    } else {
      await api.post('/buildings', { ...form })
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
