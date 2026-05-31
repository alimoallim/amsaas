<template>
  

    <!-- ───────────────────────────────────────────────────────────── -->
    <!-- Toast Notification -->
    <!-- ───────────────────────────────────────────────────────────── -->

    <Transition name="toast">

      <div
        v-if="toast.visible"
        :class="[
          'fixed top-5 right-5 z-50 flex items-start gap-3 rounded-2xl px-4 py-3.5 shadow-lg border text-sm font-medium max-w-sm',
          toast.type === 'success'
            ? 'bg-emerald-50 border-emerald-200 text-emerald-800'
            : 'bg-red-50 border-red-200 text-red-800'
        ]"
      >

        <span class="mt-0.5 shrink-0">

          <svg
            v-if="toast.type === 'success'"
            class="w-4 h-4 text-emerald-500"
            fill="none"
            stroke="currentColor"
            stroke-width="2.5"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M4.5 12.75l6 6 9-13.5"
            />
          </svg>

          <svg
            v-else
            class="w-4 h-4 text-red-500"
            fill="none"
            stroke="currentColor"
            stroke-width="2.5"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z"
            />
          </svg>

        </span>

        <span class="flex-1 leading-snug">
          {{ toast.message }}
        </span>

        <button
          @click="toast.visible = false"
          class="ml-1 -mt-0.5 opacity-50 hover:opacity-100 transition"
        >

          <svg
            class="w-3.5 h-3.5"
            fill="none"
            stroke="currentColor"
            stroke-width="2.5"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>

        </button>

      </div>

    </Transition>

    <!-- ───────────────────────────────────────────────────────────── -->
    <!-- Page -->
    <!-- ───────────────────────────────────────────────────────────── -->

    <div class="max-w-5xl mx-auto px-1">

      <!-- Breadcrumb -->

      <nav
        class="flex items-center gap-1.5 text-xs text-slate-400 mb-5 font-medium"
      >

        <router-link
          to="/buildings"
          class="hover:text-slate-600 transition"
        >
          Buildings
        </router-link>

        <svg
          class="w-3 h-3"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M8.25 4.5l7.5 7.5-7.5 7.5"
          />
        </svg>

        <span class="text-slate-600">
          Add Building
        </span>

      </nav>

      <!-- Header -->

      <div
        class="flex items-start justify-between gap-4 mb-7"
      >

        <div>

          <h1
            class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900"
          >
            Add Building
          </h1>

          <p
            class="text-slate-500 mt-1 text-sm"
          >
            Register a new building for your ERP workspace
          </p>

        </div>

        <router-link
          to="/buildings"
          class="shrink-0 inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-sm font-medium text-slate-600 transition"
        >

          <svg
            class="w-3.5 h-3.5"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"
            />
          </svg>

          Back

        </router-link>

      </div>

      <!-- Validation Errors -->

      <Transition name="fade-slide">

        <div
          v-if="generalError"
          class="mb-5 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3.5 text-sm text-red-700"
        >

          <svg
            class="mt-0.5 w-4 h-4 shrink-0 text-red-400"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"
            />
          </svg>

          <div>

            <p class="font-semibold mb-1">
              Please fix the following errors:
            </p>

            <ul
              class="list-disc list-inside space-y-0.5 text-red-600 text-xs"
            >

              <li
                v-for="(msgs, field) in errors"
                :key="field"
              >
                <span class="font-medium capitalize">
                  {{ field.replace('_', ' ') }}:
                </span>

                {{ msgs[0] }}
              </li>

            </ul>

          </div>

        </div>

      </Transition>

      <!-- Form -->

      <div
        class="bg-white border border-slate-200/80 rounded-3xl shadow-sm shadow-slate-100 overflow-hidden"
      >

        <form
          @submit.prevent="submitForm"
          novalidate
        >

          <!-- Header -->

          <div
            class="border-b border-slate-100 px-6 sm:px-8 py-5"
          >

            <h2
              class="text-base font-semibold text-slate-900"
            >
              Building Registration
            </h2>

            <p
              class="text-xs text-slate-400 mt-0.5"
            >
              Multi-country & multi-currency ERP building registration
            </p>

          </div>

          <!-- Body -->

          <div
            class="p-5 sm:p-7 lg:p-8 space-y-8"
          >

            <!-- Basic -->

            <section>

              <h3
                class="text-sm font-semibold text-slate-700 uppercase tracking-wider mb-4"
              >
                Basic Information
              </h3>

              <div
                class="grid grid-cols-1 md:grid-cols-2 gap-4"
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
                <!-- Total Units -->

