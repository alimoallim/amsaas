import { ref, reactive } from 'vue'
import api from '@/services/api.js'

function defaultMeta() {
  return {
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 25,
    from: 0,
    to: 0,
  }
}

export function useAccounts() {
  const items = ref([])
  const loading = ref(false)
  const error = ref(null)
  const meta = ref(defaultMeta())
  const filters = reactive({ search: '', type: '', status: '' })

  async function fetchList(page = 1) {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get('/accounts', {
        params: {
          page,
          per_page: meta.value.per_page,
          search: filters.search.trim() || undefined,
          type: filters.type || undefined,
          status: filters.status || undefined,
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

  async function fetchOne(id) {
    const { data } = await api.get(`/accounts/${id}`)
    return data.data ?? data
  }

  async function create(payload) {
    const { data } = await api.post('/accounts', payload)
    return data.data ?? data
  }

  async function update(id, payload) {
    const { data } = await api.put(`/accounts/${id}`, payload)
    return data.data ?? data
  }

  async function remove(id) {
    await api.delete(`/accounts/${id}`)
  }

  function typeLabel(type) {
    const labels = {
      asset: 'Asset',
      liability: 'Liability',
      equity: 'Equity',
      revenue: 'Revenue',
      expense: 'Expense',
    }
    return labels[type] || type || '—'
  }

  function typeTone(type) {
    const map = {
      asset: 'active',
      liability: 'pending',
      equity: 'draft',
      revenue: 'posted',
      expense: 'partially_paid',
    }
    return map[type] || 'inactive'
  }

  return {
    items,
    loading,
    error,
    meta,
    filters,
    fetchList,
    fetchOne,
    create,
    update,
    remove,
    typeLabel,
    typeTone,
  }
}
