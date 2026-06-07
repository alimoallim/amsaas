import { ref, reactive } from 'vue'
import api from '@/services/api'

export function usePayments() {
  const items = ref([])
  const loading = ref(false)
  const saving = ref(false)
  const meta = ref({ current_page: 1, last_page: 1, total: 0 })
  const error = ref(null)

  const form = reactive({
    building_id: '',
    tenant_id: '',
    amount: '',
    payment_date: new Date().toISOString().split('T')[0],
    payment_method: 'bank_transfer',
    reference_number: '',
    notes: '',
  })

  const tenantBalance = ref(null)
  const balanceLoading = ref(false)

  function resetForm() {
    form.building_id = ''
    form.tenant_id = ''
    form.amount = ''
    form.payment_date = new Date().toISOString().split('T')[0]
    form.payment_method = 'bank_transfer'
    form.reference_number = ''
    form.notes = ''
    tenantBalance.value = null
  }

  async function fetchTenantBalance({ tenantId, buildingId, year, month } = {}) {
    if (!tenantId) {
      tenantBalance.value = null
      return null
    }
    balanceLoading.value = true
    try {
      const { data } = await api.get('/payments/tenant-balance', {
        params: {
          tenant_id: tenantId,
          building_id: buildingId || undefined,
          year: year || undefined,
          month: month || undefined,
        },
      })
      tenantBalance.value = data.data ?? data
      return tenantBalance.value
    } catch (e) {
      tenantBalance.value = null
      throw e
    } finally {
      balanceLoading.value = false
    }
  }

  async function fetchList(page = 1) {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get('/payments', { params: { page, per_page: 20 } })
      items.value = data.data ?? []
      const m = data.meta ?? {}
      const currentPage = m.current_page ?? page
      const perPage = m.per_page ?? 20
      const total = m.total ?? items.value.length
      const from = m.from ?? (total > 0 ? (currentPage - 1) * perPage + 1 : 0)
      const to = m.to ?? (total > 0 ? from + items.value.length - 1 : 0)
      meta.value = {
        current_page: currentPage,
        last_page: m.last_page ?? 1,
        total,
        from,
        to,
        per_page: perPage,
      }
    } catch (e) {
      error.value = e
      throw e
    } finally {
      loading.value = false
    }
  }

  async function recordPayment(payload) {
    saving.value = true
    error.value = null
    try {
      const { data } = await api.post('/payments', {
        tenant_id: payload.tenant_id,
        amount: Number(payload.amount),
        payment_date: payload.payment_date,
        payment_method: payload.payment_method,
        reference_number: payload.reference_number || undefined,
        notes: payload.notes || undefined,
      })
      return {
        payment: data.data ?? data,
        message: data.message ?? '',
      }
    } catch (e) {
      error.value = e
      throw e
    } finally {
      saving.value = false
    }
  }

  return {
    items,
    loading,
    saving,
    meta,
    error,
    form,
    tenantBalance,
    balanceLoading,
    resetForm,
    fetchList,
    recordPayment,
    fetchTenantBalance,
  }
}
