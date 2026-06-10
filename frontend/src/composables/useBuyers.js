import { ref, reactive, computed } from 'vue'
import api from '@/services/api'

export function useBuyers() {
  const items = ref([])
  const loading = ref(false)
  const meta = ref({ total: 0, current_page: 1, last_page: 1 })
  const filters = reactive({ search: '', is_active: '' })

  const summary = computed(() => {
    const list = items.value
    return {
      total: meta.value.total || list.length,
      active: list.filter((b) => b.is_active).length,
      linked: list.filter((b) => b.tenant_id).length,
    }
  })

  async function fetchList(page = 1) {
    loading.value = true
    try {
      const { data } = await api.get('/buyers', {
        params: {
          page,
          search: filters.search || undefined,
          is_active: filters.is_active === '' ? undefined : filters.is_active,
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

  function resetFilters() {
    filters.search = ''
    filters.is_active = ''
  }

  return { items, loading, meta, filters, summary, fetchList, resetFilters }
}
