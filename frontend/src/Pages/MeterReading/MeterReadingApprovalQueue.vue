<template>
  <WorklistLayout
    eyebrow="Utilities"
    title="Reading approval queue"
    :count="meta.total"
    description="Review verified readings in a split layout. Approve or reject without leaving the list — selection advances automatically."
  >
    <template #actions>
      <ErpButton variant="ghost" size="sm" :to="{ name: 'MeterReadings' }">All readings</ErpButton>
      <ErpButton variant="ghost" size="sm" :loading="loading" @click="refresh">Refresh</ErpButton>
      <ErpButton variant="secondary" :to="{ name: 'MeterReadingBulkEntry' }">Bulk entry</ErpButton>
    </template>

    <template #filters>
      <SmartFilterBar :chips="chips" @clear-all="onClearAll" @remove-chip="removeChip">
        <FormField label="Search" span="2">
          <input
            v-model="smartFilters.search"
            type="search"
            class="erp-input"
            placeholder="Meter number…"
            @input="debounceFetch"
          />
        </FormField>
        <FormField label="Status">
          <select v-model="smartFilters.status" class="erp-select" @change="syncAndFetch">
            <option value="verified">Verified (ready)</option>
            <option value="draft">Draft (anomalies)</option>
            <option value="">All pending types</option>
          </select>
        </FormField>
        <FormField label="Utility">
          <select v-model="smartFilters.utility_type" class="erp-select" @change="syncAndFetch">
            <option value="">All</option>
            <option value="water">Water</option>
            <option value="electricity">Electricity</option>
            <option value="gas">Gas</option>
          </select>
        </FormField>
      </SmartFilterBar>
    </template>

    <template #table>
      <AlertBanner
        v-if="actionMessage"
        class="mb-4"
        :variant="actionMessageVariant"
        :message="actionMessage"
        @dismiss="actionMessage = ''"
      />

      <MasterDetailLayout empty-detail-label="Select a reading from the queue">
        <template #list-header>
          <div class="flex items-center justify-between gap-2 text-sm">
            <span class="font-medium text-slate-800">{{ meta.total }} in queue</span>
            <span class="text-xs text-slate-500">j/k or ↑/↓ to move</span>
          </div>
        </template>

        <template #list>
          <div v-if="loading && !items.length" class="p-6 text-center text-sm text-slate-500">
            Loading queue…
          </div>
          <div v-else-if="!items.length" class="p-6 text-center text-sm text-slate-500">
            No readings match this filter.
          </div>
          <ul v-else class="divide-y divide-slate-100">
            <li
              v-for="row in items"
              :key="row.id"
            >
              <button
                type="button"
                class="flex w-full flex-col gap-1 px-4 py-3 text-left transition-colors"
                :class="selectedId === row.id ? 'bg-indigo-50' : 'hover:bg-slate-50'"
                @click="select(row)"
              >
                <div class="flex items-start justify-between gap-2">
                  <span class="font-mono text-xs font-medium text-slate-900">
                    {{ row.meter?.meter_number || row.id?.slice(0, 8) }}
                  </span>
                  <StatusBadge
                    :status="rowStatus(row)"
                    :label="row.status?.label || rowStatus(row)"
                  />
                </div>
                <p class="text-xs text-slate-600">
                  {{ row.apartment?.unit_number ? `Unit ${row.apartment.unit_number}` : '—' }}
                  <span v-if="row.building?.name"> · {{ row.building.name }}</span>
                </p>
                <div class="flex items-center justify-between text-xs">
                  <span class="tabular-nums text-slate-700">
                    {{ formatReading(row.reading?.consumption ?? row.consumption) }}
                    <span v-if="row.meter?.measurement_unit" class="text-slate-400">
                      {{ row.meter.measurement_unit }}
                    </span>
                  </span>
                  <span v-if="row.anomaly?.detected" class="font-medium text-amber-700">Anomaly</span>
                </div>
              </button>
            </li>
          </ul>
          <div
            v-if="meta.last_page > 1"
            class="flex items-center justify-between border-t border-slate-100 px-4 py-2 text-xs text-slate-600"
          >
            <ErpButton
              variant="ghost"
              size="sm"
              :disabled="meta.current_page <= 1"
              @click="changePage(meta.current_page - 1)"
            >
              Prev
            </ErpButton>
            <span>{{ meta.current_page }} / {{ meta.last_page }}</span>
            <ErpButton
              variant="ghost"
              size="sm"
              :disabled="meta.current_page >= meta.last_page"
              @click="changePage(meta.current_page + 1)"
            >
              Next
            </ErpButton>
          </div>
        </template>

        <template v-if="selectedItem" #detail>
          <div class="space-y-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div>
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Reading</p>
                <h2 class="text-lg font-semibold text-slate-900">
                  {{ selectedItem.meter?.meter_number || '—' }}
                </h2>
                <p class="text-sm text-slate-600">
                  {{ selectedItem.building?.name || '—' }}
                  <span v-if="selectedItem.apartment?.unit_number">
                    · Unit {{ selectedItem.apartment.unit_number }}
                  </span>
                </p>
              </div>
              <StatusBadge
                :status="rowStatus(selectedItem)"
                :label="selectedItem.status?.label || rowStatus(selectedItem)"
              />
            </div>

            <KpiStrip class="grid-cols-3">
              <KpiCard
                label="Previous"
                :value="formatReading(selectedItem.reading?.previous_reading)"
              />
              <KpiCard
                label="Current"
                :value="formatReading(selectedItem.reading?.current_reading)"
              />
              <KpiCard
                label="Consumption"
                :value="formatReading(selectedItem.reading?.consumption ?? selectedItem.consumption)"
                :variant="selectedItem.anomaly?.detected ? 'warning' : 'default'"
              />
            </KpiStrip>

            <dl class="grid gap-3 text-sm sm:grid-cols-2">
              <div>
                <dt class="text-slate-500">Reading date</dt>
                <dd class="font-medium text-slate-900">
                  {{ formatDate(selectedItem.reading?.reading_date || selectedItem.reading_date) }}
                </dd>
              </div>
              <div>
                <dt class="text-slate-500">Utility</dt>
                <dd class="capitalize text-slate-900">
                  {{ selectedItem.meter?.utility_type?.label || selectedItem.meter?.utility_type?.value || '—' }}
                </dd>
              </div>
              <div v-if="selectedItem.anomaly?.detected" class="sm:col-span-2">
                <dt class="text-slate-500">Anomaly</dt>
                <dd class="font-medium text-amber-800">
                  {{ selectedItem.anomaly?.reason || 'Flagged for review' }}
                </dd>
              </div>
              <div v-if="selectedItem.notes" class="sm:col-span-2">
                <dt class="text-slate-500">Notes</dt>
                <dd class="text-slate-700">{{ selectedItem.notes }}</dd>
              </div>
            </dl>

            <AlertBanner
              v-if="!selectedItem.controls?.can_approve && rowStatus(selectedItem) === 'draft'"
              variant="warning"
              :dismissible="false"
              message="Draft readings with anomalies must be edited and re-saved before approval."
            />

            <div class="flex flex-wrap items-center gap-2 border-t border-slate-200 pt-4">
              <ErpButton
                v-if="selectedItem.controls?.can_approve"
                :loading="acting"
                @click="onApprove"
              >
                Approve
              </ErpButton>
              <ErpButton
                v-if="selectedItem.controls?.can_reject !== false && rowStatus(selectedItem) !== 'approved'"
                variant="danger"
                :disabled="acting"
                @click="openReject"
              >
                Reject
              </ErpButton>
              <ErpButton
                variant="ghost"
                size="sm"
                :to="{ name: 'MeterReadingShow', params: { id: selectedItem.id } }"
              >
                Open full detail
              </ErpButton>
            </div>
          </div>
        </template>
      </MasterDetailLayout>
    </template>
  </WorklistLayout>

  <ErpModal
    :open="rejectModal.open"
    title="Reject reading"
    subtitle="Provide a reason for rejection."
    confirm-label="Reject"
    confirm-variant="danger"
    :loading="rejectModal.loading"
    @close="rejectModal.open = false"
    @confirm="submitReject"
  >
    <FormField label="Reason" class="mt-2">
      <textarea v-model="rejectModal.reason" class="erp-input min-h-[88px]" placeholder="Required…" />
    </FormField>
  </ErpModal>
