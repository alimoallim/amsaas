import { ref, reactive } from 'vue'
import api from '@/services/api.js'

function defaultMeta() {
  return {
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 15,
    from: 0,
    to: 0,
  }
}

export function useChargeTypes() {
  const items = ref([])
  const loading = ref(false)
  const error = ref(null)
  const meta = ref(defaultMeta())
  const filters = reactive({ search: '', category: '' })

  async function fetchList(page = 1) {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get('/charge-types', {
        params: {
          page,
          per_page: meta.value.per_page,
          search: filters.search.trim() || undefined,
          category: filters.category || undefined,
        },
      })
      items.value = data.data ?? []
      if (data.meta) {
        meta.value = data.meta
      }
    } catch (e) {
      error.value = e
      throw e
    } finally {
      loading.value = false
    }
  }

  async function updateStatus(type, targetStatus) {
    const previous = type.status
    try {
      type.status = targetStatus
      await api.put(`/charge-types/${type.id}`, {
        code: type.code,
        name: type.name,
        short_name: type.short_name ?? null,
        description: type.description ?? null,
        category: type.category,
        billing_behavior: type.billing_behavior,
        calculation_method: type.calculation_method,
        billing_frequency: type.billing_frequency,
        financial_classification: type.financial_classification,
        default_currency: type.default_currency ?? 'USD',
        default_amount: type.default_amount ?? null,
        default_percentage: type.default_percentage ?? null,
        status: targetStatus,
      })
    } catch (e) {
      type.status = previous
      throw e
    }
  }

  function isActive(type) {
    return type.status === 'active'
  }

  function chargeTypeToOption(type) {
    const category = type.category ? String(type.category).replace(/_/g, ' ') : ''
    return {
      value: type.id,
      label: type.name,
      hint: [type.code, category].filter(Boolean).join(' · ') || undefined,
      raw: type,
    }
  }

  /** Load charge types for selects (create/edit charge model). */
  async function fetchForPicker({ ensureId = null, status = 'active', perPage = 100 } = {}) {
    loading.value = true
    try {
      const { data } = await api.get('/charge-types', {
        params: {
          per_page: perPage,
          status: status || undefined,
        },
      })
      let list = data.data ?? []
      if (ensureId && !list.some((t) => t.id === ensureId)) {
        try {
          const one = await api.get(`/charge-types/${ensureId}`)
          const t = one.data?.data ?? one.data
          if (t?.id) list = [t, ...list]
        } catch {
          /* keep list */
        }
      }
      items.value = list
      return list
    } finally {
      loading.value = false
    }
  }

  return {
    items,
    loading,
    error,
    meta,
    filters,
    fetchList,
    fetchForPicker,
    chargeTypeToOption,
    updateStatus,
    isActive,
  }
}
