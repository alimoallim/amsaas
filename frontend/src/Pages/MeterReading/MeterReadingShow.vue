<template>
  <AlertBanner
    v-if="pageError"
    variant="error"
    class="erp-page mb-4"
    :message="pageError"
    @dismiss="pageError = ''"
  />
  <AlertBanner
    v-if="pageMessage"
    variant="success"
    class="erp-page mb-4"
    :message="pageMessage"
    @dismiss="pageMessage = ''"
  />

  <div v-if="loading" class="erp-page py-12 text-center text-slate-500">Loading meter reading…</div>

  <ObjectPageLayout
    v-else-if="reading"
    :breadcrumbs="breadcrumbs"
    :title="reading.meter?.meter_number || 'Meter reading'"
    :subtitle="readingSubtitle"
    :status="reading.status?.value"
    :status-label="reading.status?.label"
    :attributes="attributes"
    :tabs="tabs"
    initial-tab="overview"
  >
    <template #actions>
      <ErpButton variant="ghost" size="sm" :to="{ name: 'MeterReadings' }">Back</ErpButton>
      <ErpButton
        v-if="reading.controls?.can_edit"
        variant="secondary"
        size="sm"
        :to="{ name: 'MeterReadingEdit', params: { id: reading.id } }"
      >
        Edit
      </ErpButton>
      <ErpButton
        v-if="reading.controls?.can_approve"
        variant="success"
        size="sm"
        :loading="approving"
        @click="onApprove"
      >
        Approve
      </ErpButton>
      <ErpButton
        v-if="reading.controls?.can_reject && reading.status?.value !== 'approved'"
        variant="danger"
        size="sm"
        @click="openRejectModal"
      >
        Reject
      </ErpButton>
    </template>

    <template #overview>
      <KpiStrip class="mb-6 grid-cols-2 lg:grid-cols-4">
        <KpiCard label="Previous" :value="formatReading(reading.reading?.previous_reading)" :caption="unitLabel" />
        <KpiCard label="Current" :value="formatReading(reading.reading?.current_reading)" :caption="unitLabel" variant="accent" />
        <KpiCard label="Consumption" :value="formatReading(reading.reading?.consumption)" :caption="unitLabel" />
        <KpiCard
          label="Variance"
          :value="`${variance}%`"
          caption="vs previous period"
          :variant="varianceVariant"
        />
      </KpiStrip>

      <div class="grid gap-6 lg:grid-cols-2">
        <FormSection title="Reading details">
          <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
            <div><dt class="text-slate-500">Reading date</dt><dd class="font-medium">{{ reading.reading?.reading_date || '—' }}</dd></div>
            <div><dt class="text-slate-500">Previous date</dt><dd class="font-medium">{{ reading.reading?.previous_reading_date || '—' }}</dd></div>
            <div><dt class="text-slate-500">Type</dt><dd class="font-medium">{{ reading.reading_type?.label || '—' }}</dd></div>
            <div><dt class="text-slate-500">Source</dt><dd class="font-medium">{{ reading.reading_source?.label || '—' }}</dd></div>
            <div><dt class="text-slate-500">Reader</dt><dd class="font-medium">{{ reading.reader?.name || '—' }}</dd></div>
            <div><dt class="text-slate-500">Captured</dt><dd class="font-medium">{{ reading.audit?.created_at || '—' }}</dd></div>
          </dl>
        </FormSection>

        <FormSection title="Location">
          <dl class="grid grid-cols-1 gap-3 text-sm">
            <div>
              <dt class="text-slate-500">Building</dt>
              <dd class="font-medium">
                <RouterLink
                  v-if="reading.building?.id"
                  :to="{ name: 'BuildingShow', params: { id: reading.building.id } }"
                  class="text-indigo-600 hover:underline"
                >
                  {{ reading.building.name }}
                </RouterLink>
                <span v-else>—</span>
              </dd>
            </div>
            <div>
              <dt class="text-slate-500">Unit</dt>
              <dd class="font-medium">
                <RouterLink
                  v-if="reading.apartment?.id"
                  :to="{ name: 'ApartmentShow', params: { id: reading.apartment.id } }"
                  class="text-indigo-600 hover:underline"
                >
                  {{ reading.apartment.unit_number }}
                </RouterLink>
                <span v-else>—</span>
              </dd>
            </div>
            <div>
              <dt class="text-slate-500">Meter</dt>
              <dd class="font-medium">
                <RouterLink
                  v-if="reading.meter?.id"
                  :to="{ name: 'MeterShow', params: { id: reading.meter.id } }"
                  class="text-indigo-600 hover:underline"
                >
                  {{ reading.meter.meter_number }}
                </RouterLink>
                <span v-else>—</span>
              </dd>
            </div>
          </dl>
        </FormSection>
      </div>

      <FormSection v-if="reading.notes" title="Notes" class="mt-6">
        <p class="text-sm text-slate-600">{{ reading.notes }}</p>
      </FormSection>

      <FormSection title="Evidence" class="mt-6">
        <a
          v-if="reading.attachment_path"
          :href="reading.attachment_path"
          target="_blank"
          rel="noopener noreferrer"
          class="text-sm font-medium text-indigo-600 hover:underline"
        >
          View attachment
        </a>
        <p v-else class="text-sm text-slate-500">No attachment uploaded.</p>
      </FormSection>
    </template>

    <template #anomaly>
      <AlertBanner
        v-if="reading.anomaly?.detected"
        variant="warning"
        class="mb-4"
        :message="reading.anomaly.reason || 'Anomaly detected on this reading.'"
      />
      <div v-if="reading.anomaly?.detected" class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm dark:border-amber-800/50 dark:bg-amber-950/30">
        <p class="font-medium text-amber-900 dark:text-amber-200">Severity: {{ reading.anomaly.severity || '—' }}</p>
        <p class="mt-2 text-amber-800 dark:text-amber-300">{{ reading.anomaly.reason }}</p>
      </div>
      <p v-else class="text-sm text-slate-600">No anomalies detected — reading passed validation checks.</p>
    </template>

    <template #timeline>
      <dl class="space-y-4 text-sm">
        <div class="rounded-lg border border-slate-200 p-4 dark:border-slate-700">
          <dt class="font-medium text-slate-900 dark:text-slate-100">Captured</dt>
          <dd class="mt-1 text-slate-600">{{ reading.audit?.created_at || '—' }}</dd>
          <dd class="text-slate-500">By {{ reading.reader?.name || 'System' }}</dd>
        </div>
        <div v-if="reading.approval?.approved_at" class="rounded-lg border border-emerald-200 bg-emerald-50/50 p-4 dark:border-emerald-800/50 dark:bg-emerald-950/20">
          <dt class="font-medium text-emerald-900 dark:text-emerald-200">Approved</dt>
          <dd class="mt-1 text-slate-600">{{ reading.approval.approved_at }}</dd>
          <dd class="text-slate-500">By {{ reading.approval.approved_by?.name || '—' }}</dd>
        </div>
        <div
          v-else-if="reading.status?.value === 'draft' || reading.status?.value === 'verified'"
          class="rounded-lg border border-dashed border-slate-300 p-4 text-slate-500 dark:border-slate-600"
        >
          Awaiting approval
        </div>
        <div
          v-if="reading.status?.value === 'rejected'"
          class="rounded-lg border border-red-200 bg-red-50/50 p-4 dark:border-red-800/50 dark:bg-red-950/20"
        >
          <dt class="font-medium text-red-900 dark:text-red-200">Rejected</dt>
          <dd class="mt-1 text-slate-600">{{ reading.audit?.updated_at || '—' }}</dd>
        </div>
      </dl>
    </template>
  </ObjectPageLayout>

  <div v-else class="erp-page py-12 text-center text-slate-500">Meter reading not found.</div>

  <ErpModal
    :open="rejectModal.open"
    title="Reject reading"
    subtitle="Provide a reason for audit and workflow traceability."
    confirm-label="Reject reading"
    confirm-variant="danger"
    :loading="rejecting"
    @close="rejectModal.open = false"
    @confirm="onReject"
  >
    <FormField label="Reason" required class="mt-2">
      <textarea
        v-model="rejectModal.reason"
        rows="4"
        class="erp-input min-h-[88px]"
        placeholder="Minimum 10 characters — logged in the audit trail"
      />
    </FormField>
    <p v-if="rejectModal.reason.trim().length > 0 && rejectModal.reason.trim().length < 10" class="mt-2 text-xs text-amber-700">
      At least 10 characters required.
    </p>
  </ErpModal>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import api from '@/services/api'
