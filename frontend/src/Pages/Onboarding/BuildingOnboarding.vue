<template>
  <div class="min-h-screen bg-slate-50 flex flex-col justify-center py-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto w-full">

      <!-- Step Tracker -->
      <div class="flex items-center justify-center mb-10">
        <div class="flex flex-col items-center gap-1.5">
          <div class="w-8 h-8 rounded-full bg-blue-100 border border-blue-300 flex items-center justify-center text-blue-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
          </div>
          <span class="text-xs text-blue-600 font-medium">Organization</span>
        </div>
        <div class="w-14 h-px bg-blue-500 mb-4 mx-1"></div>
        <div class="flex flex-col items-center gap-1.5">
          <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-white text-sm font-medium">2</div>
          <span class="text-xs text-blue-600 font-medium">Building</span>
        </div>
        <div class="w-14 h-px bg-slate-300 mb-4 mx-1"></div>
        <div class="flex flex-col items-center gap-1.5">
          <div class="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-400 text-sm font-medium">3</div>
          <span class="text-xs text-slate-400">Review</span>
        </div>
      </div>

      <!-- Card -->
      <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">

        <!-- Card Header -->
        <div class="px-7 py-5 border-b border-slate-100 flex items-center justify-between gap-4">
          <div class="flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
              <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z"/></svg>
            </div>
            <div>
              <p class="text-base font-medium text-slate-900 leading-tight">Building onboarding</p>
              <p class="text-sm text-slate-500">Register a new property into your portfolio</p>
            </div>
          </div>
          <span class="hidden sm:inline-flex items-center gap-1.5 text-xs text-blue-600 bg-blue-50 border border-blue-200 rounded-full px-3 py-1 whitespace-nowrap">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-9.5 11.25S.5 17.642.5 10.5a9 9 0 0119 0z"/></svg>
            Step 2 of 3
          </span>
        </div>

        <form @submit.prevent="submitForm">

          <!-- Section: Property Overview -->
          <div class="grid grid-cols-1 md:grid-cols-[200px_1fr] gap-6 px-7 py-6 border-b border-slate-100">
            <div class="pr-4">
              <div class="w-7 h-7 rounded-md bg-slate-100 flex items-center justify-center mb-2.5">
                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5V10.75M2.25 21h1.5m18 0h-18M2.25 9l4.5-1.636M18.75 3l-1.5.545m0 6.205 3 1m1.5.5-1.5-.5M6.75 7.364V3h-3v18m3-13.636 10.5-3.819"/></svg>
              </div>
              <p class="text-sm font-medium text-slate-800">Property overview</p>
              <p class="text-xs text-slate-400 mt-1 leading-relaxed">Basic identity and operational status of the building.</p>
            </div>
            <div class="flex flex-col gap-4">
              <div>
                <label class="block text-xs font-medium text-slate-600 mb-1.5">Building name <span class="text-red-500">*</span></label>
                <input v-model="form.name" type="text" placeholder="e.g. Hodan Towers" required
                  class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 bg-white text-slate-900 placeholder-slate-400 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-colors" />
                <p v-if="errors.name" class="mt-1 text-xs text-red-600">{{ errors.name[0] }}</p>
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-medium text-slate-600 mb-1.5">Building type <span class="text-red-500">*</span></label>
                  <select v-model="form.type" required
                    class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 bg-white text-slate-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-colors">
                    <option value="" disabled>Select type</option>
                    <option value="residential">Residential</option>
                    <option value="commercial">Commercial</option>
                    <option value="mixed">Mixed use</option>
                  </select>
                  <p v-if="errors.type" class="mt-1 text-xs text-red-600">{{ errors.type[0] }}</p>
                </div>
                <div>
                  <label class="block text-xs font-medium text-slate-600 mb-1.5">&nbsp;</label>
                  <label class="flex items-start gap-2.5 px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 cursor-pointer hover:bg-slate-100 transition-colors">
                    <input v-model="form.is_active" type="checkbox" class="mt-0.5 accent-blue-600 w-3.5 h-3.5 shrink-0" />
                    <div>
                      <span class="block text-sm font-medium text-slate-800 leading-tight">Active building</span>
                      <span class="block text-xs text-slate-400 mt-0.5">Visible in the system immediately</span>
                    </div>
                  </label>
                </div>
              </div>
              <div>
                <label class="block text-xs font-medium text-slate-600 mb-1.5">Description</label>
                <textarea v-model="form.description" rows="3" placeholder="Write a brief description of the building..."
                  class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 bg-white text-slate-900 placeholder-slate-400 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-colors resize-y"></textarea>
              </div>
            </div>
          </div>

          <!-- Section: Scale & Capacity -->
          <div class="grid grid-cols-1 md:grid-cols-[200px_1fr] gap-6 px-7 py-6 border-b border-slate-100">
            <div class="pr-4">
              <div class="w-7 h-7 rounded-md bg-slate-100 flex items-center justify-center mb-2.5">
                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5 7.5 3m0 0L12 7.5M7.5 3v13.5m13.5 0L16.5 21m0 0L12 16.5m4.5 4.5V7.5"/></svg>
              </div>
              <p class="text-sm font-medium text-slate-800">Scale &amp; capacity</p>
              <p class="text-xs text-slate-400 mt-1 leading-relaxed">Physical dimensions and unit allocation.</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-medium text-slate-600 mb-1.5">Total floors <span class="text-red-500">*</span></label>
                <input v-model="form.floors" type="number" min="1" placeholder="e.g. 12"
                  class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 bg-white text-slate-900 placeholder-slate-400 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-colors" />
                <p v-if="errors.floors" class="mt-1 text-xs text-red-600">{{ errors.floors[0] }}</p>
              </div>
              <div>
                <label class="block text-xs font-medium text-slate-600 mb-1.5">Total units <span class="text-red-500">*</span></label>
                <input v-model="form.total_units" type="number" min="1" placeholder="e.g. 48"
                  class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 bg-white text-slate-900 placeholder-slate-400 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-colors" />
                <p v-if="errors.total_units" class="mt-1 text-xs text-red-600">{{ errors.total_units[0] }}</p>
              </div>
            </div>
          </div>

          <!-- Section: Location & Financials -->
          <div class="grid grid-cols-1 md:grid-cols-[200px_1fr] gap-6 px-7 py-6">
            <div class="pr-4">
              <div class="w-7 h-7 rounded-md bg-slate-100 flex items-center justify-center mb-2.5">
                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z"/></svg>
              </div>
              <p class="text-sm font-medium text-slate-800">Location &amp; financials</p>
              <p class="text-xs text-slate-400 mt-1 leading-relaxed">Where the building is and its billing currency.</p>
            </div>
            <div class="flex flex-col gap-4">
              <div>
                <label class="block text-xs font-medium text-slate-600 mb-1.5">Street address</label>
                <input v-model="form.address" type="text" placeholder="Enter building address"
                  class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 bg-white text-slate-900 placeholder-slate-400 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-colors" />
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-medium text-slate-600 mb-1.5">City</label>
                  <input v-model="form.city" type="text" placeholder="e.g. Mogadishu"
                    class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 bg-white text-slate-900 placeholder-slate-400 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-colors" />
                </div>
                <div>
                  <label class="block text-xs font-medium text-slate-600 mb-1.5">Country</label>
                  <input v-model="form.country" type="text" placeholder="e.g. Somalia"
                    class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 bg-white text-slate-900 placeholder-slate-400 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-colors" />
                </div>
              </div>
              <div>
                <label class="block text-xs font-medium text-slate-600 mb-1.5">Default currency</label>
                <select v-model="form.currency_code"
                  class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 bg-white text-slate-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-colors">
                  <option value="USD">USD — US Dollar</option>
                  <option value="SOS">SOS — Somali Shilling</option>
                  <option value="KES">KES — Kenyan Shilling</option>
                  <option value="ETB">ETB — Ethiopian Birr</option>
                  <option value="AED">AED — UAE Dirham</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Validation Errors -->
          <div v-if="Object.keys(errors).length" class="mx-7 mb-5 flex items-start gap-2.5 px-4 py-3 rounded-lg border border-red-200 bg-red-50">
            <svg class="w-4 h-4 text-red-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
            <div>
              <p class="text-sm font-medium text-red-700">Please fix the following errors</p>
              <ul class="mt-1 list-disc pl-4 space-y-0.5">
                <li v-for="(error, key) in errors" :key="key" class="text-xs text-red-600">{{ error[0] }}</li>
              </ul>
            </div>
          </div>

          <!-- Footer -->
          <div class="px-7 py-4 border-t border-slate-100 bg-slate-50 flex items-center justify-between gap-3">
            <span class="hidden sm:flex items-center gap-1.5 text-xs text-slate-400">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/></svg>
              Changes are saved securely
            </span>
            <div class="flex items-center gap-2 ml-auto">
              <button type="button" @click="$router.back()"
                class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
                Back
              </button>
              <button type="submit" :disabled="loading"
                class="inline-flex items-center gap-1.5 px-5 py-2 text-sm font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                <svg v-if="loading" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                </svg>
                {{ loading ? 'Saving...' : 'Create building' }}
                <svg v-if="!loading" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
              </button>
            </div>
          </div>

        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'

const route = useRoute()
const router = useRouter()

const loading = ref(false)
const errors = ref({})

const form = reactive({
  company_id: route.params.company_id,
  name: '',
  type: '',
  address: '',
  city: '',
  country: '',
  currency_code: 'USD',
  floors: '',
  total_units: '',
  description: '',
  is_active: true,
})

const submitForm = async () => {
  try {
    loading.value = true
    errors.value = {}
    const response = await api.post('/buildings', form)
    console.log(response.data)
    router.push({ name: 'Dashboard' })
  } catch (error) {
    console.error(error)
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors
    } else {
      alert('Failed to create building')
    }
  } finally {
    loading.value = false
  }
}
</script>