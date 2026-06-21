<template>
  <AlertBanner
    v-if="pageError"
    variant="error"
    class="erp-page mb-4"
    :message="pageError"
    @dismiss="pageError = ''"
  />

  <div v-if="loading" class="erp-page py-12 text-center text-slate-500">Loading tenant…</div>

  <ObjectPageLayout
    v-else-if="tenant"
    :breadcrumbs="breadcrumbs"
    :title="title"
    :subtitle="tenant.tenant_code"
    :status="status"
    :status-label="statusLabel"
    :attributes="attributes"
    :tabs="tabs"
    initial-tab="overview"
  >
    <template #actions>
      <ErpButton variant="ghost" size="sm" :to="{ name: 'Tenants' }">Back</ErpButton>
      <ErpButton variant="secondary" size="sm" :to="{ name: 'TenantBilling', params: { id: tenant.id } }">
        Billing
      </ErpButton>
      <ErpButton size="sm" :to="{ name: 'TenantEdit', params: { id: tenant.id } }">Edit</ErpButton>
    </template>

    <template #overview>
      <div class="grid gap-6 lg:grid-cols-2">
        <FormSection title="Contact">
          <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
            <div><dt class="text-slate-500">Email</dt><dd class="font-medium">{{ tenant.contact?.email || '—' }}</dd></div>
            <div><dt class="text-slate-500">Phone</dt><dd class="font-medium">{{ tenant.contact?.phone || '—' }}</dd></div>
            <div><dt class="text-slate-500">Alternate phone</dt><dd class="font-medium">{{ tenant.contact?.alternate_phone || '—' }}</dd></div>
            <div><dt class="text-slate-500">Type</dt><dd class="font-medium capitalize">{{ tenant.tenant_type || '—' }}</dd></div>
          </dl>
        </FormSection>

        <FormSection title="Identity">
          <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
            <div><dt class="text-slate-500">National ID</dt><dd class="font-medium">{{ tenant.identity?.national_id || '—' }}</dd></div>
            <div><dt class="text-slate-500">Passport</dt><dd class="font-medium">{{ tenant.identity?.passport_number || '—' }}</dd></div>
            <div><dt class="text-slate-500">Tax number</dt><dd class="font-medium">{{ tenant.identity?.tax_number || '—' }}</dd></div>
            <div><dt class="text-slate-500">Nationality</dt><dd class="font-medium">{{ tenant.identity?.nationality || '—' }}</dd></div>
            <div><dt class="text-slate-500">Date of birth</dt><dd class="font-medium">{{ tenant.identity?.date_of_birth || '—' }}</dd></div>
            <div><dt class="text-slate-500">Occupation</dt><dd class="font-medium">{{ tenant.identity?.occupation || '—' }}</dd></div>
          </dl>
        </FormSection>

        <FormSection title="Address">
          <dl class="grid grid-cols-1 gap-3 text-sm">
            <div><dt class="text-slate-500">Street</dt><dd class="font-medium">{{ tenant.address?.address || '—' }}</dd></div>
            <div><dt class="text-slate-500">City / Country</dt><dd class="font-medium">{{ [tenant.address?.city, tenant.address?.country].filter(Boolean).join(', ') || '—' }}</dd></div>
            <div><dt class="text-slate-500">Postal code</dt><dd class="font-medium">{{ tenant.address?.postal_code || '—' }}</dd></div>
          </dl>
        </FormSection>

        <FormSection title="Emergency contact">
          <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
            <div><dt class="text-slate-500">Name</dt><dd class="font-medium">{{ tenant.emergency_contact?.name || '—' }}</dd></div>
            <div><dt class="text-slate-500">Phone</dt><dd class="font-medium">{{ tenant.emergency_contact?.phone || '—' }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-slate-500">Relationship</dt><dd class="font-medium">{{ tenant.emergency_contact?.relationship || '—' }}</dd></div>
          </dl>
        </FormSection>
      </div>

      <FormSection v-if="tenant.notes" title="Notes" class="mt-6">
        <p class="text-sm text-slate-600">{{ tenant.notes }}</p>
      </FormSection>
    </template>

    <template #agreements>
      <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-600">
          Rental agreements linked to this tenant.
        </p>
        <ErpButton
          size="sm"
          :to="{ name: 'RentalAgreementCreate', query: { tenant_id: tenant.id } }"
        >
          Create agreement
        </ErpButton>
      </div>

      <DataTable
        :columns="agreementColumns"
        :rows="agreements"
        :loading="agreementsLoading"
        empty-title="No agreements"
        empty-description="Create a rental agreement for this tenant."
        @row-click="openAgreement"
      >
        <template #emptyAction>
          <ErpButton
            :to="{ name: 'RentalAgreementCreate', query: { tenant_id: tenant.id } }"
          >
            Create agreement
          </ErpButton>
        </template>
        <template #cell-agreement_number="{ row }">
          <code class="text-xs font-medium">{{ row.agreement_number }}</code>
        </template>
        <template #cell-unit="{ row }">
          <span class="text-sm">{{ row.building_name }} · {{ row.unit_number }}</span>
        </template>
        <template #cell-rent="{ row }">
          <span class="tabular-nums">{{ formatMoney(row.monthly_rent) }}</span>
        </template>
        <template #cell-period="{ row }">
          <span class="text-xs text-slate-600">{{ row.start_date }} → {{ row.end_date || 'Open' }}</span>
        </template>
        <template #cell-status="{ row }">
          <StatusBadge :status="row.status" :label="row.status_label" />
        </template>
      </DataTable>
    </template>

    <template #billing>
      <p class="text-sm text-slate-600">
        View invoice history, outstanding balance, and payment status for this tenant.
      </p>
      <div class="mt-4 flex flex-wrap gap-2">
        <ErpButton :to="{ name: 'TenantBilling', params: { id: tenant.id } }">Open billing history</ErpButton>
        <ErpButton variant="secondary" :to="{ name: 'Payments', query: { tenant_id: tenant.id } }">
          Record payment
        </ErpButton>
      </div>
    </template>
  </ObjectPageLayout>

  <div v-else class="erp-page py-12 text-center text-slate-500">Tenant not found.</div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { tenantDisplayName } from '@/utils/tenantDisplayName'
import {
  ObjectPageLayout,
  ErpButton,
  FormSection,
  DataTable,
  StatusBadge,
  AlertBanner,
} from '@/components/erp'

const props = defineProps({ id: { type: [String, Number], required: true } })

const router = useRouter()
const tenant = ref(null)
const loading = ref(true)
const pageError = ref('')
const agreements = ref([])
const agreementsLoading = ref(false)

const title = computed(() => tenantDisplayName(tenant.value) || 'Tenant')
const status = computed(() => tenant.value?.status?.value ?? tenant.value?.status ?? 'inactive')
const statusLabel = computed(() => {
  const s = status.value
  return s ? String(s).charAt(0).toUpperCase() + String(s).slice(1) : '—'
})

const breadcrumbs = computed(() => [
  { label: 'Tenants', to: { name: 'Tenants' } },
  { label: title.value },
])

const attributes = computed(() => {
  if (!tenant.value) return []
  return [
    tenant.value.tenant_code,
    tenant.value.tenant_type,
    tenant.value.address?.city,
    agreements.value.length ? `${agreements.value.length} agreement(s)` : null,
  ].filter(Boolean)
})

const tabs = [
  { id: 'overview', label: 'Overview' },
  { id: 'agreements', label: 'Agreements' },
  { id: 'billing', label: 'Billing' },
]

const agreementColumns = [
  { key: 'agreement_number', label: 'Agreement', mono: true },
  { key: 'unit', label: 'Location', emphasis: true },
  { key: 'rent', label: 'Rent/mo', align: 'right' },
  { key: 'period', label: 'Period' },
  { key: 'status', label: 'Status' },
]

function mapAgreement(item) {
  const start = item.dates?.start_date || ''
  const end = item.dates?.end_date || ''
  const st = item.status?.value || item.status || 'draft'
  return {
    id: item.id,
    agreement_number: item.agreement_number || '—',
    building_name: item.apartment?.building?.name || '—',
    unit_number: item.apartment?.unit_number || '—',
    monthly_rent: item.financials?.monthly_rent ?? 0,
    start_date: start,
    end_date: end,
    status: st,
    status_label: item.status?.label || st,
  }
}

function formatMoney(v) {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number(v) || 0)
}

function openAgreement(row) {
  router.push({ name: 'RentalAgreementShow', params: { id: row.id } })
}

async function loadAgreements() {
  agreementsLoading.value = true
  try {
    const { data } = await api.get('/rental-agreements', {
      params: { tenant_id: props.id, per_page: 100 },
    })
    const rows = data.data ?? []
    agreements.value = rows.map(mapAgreement)
  } catch {
    agreements.value = []
  } finally {
    agreementsLoading.value = false
  }
}

onMounted(async () => {
  try {
    const { data } = await api.get(`/tenants/${props.id}`)
    tenant.value = data.data ?? data
    await loadAgreements()
  } catch (e) {
    pageError.value = e.response?.data?.message || 'Failed to load tenant.'
  } finally {
    loading.value = false
  }
})
</script>
