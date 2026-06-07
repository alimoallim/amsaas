<script setup>
import {
  computed,
  onMounted,
  reactive,
  ref,
  watch,
} from 'vue'

import {
  useRoute,
  useRouter,
} from 'vue-router'

import api from '@/services/api'
import { ErpDateInput } from '@/components/erp'

/*
|--------------------------------------------------------------------------
| Router
|--------------------------------------------------------------------------
*/

const route =
  useRoute()

const router =
  useRouter()

/*
|--------------------------------------------------------------------------
| Reactive State
|--------------------------------------------------------------------------
*/

const loading =
  ref(false)

const submitting =
  ref(false)

const meters =
  ref([])

const reading =
  ref(null)

const latestReading =
  ref(null)

const consumptionPreview =
  ref(0)

const anomalyWarning =
  ref(null)

const form = reactive({

  meter_id: '',

  reading_date: '',

  current_reading: '',

  reading_type: 'actual',

  reading_source: 'manual',

  notes: '',

  attachment: null,
})

const errors =
  reactive({})

/*
|--------------------------------------------------------------------------
| Computed
|--------------------------------------------------------------------------
*/

const selectedMeter =
  computed(() => {

    return meters.value.find(

      meter =>

        meter.id
        ===
        form.meter_id
    )
  })

const previousReading =
  computed(() => {

    if (
      reading.value
    ) {

      return Number(

        reading.value
          .reading
          .previous_reading
      )
    }

    return 0
  })

const utilityType =
  computed(() => {

    return selectedMeter.value
      ?.utility_type
      ?.label
      ?? 'N/A'
  })

const measurementUnit =
  computed(() => {

    return selectedMeter.value
      ?.measurement_unit
      ?? ''
  })

/*
|--------------------------------------------------------------------------
| Fetch Meters
|--------------------------------------------------------------------------
*/

const fetchMeters =
  async () => {

    try {

      const response =
        await api.get(
          '/meters',
          {

            params: {

              status:
                'active',
            },
          }
        )

      meters.value =
        response.data.data
    }

    catch (error) {

      console.error(
        'Failed to fetch meters:',
        error
      )
    }
  }

/*
|--------------------------------------------------------------------------
| Fetch Reading
|--------------------------------------------------------------------------
*/

const fetchReading =
  async () => {

    try {

      loading.value = true

      const response =
        await api.get(

          `/meter-readings/${route.params.id}`
        )

      reading.value =
        response.data.data

      populateForm()
    }

    catch (error) {

      console.error(
        'Failed to fetch reading:',
        error
      )
    }

    finally {

      loading.value = false
    }
  }

/*
|--------------------------------------------------------------------------
| Populate Form
|--------------------------------------------------------------------------
*/

const populateForm =
  () => {

    if (
      !reading.value
    ) {

      return
    }

    form.meter_id =
      reading.value.meter.id

    form.reading_date =
      reading.value.reading
        .reading_date

    form.current_reading =
      reading.value.reading
        .current_reading

    form.reading_type =
      reading.value.reading_type
        .value

    form.reading_source =
      reading.value.reading_source
        .value

    form.notes =
      reading.value.notes
      ?? ''

    calculateConsumption()
  }

/*
|--------------------------------------------------------------------------
| Consumption Calculation
|--------------------------------------------------------------------------
*/

const calculateConsumption =
  () => {

    const current =
      Number(
        form.current_reading
      )

    const previous =
      Number(
        previousReading.value
      )

    consumptionPreview.value =
      current - previous

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (
      current < previous
    ) {

      anomalyWarning.value =
        'Current reading cannot be less than previous reading.'

      return
    }

    /*
    |--------------------------------------------------------------------------
    | Operational Intelligence
    |--------------------------------------------------------------------------
    */

    if (
      consumptionPreview.value > 10000
    ) {

      anomalyWarning.value =
        'Abnormally high utility consumption detected.'
    }

    else if (
      consumptionPreview.value === 0
    ) {

      anomalyWarning.value =
        'Zero utility consumption detected.'
    }

    else {

      anomalyWarning.value =
        null
    }
  }

/*
|--------------------------------------------------------------------------
| File Upload
|--------------------------------------------------------------------------
*/

const handleAttachment =
  (event) => {

    form.attachment =
      event.target.files[0]
  }

/*
|--------------------------------------------------------------------------
| Submit Update
|--------------------------------------------------------------------------
*/

