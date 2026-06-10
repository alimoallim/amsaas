import { ref, reactive } from 'vue'
import api from '@/services/api'

export function useInventory() {
  const items = ref([])
  const loading = ref(false)
  const meta = ref({ total: 0, current_page: 1, last_page: 1 })
  const filters = reactive({
    search: '',
    building_id: '',
    inventory_status: '',
    min_price: '',
    max_price: '',
    bedrooms: '',
    sellable_only: true,
  })

  async function fetchList(page = 1) {
    loading.value = true
    try {
      const { data } = await api.get('/inventory/available', {
        params: {
          page,
          search: filters.search || undefined,
          building_id: filters.building_id || undefined,
          inventory_status: filters.inventory_status || undefined,
          min_price: filters.min_price || undefined,
          max_price: filters.max_price || undefined,
          bedrooms: filters.bedrooms || undefined,
          sellable_only: filters.sellable_only ? 1 : undefined,
          sort_by: 'market_sale_price',
          sort_direction: 'asc',
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
    filters.building_id = ''
    filters.inventory_status = ''
    filters.min_price = ''
    filters.max_price = ''
    filters.bedrooms = ''
    filters.sellable_only = true
  }

  return { items, loading, meta, filters, fetchList, resetFilters }
}
