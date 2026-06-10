import { ref, reactive } from 'vue'
import api from '@/services/api'
import { unwrapApiRecord } from '@/utils/apiResponse'

export function useAgingReport() {
  const report = ref(null)
  const loading = ref(false)
  const exporting = ref(false)
  const error = ref(null)

  const filters = reactive({
    as_of: new Date().toISOString().split('T')[0],
    building_id: '',
    group_by: 'tenant',
  })

  async function fetchReport(overrides = {}) {
    loading.value = true
    error.value = null
    try {
      const params = {
        as_of: overrides.as_of ?? filters.as_of,
        group_by: overrides.group_by ?? filters.group_by,
        building_id: (overrides.building_id ?? filters.building_id) || undefined,
      }
      const { data } = await api.get('/reports/aging', { params })
      report.value = unwrapApiRecord(data)
      return report.value
    } catch (e) {
      error.value = e
      throw e
    } finally {
      loading.value = false
    }
  }

  async function exportCsv(overrides = {}) {
    exporting.value = true
    error.value = null
    try {
      const params = {
        as_of: overrides.as_of ?? filters.as_of,
        building_id: (overrides.building_id ?? filters.building_id) || undefined,
      }
      const response = await api.get('/reports/aging/export', {
        params,
        responseType: 'blob',
      })
      const blob = new Blob([response.data], { type: 'text/csv' })
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `aging-receivables-${params.as_of}.csv`)
      document.body.appendChild(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)
    } catch (e) {
      error.value = e
      throw e
    } finally {
      exporting.value = false
    }
  }

  const delinquency = ref(null)
  const delinquencyLoading = ref(false)

  async function fetchDelinquency(overrides = {}) {
    delinquencyLoading.value = true
    error.value = null
    try {
      const params = {
        as_of: overrides.as_of ?? filters.as_of,
        building_id: (overrides.building_id ?? filters.building_id) || undefined,
        escalation_stage: overrides.escalation_stage || undefined,
      }
      const { data } = await api.get('/reports/delinquency', { params })
      delinquency.value = unwrapApiRecord(data)
      return delinquency.value
    } catch (e) {
      error.value = e
      throw e
    } finally {
      delinquencyLoading.value = false
    }
  }

  const reminding = ref(false)
  const generatingNotice = ref(false)

  async function sendReminders(flagIds) {
    reminding.value = true
    error.value = null
    try {
      const { data } = await api.post('/reports/delinquency/remind', {
        flag_ids: flagIds,
      })
      return {
        stats: data.data ?? {},
        message: data.message ?? '',
      }
    } catch (e) {
      error.value = e
      throw e
    } finally {
      reminding.value = false
    }
  }

  async function downloadNotice(flagId) {
    generatingNotice.value = true
    error.value = null
    try {
      const { data: gen } = await api.post('/reports/delinquency/notices', { flag_id: flagId })
      const noticeId = gen.data?.id
      if (!noticeId) throw new Error('Notice not created')

      const response = await api.get(`/reports/notices/${noticeId}/download`, {
        responseType: 'blob',
      })
      const label = gen.data?.notice_type || 'notice'
      const blob = new Blob([response.data], { type: 'application/pdf' })
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `collection-notice-${label}.pdf`)
      document.body.appendChild(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)
      return gen.data
    } catch (e) {
      error.value = e
      throw e
    } finally {
      generatingNotice.value = false
    }
  }

  return {
    report,
    delinquency,
    loading,
    delinquencyLoading,
    reminding,
    generatingNotice,
    exporting,
    error,
    filters,
    fetchReport,
    fetchDelinquency,
    sendReminders,
    downloadNotice,
    exportCsv,
  }
}
