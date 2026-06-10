<template>
  <div class="erp-page">
    <AlertBanner
      v-if="pageError"
      variant="error"
      :message="pageError"
      class="mb-4"
      @dismiss="pageError = ''"
    />

    <div v-if="loading" class="flex flex-col items-center justify-center py-24 text-center">
      <div class="mb-4 h-10 w-10 animate-spin rounded-full border-2 border-indigo-600 border-t-transparent" />
      <p class="text-sm text-slate-500 dark:text-slate-400">Loading unit details…</p>
    </div>

    <ObjectPageLayout
      v-else-if="apartment"
      :breadcrumbs="breadcrumbs"
      :title="pageTitle"
      :subtitle="pageSubtitle"
      :status="inventoryStatus"
      :status-label="formatStatus(inventoryStatus)"
      :attributes="headerAttributes"
      :tabs="tabs"
      initial-tab="overview"
    >
      <template #actions>
        <ErpButton variant="ghost" size="sm" :to="{ name: 'Apartments' }">Back</ErpButton>
        <ErpButton
          v-if="canReserveForSale"
          size="sm"
          @click="openReserve"
        >
          Reserve for sale
        </ErpButton>
        <ErpButton
          v-if="controls.can_edit !== false"
          variant="secondary"
          size="sm"
          :to="{ name: 'ApartmentEdit', params: { id: apartment.id } }"
        >
          Edit
        </ErpButton>
        <ErpButton
          v-if="controls.can_delete"
          variant="danger"
          size="sm"
          @click="deleteApartment"
        >
          Delete
        </ErpButton>
      </template>

      <!-- Overview -->
      <template #overview>
        <KpiStrip class="mb-6 grid-cols-2 md:grid-cols-3 xl:grid-cols-5">
          <KpiCard label="Floor" :value="unit.floor ?? '—'" />
          <KpiCard label="Bedrooms" :value="layout.bedrooms ?? '—'" />
          <KpiCard label="Bathrooms" :value="layout.bathrooms ?? '—'" />
          <KpiCard label="Area" :value="layout.area_sqm ? `${layout.area_sqm} sqm` : '—'" />
          <KpiCard
            :label="primaryPriceLabel"
            :value="formatCurrency(pricing.effective_price, pricing.currency)"
          />
        </KpiStrip>

        <div class="grid gap-6 lg:grid-cols-2">
          <FormSection title="Unit specification">
            <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
              <DetailRow label="Unit number" :value="unit.unit_number" />
              <DetailRow label="Property type" :value="formatLabel(unit.property_type)" />
              <DetailRow label="Listing type" :value="formatLabel(listing.listing_type)" />
              <DetailRow label="Inventory status" :value="formatStatus(inventoryStatus)" />
              <DetailRow label="Floor" :value="unit.floor" />
              <DetailRow label="Bedrooms / Baths" :value="`${layout.bedrooms ?? '—'} / ${layout.bathrooms ?? '—'}`" />
              <DetailRow label="Area" :value="layout.area_sqm ? `${layout.area_sqm} sqm` : '—'" />
              <DetailRow label="Currency" :value="pricing.currency || 'USD'" />
            </dl>
          </FormSection>

          <FormSection title="Building & location">
            <dl class="grid grid-cols-1 gap-4 text-sm">
              <DetailRow label="Building" :value="building.name">
                <template v-if="building.id" #extra>
                  <RouterLink
                    :to="{ name: 'BuildingShow', params: { id: building.id } }"
                    class="text-xs font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
                  >
                    View building
                  </RouterLink>
                </template>
              </DetailRow>
              <DetailRow label="City" :value="building.city" />
              <DetailRow label="Country" :value="building.country" />
            </dl>
          </FormSection>
        </div>

        <FormSection v-if="occupancy.hint" title="Occupancy summary" class="mt-6">
          <p class="text-sm text-slate-600 dark:text-slate-400">{{ occupancy.hint }}</p>
          <div class="mt-3 flex flex-wrap gap-2">
            <StatusBadge
              v-if="occupancy.has_active_lease"
              status="occupied"
              label="Active lease"
            />
            <StatusBadge
              v-if="listing.can_be_rented"
              status="available"
              label="Rentable"
              :dot="false"
            />
            <StatusBadge
              v-if="listing.can_be_sold"
              status="approved"
              label="Sellable"
              :dot="false"
            />
          </div>
        </FormSection>
      </template>

      <!-- Occupancy & lifecycle -->
      <template #occupancy>
        <div class="grid gap-6 lg:grid-cols-2">
          <FormSection title="Current state">
            <dl class="space-y-4 text-sm">
              <DetailRow label="Inventory status" :value="formatStatus(inventoryStatus)" />
              <DetailRow label="Occupancy hint" :value="occupancy.hint" />
              <DetailRow
                label="Active lease"
                :value="occupancy.active_agreement_number || (occupancy.has_active_lease ? 'Yes' : 'No')"
              />
              <DetailRow label="Available for rent" :value="listing.can_be_rented ? 'Yes' : 'No'" />
              <DetailRow label="Available for sale" :value="listing.can_be_sold ? 'Yes' : 'No'" />
            </dl>
          </FormSection>

          <FormSection title="Status history">
            <div v-if="historyLoading" class="py-6 text-center text-sm text-slate-500">Loading history…</div>
            <EmptyState
              v-else-if="!inventoryHistory.length"
              title="No status changes"
              description="Inventory transitions will appear here when the unit is reserved, leased, or sold."
            />
            <ol v-else class="relative space-y-0 border-l border-slate-200 pl-4 dark:border-slate-700">
              <li
                v-for="entry in inventoryHistory"
                :key="entry.id"
                class="relative pb-6 last:pb-0"
              >
                <span
                  class="absolute -left-[1.3rem] top-1 h-2.5 w-2.5 rounded-full border-2 border-white bg-indigo-500 dark:border-slate-900"
                />
                <p class="text-sm font-medium text-slate-900 dark:text-slate-100">
                  {{ formatStatus(entry.to_status) }}
                  <span v-if="entry.from_status" class="font-normal text-slate-500 dark:text-slate-400">
                    ← {{ formatStatus(entry.from_status) }}
                  </span>
                </p>
                <p v-if="entry.reason" class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                  {{ entry.reason }}
                </p>
                <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">
                  {{ formatDateTime(entry.created_at) }}
                  <span v-if="entry.changed_by?.name"> · {{ entry.changed_by.name }}</span>
                </p>
              </li>
            </ol>
          </FormSection>

          <FormSection v-if="showSalePrice" title="Ownership history" class="lg:col-span-2">
            <div v-if="ownershipLoading" class="py-6 text-center text-sm text-slate-500">Loading ownership history…</div>
            <EmptyState
              v-else-if="!ownershipHistory.length"
              title="No ownership transfers"
              description="Ownership records appear here after a sale is completed and fully approved."
            />
            <div v-else class="overflow-x-auto">
              <table class="erp-table w-full text-sm">
                <thead>
                  <tr>
                    <th class="erp-table-head text-left">Transfer date</th>
                    <th class="erp-table-head text-left">Owner</th>
                    <th class="erp-table-head text-left">Contract</th>
                    <th class="erp-table-head text-left">Title deed</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="entry in ownershipHistory" :key="entry.id">
                    <td class="erp-table-cell">{{ entry.transfer_date || '—' }}</td>
                    <td class="erp-table-cell">{{ entry.buyer?.full_name || '—' }}</td>
                    <td class="erp-table-cell">
                      <RouterLink
                        v-if="entry.sale_agreement?.id"
                        :to="{ name: 'SaleAgreementShow', params: { id: entry.sale_agreement.id } }"
                        class="font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
                      >
                        {{ entry.sale_agreement.agreement_number || 'View contract' }}
                      </RouterLink>
                      <span v-else>—</span>
                    </td>
                    <td class="erp-table-cell">{{ entry.title_deed_number || '—' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </FormSection>
        </div>
      </template>

      <!-- Pricing -->
      <template #pricing>
        <KpiStrip class="mb-6" :class="pricingGridClass">
          <KpiCard
            v-if="showRentPrice"
            label="Market rent"
            :value="formatCurrency(pricing.market_rent_price, pricing.currency)"
            caption="Per month"
          />
          <KpiCard
            v-if="showSalePrice"
            label="Market sale price"
            :value="formatCurrency(pricing.market_sale_price, pricing.currency)"
          />
          <KpiCard
            v-if="showRentPrice"
            label="Security deposit"
            :value="formatCurrency(pricing.security_deposit, pricing.currency)"
          />
          <KpiCard
            label="Effective price"
            :value="formatCurrency(pricing.effective_price, pricing.currency)"
            :caption="primaryPriceLabel"
          />
        </KpiStrip>

        <FormSection title="Commercial terms">
          <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
            <DetailRow label="Listing type" :value="formatLabel(listing.listing_type)" />
            <DetailRow label="Currency" :value="pricing.currency" />
            <DetailRow
              v-if="showRentPrice"
              label="Market rent"
              :value="formatCurrency(pricing.market_rent_price, pricing.currency)"
            />
            <DetailRow
              v-if="showSalePrice"
              label="Market sale price"
              :value="formatCurrency(pricing.market_sale_price, pricing.currency)"
            />
            <DetailRow
              v-if="showRentPrice"
              label="Security deposit"
              :value="formatCurrency(pricing.security_deposit, pricing.currency)"
            />
          </dl>
        </FormSection>
      </template>

      <!-- Features -->
      <template #features>
        <FormSection title="Amenities & fit-out">
          <div class="flex flex-wrap gap-3">
            <FeatureChip label="Balcony" :active="features.has_balcony" />
            <FeatureChip label="Parking" :active="features.has_parking" />
            <FeatureChip label="Storage" :active="features.has_storage" />
            <FeatureChip label="Furnished" :active="features.is_furnished" />
          </div>
        </FormSection>
      </template>

      <!-- Notes -->
      <template #notes>
        <FormSection title="Operational notes">
          <p
            v-if="notes"
            class="whitespace-pre-wrap text-sm leading-relaxed text-slate-600 dark:text-slate-400"
          >
            {{ notes }}
          </p>
          <EmptyState
            v-else
            title="No notes"
            description="Add remarks about access, condition, or handover instructions when editing this unit."
          />
        </FormSection>

        <FormSection v-if="timestamps.updated_at" title="Record metadata" class="mt-6">
          <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
            <DetailRow label="Created" :value="formatDateTime(timestamps.created_at)" />
            <DetailRow label="Last updated" :value="formatDateTime(timestamps.updated_at)" />
            <DetailRow label="Unit ID" :value="apartment.id" mono />
          </dl>
        </FormSection>
      </template>
    </ObjectPageLayout>

    <EmptyState
      v-else
      title="Unit not found"
      description="This apartment may have been removed or you do not have access."
    >
      <template #action>
        <ErpButton :to="{ name: 'Apartments' }">Back to apartments</ErpButton>
      </template>
    </EmptyState>

    <SaleReservationFormModal
      :open="reserveOpen"
      :apartment="reserveApartment"
      @close="closeReserve"
      @saved="onReserved"
    />
  </div>
</template>

<script setup>
import { computed, ref, onMounted, defineComponent, h } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import api from '@/services/api'
import { useConfirm } from '@/composables/useConfirm'
import { useToastStore } from '@/stores/toast'
import SaleReservationFormModal from '@/components/forms/SaleReservationFormModal.vue'
import {
  ObjectPageLayout,
  ErpButton,
  FormSection,
  KpiCard,
  KpiStrip,
  StatusBadge,
  AlertBanner,
  EmptyState,
} from '@/components/erp'

const DetailRow = defineComponent({
  name: 'DetailRow',
  props: {
    label: { type: String, required: true },
    value: { type: [String, Number], default: '' },
    mono: { type: Boolean, default: false },
  },
  setup(props, { slots }) {
    return () =>
      h('div', {}, [
        h('dt', { class: 'text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400' }, props.label),
        h(
          'dd',
          {
            class: [
              'mt-1 font-medium text-slate-900 dark:text-slate-100',
              props.mono ? 'font-mono text-xs break-all' : '',
            ],
          },
          props.value ?? '—',
        ),
        slots.extra ? h('div', { class: 'mt-1' }, slots.extra()) : null,
      ])
  },
})

const FeatureChip = defineComponent({
  name: 'FeatureChip',
  props: {
    label: { type: String, required: true },
    active: { type: Boolean, default: false },
  },
  setup(props) {
    return () =>
      h(
        'span',
        {
          class: [
            'inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium',
            props.active
              ? 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-800/50 dark:bg-emerald-950/40 dark:text-emerald-300'
              : 'border-slate-200 bg-slate-50 text-slate-500 dark:border-slate-700 dark:bg-slate-800/50 dark:text-slate-400',
          ],
        },
        [
          h('span', { 'aria-hidden': 'true' }, props.active ? '✓' : '—'),
          props.label,
        ],
      )
  },
})

const route = useRoute()
const router = useRouter()
const toast = useToastStore()
const { confirm } = useConfirm()

const apartmentId = route.params.id
const loading = ref(true)
const historyLoading = ref(false)
const ownershipLoading = ref(false)
const pageError = ref('')
const apartment = ref(null)
const inventoryHistory = ref([])
const ownershipHistory = ref([])
const reserveOpen = ref(false)
const reserveApartment = ref(null)

const building = computed(() => apartment.value?.building || {})
const unit = computed(() => apartment.value?.unit || {})
const layout = computed(() => apartment.value?.layout || {})
const listing = computed(() => apartment.value?.listing || {})
const pricing = computed(() => apartment.value?.pricing || {})
const features = computed(() => apartment.value?.features || {})
const occupancy = computed(() => apartment.value?.occupancy || {})
const controls = computed(() => apartment.value?.controls || {})
const notes = computed(() => apartment.value?.notes || '')
const timestamps = computed(() => apartment.value?.timestamps || {})
const inventoryStatus = computed(() => listing.value.inventory_status || '')

const pageTitle = computed(() => {
  const num = unit.value.unit_number
  return num ? `Unit ${num}` : 'Apartment'
})

const pageSubtitle = computed(() => {
  const parts = [building.value.name, unit.value.floor != null ? `Floor ${unit.value.floor}` : null]
    .filter(Boolean)
  return parts.join(' · ') || 'Property unit'
})

const breadcrumbs = computed(() => [
  { label: 'Apartments', to: '/apartments' },
  { label: pageTitle.value },
])

const headerAttributes = computed(() => {
  const attrs = []
  if (unit.value.property_type) attrs.push(formatLabel(unit.value.property_type))
  if (listing.value.listing_type) attrs.push(formatLabel(listing.value.listing_type))
  if (layout.value.bedrooms != null) attrs.push(`${layout.value.bedrooms} bed`)
  if (layout.value.area_sqm) attrs.push(`${layout.value.area_sqm} sqm`)
  return attrs
})

const tabs = [
  { id: 'overview', label: 'Overview' },
  { id: 'occupancy', label: 'Occupancy' },
  { id: 'pricing', label: 'Pricing' },
  { id: 'features', label: 'Features' },
  { id: 'notes', label: 'Notes' },
]

const showRentPrice = computed(
  () => listing.value.listing_type === 'rental' || listing.value.listing_type === 'hybrid',
)

const showSalePrice = computed(
  () => listing.value.listing_type === 'sale' || listing.value.listing_type === 'hybrid',
)

const primaryPriceLabel = computed(() => {
  if (listing.value.listing_type === 'sale') return 'Sale price'
  if (listing.value.listing_type === 'rental') return 'Monthly rent'
  if (listing.value.listing_type === 'hybrid') return 'Listing price'
  return 'Effective price'
})

const pricingGridClass = computed(() => {
  const count = [showRentPrice.value, showSalePrice.value].filter(Boolean).length
  if (count >= 2) return 'grid-cols-2 lg:grid-cols-4'
  return 'grid-cols-2 lg:grid-cols-3'
})

const canReserveForSale = computed(
  () => listing.value.can_be_sold && inventoryStatus.value === 'available',
)

function formatLabel(value) {
  if (!value) return '—'
  return String(value).replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}

function formatStatus(status) {
  return formatLabel(status)
}

function formatCurrency(value, currency = 'USD') {
  if (value === null || value === undefined || value === '') return '—'
  return new Intl.NumberFormat(undefined, { style: 'currency', currency: currency || 'USD' }).format(Number(value))
}

function formatDateTime(iso) {
  if (!iso) return '—'
  try {
    return new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(iso))
  } catch {
    return iso
  }
}

async function fetchApartment() {
  loading.value = true
  pageError.value = ''
  try {
    const { data } = await api.get(`/apartments/${apartmentId}`)
    apartment.value = data.data ?? data
  } catch (err) {
    pageError.value = err.response?.data?.message || 'Could not load apartment details.'
    apartment.value = null
  } finally {
    loading.value = false
  }
}

async function fetchInventoryHistory() {
  historyLoading.value = true
  try {
    const { data } = await api.get(`/apartments/${apartmentId}/inventory-history`, { params: { per_page: 20 } })
    inventoryHistory.value = data.data || []
  } catch {
    inventoryHistory.value = []
  } finally {
    historyLoading.value = false
  }
}

async function fetchOwnershipHistory() {
  ownershipLoading.value = true
  try {
    const { data } = await api.get(`/apartments/${apartmentId}/ownership-history`, { params: { per_page: 20 } })
    ownershipHistory.value = data.data || []
  } catch {
    ownershipHistory.value = []
  } finally {
    ownershipLoading.value = false
  }
}

function openReserve() {
  reserveApartment.value = {
    id: apartment.value.id,
    unit_number: unit.value.unit_number,
    building: building.value,
    market_sale_price: pricing.value.market_sale_price,
    currency: pricing.value.currency,
    listing: listing.value,
    controls: { can_be_sold: listing.value.can_be_sold },
  }
  reserveOpen.value = true
}

function closeReserve() {
  reserveOpen.value = false
  reserveApartment.value = null
}

async function onReserved() {
  toast.show('Reservation created', 'success')
  closeReserve()
  await Promise.all([fetchApartment(), fetchInventoryHistory(), fetchOwnershipHistory()])
}

async function deleteApartment() {
  const confirmed = await confirm({
    title: 'Delete apartment',
    message: 'Are you sure you want to delete this unit? This cannot be undone.',
    confirmLabel: 'Delete',
    variant: 'danger',
  })
  if (!confirmed) return

  try {
    await api.delete(`/apartments/${apartmentId}`)
    toast.show('Apartment deleted', 'success')
    router.push({ name: 'Apartments' })
  } catch (err) {
    pageError.value = err.response?.data?.message || 'Could not delete apartment.'
  }
}

onMounted(async () => {
  await fetchApartment()
  if (apartment.value) {
    await Promise.all([fetchInventoryHistory(), fetchOwnershipHistory()])
  }
})
</script>
