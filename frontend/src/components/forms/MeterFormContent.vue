<template>
  <div v-if="loading" class="py-8 text-center text-sm text-slate-500">Loading…</div>
  <form v-else class="meter-form-content" @submit.prevent="submit">
    <AlertBanner v-if="serverError" :message="serverError" variant="error" @dismiss="serverError = ''" />

    <FormSection
      compact
      title="Meter identification"
      description="Unique meter ID on the dial, register, or utility bill."
    >
      <FormGrid>
        <FormField label="Meter number" required span="2" :error="fieldError('meter_number')">
          <input
            ref="meterNumberInputRef"
            v-model="form.meter_number"
            type="text"
            name="meter_number"
            autocomplete="off"
            spellcheck="false"
            class="erp-input font-mono"
            placeholder="e.g. MTR-0001"
          />
        </FormField>
      </FormGrid>
    </FormSection>

    <FormSection
      compact
      title="Utility & readings"
      description="Utility type, unit of measure, and starting index at installation."
    >
      <FormGrid :cols="3">
        <FormField label="Utility type" required :error="fieldError('utility_type')">
          <select v-model="form.utility_type" class="erp-select">
            <option value="">Select…</option>
            <option v-for="u in UTILITY_TYPE_OPTIONS" :key="u.value" :value="u.value">{{ u.label }}</option>
          </select>
        </FormField>
        <FormField label="Measurement unit" required :error="fieldError('measurement_unit')">
          <select
            v-model="form.measurement_unit"
            class="erp-select"
            :disabled="!form.utility_type"
          >
            <option value="">{{ form.utility_type ? 'Select…' : 'Select utility first' }}</option>
            <option v-for="mu in filteredMeasurementUnits" :key="mu.value" :value="mu.value">
              {{ mu.label }}
            </option>
          </select>
        </FormField>
        <FormField label="Hardware type" required :error="fieldError('meter_type')">
          <select v-model="form.meter_type" class="erp-select">
            <option value="">Select…</option>
            <option v-for="mt in METER_TYPE_OPTIONS" :key="mt.value" :value="mt.value">{{ mt.label }}</option>
          </select>
        </FormField>
        <FormField
          label="Initial reading"
          :error="fieldError('initial_reading')"
          hint="Index at install"
        >
          <div class="flex max-w-xs gap-2 sm:max-w-none">
            <input
              v-model.number="form.initial_reading"
              type="number"
              step="0.0001"
              min="0"
              class="erp-input min-w-0 flex-1 tabular-nums"
              placeholder="0"
            />
            <span
              v-if="form.measurement_unit"
              class="inline-flex h-[42px] shrink-0 items-center rounded-lg border border-slate-200 bg-slate-50 px-3 text-xs font-semibold uppercase tracking-wide text-slate-600"
            >
              {{ form.measurement_unit }}
            </span>
          </div>
        </FormField>
        <FormField label="Status" :error="fieldError('status')">
          <select v-model="form.status" class="erp-select">
            <option value="active">Active</option>
            <option value="faulty">Faulty</option>
            <option value="under_maintenance">Maintenance</option>
            <option value="inactive">Inactive</option>
          </select>
        </FormField>
        <FormField label="Serial number" :error="fieldError('serial_number')">
          <input
            v-model="form.serial_number"
            type="text"
            class="erp-input font-mono"
            placeholder="Optional"
          />
        </FormField>
      </FormGrid>
    </FormSection>

    <FormSection
      compact
      title="Ownership & assignment"
      description="Who is billed — then link the building, unit, or tenant as needed."
      tinted
    >
      <FormGrid>
        <FormField
          label="Billed to"
          required
          span="2"
          :error="fieldError('ownership_type')"
          :hint="activeOwnershipHint"
        >
          <select v-model="form.ownership_type" class="erp-select">
            <option value="">Select ownership…</option>
            <option v-for="o in OWNERSHIP_TYPE_OPTIONS" :key="o.value" :value="o.value">
              {{ o.label }}
            </option>
          </select>
        </FormField>

        <FormField
          v-if="showBuildingField"
          label="Building"
          :required="buildingRequired"
          :span="showApartmentField ? '1' : '2'"
          :error="fieldError('building_id')"
        >
          <ErpSearchSelect
            v-model="form.building_id"
            :options="buildingOptions"
            :loading="buildingsLoading"
            remote
            :placeholder="buildingRequired ? 'Select building…' : '— None'"
            search-placeholder="Search name, code, city…"
            empty-text="No buildings match your search"
            :has-error="!!fieldError('building_id')"
            @search="onBuildingSearch"
          />
        </FormField>

        <FormField
          v-if="showApartmentField"
          label="Apartment / unit"
          required
          :span="showBuildingField ? '1' : '2'"
          :error="fieldError('apartment_id')"
          :hint="!form.building_id ? 'Select a building first' : ''"
        >
          <ErpSearchSelect
            v-model="form.apartment_id"
            :options="apartmentOptions"
            :loading="apartmentsLoading"
            :disabled="!form.building_id"
            remote
            placeholder="Select unit…"
            search-placeholder="Search unit…"
            :empty-text="apartmentEmptyText"
            @search="onApartmentSearch"
          />
        </FormField>

        <FormField
          v-if="showTenantField"
          label="Tenant"
          required
          span="2"
          :error="fieldError('tenant_id')"
        >
          <ErpSearchSelect
            v-model="form.tenant_id"
            :options="tenantOptions"
            :loading="tenantsLoading"
            remote
            placeholder="Select tenant…"
            search-placeholder="Search name, code, email…"
            empty-text="No tenants match your search"
            :has-error="!!fieldError('tenant_id')"
            @search="onTenantSearch"
          />
        </FormField>

        <FormField
          label="Location on site"
          span="2"
          :error="fieldError('location_description')"
          hint="Optional — e.g. basement riser, kitchen"
        >
          <input
            v-model="form.location_description"
            type="text"
            class="erp-input"
            placeholder="Physical location of the meter"
          />
        </FormField>
      </FormGrid>
    </FormSection>

    <FormSection
      compact
      title="Calibration & dates"
      description="Optional multiplier and installation schedule."
    >
      <FormGrid :cols="3">
        <FormField label="Multiplier" :error="fieldError('multiplier_factor')" hint="Default 1.0">
          <input
            v-model.number="form.multiplier_factor"
            type="number"
            step="0.0001"
            min="0.0001"
            class="erp-input tabular-nums"
            placeholder="1"
          />
        </FormField>
        <FormField label="Installation date" :error="fieldError('installation_date')">
          <ErpDateInput v-model="form.installation_date" placeholder="Date" />
        </FormField>
        <FormField label="Inspection due" :error="fieldError('inspection_due_date')">
          <ErpDateInput
            v-model="form.inspection_due_date"
            placeholder="Date"
            :min="form.installation_date || ''"
          />
        </FormField>
      </FormGrid>
    </FormSection>
  </form>
