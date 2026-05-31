<template>
    <div class="min-h-screen bg-slate-50 p-6">

      <!-- ===================================================== -->
      <!-- PAGE HEADER -->
      <!-- ===================================================== -->

      <div
        class="mb-8 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between"
      >

        <div>

          <div class="mb-3 flex items-center gap-3">

            <RouterLink
              :to="{ name: 'Apartments' }"
              class="text-sm font-medium text-slate-500 transition hover:text-slate-800"
            >
              ← Back to Apartments
            </RouterLink>

            <span
              class="rounded-full px-3 py-1 text-xs font-bold"
              :class="
                statusBadgeClass(
                  apartment.listing.inventory_status
                )
              "
            >
              {{
                formatStatus(
                  apartment.listing.inventory_status
                )
              }}
            </span>

          </div>

          <h1 class="text-4xl font-bold text-slate-900">

            Apartment
            {{ apartment.unit.unit_number || '—' }}

          </h1>

          <p class="mt-2 text-slate-500">

            {{ apartment.building.name || 'Building' }}

            •

            {{ apartment.building.city || 'City' }}

          </p>

        </div>

        <!-- ACTIONS -->

        <div class="flex items-center gap-3">

          <RouterLink
            v-if="apartment.id"
            :to="{
              name: 'ApartmentEdit',
              params: {
                id: apartment.id
              }
            }"
            class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white transition hover:bg-blue-700"
          >
            Edit Apartment
          </RouterLink>

          <button
            @click="deleteApartment"
            class="inline-flex items-center gap-2 rounded-xl border border-red-200 px-5 py-3 font-semibold text-red-600 transition hover:bg-red-50"
          >
            Delete
          </button>

        </div>

      </div>

      <!-- ===================================================== -->
      <!-- LOADING -->
      <!-- ===================================================== -->

      <div
        v-if="loading"
        class="rounded-3xl border border-slate-200 bg-white p-16 text-center"
      >

        <div
          class="mx-auto mb-5 h-12 w-12 animate-spin rounded-full border-4 border-blue-600 border-t-transparent"
        />

        <p class="text-slate-500">
          Loading apartment information...
        </p>

      </div>

      <!-- ===================================================== -->
      <!-- CONTENT -->
      <!-- ===================================================== -->

      <div
        v-else
        class="grid grid-cols-1 gap-6 xl:grid-cols-4"
      >

        <!-- ================================================= -->
        <!-- MAIN -->
        <!-- ================================================= -->

        <div class="space-y-6 xl:col-span-3">

          <!-- ================================================= -->
          <!-- HERO -->
          <!-- ================================================= -->

          <div
            class="overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 shadow-xl"
          >

            <div class="p-8">

              <div
                class="flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between"
              >

                <!-- LEFT -->

                <div>

                  <div
                    class="mb-4 flex flex-wrap items-center gap-2"
                  >

                    <!-- PROPERTY TYPE -->

                    <span
                      class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-white backdrop-blur"
                    >
                      {{ apartment.unit.property_type }}
                    </span>

                    <!-- LISTING TYPE -->

                    <span
                      class="rounded-full px-3 py-1 text-xs font-semibold"
                      :class="
                        listingBadgeClass(
                          apartment.listing.listing_type
                        )
                      "
                    >
                      {{ apartment.listing.listing_type }}
                    </span>

                  </div>

                  <h2 class="mb-2 text-5xl font-bold text-white">
                    {{ apartment.unit.unit_number }}
                  </h2>

                  <p class="text-slate-300">

                    Floor
                    {{ apartment.unit.floor || '-' }}

                    •

                    {{ apartment.layout.bedrooms || 0 }}
                    Bedrooms

                    •

                    {{ apartment.layout.bathrooms || 0 }}
                    Bathrooms

                    •

                    {{ apartment.layout.area_sqm || 0 }}
                    sqm

                  </p>

                </div>

                <!-- RIGHT -->

                <div
                  class="min-w-[280px] rounded-3xl bg-white/10 p-6 backdrop-blur-md"
                >

                  <div class="mb-2 text-sm text-slate-300">
                    Effective Price
                  </div>

                  <div class="text-4xl font-bold text-white">

                    {{
                      formatCurrency(
                        apartment.pricing.effective_price,
                        apartment.pricing.currency
                      )
                    }}

                  </div>

                  <div class="mt-3 text-sm text-slate-300">

                    <template
                      v-if="
                        apartment.listing.listing_type === 'sale'
                      "
                    >
                      Sale Listing
                    </template>

                    <template
                      v-else-if="
                        apartment.listing.listing_type === 'rental'
                      "
                    >
                      Monthly Rental
                    </template>

                    <template
                      v-else-if="
                        apartment.listing.listing_type === 'hybrid'
                      "
                    >
                      Hybrid Listing
                    </template>

                    <template v-else>
                      Inventory Asset
                    </template>

                  </div>

                </div>

              </div>

            </div>

          </div>

          <!-- ================================================= -->
          <!-- APARTMENT INFORMATION -->
          <!-- ================================================= -->

          <div
            class="rounded-3xl border border-slate-200 bg-white shadow-sm"
          >

            <div
              class="border-b border-slate-100 px-6 py-5"
            >

              <h3 class="text-lg font-bold text-slate-900">
                Apartment Information
              </h3>

            </div>

            <div
              class="grid grid-cols-1 gap-6 p-6 md:grid-cols-2"
            >

              <InfoCard
                label="Building"
                :value="apartment.building.name"
              />

              <InfoCard
                label="Property Type"
                :value="apartment.unit.property_type"
              />

              <InfoCard
                label="Listing Type"
                :value="apartment.listing.listing_type"
              />

              <InfoCard
                label="Inventory Status"
                :value="
                  apartment.listing.inventory_status
                "
              />

              <InfoCard
                label="Bedrooms"
                :value="apartment.layout.bedrooms"
              />

              <InfoCard
                label="Bathrooms"
                :value="apartment.layout.bathrooms"
              />

              <InfoCard
                label="Area"
                :value="`${apartment.layout.area_sqm || 0} sqm`"
              />

              <InfoCard
                label="Currency"
                :value="apartment.pricing.currency"
              />

            </div>

          </div>

          <!-- ================================================= -->
          <!-- PRICING -->
          <!-- ================================================= -->

          <div
            class="rounded-3xl border border-slate-200 bg-white shadow-sm"
          >

            <div
              class="border-b border-slate-100 px-6 py-5"
            >

              <h3 class="text-lg font-bold text-slate-900">
                Pricing & Commercial
              </h3>

            </div>

            <div
              class="grid grid-cols-1 gap-6 p-6 md:grid-cols-3"
            >

              <InfoCard
                label="Market Rent"
                :value="
                  apartment.pricing.market_rent_price
                    ? formatCurrency(
                        apartment.pricing.market_rent_price,
                        apartment.pricing.currency
                      )
                    : '—'
                "
              />

              <InfoCard
                label="Market Sale Price"
                :value="
                  apartment.pricing.market_sale_price
                    ? formatCurrency(
                        apartment.pricing.market_sale_price,
                        apartment.pricing.currency
                      )
                    : '—'
                "
              />

              <InfoCard
                label="Security Deposit"
                :value="
                  apartment.pricing.security_deposit
                    ? formatCurrency(
                        apartment.pricing.security_deposit,
                        apartment.pricing.currency
                      )
                    : '—'
                "
              />

            </div>

          </div>

          <!-- ================================================= -->
          <!-- FEATURES -->
          <!-- ================================================= -->

          <div
            class="rounded-3xl border border-slate-200 bg-white shadow-sm"
          >

            <div
              class="border-b border-slate-100 px-6 py-5"
            >

              <h3 class="text-lg font-bold text-slate-900">
                Features & Amenities
              </h3>

            </div>

            <div class="p-6">

              <div class="flex flex-wrap gap-3">

                <FeaturePill
                  label="Balcony"
                  :active="
                    apartment.features.has_balcony
                  "
                />

                <FeaturePill
                  label="Parking"
                  :active="
                    apartment.features.has_parking
                  "
                />

                <FeaturePill
                  label="Storage"
                  :active="
                    apartment.features.has_storage
                  "
                />

                <FeaturePill
                  label="Furnished"
                  :active="
                    apartment.features.is_furnished
                  "
                />

              </div>

            </div>

          </div>

          <!-- ================================================= -->
          <!-- NOTES -->
          <!-- ================================================= -->

          <div
            class="rounded-3xl border border-slate-200 bg-white shadow-sm"
          >

            <div
              class="border-b border-slate-100 px-6 py-5"
            >

              <h3 class="text-lg font-bold text-slate-900">
                Notes & Operational Remarks
              </h3>

            </div>

            <div class="p-6">

              <p class="leading-relaxed text-slate-600">

                {{
                  apartment.notes ||
                  'No operational notes available.'
                }}

              </p>

            </div>

          </div>

        </div>

        <!-- ================================================= -->
        <!-- SIDEBAR -->
        <!-- ================================================= -->

        <div class="space-y-6">

          <!-- STATUS -->

          <div
            class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
          >

            <h3
              class="mb-5 text-sm font-bold uppercase tracking-wide text-slate-500"
            >
              Inventory Lifecycle
            </h3>

            <div class="space-y-5">

              <SidebarRow
                label="Status"
                :value="
                  formatStatus(
                    apartment.listing.inventory_status
                  )
                "
              />

              <SidebarRow
                label="Listing Type"
                :value="
                  apartment.listing.listing_type
                "
              />

              <SidebarRow
                label="Property Type"
                :value="
                  apartment.unit.property_type
                "
              />

              <SidebarRow
                label="Apartment ID"
                :value="apartment.id"
                small
              />

            </div>

          </div>

          <!-- BUILDING -->

          <div
            class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
          >

            <h3
              class="mb-5 text-sm font-bold uppercase tracking-wide text-slate-500"
            >
              Building Information
            </h3>

            <div class="space-y-5">

              <SidebarRow
                label="Building"
                :value="apartment.building.name"
              />

              <SidebarRow
                label="City"
                :value="apartment.building.city"
              />

              <SidebarRow
                label="Country"
                :value="apartment.building.country"
              />

              <SidebarRow
                label="Currency"
                :value="
                  apartment.building.currency_code
                "
              />

            </div>

          </div>

        </div>

      </div>

    </div>

 
