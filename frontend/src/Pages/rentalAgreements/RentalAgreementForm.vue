<template>
  <component
    :is="transactional ? 'div' : 'form'"
    class="space-y-0"
    v-bind="transactional ? {} : { onSubmit: (e) => { e.preventDefault(); handleSubmit() } }"
  >
    <AlertBanner
      v-if="isEdit && initialStatus === 'active' && !isReadonly"
      variant="warning"
      :dismissible="false"
      class="mb-0 rounded-none border-x-0 border-t-0"
      message="Active agreement — unit, tenant, and dates can be corrected. Draft invoices update automatically; issued invoices are not modified."
    />

    <FormSection compact title="Agreement status" description="Lifecycle state for this contract">
      <div class="flex flex-wrap gap-2">
        <button
          v-for="s in statusOptionsForMode"
          :key="s.value"
          type="button"
          :disabled="isReadonly"
          :class="[
            'inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-medium transition',
            form.status === s.value
              ? 'border-indigo-600 bg-indigo-50 text-indigo-800'
              : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 disabled:opacity-50',
          ]"
          @click="!isReadonly && (form.status = s.value)"
        >
          <span
            class="h-2 w-2 rounded-full"
            :class="statusDotClass(s.value)"
          />
          {{ s.label }}
        </button>
      </div>
      <p v-if="fieldError('status')" class="mt-2 text-xs text-red-600">{{ fieldError('status') }}</p>
    </FormSection>

    <FormSection compact title="Property & tenant" description="Building, unit, and lessee assignment">
      <FormGrid cols="3">
        <FormField label="Building" required :error="fieldError('building_id')">
          <select
            v-model="selectedBuildingId"
            :disabled="isReadonly"
            class="erp-select w-full"
          >
            <option value="">Select building…</option>
            <option v-for="building in buildings" :key="building.id" :value="building.id">
              {{ buildingLabel(building) }}
            </option>
          </select>
          <p v-if="!buildings.length" class="mt-1 text-xs text-amber-700">No buildings available</p>
        </FormField>

        <FormField label="Apartment / unit" required :error="fieldError('apartment_id')">
          <ErpSearchSelect
            v-model="form.apartment_id"
            :options="apartmentOptions"
            :disabled="isReadonly || !selectedBuildingId"
            :loading="apartmentsLoading"
            remote
            placeholder="Select unit…"
            search-placeholder="Search unit number, floor…"
            :empty-text="apartmentEmptyText"
            :has-error="!!fieldError('apartment_id')"
            @search="onApartmentSearch"
          />
          <p v-if="!selectedBuildingId" class="mt-1 text-xs text-slate-500">Choose a building first</p>
        </FormField>

        <FormField label="Tenant" required :error="fieldError('tenant_id')">
          <select
            v-model="form.tenant_id"
            :disabled="isReadonly"
            class="erp-select w-full"
          >
            <option value="">Select tenant…</option>
            <option v-for="tenant in tenants" :key="tenant.id" :value="tenant.id">
              {{ tenantLabel(tenant) }}
            </option>
          </select>
          <p v-if="!tenants.length" class="mt-1 text-xs text-amber-700">No tenants available</p>
        </FormField>
      </FormGrid>
    </FormSection>

    <FormSection compact title="Dates & terms" description="Lease period, payment schedule, and renewal">
      <FormGrid cols="3">
        <FormField label="Start date" required :error="fieldError('start_date')">
          <ErpDateInput v-model="form.start_date" :disabled="isReadonly" placeholder="Start" />
        </FormField>

        <FormField label="End date" :error="fieldError('end_date')">
          <ErpDateInput
            v-model="form.end_date"
            :disabled="isReadonly"
            placeholder="Open-ended"
            :min="form.start_date || ''"
          />
        </FormField>

        <FormField label="Payment due day" :error="fieldError('payment_due_day')" hint="Day of month (1–28)">
          <input
            v-model.number="form.payment_due_day"
            type="number"
            min="1"
            max="28"
            :disabled="isReadonly"
            class="erp-input w-full"
          />
        </FormField>

        <FormField label="Renewal notice (days)" hint="Days before lease end">
          <input
            v-model.number="form.renewal_notice_days"
            type="number"
            min="1"
            max="365"
            :disabled="isReadonly"
            class="erp-input w-full"
          />
        </FormField>

        <FormField label="Auto renewal">
          <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-slate-700">
            <input
              v-model="form.auto_renew"
              type="checkbox"
              :disabled="isReadonly"
              class="rounded border-slate-300 text-indigo-600"
            />
            {{ form.auto_renew ? 'Enabled' : 'Disabled' }}
          </label>
        </FormField>
      </FormGrid>
    </FormSection>

    <FormSection compact title="Charges & billing" description="Rent, security deposit, and recurring charge models">
      <FormGrid cols="3">
        <FormField label="Monthly rent" required :error="fieldError('monthly_rent')">
          <div class="flex">
            <span class="inline-flex items-center rounded-l-lg border border-r-0 border-slate-300 bg-slate-50 px-3 text-sm text-slate-600">
              {{ form.currency || 'USD' }}
            </span>
            <input
              v-model="form.monthly_rent"
              type="number"
              step="0.01"
              min="0"
              :disabled="isReadonly"
              placeholder="0.00"
              class="erp-input w-full rounded-l-none"
            />
          </div>
        </FormField>

        <FormField
          label="Security deposit"
          :error="fieldError('security_deposit')"
          hint="Contractual amount — receive and refund on the agreement page after activation"
        >
          <div class="flex">
            <span class="inline-flex items-center rounded-l-lg border border-r-0 border-slate-300 bg-slate-50 px-3 text-sm text-slate-600">
              {{ form.currency || 'USD' }}
            </span>
            <input
              v-model="form.security_deposit"
              type="number"
              step="0.01"
              min="0"
              :disabled="isReadonly"
              placeholder="0.00"
              class="erp-input w-full rounded-l-none"
            />
          </div>
        </FormField>

        <FormField label="Currency">
          <select v-model="form.currency" :disabled="isReadonly" class="erp-select w-full">
            <option v-for="c in currencyOptions" :key="c" :value="c">{{ c }}</option>
          </select>
        </FormField>

        <FormField label="Rent charge model" span="2" hint="Optional override of the default active rent model">
          <select
            v-model="form.rent_charge_model_id"
            :disabled="!canEditBilling || !rentChargeModels.length"
            class="erp-select w-full"
          >
            <option value="">Default active rent model</option>
            <option v-for="m in rentChargeModels" :key="m.id" :value="m.id">
              {{ m.name }} ({{ m.code }})
            </option>
          </select>
          <p v-if="!rentChargeModels.length" class="mt-1 text-xs text-amber-700">
            Create an active rent charge model under Finance → Charge Models.
          </p>
        </FormField>

        <FormField label="Rent billed">
          <p class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium tabular-nums text-slate-900">
            {{ form.currency || 'USD' }} {{ form.monthly_rent || '0' }} / mo
          </p>
        </FormField>
      </FormGrid>

      <div class="mt-6 space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <div>
            <p class="text-sm font-medium text-slate-900">Additional charge models</p>
            <p class="text-xs text-slate-500">
              Flat fees, fixed amounts, or metered utilities (billed from approved readings).
            </p>
          </div>
          <ErpButton
            v-if="canEditBilling"
            type="button"
            variant="ghost"
            size="sm"
            @click="addChargeRow"
          >
            Add charge model
          </ErpButton>
        </div>

        <div
          v-if="!form.recurring_charges?.length"
          class="rounded-lg border border-dashed border-slate-200 bg-slate-50/60 px-4 py-6 text-center text-sm text-slate-500"
        >
          No additional charge models. Add fees or metered utilities from Finance → Charge Models.
        </div>

        <div
          v-for="(row, index) in form.recurring_charges"
          :key="row.id || `new-${index}`"
          class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm"
        >
          <div class="mb-3 flex items-center justify-between gap-2">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
              Line {{ index + 1 }}
            </p>
            <ErpButton
              v-if="canEditBilling"
              type="button"
              variant="danger"
              size="sm"
              @click="removeRecurringRow(index)"
            >
              Remove
            </ErpButton>
          </div>
          <FormGrid cols="3">
            <FormField
              label="Charge model"
              span="2"
              :error="recurringRowError(index, 'charge_model_id')"
            >
              <select
                v-model="row.charge_model_id"
                :disabled="!canEditBilling"
                class="erp-select w-full"
                @change="onRecurringModelChange(row)"
              >
                <option value="">Select…</option>
                <option
                  v-for="m in modelsForRow(row, index)"
                  :key="m.id"
                  :value="m.id"
                >
                  {{ m.name }} — {{ chargeModelPolicyLabel(m) }}
                </option>
              </select>
            </FormField>

            <FormField
              v-if="rowNeedsAmount(resolveModel(row))"
              label="Monthly amount"
              required
              :error="recurringRowError(index, 'override_amount')"
            >
              <div class="flex">
                <span class="inline-flex items-center rounded-l-lg border border-r-0 border-slate-300 bg-slate-50 px-3 text-sm text-slate-600">
                  {{ form.currency || 'USD' }}
                </span>
                <input
                  v-model.number="row.override_amount"
                  type="number"
                  step="0.01"
                  min="0"
                  :disabled="!canEditBilling"
                  class="erp-input w-full rounded-l-none"
                />
              </div>
            </FormField>

            <FormField
              v-if="rowNeedsUnitRate(resolveModel(row))"
              label="Unit rate override"
              hint="Leave blank to use the charge model default"
            >
              <input
                v-model.number="row.override_unit_rate"
                type="number"
                step="0.0001"
                min="0"
                :disabled="!canEditBilling"
                :placeholder="resolveModel(row)?.unit_rate != null ? String(resolveModel(row).unit_rate) : 'Model default'"
                class="erp-input w-full"
              />
            </FormField>

            <FormField label="Label (optional)">
              <input
                v-model="row.custom_name"
                type="text"
                :disabled="!canEditBilling"
                class="erp-input w-full"
                :placeholder="row.preferMetered ? 'e.g. Unit electricity' : 'e.g. Security service'"
              />
            </FormField>
          </FormGrid>
        </div>
      </div>
    </FormSection>

    <footer
      v-if="!isReadonly && !transactional"
      class="flex flex-col-reverse gap-2 border-t border-slate-100 px-4 py-4 sm:flex-row sm:justify-end sm:px-5"
    >
      <ErpButton type="button" variant="secondary" @click="$emit('cancel')">Cancel</ErpButton>
      <ErpButton type="submit" variant="primary" :loading="loading" :disabled="loading">
        {{ isEdit ? 'Update agreement' : 'Create agreement' }}
      </ErpButton>
    </footer>
  </component>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import api from '@/services/api'
