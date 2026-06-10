import { ref, reactive } from 'vue'
import api from '@/services/api'
import { unwrapApiList, unwrapApiMeta, unwrapApiRecord } from '@/utils/apiResponse'

export function useCharges() {
  const items = ref([])
  const loading = ref(false)
  const error = ref(null)
  const meta = ref({ current_page: 1, last_page: 1, total: 0, per_page: 20 })
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
      const s = unwrapApiRecord(data)
      Object.assign(companySummary, {
        pending: s.pending ?? 0,
        approved_ready: s.approved_ready ?? 0,
        invoiced: s.invoiced ?? 0,
        cancelled: s.cancelled ?? 0,
      })
    } catch (e) {
      console.error('charges/summary failed', e)
    }
  }

  async function fetchList(page = 1) {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get('/charges', {
        params: {
          page,
          search: filters.search?.trim() || undefined,
          status: filters.status || undefined,
          category: filters.category || 'utility',
          meter_reading_id: filters.meter_reading_id || undefined,
          per_page: 20,
        },
      })

      const rows = unwrapApiList(data)
      items.value = rows
      const m = unwrapApiMeta(data, {
        current_page: page,
        last_page: 1,
        total: rows.length,
        per_page: 20,
      })
      meta.value = {
        current_page: m.current_page ?? page,
        last_page: m.last_page ?? 1,
        total: m.total ?? rows.length,
        from: m.from ?? (rows.length ? 1 : 0),
        to: m.to ?? rows.length,
        per_page: m.per_page ?? 20,
      }
      refreshSummary()
      await fetchCompanySummary()
    } catch (e) {
      error.value = e
      items.value = []
      throw e
    } finally {
      loading.value = false
    }
  }

  function refreshSummary() {
    summary.pending = items.value.filter((c) => chargeStatus(c) === 'pending').length
    summary.approved = items.value.filter((c) => chargeStatus(c) === 'approved').length
    summary.cancelled = items.value.filter((c) => chargeStatus(c) === 'cancelled').length
  }

  function chargeStatus(charge) {
    return charge?.status?.value ?? charge?.status ?? ''
  }

  async function approve(charge) {
    const { data } = await api.post(`/charges/${charge.id}/approve`)
    return {
      charge: unwrapApiRecord(data),
      message: data.message ?? '',
    }
  }

  async function reject(charge, reason) {
    await api.post(`/charges/${charge.id}/reject`, { reason })
  }

  async function bulkApprove(chargeIds) {
    const { data } = await api.post('/charges/bulk-approve', { charge_ids: chargeIds })
    return {
      result: unwrapApiRecord(data),
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
    error,
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
    chargeStatus,
  }
}