</template>

<script setup>

import {
  reactive,
  ref,
  onMounted
} from 'vue'

import {
  useRouter,
  useRoute
} from 'vue-router'

import api from '../../services/api'

import DashboardLayout from '../../layouts/DashboardLayout.vue'

/*
|--------------------------------------------------------------------------
| Components
|--------------------------------------------------------------------------
*/

const InfoCard = {

  props: {

    label: String,

    value: [String, Number],
  },

  template: `
    <div
      class="rounded-2xl border border-slate-100 bg-slate-50 p-5"
    >
      <div
        class="mb-2 text-xs uppercase tracking-wide text-slate-500"
      >
        {{ label }}
      </div>

      <div
        class="text-lg font-semibold capitalize text-slate-800"
      >
        {{ value || '—' }}
      </div>
    </div>
  `,
}

const SidebarRow = {

  props: {

    label: String,

    value: [String, Number],

    small: Boolean,
  },

  template: `
    <div
      class="flex items-center justify-between gap-3"
    >
      <span
        class="text-sm text-slate-500"
      >
        {{ label }}
      </span>

      <span
        :class="[
          small
            ? 'text-xs break-all text-right'
            : 'text-sm',
          'font-medium text-slate-800'
        ]"
      >
        {{ value || '—' }}
      </span>
    </div>
  `,
}

