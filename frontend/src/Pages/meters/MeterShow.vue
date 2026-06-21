<template>
  <AlertBanner
    v-if="error"
    variant="error"
    class="erp-page mb-4"
    :message="error"
    @dismiss="error = ''"
  />

  <div v-if="loading" class="erp-page py-12 text-center text-slate-500">Loading meter…</div>

  <ObjectPageLayout
    v-else-if="meter.id"
    :breadcrumbs="breadcrumbs"
    :title="meter.meter_number"
    :subtitle="meterSubtitle"
    :status="meter.status?.value"
    :status-label="meter.status?.label"
    :attributes="attributes"
    :tabs="tabs"
    initial-tab="overview"
  >
    <template #actions>
      <ErpButton variant="ghost" size="sm" :to="{ name: 'Meters' }">Back</ErpButton>
      <ErpButton variant="secondary" size="sm" :to="{ name: 'MeterReadingCreate' }">Capture reading</ErpButton>
      <ErpButton
        v-if="meter.status?.value !== 'active'"
        variant="success"
        size="sm"
        :loading="acting"
        @click="lifecycleAction('activate')"
      >
        Activate
      </ErpButton>
      <ErpButton
        v-if="meter.status?.value !== 'faulty'"
        variant="warning"
        size="sm"
        :loading="acting"
        @click="lifecycleAction('faulty')"
      >
        Mark faulty
      </ErpButton>
      <ErpButton
        v-if="meter.status?.value !== 'under_maintenance'"
        variant="secondary"
        size="sm"
        :loading="acting"
        @click="lifecycleAction('maintenance')"
      >
        Maintenance
      </ErpButton>
      <ErpButton variant="secondary" size="sm" :loading="acting" @click="lifecycleAction('inspection/complete')">
        Complete inspection
      </ErpButton>
      <ErpButton variant="danger" size="sm" :loading="acting" @click="onDecommission">Decommission</ErpButton>
      <ErpButton size="sm" :to="{ name: 'MeterEdit', params: { id: meter.id } }">Edit</ErpButton>
    </template>

    <template #overview>
      <KpiStrip class="mb-6 grid-cols-2 lg:grid-cols-4">
        <KpiCard label="Current reading" :value="formatNumber(meter.readings?.current_reading)" :caption="meter.measurement_unit || 'units'" />
        <KpiCard label="Multiplier" :value="meter.readings?.multiplier_factor ?? 1" caption="Billing scale" />
        <KpiCard
          label="Inspection due"
          :value="formatDate(meter.lifecycle?.inspection_due_date) || '—'"
          :caption="meter.lifecycle?.inspection_due ? 'Overdue' : 'Scheduled'"
        />
        <KpiCard label="Last reading" :value="formatDate(meter.readings?.last_reading_at) || '—'" caption="Activity" />
      </KpiStrip>

      <div class="grid gap-6 lg:grid-cols-2">
        <FormSection title="Meter information">
          <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
            <div><dt class="text-slate-500">Meter number</dt><dd class="font-mono font-medium">{{ meter.meter_number }}</dd></div>
            <div><dt class="text-slate-500">Serial number</dt><dd class="font-medium">{{ meter.serial_number || '—' }}</dd></div>
            <div><dt class="text-slate-500">Utility</dt><dd class="font-medium capitalize">{{ meter.utility_type?.label || '—' }}</dd></div>
            <div><dt class="text-slate-500">Meter type</dt><dd class="font-medium capitalize">{{ meter.meter_type?.label || '—' }}</dd></div>
            <div><dt class="text-slate-500">Ownership</dt><dd class="font-medium capitalize">{{ meter.ownership_type?.label || '—' }}</dd></div>
            <div><dt class="text-slate-500">Measurement unit</dt><dd class="font-medium">{{ meter.measurement_unit || '—' }}</dd></div>
          </dl>
        </FormSection>

        <FormSection title="Property assignment">
          <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
            <div>
              <dt class="text-slate-500">Building</dt>
              <dd class="font-medium">
                <RouterLink
                  v-if="meter.building?.id"
                  :to="{ name: 'BuildingShow', params: { id: meter.building.id } }"
                  class="text-indigo-600 hover:underline"
                >
                  {{ meter.building.name }}
                </RouterLink>
                <span v-else>—</span>
              </dd>
            </div>
            <div>
              <dt class="text-slate-500">Unit</dt>
              <dd class="font-medium">
                <RouterLink
                  v-if="meter.apartment?.id"
                  :to="{ name: 'ApartmentShow', params: { id: meter.apartment.id } }"
                  class="text-indigo-600 hover:underline"
                >
                  {{ meter.apartment.unit_number }}
                </RouterLink>
                <span v-else>—</span>
              </dd>
            </div>
            <div><dt class="text-slate-500">Tenant</dt><dd class="font-medium">{{ meter.tenant?.name || '—' }}</dd></div>
            <div><dt class="text-slate-500">Shared meter</dt><dd class="font-medium">{{ meter.operational_indicators?.is_shared ? 'Yes' : 'No' }}</dd></div>
          </dl>
        </FormSection>

        <FormSection title="Lifecycle">
          <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
            <div><dt class="text-slate-500">Installed</dt><dd class="font-medium">{{ formatDate(meter.lifecycle?.installation_date) || '—' }}</dd></div>
            <div><dt class="text-slate-500">Last maintenance</dt><dd class="font-medium">{{ formatDate(meter.lifecycle?.last_maintenance_at) || '—' }}</dd></div>
            <div><dt class="text-slate-500">Last inspection</dt><dd class="font-medium">{{ formatDate(meter.lifecycle?.last_inspected_at) || '—' }}</dd></div>
            <div><dt class="text-slate-500">Maintenance required</dt><dd class="font-medium">{{ meter.lifecycle?.maintenance_required ? 'Yes' : 'No' }}</dd></div>
          </dl>
        </FormSection>

        <FormSection v-if="meter.smart_features?.is_smart_meter" title="Smart features">
          <dl class="grid grid-cols-1 gap-3 text-sm">
            <div><dt class="text-slate-500">Remote reading</dt><dd class="font-medium">{{ meter.smart_features?.supports_remote_reading ? 'Supported' : 'Not supported' }}</dd></div>
          </dl>
        </FormSection>
      </div>

      <FormSection v-if="meter.notes" title="Notes" class="mt-6">
        <p class="text-sm text-slate-600">{{ meter.notes }}</p>
      </FormSection>
    </template>

    <template #readings>
      <p class="text-sm text-slate-600">Capture consumption or review historical readings for this meter.</p>
      <div class="mt-4 flex flex-wrap gap-2">
        <ErpButton :to="{ name: 'MeterReadingCreate' }">Capture reading</ErpButton>
        <ErpButton variant="secondary" :to="{ name: 'MeterReadings' }">All readings</ErpButton>
      </div>
    </template>
  </ObjectPageLayout>

  <div v-else class="erp-page py-12 text-center text-slate-500">Meter not found.</div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import api from '@/services/api'
