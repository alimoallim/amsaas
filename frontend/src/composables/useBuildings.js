import { ref, reactive, computed } from 'vue'
import api from '@/services/api'

export function useBuildings() {
  const items = ref([])
  const loading = ref(false)
  const meta = ref({ total: 0, current_page: 1, last_page: 1, from: 0, to: 0 })
  const filters = reactive({ search: '', status: '' })

  const summary = computed(() => {
    const list = items.value
    return {
      total: meta.value.total || list.length,
      active: list.filter((b) => b.is_active).length,
      inactive: list.filter((b) => !b.is_active).length,
      floors: list.reduce((t, b) => t + Number(b.total_floors || 0), 0),
    }
  })

  async function fetchList(page = 1) {
    loading.value = true
    try {
      const { data } = await api.get('/buildings', {
        params: { page, search: filters.search || undefined, status: filters.status || undefined },
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

  async function remove(building) {
    await api.delete(`/buildings/${building.id}`)
    await fetchList(meta.value.current_page)
  }

  function resetFilters() {
    filters.search = ''
    filters.status = ''
  }

  return { items, loading, meta, filters, summary, fetchList, remove, resetFilters }
}