<div>

  <label class="field-label">
    Total Units
  </label>

  <input
    v-model.number="form.total_units"
    type="number"
    min="0"
    :class="fieldClass('total_units')"
  />

  <p
    v-if="errors.total_units"
    class="field-error"
  >
    {{ errors.total_units[0] }}
  </p>

</div>

                <!-- Status -->

                <div>

                  <label class="field-label">
                    Status
                  </label>

                  <select
                    v-model="form.status"
                    :class="fieldClass('status')"
                  >

                    <option value="active">
                      Active
                    </option>

                    <option value="inactive">
                      Inactive
                    </option>

                  </select>

                </div>

              </div>

            </section>

            <!-- Location -->

            <section>

              <h3
                class="text-sm font-semibold text-slate-700 uppercase tracking-wider mb-4"
              >
                Location
              </h3>

              <div class="space-y-4">

                <!-- Address -->

                <div>

                  <label class="field-label">
                    Address
                  </label>

                  <textarea
                    v-model="form.address"
                    rows="3"
                    placeholder="Street address"
                    :class="[fieldClass('address'), 'resize-none']"
                  />

                </div>

                <div
                  class="grid grid-cols-1 md:grid-cols-3 gap-4"
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

            <!-- Financial -->

            <section>

              <h3
                class="text-sm font-semibold text-slate-700 uppercase tracking-wider mb-4"
              >
                Financial Settings
              </h3>

              <div
                class="grid grid-cols-1 md:grid-cols-2 gap-4"
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

          <!-- Footer -->

          <div
            class="border-t border-slate-100 px-5 sm:px-7 lg:px-8 py-4 flex justify-end gap-3"
          >

            <router-link
              to="/buildings"
              class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-sm font-medium text-slate-600 transition"
            >
              Cancel
            </router-link>

            <button
              type="submit"
              :disabled="submitting"
              class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold transition disabled:opacity-60"
            >

              <svg
                v-if="submitting"
                class="animate-spin w-4 h-4"
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

              {{ submitting ? 'Creating...' : 'Create Building' }}

            </button>

          </div>

        </form>

      </div>

    </div>


</template>

<script setup>

import {
  reactive,
  ref,
  computed,
} from 'vue'

import {
  useRouter,
} from 'vue-router'

import DashboardLayout
from '@/layouts/DashboardLayout.vue'

import api
from '@/services/api'

const router =
  useRouter()

const submitting =
  ref(false)

const errors =
  ref({})

const toast =
  reactive({

    visible: false,

    type: 'success',

    message: '',
  })

const form =
  reactive({

    name: '',

    code: '',

    total_floors: 1,
    total_unit:0,

    address: '',

    city: '',

    country: 'Somalia',

    timezone:
      'Africa/Mogadishu',

    operating_currency:
      'USD',

    status: 'active',
  })

const generalError =
  computed(() =>
    Object.keys(
      errors.value
    ).length > 0
  )

function showToast(
  type,
  message,
  duration = 4000
) {

  Object.assign(
    toast,
    {
      visible: true,
      type,
      message,
    }
  )

  setTimeout(() => {

    toast.visible = false

  }, duration)
}

function fieldClass(field) {

  const base =
    'w-full rounded-xl border px-3 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 outline-none transition bg-white'

  return errors.value[field]

    ? `${base} border-red-300 ring-4 ring-red-50`

    : `${base} border-slate-200 focus:border-slate-400 focus:ring-4 focus:ring-slate-100`
}

const submitForm =
async () => {

  errors.value = {}

  try {

    submitting.value = true

    await api.post(
      '/buildings',
      form
    )

    showToast(
      'success',
      'Building created successfully.'
    )

    setTimeout(() => {

      router.push(
        '/buildings'
      )

    }, 900)
  }
  catch (error) {

    if (
      error.response?.status === 422
    ) {

      errors.value =
        error.response.data.errors || {}

      showToast(
        'error',
        'Please fix validation errors.'
      )
    }
    else {

      showToast(
        'error',
        error.response?.data?.message
          || 'Failed to create building.'
      )
    }
  }
  finally {

    submitting.value = false
  }
}

</script>

<style scoped>

.toast-enter-active,
.toast-leave-active {
  transition: all 0.25s ease;
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.2s ease;
}

.fade-slide-enter-from,
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

.field-label {
  display: block;
  margin-bottom: 0.4rem;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgb(71 85 105);
}

</style>