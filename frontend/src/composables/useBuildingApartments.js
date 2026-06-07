import { ref } from 'vue'
import api from '@/services/api'

/**
 * Load rental apartments for a building with optional server-side search.
 */
export function useBuildingApartments() {
  const apartments = ref([])
  const loading = ref(false)

  async function fetchApartments(buildingId, { search = '', mode = 'create', ensureId = null } = {}) {
    if (!buildingId) {
      apartments.value = []
      return []
    }

    loading.value = true
    try {
      const params = {
        building_id: buildingId,
        listing_type: 'rental',
        per_page: 100,
      }
      if (search) params.search = search
      if (mode === 'create') params.inventory_status = 'available'

      const { data } = await api.get('/apartments', { params })
      let list = data?.data ?? []

      if (ensureId && !list.some((a) => a.id === ensureId)) {
        try {
          const one = await api.get(`/apartments/${ensureId}`)
          const apt = one.data?.data ?? one.data
          if (apt?.id) list = [apt, ...list]
        } catch {
          /* keep list as-is */
        }
      }

      apartments.value = list
      return list
    } finally {
      loading.value = false
    }
  }

  function apartmentToOption(apt) {
    const unit = apt.unit?.unit_number ?? apt.unit_number ?? '—'
    const floor = apt.unit?.floor ?? apt.floor
    const status = apt.listing?.inventory_status ?? apt.inventory_status
    const hints = []
    if (floor != null && floor !== '') hints.push(`Floor ${floor}`)
    if (status) hints.push(String(status).replaceAll('_', ' '))
    if (apt.layout?.bedrooms != null) hints.push(`${apt.layout.bedrooms} bed`)

    return {
      value: apt.id,
      label: `Unit ${unit}`,
      hint: hints.join(' · ') || undefined,
      raw: apt,
    }
  }

  return { apartments, loading, fetchApartments, apartmentToOption }
}