const FeaturePill = {

  props: {

    label: String,

    active: Boolean,
  },

  template: `
    <div
      :class="[
        active
          ? 'border-green-200 bg-green-50 text-green-700'
          : 'border-slate-200 bg-slate-100 text-slate-500',
        'inline-flex items-center rounded-xl border px-4 py-2 text-sm font-medium'
      ]"
    >
      {{ label }}
    </div>
  `,
}

/*
|--------------------------------------------------------------------------
| Router
|--------------------------------------------------------------------------
*/

const router = useRouter()

const route = useRoute()

const apartmentId = route.params.id

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const loading = ref(true)

const apartment = reactive({

  id: '',

  company_id: '',

  building: {

    name: '',

    city: '',

    country: '',

    currency_code: '',
  },

  unit: {

    unit_number: '',

    floor: '',

    property_type: '',
  },

  layout: {

    bedrooms: 0,

    bathrooms: 0,

    area_sqm: '',
  },

  listing: {

    listing_type: '',

    inventory_status: '',
  },

  pricing: {

    market_rent_price: null,

    market_sale_price: null,

    security_deposit: null,

    effective_price: null,

    currency: 'USD',
  },

  features: {

    has_balcony: false,

    has_parking: false,

    has_storage: false,

    is_furnished: false,
  },

  notes: '',
})

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

