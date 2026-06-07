<template>
  <TransactionalFormLayout
    eyebrow="Finance · Invoice"
    title="Create manual invoice"
    description="Draft invoice with custom line items. Issue from the invoice detail page when ready."
    primary-label="Create draft invoice"
    :saving="submitting"
    :error="serverError"
    @primary="submit"
    @cancel="onCancel"
    @dismiss-error="serverError = ''"
  >
    <FormSection title="Billing context">
      <FormGrid>
        <FormField label="Building" required :error="fieldError('building_id')" span="2">
          <ErpSearchSelect
            v-model="form.building_id"
            :options="buildingOptions"
            :loading="buildingsLoading"
            remote
            placeholder="Select building…"
            search-placeholder="Search building name…"
            @search="onBuildingSearch"
          />
        </FormField>

        <FormField label="Unit" required :error="fieldError('apartment_id')" span="2">
          <ErpSearchSelect
            v-model="form.apartment_id"
            :options="apartmentOptions"
            :loading="apartmentsLoading"
            :disabled="!form.building_id"
            remote
            placeholder="Select unit…"
            search-placeholder="Search unit number…"
            @search="onApartmentSearch"
          />
          <p v-if="selectedApartmentHint" class="mt-1 text-xs text-slate-500">
            {{ selectedApartmentHint }}
          </p>
        </FormField>

        <FormField label="Billing year" required :error="fieldError('billing_year')">
          <input
            v-model.number="form.billing_year"
            type="number"
            min="2020"
            max="2050"
            class="erp-input w-full"
          />
        </FormField>

        <FormField label="Billing month" required :error="fieldError('billing_month')">
          <select v-model.number="form.billing_month" class="erp-input w-full">
            <option v-for="(label, idx) in monthLabels" :key="idx" :value="idx + 1">
              {{ label }}
            </option>
          </select>
        </FormField>

        <FormField label="Issue date" :error="fieldError('issue_date')">
          <ErpDateInput v-model="form.issue_date" />
        </FormField>

        <FormField label="Due date" :error="fieldError('due_date')">
          <ErpDateInput v-model="form.due_date" />
        </FormField>

        <FormField label="Discount" :error="fieldError('discount_amount')" span="2">
          <input
            v-model.number="form.discount_amount"
            type="number"
            min="0"
            step="0.01"
            class="erp-input w-full"
            placeholder="0.00"
          />
        </FormField>

        <FormField label="Notes" :error="fieldError('notes')" span="2">
          <textarea
            v-model="form.notes"
            rows="2"
            class="erp-input w-full"
            placeholder="Optional note on this invoice…"
          />
        </FormField>
      </FormGrid>
    </FormSection>

    <FormSection title="Line items" class="mt-8">
      <p class="mb-4 text-sm text-slate-600">
        Add one or more charges. Line type drives how subtotals roll up on the invoice.
      </p>

      <div v-if="fieldError('line_items')" class="mb-3 text-sm text-red-600">
        {{ fieldError('line_items') }}
      </div>

      <div class="space-y-3">
        <div
          v-for="(line, index) in form.line_items"
          :key="line._key"
          class="rounded-lg border border-slate-200 bg-slate-50/60 p-4"
        >
          <div class="mb-3 flex items-center justify-between gap-2">
            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">
              Line {{ index + 1 }}
            </span>
            <button
              v-if="form.line_items.length > 1"
              type="button"
              class="text-xs font-medium text-red-600 hover:text-red-800"
              @click="removeLine(index)"
            >
              Remove
            </button>
          </div>

          <FormGrid>
            <FormField
              label="Description"
              required
              :error="fieldError(`line_items.${index}.description`)"
              span="2"
            >
              <input
                v-model="line.description"
                type="text"
                class="erp-input w-full"
                placeholder="e.g. June rent"
              />
            </FormField>

            <FormField
              label="Type"
              required
              :error="fieldError(`line_items.${index}.line_type`)"
            >
              <select v-model="line.line_type" class="erp-input w-full">
                <option v-for="opt in lineTypeOptions" :key="opt.value" :value="opt.value">
                  {{ opt.label }}
                </option>
              </select>
            </FormField>

            <FormField
              label="Quantity"
              required
              :error="fieldError(`line_items.${index}.quantity`)"
            >
              <input
                v-model.number="line.quantity"
                type="number"
                min="0.001"
                step="any"
                class="erp-input w-full font-mono"
              />
            </FormField>

            <FormField
              label="Unit price"
              required
              :error="fieldError(`line_items.${index}.unit_price`)"
            >
              <input
                v-model.number="line.unit_price"
                type="number"
                min="0.01"
                step="0.01"
                class="erp-input w-full font-mono"
              />
            </FormField>

            <div class="flex items-end justify-end sm:col-span-2">
              <p class="text-sm text-slate-600">
                Line total:
                <span class="font-mono font-semibold text-slate-900">
                  {{ formatMoney(lineAmount(line)) }}
                </span>
              </p>
            </div>
          </FormGrid>
        </div>
      </div>

      <ErpButton type="button" variant="secondary" size="sm" class="mt-4" @click="addLine">
        Add line item
      </ErpButton>
    </FormSection>

    <section class="mt-8 rounded-xl border border-slate-200 bg-white p-4">
      <h3 class="text-sm font-semibold text-slate-800">Totals preview</h3>
      <dl class="mt-3 space-y-1 text-sm text-slate-600">
        <div class="flex justify-between">
          <dt>Rent</dt>
          <dd class="font-mono">{{ formatMoney(totals.rent) }}</dd>
        </div>
        <div class="flex justify-between">
          <dt>Utilities</dt>
          <dd class="font-mono">{{ formatMoney(totals.utilities) }}</dd>
        </div>
        <div class="flex justify-between">
          <dt>Services & other</dt>
          <dd class="font-mono">{{ formatMoney(totals.services) }}</dd>
        </div>
        <div class="flex justify-between">
          <dt>Installments</dt>
          <dd class="font-mono">{{ formatMoney(totals.installment) }}</dd>
        </div>
        <div v-if="form.discount_amount > 0" class="flex justify-between text-amber-800">
          <dt>Discount</dt>
          <dd class="font-mono">−{{ formatMoney(form.discount_amount) }}</dd>
        </div>
        <div class="flex justify-between border-t border-slate-100 pt-2 font-semibold text-slate-900">
          <dt>Invoice total</dt>
          <dd class="font-mono">{{ formatMoney(grandTotal) }}</dd>
        </div>
      </dl>
    </section>
  </TransactionalFormLayout>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import {
  ErpButton,
  ErpDateInput,
  ErpSearchSelect,
  FormField,
  FormGrid,
  FormSection,
  TransactionalFormLayout,
} from '@/components/erp'
import { useBuildingApartments } from '@/composables/useBuildingApartments'
import { useBuildingPicker } from '@/composables/useBuildingPicker'
import { useMonthlyInvoices } from '@/composables/useMonthlyInvoices'

