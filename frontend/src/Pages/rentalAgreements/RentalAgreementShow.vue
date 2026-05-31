<template>

 

    <div
      class="min-h-screen bg-gray-50"
    >

      <!-- LOADING -->
      <div
        v-if="loading"
        class="flex items-center justify-center py-40"
      >

        <div
          class="text-center"
        >

          <div
            class="w-16 h-16 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin mx-auto"
          ></div>

          <p
            class="mt-6 text-sm text-gray-500"
          >
            Loading rental agreement...
          </p>

        </div>

      </div>

      <!-- CONTENT -->
      <template
        v-else-if="agreement"
      >

        <!-- PAGE HEADER -->
        <div
          class="bg-white border-b border-gray-200"
        >

          <div
            class="px-6 py-6"
          >

            <div
              class="flex flex-col 2xl:flex-row 2xl:items-start 2xl:justify-between gap-6"
            >

              <!-- LEFT -->
              <div>

                <div
                  class="flex flex-wrap items-center gap-3"
                >

                  <h1
                    class="text-3xl font-bold text-gray-900"
                  >
                    {{ agreement.agreement_number }}
                  </h1>

                  <span
                    :class="statusBadgeClass"
                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold"
                  >
                    {{ agreement.status?.label }}
                  </span>

                  <span
                    class="inline-flex items-center px-3 py-1 rounded-full bg-indigo-100 text-indigo-700 text-xs font-semibold capitalize"
                  >
                    {{ agreement.agreement_type }}
                  </span>

                </div>

                <div
                  class="mt-5 flex flex-wrap items-center gap-5 text-sm text-gray-600"
                >

                  <div>

                    <span
                      class="font-semibold text-gray-900"
                    >
                      Building:
                    </span>

                    {{ agreement.apartment?.building?.name || 'N/A' }}

                  </div>

                  <div>

                    <span
                      class="font-semibold text-gray-900"
                    >
                      Unit:
                    </span>

                    {{ agreement.apartment?.unit_number || 'N/A' }}

                  </div>

                  <div>

                    <span
                      class="font-semibold text-gray-900"
                    >
                      Tenant:
                    </span>

                    {{ agreement.tenant?.display_name || 'N/A' }}

                  </div>

                  <div>

                    <span
                      class="font-semibold text-gray-900"
                    >
                      Duration:
                    </span>

                    {{ agreement.dates?.start_date }}
                    →
                    {{ agreement.dates?.end_date }}

                  </div>

                </div>

              </div>

              <!-- ACTIONS -->
              <div
                class="flex flex-wrap items-center gap-3"
              >

                <button
                  class="px-4 py-3 rounded-xl border border-gray-300 hover:bg-gray-100 text-sm font-medium transition"
                >
                  Print
                </button>

                <button
                  class="px-4 py-3 rounded-xl border border-gray-300 hover:bg-gray-100 text-sm font-medium transition"
                >
                  Download PDF
                </button>

                <button
                  class="px-4 py-3 rounded-xl border border-gray-300 hover:bg-gray-100 text-sm font-medium transition"
                >
                  Send Email
                </button>

                <button
                  class="px-4 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium transition"
                >
                  Generate Invoice
                </button>

                <button
                  class="px-4 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium transition"
                >
                  Record Payment
                </button>

                <button
                  v-if="agreement.status?.value === 'active'"
                  class="px-4 py-3 rounded-xl bg-red-600 hover:bg-red-700 text-white text-sm font-medium transition"
                >
                  Terminate
                </button>

              </div>

            </div>

          </div>

        </div>

        <!-- MAIN CONTENT -->
        <div
          class="p-6 space-y-6"
        >

          <!-- KPI -->
          <div
            class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-5"
          >

            <div
              class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5"
            >

              <p
                class="text-sm text-gray-500"
              >
                Monthly Rent
              </p>

              <h2
                class="text-3xl font-bold text-gray-900 mt-2"
              >
                ${{ agreement.financials?.monthly_rent || 0 }}
              </h2>

            </div>

            <div
              class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5"
            >

              <p
                class="text-sm text-gray-500"
              >
                Security Deposit
              </p>

              <h2
                class="text-3xl font-bold text-gray-900 mt-2"
              >
                ${{ agreement.financials?.security_deposit || 0 }}
              </h2>

            </div>

            <div
              class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5"
            >

              <p
                class="text-sm text-gray-500"
              >
                Outstanding Balance
              </p>

              <h2
                class="text-3xl font-bold text-red-600 mt-2"
              >
                ${{ outstandingBalance }}
              </h2>

            </div>

            <div
              class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5"
            >

              <p
                class="text-sm text-gray-500"
              >
                Active Invoices
              </p>

              <h2
                class="text-3xl font-bold text-indigo-600 mt-2"
              >
                {{ invoices.length }}
              </h2>

            </div>

            <div
              class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5"
            >

              <p
                class="text-sm text-gray-500"
              >
                Maintenance Requests
              </p>

              <h2
                class="text-3xl font-bold text-amber-600 mt-2"
              >
                {{ maintenanceRequests.length }}
              </h2>

            </div>

          </div>

          <!-- GRID -->
          <div
            class="grid grid-cols-1 2xl:grid-cols-3 gap-6"
          >

            <!-- LEFT -->
            <div
              class="2xl:col-span-2 space-y-6"
            >

              <!-- AGREEMENT DETAILS -->
              <div
                class="bg-white rounded-2xl border border-gray-100 shadow-sm"
              >

                <div
                  class="px-6 py-5 border-b border-gray-100"
                >

                  <h2
                    class="text-lg font-semibold text-gray-900"
                  >
                    Agreement Overview
                  </h2>

                </div>

                <div
                  class="p-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6"
                >

                  <InfoItem
                    label="Agreement Number"
                    :value="agreement.agreement_number"
                  />

                  <InfoItem
                    label="Agreement Type"
                    :value="agreement.agreement_type"
                  />

                  <InfoItem
                    label="Status"
                    :value="agreement.status?.label"
                  />

                  <InfoItem
                    label="Start Date"
                    :value="agreement.dates?.start_date"
                  />

                  <InfoItem
                    label="End Date"
                    :value="agreement.dates?.end_date"
                  />

                  <InfoItem
                    label="Billing Cycle"
                    :value="agreement.financials?.billing_cycle"
                  />

                  <InfoItem
                    label="Payment Due Day"
                    :value="agreement.financials?.payment_due_day"
                  />

                  <InfoItem
                    label="Currency"
                    :value="agreement.financials?.currency"
                  />

                  <InfoItem
                    label="Auto Renew"
                    :value="agreement.renewal?.auto_renew ? 'Enabled' : 'Disabled'"
                  />

                </div>

              </div>

              <!-- PAYMENT HISTORY -->
              <div
                class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden"
              >

                <div
                  class="px-6 py-5 border-b border-gray-100 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4"
                >

                  <div>

                    <h2
                      class="text-lg font-semibold text-gray-900"
                    >
                      Invoice & Payment Registry
                    </h2>

                    <p
                      class="text-sm text-gray-500 mt-1"
                    >
                      Financial transactions and invoice management.
                    </p>

                  </div>

                  <input
                    v-model="invoiceSearch"
                    type="text"
                    placeholder="Search invoices..."
                    class="w-full lg:w-72 rounded-xl border border-gray-300 px-4 py-3 text-sm"
                  >

                </div>

                <div
                  class="overflow-x-auto"
                >

                  <table
                    class="w-full min-w-[1000px]"
                  >

                    <thead
                      class="bg-gray-50 border-b border-gray-100"
                    >

                      <tr>

                        <th
                          class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                        >
                          Invoice
                        </th>

                        <th
                          class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                        >
                          Due Date
                        </th>

                        <th
                          class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                        >
                          Amount
                        </th>

                        <th
                          class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                        >
                          Paid
                        </th>

                        <th
                          class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                        >
                          Balance
                        </th>

                        <th
                          class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                        >
                          Status
                        </th>

                        <th
                          class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-gray-500"
                        >
                          Actions
                        </th>

                      </tr>

                    </thead>

                    <tbody
                      class="divide-y divide-gray-100"
                    >

                      <tr
                        v-for="invoice in filteredInvoices"
                        :key="invoice.id"
                        class="hover:bg-gray-50 transition"
                      >

                        <td
                          class="px-6 py-5"
                        >

                          <div
                            class="font-semibold text-gray-900"
                          >
                            {{ invoice.invoice_number }}
                          </div>

                        </td>

                        <td
                          class="px-6 py-5 text-sm text-gray-700"
                        >
                          {{ invoice.due_date }}
                        </td>

                        <td
                          class="px-6 py-5 text-sm font-semibold text-gray-900"
                        >
                          ${{ invoice.amount }}
                        </td>

                        <td
                          class="px-6 py-5 text-sm font-semibold text-emerald-600"
                        >
                          ${{ invoice.paid_amount }}
                        </td>

                        <td
                          class="px-6 py-5 text-sm font-semibold text-red-600"
                        >
                          ${{ invoice.balance }}
                        </td>

                        <td
                          class="px-6 py-5"
                        >

                          <span
                            :class="invoiceStatusClass(invoice.status)"
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold"
                          >
                            {{ invoice.status }}
                          </span>

                        </td>

                        <td
                          class="px-6 py-5 text-right"
                        >

                          <div
                            class="flex justify-end gap-2"
                          >

                            <button
                              class="px-3 py-2 rounded-lg border border-gray-300 hover:bg-gray-100 text-sm"
                            >
                              View
                            </button>

                            <button
                              class="px-3 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm"
                            >
                              Receipt
                            </button>

                          </div>

                        </td>

                      </tr>

                    </tbody>

                  </table>

                </div>

              </div>

            </div>

            <!-- RIGHT -->
            <div
              class="space-y-6"
            >

              <!-- TENANT -->
              <div
                class="bg-white rounded-2xl border border-gray-100 shadow-sm"
              >

                <div
                  class="px-6 py-5 border-b border-gray-100"
                >

                  <h2
                    class="text-lg font-semibold text-gray-900"
                  >
                    Tenant Profile
                  </h2>

                </div>

                <div
                  class="p-6"
                >

                  <div
                    class="flex items-center gap-4"
                  >

                    <div
                      class="w-16 h-16 rounded-2xl bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-xl"
                    >
                      {{ tenantInitials }}
                    </div>

                    <div>

                      <div
                        class="font-semibold text-gray-900 text-lg"
                      >
                        {{ agreement.tenant?.display_name }}
                      </div>

                      <div
                        class="text-sm text-gray-500 mt-1"
                      >
                        {{ agreement.tenant?.phone }}
                      </div>

                    </div>

                  </div>

                  <div
                    class="mt-6 space-y-5"
                  >

                    <InfoItem
                      label="Tenant Code"
                      :value="agreement.tenant?.tenant_code"
                    />

                    <InfoItem
                      label="Email"
                      :value="agreement.tenant?.email"
                    />

                  </div>

                </div>

              </div>

              <!-- PROPERTY -->
              <div
                class="bg-white rounded-2xl border border-gray-100 shadow-sm"
              >

                <div
                  class="px-6 py-5 border-b border-gray-100"
                >

                  <h2
                    class="text-lg font-semibold text-gray-900"
                  >
                    Property Information
                  </h2>

                </div>

                <div
                  class="p-6 space-y-5"
                >

                  <InfoItem
                    label="Building"
                    :value="agreement.apartment?.building?.name"
                  />

                  <InfoItem
                    label="Unit"
                    :value="agreement.apartment?.unit_number"
                  />

                  <InfoItem
                    label="Floor"
                    :value="agreement.apartment?.floor"
                  />

                  <InfoItem
                    label="Inventory Status"
                    :value="agreement.apartment?.inventory_status"
                  />

                  <InfoItem
                    label="Bedrooms"
                    :value="agreement.apartment?.bedrooms"
                  />

                  <InfoItem
                    label="Bathrooms"
                    :value="agreement.apartment?.bathrooms"
                  />

                </div>

              </div>

              <!-- NOTES -->
              <div
                class="bg-white rounded-2xl border border-gray-100 shadow-sm"
              >

                <div
                  class="px-6 py-5 border-b border-gray-100"
                >

                  <h2
                    class="text-lg font-semibold text-gray-900"
                  >
                    Notes & Attachments
                  </h2>

                </div>

                <div
                  class="p-6"
                >

                  <textarea
                    rows="6"
                    class="w-full rounded-2xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 px-4 py-4 text-sm"
                    placeholder="Operational notes..."
                  >{{ agreement.notes?.agreement_notes }}</textarea>

                  <div
                    class="mt-4 flex gap-3"
                  >

                    <button
                      class="px-4 py-3 rounded-xl border border-gray-300 hover:bg-gray-100 text-sm font-medium"
                    >
                      Upload File
                    </button>

                    <button
                      class="px-4 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium"
                    >
                      Save Notes
                    </button>

                  </div>

                </div>

              </div>

            </div>

          </div>

        </div>

      </template>

    </div>

 

