<template>
  <div v-if="meter" class="min-h-screen bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-slate-50 via-slate-100 to-slate-50 dark:from-slate-900 dark:via-slate-950 dark:to-slate-900 py-8 px-4 sm:px-6 lg:px-8 font-sans transition-colors duration-300">
    <div class="mx-auto max-w-5xl space-y-6">
      
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white flex items-center gap-3">
            {{ meter.meter_number }}
            <span 
              class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium ring-1 ring-inset"
              :class="statusColors[meter.status.value]"
            >
              {{ meter.status.label }}
            </span>
          </h1>
          <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            {{ meter.utility_type.label }} Meter &bull; {{ meter.meter_type.label }}
          </p>
        </div>
      </div>

      <div class="overflow-hidden rounded-2xl bg-white dark:bg-slate-900 shadow-sm ring-1 ring-slate-200 dark:ring-slate-800">
        <div class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 px-6 py-5 flex items-center gap-2">
           <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          <h2 class="text-lg font-semibold leading-6 text-slate-900 dark:text-white">Operational Controls</h2>
        </div>
        
        <div class="p-6">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            
            <button 
              v-if="meter.controls.can_activate"
              @click="triggerAction('activate')"
              :disabled="processing"
              class="relative flex items-center justify-center gap-2 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 px-4 py-3 text-sm font-semibold text-emerald-700 dark:text-emerald-400 ring-1 ring-inset ring-emerald-600/20 hover:bg-emerald-100 dark:hover:bg-emerald-500/20 transition-colors"
            >
              Activate Meter
            </button>

            <button 
              v-if="!meter.status.is_faulty && !meter.status.is_decommissioned"
              @click="openModal('faulty', 'Mark as Faulty', 'Please describe the fault or error observed:')"
              :disabled="processing"
              class="relative flex items-center justify-center gap-2 rounded-xl bg-amber-50 dark:bg-amber-500/10 px-4 py-3 text-sm font-semibold text-amber-700 dark:text-amber-400 ring-1 ring-inset ring-amber-600/20 hover:bg-amber-100 dark:hover:bg-amber-500/20 transition-colors"
            >
              Report Fault
            </button>

            <button 
              v-if="meter.status.value !== 'under_maintenance' && !meter.status.is_decommissioned"
              @click="openModal('maintenance', 'Start Maintenance', 'Reason for scheduled or unscheduled maintenance:')"
              :disabled="processing"
              class="relative flex items-center justify-center gap-2 rounded-xl bg-blue-50 dark:bg-blue-500/10 px-4 py-3 text-sm font-semibold text-blue-700 dark:text-blue-400 ring-1 ring-inset ring-blue-600/20 hover:bg-blue-100 dark:hover:bg-blue-500/20 transition-colors"
            >
              Start Maintenance
            </button>

            <button 
              v-if="meter.status.value === 'under_maintenance'"
              @click="openModal('maintenance/complete', 'Complete Maintenance', 'Resolution notes or actions taken:', 'note')"
              :disabled="processing"
              class="relative flex items-center justify-center gap-2 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 px-4 py-3 text-sm font-semibold text-indigo-700 dark:text-indigo-400 ring-1 ring-inset ring-indigo-600/20 hover:bg-indigo-100 dark:hover:bg-indigo-500/20 transition-colors"
            >
              Resolve Maintenance
            </button>

            <button 
              v-if="!meter.status.is_decommissioned"
              @click="openModal('inspection/complete', 'Log Inspection', 'Inspection notes or findings:', 'note')"
              :disabled="processing"
              class="relative flex items-center justify-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-500/10 px-4 py-3 text-sm font-semibold text-slate-700 dark:text-slate-300 ring-1 ring-inset ring-slate-600/20 hover:bg-slate-100 dark:hover:bg-slate-500/20 transition-colors"
            >
              Log Inspection
            </button>

            <button 
              v-if="meter.controls.can_decommission"
              @click="openModal('decommission', 'Decommission Meter', 'Reason for decommissioning (End of life, replaced, etc.):')"
              :disabled="processing"
              class="relative flex items-center justify-center gap-2 rounded-xl bg-rose-50 dark:bg-rose-500/10 px-4 py-3 text-sm font-semibold text-rose-700 dark:text-rose-400 ring-1 ring-inset ring-rose-600/20 hover:bg-rose-100 dark:hover:bg-rose-500/20 transition-colors"
            >
              Decommission
            </button>

          </div>
        </div>
      </div>

      <div v-if="modal.isOpen" class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm transition-opacity"></div>
        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
          <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-slate-900 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg ring-1 ring-slate-200 dark:ring-slate-800">
              <div class="px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                <h3 class="text-lg font-semibold leading-6 text-slate-900 dark:text-white" id="modal-title">
                  {{ modal.title }}
                </h3>
                <div class="mt-4">
                  <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    {{ modal.promptLabel }}
                  </label>
                  <textarea 
                    v-model="modal.inputValue" 
                    rows="3" 
                    class="block w-full rounded-xl border-0 py-3 px-4 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 dark:bg-slate-800/50 dark:text-white dark:ring-slate-700 transition-shadow" 
                    placeholder="Enter details..."
                    required
                  ></textarea>
                </div>
              </div>
              <div class="bg-slate-50 dark:bg-slate-800/50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                <button 
                  type="button" 
                  @click="submitModalAction"
                  :disabled="processing || !modal.inputValue.trim()"
                  class="inline-flex w-full justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50 sm:ml-3 sm:w-auto transition-colors"
                >
                  {{ processing ? 'Processing...' : 'Confirm' }}
                </button>
                <button 
                  type="button" 
                  @click="closeModal"
                  :disabled="processing"
                  class="mt-3 inline-flex w-full justify-center rounded-xl bg-white dark:bg-slate-800 px-5 py-2.5 text-sm font-semibold text-slate-900 dark:text-slate-200 shadow-sm ring-1 ring-inset ring-slate-300 dark:ring-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700 sm:mt-0 sm:w-auto transition-colors"
                >
                  Cancel
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/services/api' // Adjust to your Axios instance

