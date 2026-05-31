<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

import api from '../../services/api'
import DashboardLayout from '../../layouts/DashboardLayout.vue'

import RentalAgreementForm from './RentalAgreementForm.vue'

const router = useRouter()

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const loading = ref(false)

const initialLoading = ref(true)

const apartments = ref([])

const tenants = ref([])

const errors = ref({})

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

  deposit_amount: '',

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
  status:'',
})

/*
|--------------------------------------------------------------------------
| Load Dependencies
|--------------------------------------------------------------------------
*/

async function loadDependencies() {

  initialLoading.value = true

  try {

    const [

      apartmentsResponse,

      tenantsResponse,

    ] = await Promise.all([

      api.get('/apartments', {

        params: {

          listing_type: 'rental',

          inventory_status: 'available',

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
console.log(apartmentsResponse.data)
console.log(tenantsResponse.data)
  } catch (error) {

    console.error(
      'Failed to load dependencies:',
      error
    )

  } finally {

    initialLoading.value = false
  }
}

/*
|--------------------------------------------------------------------------
| Submit Workflow
|--------------------------------------------------------------------------
*/

async function submitForm() {

  loading.value = true

  errors.value = {}

  try {

    await api.post(

  '/rental-agreements',

  {

    apartment_id:
      form.apartment_id,

    tenant_id:
      form.tenant_id,

    start_date:
      form.start_date,

    end_date:
      form.end_date,

    monthly_rent:
      form.monthly_rent,

    security_deposit:
      form.deposit_amount,

    currency:
      form.currency,

    payment_due_day:
      form.payment_due_day,

    notes:
      form.notes,
      status:
      form.status,
  },
  
  
)

    /*
    |--------------------------------------------------------------------------
    | Success Redirect
    |--------------------------------------------------------------------------
    */

    router.push({

      name: 'RentalAgreementIndex',
    })

  } 
 catch (error) {

  console.error('RAW ERROR:', error)

  console.error('MESSAGE:', error.message)

  console.error('RESPONSE:', error.response)

  console.error('REQUEST:', error.request)

  console.error('CONFIG:', error.config)

  if (error.response) {

    alert(

      JSON.stringify(

        error.response.data,

        null,

        2
      )
    )
  }
}
}

/*
|--------------------------------------------------------------------------
| Cancel Workflow
|--------------------------------------------------------------------------
*/

function cancelCreate() {

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

  loadDependencies()
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

          <h1
            class="mt-1 text-3xl font-bold tracking-tight text-slate-900"
          >
            Create Rental Agreement
          </h1>

          <p
            class="mt-2 text-sm text-slate-500"
          >
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

      <!-- Loading -->
      <div
        v-if="initialLoading"
        class="rounded-2xl border border-slate-200 bg-white p-12 text-center shadow-sm"
      >
        <div
          class="mx-auto mb-4 h-10 w-10 animate-spin rounded-full border-4 border-slate-200 border-t-primary-600"
        />

        <p class="text-sm text-slate-500">
          Loading agreement resources...
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
        mode="create"
        @submit="submitForm"
        @cancel="cancelCreate"
      />
    </div>
 
</template>