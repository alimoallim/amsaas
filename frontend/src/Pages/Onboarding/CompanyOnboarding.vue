<template>
  <AuthShellLayout
    wide
    eyebrow="Workspace Setup"
    headline="Your property empire starts here."
    description="Configure your company profile, create your admin account, and get immediate access to your fully-isolated management workspace."
    :features="brandFeatures"
  >
    <PageHeader
      eyebrow="Onboarding"
      title="Create your workspace"
      description="Fill in your company details and administrator credentials to get started"
    />

    <!-- Step progress -->
    <div
      class="mt-5 rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4 dark:border-slate-700 dark:bg-slate-900"
      role="list"
      aria-label="Setup steps"
    >
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-0">
        <div
          v-for="(step, i) in steps"
          :key="step.id"
          class="flex flex-1 items-center gap-3"
          role="listitem"
        >
          <div
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold transition"
            :class="stepBubbleClass(i)"
          >
            <svg
              v-if="currentStep > i"
              width="14"
              height="14"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="3"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <polyline points="20 6 9 17 4 12"/>
            </svg>
            <span v-else>{{ i + 1 }}</span>
          </div>
          <div class="min-w-0 flex-1">
            <p
              class="truncate text-sm font-medium"
              :class="currentStep >= i ? 'text-slate-900 dark:text-slate-100' : 'text-slate-400'"
            >
              {{ step.title }}
            </p>
            <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ step.sub }}</p>
          </div>
          <div
            v-if="i < steps.length - 1"
            class="mx-2 hidden h-px flex-1 sm:block"
            :class="currentStep > i ? 'bg-emerald-400' : 'bg-slate-200 dark:bg-slate-700'"
          />
        </div>
      </div>
      <p class="mt-3 text-center text-xs text-slate-500 sm:hidden dark:text-slate-400">
        Step {{ currentStep + 1 }} of {{ steps.length }} — {{ steps[currentStep].title }}
      </p>
    </div>

    <AlertBanner
      v-if="serverError"
      class="mt-5"
      :message="serverError"
      @dismiss="serverError = ''"
    />

    <form class="mt-5" @submit.prevent="submitCompany" novalidate>
      <!-- Step 1: Company -->
      <ErpPanel v-show="currentStep === 0" :no-padding="true">
        <FormSection
          title="Company information"
          description="Your organization's legal identity and contact details"
        >
          <FormGrid>
            <FormField label="Company name" span="2" :error="errors.company_name?.[0]" required>
              <input
                v-model="form.company_name"
                type="text"
                required
                placeholder="e.g. Horizon Property Group"
                class="erp-input"
                :class="{ 'erp-input--error': errors.company_name }"
                @blur="touchField('company_name')"
              />
            </FormField>

            <FormField label="Company email" :error="errors.company_email?.[0]" required>
              <input
                v-model="form.company_email"
                type="email"
                required
                placeholder="company@example.com"
                class="erp-input"
                :class="{ 'erp-input--error': errors.company_email }"
              />
            </FormField>

            <FormField label="Phone number">
              <input
                v-model="form.company_phone"
                type="text"
                placeholder="+1 (555) 000-0000"
                class="erp-input"
              />
            </FormField>

            <FormField label="Country">
              <select v-model="form.company_country" class="erp-select">
                <option value="">Select country…</option>
                <option value="Somalia">Somalia</option>
                <option value="Kenya">Kenya</option>
                <option value="Ethiopia">Ethiopia</option>
                <option value="Djibouti">Djibouti</option>
                <option value="Uganda">Uganda</option>
                <option value="Tanzania">Tanzania</option>
                <option value="Rwanda">Rwanda</option>
              </select>
            </FormField>

            <FormField label="City">
              <input
                v-model="form.company_city"
                type="text"
                placeholder="e.g. Mogadishu"
                class="erp-input"
              />
            </FormField>

            <FormField label="Registration no." hint="Optional">
              <input
                v-model="form.registration_number"
                type="text"
                placeholder="Business reg. number"
                class="erp-input font-mono text-sm"
              />
            </FormField>

            <FormField label="Tax number" hint="Optional">
              <input
                v-model="form.tax_number"
                type="text"
                placeholder="VAT / TIN"
                class="erp-input font-mono text-sm"
              />
            </FormField>

            <FormField label="Physical address" span="2" hint="Optional">
              <textarea
                v-model="form.company_address"
                rows="3"
                placeholder="Street address, district, postal code…"
                class="erp-input min-h-[88px] resize-y"
              />
            </FormField>
          </FormGrid>
        </FormSection>

        <template #footer>
          <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-xs text-slate-500 dark:text-slate-400">
              Fields marked <span class="text-red-500">*</span> are required
            </p>
            <ErpButton native-type="button" @click="goNext">
              Continue to admin setup
            </ErpButton>
          </div>
        </template>
      </ErpPanel>

      <!-- Step 2: Admin -->
      <ErpPanel v-show="currentStep === 1" :no-padding="true">
        <FormSection
          title="Administrator account"
          description="This account will have full control over your workspace"
        >
          <div class="mb-5 flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50/80 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/50">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-sm font-bold text-white">
              {{ adminInitials || '?' }}
            </div>
            <div class="min-w-0">
              <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">
                {{ form.name || 'Your full name' }}
              </p>
              <p class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                <span class="rounded-full bg-indigo-50 px-2 py-0.5 font-semibold text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300">
                  Administrator
                </span>
                <span class="truncate">{{ form.email || 'login@email.com' }}</span>
              </p>
            </div>
          </div>

          <FormGrid>
            <FormField label="Full name" span="2" :error="errors.name?.[0]" required>
              <input
                v-model="form.name"
                type="text"
                required
                placeholder="e.g. Ahmed Mohamed Ali"
                class="erp-input"
                :class="{ 'erp-input--error': errors.name }"
              />
            </FormField>

            <FormField label="Login email" :error="errors.email?.[0]" required>
              <input
                v-model="form.email"
                type="email"
                required
                placeholder="admin@yourcompany.com"
                class="erp-input"
                :class="{ 'erp-input--error': errors.email }"
              />
            </FormField>

            <FormField label="Password" :error="errors.password?.[0]" required>
              <div class="relative">
                <input
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  required
                  placeholder="Min. 8 characters"
                  class="erp-input pr-10"
                  :class="{ 'erp-input--error': errors.password }"
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
              <div v-if="form.password" class="mt-2 flex items-center gap-2">
                <div class="flex flex-1 gap-1">
                  <div
                    v-for="i in 4"
                    :key="i"
                    class="h-1 flex-1 rounded-full transition"
                    :class="strengthBarClass(i)"
                  />
                </div>
                <span class="text-xs font-medium" :class="strengthLabelClass">
                  {{ passwordStrength.label }}
                </span>
              </div>
            </FormField>
          </FormGrid>

          <AlertBanner
            variant="success"
            :dismissible="false"
            class="mt-2"
            message="Your credentials are encrypted in transit and stored using industry-standard hashing. We never store plain-text passwords."
          />
        </FormSection>

        <template #footer>
          <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
            <ErpButton variant="secondary" native-type="button" @click="goBack">
              Back
            </ErpButton>
            <ErpButton
              native-type="submit"
              :loading="isSubmitting"
              :disabled="isSubmitting"
            >
              {{ isSubmitting ? 'Creating workspace…' : 'Launch workspace' }}
            </ErpButton>
          </div>
        </template>
      </ErpPanel>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
      Already have an account?
      <router-link
        to="/login"
        class="font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300"
      >
        Sign in instead
      </router-link>
    </p>
  </AuthShellLayout>
