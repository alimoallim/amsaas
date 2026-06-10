import { ref, reactive } from 'vue'
import api from '@/services/api.js'
import { calendarMonthPeriod } from '@/utils/localDate.js'

export function useIncomeStatement() {
  const report = ref(null)
  const loading = ref(false)
  const error = ref(null)
  const filters = reactive({
    from: '',
    to: '',
    billingYear: new Date().getFullYear(),
    billingMonth: new Date().getMonth() + 1,
  })

  function defaultPeriod() {
    const period = calendarMonthPeriod()
    const now = new Date()
    return {
      ...period,
      billingYear: now.getFullYear(),
      billingMonth: now.getMonth() + 1,
    }
  }

  async function fetchReport({ from, to, billingYear, billingMonth } = {}) {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get('/income-statement', {
        params: {
          from: from || undefined,
          to: to || undefined,
          billing_year: billingYear || undefined,
          billing_month: billingMonth || undefined,
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

  async function exportCsv({ from, to, billingYear, billingMonth } = {}) {
    const response = await api.get('/income-statement/export', {
      params: {
        from: from || undefined,
        to: to || undefined,
        billing_year: billingYear || undefined,
        billing_month: billingMonth || undefined,
      },
      responseType: 'blob',
    })

    const fromLabel = from || 'start'
    const toLabel = to || 'end'
    const blob = new Blob([response.data], { type: 'text/csv' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `income-statement-${fromLabel}-${toLabel}.csv`
    link.click()
    URL.revokeObjectURL(url)
  }

  async function exportPdf({ from, to, billingYear, billingMonth } = {}) {
    const response = await api.get('/income-statement/export-pdf', {
      params: {
        from: from || undefined,
        to: to || undefined,
        billing_year: billingYear || undefined,
        billing_month: billingMonth || undefined,
      },
      responseType: 'blob',
    })

    const fromLabel = from || 'start'
    const toLabel = to || 'end'
    const blob = new Blob([response.data], { type: 'application/pdf' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `income-statement-${fromLabel}-${toLabel}.pdf`
    link.click()
    URL.revokeObjectURL(url)
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
    error,
    filters,
    defaultPeriod,
    fetchReport,
    exportCsv,
    exportPdf,
    formatMoney,
  }
}
