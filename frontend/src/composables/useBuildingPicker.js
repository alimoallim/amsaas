import { ref } from 'vue'
import api from '@/services/api'

/**
 * Searchable building list for ErpSearchSelect (remote).
 */
export function useBuildingPicker() {
  const buildings = ref([])
  const loading = ref(false)

  async function fetchBuildings(search = '', { ensureId = null, perPage = 50 } = {}) {
    loading.value = true
    try {
      const { data } = await api.get('/buildings', {
        params: {
          search: search || undefined,
          per_page: perPage,
        },
      })
      let list = data?.data ?? []
      if (ensureId && !list.some((b) => b.id === ensureId)) {
        try {
          const one = await api.get(`/buildings/${ensureId}`)
          const b = one.data?.data ?? one.data
          if (b?.id) list = [b, ...list]
        } catch {
          /* keep list */
        }
      }
      buildings.value = list
      return list
    } finally {
      loading.value = false
    }
  }

  function buildingToOption(building) {
    const hint = [building.code, building.city, building.country].filter(Boolean).join(' · ')
    return {
      value: building.id,
      label: building.name,
      hint: hint || undefined,
      raw: building,
    }
  }

  return { buildings, loading, fetchBuildings, buildingToOption }
}