const submit =
  async () => {

    try {

      submitting.value = true

      Object.keys(errors)
        .forEach(

          key =>

            delete errors[key]
        )

      const payload =
        new FormData()

      payload.append(
        '_method',
        'PUT'
      )

      payload.append(
        'meter_id',
        form.meter_id
      )

      payload.append(
        'reading_date',
        form.reading_date
      )

      payload.append(
        'current_reading',
        form.current_reading
      )

      payload.append(
        'reading_type',
        form.reading_type
      )

      payload.append(
        'reading_source',
        form.reading_source
      )

      payload.append(
        'notes',
        form.notes
      )

      if (
        form.attachment
      ) {

        payload.append(
          'attachment',
          form.attachment
        )
      }

      await api.post(

        `/meter-readings/${route.params.id}`,

        payload,

        {

          headers: {

            'Content-Type':
              'multipart/form-data',
          },
        }
      )

      router.push(

        `/meter-readings/${route.params.id}`
      )
    }

    catch (error) {

      console.error(
        'Meter reading update failed:',
        error
      )

      if (
        error.response?.status === 422
      ) {

        Object.assign(

          errors,

          error.response.data.errors
        )
      }
    }

    finally {

      submitting.value = false
    }
  }

/*
|--------------------------------------------------------------------------
| Watchers
|--------------------------------------------------------------------------
*/

watch(

  () => form.current_reading,

  () => {

    calculateConsumption()
  }
)

/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/

onMounted(async () => {

  await fetchMeters()

  await fetchReading()
})
</script>

