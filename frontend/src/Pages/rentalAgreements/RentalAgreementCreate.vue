<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

import api from '../../services/api'
import DashboardLayout from '../../layouts/DashboardLayout.vue'

import RentalAgreementForm from './RentalAgreementForm.vue'
import { buildRentalAgreementPayload, firstFormError } from '@/utils/rentalAgreementBilling'

const router = useRouter()

const loading = ref(false)
const initialLoading = ref(true)
const buildings = ref([])
const tenants = ref([])
const errors = ref({})
const serverError = ref('')

const form = reactive({
  apartment_id: '',
  tenant_id: '',
  start_date: '',
  end_date: '',
  monthly_rent: '',
  security_deposit: '',
  currency: 'USD',
  payment_due_day: 1,
  auto_renew: false,
  renewal_notice_days: 30,
  status: 'draft',
  rent_charge_model_id: '',
  recurring_charges: [],
})

async function loadDependencies() {
  initialLoading.value = true
  try {
    const [buildingsResponse, tenantsResponse] = await Promise.all([
      api.get('/buildings', { params: { per_page: 200 } }),
      api.get('/tenants', { params: { per_page: 100 } }),
    ])
    buildings.value = buildingsResponse?.data?.data ?? []
    tenants.value = tenantsResponse?.data?.data ?? []
  } catch (error) {
    console.error('Failed to load dependencies:', error)
    serverError.value = 'Failed to load buildings or tenants.'
  } finally {
    initialLoading.value = false
  }
}

async function submitForm() {
  loading.value = true
  errors.value = {}
  serverError.value = ''

  try {
    await api.post('/rental-agreements', buildRentalAgreementPayload(form))

    router.push({ name: 'RentalAgreementIndex' })
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors ?? {}
      serverError.value =
        firstFormError(errors.value)
        || error.response.data.message
        || 'Please fix the highlighted fields.'
      return
    }
    serverError.value = error.response?.data?.message || 'Failed to create rental agreement.'
    console.error('Failed to create rental agreement:', error)
  } finally {
    loading.value = false
  }
}

function cancelCreate() {
  router.push({ name: 'RentalAgreementIndex' })
}

onMounted(() => {
  loadDependencies()
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
      <div>
        <p class="text-sm font-medium text-slate-500">Rental Agreements</p>
        <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900">Create Rental Agreement</h1>
        <p class="mt-2 text-sm text-slate-500">
          Configure legal, financial, and operational rental contract details.
        </p>
      </div>
      <button
        type="button"
        class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
        @click="cancelCreate"
      >
        Back to Agreements
      </button>
    </div>

    <div
      v-if="serverError"
      class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
      role="alert"
    >
      {{ serverError }}
    </div>

    <div
      v-if="initialLoading"
      class="rounded-2xl border border-slate-200 bg-white p-12 text-center shadow-sm"
    >
      <div class="mx-auto mb-4 h-10 w-10 animate-spin rounded-full border-4 border-slate-200 border-t-primary-600" />
      <p class="text-sm text-slate-500">Loading agreement resources...</p>
    </div>

    <RentalAgreementForm
      v-else
      :form="form"
      :errors="errors"
      :loading="loading"
      :buildings="buildings"
      :tenants="tenants"
      mode="create"
      @submit="submitForm"
      @cancel="cancelCreate"
    />
  </div>
</template>
