<template>
  <div v-if="loading" class="py-8 text-center text-sm text-slate-500">Loading…</div>
  <form v-else class="space-y-5" @submit.prevent="submit">
    <AlertBanner v-if="serverError" :message="serverError" variant="error" @dismiss="serverError = ''" />
    <FormSection compact title="Account details">
      <FormGrid>
        <FormField label="Name" required :error="fieldError('name')">
          <input v-model="form.name" type="text" class="erp-input" />
        </FormField>
        <FormField label="Code" required :error="fieldError('code')">
          <input
            v-model="form.code"
            type="text"
            class="erp-input font-mono uppercase"
            :readonly="isEdit && form.is_system"
          />
        </FormField>
        <FormField label="Type" required :error="fieldError('type')">
          <select v-model="form.type" class="erp-select" :disabled="isEdit && form.is_system">
            <option value="asset">Asset</option>
            <option value="liability">Liability</option>
            <option value="equity">Equity</option>
            <option value="revenue">Revenue</option>
            <option value="expense">Expense</option>
          </select>
        </FormField>
        <FormField label="Status" required :error="fieldError('status')">
          <select v-model="form.status" class="erp-select">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </FormField>
        <FormField label="Sort order" :error="fieldError('sort_order')">
          <input v-model.number="form.sort_order" type="number" min="0" class="erp-input" />
        </FormField>
        <FormField label="Description" span="2" :error="fieldError('description')">
          <textarea v-model="form.description" rows="2" class="erp-input" />
        </FormField>
      </FormGrid>
      <p v-if="form.is_system" class="mt-2 text-xs text-slate-500 dark:text-slate-400">
        System accounts: code and type are locked. You may update name, status, and description.
      </p>
    </FormSection>
  </form>
</template>

<script setup>
import { reactive, ref, onMounted, watch, computed } from 'vue'
import { FormSection, FormGrid, FormField, AlertBanner } from '@/components/erp'
import { useAccounts } from '@/composables/useAccounts'

const props = defineProps({ entityId: { default: null } })
const emit = defineEmits(['saved'])

const { fetchOne, create, update } = useAccounts()
const loading = ref(false)
const serverError = ref('')
const fieldErrors = ref({})
const isEdit = computed(() => props.entityId != null)

const form = reactive({
  name: '',
  code: '',
  type: 'expense',
  description: '',
  sort_order: 0,
  status: 'active',
  is_system: false,
})

function fieldError(key) {
  const err = fieldErrors.value[key]
  return Array.isArray(err) ? err[0] : err
}

function applyEntity(entity) {
  form.name = entity.name ?? ''
  form.code = entity.code ?? ''
  form.type = entity.type ?? 'expense'
  form.description = entity.description ?? ''
  form.sort_order = entity.sort_order ?? 0
  form.status = entity.status ?? 'active'
  form.is_system = !!entity.is_system
}

async function load() {
  if (!props.entityId) return
  loading.value = true
  try {
    applyEntity(await fetchOne(props.entityId))
  } catch (e) {
    serverError.value = e.response?.data?.message || 'Could not load account.'
  } finally {
    loading.value = false
  }
}

async function submit() {
  serverError.value = ''
  fieldErrors.value = {}
  const payload = {
    name: form.name,
    code: form.code,
    type: form.type,
    description: form.description || null,
    sort_order: form.sort_order || 0,
    status: form.status,
  }
  try {
    if (isEdit.value) {
      await update(props.entityId, payload)
    } else {
      await create(payload)
    }
    emit('saved')
  } catch (e) {
    if (e.response?.status === 422) {
      fieldErrors.value = e.response.data?.errors || {}
      serverError.value = e.response.data?.message || 'Validation failed.'
    } else {
      serverError.value = e.response?.data?.message || 'Save failed.'
    }
    throw e
  }
}

watch(() => props.entityId, load, { immediate: true })

onMounted(load)

defineExpose({ submit })
</script>
