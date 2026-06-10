import { reactive, ref } from 'vue'
import api from '@/services/api'

export function useTenantBilling(tenantId) {
  const tenant = ref(null)
  const invoices = ref([])
  const summary = reactive({
    total_invoiced: 0,
    total_paid: 0,
    outstanding_balance: 0,
    invoice_count: 0,
    counts_by_status: {},
    agreement_count: 0,
  })
  const loading = ref(false)
  const error = ref(null)
  const meta = ref({ current_page: 1, last_page: 1, total: 0, per_page: 25 })

  const filters = reactive({
    year: '',
    month: '',
    status: '',
    per_page: 25,
  })

  async function fetchBilling(page = 1) {
    if (!tenantId.value && !tenantId) {
      return
    }

    const id = typeof tenantId === 'object' && tenantId.value != null ? tenantId.value : tenantId

    loading.value = true
    error.value = null

    try {
      const { data } = await api.get(`/tenants/${id}/billing`, {
        params: {
          page,
          year: filters.year || undefined,
          month: filters.month || undefined,
          status: filters.status || undefined,
          per_page: filters.per_page,
        },
      })

      const payload = data.data ?? data
      tenant.value = payload.tenant ?? null
      invoices.value = payload.invoices ?? []
      Object.assign(summary, payload.summary ?? {})

      const m = data.meta ?? {}
      meta.value = {
        current_page: m.current_page ?? 1,
        last_page: m.last_page ?? 1,
        total: m.total ?? invoices.value.length,
        from: m.from ?? 0,
        to: m.to ?? 0,
        per_page: m.per_page ?? filters.per_page,
      }
    } catch (e) {
      error.value = e
      throw e
    } finally {
      loading.value = false
    }
  }

  return {
    tenant,
    invoices,
    summary,
    loading,
    error,
    meta,
    filters,
    fetchBilling,
  }
}