const route = useRoute()
const meter = ref(null)
const processing = ref(false)

// Unified Modal State
const modal = ref({
  isOpen: false,
  endpoint: '',
  title: '',
  promptLabel: '',
  payloadKey: 'reason', // some endpoints expect 'reason', some 'note'
  inputValue: ''
})

// Dynamic Status Colors for the Badge
const statusColors = {
  active: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/20',
  inactive: 'bg-slate-50 text-slate-700 ring-slate-600/20 dark:bg-slate-500/10 dark:text-slate-400 dark:ring-slate-500/20',
  faulty: 'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-500/10 dark:text-rose-400 dark:ring-rose-500/20',
  under_maintenance: 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-500/10 dark:text-blue-400 dark:ring-blue-500/20',
  decommissioned: 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/20',
}

const fetchMeter = async () => {
  try {
    const { data } = await api.get(`/meters/${route.params.id}`)
    meter.value = data.data
  } catch (error) {
    console.error('Error fetching meter data:', error)
  }
}

// Opens the modal for actions requiring input (faulty, maintenance, decommission, inspection)
const openModal = (endpoint, title, promptLabel, payloadKey = 'reason') => {
  modal.value = {
    isOpen: true,
    endpoint,
    title,
    promptLabel,
    payloadKey,
    inputValue: ''
  }
}

const closeModal = () => {
  modal.value.isOpen = false
}

// Submits actions that require modal input
const submitModalAction = async () => {
  const payload = {
    [modal.value.payloadKey]: modal.value.inputValue
  }
  await triggerAction(modal.value.endpoint, payload)
  closeModal()
}

// Core function to hit the lifecycle routes you provided
const triggerAction = async (endpointSuffix, payload = {}) => {
  processing.value = true
  try {
    // Hits routes like: POST /api/v1/meters/{id}/faulty
    const { data } = await api.post(`/meters/${meter.value.id}/${endpointSuffix}`, payload)
    
    // The backend returns the updated resource, simply swap it out!
    meter.value = data.data
    
    // Optional: Trigger a success toast notification here
  } catch (error) {
    console.error(`Error executing ${endpointSuffix}:`, error)
    // Optional: Trigger an error toast notification here
  } finally {
    processing.value = false
  }
}

onMounted(() => {
  fetchMeter()
})
</script>