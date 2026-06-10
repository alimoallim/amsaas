<template>
  <WorklistLayout
    eyebrow="Utilities"
    title="Bulk meter readings"
    :count="meta.total"
    description="Spreadsheet-style capture for monthly utility readings. Tab or Enter to move between units; empty cells are skipped on save."
  >
    <template #actions>
      <ErpButton variant="ghost" size="sm" :to="{ name: 'MeterReadings' }">Worklist</ErpButton>
      <ErpButton variant="ghost" size="sm" :loading="loading" @click="reload">Refresh</ErpButton>
      <ErpButton :loading="saving" :disabled="sessionStats.entered === 0" @click="onSave">
        Save all entered
      </ErpButton>
    </template>

    <template #filters>
      <div class="erp-panel p-4">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <FormField label="Reading date" required>
            <input v-model="filters.reading_date" type="date" class="erp-input" @change="reload" />
          </FormField>
          <FormField label="Building">
            <ErpSearchSelect
              v-model="filters.building_id"
              :options="buildingOptions"
              :loading="buildingsLoading"
              remote
              clearable
              placeholder="All buildings"
              search-placeholder="Search buildings…"
              @search="onBuildingSearch"
              @update:model-value="reload"
            />
          </FormField>
          <FormField label="Utility" required>
            <select v-model="filters.utility_type" class="erp-select" @change="reload">
              <option value="water">Water</option>
              <option value="electricity">Electricity</option>
              <option value="gas">Gas</option>
            </select>
          </FormField>
          <FormField label="Per page">
            <select v-model.number="meta.per_page" class="erp-select" @change="reload">
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </select>
          </FormField>
        </div>
      </div>
    </template>

    <template #table>
      <AlertBanner
        v-if="saveMessage"
        class="mb-4"
        :variant="saveVariant"
        :message="saveMessage"
        @dismiss="saveMessage = ''"
      />

      <div class="erp-panel overflow-hidden">
        <div v-if="loading" class="p-8 text-center text-sm text-slate-500">Loading grid…</div>
        <div v-else-if="!rows.length" class="p-8 text-center text-sm text-slate-500">
          No operational meters match these filters.
        </div>
        <div v-else class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
              <tr>
                <th class="px-3 py-2">Unit</th>
                <th class="px-3 py-2">Tenant</th>
                <th class="px-3 py-2 text-right">Previous</th>
                <th class="px-3 py-2 text-right">Current</th>
                <th class="px-3 py-2 text-right">Consumption</th>
                <th class="px-3 py-2 text-center">Flag</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="(row, index) in rows"
                :key="row.meter_id"
                class="hover:bg-slate-50/80"
                :class="{ 'bg-amber-50/60': row.existing_reading?.anomaly_detected }"
              >
                <td class="px-3 py-2 font-mono text-xs">
                  {{ row.unit_number || row.meter_number || '—' }}
                </td>
                <td class="px-3 py-2 text-slate-700">
                  {{ row.tenant_name || '—' }}
                </td>
                <td class="px-3 py-2 text-right tabular-nums text-slate-600">
                  {{ formatReading(row.previous_reading) }}
                </td>
                <td class="px-3 py-2 text-right">
                  <input
                    :ref="(el) => setInputRef(el, index)"
                    v-model="inputs[row.meter_id]"
                    type="text"
                    inputmode="decimal"
                    class="erp-input w-28 text-right tabular-nums"
                    placeholder="—"
                    @keydown.enter.prevent="focusNext(index)"
                    @keydown.tab="onTab($event, index)"
                  />
                </td>
                <td class="px-3 py-2 text-right tabular-nums">
                  {{ formatConsumption(row) }}
                </td>
                <td class="px-3 py-2 text-center">
                  <span
                    v-if="flagFor(row)"
                    class="inline-flex min-w-[2.5rem] items-center justify-center rounded px-1.5 py-0.5 text-xs font-medium"
                    :class="flagClass(flagFor(row))"
                  >
                    {{ flagFor(row).label }}
                  </span>
                  <span v-else class="text-slate-300">—</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-4 py-3 text-sm text-slate-600">
          <div>
            Showing {{ meta.from || 0 }}–{{ meta.to || 0 }} of {{ meta.total }}
            <span class="mx-2 text-slate-300">|</span>
            Entered: {{ sessionStats.entered }} / {{ meta.total }}
            <span class="mx-2 text-slate-300">|</span>
            On page: {{ pageStats.entered }}
            <span class="mx-2 text-slate-300">|</span>
            Anomalies: {{ pageStats.anomalies }}
          </div>
          <div class="flex items-center gap-2">
            <ErpButton
              variant="ghost"
              size="sm"
              :disabled="meta.current_page <= 1 || loading"
              @click="changePage(meta.current_page - 1)"
            >
              Previous
            </ErpButton>
            <span class="tabular-nums">Page {{ meta.current_page }} of {{ meta.last_page }}</span>
            <ErpButton
              variant="ghost"
              size="sm"
              :disabled="meta.current_page >= meta.last_page || loading"
              @click="changePage(meta.current_page + 1)"
            >
              Next
            </ErpButton>
          </div>
        </div>
      </div>
    </template>
  </WorklistLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useMeterReadingBulk } from '@/composables/useMeterReadingBulk'
