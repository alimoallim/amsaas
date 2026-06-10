<template>
  <div v-if="loading" class="py-8 text-center text-sm text-slate-500">Loading…</div>
  <form v-else class="space-y-5" @submit.prevent="submit">
    <AlertBanner v-if="serverError" :message="serverError" variant="error" @dismiss="serverError = ''" />
    <AlertBanner
      v-if="entityId"
      variant="info"
      message="Correct the reading values and save. If this reading was approved, it returns to verified status and must be re-approved to regenerate utility charges."
      :dismissible="false"
    />
    <AlertBanner
      v-if="consumptionWarning && !fieldError('current_reading')"
      :message="consumptionWarning"
      variant="warning"
    />
    <AlertBanner
      v-if="existingDuplicate && !entityId"
      variant="warning"
      :dismissible="false"
    >
      <span>{{ duplicateMessage }}</span>
      <button
        type="button"
        class="ml-2 font-semibold underline hover:no-underline"
        @click="emit('edit-existing', existingDuplicate.id)"
      >
        Edit existing reading
      </button>
    </AlertBanner>

    <FormSection
      compact
      title="Property & meter"
      description="Select the building first, then choose the meter to read."
    >
      <FormGrid>
        <FormField
          label="Building"
          required
          span="2"
          :error="fieldError('building_id')"
        >
          <ErpSearchSelect
            v-model="selectedBuildingId"
            :options="buildingOptions"
            :loading="buildingsLoading"
            :disabled="!!entityId"
            remote
            placeholder="Select building…"
            search-placeholder="Search name, code, city…"
            empty-text="No buildings match your search"
            @search="onBuildingSearch"
          />
        </FormField>

        <FormField
          label="Meter"
          required
          span="2"
          :error="fieldError('meter_id')"
          :hint="!selectedBuildingId ? 'Select a building first' : ''"
        >
          <ErpSearchSelect
            v-model="form.meter_id"
            :options="meterOptions"
            :loading="metersLoading"
            :disabled="!!entityId || !selectedBuildingId"
            remote
            placeholder="Select meter…"
            search-placeholder="Search meter number…"
            :empty-text="meterEmptyText"
            :has-error="!!fieldError('meter_id')"
            @search="onMeterSearch"
          />
        </FormField>
      </FormGrid>
    </FormSection>

    <FormSection
      v-if="form.meter_id && meterContext"
      compact
      title="Reading baseline"
      description="Previous index from the last approved reading (or install reading)."
    >
      <div
        class="grid gap-3 rounded-lg border border-slate-200 bg-slate-50/80 p-4 sm:grid-cols-3"
      >
        <div>
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Previous reading</p>
          <p class="mt-1 font-mono text-lg font-semibold tabular-nums text-slate-900">
            {{ formatReading(meterContext.previousReading) }}
            <span v-if="meterContext.unit" class="text-sm font-normal text-slate-500">
              {{ meterContext.unit }}
            </span>
          </p>
        </div>
        <div>
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Current reading</p>
          <p class="mt-1 font-mono text-lg font-semibold tabular-nums text-slate-900">
            {{ currentReadingDisplay }}
            <span v-if="meterContext.unit" class="text-sm font-normal text-slate-500">
              {{ meterContext.unit }}
            </span>
          </p>
        </div>
        <div>
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Consumption</p>
          <p
            class="mt-1 font-mono text-lg font-semibold tabular-nums"
            :class="consumptionInvalid ? 'text-red-600' : 'text-emerald-700'"
          >
            {{ consumptionDisplay }}
            <span v-if="meterContext.unit" class="text-sm font-normal text-slate-500">
              {{ meterContext.unit }}
            </span>
          </p>
        </div>
      </div>
      <p v-if="meterContext.utilityLabel" class="mt-2 text-xs text-slate-500">
        {{ meterContext.utilityLabel }}
        <span v-if="meterContext.meterTypeLabel"> · {{ meterContext.meterTypeLabel }}</span>
      </p>
    </FormSection>

    <FormSection compact title="New reading" description="Enter the dial/register value for this period.">
      <FormGrid>
        <FormField label="Reading date" required :error="fieldError('reading_date')">
          <ErpDateInput v-model="form.reading_date" placeholder="Reading date" />
        </FormField>
        <FormField label="Current reading" required :error="fieldError('current_reading')">
          <input
            v-model="form.current_reading"
            type="number"
            step="0.0001"
            min="0"
            class="erp-input tabular-nums"
            :disabled="!form.meter_id"
            placeholder="Enter index on meter"
          />
        </FormField>
        <FormField label="Reading type" :error="fieldError('reading_type')">
          <select v-model="form.reading_type" class="erp-select">
            <option value="actual">Actual</option>
            <option value="estimated">Estimated</option>
          </select>
        </FormField>
        <FormField label="Notes" span="2" :error="fieldError('notes')">
          <textarea v-model="form.notes" rows="2" class="erp-input" placeholder="Optional notes" />
        </FormField>
      </FormGrid>
    </FormSection>
  </form>
