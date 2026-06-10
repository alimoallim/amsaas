import { ref, reactive } from 'vue'
import api from '@/services/api.js'
import { calendarMonthPeriod } from '@/utils/localDate.js'

export function useTrialBalance() {
  const report = ref(null)
  const loading = ref(false)
  const closing = ref(false)
  const error = ref(null)
  const filters = reactive({
    from: '',
    to: '',
  })

  function defaultPeriod() {
    return calendarMonthPeriod()
  }

  async function fetchReport({ from, to } = {}) {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get('/trial-balance', {
        params: {
          from: from || undefined,
          to: to || undefined,
        },
      })
      report.value = data.data ?? null
      return report.value
    } catch (e) {
      error.value = e
      report.value = null
      throw e
    } finally {
      loading.value = false
    }
  }

  async function exportCsv({ from, to } = {}) {
    const response = await api.get('/trial-balance/export', {
      params: {
        from: from || undefined,
        to: to || undefined,
      },
      responseType: 'blob',
    })

    const fromLabel = from || 'start'
    const toLabel = to || 'end'
    const blob = new Blob([response.data], { type: 'text/csv' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `trial-balance-${fromLabel}-${toLabel}.csv`
    link.click()
    URL.revokeObjectURL(url)
  }

  async function closePeriod({ fiscalYear, fiscalMonth, notes } = {}) {
    closing.value = true
    try {
      const { data } = await api.post('/trial-balance/close-period', {
        fiscal_year: fiscalYear,
        fiscal_month: fiscalMonth,
        notes: notes || undefined,
      })
      report.value = data.data?.report ?? report.value
      return data.data
    } finally {
      closing.value = false
    }
  }

  function formatMoney(value) {
    if (value == null || Number.isNaN(Number(value))) return '—'
    return new Intl.NumberFormat(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(Number(value))
  }

  return {
    report,
    loading,
    closing,
    error,
    filters,
    defaultPeriod,
    fetchReport,
    exportCsv,
    closePeriod,
    formatMoney,
  }
}
