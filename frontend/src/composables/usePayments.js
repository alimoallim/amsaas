import { ref, reactive } from 'vue'
import api from '@/services/api'
import { unwrapApiList, unwrapApiMeta, unwrapApiRecord } from '@/utils/apiResponse'

export function usePayments() {
  const items = ref([])
  const loading = ref(false)
  const saving = ref(false)
  const meta = ref({ current_page: 1, last_page: 1, total: 0 })
  const error = ref(null)

  const form = reactive({
    building_id: '',
    tenant_id: '',
    payment_purpose: 'rent',
    agreement_id: '',
    amount: '',
    payment_date: new Date().toISOString().split('T')[0],
    payment_method: 'bank_transfer',
    receipt_account_override: false,
    receipt_account_code: '',
    reference_number: '',
    notes: '',
  })

  const tenantBalance = ref(null)
  const balanceLoading = ref(false)

  function resetForm() {
    form.building_id = ''
    form.tenant_id = ''
    form.payment_purpose = 'rent'
    form.agreement_id = ''
    form.amount = ''
    form.payment_date = new Date().toISOString().split('T')[0]
    form.payment_method = 'bank_transfer'
    form.receipt_account_override = false
    form.receipt_account_code = ''
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
      tenantBalance.value = unwrapApiRecord(data)
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
      items.value = unwrapApiList(data)
      const m = unwrapApiMeta(data, {})
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
      const body = {
        tenant_id: payload.tenant_id,
        amount: Number(payload.amount),
        payment_date: payload.payment_date,
        payment_method: payload.payment_method,
        reference_number: payload.reference_number || undefined,
        notes: payload.notes || undefined,
      }
      if (payload.payment_purpose && payload.payment_purpose !== 'rent') {
        body.payment_purpose = payload.payment_purpose
        body.agreement_id = payload.agreement_id
      }
      if (payload.receipt_account_override && payload.receipt_account_code) {
        body.receipt_account_code = payload.receipt_account_code
      }
      const { data } = await api.post('/payments', body)
      return {
        payment: unwrapApiRecord(data),
        message: data.message ?? '',
      }
    } catch (e) {
      error.value = e
      throw e
    } finally {
      saving.value = false
    }
  }

  async function fetchOne(id) {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get(`/payments/${id}`)
      return unwrapApiRecord(data)
    } catch (e) {
      error.value = e
      throw e
    } finally {
      loading.value = false
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
    fetchOne,
    recordPayment,
    fetchTenantBalance,
  }
}
