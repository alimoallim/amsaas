<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import api from '../../services/api'
import { useConfirm } from '@/composables/useConfirm'


import RentalAgreementForm from './RentalAgreementForm.vue'
import {
  mapBillingFromApi,
  buildRentalAgreementPayload,
  firstFormError,
} from '@/utils/rentalAgreementBilling'

const route = useRoute()

const router = useRouter()

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const agreementId = route.params.id

const loading = ref(false)

const initialLoading = ref(true)

const buildings = ref([])

const tenants = ref([])

const initialBuildingId = ref('')

const errors = ref({})
const pageError = ref('')

const agreement = ref(null)

/*
|--------------------------------------------------------------------------
| Form State
|--------------------------------------------------------------------------
*/

const form = reactive({

  apartment_id: '',

  tenant_id: '',
  status:'',

  start_date: '',

  end_date: '',

  monthly_rent: '',

  security_deposit: '',

  currency: 'USD',

  payment_due_day: 1,

  auto_renew: false,

  renewal_notice_days: 30,

  rent_charge_model_id: '',
  recurring_charges: [],
  notes: '',
  special_terms: '',
})

/*
|--------------------------------------------------------------------------
| Load Agreement
|--------------------------------------------------------------------------
*/

async function loadAgreement() {

  try {

    const response = await api.get(

      `/rental-agreements/${agreementId}`
    )

    agreement.value =

      response?.data?.data

    const data = agreement.value

    /*
    |--------------------------------------------------------------------------
    | Populate Form
    |--------------------------------------------------------------------------
    */

    form.apartment_id =
      data?.apartment?.id ?? ''

    initialBuildingId.value =
      data?.apartment?.building?.id ?? ''

    form.tenant_id =
      data?.tenant?.id ?? ''

    form.start_date =
      data?.dates?.start_date ?? ''

    form.end_date =
      data?.dates?.end_date ?? ''

    form.monthly_rent =
      data?.financials?.monthly_rent ?? ''

    form.security_deposit =
      data?.financials?.security_deposit ?? ''

    form.currency =
      data?.financials?.currency ?? 'USD'
       form.status = data?.status?.value ?? 'draft'

    form.payment_due_day =
      data?.financials?.payment_due_day ?? 1

    form.auto_renew =
      data?.renewal?.auto_renew ?? false

    form.renewal_notice_days =
      data?.renewal?.renewal_notice_days ?? 30

    const billing = mapBillingFromApi(data?.billing)
    form.rent_charge_model_id = billing.rent_charge_model_id
    form.recurring_charges = billing.recurring_charges

    form.notes = data?.notes?.agreement_notes ?? ''
    form.special_terms = data?.notes?.special_terms ?? ''

  } catch (error) {

    console.error(
      'Failed to load rental agreement:',
      error
    )

    router.push({
      name: 'RentalAgreementIndex',
    })
  }
}

/*
|--------------------------------------------------------------------------
| Load Dependencies
|--------------------------------------------------------------------------
*/

async function loadDependencies() {

  try {

    const [buildingsResponse, tenantsResponse] = await Promise.all([
      api.get('/buildings', { params: { per_page: 200 } }),
      api.get('/tenants', { params: { per_page: 100 } }),
    ])

    buildings.value = buildingsResponse?.data?.data ?? []
    tenants.value = tenantsResponse?.data?.data ?? []

  } catch (error) {

    console.error(
      'Failed to load dependencies:',
      error
    )
  }
}

/*
|--------------------------------------------------------------------------
| Load Page Data
|--------------------------------------------------------------------------
*/

async function initializePage() {

  initialLoading.value = true

  try {

    await Promise.all([

      loadAgreement(),

      loadDependencies(),
    ])

  } finally {

    initialLoading.value = false
  }
}

/*
|--------------------------------------------------------------------------
| Submit Update
|--------------------------------------------------------------------------
*/

