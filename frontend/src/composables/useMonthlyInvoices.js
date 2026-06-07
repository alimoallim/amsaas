import { reactive, ref } from 'vue'
import api from '@/services/api'

export function useMonthlyInvoices() {
  const items = ref([])
  const loading = ref(false)
  const issuing = ref(false)
  const error = ref(null)
  const meta = ref({ current_page: 1, last_page: 1, total: 0, per_page: 25 })
  const periodSummary = reactive({
    counts: { draft: 0, issued: 0, partially_paid: 0, paid: 0, total: 0 },
    amounts: { draft: 0, billed: 0, open_balance: 0 },
    needs_attention: 0,
    can_bulk_issue: false,
  })

  const filters = reactive({
    year: new Date().getFullYear(),
    month: new Date().getMonth() + 1,
    view: 'attention',
    status: '',
    building_id: '',
    search: '',
    per_page: 25,
  })

  async function fetchSummary() {
    try {
      const { data } = await api.get('/invoices/summary', {
        params: { year: filters.year, month: filters.month },
      })
      const s = data.data ?? data
      Object.assign(periodSummary, {
        counts: s.counts ?? periodSummary.counts,
        amounts: s.amounts ?? periodSummary.amounts,
        needs_attention: s.needs_attention ?? 0,
        can_bulk_issue: s.can_bulk_issue ?? false,
      })
    } catch {
      /* keep prior */
    }
  }

  async function fetchList(page = 1) {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get('/invoices', {
        params: {
          page,
          year: filters.year,
          month: filters.month,
          view: filters.status ? 'all' : filters.view,
          status: filters.status || undefined,
          building_id: filters.building_id || undefined,
          search: filters.search || undefined,
          per_page: filters.per_page,
        },
      })
      items.value = data.data ?? []
      const m = data.meta ?? {}
      meta.value = {
        current_page: m.current_page ?? 1,
        last_page: m.last_page ?? 1,
        total: m.total ?? items.value.length,
        from: m.from ?? 0,
        to: m.to ?? 0,
        per_page: m.per_page ?? filters.per_page,
      }
      await fetchSummary()
    } catch (e) {
      error.value = e
      throw e
    } finally {
      loading.value = false
    }
  }

  async function fetchOne(id) {
    const { data } = await api.get(`/invoices/${id}`)
    return data.data ?? data
  }

  async function createInvoice(payload) {
    const { data } = await api.post('/invoices', payload)
    return data.data ?? data
  }

  async function issueOne(invoice) {
    const { data } = await api.post(`/invoices/${invoice.id}/finalize`)
    return data.data ?? data
  }

  async function voidInvoice(id, reason) {
    const { data } = await api.post(`/invoices/${id}/void`, { reason })
    return data.data ?? data
  }

  async function downloadPdf(id, filename = 'invoice') {
    const response = await api.get(`/invoices/${id}/download`, { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `${filename}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  }

  /**
   * @param {string[]|null} ids  null = all drafts for period
   */
  async function bulkIssue(ids = null) {
    issuing.value = true
    error.value = null
    try {
      const payload = {
        year: filters.year,
        month: filters.month,
      }
      if (ids?.length) {
        payload.ids = ids
      }
      const { data } = await api.post('/invoices/bulk-issue', payload)
      return data.data ?? data
    } catch (e) {
      error.value = e
      throw e
    } finally {
      issuing.value = false
    }
  }

  return {
    items,
    loading,
    issuing,
    error,
    meta,
    periodSummary,
    filters,
    fetchList,
    fetchSummary,
    fetchOne,
    createInvoice,
    issueOne,
    voidInvoice,
    downloadPdf,
    bulkIssue,
  }
}
