import { ref, reactive, computed } from 'vue'
import api from '@/services/api'

export function useMeterReadings() {
  const items = ref([])
  const loading = ref(false)
  const meta = ref({ current_page: 1, last_page: 1, total: 0, from: 0, to: 0, per_page: 15 })
  const filters = reactive({
    search: '',
    status: '',
    utility_type: '',
    anomalies_only: '',
  })

  const summary = computed(() => {
    const list = items.value
    const status = (r) => r.status?.value ?? r.status
    return {
      total: meta.value.total || list.length,
      approved: list.filter((r) => status(r) === 'approved').length,
      pending: list.filter((r) => status(r) !== 'approved').length,
      anomalies: list.filter((r) => r.anomaly?.detected).length,
    }
  })

  async function fetchList(page = 1) {
    loading.value = true
    try {
      const { data } = await api.get('/meter-readings', {
        params: {
          page,
          search: filters.search || undefined,
          status: filters.status || undefined,
          utility_type: filters.utility_type || undefined,
          anomalies_only: filters.anomalies_only === '1' ? true : undefined,
        },
      })
      items.value = data.data || []
      const m = data.meta || {}
      meta.value = {
        current_page: m.current_page || 1,
        last_page: m.last_page || 1,
        total: m.total ?? items.value.length,
        from: ((m.current_page || 1) - 1) * (m.per_page || 15) + 1,
        to: Math.min((m.current_page || 1) * (m.per_page || 15), m.total || 0),
        per_page: m.per_page || 15,
      }
    } finally {
      loading.value = false
    }
  }

  async function approve(reading) {
    await api.post(`/meter-readings/${reading.id}/approve`)
    await fetchList(meta.value.current_page)
  }

  async function reject(reading, reason) {
    await api.post(`/meter-readings/${reading.id}/reject`, { reason })
    await fetchList(meta.value.current_page)
  }

  function resetFilters() {
    filters.search = ''
    filters.status = ''
    filters.utility_type = ''
    filters.anomalies_only = ''
  }

  return {
    items,
    loading,
    meta,
    filters,
    summary,
    fetchList,
    approve,
    reject,
    resetFilters,
  }
}
