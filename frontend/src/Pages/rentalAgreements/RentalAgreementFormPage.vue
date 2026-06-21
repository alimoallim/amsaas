<template>
  <TransactionalFormLayout
    eyebrow="Operations"
    :breadcrumbs="breadcrumbs"
    :title="title"
    :description="description"
    :state="form.status || state"
    :primary-label="primaryLabel"
    :show-save-draft="false"
    :saving="loading || initialLoading"
    :error="serverError"
    @primary="onSubmit"
    @cancel="onCancel"
    @dismiss-error="serverError = ''"
  >
    <div v-if="initialLoading" class="py-12 text-center text-sm text-slate-500">Loading agreement…</div>
    <RentalAgreementForm
      v-else
      ref="agreementFormRef"
      embedded
      transactional
      :form="form"
      :errors="errors"
      :loading="loading"
      :mode="isEdit ? 'edit' : 'create'"
      :buildings="buildings"
      :tenants="tenants"
      :initial-building-id="initialBuildingId"
      :initial-status="initialStatus"
      @submit="submit"
    />
  </TransactionalFormLayout>
</template>

<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/services/api'
import { TransactionalFormLayout } from '@/components/erp'
import RentalAgreementForm from './RentalAgreementForm.vue'
import { useTransactionalEntityPage } from '@/composables/useTransactionalEntityPage'
import { mapBillingFromApi, buildRentalAgreementPayload } from '@/utils/rentalAgreementBilling'
import { useConfirm } from '@/composables/useConfirm'

const route = useRoute()
const { confirm } = useConfirm()

const loading = ref(false)
const agreementFormRef = ref(null)

const {
  entityId,
  isEdit,
  breadcrumbs,
  serverError,
  onCancel,
  onSaved,
  state,
} = useTransactionalEntityPage({
  registryKey: null,
  listRouteName: 'RentalAgreementIndex',
  showRouteName: 'RentalAgreementShow',
  moduleLabel: 'Rental agreements',
  createTitle: 'Create agreement',
  editTitle: 'Edit agreement',
  createDescription: 'Lease assignment, dates, and financial terms.',
  editDescription: 'Update agreement terms and billing configuration.',
  primaryLabelCreate: 'Create agreement',
  primaryLabelEdit: 'Save changes',
})

const title = computed(() => (isEdit.value ? 'Edit agreement' : 'Create agreement'))
const description = computed(() =>
  isEdit.value
    ? 'Update agreement terms and billing configuration.'
    : 'Lease assignment, dates, and financial terms.',
)
const primaryLabel = computed(() => (isEdit.value ? 'Save changes' : 'Create agreement'))

const initialLoading = ref(true)
const errors = ref({})
const buildings = ref([])
const tenants = ref([])
const initialBuildingId = ref('')
const initialStatus = ref('')

const emptyForm = () => ({
  apartment_id: '',
  tenant_id: '',
  start_date: '',
  end_date: '',
  monthly_rent: '',
  security_deposit: '',
  currency: 'USD',
  payment_due_day: 1,
  auto_renew: false,
  renewal_notice_days: 30,
  status: 'draft',
  rent_charge_model_id: '',
  recurring_charges: [],
})

const form = reactive(emptyForm())

async function loadDependencies() {
  const [bRes, tRes] = await Promise.all([
    api.get('/buildings', { params: { per_page: 200 } }),
    api.get('/tenants', { params: { per_page: 100 } }),
  ])
  buildings.value = bRes.data?.data ?? []
  tenants.value = tRes.data?.data ?? []
}

async function load() {
  initialLoading.value = true
  try {
    await loadDependencies()
    if (entityId.value) {
      const { data } = await api.get(`/rental-agreements/${entityId.value}`)
      const item = data.data ?? data
      initialStatus.value = item.status?.value ?? item.status ?? ''
      initialBuildingId.value = item.apartment?.building?.id ?? ''
      Object.assign(form, {
        apartment_id: item.apartment_id ?? item.apartment?.id ?? '',
        tenant_id: item.tenant_id ?? item.tenant?.id ?? '',
        start_date: item.dates?.start_date ?? '',
        end_date: item.dates?.end_date ?? '',
        monthly_rent: item.financials?.monthly_rent ?? '',
        security_deposit: item.financials?.security_deposit ?? '',
        currency: item.financials?.currency ?? 'USD',
        payment_due_day: item.financials?.payment_due_day ?? 1,
        status: initialStatus.value,
        auto_renew: item.renewal?.auto_renew ?? false,
        renewal_notice_days: item.renewal?.renewal_notice_days ?? 30,
        ...mapBillingFromApi(item.billing),
      })
    } else {
      Object.assign(form, emptyForm())
      initialStatus.value = ''
      initialBuildingId.value = ''
      const prefillTenantId = route.query.tenant_id
      if (prefillTenantId) {
        form.tenant_id = String(prefillTenantId)
      }
    }
  } finally {
    initialLoading.value = false
  }
}

async function onSubmit() {
  await agreementFormRef.value?.handleSubmit()
}

async function submit({ confirmCriticalChanges = false } = {}) {
  loading.value = true
  errors.value = {}
  serverError.value = ''
  try {
    if (entityId.value) {
      await api.put(
        `/rental-agreements/${entityId.value}`,
        buildRentalAgreementPayload(form, { forUpdate: true, confirmCriticalChanges }),
      )
    } else {
      const { data } = await api.post('/rental-agreements', buildRentalAgreementPayload(form))
      onSaved(data.data ?? data)
      return
    }
    onSaved({ id: entityId.value })
  } catch (e) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors || {}
      if (errors.value.confirm_critical_changes && !confirmCriticalChanges) {
        const ok = await confirm({
          title: 'Issued invoices on this agreement',
          message:
            'This agreement has issued invoices. Unit, tenant, or start date changes will not alter issued invoices but will update future billing. Continue?',
          confirmLabel: 'Save changes',
          variant: 'primary',
        })
        if (ok) {
          loading.value = false
          return submit({ confirmCriticalChanges: true })
        }
      }
      serverError.value = 'Please fix the highlighted fields.'
    } else {
      serverError.value = e.response?.data?.message || 'Save failed.'
    }
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>
