<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import api from '../../services/api'
import DashboardLayout from '../../layouts/DashboardLayout.vue'

import RentalAgreementForm from './RentalAgreementForm.vue'

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

const apartments = ref([])

const tenants = ref([])

const errors = ref({})

const agreement = ref(null)

/*
|--------------------------------------------------------------------------
| Form State
|--------------------------------------------------------------------------
*/

const form = reactive({

  apartment_id: '',

  tenant_id: '',

  start_date: '',

  end_date: '',

  monthly_rent: '',

  security_deposit: '',

  currency: 'USD',

  payment_due_day: 1,

  includes_water: false,

  includes_electricity: false,

  includes_internet: false,

  auto_renew: false,

  renewal_notice_days: 30,

  contract_file: null,

  special_terms: '',

  notes: '',
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

    form.payment_due_day =
      data?.financials?.payment_due_day ?? 1

    form.includes_water =
      data?.utilities?.includes_water ?? false

    form.includes_electricity =
      data?.utilities?.includes_electricity ?? false

    form.includes_internet =
      data?.utilities?.includes_internet ?? false

    form.auto_renew =
      data?.renewal?.auto_renew ?? false

    form.renewal_notice_days =
      data?.renewal?.renewal_notice_days ?? 30

    form.special_terms =
      data?.notes?.special_terms ?? ''

    form.notes =
      data?.notes?.agreement_notes ?? ''

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

    const [

      apartmentsResponse,

      tenantsResponse,

    ] = await Promise.all([

      api.get('/apartments', {

        params: {

          per_page: 100,
        },
      }),

      api.get('/tenants', {

        params: {

          per_page: 100,
        },
      }),
    ])

    apartments.value =

      apartmentsResponse?.data?.data
      ?? []

    tenants.value =

      tenantsResponse?.data?.data
      ?? []

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

async function submitForm() {

  loading.value = true

  errors.value = {}

  try {

    const payload = new FormData()

    payload.append(
      '_method',
      'PUT'
    )

    Object.entries(form)

      .forEach(([key, value]) => {

        if (

          value !== null
          &&
          value !== undefined

        ) {

          payload.append(
            key,
            value
          )
        }
      })

    await api.post(

      `/rental-agreements/${agreementId}`,

      payload,

      {

        headers: {

          'Content-Type':
            'multipart/form-data',
        },
      }
    )

    /*
    |--------------------------------------------------------------------------
    | Redirect
    |--------------------------------------------------------------------------
    */

    router.push({

      name: 'RentalAgreementIndex',
    })

  } catch (error) {

    /*
    |--------------------------------------------------------------------------
    | Validation Errors
    |--------------------------------------------------------------------------
    */

    if (

      error.response?.status === 422

    ) {

      errors.value =

        error.response.data.errors
        ?? {}

      return
    }

    console.error(
      'Failed to update rental agreement:',
      error
    )

  } finally {

    loading.value = false
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
 
    <div class="space-y-6">
      <!-- Header -->
      <div
        class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
      >
        <div>
          <p
            class="text-sm font-medium text-slate-500"
          >
            Rental Agreements
          </p>

          <div
            class="mt-1 flex flex-wrap items-center gap-3"
          >
            <h1
              class="text-3xl font-bold tracking-tight text-slate-900"
            >
              Edit Rental Agreement
            </h1>

            <span
              v-if="agreement?.agreement_number"
              class="inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-700"
            >
              {{ agreement.agreement_number }}
            </span>
          </div>

          <p
            class="mt-2 text-sm text-slate-500"
          >
            Manage legal, financial, and operational rental agreement details.
          </p>
        </div>

        <button
          type="button"
          class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
          @click="cancelEdit"
        >
          Back to Agreements
        </button>
      </div>

      <!-- Agreement Status -->
      <div
        v-if="agreement?.status"
        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
      >
        <div
          class="flex flex-wrap items-center justify-between gap-4"
        >
          <div>
            <p
              class="text-xs font-semibold uppercase tracking-wide text-slate-500"
            >
              Agreement Status
            </p>

            <div
              class="mt-2 flex items-center gap-3"
            >
              <span
                class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wide"
                :class="{
                  'bg-emerald-100 text-emerald-700':
                    agreement.status.value === 'active',

                  'bg-amber-100 text-amber-700':
                    agreement.status.value === 'draft',

                  'bg-rose-100 text-rose-700':
                    agreement.status.value === 'terminated',

                  'bg-slate-100 text-slate-700':
                    agreement.status.value !== 'active'
                    && agreement.status.value !== 'draft'
                    && agreement.status.value !== 'terminated',
                }"
              >
                {{ agreement.status.label }}
              </span>

              <span
                class="text-sm text-slate-500"
              >
                {{ agreement.apartment?.unit_number }}
              </span>
            </div>
          </div>

          <div
            v-if="agreement.status.value === 'active'"
            class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700"
          >
            Active agreements restrict modification of protected legal fields.
          </div>
        </div>
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
  :apartments="apartments"
  :tenants="tenants"
  :mode="agreement?.status?.value === 'terminated'
    ? 'readonly'
    : 'edit'"
  @submit="submitForm"
  @cancel="cancelEdit"
/>
    </div>
  
</template>