import { useConfirm } from '@/composables/useConfirm'
import {
  AlertBanner,
  ErpButton,
  ErpDateInput,
  ErpSearchSelect,
  FormField,
  FormGrid,
  FormSection,
} from '@/components/erp'
import { useBuildingApartments } from '@/composables/useBuildingApartments'
import { tenantDisplayName } from '@/utils/tenantDisplayName'
import {
  emptyRecurringChargeRow,
  chargeModelPolicyLabel,
  rowNeedsAmount,
  rowNeedsUnitRate,
} from '@/utils/rentalAgreementBilling'

const props = defineProps({
  form: { type: Object, required: true },
  errors: { type: Object, default: () => ({}) },
  loading: { type: Boolean, default: false },
  mode: { type: String, default: 'create' },
  buildings: { type: Array, default: () => [] },
  /** @deprecated use buildings + building-scoped fetch */
  apartments: { type: Array, default: () => [] },
  tenants: { type: Array, default: () => [] },
  initialBuildingId: { type: [String, Number], default: '' },
  initialStatus: { type: String, default: '' },
  embedded: { type: Boolean, default: false },
  transactional: { type: Boolean, default: false },
})

const emit = defineEmits(['submit', 'cancel'])

const { confirm } = useConfirm()

const selectedBuildingId = ref('')
const { apartments: buildingApartments, loading: apartmentsLoading, fetchApartments, apartmentToOption } = useBuildingApartments()