import {
  ObjectPageLayout,
  ErpButton,
  FormSection,
  FormField,
  KpiCard,
  KpiStrip,
  ErpModal,
  AlertBanner,
} from '@/components/erp'

const route = useRoute()

const loading = ref(true)
const approving = ref(false)
const rejecting = ref(false)
const reading = ref(null)
const pageError = ref('')
const pageMessage = ref('')
const rejectModal = reactive({ open: false, reason: '' })

const unitLabel = computed(() => reading.value?.meter?.measurement_unit || 'units')

const breadcrumbs = computed(() => [
  { label: 'Meter readings', to: { name: 'MeterReadings' } },
  { label: reading.value?.meter?.meter_number || '…' },
])

const readingSubtitle = computed(() => {
  if (!reading.value) return ''
  const parts = [
    reading.value.meter?.utility_type?.label,
    reading.value.building?.name,
    reading.value.apartment?.unit_number ? `Unit ${reading.value.apartment.unit_number}` : null,
    reading.value.reading?.reading_date,
  ]
  return parts.filter(Boolean).join(' · ')
})

const attributes = computed(() => {
  if (!reading.value) return []
  return [
    reading.value.meter?.utility_type?.label,
    reading.value.reading_type?.label,
    reading.value.anomaly?.detected ? 'Anomaly' : null,
  ].filter(Boolean)
})