</template>

<script setup>
import { reactive, ref, watch, onMounted, toRef, computed, nextTick } from 'vue'
import api from '@/services/api'
import {
  FormSection,
  FormGrid,
  FormField,
  AlertBanner,
  ErpDateInput,
  ErpSearchSelect,
} from '@/components/erp'
import {
  UTILITY_TYPE_OPTIONS,
  METER_TYPE_OPTIONS,
  OWNERSHIP_TYPE_OPTIONS,
  ownershipNeedsBuilding,
  ownershipNeedsApartment,
  ownershipNeedsTenant,
  useMeterMeasurementUnits,
} from '@/composables/useMeterFormOptions'
import { useBuildingPicker } from '@/composables/useBuildingPicker'
import { useBuildingApartments } from '@/composables/useBuildingApartments'
import { useTenantPicker } from '@/composables/useTenantPicker'

const props = defineProps({ entityId: { default: null } })
const emit = defineEmits(['saved'])

const loading = ref(false)
const {
  buildings,
  loading: buildingsLoading,
  fetchBuildings,
  buildingToOption,
} = useBuildingPicker()
const {
  tenants,
  loading: tenantsLoading,
  fetchTenants,
  tenantToOption,
} = useTenantPicker()
const {
  apartments,
  loading: apartmentsLoading,
  fetchApartments,
  apartmentToOption,
} = useBuildingApartments()

const meterNumberInputRef = ref(null)
const buildingOptions = computed(() => buildings.value.map((b) => buildingToOption(b)))
const apartmentOptions = computed(() => apartments.value.map((a) => apartmentToOption(a)))
const tenantOptions = computed(() => tenants.value.map((t) => tenantToOption(t)))
const apartmentEmptyText = computed(() =>
  !form.building_id ? 'Select a building first' : 'No units match your search'
)

