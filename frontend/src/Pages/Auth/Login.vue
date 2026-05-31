<template>

  <div
    class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-indigo-50 flex items-center justify-center px-4 py-8"
  >

    <div
      class="w-full max-w-5xl bg-white rounded-3xl shadow-2xl overflow-hidden grid lg:grid-cols-2 border border-slate-200"
    >

      <!-- LEFT PANEL -->
      <div
        class="hidden lg:flex flex-col justify-between bg-gradient-to-br from-indigo-600 via-blue-700 to-slate-900 p-12 text-white relative overflow-hidden"
      >

        <div
          class="absolute inset-0 opacity-10"
        >
          <div
            class="absolute top-0 left-0 w-72 h-72 bg-white rounded-full blur-3xl"
          ></div>

          <div
            class="absolute bottom-0 right-0 w-96 h-96 bg-cyan-300 rounded-full blur-3xl"
          ></div>
        </div>

        <div class="relative z-10">

          <div
            class="flex items-center gap-3"
          >

            <div
              class="w-14 h-14 rounded-2xl bg-white/20 backdrop-blur flex items-center justify-center text-2xl font-black"
            >
              A
            </div>

            <div>

              <h1
                class="text-3xl font-black tracking-tight"
              >
                AMSAAS
              </h1>

              <p
                class="text-blue-100 text-sm"
              >
                Enterprise Property Management
              </p>

            </div>

          </div>

          <div class="mt-20">

            <h2
              class="text-5xl font-black leading-tight"
            >
              Welcome back.
            </h2>

            <p
              class="mt-6 text-lg text-blue-100 leading-relaxed"
            >
              Securely access your company workspace and manage buildings,
              apartments, invoices, payments, and tenants from one platform.
            </p>

          </div>

        </div>

        <div
          class="relative z-10 grid grid-cols-3 gap-4"
        >

          <div
            class="bg-white/10 backdrop-blur rounded-2xl p-4 border border-white/10"
          >
            <p class="text-3xl font-black">
              SaaS
            </p>

            <p class="text-sm text-blue-100 mt-1">
              Multi-Tenant
            </p>
          </div>

          <div
            class="bg-white/10 backdrop-blur rounded-2xl p-4 border border-white/10"
          >
            <p class="text-3xl font-black">
              API
            </p>

            <p class="text-sm text-blue-100 mt-1">
              Sanctum Auth
            </p>
          </div>

          <div
            class="bg-white/10 backdrop-blur rounded-2xl p-4 border border-white/10"
          >
            <p class="text-3xl font-black">
              UUID
            </p>

            <p class="text-sm text-blue-100 mt-1">
              Secure Architecture
            </p>
          </div>

        </div>

      </div>

      <!-- RIGHT PANEL -->
      <div
        class="flex items-center justify-center p-8 lg:p-14"
      >

        <div
          class="w-full max-w-md"
        >

          <!-- MOBILE LOGO -->
          <div
            class="lg:hidden text-center mb-10"
          >

            <div
              class="w-16 h-16 rounded-2xl bg-indigo-600 text-white flex items-center justify-center mx-auto text-2xl font-black"
            >
              A
            </div>

            <h1
              class="mt-4 text-3xl font-black text-slate-900"
            >
              AMSAAS
            </h1>

          </div>

          <div>

            <h2
              class="text-3xl font-black text-slate-900"
            >
              Sign In
            </h2>

            <p
              class="mt-2 text-slate-500"
            >
              Access your enterprise workspace.
            </p>

          </div>

          <!-- ERROR -->
          <div
            v-if="errorMessage"
            class="mt-6 rounded-2xl bg-red-50 border border-red-200 px-4 py-3 text-red-600 text-sm"
          >
            {{ errorMessage }}
          </div>

          <!-- FORM -->
          <form
            class="mt-8 space-y-6"
            @submit.prevent="submitLogin"
          >

            <!-- EMAIL -->
            <div>

              <label
                class="block text-sm font-semibold text-slate-700 mb-2"
              >
                Email Address
              </label>

              <input

                v-model="form.email"

                type="email"

                required

                autocomplete="email"

                class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:outline-none focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 transition"
              />

            </div>

            <!-- PASSWORD -->
            <div>

              <label
                class="block text-sm font-semibold text-slate-700 mb-2"
              >
                Password
              </label>

              <input

                v-model="form.password"

                type="password"

                required

                autocomplete="current-password"

                class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:outline-none focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 transition"
              />

            </div>

            <!-- BUTTON -->
            <button

              type="submit"

              :disabled="isSubmitting"

              class="w-full bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 text-white py-4 rounded-2xl font-bold shadow-lg shadow-indigo-200 transition-all duration-300 disabled:opacity-50 flex items-center justify-center"
            >

              <svg
                v-if="isSubmitting"
                class="animate-spin h-5 w-5 mr-3"
                xmlns="http://www.w3.org/2000/svg"
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
                  d="M4 12a8 8 0 018-8v8z"
                />
              </svg>

              {{
                isSubmitting
                  ? 'Signing In...'
                  : 'Sign In'
              }}

            </button>

          </form>

        </div>

      </div>

    </div>

  </div>

</template>

<script setup>

import {
  reactive,
  ref,
} from 'vue'

import {
  useRouter,
} from 'vue-router'

import {
  useAuthStore,
} from '@/stores/auth'

const router =
  useRouter()

const authStore =
  useAuthStore()

const isSubmitting =
  ref(false)

const errorMessage =
  ref('')

const form = reactive({

  email: '',

  password: '',
})

const submitLogin =
async () => {

  isSubmitting.value = true

  errorMessage.value = ''

  try {

    await authStore.login(
      form
    )

    router.push(
      '/dashboard'
    )

  }
  catch (error) {

    errorMessage.value =

      error.response?.data?.message ||

      'Invalid login credentials.'
  }
  finally {

    isSubmitting.value = false
  }
}

</script>