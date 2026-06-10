<template>
  <FormModal
    ref="modalRef"
    :open="open"
    :title="isEdit ? 'Edit agreement' : 'Create agreement'"
    subtitle="Lease assignment, dates, and financial terms."
    size="full"
    :show-footer="false"
    @close="$emit('close')"
  >
    <div v-if="initialLoading" class="py-12 text-center text-sm text-slate-500">Loading…</div>
    <RentalAgreementForm
      v-else
      embedded
      :form="form"
      :errors="errors"
      :loading="loading"
      :mode="isEdit ? 'edit' : 'create'"
      :buildings="buildings"
      :tenants="tenants"
      :initial-building-id="initialBuildingId"
      :initial-status="initialStatus"
      @submit="submit"
      @cancel="onCancel"
    />
  </FormModal>
</template>

<script setup>
import { reactive, ref, watch, computed } from 'vue'
import api from '@/services/api'
import { useConfirm } from '@/composables/useConfirm'
import { FormModal } from '@/components/erp'
import RentalAgreementForm from '@/Pages/rentalAgreements/RentalAgreementForm.vue'
import { mapBillingFromApi, buildRentalAgreementPayload } from '@/utils/rentalAgreementBilling'

const props = defineProps({ open: Boolean, entityId: { default: null } })
const emit = defineEmits(['close', 'saved'])

const modalRef = ref(null)
const initialLoading = ref(true)
const loading = ref(false)
const errors = ref({})
const buildings = ref([])
const tenants = ref([])
const initialBuildingId = ref('')
const initialStatus = ref('')
const isEdit = computed(() => !!props.entityId)
const { confirm } = useConfirm()

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
  notes: '',
  special_terms: '',
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

function onCancel() {
  modalRef.value?.requestClose?.()
}

async function load() {
  initialLoading.value = true
  try {
    await loadDependencies()
    if (props.entityId) {
      const { data } = await api.get(`/rental-agreements/${props.entityId}`)
      const item = data.data ?? data
      initialStatus.value = item.status?.value ?? item.status ?? ''
      initialBuildingId.value = item.apartment?.building?.id ?? ''
      Object.assign(form, {
        apartment_id: item.apartment_id ?? item.apartment?.id ?? '',
        tenant_id: item.tenant_id ?? item.tenant?.id ?? '',
        start_date: item.dates?.start_date ?? '',
        end_date: item.dates?.end_date ?? '',
        monthly_rent: item.financials?.monthly_rent ?? '',
        deposit_amount: item.financials?.deposit_amount ?? '',
        currency: item.financials?.currency ?? 'USD',
        payment_due_day: item.financials?.payment_due_day ?? 1,
        status: initialStatus.value,
        security_deposit: item.financials?.security_deposit ?? '',
        auto_renew: item.renewal?.auto_renew ?? false,
        renewal_notice_days: item.renewal?.renewal_notice_days ?? 30,
        notes: item.notes?.agreement_notes ?? '',
        special_terms: item.notes?.special_terms ?? '',
        ...mapBillingFromApi(item.billing),
      })
    } else {
      Object.assign(form, emptyForm())
      initialStatus.value = ''
      initialBuildingId.value = ''
    }
  } finally {
    initialLoading.value = false
  }
}

async function submit({ confirmCriticalChanges = false } = {}) {
  loading.value = true
  errors.value = {}
  try {
    if (props.entityId) {
      await api.put(
        `/rental-agreements/${props.entityId}`,
        buildRentalAgreementPayload(form, { forUpdate: true, confirmCriticalChanges }),
      )
    } else {
      await api.post('/rental-agreements', buildRentalAgreementPayload(form))
    }
    emit('saved')
    emit('close')
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
    }
  } finally {
    loading.value = false
  }
}

watch(
  () => [props.open, props.entityId],
  ([open]) => {
    if (open) load()
  },
  { immediate: true }
)
</script>