const router = useRouter()
const { createInvoice } = useMonthlyInvoices()

const { buildings, loading: buildingsLoading, fetchBuildings, buildingToOption } = useBuildingPicker()
const {
  apartments,
  loading: apartmentsLoading,
  fetchApartments,
  apartmentToOption,
} = useBuildingApartments()

const submitting = ref(false)
const serverError = ref('')
const fieldErrors = ref({})

const now = new Date()
const monthLabels = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December',
]

let lineKey = 0
function newLine(overrides = {}) {
  return {
    _key: ++lineKey,
    description: '',
    line_type: 'rent',
    quantity: 1,
    unit_price: '',
    ...overrides,
  }
}

const form = reactive({
  building_id: '',
  apartment_id: '',
  billing_year: now.getFullYear(),
  billing_month: now.getMonth() + 1,
  issue_date: now.toISOString().split('T')[0],
  due_date: '',
  discount_amount: 0,
  notes: '',
  line_items: [newLine()],
})

const lineTypeOptions = [
  { value: 'rent', label: 'Rent' },
  { value: 'utility', label: 'Utility (general)' },
  { value: 'electricity', label: 'Electricity' },
  { value: 'water', label: 'Water' },
  { value: 'gas', label: 'Gas' },
  { value: 'service', label: 'Service / amenity' },
  { value: 'installment', label: 'Installment' },
  { value: 'other', label: 'Other' },
]

