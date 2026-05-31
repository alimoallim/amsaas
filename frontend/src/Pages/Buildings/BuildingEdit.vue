<template>



    <div class="max-w-5xl mx-auto">

      <!-- ====================================================== -->
      <!-- Header -->
      <!-- ====================================================== -->

      <div
        class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5 mb-7"
      >

        <div>

          <div
            class="flex items-center gap-2 text-sm text-slate-500 mb-2"
          >

            <router-link
              to="/buildings"
              class="hover:text-indigo-600 transition"
            >
              Buildings
            </router-link>

            <span>
              /
            </span>

            <span>
              Edit Building
            </span>

          </div>

          <h1
            class="text-3xl font-bold tracking-tight text-slate-900"
          >
            Edit Building
          </h1>

          <p
            class="text-slate-500 mt-2"
          >
            Update operational building information and ERP configuration
          </p>

        </div>

        <router-link
          to="/buildings"
          class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl border border-slate-200 hover:bg-slate-50 transition text-sm font-medium"
        >

          ← Back

        </router-link>

      </div>

      <!-- ====================================================== -->
      <!-- Loading -->
      <!-- ====================================================== -->

      <div
        v-if="loading"
        class="bg-white rounded-3xl border border-slate-200 shadow-sm p-12 flex items-center justify-center"
      >

        <div
          class="w-14 h-14 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"
        ></div>

      </div>

      <!-- ====================================================== -->
      <!-- Form -->
      <!-- ====================================================== -->

      <form
        v-else
        @submit.prevent="submitForm"
        class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden"
      >

        <!-- ====================================================== -->
        <!-- Error Alert -->
        <!-- ====================================================== -->

        <div
          v-if="generalError"
          class="mx-6 mt-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4"
        >

          <div
            class="flex items-start gap-3"
          >

            <div
              class="text-red-500 text-lg"
            >
              ⚠️
            </div>

            <div>

              <h3
                class="font-semibold text-red-800"
              >
                Validation Error
              </h3>

              <p
                class="text-sm text-red-600 mt-1"
              >
                Please correct the highlighted fields.
              </p>

            </div>

          </div>

        </div>

        <!-- ====================================================== -->
        <!-- Content -->
        <!-- ====================================================== -->

        <div
          class="p-6 lg:p-8 space-y-10"
        >

          <!-- ====================================================== -->
          <!-- Basic -->
          <!-- ====================================================== -->

          <section>

            <div
              class="mb-5"
            >

              <h2
                class="text-lg font-semibold text-slate-900"
              >
                Basic Information
              </h2>

              <p
                class="text-sm text-slate-500 mt-1"
              >
                Core building registration details
              </p>

            </div>

            <div
              class="grid grid-cols-1 md:grid-cols-2 gap-5"
            >

              <!-- Name -->

              <div>

                <label class="field-label">
                  Building Name
                </label>

                <input
                  v-model="form.name"
                  type="text"
                  placeholder="Sunrise Tower"
                  :class="fieldClass('name')"
                />

                <p
                  v-if="errors.name"
                  class="field-error"
                >
                  {{ errors.name[0] }}
                </p>

              </div>

              <!-- Code -->

              <div>

                <label class="field-label">
                  Building Code
                </label>

                <input
                  v-model="form.code"
                  type="text"
                  placeholder="BLD-001"
                  :class="fieldClass('code')"
                />

                <p
                  v-if="errors.code"
                  class="field-error"
                >
                  {{ errors.code[0] }}
                </p>

              </div>

              <!-- Floors -->

              <div>

                <label class="field-label">
                  Total Floors
                </label>

                <input
                  v-model.number="form.total_floors"
                  type="number"
                  min="1"
                  :class="fieldClass('total_floors')"
                />

              </div>
              <div>

                <label class="field-label">
                  Total Units
                </label>

                <input
                  v-model.number="form.total_units"
                  type="number"
                  min="1"
                  :class="fieldClass('total_units')"
                />

              </div>

              <!-- Status -->

              <div>

                <label class="field-label">
                  Building Status
                </label>

                <select
                  v-model="form.is_active"
                  :class="fieldClass('is_active')"
                >

                  <option :value="true">
                    Active
                  </option>

                  <option :value="false">
                    Inactive
                  </option>

                </select>

              </div>

            </div>

          </section>

          <!-- ====================================================== -->
          <!-- Location -->
          <!-- ====================================================== -->

          <section>

            <div
              class="mb-5"
            >

              <h2
                class="text-lg font-semibold text-slate-900"
              >
                Location Information
              </h2>

              <p
                class="text-sm text-slate-500 mt-1"
              >
                Multi-country operational building setup
              </p>

            </div>

            <div
              class="space-y-5"
            >

              <!-- Address -->

              <div>

                <label class="field-label">
                  Address
                </label>

                <textarea
                  v-model="form.address"
                  rows="4"
                  placeholder="Building address"
                  :class="[fieldClass('address'), 'resize-none']"
                ></textarea>

              </div>

              <!-- Grid -->

              <div
                class="grid grid-cols-1 md:grid-cols-3 gap-5"
              >

                <!-- City -->

                <div>

                  <label class="field-label">
                    City
                  </label>

                  <input
                    v-model="form.city"
                    type="text"
                    placeholder="Mogadishu"
                    :class="fieldClass('city')"
                  />

                </div>

                <!-- Country -->

                <div>

                  <label class="field-label">
                    Country
                  </label>

                  <input
                    v-model="form.country"
                    type="text"
                    placeholder="Somalia"
                    :class="fieldClass('country')"
                  />

                </div>

                <!-- Timezone -->

                <div>

                  <label class="field-label">
                    Timezone
                  </label>

                  <select
                    v-model="form.timezone"
                    :class="fieldClass('timezone')"
                  >

                    <option value="Africa/Mogadishu">
                      Africa/Mogadishu
                    </option>

                    <option value="Africa/Nairobi">
                      Africa/Nairobi
                    </option>

                    <option value="Asia/Dubai">
                      Asia/Dubai
                    </option>

                    <option value="Europe/London">
                      Europe/London
                    </option>

                  </select>

                </div>

              </div>

            </div>

          </section>

          <!-- ====================================================== -->
          <!-- Financial -->
          <!-- ====================================================== -->

          <section>

            <div
              class="mb-5"
            >

              <h2
                class="text-lg font-semibold text-slate-900"
              >
                Financial Settings
              </h2>

              <p
                class="text-sm text-slate-500 mt-1"
              >
                Operational billing currency configuration
              </p>

            </div>

            <div
              class="grid grid-cols-1 md:grid-cols-2 gap-5"
            >

              <!-- Currency -->

              <div>

                <label class="field-label">
                  Operating Currency
                </label>

                <select
                  v-model="form.operating_currency"
                  :class="fieldClass('operating_currency')"
                >

                  <option value="USD">
                    USD — US Dollar
                  </option>

                  <option value="SOS">
                    SOS — Somali Shilling
                  </option>

                  <option value="KES">
                    KES — Kenyan Shilling
                  </option>

                  <option value="AED">
                    AED — UAE Dirham
                  </option>

                  <option value="EUR">
                    EUR — Euro
                  </option>

                  <option value="GBP">
                    GBP — British Pound
                  </option>

                </select>

              </div>

            </div>

          </section>

        </div>

        <!-- ====================================================== -->
        <!-- Footer -->
        <!-- ====================================================== -->

        <div
          class="border-t border-slate-100 px-6 lg:px-8 py-5 flex items-center justify-end gap-3"
        >

          <router-link
            to="/buildings"
            class="px-5 py-3 rounded-2xl border border-slate-200 hover:bg-slate-50 transition font-medium"
          >
            Cancel
          </router-link>

          <button
            type="submit"
            :disabled="submitting"
            class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-medium shadow-lg shadow-indigo-200 transition disabled:opacity-60"
          >

            <svg
              v-if="submitting"
              class="animate-spin w-5 h-5"
              fill="none"
              viewBox="0 0 24 24"
            >

              <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
              />

              <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
              />

            </svg>

            {{
              submitting
                ? 'Updating...'
                : 'Update Building'
            }}

          </button>

        </div>

      </form>

    </div>

  
