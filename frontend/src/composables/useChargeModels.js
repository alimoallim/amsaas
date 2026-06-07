import { ref, reactive } from 'vue'
import api from '@/services/api.js'

export function useChargeModels() {
  const items = ref([])
  const loading = ref(false)
  const error = ref(null)
  const meta = ref({ current_page: 1, last_page: 1, total: 0 })
  const filters = reactive({ search: '', pricing_strategy: '', status: '' })

  async function fetchList(page = 1) {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get('/charge-models', {
        params: {
          page,
          search: filters.search || undefined,
          pricing_strategy: filters.pricing_strategy || undefined,
          status: filters.status || undefined,
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
        per_page: m.per_page ?? 15,
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
    filters.pricing_strategy = ''
    filters.status = ''
  }

  return { items, loading, error, meta, filters, fetchList, resetFilters }
}
