import { ref, reactive, computed } from 'vue'
import api from '@/services/api'
import { tenantDisplayName } from '@/utils/tenantDisplayName'

function mapAgreement(item) {
  const start = item.dates?.start_date || ''
  const end = item.dates?.end_date || ''
  const status = item.status?.value || item.status || 'draft'
  return {
    id: item.id,
    agreement_number: item.agreement_number || '—',
    agreement_type: item.agreement_type || 'rental',
    tenant_name: tenantDisplayName(item.tenant) || '—',
    tenant_phone: item.tenant?.phone || '',
    building_name: item.apartment?.building?.name || '—',
    unit_number: item.apartment?.unit_number || '—',
    monthly_rent: item.financials?.monthly_rent ?? 0,
    start_date: start,
    end_date: end,
    status,
    status_label: item.status?.label || status,
    controls: item.controls || {},
    _raw: item,
  }
}

export function useRentalAgreements() {
  const items = ref([])
  const loading = ref(false)
  const filters = reactive({ search: '', status: '', building: '' })

  const summary = computed(() => ({
    total: items.value.length,
    active: items.value.filter((a) => a.status === 'active').length,
    draft: items.value.filter((a) => a.status === 'draft').length,
    monthlyRevenue: items.value
      .filter((a) => a.status === 'active')
      .reduce((s, a) => s + Number(a.monthly_rent || 0), 0),
  }))

  const filteredItems = computed(() => {
    const q = filters.search.toLowerCase()
    const stat = filters.status
    const bldg = filters.building.toLowerCase()
    return items.value.filter((a) => {
      const matchSearch =
        !q ||
        [a.agreement_number, a.tenant_name, a.building_name, a.unit_number].some((v) =>
          String(v || '').toLowerCase().includes(q)
        )
      const matchStatus = !stat || a.status === stat
      const matchBuilding = !bldg || String(a.building_name || '').toLowerCase().includes(bldg)
      return matchSearch && matchStatus && matchBuilding
    })
  })

  const meta = computed(() => ({
    current_page: 1,
    last_page: 1,
    total: filteredItems.value.length,
    from: filteredItems.value.length ? 1 : 0,
    to: filteredItems.value.length,
  }))

  async function fetchList() {
    loading.value = true
    try {
      const { data } = await api.get('/rental-agreements', {
        params: { per_page: 200 },
      })
      items.value = (data.data || []).map(mapAgreement)
    } finally {
      loading.value = false
    }
  }

  async function fetchOne(id) {
    const { data } = await api.get(`/rental-agreements/${id}`)
    return data.data
  }

  async function terminate(agreement, terminationReason, { refreshList = true } = {}) {
    await api.post(`/rental-agreements/${agreement.id}/terminate`, {
      termination_reason: terminationReason,
    })
    if (refreshList) {
      await fetchList()
    }
  }

  async function approve(agreement, { refreshList = true } = {}) {
    await api.post(`/rental-agreements/${agreement.id}/approve`)
    if (refreshList) {
      await fetchList()
    }
  }

  async function activate(agreement, { refreshList = true } = {}) {
    await api.post(`/rental-agreements/${agreement.id}/activate`)
    if (refreshList) {
      await fetchList()
    }
  }

  async function remove(agreement, { refreshList = true } = {}) {
    await api.delete(`/rental-agreements/${agreement.id}`)
    if (refreshList) {
      await fetchList()
    }
  }

  function resetFilters() {
    filters.search = ''
    filters.status = ''
    filters.building = ''
  }

  return {
    items,
    filteredItems,
    loading,
    meta,
    filters,
    summary,
    fetchList,
    fetchOne,
    terminate,
    approve,
    activate,
    remove,
    resetFilters,
  }
}