import { useBuildingPicker } from '@/composables/useBuildingPicker'
import {
  WorklistLayout,
  FormField,
  ErpButton,
  ErpSearchSelect,
  AlertBanner,
} from '@/components/erp'

const {
  rows,
  loading,
  saving,
  meta,
  filters,
  inputs,
  pageStats,
  sessionStats,
  fetchGrid,
  saveEntered,
  formatReading,
  computeConsumption,
  detectAnomaly,
} = useMeterReadingBulk()

const { buildings, loading: buildingsLoading, fetchBuildings, buildingToOption } = useBuildingPicker()

const inputRefs = ref([])
const saveMessage = ref('')
const saveVariant = ref('success')

const buildingOptions = computed(() => buildings.value.map(buildingToOption))

function setInputRef(el, index) {
  if (el) inputRefs.value[index] = el
}

function flagFor(row) {
  const value = inputs.value[row.meter_id]
  if (value === '' || value == null) return null
  return detectAnomaly(row, computeConsumption(row.previous_reading, value))
}

function flagClass(flag) {
  if (flag.type === 'ok') return 'bg-emerald-100 text-emerald-800'
  if (flag.type === 'warning') return 'bg-amber-100 text-amber-800'
  return 'bg-rose-100 text-rose-800'
}

function formatConsumption(row) {
  const value = inputs.value[row.meter_id]
  const consumption = computeConsumption(row.previous_reading, value)
  if (consumption == null) return '—'
  return formatReading(consumption)
}

function focusNext(index) {
  const next = inputRefs.value[index + 1]
  if (next) {
    next.focus()
    next.select?.()
  }
}

function onTab(event, index) {
  if (event.shiftKey) return
  const next = inputRefs.value[index + 1]
  if (next) {
    event.preventDefault()
    next.focus()
    next.select?.()
  }
}

async function onBuildingSearch(term) {
  await fetchBuildings(term, { ensureId: filters.building_id || undefined })
}

async function reload() {
  inputRefs.value = []
  await fetchGrid(1)
}

async function changePage(page) {
  inputRefs.value = []
  await fetchGrid(page)
}

async function onSave() {
  saveMessage.value = ''
  try {
    const result = await saveEntered()
    const parts = []
    if (result.saved) parts.push(`${result.saved} saved`)
    if (result.skipped) parts.push(`${result.skipped} skipped`)
    if (result.failed) parts.push(`${result.failed} failed`)

    saveVariant.value = result.failed > 0 ? (result.saved > 0 ? 'warning' : 'error') : 'success'
    saveMessage.value = parts.length ? parts.join(', ') + '.' : 'No readings to save.'
  } catch (err) {
    saveVariant.value = 'error'
    saveMessage.value = err?.response?.data?.message || 'Bulk save failed.'
  }
}

onMounted(async () => {
  await fetchBuildings('', { ensureId: filters.building_id || undefined })
  await fetchGrid(1)
})
</script>
