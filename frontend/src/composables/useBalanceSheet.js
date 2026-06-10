import { ref, reactive } from 'vue'
import api from '@/services/api.js'
import { localDateString } from '@/utils/localDate.js'

export function useBalanceSheet() {
  const report = ref(null)
  const loading = ref(false)
  const error = ref(null)
  const filters = reactive({
    asOf: localDateString(),
  })

  function defaultAsOf() {
    return localDateString()
  }

  async function fetchReport({ asOf } = {}) {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get('/balance-sheet', {
        params: {
          as_of: asOf || undefined,
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

  async function exportCsv({ asOf } = {}) {
    const response = await api.get('/balance-sheet/export', {
      params: { as_of: asOf || undefined },
      responseType: 'blob',
    })

    const label = asOf || 'today'
    const blob = new Blob([response.data], { type: 'text/csv' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `balance-sheet-${label}.csv`
    link.click()
    URL.revokeObjectURL(url)
  }

  async function exportPdf({ asOf } = {}) {
    const response = await api.get('/balance-sheet/export-pdf', {
      params: { as_of: asOf || undefined },
      responseType: 'blob',
    })

    const label = asOf || 'today'
    const blob = new Blob([response.data], { type: 'application/pdf' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `balance-sheet-${label}.pdf`
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
    defaultAsOf,
    fetchReport,
    exportCsv,
    exportPdf,
    formatMoney,
  }
}
