import { ref, reactive } from 'vue'
import api from '@/services/api.js'
import { calendarMonthPeriod } from '@/utils/localDate.js'

export function useFinancialAudit() {
  const rows = ref([])
  const meta = ref({ total: 0, current_page: 1, last_page: 1 })
  const loading = ref(false)
  const error = ref(null)
  const filters = reactive({
    from: '',
    to: '',
    entityType: '',
    action: '',
    page: 1,
  })

  function defaultPeriod() {
    return calendarMonthPeriod()
  }

  async function fetchTimeline({ from, to, entityType, action, page } = {}) {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get('/financial-audit', {
        params: {
          from: from || undefined,
          to: to || undefined,
          entity_type: entityType || undefined,
          action: action || undefined,
          page: page || 1,
          per_page: 25,
        },
      })
      rows.value = data.data ?? []
      meta.value = data.meta ?? meta.value
      return rows.value
    } catch (e) {
      error.value = e
      rows.value = []
      throw e
    } finally {
      loading.value = false
    }
  }

  async function exportCsv({ from, to, entityType, action } = {}) {
    const response = await api.get('/financial-audit/export', {
      params: {
        from: from || undefined,
        to: to || undefined,
        entity_type: entityType || undefined,
        action: action || undefined,
      },
      responseType: 'blob',
    })

    const fromLabel = from || 'start'
    const toLabel = to || 'end'
    const blob = new Blob([response.data], { type: 'text/csv' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `financial-audit-${fromLabel}-${toLabel}.csv`
    link.click()
    URL.revokeObjectURL(url)
  }

  function formatTimestamp(iso) {
    if (!iso) return '—'
    try {
      return new Date(iso).toLocaleString()
    } catch {
      return iso
    }
  }

  return {
    rows,
    meta,
    loading,
    error,
    filters,
    defaultPeriod,
    fetchTimeline,
    exportCsv,
    formatTimestamp,
  }
}