const apartmentOptions = computed(() =>
  buildingApartments.value.map((apt) => apartmentToOption(apt)),
)

const apartmentEmptyText = computed(() => {
  if (!selectedBuildingId.value) return 'Select a building first'
  return props.mode === 'create'
    ? 'No available rental units in this building'
    : 'No rental units found in this building'
})

let apartmentSearchDebounce = null
function onApartmentSearch(query) {
  clearTimeout(apartmentSearchDebounce)
  apartmentSearchDebounce = setTimeout(() => reloadApartments(query), 280)
}

async function reloadApartments(search = '') {
  if (!selectedBuildingId.value) return
  await fetchApartments(selectedBuildingId.value, {
    search,
    mode: props.mode,
    ensureId: props.form.apartment_id || undefined,
  })
}

function buildingLabel(building) {
  const parts = [building.name, building.city, building.code].filter(Boolean)
  return parts.join(' · ')
}

function fieldError(key) {
  const err = props.errors[key]
  return Array.isArray(err) ? err[0] : err || ''
}

function statusDotClass(value) {
  const map = {
    draft: 'bg-slate-400',
    pending: 'bg-amber-500',
    active: 'bg-emerald-500',
    terminated: 'bg-red-500',
    expired: 'bg-slate-500',
  }
  return map[value] || 'bg-slate-400'
}

