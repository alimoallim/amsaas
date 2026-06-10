import { ref, reactive, computed } from 'vue'
import api from '@/services/api'

function mapAgreement(item) {
  const status = item.status?.value || item.status || 'draft'
  return {
    id: item.id,
    agreement_number: item.agreement_number || '—',
    buyer_name: item.buyer?.full_name || '—',
    building_name: item.apartment?.building?.name || '—',
    unit_number: item.apartment?.unit_number || '—',
    sale_price: item.financials?.sale_price ?? 0,
    down_payment: item.financials?.down_payment ?? 0,
    paid_amount: item.financials?.paid_amount ?? 0,
    balance_due: item.financials?.balance_due ?? item.financials?.remaining_balance ?? 0,
    remaining_balance: item.financials?.remaining_balance ?? 0,
    currency: item.financials?.currency || 'USD',
    is_installment_sale: item.installments?.is_installment_sale ?? item.payment_plan?.is_payment_plan ?? false,
    is_payment_plan: item.payment_plan?.mode === 'payment_plan' || item.installments?.is_payment_plan || false,
    contract_date: item.dates?.start_date || '',
    status,
    status_label: item.status?.label || status,
    inventory_status: item.apartment?.inventory_status || '',
    controls: item.controls || {},
    _raw: item,
  }
}