</template>

<script setup>

import {

  ref,
  computed,
  onMounted,

} from 'vue'

import {

  useRoute,

} from 'vue-router'

import api from '../../services/api'

import DashboardLayout from '../../layouts/DashboardLayout.vue'

/*
|--------------------------------------------------------------------------
| Route
|--------------------------------------------------------------------------
*/

const route = useRoute()

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const loading = ref(false)

const agreement = ref(null)

const invoices = ref([])

const maintenanceRequests = ref([])

const invoiceSearch = ref('')

/*
|--------------------------------------------------------------------------
| Load Agreement
|--------------------------------------------------------------------------
*/

const loadAgreement = async () => {

  try {

    loading.value = true

    const response =
      await api.get(

        `/rental-agreements/${route.params.id}`
      )

    agreement.value =
      response.data.data

  } catch (error) {

    console.error(

      'Failed to load agreement:',
      error
    )

  } finally {

    loading.value = false
  }
}

/*
|--------------------------------------------------------------------------
| Load Invoices
|--------------------------------------------------------------------------
*/

const loadInvoices = async () => {

  try {

    /*
    |--------------------------------------------------------------------------
    | Replace with real API later
    |--------------------------------------------------------------------------
    */

    invoices.value = [

      {

        id: 1,

        invoice_number:
          'INV-2026-001',

        due_date:
          '2026-06-05',

        amount:
          3000,

        paid_amount:
          1750,

        balance:
          1250,

        status:
          'Partial',
      },

      {

        id: 2,

        invoice_number:
          'INV-2026-002',

        due_date:
          '2026-07-05',

        amount:
          3000,

        paid_amount:
          3000,

        balance:
          0,

        status:
          'Paid',
      },
    ]

  } catch (error) {

    console.error(error)
  }
}

