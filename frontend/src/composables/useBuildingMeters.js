import { ref } from 'vue'
import api from '@/services/api'

/**
 * Meters scoped to a building for dependent selects (e.g. meter readings).
 */
export function useBuildingMeters() {
  const meters = ref([])
  const loading = ref(false)

  async function fetchMeters(buildingId, { search = '', status = 'active', ensureId = null, perPage = 100 } = {}) {
    if (!buildingId) {
      meters.value = []
      return []
    }

    loading.value = true
    try {
      const { data } = await api.get('/meters', {
        params: {
          building_id: buildingId,
          status,
          search: search || undefined,
          per_page: perPage,
        },
      })
      let list = data?.data ?? []
      if (ensureId && !list.some((m) => m.id === ensureId)) {
        try {
          const one = await api.get(`/meters/${ensureId}`)
          const m = one.data?.data ?? one.data
          if (m?.id) {
            list = [m, ...list]
          }
        } catch {
          /* keep list */
        }
      }
      meters.value = list
      return list
    } finally {
      loading.value = false
    }
  }

  function meterOwnershipHint(meter) {
    const ownership = meter.ownership_type?.value ?? meter.ownership_type ?? ''
    switch (ownership) {
      case 'building':
        return 'Building'
      case 'shared':
        return 'Shared'
      case 'apartment':
        return meter.apartment?.unit_number
          ? `Unit ${meter.apartment.unit_number}`
          : 'Apartment'
      case 'tenant': {
        const name = meter.tenant?.name ?? meter.tenant?.display_name
        return name ? `Tenant · ${name}` : 'Tenant'
      }
      default:
        return ''
    }
  }

  function meterToOption(meter) {
    const utility =
      meter.utility_type?.label ?? meter.utility_type ?? ''
    const scope = meterOwnershipHint(meter)
    return {
      value: meter.id,
      label: meter.meter_number,
      hint: [utility, scope].filter(Boolean).join(' · ') || undefined,
      raw: meter,
    }
  }

  return { meters, loading, fetchMeters, meterToOption }
}