watch(
  () => props.initialBuildingId,
  (id) => {
    if (id && String(id) !== selectedBuildingId.value) {
      selectedBuildingId.value = String(id)
    }
  },
  { immediate: true },
)

watch(selectedBuildingId, async (id, prev) => {
  if (!id) {
    buildingApartments.value = []
    return
  }
  if (prev && id !== prev) {
    props.form.apartment_id = ''
  }
  await reloadApartments()
})

const isReadonly = computed(() => props.mode === 'readonly')
const isEdit = computed(() => props.mode === 'edit')
const canEditBilling = computed(() => !isReadonly.value)

const coreBaseline = ref(null)

function captureCoreBaseline() {
  if (coreBaseline.value || !isEdit.value) return
  if (!props.form.apartment_id && !props.form.start_date) return

  coreBaseline.value = {
    apartment_id: String(props.form.apartment_id || ''),
    tenant_id: String(props.form.tenant_id || ''),
    start_date: props.form.start_date || '',
  }
}

watch(
  () => [props.form.apartment_id, props.form.tenant_id, props.form.start_date],
  captureCoreBaseline,
  { immediate: true },
)

function hasCriticalCoreChanges() {
  if (!coreBaseline.value) return false

  return (
    String(props.form.apartment_id || '') !== coreBaseline.value.apartment_id
    || String(props.form.tenant_id || '') !== coreBaseline.value.tenant_id
    || (props.form.start_date || '') !== coreBaseline.value.start_date
  )
}

function criticalChangeMessages() {
  const lines = []
  if (!coreBaseline.value) return lines

  if (String(props.form.apartment_id || '') !== coreBaseline.value.apartment_id) {
    lines.push('Unit assignment will change; draft invoices will point to the new unit.')
  }
  if (String(props.form.tenant_id || '') !== coreBaseline.value.tenant_id) {
    lines.push('Tenant assignment will change for this agreement.')
  }
  if ((props.form.start_date || '') !== coreBaseline.value.start_date) {
    lines.push('Start date will change; charge billing schedules will be recalculated.')
  }

  return lines
}

