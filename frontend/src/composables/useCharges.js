import { ref, reactive } from 'vue'
import api from '@/services/api'

export function useCharges() {
  const items = ref([])
  const loading = ref(false)
  const meta = ref({ current_page: 1, last_page: 1, total: 0 })
  const summary = reactive({ pending: 0, approved: 0, cancelled: 0 })
  const companySummary = reactive({
    pending: 0,
    approved_ready: 0,
    invoiced: 0,
    cancelled: 0,
  })

  const filters = reactive({
    search: '',
    status: '',
    category: 'utility',
    meter_reading_id: '',
  })

  async function fetchCompanySummary() {
    try {
      const { data } = await api.get('/charges/summary')
      const s = data.data ?? data
      Object.assign(companySummary, {
        pending: s.pending ?? 0,
        approved_ready: s.approved_ready ?? 0,
        invoiced: s.invoiced ?? 0,
        cancelled: s.cancelled ?? 0,
      })
    } catch {
      /* keep prior */
    }
  }

  async function fetchList(page = 1) {
    loading.value = true
    try {
      const { data } = await api.get('/charges', {
        params: {
          page,
          search: filters.search || undefined,
          status: filters.status || undefined,
          category: filters.category || undefined,
          meter_reading_id: filters.meter_reading_id || undefined,
          per_page: 20,
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
        per_page: m.per_page ?? 20,
      }
      refreshSummary()
      await fetchCompanySummary()
    } finally {
      loading.value = false
    }
  }

  function refreshSummary() {
    summary.pending = items.value.filter((c) => c.status?.value === 'pending').length
    summary.approved = items.value.filter((c) => c.status?.value === 'approved').length
    summary.cancelled = items.value.filter((c) => c.status?.value === 'cancelled').length
  }

  async function approve(charge) {
    const { data } = await api.post(`/charges/${charge.id}/approve`)
    return {
      charge: data.data ?? data,
      message: data.message ?? '',
    }
  }

  async function reject(charge, reason) {
    await api.post(`/charges/${charge.id}/reject`, { reason })
  }

  async function bulkApprove(chargeIds) {
    const { data } = await api.post('/charges/bulk-approve', { charge_ids: chargeIds })
    return {
      result: data.data ?? data,
      message: data.message ?? '',
    }
  }

  function resetFilters() {
    filters.search = ''
    filters.status = ''
    filters.category = 'utility'
    filters.meter_reading_id = ''
  }

  return {
    items,
    loading,
    meta,
    summary,
    companySummary,
    filters,
    fetchList,
    fetchCompanySummary,
    approve,
    reject,
    bulkApprove,
    resetFilters,
  }
}
