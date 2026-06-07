<template>
  <div class="space-y-6 max-w-5xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-slate-900">Edit Charge Type</h1>
          <p class="mt-1 text-sm text-slate-500">
            Modify underlying rules, status, sorting matrix rules, or general ledger identifiers for this classification model.
          </p>
        </div>
        <RouterLink
          :to="{ name: 'ChargeTypes' }"
          class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition-colors duration-150 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2"
        >
          Back to List
        </RouterLink>
      </div>
    </div>

    <div v-if="successMessage" class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm font-medium text-green-700 shadow-sm transition-all duration-200">
      {{ successMessage }}
    </div>

    <div v-if="serverError" class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-700 shadow-sm transition-all duration-200">
      {{ serverError }}
    </div>

    <div v-if="loading" class="bg-white rounded-xl border border-slate-200 p-12 text-center text-slate-500 shadow-sm">
      <div class="flex items-center justify-center space-x-2">
        <svg class="animate-spin h-6 w-6 text-slate-600" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
        </svg>
        <span class="text-sm font-medium">Unpacking matrix definition indexes out of secure data nodes...</span>
      </div>
    </div>

    <form v-else @submit.prevent="submit" class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden space-y-8">
      
      <div class="p-6 border-b border-slate-100">
        <h2 class="text-base font-semibold text-slate-900 mb-4">1. Identification & Nomenclature</h2>
        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
          <div>
            <label for="name" class="block text-sm font-medium text-slate-700">Official Nomenclature Name <span class="text-red-500">*</span></label>
            <input id="name" type="text" v-model="form.name" placeholder="e.g., Residential Rent" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none" :class="{ 'border-red-300': errors.name }" />
            <p v-if="errors.name" class="mt-1 text-xs text-red-600">{{ errors.name[0] }}</p>
          </div>

          <div>
            <label for="short_name" class="block text-sm font-medium text-slate-700">Abbreviated / Short Name</label>
            <input id="short_name" type="text" v-model="form.short_name" placeholder="e.g., Rent" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none" />
          </div>

          <div>
            <label for="code" class="block text-sm font-medium text-slate-700">System Unique Code Reference <span class="text-red-500">*</span></label>
            <input id="code" type="text" v-model="form.code" placeholder="e.g., RENT_RESIDENTIAL" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm uppercase font-mono bg-slate-100 text-slate-600 focus:outline-none" readonly :class="{ 'border-red-300': errors.code }" />
            <p class="mt-1 text-[11px] text-amber-600 font-medium">⚠️ Core system constraints protect the unique immutable value string against runtime changes.</p>
            <p v-if="errors.code" class="mt-1 text-xs text-red-600">{{ errors.code[0] }}</p>
          </div>
        </div>

        <div class="mt-4">
          <label for="description" class="block text-sm font-medium text-slate-700">Classification Description</label>
          <textarea id="description" rows="2" v-model="form.description" placeholder="Provide system context or structural explanation regarding this allocation ruleset..." class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none"></textarea>
        </div>
      </div>

      <div class="p-6 border-b border-slate-100 bg-slate-50/50">
        <h2 class="text-base font-semibold text-slate-900 mb-4">2. Financial Architecture Enums</h2>
        <div class="grid grid-cols-1 gap-6 md:grid-cols-3 lg:grid-cols-5">
          <div>
            <label for="category" class="block text-sm font-medium text-slate-700">Core Category <span class="text-red-500">*</span></label>
            <select id="category" v-model="form.category" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white focus:border-slate-500 focus:outline-none">
              <option value="rent">Rent Operations</option>
              <option value="utility">Utility Tracking</option>
              <option value="deposit">Deposit Mapping</option>
              <option value="penalty">Penalty Fine Engine</option>
              <option value="service">Service/Amenities</option>
              <option value="tax">Tax Levies</option>
              <option value="discount">Discount Stream</option>
              <option value="adjustment">Adjustments</option>
              <option value="miscellaneous">Miscellaneous</option>
            </select>
          </div>

          <div>
            <label for="billing_behavior" class="block text-sm font-medium text-slate-700">Billing Behavior <span class="text-red-500">*</span></label>
            <select id="billing_behavior" v-model="form.billing_behavior" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white focus:border-slate-500 focus:outline-none">
              <option value="fixed">Fixed Framework</option>
              <option value="variable">Variable Framework</option>
              <option value="metered">Metered Metric</option>
              <option value="percentage">Percentage Allocation</option>
              <option value="tiered">Tiered Structures</option>
              <option value="formula">Dynamic Formula Hooks</option>
            </select>
          </div>

          <div>
            <label for="calculation_method" class="block text-sm font-medium text-slate-700">Calculation Path <span class="text-red-500">*</span></label>
            <select id="calculation_method" v-model="form.calculation_method" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white focus:border-slate-500 focus:outline-none">
              <option value="fixed_amount">Fixed Abs Amount</option>
              <option value="per_unit">Per Measurement Unit</option>
              <option value="percentage">Percentage Rule</option>
              <option value="formula">Dynamic Formula Profile</option>
              <option value="tiered">Tiered Bounds Matrix</option>
            </select>
          </div>

          <div>
            <label for="billing_frequency" class="block text-sm font-medium text-slate-700">Cycle Frequency <span class="text-red-500">*</span></label>
            <select id="billing_frequency" v-model="form.billing_frequency" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white focus:border-slate-500 focus:outline-none">
              <option value="one_time">One-Time Event</option>
              <option value="daily">Daily Sweeps</option>
              <option value="weekly">Weekly Runs</option>
              <option value="monthly">Monthly Cycle Run</option>
              <option value="quarterly">Quarterly Cycle</option>
              <option value="yearly">Yearly Base Run</option>
            </select>
          </div>

          <div>
            <label for="financial_classification" class="block text-sm font-medium text-slate-700">Ledger Classification <span class="text-red-500">*</span></label>
            <select id="financial_classification" v-model="form.financial_classification" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white focus:border-slate-500 focus:outline-none">
              <option value="income">Income Stream</option>
              <option value="liability">Liability Hold</option>
              <option value="expense">Expense Clearing</option>
              <option value="contra_revenue">Contra-Revenue Map</option>
            </select>
          </div>
        </div>
      </div>

      <div class="p-6 border-b border-slate-100">
        <h2 class="text-base font-semibold text-slate-900 mb-4">3. Price Matrix Defaults & General Ledger Integration</h2>
        <div class="grid grid-cols-1 gap-6 md:grid-cols-5">
          <div>
            <label for="default_currency" class="block text-sm font-medium text-slate-700">Operating Currency</label>
            <input id="default_currency" type="text" v-model="form.default_currency" placeholder="USD" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none" />
          </div>

          <div class="md:col-span-2">
            <label for="default_amount" class="block text-sm font-medium text-slate-700">Default Absolute Amount</label>
            <input id="default_amount" type="number" step="0.01" v-model.number="form.default_amount" placeholder="0.00" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none" />
          </div>

          <div class="md:col-span-2">
            <label for="default_percentage" class="block text-sm font-medium text-slate-700">Default Calculation Percentage (%)</label>
            <input id="default_percentage" type="number" step="0.0001" v-model.number="form.default_percentage" placeholder="0.0000" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none" />
          </div>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-3 mt-6">
          <div class="md:col-span-2">
            <label for="ledger_account_code" class="block text-sm font-medium text-slate-700">Double-Entry Ledger Chart Account Code</label>
            <input id="ledger_account_code" type="text" v-model="form.ledger_account_code" placeholder="e.g., 4000-INC-RENT" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono focus:border-slate-500 focus:outline-none" />
          </div>

          <div>
            <label for="sort_order" class="block text-sm font-medium text-slate-700">Presentation Sort Weighting</label>
            <input id="sort_order" type="number" v-model.number="form.sort_order" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none" />
          </div>
        </div>
      </div>

      <div class="p-6 bg-slate-50/50 border-b border-slate-100">
        <h2 class="text-base font-semibold text-slate-900 mb-4">4. Core Invoicing Rules Engine Configuration Flags</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          
          <div class="flex items-start p-4 rounded-lg border border-slate-200 bg-white shadow-sm">
            <input id="is_recurring" type="checkbox" v-model="form.is_recurring" class="h-4 w-4 rounded border-slate-300 text-slate-600 mt-0.5" />
            <label for="is_recurring" class="ml-3 text-sm"><span class="block font-medium text-slate-700">Is Recurring</span><span class="text-xs text-slate-400">Triggers repetitive billing generation runs.</span></label>
          </div>

          <div class="flex items-start p-4 rounded-lg border border-slate-200 bg-white shadow-sm">
            <input id="is_metered" type="checkbox" v-model="form.is_metered" class="h-4 w-4 rounded border-slate-300 text-slate-600 mt-0.5" />
            <label for="is_metered" class="ml-3 text-sm"><span class="block font-medium text-slate-700">Is Metered Consumption</span><span class="text-xs text-slate-400">Calculates value linked to equipment meters.</span></label>
          </div>

          <div class="flex items-start p-4 rounded-lg border border-slate-200 bg-white shadow-sm">
            <input id="requires_meter_reading" type="checkbox" v-model="form.requires_meter_reading" class="h-4 w-4 rounded border-slate-300 text-slate-600 mt-0.5" />
            <label for="requires_meter_reading" class="ml-3 text-sm"><span class="block font-medium text-slate-700">Requires Meter Reading Entry</span><span class="text-xs text-slate-400">Blocks billing until entry transaction verified.</span></label>
          </div>

          <div class="flex items-start p-4 rounded-lg border border-slate-200 bg-white shadow-sm">
            <input id="is_taxable" type="checkbox" v-model="form.is_taxable" class="h-4 w-4 rounded border-slate-300 text-slate-600 mt-0.5" />
            <label for="is_taxable" class="ml-3 text-sm"><span class="block font-medium text-slate-700">Is Taxable</span><span class="text-xs text-slate-400">Applies company transactional tax rules.</span></label>
          </div>

          <div class="flex items-start p-4 rounded-lg border border-slate-200 bg-white shadow-sm">
            <input id="is_refundable" type="checkbox" v-model="form.is_refundable" class="h-4 w-4 rounded border-slate-300 text-slate-600 mt-0.5" />
            <label for="is_refundable" class="ml-3 text-sm"><span class="block font-medium text-slate-700">Is Refundable Escrow</span><span class="text-xs text-slate-400">Tracked for terminal move-out balance returns.</span></label>
          </div>

          <div class="flex items-start p-4 rounded-lg border border-slate-200 bg-white shadow-sm">
            <input id="allow_manual_override" type="checkbox" v-model="form.allow_manual_override" class="h-4 w-4 rounded border-slate-300 text-slate-600 mt-0.5" />
            <label for="allow_manual_override" class="ml-3 text-sm"><span class="block font-medium text-slate-700">Allow Manual Modification</span><span class="text-xs text-slate-400">Permits operators to overwrite unit pricing manually.</span></label>
          </div>

          <div class="flex items-start p-4 rounded-lg border border-slate-200 bg-white shadow-sm">
            <input id="allow_proration" type="checkbox" v-model="form.allow_proration" class="h-4 w-4 rounded border-slate-300 text-slate-600 mt-0.5" />
            <label for="allow_proration" class="ml-3 text-sm"><span class="block font-medium text-slate-700">Allow Calendar Proration</span><span class="text-xs text-slate-400">Calculates day-rate splits for mid-cycle events.</span></label>
          </div>

          <div class="flex items-start p-4 rounded-lg border border-slate-200 bg-white shadow-sm">
            <input id="allow_discount" type="checkbox" v-model="form.allow_discount" class="h-4 w-4 rounded border-slate-300 text-slate-600 mt-0.5" />
            <label for="allow_discount" class="ml-3 text-sm"><span class="block font-medium text-slate-700">Allow Discount Markdown</span><span class="text-xs text-slate-400">Permits application of promotional balance credits.</span></label>
          </div>

          <div class="flex items-start p-4 rounded-lg border border-slate-200 bg-white shadow-sm">
            <input id="allow_penalty" type="checkbox" v-model="form.allow_penalty" class="h-4 w-4 rounded border-slate-300 text-slate-600 mt-0.5" />
            <label for="allow_penalty" class="ml-3 text-sm"><span class="block font-medium text-slate-700">Allow Late Fines Escalation</span><span class="text-xs text-slate-400">Triggers automation processing for overdue flags.</span></label>
          </div>

          <div class="flex items-start p-4 rounded-lg border border-slate-200 bg-white shadow-sm">
            <input id="allow_adjustment" type="checkbox" v-model="form.allow_adjustment" class="h-4 w-4 rounded border-slate-300 text-slate-600 mt-0.5" />
            <label for="allow_adjustment" class="ml-3 text-sm"><span class="block font-medium text-slate-700">Allow Balancing Adjustment</span><span class="text-xs text-slate-400">Accepts credit/debit adjustment notes post invoice.</span></label>
          </div>

          <div class="flex items-start p-4 rounded-lg border border-slate-200 bg-white shadow-sm">
            <input id="auto_generate" type="checkbox" v-model="form.auto_generate" class="h-4 w-4 rounded border-slate-300 text-slate-600 mt-0.5" />
            <label for="auto_generate" class="ml-3 text-sm"><span class="block font-medium text-slate-700">Auto-Generate System Invoice</span><span class="text-xs text-slate-400">Cron queue services run this run automatically.</span></label>
          </div>

          <div class="flex items-start p-4 rounded-lg border border-slate-200 bg-white shadow-sm">
            <input id="affects_occupancy" type="checkbox" v-model="form.affects_occupancy" class="h-4 w-4 rounded border-slate-300 text-slate-600 mt-0.5" />
            <label for="affects_occupancy" class="ml-3 text-sm"><span class="block font-medium text-slate-700">Affects Lease Occupancy Metrics</span><span class="text-xs text-slate-400">Factors execution rules into occupancy models.</span></label>
          </div>

        </div>
      </div>

      <div class="p-6">
        <div class="max-w-xs">
          <label for="status" class="block text-sm font-medium text-slate-700">System Visibility Status <span class="text-red-500">*</span></label>
          <select id="status" v-model="form.status" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white focus:border-slate-500 focus:outline-none">
            <option value="active">Active (Deployable and Searchable)</option>
            <option value="inactive">Inactive (Suspended on Run Architectures)</option>
            <option value="archived">Archived (Locked Immutable History Record)</option>
          </select>
        </div>
      </div>

      <div class="bg-slate-50 px-6 py-4 flex items-center justify-end space-x-3 border-t border-slate-200">
        <button type="button" @click="cancel" :disabled="submitting" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50">Cancel</button>
        <button type="submit" :disabled="submitting" class="inline-flex justify-center rounded-lg border border-transparent bg-slate-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-slate-800 focus:outline-none disabled:opacity-50">
          <svg v-if="submitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" /></svg>
          {{ submitting ? 'Persisting Configuration Changes...' : 'Save Changes' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import { useRouter, useRoute, RouterLink } from 'vue-router'
import api from '@/services/api'

const router = useRouter()
const route = useRoute()

const loading = ref(true)
const submitting = ref(false)
const successMessage = ref('')
const serverError = ref('')
const errors = ref({})

const chargeTypeId = route.params.id

const form = reactive({
  name: '',
  short_name: '',
  code: '',
  description: '',
  category: 'rent',
  billing_behavior: 'fixed',
  calculation_method: 'fixed_amount',
  billing_frequency: 'monthly',
  financial_classification: 'income',
  default_currency: 'USD',
  default_amount: null,
  default_percentage: null,
  is_recurring: false,
  is_metered: false,
  requires_meter_reading: false,
  is_taxable: false,
  is_refundable: false,
  allow_manual_override: true,
  allow_proration: false,
  allow_discount: false,
  allow_penalty: false,
  allow_adjustment: true,
  auto_generate: false,
  affects_occupancy: false,
  ledger_account_code: '',
  sort_order: 0,
  status: 'active'
})

async function loadChargeType() {
  loading.value = true
  serverError.value = ''
  
  try {
    const response = await api.get(`/charge-types/${chargeTypeId}`)
    const record = response.data.data

    if (record) {
      form.name = record.name || ''
      form.short_name = record.short_name || ''
      form.code = record.code || ''
      form.description = record.description || ''
      form.category = record.category || 'rent'
      form.billing_behavior = record.billing_behavior || 'fixed'
      form.calculation_method = record.calculation_method || 'fixed_amount'
      form.billing_frequency = record.billing_frequency || 'monthly'
      form.financial_classification = record.financial_classification || 'income'
      form.default_currency = record.default_currency || 'USD'
      form.default_amount = record.default_amount !== null ? Number(record.default_amount) : null
      form.default_percentage = record.default_percentage !== null ? Number(record.default_percentage) : null
      form.ledger_account_code = record.ledger_account_code || ''
      form.sort_order = record.sort_order !== undefined ? Number(record.sort_order) : 0
      form.status = record.status || 'active'
      
      // Explicitly evaluate structural booleans
      form.is_recurring = !!record.is_recurring
      form.is_metered = !!record.is_metered
      form.requires_meter_reading = !!record.requires_meter_reading
      form.is_taxable = !!record.is_taxable
      form.is_refundable = !!record.is_refundable
      form.allow_manual_override = record.allow_manual_override !== undefined ? !!record.allow_manual_override : true
      form.allow_proration = !!record.allow_proration
      form.allow_discount = !!record.allow_discount
      form.allow_penalty = !!record.allow_penalty
      form.allow_adjustment = record.allow_adjustment !== undefined ? !!record.allow_adjustment : true
      form.auto_generate = !!record.auto_generate
      form.affects_occupancy = !!record.affects_occupancy
    }
  } catch (error) {
    console.error('Failed to parse database configuration dictionary:', error)
    serverError.value = 'Failed to locate the requested charge configuration model.'
  } finally {
    loading.value = false
  }
}

async function submit() {
  submitting.value = true
  errors.value = {}
  serverError.value = ''
  successMessage.value = ''

  try {
    const payload = {
      ...form,
      default_amount: form.default_amount === '' || form.default_amount === null ? null : Number(form.default_amount),
      default_percentage: form.default_percentage === '' || form.default_percentage === null ? null : Number(form.default_percentage),
      sort_order: Number(form.sort_order || 0)
    }

    await api.put(`/charge-types/${chargeTypeId}`, payload)
    successMessage.value = `Charge Type configuration has been updated successfully.`
    
    setTimeout(() => {
      router.push({ name: 'ChargeTypes' })
    }, 1250)
  } catch (error) {
    console.error('Persistence validation reject captured:', error)
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
      serverError.value = 'Validation failure. Review form inputs.'
      return
    }
    serverError.value = error.response?.data?.message || 'Failed to update remote model context.'
  } finally {
    submitting.value = false
  }
}

function cancel() {
  router.push({ name: 'ChargeTypes' })
}

onMounted(() => {
  if (chargeTypeId) {
    loadChargeType()
  } else {
    serverError.value = 'Route state is missing resource UUID parameter constraint token.'
    loading.value = false
  }
})
</script>