const chargeModels = ref([])

const rentChargeModels = computed(() =>
  chargeModels.value.filter((m) => m.pricing_strategy === 'agreement_rent'),
)

const recurringChargeModels = computed(() =>
  chargeModels.value.filter((m) => m.pricing_strategy !== 'agreement_rent'),
)

function modelsForRow(row, index) {
  const usedElsewhere = new Set(
    (props.form.recurring_charges ?? [])
      .filter((_, i) => i !== index)
      .map((r) => r.charge_model_id)
      .filter(Boolean),
  )

  return recurringChargeModels.value.filter(
    (m) => m.id === row.charge_model_id || !usedElsewhere.has(m.id),
  )
}

async function loadChargeModels() {
  try {
    const { data } = await api.get('/charge-models', {
      params: { status: 'active', per_page: 50 },
    })
    chargeModels.value = data.data ?? []
  } catch (error) {
    chargeModels.value = []
    if (error.response?.status !== 403) {
      console.warn('Could not load charge models for billing section.', error.response?.status)
    }
  }
}

function ensureBillingFields() {
  if (!Array.isArray(props.form.recurring_charges)) {
    props.form.recurring_charges = []
  }
  if (props.form.rent_charge_model_id === undefined) {
    props.form.rent_charge_model_id = ''
  }
}

function addChargeRow() {
  ensureBillingFields()
  props.form.recurring_charges.push(emptyRecurringChargeRow())
}

function removeRecurringRow(index) {
  props.form.recurring_charges.splice(index, 1)
}

function resolveModel(row) {
  return chargeModels.value.find((m) => m.id === row.charge_model_id) ?? null
}

function onRecurringModelChange(row) {
  const model = resolveModel(row)
  if (!model) return
  row.preferMetered = model.pricing_strategy === 'metered'
  if (!rowNeedsAmount(model)) row.override_amount = null
  if (!rowNeedsUnitRate(model)) row.override_unit_rate = null
}

function recurringRowError(index, field) {
  const key = `recurring_charges.${index}.${field}`
  return fieldError(key)
}

const allStatusOptions = [
  { value: 'draft', label: 'Draft' },
  { value: 'pending', label: 'Pending' },
  { value: 'active', label: 'Active' },
  { value: 'terminated', label: 'Terminated' },
  { value: 'expired', label: 'Expired' },
]

const statusOptionsForMode = computed(() => {
  if (props.mode === 'create') {
    return allStatusOptions.filter((s) =>
      ['draft', 'pending', 'active'].includes(s.value),
    )
  }
  return allStatusOptions
})

const currencyOptions = ['USD', 'EUR', 'GBP', 'AED', 'SAR', 'TRY', 'KES', 'NGN', 'ZAR', 'EGP', 'SOS']

function tenantLabel(tenant) {
  return tenantDisplayName(tenant) || tenant.email || tenant.tenant_code || tenant.id
}

async function handleSubmit() {
  if (Array.isArray(props.form.recurring_charges)) {
    props.form.recurring_charges = props.form.recurring_charges.filter(
      (row) => row.charge_model_id,
    )
  }
  if (!props.form.status || !statusOptionsForMode.value.some((s) => s.value === props.form.status)) {
    props.form.status = 'draft'
  }

  let confirmCriticalChanges = false

  if (isEdit.value && props.initialStatus === 'active' && hasCriticalCoreChanges()) {
    const detail = criticalChangeMessages().join(' ')
    const ok = await confirm({
      title: 'Confirm agreement changes',
      message: detail
        ? `${detail} Issued invoices will not be changed. Save anyway?`
        : 'These changes may affect future billing. Save anyway?',
      confirmLabel: 'Save changes',
      variant: 'primary',
    })
    if (!ok) return
    confirmCriticalChanges = true
  }

  emit('submit', { confirmCriticalChanges })
}

onMounted(() => {
  ensureBillingFields()
  loadChargeModels()
})

defineExpose({ handleSubmit })
</script>
