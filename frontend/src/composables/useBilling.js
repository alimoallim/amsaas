import { reactive, ref } from 'vue'
import api from '@/services/api.js'

export function useBilling() {
  const loading = ref(false)
  const processing = ref(false)
  const error = ref(null)
  const summary = reactive({
    metrics: {
      fixed_items_count: 0,
      fixed_items_revenue: 0,
      utility_items_count: 0,
      utility_items_revenue: 0,
      total_pending_rows: 0,
      estimated_total: 0,
      active_rental_agreements: 0,
      billable_agreement_charges: 0,
      agreements_missing_rent_charge: 0,
      has_rent_charge_model: true,
      can_compile: false,
    },
  })
  const lastRunResult = ref(null)
  const period = reactive({
    month: new Date().getMonth() + 1,
    year: new Date().getFullYear(),
  })

  const pipeline = ref(null)

  async function fetchSummary() {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get('/billing/summary', {
        params: { year: period.year, month: period.month },
      })
      Object.assign(summary, data)
      pipeline.value = data.pipeline ?? null
      if (data.metrics) {
        summary.metrics = data.metrics
      }
    } catch (e) {
      error.value = e
      throw e
    } finally {
      loading.value = false
    }
  }

  async function fetchPipelineStatus() {
    const { data } = await api.get('/billing/pipeline-status', {
      params: { year: period.year, month: period.month },
    })
    pipeline.value = data.data ?? data
    return pipeline.value
  }

  async function runConsolidation({ generateRecurring = true } = {}) {
    processing.value = true
    error.value = null
    lastRunResult.value = null
    try {
      const { data } = await api.post('/billing/generate', {
        year: period.year,
        month: period.month,
        generate_recurring: generateRecurring,
      })
      lastRunResult.value = data
      await fetchSummary()
      return data
    } catch (e) {
      error.value = e
      throw e
    } finally {
      processing.value = false
    }
  }

  return {
    loading,
    processing,
    error,
    summary,
    pipeline,
    lastRunResult,
    period,
    fetchSummary,
    fetchPipelineStatus,
    runConsolidation,
  }
}