</template>

<script setup>
import { reactive, ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import AuthShellLayout from '@/layouts/AuthShellLayout.vue'
import {
  PageHeader,
  ErpPanel,
  FormSection,
  FormGrid,
  FormField,
  ErpButton,
  AlertBanner,
} from '@/components/erp'

const router = useRouter()
const authStore = useAuthStore()

const isSubmitting = ref(false)
const serverError = ref('')
const errors = ref({})
const showPassword = ref(false)
const currentStep = ref(0)

const steps = [
  { id: 'company', title: 'Company info', sub: 'Organization details' },
  { id: 'admin', title: 'Admin account', sub: 'Credentials & access' },
]

const form = reactive({
  company_name: '',
  company_email: '',
  company_phone: '',
  company_address: '',
  company_city: '',
  company_country: '',
  registration_number: '',
  tax_number: '',
  name: '',
  email: '',
  password: '',
})

const brandFeatures = [
  {
    title: 'Multi-building management',
    sub: 'Manage unlimited buildings, floors, and units across any location',
    icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
  },
  {
    title: 'Tenant lifecycle',
    sub: 'From agreements to renewals — full lifecycle automation',
    icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
  },
  {
    title: 'Financial operations',
    sub: 'Invoices, payments, and revenue reports in one place',
    icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>',
  },
  {
    title: 'Isolated & secure',
    sub: 'UUID-based multi-tenant architecture with Sanctum auth',
    icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
  },
]

const adminInitials = computed(() => {
  if (!form.name) return ''
  return form.name.split(' ').slice(0, 2).map((w) => w[0]).join('').toUpperCase()
})

const passwordStrength = computed(() => {
  const p = form.password
  if (!p) return { score: 0, level: 'weak', label: '' }
  let score = 0
  if (p.length >= 8) score++
  if (p.length >= 12) score++
  if (/[A-Z]/.test(p) && /[a-z]/.test(p)) score++
  if (/[0-9]/.test(p) && /[^A-Za-z0-9]/.test(p)) score++
  const levels = ['', 'weak', 'fair', 'good', 'strong']
  const labels = ['', 'Too weak', 'Fair', 'Good', 'Strong']
  return { score, level: levels[score] || 'weak', label: labels[score] || 'Too weak' }
})

const strengthColors = {
  weak: 'bg-red-500',
  fair: 'bg-amber-500',
  good: 'bg-amber-400',
  strong: 'bg-emerald-500',
}

const strengthTextColors = {
  weak: 'text-red-600 dark:text-red-400',
  fair: 'text-amber-600 dark:text-amber-400',
  good: 'text-amber-500',
  strong: 'text-emerald-600 dark:text-emerald-400',
}

const strengthBarClass = (i) => {
  const filled = i <= passwordStrength.value.score
  if (!filled) return 'bg-slate-200 dark:bg-slate-700'
  return strengthColors[passwordStrength.value.level] || 'bg-slate-200'
}

const strengthLabelClass = computed(() =>
  strengthTextColors[passwordStrength.value.level] || 'text-slate-500',
)

const stepBubbleClass = (i) => {
  if (currentStep.value > i) {
    return 'bg-emerald-500 text-white'
  }
  if (currentStep.value === i) {
    return 'bg-indigo-600 text-white ring-4 ring-indigo-500/20'
  }
  return 'border border-slate-200 bg-slate-100 text-slate-400 dark:border-slate-600 dark:bg-slate-800'
}

const goNext = () => {
  if (!form.company_name || !form.company_email) {
    if (!form.company_name) errors.value.company_name = ['Company name is required']
    if (!form.company_email) errors.value.company_email = ['Company email is required']
    return
  }
  errors.value = {}
  currentStep.value = 1
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const goBack = () => {
  currentStep.value = 0
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const touchField = (field) => {
  if (form[field]) {
    delete errors.value[field]
  }
}

const submitCompany = async () => {
  isSubmitting.value = true
  errors.value = {}
  serverError.value = ''
  try {
    const response = await api.post('/setup/company', form)
    authStore.setAuth(response.data)
    router.push('/dashboard')
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
      const companyFields = ['company_name', 'company_email', 'company_phone', 'company_country']
      if (companyFields.some((f) => errors.value[f])) currentStep.value = 0
    } else {
      serverError.value = error.response?.data?.message || 'Unable to create workspace. Please try again.'
    }
  } finally {
    isSubmitting.value = false
  }
}
</script>
