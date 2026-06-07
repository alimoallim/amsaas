import { ref } from 'vue'
import api from '@/services/api'
import { tenantDisplayName } from '@/utils/tenantDisplayName'

/**
 * Searchable tenant list for ErpSearchSelect (remote).
 */
export function useTenantPicker() {
  const tenants = ref([])
  const loading = ref(false)

  async function fetchTenants(
    search = '',
    { ensureId = null, perPage = 50, status = 'active', buildingId = null } = {},
  ) {
    loading.value = true
    try {
      const { data } = await api.get('/tenants', {
        params: {
          search: search || undefined,
          status: status || undefined,
          building_id: buildingId || undefined,
          per_page: perPage,
        },
      })
      let list = data?.data ?? []
      if (ensureId && !list.some((t) => t.id === ensureId)) {
        try {
          const one = await api.get(`/tenants/${ensureId}`)
          const t = one.data?.data ?? one.data
          if (t?.id) list = [t, ...list]
        } catch {
          /* keep list */
        }
      }
      tenants.value = list
      return list
    } finally {
      loading.value = false
    }
  }

  function tenantToOption(tenant) {
    const email = tenant.contact?.email ?? tenant.email
    const phone = tenant.contact?.phone ?? tenant.phone
    const code = tenant.tenant_code
    const hint = [code, email, phone].filter(Boolean).join(' · ')

    return {
      value: tenant.id,
      label: tenantDisplayName(tenant) || code || tenant.id,
      hint: hint || undefined,
      raw: tenant,
    }
  }

  return { tenants, loading, fetchTenants, tenantToOption }
}