</template>

<script setup>
import { reactive, ref, computed, watch, onMounted } from 'vue'
import api from '@/services/api'
import {
  FormSection,
  FormGrid,
  FormField,
  ErpDateInput,
  ErpSearchSelect,
  AlertBanner,
} from '@/components/erp'
import { useBuildingPicker } from '@/composables/useBuildingPicker'
import { useBuildingMeters } from '@/composables/useBuildingMeters'

const props = defineProps({ entityId: { default: null } })
const emit = defineEmits(['saved', 'edit-existing'])

const loading = ref(false)
const selectedBuildingId = ref('')
const meterContext = ref(null)
const existingDuplicate = ref(null)
const serverError = ref('')
const fieldErrors = ref({})

const duplicateMessage = computed(() => {
  if (!existingDuplicate.value) return ''
  const current = existingDuplicate.value.current_reading
  const status = existingDuplicate.value.status_label || existingDuplicate.value.status
  return `A reading for this meter on this date already exists (${current} · ${status}). Edit it or pick another date.`
})

const {
  buildings,
  loading: buildingsLoading,
  fetchBuildings,
  buildingToOption,
} = useBuildingPicker()

const {
  meters,
  loading: metersLoading,
  fetchMeters,
  meterToOption,
} = useBuildingMeters()

const buildingOptions = computed(() => buildings.value.map((b) => buildingToOption(b)))
const meterOptions = computed(() => meters.value.map((m) => meterToOption(m)))
const meterEmptyText = computed(() =>
  !selectedBuildingId.value ? 'Select a building first' : 'No meters for this building'
)

const defaults = () => ({
  meter_id: '',
  reading_date: new Date().toISOString().split('T')[0],
  current_reading: '',
  reading_type: 'actual',
  reading_source: 'manual',
  notes: '',
})

const form = reactive(defaults())

function fieldError(key) {
  const e = fieldErrors.value[key]
  return Array.isArray(e) ? e[0] : e || ''
}

function formatReading(value) {
  if (value === '' || value == null || Number.isNaN(Number(value))) {
    return '—'
  }
  return Number(value).toFixed(4)
}

const previousReadingNum = computed(() => {
  const v = meterContext.value?.previousReading
  return v == null || v === '' ? null : Number(v)
})

const currentReadingNum = computed(() => {
  const v = form.current_reading
  if (v === '' || v == null) return null
  const n = Number(v)
  return Number.isNaN(n) ? null : n
})

const consumptionValue = computed(() => {
  if (previousReadingNum.value == null || currentReadingNum.value == null) {
    return null
  }
  return currentReadingNum.value - previousReadingNum.value
})

const consumptionInvalid = computed(
  () => consumptionValue.value != null && consumptionValue.value < 0
)

const consumptionDisplay = computed(() => {
  if (currentReadingNum.value == null) return '—'
  if (consumptionValue.value == null) return '—'
  return formatReading(consumptionValue.value)
})

const currentReadingDisplay = computed(() => {
  if (currentReadingNum.value == null) return '—'
  return formatReading(currentReadingNum.value)
})

const consumptionWarning = computed(() => {
  if (consumptionInvalid.value) {
    return 'Current reading cannot be less than the previous reading.'
  }
  if (consumptionValue.value != null && consumptionValue.value === 0) {
    return 'Zero consumption for this period — confirm the reading is correct.'
  }
  if (consumptionValue.value != null && consumptionValue.value > 10000) {
    return 'Unusually high consumption — verify before submitting.'
  }
  return ''
})

let buildingSearchDebounce = null
function onBuildingSearch(query) {
  clearTimeout(buildingSearchDebounce)
  buildingSearchDebounce = setTimeout(
    () => fetchBuildings(query, { ensureId: selectedBuildingId.value || undefined }),
    280
  )
}

