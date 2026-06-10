<template>
  <AuthShellLayout
    eyebrow="Enterprise Operations"
    description="From lease agreements and utility billing to apartment sales and financial reporting — manage your entire real estate operation in a single workspace."
    :features="brandFeatures"
  >
    <template #headline>
      One platform.<br />Every property.
    </template>
    <PageHeader
      title="Welcome back"
      description="Sign in to your company workspace"
    />

    <AlertBanner
      v-if="errorMessage"
      class="mt-5"
      :message="errorMessage"
      @dismiss="errorMessage = ''"
    />

    <ErpPanel class="mt-5">
      <form class="space-y-5" @submit.prevent="submitLogin" novalidate>
        <FormField label="Email address" :error="fieldErrors.email" required>
          <template #default="{ id }">
          <input
            :id="id"
            v-model="form.email"
            type="email"
            required
            autocomplete="email"
            placeholder="you@company.com"
            class="erp-input"
            :class="{ 'erp-input--error': fieldErrors.email }"
            @focus="fieldErrors.email = ''"
            @blur="validateEmail"
          />
          </template>
        </FormField>

        <FormField label="Password" :error="fieldErrors.password" required>
          <template #default="{ id }">
            <div class="relative">
              <input
                :id="id"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                required
                autocomplete="current-password"
                placeholder="Enter your password"
                class="erp-input pr-10"
                :class="{ 'erp-input--error': fieldErrors.password }"
                @focus="fieldErrors.password = ''"
                @blur="validatePassword"
              />
              <button
                type="button"
                class="absolute right-2.5 top-1/2 -translate-y-1/2 rounded p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                :aria-label="showPassword ? 'Hide password' : 'Show password'"
                @click="showPassword = !showPassword"
              >
                <svg v-if="!showPassword" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                </svg>
                <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                  <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                  <line x1="1" y1="1" x2="23" y2="23"/>
                </svg>
              </button>
            </div>
          </template>
        </FormField>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <label class="flex cursor-pointer items-center gap-2.5">
            <input
              v-model="rememberMe"
              type="checkbox"
              class="h-4 w-4 shrink-0 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600"
            />
            <span class="text-sm text-slate-600 dark:text-slate-400">Keep me signed in</span>
          </label>
          <a
            href="#"
            class="text-sm font-medium text-indigo-600 hover:text-indigo-700 sm:text-right dark:text-indigo-400 dark:hover:text-indigo-300"
            tabindex="-1"
          >
            Forgot password?
          </a>
        </div>

        <ErpButton
          variant="primary"
          native-type="submit"
          size="lg"
          class="w-full"
          :loading="isSubmitting"
          :disabled="isSubmitting"
        >
          Sign in
        </ErpButton>
      </form>
    </ErpPanel>

    <div class="mt-6 text-center">
      <p class="text-sm text-slate-500 dark:text-slate-400">
        New to AMSAAS?
        <router-link
          to="/onboarding/company"
          class="font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300"
        >
          Create your company workspace
        </router-link>
      </p>
      <p class="mt-3 text-xs text-slate-400 dark:text-slate-500">
        By signing in you agree to the
        <a href="#" class="text-indigo-600 hover:underline dark:text-indigo-400">Terms of Service</a>
        and
        <a href="#" class="text-indigo-600 hover:underline dark:text-indigo-400">Privacy Policy</a>
      </p>
    </div>
  </AuthShellLayout>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AuthShellLayout from '@/layouts/AuthShellLayout.vue'
import { PageHeader, ErpPanel, FormField, ErpButton, AlertBanner } from '@/components/erp'

const router = useRouter()
const authStore = useAuthStore()

const isSubmitting = ref(false)
const errorMessage = ref('')
const showPassword = ref(false)
const rememberMe = ref(false)

const form = reactive({ email: '', password: '' })
const fieldErrors = reactive({ email: '', password: '' })

const brandFeatures = [
  {
    title: 'Multi-building management',
    sub: 'Manage unlimited buildings, floors, and units',
    icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
  },
  {
    title: 'Utility billing engine',
    sub: 'Metered readings auto-converted to billable charges',
    icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>',
  },
  {
    title: 'Full financial operations',
    sub: 'Invoices, payments, receivables, and audit trails',
    icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>',
  },
  {
    title: 'Tenant-isolated & secure',
    sub: 'UUID architecture with Sanctum-powered authentication',
    icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
  },
]

const validateEmail = () => {
  if (!form.email) { fieldErrors.email = 'Email address is required'; return false }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) { fieldErrors.email = 'Enter a valid email address'; return false }
  fieldErrors.email = ''
  return true
}

const validatePassword = () => {
  if (!form.password) { fieldErrors.password = 'Password is required'; return false }
  if (form.password.length < 6) { fieldErrors.password = 'Password must be at least 6 characters'; return false }
  fieldErrors.password = ''
  return true
}

const submitLogin = async () => {
  const emailOk = validateEmail()
  const passwordOk = validatePassword()
  if (!emailOk || !passwordOk) return

  isSubmitting.value = true
  errorMessage.value = ''

  try {
    await authStore.login(form)
    router.push('/dashboard')
  } catch (error) {
    errorMessage.value =
      error.response?.data?.message ||
      'Invalid email or password. Please try again.'
  } finally {
    isSubmitting.value = false
  }
}
</script>