import { useConfirm } from '@/composables/useConfirm'
import {
  ObjectPageLayout,
  ErpButton,
  FormSection,
  KpiCard,
  KpiStrip,
  AlertBanner,
} from '@/components/erp'

const route = useRoute()
const { confirm } = useConfirm()

const loading = ref(true)
const acting = ref(false)
const error = ref('')
const meter = ref({})

const breadcrumbs = computed(() => [
  { label: 'Meters', to: { name: 'Meters' } },
  { label: meter.value.meter_number || '…' },
])

const meterSubtitle = computed(() => {
  const parts = []
  if (meter.value.utility_type?.label) parts.push(meter.value.utility_type.label)
  if (meter.value.building?.name) parts.push(meter.value.building.name)
  return parts.join(' · ')
})

const attributes = computed(() => {
  const m = meter.value
  if (!m.id) return []
  return [
    m.utility_type?.label,
    m.meter_type?.label,
    m.smart_features?.is_smart_meter ? 'Smart' : null,
  ].filter(Boolean)
})

const tabs = [
  { id: 'overview', label: 'Overview' },
  { id: 'readings', label: 'Readings' },
]

async function fetchMeter() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get(`/meters/${route.params.id}`)
    meter.value = data.data ?? data
  } catch {
    error.value = 'Failed to load meter details.'
  } finally {
    loading.value = false
  }
}

async function lifecycleAction(endpoint) {
  acting.value = true
  try {
    await api.post(`/meters/${route.params.id}/${endpoint}`)
    await fetchMeter()
  } catch (e) {
    error.value = e.response?.data?.message || 'Action failed.'
  } finally {
    acting.value = false
  }
}

async function onDecommission() {
  const ok = await confirm({
    title: 'Decommission meter',
    message: 'This meter will be marked decommissioned and removed from active billing. Continue?',
    confirmLabel: 'Decommission',
    variant: 'danger',
  })
  if (!ok) return
  await lifecycleAction('decommission')
}

function formatNumber(value) {
  if (value == null || value === '') return '—'
  return Number(value).toLocaleString(undefined, { maximumFractionDigits: 4 })
}

function formatDate(dateString) {
  if (!dateString) return null
  return new Date(dateString).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

onMounted(fetchMeter)
</script>