const showBuildingField = computed(() => ownershipNeedsBuilding(form.ownership_type))
const showApartmentField = computed(() => ownershipNeedsApartment(form.ownership_type))
const showTenantField = computed(() => ownershipNeedsTenant(form.ownership_type))
const buildingRequired = computed(() => showBuildingField.value)

const activeOwnershipHint = computed(() => {
  const o = OWNERSHIP_TYPE_OPTIONS.find((x) => x.value === form.ownership_type)
  return o?.hint ?? ''
})
const serverError = ref('')
const fieldErrors = ref({})

const defaults = () => ({
  meter_number: '',
  serial_number: '',
  utility_type: '',
  meter_type: '',
  measurement_unit: '',
  building_id: '',
  apartment_id: '',
  ownership_type: '',
  tenant_id: '',
  initial_reading: 0,
  multiplier_factor: 1,
  installation_date: '',
  inspection_due_date: '',
  status: 'active',
  location_description: '',
})

const form = reactive(defaults())

const { filteredMeasurementUnits, defaultUnitForUtility } = useMeterMeasurementUnits(toRef(form, 'utility_type'))

function fieldError(key) {
  const e = fieldErrors.value[key]
  return Array.isArray(e) ? e[0] : e || ''
}

let tenantSearchDebounce = null
function onTenantSearch(query) {
  clearTimeout(tenantSearchDebounce)
  tenantSearchDebounce = setTimeout(
    () => fetchTenants(query, { ensureId: form.tenant_id || undefined }),
    280
  )
}

let buildingSearchDebounce = null
function onBuildingSearch(query) {
  clearTimeout(buildingSearchDebounce)
  buildingSearchDebounce = setTimeout(
    () => fetchBuildings(query, { ensureId: form.building_id || undefined }),
    280
  )
}

let apartmentSearchDebounce = null
function onApartmentSearch(query) {
  clearTimeout(apartmentSearchDebounce)
  apartmentSearchDebounce = setTimeout(() => reloadApartments(query), 280)
}

async function reloadApartments(search = '') {
  if (!form.building_id) {
    apartments.value = []
    return
  }
  await fetchApartments(form.building_id, {
    search,
    mode: 'edit',
    ensureId: form.apartment_id || undefined,
  })
}

watch(
  () => form.building_id,
  (id, prev) => {
    if (prev && id !== prev) {
      form.apartment_id = ''
    }
    reloadApartments()
  }
)

watch(
  () => form.utility_type,
  (utility, prev) => {
    if (!utility) {
      form.measurement_unit = ''
      return
    }
    const allowed = filteredMeasurementUnits.value.map((u) => u.value)
    if (!form.measurement_unit || !allowed.includes(form.measurement_unit) || (prev && utility !== prev)) {
      form.measurement_unit = defaultUnitForUtility(utility)
    }
  }
)

watch(
  () => form.ownership_type,
  (type, prev) => {
    if (type === prev) return
    if (type !== 'tenant') {
      form.tenant_id = ''
    } else {
      fetchTenants('', { ensureId: form.tenant_id || undefined })
    }
    if (type !== 'apartment') form.apartment_id = ''
    if (!ownershipNeedsBuilding(type)) {
      form.building_id = ''
      form.apartment_id = ''
    }
    if (type === 'building') {
      form.apartment_id = ''
      form.tenant_id = ''
    }
    if (type === 'shared') {
      form.apartment_id = ''
      form.tenant_id = ''
    }
  }
)

function buildPayload() {
  const initialReading = Number(form.initial_reading) || 0
  const payload = {
    meter_number: form.meter_number,
    serial_number: form.serial_number || null,
    utility_type: form.utility_type || null,
    meter_type: form.meter_type || null,
    measurement_unit: form.measurement_unit || null,
    ownership_type: form.ownership_type || null,
    building_id: ownershipNeedsBuilding(form.ownership_type) ? form.building_id || null : null,
    apartment_id: ownershipNeedsApartment(form.ownership_type) ? form.apartment_id || null : null,
    tenant_id: ownershipNeedsTenant(form.ownership_type) ? form.tenant_id || null : null,
    initial_reading: initialReading,
    multiplier_factor: Number(form.multiplier_factor) || 1,
    installation_date: form.installation_date || null,
    inspection_due_date: form.inspection_due_date || null,
    status: form.status || 'active',
    location_description: form.location_description || null,
    is_shared: form.ownership_type === 'shared',
  }
  if (!props.entityId) {
    payload.current_reading = initialReading
  }
  return payload
}