const formatStatus = (status) => {

  if (!status) return '—'

  return status
    .replaceAll('_', ' ')
}

const formatCurrency = (
  value,
  currency = 'USD'
) => {

  if (
    value === null
    || value === undefined
    || value === ''
  ) {

    return '—'
  }

  return new Intl.NumberFormat(

    'en-US',

    {

      style: 'currency',

      currency,
    }

  ).format(value)
}

const statusBadgeClass = (status) => {

  const map = {

    available:
      'bg-green-100 text-green-700',

    occupied:
      'bg-blue-100 text-blue-700',

    reserved:
      'bg-amber-100 text-amber-700',

    under_contract:
      'bg-purple-100 text-purple-700',

    sold:
      'bg-red-100 text-red-700',

    maintenance:
      'bg-orange-100 text-orange-700',

    blocked:
      'bg-slate-200 text-slate-700',
  }

  return map[status]
    || 'bg-slate-100 text-slate-700'
}

const listingBadgeClass = (type) => {

  const map = {

    rental:
      'bg-blue-100 text-blue-700',

    sale:
      'bg-emerald-100 text-emerald-700',

    hybrid:
      'bg-purple-100 text-purple-700',

    not_listed:
      'bg-slate-200 text-slate-700',
  }

  return map[type]
    || 'bg-slate-100 text-slate-700'
}

/*
|--------------------------------------------------------------------------
| Fetch Apartment
|--------------------------------------------------------------------------
*/

const fetchApartment = async () => {

  loading.value = true

  try {

    const response = await api.get(
      `/apartments/${apartmentId}`
    )

    const data =
      response.data.data

    apartment.id =
      data.id || ''

    apartment.company_id =
      data.company_id || ''

    apartment.building =
      data.building || {}

    apartment.unit =
      data.unit || {}

    apartment.layout =
      data.layout || {}

    apartment.listing =
      data.listing || {}

    apartment.pricing =
      data.pricing || {}

    apartment.features =
      data.features || {}

    apartment.notes =
      data.notes || ''

  } catch (error) {

    console.error(error)

  } finally {

    loading.value = false
  }
}

/*
|--------------------------------------------------------------------------
| Delete Apartment
|--------------------------------------------------------------------------
*/

const deleteApartment = async () => {

  const confirmed = confirm(
    'Are you sure you want to delete this apartment?'
  )

  if (!confirmed) return

  try {

    await api.delete(
      `/apartments/${apartmentId}`
    )

    router.push({

      name: 'Apartments',
    })

  } catch (error) {

    console.error(error)
  }
}

/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/

onMounted(() => {

  fetchApartment()
})

</script>