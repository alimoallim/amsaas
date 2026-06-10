<template>
  <form class="space-y-5" @submit.prevent="submit">
    <AlertBanner v-if="serverError" :message="serverError" variant="error" @dismiss="serverError = ''" />

    <FormSection compact title="Unit">
      <p class="text-sm text-slate-700 dark:text-slate-300">
        <span class="font-medium">{{ apartmentLabel }}</span>
        <span v-if="reservedPrice" class="text-slate-500 dark:text-slate-400"> · {{ reservedPrice }}</span>
      </p>
    </FormSection>

    <FormSection compact title="Buyer & terms">
      <FormGrid>
        <FormField label="Buyer" span="2" :error="fieldError('buyer_id')">
          <select v-model="form.buyer_id" class="erp-select" required>
            <option value="">Select buyer…</option>
            <option v-for="b in buyers" :key="b.id" :value="b.id">
              {{ b.full_name }} ({{ b.buyer_code || b.id.slice(0, 8) }})
            </option>
          </select>
        </FormField>
        <FormField label="Deposit amount" :error="fieldError('deposit_amount')">
          <input v-model.number="form.deposit_amount" type="number" min="0" step="0.01" class="erp-input" required />
        </FormField>
        <FormField label="Expiry date" :error="fieldError('expiry_date')">
          <input v-model="form.expiry_date" type="date" class="erp-input" required />
        </FormField>
        <FormField label="Notes" span="2" :error="fieldError('notes')">
          <textarea v-model="form.notes" rows="2" class="erp-input" />
        </FormField>
      </FormGrid>
    </FormSection>

    <FormSection compact title="Collect deposit now">
      <label class="mb-3 flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
        <input v-model="form.record_deposit" type="checkbox" class="rounded border-slate-300" />
        Record deposit payment on create
      </label>
      <FormGrid v-if="form.record_deposit && form.deposit_amount > 0">
        <FormField label="Payment date" :error="fieldError('payment_date')">
          <input v-model="form.payment_date" type="date" class="erp-input" />
        </FormField>
        <FormField label="Payment method" :error="fieldError('payment_method')">
          <select v-model="form.payment_method" class="erp-select">
            <option value="cash">Cash</option>
            <option value="bank_transfer">Bank transfer</option>
            <option value="mobile_money">Mobile money</option>
            <option value="cheque">Cheque</option>
          </select>
        </FormField>
        <FormField label="Reference" span="2" :error="fieldError('reference_number')">
          <input v-model="form.reference_number" type="text" class="erp-input" />
        </FormField>
      </FormGrid>
    </FormSection>
  </form>
</template>

<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import api from '@/services/api'
import { FormSection, FormGrid, FormField, AlertBanner } from '@/components/erp'

const props = defineProps({
  apartment: { type: Object, required: true },
})

const emit = defineEmits(['saved'])

const buyers = ref([])
const serverError = ref('')
const fieldErrors = ref({})

const apartmentLabel = computed(() => {
  const a = props.apartment
  const building = a.building?.name || a.building_name || ''
  return [building, a.unit_number].filter(Boolean).join(' · ') || 'Unit'
})

const reservedPrice = computed(() => {
  const price = props.apartment.market_sale_price ?? props.apartment.listing?.market_sale_price
  if (price == null) return ''
  const currency = props.apartment.currency || 'USD'
  return new Intl.NumberFormat(undefined, { style: 'currency', currency }).format(Number(price))
})

const defaultExpiry = () => {
  const d = new Date()
  d.setDate(d.getDate() + 7)
  return d.toISOString().slice(0, 10)
}

const form = reactive({
  apartment_id: props.apartment.id,
  buyer_id: '',
  deposit_amount: 5000,
  expiry_date: defaultExpiry(),
  notes: '',
  record_deposit: true,
  payment_date: new Date().toISOString().slice(0, 10),
  payment_method: 'cash',
  reference_number: '',
})

function fieldError(key) {
  const e = fieldErrors.value[key]
  return Array.isArray(e) ? e[0] : e || ''
}

async function loadBuyers() {
  const { data } = await api.get('/buyers', { params: { per_page: 100, is_active: true } })
  buyers.value = data.data || []
}

async function submit() {
  serverError.value = ''
  fieldErrors.value = {}
  const payload = {
    apartment_id: form.apartment_id,
    buyer_id: form.buyer_id,
    deposit_amount: form.deposit_amount,
    expiry_date: form.expiry_date,
    notes: form.notes || undefined,
    record_deposit: form.record_deposit && form.deposit_amount > 0,
    payment_date: form.record_deposit ? form.payment_date : undefined,
    payment_method: form.record_deposit ? form.payment_method : undefined,
    reference_number: form.reference_number || undefined,
  }
  try {
    await api.post('/sale-reservations', payload)
    emit('saved')
  } catch (err) {
    if (err.response?.status === 422) {
      fieldErrors.value = err.response.data.errors || {}
      serverError.value = err.response.data.message || 'Validation failed.'
    } else {
      serverError.value = err.response?.data?.message || 'Could not create reservation.'
    }
    throw err
  }
}

defineExpose({ submit })

onMounted(loadBuyers)
</script>