/*
|--------------------------------------------------------------------------
| Computed
|--------------------------------------------------------------------------
*/

const tenantInitials = computed(() => {

  return agreement.value
    ?.tenant
    ?.display_name
    ?.split(' ')
    ?.map(name => name[0])
    ?.join('')
    ?.substring(0, 2)
})

const outstandingBalance = computed(() => {

  return invoices.value

    .reduce(

      (sum, invoice) => {

        return (
          sum +
          Number(
            invoice.balance || 0
          )
        )

      }, 0
    )

    .toLocaleString()
})

const filteredInvoices = computed(() => {

  const search =
    invoiceSearch.value
      .toLowerCase()

  return invoices.value.filter(

    invoice => {

      return (

        invoice.invoice_number
          ?.toLowerCase()
          .includes(search)
      )
    }
  )
})

const statusBadgeClass = computed(() => {

  switch (
    agreement.value?.status?.value
  ) {

    case 'active':

      return 'bg-green-100 text-green-700'

    case 'draft':

      return 'bg-yellow-100 text-yellow-700'

    case 'terminated':

      return 'bg-red-100 text-red-700'

    default:

      return 'bg-gray-100 text-gray-700'
  }
})

/*
|--------------------------------------------------------------------------
| Invoice Status
|--------------------------------------------------------------------------
*/

const invoiceStatusClass = (
  status
) => {

  switch (status) {

    case 'Paid':

      return 'bg-green-100 text-green-700'

    case 'Partial':

      return 'bg-amber-100 text-amber-700'

    case 'Overdue':

      return 'bg-red-100 text-red-700'

    default:

      return 'bg-gray-100 text-gray-700'
  }
}

/*
|--------------------------------------------------------------------------
| Mounted
|--------------------------------------------------------------------------
*/

onMounted(async () => {

  await Promise.all([

    loadAgreement(),

    loadInvoices(),
  ])
})

</script>

<script>

export default {

  components: {

    InfoItem: {

      props: {

        label: String,

        value: String,
      },

      template: `

        <div>

          <label
            class="text-xs uppercase tracking-wide text-gray-500 font-semibold"
          >
            {{ label }}
          </label>

          <div
            class="mt-1 text-sm font-medium text-gray-900"
          >
            {{ value || 'N/A' }}
          </div>

        </div>
      `,
    },
  },
}

</script>