let meterSearchDebounce = null
function onMeterSearch(query) {
  clearTimeout(meterSearchDebounce)
  meterSearchDebounce = setTimeout(() => {
    if (!selectedBuildingId.value) return
    fetchMeters(selectedBuildingId.value, {
      search: query,
      ensureId: form.meter_id || undefined,
    })
  }, 280)
}

async function loadMeterContext(meterId) {
  if (!meterId) {
    meterContext.value = null
    return
  }
  try {
    const { data } = await api.get(`/meters/${meterId}`)
    const m = data.data ?? data
    const previous =
      m.readings?.previous_reading_value ??
      m.previous_reading_value ??
      m.initial_reading ??
      0
    meterContext.value = {
      previousReading: Number(previous),
      unit: m.measurement_unit ?? '',
      utilityLabel: m.utility_type?.label ?? m.utility_type ?? '',
      meterTypeLabel: m.meter_type?.label ?? m.meter_type ?? '',
    }
  } catch {
    meterContext.value = null
  }
}

watch(selectedBuildingId, (id, prev) => {
  if (prev && id !== prev) {
    form.meter_id = ''
    meterContext.value = null
  }
  if (!id) {
    meters.value = []
    return
  }
  fetchMeters(id, { ensureId: form.meter_id || undefined })
})

watch(
  () => form.meter_id,
  (id) => {
    loadMeterContext(id)
    checkDuplicateReading()
  }
)

watch(
  () => form.reading_date,
  () => {
    checkDuplicateReading()
  }
)

async function checkDuplicateReading() {
  existingDuplicate.value = null
  if (props.entityId || !form.meter_id || !form.reading_date) {
    return
  }
  try {
    const { data } = await api.get('/meter-readings', {
      params: {
        meter_id: form.meter_id,
        reading_date: form.reading_date,
        per_page: 1,
      },
    })
    const row = data.data?.[0]
    if (row?.id) {
      existingDuplicate.value = {
        id: row.id,
        current_reading: row.reading?.current_reading ?? row.current_reading,
        status: row.status?.value ?? row.status,
        status_label: row.status?.label,
      }
    }
  } catch {
    existingDuplicate.value = null
  }
}

function validateClient() {
  const errors = {}
  if (!props.entityId && !selectedBuildingId.value) {
    errors.building_id = ['Select the building where this meter is installed.']
  }
  if (!form.meter_id) {
    errors.meter_id = ['Select the meter you are reading.']
  }
  if (currentReadingNum.value == null) {
    errors.current_reading = ['Enter the current meter reading.']
  } else if (consumptionInvalid.value) {
    errors.current_reading = ['Current reading cannot be less than the previous reading.']
  }
  return errors
}

async function load() {
  if (!props.entityId) {
    Object.assign(form, defaults())
    selectedBuildingId.value = ''
    meterContext.value = null
    return
  }
  loading.value = true
  try {
    const { data } = await api.get(`/meter-readings/${props.entityId}`)
    const r = data.data ?? data
    const buildingId = r.building_id ?? r.building?.id ?? r.meter?.building?.id ?? ''
    selectedBuildingId.value = buildingId
    Object.assign(form, {
      meter_id: r.meter_id ?? r.meter?.id ?? '',
      reading_date: (r.reading?.reading_date ?? r.reading_date ?? '').toString().slice(0, 10),
      current_reading: r.reading?.current_reading ?? r.current_reading ?? '',
      reading_type: r.reading_type?.value ?? r.reading_type ?? 'actual',
      notes: r.notes ?? '',
    })
    await Promise.all([
      fetchBuildings('', { ensureId: buildingId || undefined }),
      buildingId
        ? fetchMeters(buildingId, { ensureId: form.meter_id || undefined })
        : Promise.resolve(),
      loadMeterContext(form.meter_id),
    ])
  } finally {
    loading.value = false
  }
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
  if (existingDuplicate.value && !props.entityId) {
    fieldErrors.value = {
      reading_date: [
        'A reading for this meter already exists on this date. Edit the existing reading or choose a different date.',
      ],
    }
    serverError.value = fieldErrors.value.reading_date[0]
    return
  }
  try {
    const payload = { ...form, current_reading: Number(form.current_reading) }
    if (props.entityId) {
      await api.put(`/meter-readings/${props.entityId}`, payload)
    } else {
      await api.post('/meter-readings', payload)
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

watch(
  () => props.entityId,
  async () => {
    await load()
  }
)

onMounted(async () => {
  await fetchBuildings()
  await load()
})

defineExpose({ submit })
</script>