export function useSaleAgreements() {
  const items = ref([])
  const loading = ref(false)
  const meta = ref({ total: 0, current_page: 1, last_page: 1 })
  const filters = reactive({ search: '', status: '' })

  const summary = computed(() => ({
    total: meta.value.total || items.value.length,
    active: items.value.filter((a) => a.status === 'active').length,
    draft: items.value.filter((a) => a.status === 'draft').length,
    contractValue: items.value
      .filter((a) => a.status === 'active')
      .reduce((s, a) => s + Number(a.sale_price || 0), 0),
  }))

  async function fetchList(page = 1) {
    loading.value = true
    try {
      const { data } = await api.get('/sale-agreements', {
        params: {
          page,
          per_page: 50,
          search: filters.search || undefined,
          status: filters.status || undefined,
        },
      })
      items.value = (data.data || []).map(mapAgreement)
      const m = data.meta || {}
      meta.value = {
        current_page: m.current_page || 1,
        last_page: m.last_page || 1,
        total: m.total ?? items.value.length,
      }
    } finally {
      loading.value = false
    }
  }

  async function fetchOne(id) {
    const { data } = await api.get(`/sale-agreements/${id}`)
    return data.data
  }

  async function createContract(payload) {
    const { data } = await api.post('/sale-agreements', payload)
    return data.data
  }

  async function executeContract(id) {
    const { data } = await api.post(`/sale-agreements/${id}/execute`)
    return data.data
  }

  async function cancelContract(id, reason = '') {
    const { data } = await api.post(`/sale-agreements/${id}/cancel`, { reason })
    return data.data
  }

  async function recordPayment(id, payload) {
    const { data } = await api.post(`/sale-agreements/${id}/record-payment`, {
      amount: Number(payload.amount),
      payment_date: payload.payment_date,
      payment_method: payload.payment_method,
      reference_number: payload.reference_number || undefined,
      notes: payload.notes || undefined,
    })
    return {
      contract: data.data,
      message: data.message,
      completed: data.completed,
    }
  }

  async function applyReservationDeposit(id, payload = {}) {
    const body = {}
    if (payload.amount != null && payload.amount !== '') {
      body.amount = Number(payload.amount)
    }
    if (payload.notes) {
      body.notes = payload.notes
    }
    const { data } = await api.post(`/sale-agreements/${id}/apply-deposit`, body)
    return {
      contract: data.data,
      message: data.message,
      completed: data.completed,
    }
  }

  async function recordInstallmentPayment(id, payload) {
    const { data } = await api.post(`/sale-agreements/${id}/record-installment-payment`, {
      installment_schedule_id: payload.installment_schedule_id,
      amount: Number(payload.amount),
      payment_date: payload.payment_date,
      payment_method: payload.payment_method,
      reference_number: payload.reference_number || undefined,
      notes: payload.notes || undefined,
    })
    return {
      contract: data.data,
      message: data.message,
      completed: data.completed,
    }
  }

  async function generateSchedule(id) {
    const { data } = await api.post(`/sale-agreements/${id}/generate-schedule`)
    return {
      contract: data.data,
      message: data.message,
    }
  }

  async function extractBlobError(data) {
    if (!(data instanceof Blob)) {
      return null
    }
    try {
      const text = await data.text()
      const json = JSON.parse(text)
      return json.message || null
    } catch {
      return null
    }
  }

  async function downloadPdf(path, filename) {
    try {
      const res = await api.get(path, {
        responseType: 'blob',
        headers: { Accept: 'application/pdf' },
      })

      const contentType = res.headers['content-type'] || ''
      if (!contentType.includes('pdf') && res.data?.type?.includes?.('json')) {
        const message = await extractBlobError(res.data)
        throw new Error(message || 'Server did not return a PDF file.')
      }

      if (!res.data || res.data.size === 0) {
        throw new Error('Received an empty PDF file.')
      }

      const blob = new Blob([res.data], { type: contentType || 'application/pdf' })
      const url = URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = filename
      link.click()
      URL.revokeObjectURL(url)
    } catch (err) {
      const blobMessage = await extractBlobError(err.response?.data)
      if (blobMessage) {
        err.message = blobMessage
        err.response = { ...(err.response || {}), data: { message: blobMessage } }
      }
      throw err
    }
  }

  async function downloadCompletionCertificate(id, agreementNumber) {
    await downloadPdf(
      `/sale-agreements/${id}/completion-certificate`,
      `${agreementNumber || 'sale'}-completion.pdf`,
    )
  }

  async function downloadOwnershipTransferCertificate(id, agreementNumber) {
    await downloadPdf(
      `/sale-agreements/${id}/ownership-transfer-certificate`,
      `${agreementNumber || 'sale'}-ownership-transfer.pdf`,
    )
  }

  async function downloadSalesContract(id, agreementNumber) {
    await downloadPdf(
      `/sale-agreements/${id}/sales-contract`,
      `${agreementNumber || 'sale'}-contract.pdf`,
    )
  }

  async function downloadPaymentPlanStatement(id, agreementNumber) {
    await downloadPdf(
      `/sale-agreements/${id}/payment-plan-statement`,
      `${agreementNumber || 'sale'}-payment-plan.pdf`,
    )
  }

  /** @deprecated */
  async function downloadInstallmentSchedulePdf(id, agreementNumber) {
    return downloadPaymentPlanStatement(id, agreementNumber)
  }

  async function approveOwnership(id, step, notes = '') {
    const { data } = await api.post(`/sale-agreements/${id}/approve-ownership`, {
      step,
      notes: notes || undefined,
    })
    return {
      contract: data.data,
      message: data.message,
      finalized: data.finalized,
    }
  }

  async function issueTitleDeed(id, payload) {
    const { data } = await api.post(`/sale-agreements/${id}/issue-title-deed`, {
      title_deed_number: payload.title_deed_number,
      notes: payload.notes || undefined,
    })
    return {
      contract: data.data,
      message: data.message,
    }
  }

  async function remove(id) {
    await api.delete(`/sale-agreements/${id}`)
  }

  function resetFilters() {
    filters.search = ''
    filters.status = ''
  }

  return {
    items,
    loading,
    meta,
    filters,
    summary,
    fetchList,
    fetchOne,
    createContract,
    executeContract,
    cancelContract,
    recordPayment,
    applyReservationDeposit,
    recordInstallmentPayment,
    generateSchedule,
    downloadCompletionCertificate,
    downloadOwnershipTransferCertificate,
    downloadSalesContract,
    downloadPaymentPlanStatement,
    downloadInstallmentSchedulePdf,
    approveOwnership,
    issueTitleDeed,
    remove,
    resetFilters,
  }
}