</template>

<script setup>
import { ref, reactive, watch, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useMeterReadings } from '@/composables/useMeterReadings'
import { useMasterDetailQueue } from '@/composables/useMasterDetailQueue'
import { useSmartFilters } from '@/composables/useSmartFilters'
import {
  WorklistLayout,
  SmartFilterBar,
  MasterDetailLayout,
  FormField,
  ErpButton,
  StatusBadge,
  KpiCard,
  KpiStrip,
  ErpModal,
  AlertBanner,
} from '@/components/erp'

const route = useRoute()
const router = useRouter()
const acting = ref(false)
const actionMessage = ref('')
const actionMessageVariant = ref('success')

const { items, loading, meta, filters, fetchList, approve, reject, resetFilters } = useMeterReadings()
filters.per_page = 50
filters.status = 'verified'

const { selectedId, selectedItem, select, advanceAfterAction, moveSelection } = useMasterDetailQueue(items)

const { filters: smartFilters, chips, clearAll, removeChip, bindRoute } = useSmartFilters({
  defaults: { search: '', status: 'verified', utility_type: '' },
  labels: {
    search: { label: 'Search' },
    status: { label: 'Status' },
    utility_type: { label: 'Utility' },
  },
})

watch(smartFilters, () => Object.assign(filters, { ...smartFilters }), { deep: true, immediate: true })

