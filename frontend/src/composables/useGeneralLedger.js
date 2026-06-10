import { ref, reactive } from 'vue'
import api from '@/services/api.js'
import { calendarMonthPeriod, localDateString } from '@/utils/localDate.js'

export function useGeneralLedger() {
  const ledger = ref(null)
  const loading = ref(false)
  const error = ref(null)
  const filters = reactive({
    accountId: '',
    from: '',
    to: '',
  })

  function defaultPeriod() {
    return calendarMonthPeriod()
  }

  async function fetchLedger(accountId, { from, to } = {}) {
    if (!accountId) {
      ledger.value = null
      return null
    }

    loading.value = true
    error.value = null
    try {
      const { data } = await api.get(`/accounts/${accountId}/ledger`, {
        params: {
          from: from || undefined,
          to: to || undefined,
        },
      })
      ledger.value = data.data ?? null
      return ledger.value
    } catch (e) {
      error.value = e
      ledger.value = null
      throw e
    } finally {
      loading.value = false
    }
  }

  async function exportCsv(accountId, { from, to } = {}) {
    const response = await api.get(`/accounts/${accountId}/ledger/export`, {
      params: {
        from: from || undefined,
        to: to || undefined,
      },
      responseType: 'blob',
    })

    const accountCode = ledger.value?.account?.code || 'account'
    const fromLabel = from || 'start'
    const toLabel = to || 'end'
    const blob = new Blob([response.data], { type: 'text/csv' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `ledger-${accountCode}-${fromLabel}-${toLabel}.csv`
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
    ledger,
    loading,
    error,
    filters,
    defaultPeriod,
    fetchLedger,
    exportCsv,
    formatMoney,
  }
}