const buildingOptions = computed(() => buildings.value.map(buildingToOption))
const apartmentOptions = computed(() => apartments.value.map(apartmentToOption))

const selectedApartmentHint = computed(() => {
  const apt = apartments.value.find((a) => a.id === form.apartment_id)
  return apt?.occupancy?.hint || apt?.occupancy_hint || null
})

function bucketForLineType(type) {
  if (type === 'rent') return 'rent'
  if (['utility', 'electricity', 'water', 'gas'].includes(type)) return 'utilities'
  if (type === 'installment') return 'installment'
  return 'services'
}

function lineAmount(line) {
  const qty = Number(line.quantity) || 0
  const price = Number(line.unit_price) || 0
  return qty * price
}

const totals = computed(() => {
  const buckets = { rent: 0, utilities: 0, services: 0, installment: 0 }
  for (const line of form.line_items) {
    buckets[bucketForLineType(line.line_type)] += lineAmount(line)
  }
  return buckets
})

const grandTotal = computed(() => {
  const subtotal =
    totals.value.rent +
    totals.value.utilities +
    totals.value.services +
    totals.value.installment
  return Math.max(0, subtotal - (Number(form.discount_amount) || 0))
})

function formatMoney(v) {
  return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(Number(v) || 0)
}

function fieldError(key) {
  const e = fieldErrors.value[key]
  return Array.isArray(e) ? e[0] : e || ''
}

function addLine() {
  form.line_items.push(newLine())
}

function removeLine(index) {
  if (form.line_items.length <= 1) return
  form.line_items.splice(index, 1)
}

let buildingSearchTimer = null
function onBuildingSearch(q) {
  clearTimeout(buildingSearchTimer)
  buildingSearchTimer = setTimeout(
    () => fetchBuildings(q, { ensureId: form.building_id || undefined }),
    280,
  )
}

let apartmentSearchTimer = null
function onApartmentSearch(q) {
  if (!form.building_id) return
  clearTimeout(apartmentSearchTimer)
  apartmentSearchTimer = setTimeout(
    () =>
      fetchApartments(form.building_id, {
        search: q,
        mode: 'invoice',
        ensureId: form.apartment_id || undefined,
      }),
    280,
  )
}

watch(
  () => form.building_id,
  (buildingId, prev) => {
    if (buildingId === prev) return
    form.apartment_id = ''
    apartments.value = []
    if (buildingId) {
      fetchApartments(buildingId, { mode: 'invoice' })
    }
  },
)

function buildPayload() {
  return {
    apartment_id: form.apartment_id,
    billing_year: form.billing_year,
    billing_month: form.billing_month,
    issue_date: form.issue_date || undefined,
    due_date: form.due_date || undefined,
    discount_amount: Number(form.discount_amount) || 0,
    notes: form.notes || undefined,
    line_items: form.line_items.map((line) => ({
      description: line.description,
      line_type: line.line_type,
      quantity: Number(line.quantity),
      unit_price: Number(line.unit_price),
    })),
  }
}

async function submit() {
  submitting.value = true
  serverError.value = ''
  fieldErrors.value = {}

  try {
    const invoice = await createInvoice(buildPayload())
    router.push({ name: 'InvoiceShow', params: { id: invoice.id } })
  } catch (error) {
    if (error.response?.status === 422) {
      fieldErrors.value = error.response.data.errors ?? {}
      serverError.value =
        error.response.data.message || 'Please fix the highlighted fields.'
      return
    }
    serverError.value = error.response?.data?.message || 'Failed to create invoice.'
  } finally {
    submitting.value = false
  }
}

function onCancel() {
  router.push({ name: 'MonthlyInvoices' })
}

onMounted(() => {
  fetchBuildings('')
})
</script>