<template>
    <DashboardLayout>
  <div
    class="
      min-h-screen
      bg-slate-50
      p-6
    "
  >
    <!-- Loading -->

    <div
      v-if="loading"
      class="
        flex
        min-h-[500px]
        items-center
        justify-center
      "
    >
      <div
        class="
          text-sm
          text-slate-500
        "
      >
        Loading operational reading...
      </div>
    </div>

    <!-- Content -->

    <div
      v-else
      class="
        space-y-6
      "
    >
      <!-- Header -->

      <div
        class="
          flex
          flex-col
          gap-4
          rounded-3xl
          border
          bg-white
          p-6
          shadow-sm
          xl:flex-row
          xl:items-center
          xl:justify-between
        "
      >
        <div>
          <h1
            class="
              text-3xl
              font-bold
              text-slate-900
            "
          >
            Edit Meter Reading
          </h1>

          <p
            class="
              mt-2
              text-sm
              text-slate-500
            "
          >
            Operational utility reading adjustment and correction workflow.
          </p>
        </div>

        <div
          class="
            flex
            flex-wrap
            gap-3
          "
        >
          <RouterLink
            :to="`/meter-readings/${route.params.id}`"
            class="
              rounded-xl
              border
              border-slate-300
              bg-white
              px-5
              py-3
              text-sm
              font-semibold
              text-slate-700
              hover:bg-slate-100
            "
          >
            Back to Reading
          </RouterLink>
        </div>
      </div>

      <!-- Layout -->

      <div
        class="
          grid
          grid-cols-1
          gap-6
          xl:grid-cols-3
        "
      >
        <!-- Form -->

        <div
          class="
            xl:col-span-2
          "
        >
          <div
            class="
              rounded-3xl
              border
              bg-white
              p-6
              shadow-sm
            "
          >
            <form
              @submit.prevent="submit"
              class="
                space-y-6
              "
            >
              <!-- Meter -->

              <div>
                <label
                  class="
                    mb-2
                    block
                    text-sm
                    font-semibold
                    text-slate-700
                  "
                >
                  Meter
                </label>

                <select
                  v-model="form.meter_id"
                  disabled
                  class="
                    w-full
                    rounded-xl
                    border
                    border-slate-300
                    bg-slate-100
                    px-4
                    py-3
                    text-sm
                  "
                >
                  <option
                    v-for="meter in meters"
                    :key="meter.id"
                    :value="meter.id"
                  >
                    {{
                      meter.meter_number
                    }}
                    •
                    {{
                      meter.utility_type?.label
                    }}
                  </option>
                </select>
              </div>

              <!-- Grid -->

              <div
                class="
                  grid
                  grid-cols-1
                  gap-6
                  md:grid-cols-2
                "
              >
                <!-- Date -->

                <div>
                  <label
                    class="
                      mb-2
                      block
                      text-sm
                      font-semibold
                      text-slate-700
                    "
                  >
                    Reading Date
                  </label>

                  <ErpDateInput v-model="form.reading_date" placeholder="Reading date" />
                </div>

                <!-- Current -->

                <div>
                  <label
                    class="
                      mb-2
                      block
                      text-sm
                      font-semibold
                      text-slate-700
                    "
                  >
                    Current Reading
                  </label>

                  <input
                    v-model="form.current_reading"
                    type="number"
                    step="0.0001"
                    class="
                      w-full
                      rounded-xl
                      border
                      border-slate-300
                      px-4
                      py-3
                      text-sm
                    "
                  />

                  <p
                    v-if="
                      errors.current_reading
                    "
                    class="
                      mt-2
                      text-sm
                      text-red-600
                    "
                  >
                    {{
                      errors.current_reading[0]
                    }}
                  </p>
                </div>
              </div>

              <!-- Type + Source -->

              <div
                class="
                  grid
                  grid-cols-1
                  gap-6
                  md:grid-cols-2
                "
              >
                <div>
                  <label
                    class="
                      mb-2
                      block
                      text-sm
                      font-semibold
                      text-slate-700
                    "
                  >
                    Reading Type
                  </label>

                  <select
                    v-model="form.reading_type"
                    class="
                      w-full
                      rounded-xl
                      border
                      border-slate-300
                      px-4
                      py-3
                      text-sm
                    "
                  >
                    <option value="actual">
                      Actual
                    </option>

                    <option value="estimated">
                      Estimated
                    </option>

                    <option value="adjusted">
                      Adjusted
                    </option>

                    <option value="imported">
                      Imported
                    </option>
                  </select>
                </div>

                <div>
                  <label
                    class="
                      mb-2
                      block
                      text-sm
                      font-semibold
                      text-slate-700
                    "
                  >
                    Reading Source
                  </label>

                  <select
                    v-model="form.reading_source"
                    class="
                      w-full
                      rounded-xl
                      border
                      border-slate-300
                      px-4
                      py-3
                      text-sm
                    "
                  >
                    <option value="manual">
                      Manual
                    </option>

                    <option value="smart_meter">
                      Smart Meter
                    </option>

                    <option value="api">
                      API
                    </option>

                    <option value="csv_import">
                      CSV Import
                    </option>
                  </select>
                </div>
              </div>

              <!-- Notes -->

              <div>
                <label
                  class="
                    mb-2
                    block
                    text-sm
                    font-semibold
                    text-slate-700
                  "
                >
                  Operational Notes
                </label>

                <textarea
                  v-model="form.notes"
                  rows="5"
                  class="
                    w-full
                    rounded-xl
                    border
                    border-slate-300
                    px-4
                    py-3
                    text-sm
                  "
                />
              </div>

              <!-- Attachment -->

              <div>
                <label
                  class="
                    mb-2
                    block
                    text-sm
                    font-semibold
                    text-slate-700
                  "
                >
                  Replace Attachment
                </label>

                <input
                  type="file"
                  @change="handleAttachment"
                  class="
                    w-full
                    rounded-xl
                    border
                    border-slate-300
                    bg-white
                    px-4
                    py-3
                    text-sm
                  "
                />
              </div>

              <!-- Submit -->

              <div
                class="
                  flex
                  justify-end
                  gap-3
                  pt-4
                "
              >
                <RouterLink
                  :to="`/meter-readings/${route.params.id}`"
                  class="
                    rounded-xl
                    border
                    border-slate-300
                    bg-white
                    px-5
                    py-3
                    text-sm
                    font-semibold
                    text-slate-700
                  "
                >
                  Cancel
                </RouterLink>

                <button
                  type="submit"
                  :disabled="submitting"
                  class="
                    rounded-xl
                    bg-indigo-600
                    px-6
                    py-3
                    text-sm
                    font-semibold
                    text-white
                    shadow-sm
                    hover:bg-indigo-700
                    disabled:opacity-50
                  "
                >
                  {{
                    submitting
                      ? 'Updating...'
                      : 'Update Reading'
                  }}
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Intelligence Sidebar -->

        <div
          class="
            space-y-6
          "
        >
          <!-- Consumption Intelligence -->

          <div
            class="
              rounded-3xl
              border
              bg-white
              p-6
              shadow-sm
            "
          >
            <h2
              class="
                text-lg
                font-bold
                text-slate-900
              "
            >
              Consumption Intelligence
            </h2>

            <div
              class="
                mt-6
                space-y-5
              "
            >
              <div>
                <p
                  class="
                    text-xs
                    font-semibold
                    uppercase
                    tracking-wide
                    text-slate-400
                  "
                >
                  Utility Type
                </p>

                <div
                  class="
                    mt-2
                    inline-flex
                    rounded-full
                    bg-indigo-100
                    px-3
                    py-1
                    text-xs
                    font-semibold
                    text-indigo-700
                  "
                >
                  {{
                    utilityType
                  }}
                </div>
              </div>

              <div>
                <p
                  class="
                    text-xs
                    font-semibold
                    uppercase
                    tracking-wide
                    text-slate-400
                  "
                >
                  Previous Reading
                </p>

                <h3
                  class="
                    mt-2
                    text-3xl
                    font-bold
                  "
                >
                  {{
                    previousReading
                  }}
                </h3>
              </div>

              <div>
                <p
                  class="
                    text-xs
                    font-semibold
                    uppercase
                    tracking-wide
                    text-slate-400
                  "
                >
                  Consumption Preview
                </p>

                <h3
                  class="
                    mt-2
                    text-4xl
                    font-bold
                    text-emerald-600
                  "
                >
                  {{
                    consumptionPreview.toFixed(2)
                  }}

                  <span
                    class="
                      text-sm
                      font-medium
                      text-slate-500
                    "
                  >
                    {{
                      measurementUnit
                    }}
                  </span>
                </h3>
              </div>

              <!-- Warning -->

              <div
                v-if="anomalyWarning"
                class="
                  rounded-2xl
                  border
                  border-red-200
                  bg-red-50
                  p-5
                "
              >
                <div
                  class="
                    flex
                    gap-3
                  "
                >
                  <div
                    class="
                      mt-1
                      h-2
                      w-2
                      rounded-full
                      bg-red-500
                    "
                  />

                  <div>
                    <h4
                      class="
                        text-sm
                        font-bold
                        text-red-700
                      "
                    >
                      Operational Warning
                    </h4>

                    <p
                      class="
                        mt-2
                        text-sm
                        text-red-700
                      "
                    >
                      {{
                        anomalyWarning
                      }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Guidance -->

              <div
                class="
                  rounded-2xl
                  border
                  border-amber-200
                  bg-amber-50
                  p-5
                "
              >
                <h4
                  class="
                    text-sm
                    font-bold
                    text-amber-700
                  "
                >
                  Operational Guidance
                </h4>

                <ul
                  class="
                    mt-4
                    space-y-2
                    text-sm
                    text-amber-700
                  "
                >
                  <li>
                    • Editing approved readings should require audit review
                  </li>

                  <li>
                    • Attach evidence for operational adjustments
                  </li>

                  <li>
                    • Verify physical meter before correction
                  </li>

                  <li>
                    • Document anomaly reasons carefully
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <!-- Audit -->

          <div
            class="
              rounded-3xl
              border
              bg-white
              p-6
              shadow-sm
            "
          >
            <h2
              class="
                text-lg
                font-bold
                text-slate-900
              "
            >
              Audit Information
            </h2>

            <div
              class="
                mt-6
                space-y-5
              "
            >
              <div>
                <p
                  class="
                    text-xs
                    font-semibold
                    uppercase
                    tracking-wide
                    text-slate-400
                  "
                >
                  Created At
                </p>

                <p
                  class="
                    mt-2
                    text-sm
                    font-medium
                  "
                >
                  {{
                    reading?.audit
                      ?.created_at
                  }}
                </p>
              </div>

              <div>
                <p
                  class="
                    text-xs
                    font-semibold
                    uppercase
                    tracking-wide
                    text-slate-400
                  "
                >
                  Updated At
                </p>

                <p
                  class="
                    mt-2
                    text-sm
                    font-medium
                  "
                >
                  {{
                    reading?.audit
                      ?.updated_at
                  }}
                </p>
              </div>

              <div>
                <p
                  class="
                    text-xs
                    font-semibold
                    uppercase
                    tracking-wide
                    text-slate-400
                  "
                >
                  Current Status
                </p>

                <div
                  class="
                    mt-2
                    inline-flex
                    rounded-full
                    bg-slate-100
                    px-3
                    py-1
                    text-xs
                    font-semibold
                    text-slate-700
                  "
                >
                  {{
                    reading?.status
                      ?.label
                  }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
    </DashboardLayout>
</template>