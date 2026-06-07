<template>
  <FormModal
    :open="open"
    :title="isEdit ? 'Edit charge model' : 'Add charge model'"
    subtitle="Pricing strategy and billing behavior."
    size="2xl"
    :show-footer="false"
    @close="$emit('close')"
  >
    <div v-if="loading" class="py-8 text-center text-sm text-slate-500">Loading…</div>
    <template v-else>
      <AlertBanner v-if="serverError" class="mb-4" :message="serverError" variant="error" @dismiss="serverError = ''" />
      <ChargeModelForm
        v-model="form"
        :errors="errors"
        :submitting="submitting"
        :submit-label="isEdit ? 'Save changes' : 'Create charge model'"
        @submit="submit"
        @cancel="$emit('close')"
      />
    </template>
  </FormModal>
</template>

<script setup>
import { reactive, ref, watch, computed } from 'vue'
import api from '@/services/api'
import { FormModal, AlertBanner } from '@/components/erp'
import ChargeModelForm from '@/Pages/ChargeModels/ChargeModelForm.vue'
import {
  defaultChargeModelForm,
  buildChargeModelPayload,
  firstValidationMessage,
} from '@/utils/chargeModelForm'

const props = defineProps({ open: Boolean, entityId: { default: null } })
const emit = defineEmits(['close', 'saved'])

const loading = ref(false)
const submitting = ref(false)
const serverError = ref('')
const errors = ref({})
const isEdit = computed(() => !!props.entityId)

const form = reactive(defaultChargeModelForm())

async function load() {
  if (!props.entityId) {
    Object.assign(form, defaultChargeModelForm())
    return
  }
  loading.value = true
  try {
    const { data } = await api.get(`/charge-models/${props.entityId}`)
    const record = data.data ?? data
    Object.assign(form, record)
    form.charge_type_id = record.charge_type_id ?? record.charge_type?.id ?? ''
    if (!Array.isArray(form.tier_configuration)) form.tier_configuration = []
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
    let saved = null
    if (props.entityId) {
      const { data } = await api.put(`/charge-models/${props.entityId}`, payload)
      saved = data.data ?? data
      if (data.versioned && saved?.id && saved.id !== props.entityId) {
        serverError.value = data.message || 'New version created.'
      }
    } else {
      const { data } = await api.post('/charge-models', payload)
      saved = data.data ?? data
    }
    emit('saved', saved)
    emit('close')
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

watch(
  () => [props.open, props.entityId],
  ([open]) => {
    if (open) load()
  },
  { immediate: true }
)
</script>
