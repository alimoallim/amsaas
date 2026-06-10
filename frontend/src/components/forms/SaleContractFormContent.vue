<template>
  <form class="space-y-5" @submit.prevent="submit">
    <AlertBanner v-if="serverError" :message="serverError" variant="error" @dismiss="serverError = ''" />

    <FormSection compact title="Reservation">
      <p class="text-sm text-slate-700 dark:text-slate-300">
        <span class="font-medium">{{ reservationLabel }}</span>
        <span v-if="buyerLabel" class="text-slate-500 dark:text-slate-400"> · {{ buyerLabel }}</span>
      </p>
    </FormSection>

    <FormSection compact title="Contract terms">
      <FormGrid>
        <FormField label="Sale price" :error="fieldError('sale_price')">
          <input v-model.number="form.sale_price" type="number" min="0.01" step="0.01" class="erp-input" required />
        </FormField>
        <FormField label="Down payment" :error="fieldError('down_payment')">
          <input v-model.number="form.down_payment" type="number" min="0" step="0.01" class="erp-input" />
        </FormField>
        <FormField label="Agreement start date" :error="fieldError('contract_date')">
          <input v-model="form.contract_date" type="date" class="erp-input" />
        </FormField>
        <FormField label="Payment type" span="2">
          <div class="flex flex-wrap gap-4 text-sm">
            <label class="flex items-center gap-2">
              <input v-model="form.is_payment_plan" type="radio" :value="false" />
              Cash sale
            </label>
            <label class="flex items-center gap-2">
              <input v-model="form.is_payment_plan" type="radio" :value="true" />
              Payment plan
            </label>
          </div>
        </FormField>
        <template v-if="form.is_payment_plan">
          <FormField label="Duration (years)" :error="fieldError('plan_duration_years')">
            <input v-model.number="form.plan_duration_years" type="number" min="0" max="50" class="erp-input" />
          </FormField>
          <FormField label="Extra months" :error="fieldError('plan_duration_months')">
            <input v-model.number="form.plan_duration_months" type="number" min="0" max="11" class="erp-input" />
          </FormField>
          <FormField label="Or fixed end date" :error="fieldError('agreement_end_date')" span="2">
            <input v-model="form.agreement_end_date" type="date" class="erp-input" />
          </FormField>
          <div class="sm:col-span-2 text-sm text-slate-600 dark:text-slate-400">
            Financed amount:
            <span class="font-medium tabular-nums text-slate-900 dark:text-slate-100">
              {{ formatMoney(financedPreview) }}
            </span>
          </div>
        </template>
        <FormField label="Notes" span="2" :error="fieldError('notes')">
          <textarea v-model="form.notes" rows="2" class="erp-input" />
        </FormField>
      </FormGrid>
    </FormSection>

    <FormSection compact title="Execution">
      <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
        <input v-model="form.execute" type="checkbox" class="rounded border-slate-300" />
        Execute contract immediately (locks price and marks unit under contract)
      </label>
    </FormSection>
  </form>
</template>

<script setup>
import { reactive, ref, computed } from 'vue'
import api from '@/services/api'
import { FormSection, FormGrid, FormField, AlertBanner } from '@/components/erp'

const props = defineProps({
  reservation: { type: Object, required: true },
})

const emit = defineEmits(['saved'])

const serverError = ref('')
const fieldErrors = ref({})

const reservationLabel = computed(() => {
  const r = props.reservation
  const unit = r.apartment?.unit_number || 'Unit'
  const building = r.apartment?.building?.name || ''
  return [building, unit, r.reservation_number].filter(Boolean).join(' · ')
})

const buyerLabel = computed(() => props.reservation.buyer?.full_name || '')

const form = reactive({
  sale_reservation_id: props.reservation.id,
  sale_price: Number(props.reservation.reserved_price || 0) || 0,
  down_payment: Number(props.reservation.deposit_amount || 0) || 0,
  is_payment_plan: false,
  plan_duration_years: 5,
  plan_duration_months: 0,
  agreement_end_date: '',
  contract_date: new Date().toISOString().slice(0, 10),
  notes: '',
  execute: true,
})

const financedPreview = computed(() =>
  Math.max(0, Number(form.sale_price || 0) - Number(form.down_payment || 0)),
)

function formatMoney(value) {
  return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(Number(value || 0))
}

function fieldError(key) {
  const e = fieldErrors.value[key]
  return Array.isArray(e) ? e[0] : e || ''
}

async function submit() {
  serverError.value = ''
  fieldErrors.value = {}

  const payload = {
    sale_reservation_id: form.sale_reservation_id,
    sale_price: form.sale_price,
    down_payment: form.down_payment,
    is_payment_plan: form.is_payment_plan,
    contract_date: form.contract_date,
    notes: form.notes || undefined,
    execute: form.execute,
  }

  if (form.is_payment_plan) {
    payload.plan_duration_years = form.plan_duration_years
    payload.plan_duration_months = form.plan_duration_months
    if (form.agreement_end_date) {
      payload.agreement_end_date = form.agreement_end_date
    }
  }

  try {
    const { data } = await api.post('/sale-agreements', payload)
    emit('saved', data.data)
  } catch (err) {
    if (err.response?.status === 422) {
      fieldErrors.value = err.response.data.errors || {}
      serverError.value = err.response.data.message || 'Validation failed.'
    } else {
      serverError.value = err.response?.data?.message || 'Could not create sale contract.'
    }
  }
}

defineExpose({ submit })
</script>