const tabs = [
  { id: 'overview', label: 'Overview' },
  { id: 'anomaly', label: 'Anomaly' },
  { id: 'timeline', label: 'Timeline' },
]

const variance = computed(() => {
  if (!reading.value?.reading) return '0.00'
  const prev = Number(reading.value.reading.previous_reading)
  const curr = Number(reading.value.reading.current_reading)
  if (!prev) return '0.00'
  return (((curr - prev) / prev) * 100).toFixed(2)
})

const varianceVariant = computed(() => (Number(variance.value) > 20 ? 'accent' : 'default'))

function formatReading(val) {
  if (val == null || val === '') return '—'
  return Number(val).toLocaleString(undefined, { maximumFractionDigits: 4 })
}

async function fetchReading() {
  loading.value = true
  pageError.value = ''
  try {
    const { data } = await api.get(`/meter-readings/${route.params.id}`)
    reading.value = data.data ?? data
  } catch {
    pageError.value = 'Failed to load meter reading.'
  } finally {
    loading.value = false
  }
}

async function onApprove() {
  approving.value = true
  pageError.value = ''
  try {
    await api.post(`/meter-readings/${route.params.id}/approve`)
    pageMessage.value = 'Reading approved. Utility charges generated where configured.'
    await fetchReading()
  } catch (e) {
    pageError.value = e.response?.data?.message || 'Approval failed.'
  } finally {
    approving.value = false
  }
}

function openRejectModal() {
  rejectModal.reason = ''
  rejectModal.open = true
}

async function onReject() {
  const reason = rejectModal.reason.trim()
  if (reason.length < 10) {
    pageError.value = 'Rejection reason must be at least 10 characters.'
    return
  }
  rejecting.value = true
  pageError.value = ''
  try {
    await api.post(`/meter-readings/${route.params.id}/reject`, { reason })
    rejectModal.open = false
    pageMessage.value = 'Reading rejected.'
    await fetchReading()
  } catch (e) {
    pageError.value = e.response?.data?.message || 'Rejection failed.'
  } finally {
    rejecting.value = false
  }
}

onMounted(fetchReading)
</script>
