import { ref, reactive } from 'vue'
import api from '@/services/api'
import { unwrapApiRecord } from '@/utils/apiResponse'

const emptyDashboard = () => ({
  user: {},
  period: {},
  portfolio: {},
  financials: {},
  collections: { aging_buckets: {}, delinquency: {} },
  operations: {},
  alerts: [],
})

export function useDashboard() {
  const dashboard = ref(emptyDashboard())
  const loading = ref(false)
  const error = ref(null)

  const filters = reactive({
    as_of: new Date().toISOString().split('T')[0],
  })

  async function fetchDashboard(overrides = {}) {
    loading.value = true
    error.value = null
    try {
      const params = {
        as_of: overrides.as_of ?? filters.as_of,
      }
      const { data } = await api.get('/dashboard', { params })
      dashboard.value = unwrapApiRecord(data) ?? emptyDashboard()
      return dashboard.value
    } catch (e) {
      error.value = e
      dashboard.value = emptyDashboard()
      throw e
    } finally {
      loading.value = false
    }
  }

  function formatMoney(value, currency = 'USD') {
    if (value == null || value === '') return '—'
    const amount = Number(value)
    if (Number.isNaN(amount)) return '—'
    return new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(amount)
  }

  function formatPercent(value) {
    if (value == null || value === '') return '—'
    return `${Number(value)}%`
  }

  return {
    dashboard,
    loading,
    error,
    filters,
    fetchDashboard,
    formatMoney,
    formatPercent,
  }
}