</template>

<script setup>

import {
  reactive,
  ref,
  computed,
  onMounted
} from 'vue'

import {
  useRoute,
  useRouter
} from 'vue-router'

import api
from '@/services/api'

import DashboardLayout
from '@/layouts/DashboardLayout.vue'

/*
|--------------------------------------------------------------------------
| Router
|--------------------------------------------------------------------------
*/

const route =
  useRoute()

const router =
  useRouter()

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const loading =
  ref(true)

const submitting =
  ref(false)

const errors =
  reactive({})

const form =
  reactive({

    name: '',

    code: '',

    total_floors: 1,
    total_unit:0,

    address: '',

    city: '',

    country: '',

    timezone:
      'Africa/Mogadishu',

    operating_currency:
      'USD',

    is_active: true,
  })

/*
|--------------------------------------------------------------------------
| Computed
|--------------------------------------------------------------------------
*/

const generalError =
computed(() => {

  return Object.keys(
    errors
  ).length > 0
})

/*
|--------------------------------------------------------------------------
| Field Classes
|--------------------------------------------------------------------------
*/

const fieldClass =
(field) => {

  const base =
    'w-full rounded-2xl border px-4 py-3 text-sm outline-none transition'

  return errors[field]

    ? `${base} border-red-300 bg-red-50 focus:ring-4 focus:ring-red-100`

    : `${base} border-slate-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100`
}

/*
|--------------------------------------------------------------------------
| Fetch Building
|--------------------------------------------------------------------------
*/

const fetchBuilding =
async () => {

  try {

    loading.value = true

    const response =
      await api.get(
        `/buildings/${route.params.id}`
      )

    Object.assign(
      form,
      response.data.data
    )
  }
  catch (error) {

    console.error(error)

    alert(
      'Failed to load building'
    )

    router.push(
      '/buildings'
    )
  }
  finally {

    loading.value = false
  }
}

/*
|--------------------------------------------------------------------------
| Submit
|--------------------------------------------------------------------------
*/

const submitForm =
async () => {

  try {

    submitting.value = true

    Object.keys(errors)
      .forEach(
        key => delete errors[key]
      )

    await api.put(

      `/buildings/${route.params.id}`,

      form
    )

    router.push(
      '/buildings'
    )
  }
  catch (error) {

    console.error(error)

    if (
      error.response?.status === 422
    ) {

      Object.assign(

        errors,

        error.response.data.errors
      )
    }
    else {

      alert(
        'Failed to update building'
      )
    }
  }
  finally {

    submitting.value = false
  }
}

/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/

onMounted(() => {

  fetchBuilding()
})

</script>

<style scoped>

.field-label {

  display: block;

  margin-bottom: 0.5rem;

  font-size: 0.75rem;

  font-weight: 700;

  text-transform: uppercase;

  letter-spacing: 0.08em;

  color: rgb(71 85 105);
}

.field-error {

  margin-top: 0.4rem;

  font-size: 0.75rem;

  color: rgb(220 38 38);
}

</style>