async function load() {
  if (!props.entityId) {
    Object.assign(form, defaults())
    return
  }
  loading.value = true
  try {
    const { data } = await api.get(`/meters/${props.entityId}`)
    const m = data.data ?? data
    Object.assign(form, {
      meter_number: m.meter_number ?? '',
      serial_number: m.serial_number ?? '',
      utility_type: m.utility_type?.value ?? m.utility_type ?? '',
      meter_type: m.meter_type?.value ?? m.meter_type ?? '',
      measurement_unit: m.measurement_unit ?? '',
      building_id: m.building_id ?? m.building?.id ?? '',
      apartment_id: m.apartment_id ?? m.apartment?.id ?? '',
      ownership_type: m.ownership_type?.value ?? m.ownership_type ?? '',
      tenant_id: m.tenant_id ?? m.tenant?.id ?? '',
      initial_reading: m.readings?.initial_reading ?? m.readings?.current_reading ?? 0,
      multiplier_factor: m.readings?.multiplier_factor ?? m.multiplier_factor ?? 1,
      installation_date: m.lifecycle?.installation_date ?? m.dates?.installation_date ?? m.installation_date ?? '',
      inspection_due_date: m.lifecycle?.inspection_due_date ?? m.dates?.inspection_due_date ?? m.inspection_due_date ?? '',
      status: m.status?.value ?? m.status ?? 'active',
      location_description: m.location?.description ?? m.location_description ?? '',
    })
    await Promise.all([
      fetchBuildings('', { ensureId: form.building_id || undefined }),
      reloadApartments(),
      ownershipNeedsTenant(form.ownership_type)
        ? fetchTenants('', { ensureId: form.tenant_id || undefined })
        : Promise.resolve(),
    ])
  } finally {
    loading.value = false
  }
}

function validateClient() {
  const errors = {}
  const o = form.ownership_type

  if (!String(form.meter_number || '').trim()) {
    errors.meter_number = ['Meter number is required.']
  }
  if (!o) {
    errors.ownership_type = ['Select who is billed for this meter.']
    return errors
  }
  if (ownershipNeedsBuilding(o) && !form.building_id) {
    errors.building_id = ['Select the building for this meter.']
  }
  if (ownershipNeedsApartment(o)) {
    if (!form.apartment_id) {
      errors.apartment_id = ['Select the unit this meter serves.']
    } else if (!form.building_id) {
      errors.building_id = ['Select a building before choosing a unit.']
    }
  }
  if (ownershipNeedsTenant(o) && !form.tenant_id) {
    errors.tenant_id = ['Select the tenant billed for this meter.']
  }
  if (o === 'building' && (form.apartment_id || form.tenant_id)) {
    if (form.apartment_id) errors.apartment_id = ['Not used for whole-building meters.']
    if (form.tenant_id) errors.tenant_id = ['Not used for whole-building meters.']
  }
  if (o === 'shared' && !form.building_id) {
    errors.building_id = ['Shared meters must belong to a building.']
  }
  return errors
}

async function submit() {
  fieldErrors.value = {}
  serverError.value = ''
  const clientErrors = validateClient()
  if (Object.keys(clientErrors).length) {
    fieldErrors.value = clientErrors
    serverError.value = 'Please fix the highlighted fields.'
    return
  }
  const payload = buildPayload()
  try {
    if (props.entityId) {
      await api.put(`/meters/${props.entityId}`, payload)
    } else {
      await api.post('/meters', payload)
    }
    emit('saved')
  } catch (e) {
    if (e.response?.status === 422) {
      fieldErrors.value = e.response.data.errors || {}
      serverError.value = 'Please fix the highlighted fields.'
    } else {
      serverError.value = e.response?.data?.message || 'Save failed.'
    }
  }
}

onMounted(async () => {
  await load()
  await fetchBuildings('', { ensureId: form.building_id || undefined })
  if (!props.entityId) {
    await nextTick()
    meterNumberInputRef.value?.focus()
  }
})

defineExpose({ submit })
</script>