const rejectModal = reactive({ open: false, loading: false, reason: '' })

let debounceTimer = null
function debounceFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(syncAndFetch, 350)
}

function syncAndFetch() {
  Object.assign(filters, { ...smartFilters })
  fetchList(1)
}

function onClearAll() {
  clearAll()
  smartFilters.status = 'verified'
  resetFilters()
  filters.per_page = 50
  filters.status = 'verified'
  syncAndFetch()
}

function rowStatus(row) {
  return row.status?.value || row.status
}

function formatReading(val) {
  if (val == null || val === '') return '—'
  return Number(val).toLocaleString(undefined, { maximumFractionDigits: 4 })
}

function formatDate(d) {
  if (!d) return '—'
  try {
    return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
  } catch {
    return d
  }
}

async function refresh() {
  await fetchList(meta.value.current_page)
}

async function changePage(page) {
  await fetchList(page)
}

async function onApprove() {
  if (!selectedItem.value?.controls?.can_approve) return
  acting.value = true
  actionMessage.value = ''
  try {
    await approve(selectedItem.value)
    actionMessageVariant.value = 'success'
    actionMessage.value = 'Reading approved. Utility charge generated.'
    advanceAfterAction()
    await refresh()
  } catch (err) {
    actionMessageVariant.value = 'error'
    actionMessage.value = err?.response?.data?.message || 'Approval failed.'
  } finally {
    acting.value = false
  }
}

function openReject() {
  rejectModal.reason = ''
  rejectModal.open = true
}

async function submitReject() {
  if (!rejectModal.reason.trim() || !selectedItem.value) return
  rejectModal.loading = true
  try {
    await reject(selectedItem.value, rejectModal.reason.trim())
    rejectModal.open = false
    actionMessageVariant.value = 'success'
    actionMessage.value = 'Reading rejected.'
    advanceAfterAction()
    await refresh()
  } catch (err) {
    actionMessageVariant.value = 'error'
    actionMessage.value = err?.response?.data?.message || 'Reject failed.'
  } finally {
    rejectModal.loading = false
  }
}

function onKeydown(event) {
  const tag = event.target?.tagName?.toLowerCase()
  if (tag === 'input' || tag === 'textarea' || tag === 'select') return

  if (event.key === 'j' || event.key === 'ArrowDown') {
    event.preventDefault()
    moveSelection(1)
  } else if (event.key === 'k' || event.key === 'ArrowUp') {
    event.preventDefault()
    moveSelection(-1)
  }
}

onMounted(() => {
  bindRoute(route, router, { debounceMs: 300 })
  syncAndFetch()
  window.addEventListener('keydown', onKeydown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', onKeydown)
})
</script>
