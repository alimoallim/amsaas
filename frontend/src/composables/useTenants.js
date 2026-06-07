import { ref, reactive, computed } from 'vue'
import api from '@/services/api'

export function useTenants() {
  const items = ref([])
  const loading = ref(false)
  const meta = ref({ total: 0, current_page: 1, last_page: 1 })
  const filters = reactive({ search: '', status: '', tenant_type: '' })

  const summary = computed(() => {
    const list = items.value
    const statusVal = (t) => t.status?.value ?? t.status
    return {
      total: meta.value.total || list.length,
      active: list.filter((t) => statusVal(t) === 'active').length,
      pending: list.filter((t) => statusVal(t) === 'pending').length,
      blacklisted: list.filter((t) => statusVal(t) === 'blacklisted').length,
    }
  })

  async function fetchList(page = 1) {
    loading.value = true
    try {
      const { data } = await api.get('/tenants', {
        params: {
          page,
          search: filters.search || undefined,
          status: filters.status || undefined,
          tenant_type: filters.tenant_type || undefined,
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
    filters.status = ''
    filters.tenant_type = ''
  }

  return { items, loading, meta, filters, summary, fetchList, resetFilters }
}