async function submitForm({ confirmCriticalChanges = false } = {}) {
  loading.value = true
  errors.value = {}
  pageError.value = ''

  try {
    await api.put(
      `/rental-agreements/${agreementId}`,
      buildRentalAgreementPayload(form, { forUpdate: true, confirmCriticalChanges }),
    )

    router.push({ name: 'RentalAgreementShow', params: { id: agreementId } })
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors ?? {}

      if (errors.value.confirm_critical_changes && !confirmCriticalChanges) {
        const { confirm } = useConfirm()
        const ok = await confirm({
          title: 'Issued invoices on this agreement',
          message:
            'This agreement has issued invoices. Unit, tenant, or start date changes will not alter issued invoices but will update future billing. Continue?',
          confirmLabel: 'Save changes',
          variant: 'primary',
        })
        if (ok) {
          loading.value = false
          return submitForm({ confirmCriticalChanges: true })
        }
      }

      pageError.value =
        firstFormError(errors.value)
        || error.response.data.message
        || 'Please fix the highlighted fields.'
      return
    }

    pageError.value = error.response?.data?.message || 'Failed to update rental agreement.'
    console.error('Failed to update rental agreement:', error)
  } finally {
    loading.value = false
  }
}
// Add this new function in your <script setup>
async function activateAgreement() {
  const { confirm } = useConfirm()
  const ok = await confirm({
    title: 'Activate agreement',
    message: 'Activate this agreement? The contract will be finalized for billing.',
    confirmLabel: 'Activate',
    variant: 'primary',
  })
  if (!ok) return

  loading.value = true;
  try {
    // Call a dedicated endpoint for state change
    await api.post(`/rental-agreements/${agreementId}/activate`);
    
    // Refresh the page data to show the new status
    await loadAgreement(); 
  } catch (error) {
    console.error('Failed to activate agreement:', error);
    alert('Failed to change status. Please check your permissions.');
  } finally {
    loading.value = false;
  }
}

/*
|--------------------------------------------------------------------------
| Navigation
|--------------------------------------------------------------------------
*/

function cancelEdit() {

  router.push({

    name: 'RentalAgreementIndex',
  })
}

/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/

onMounted(() => {

  initializePage()
})
</script>

<template>
 
    <div class="space-y-4">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex min-w-0 flex-wrap items-center gap-2">
          <div>
            <p class="text-xs font-medium text-slate-500">Rental Agreements</p>
            <div class="flex flex-wrap items-center gap-2">
              <h1 class="text-xl font-bold tracking-tight text-slate-900">Edit Rental Agreement</h1>
              <span
                v-if="agreement?.agreement_number"
                class="inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-slate-700"
              >
                {{ agreement.agreement_number }}
              </span>
            </div>
          </div>
          <template v-if="agreement?.status">
            <span
              class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide"
              :class="{
                'bg-emerald-100 text-emerald-700': agreement.status.value === 'active',
                'bg-amber-100 text-amber-700': agreement.status.value === 'draft',
                'bg-rose-100 text-rose-700': agreement.status.value === 'terminated',
                'bg-slate-100 text-slate-700':
                  agreement.status.value !== 'active'
                  && agreement.status.value !== 'draft'
                  && agreement.status.value !== 'terminated',
              }"
            >
              {{ agreement.status.label }}
            </span>
            <span v-if="agreement.apartment?.unit_number" class="text-xs text-slate-500">
              Unit {{ agreement.apartment.unit_number }}
            </span>
          </template>
        </div>
        <button
          type="button"
          class="inline-flex shrink-0 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
          @click="cancelEdit"
        >
          Back
        </button>
      </div>


      <div
        v-if="pageError"
        class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
        role="alert"
      >
        {{ pageError }}
      </div>

      <!-- Loading -->
      <div
        v-if="initialLoading"
        class="rounded-2xl border border-slate-200 bg-white p-12 text-center shadow-sm"
      >
        <div
          class="mx-auto mb-4 h-10 w-10 animate-spin rounded-full border-4 border-slate-200 border-t-primary-600"
        />

        <p class="text-sm text-slate-500">
          Loading rental agreement...
        </p>
      </div>

      <!-- Form -->
      <RentalAgreementForm
  v-else
  :form="form"
  :errors="errors"
  :loading="loading"
  :buildings="buildings"
  :tenants="tenants"
  :initial-building-id="initialBuildingId"
  :initial-status="agreement?.status?.value ?? ''"
  :mode="agreement?.status?.value === 'terminated'
    ? 'readonly'
    : 'edit'"
  @submit="submitForm"
  @cancel="cancelEdit"
/>
    </div>
  
</template>