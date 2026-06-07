import { ref, reactive } from 'vue'
import api from '@/services/api'

export function useApartments() {
  const items = ref([])
  const buildings = ref([])
  const loading = ref(false)
  const meta = ref({ total: 0, current_page: 1, last_page: 1, from: 0, to: 0 })
  const summary = ref({
    total: 0,
    available: 0,
    occupied: 0,
    reserved: 0,
  })
  const filters = reactive({
    search: '',
    building_id: '',
    listing_type: '',
    inventory_status: '',
  })

  async function fetchBuildings() {
    try {
      const { data } = await api.get('/buildings')
      buildings.value = data.data || []
    } catch {
      buildings.value = []
    }
  }

  async function fetchSummary() {
    try {
      const { data } = await api.get('/apartments/summary')
      summary.value = { ...summary.value, ...(data.data || data) }
    } catch {
      /* optional endpoint */
    }
  }

  async function fetchList(page = 1) {
    loading.value = true
    try {
      const { data } = await api.get('/apartments', {
        params: {
          page,
          search: filters.search || undefined,
          building_id: filters.building_id || undefined,
          listing_type: filters.listing_type || undefined,
          inventory_status: filters.inventory_status || undefined,
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

  async function remove(apartment) {
    await api.delete(`/apartments/${apartment.id}`)
    await fetchList(meta.value.current_page)
    await fetchSummary()
  }

  function resetFilters() {
    filters.search = ''
    filters.building_id = ''
    filters.listing_type = ''
    filters.inventory_status = ''
  }

  return {
    items,
    buildings,
    loading,
    meta,
    summary,
    filters,
    fetchList,
    fetchBuildings,
    fetchSummary,
    remove,
    resetFilters,
  }
}
