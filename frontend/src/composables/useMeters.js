import { ref, reactive } from 'vue'
import api from '@/services/api.js'

export function useMeters() {
  const items = ref([])
  const summary = ref({})
  const loading = ref(false)
  const error = ref(null)
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
    from: 0,
    to: 0,
  })
  const filters = reactive({
    search: '',
    utility_type: '',
    status: '',
    smart_meter: '',
  })

  async function fetchList(page = 1) {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get('/meters', { params: { page, ...filters } })
      items.value = data.data ?? []
      summary.value = data.summary ?? {}
      const m = data.meta ?? {}
      pagination.value = {
        current_page: m.current_page || 1,
        last_page: m.last_page || 1,
        per_page: m.per_page || 15,
        total: m.total || 0,
        from: ((m.current_page || 1) - 1) * (m.per_page || 15) + 1,
        to: Math.min((m.current_page || 1) * (m.per_page || 15), m.total || 0),
      }
    } catch (e) {
      error.value = e
      throw e
    } finally {
      loading.value = false
    }
  }

  function resetFilters() {
    filters.search = ''
    filters.utility_type = ''
    filters.status = ''
    filters.smart_meter = ''
  }

  const meta = pagination

  return {
    items,
    summary,
    loading,
    error,
    pagination,
    meta,
    filters,
    fetchList,
    resetFilters,
  }
}
