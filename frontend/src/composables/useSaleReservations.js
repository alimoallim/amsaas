import { ref, reactive, computed } from 'vue'
import api from '@/services/api'

export function useSaleReservations() {
  const items = ref([])
  const loading = ref(false)
  const meta = ref({ total: 0, current_page: 1, last_page: 1 })
  const filters = reactive({ search: '', status: '' })

  const summary = computed(() => {
    const list = items.value
    return {
      total: meta.value.total || list.length,
      pending: list.filter((r) => r.status === 'pending_deposit').length,
      confirmed: list.filter((r) => r.status === 'confirmed').length,
      expired: list.filter((r) => r.status === 'expired').length,
    }
  })

  async function fetchList(page = 1) {
    loading.value = true
    try {
      const { data } = await api.get('/sale-reservations', {
        params: {
          page,
          search: filters.search || undefined,
          status: filters.status || undefined,
        },
      })
      items.value = data.data || []
      const m = data.meta || {}
      meta.value = {
        current_page: m.current_page || 1,
        last_page: m.last_page || 1,
        total: m.total ?? items.value.length,
        from: m.from ?? 1,
        to: m.to ?? items.value.length,
        per_page: m.per_page || 15,
      }
    } finally {
      loading.value = false
    }
  }

  async function createReservation(payload) {
    const { data } = await api.post('/sale-reservations', payload)
    return data.data
  }

  async function cancelReservation(id, reason = '') {
    const { data } = await api.post(`/sale-reservations/${id}/cancel`, { reason })
    return data.data
  }

  function resetFilters() {
    filters.search = ''
    filters.status = ''
  }

  return {
    items,
    loading,
    meta,
    filters,
    summary,
    fetchList,
    createReservation,
    cancelReservation,
    resetFilters,
  }
}
