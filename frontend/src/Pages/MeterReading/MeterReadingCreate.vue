<script setup>
import {
  computed,
  onMounted,
  reactive,
  ref,
  watch,
} from 'vue'

import {
  useRouter,
} from 'vue-router'
import api from '@/services/api'
/*
|--------------------------------------------------------------------------
| Router
|--------------------------------------------------------------------------
*/
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
const selectedMeter =
  ref(null)
const latestReading =
  ref(null)
const consumptionPreview =
  ref(0)
const anomalyWarning =
  ref(null)
const form = reactive({
  meter_id: '',
  reading_date:
    new Date()
      .toISOString()
      .split('T')[0],

  current_reading: '',

  reading_type:
    'actual',

  reading_source:
    'manual',

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

const selectedMeterObject =
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

    return Number(

      latestReading.value
        ?.reading
        ?.current_reading

      ??

      selectedMeterObject.value
        ?.current_reading

      ??

      0
    )
  })

const utilityUnit =
  computed(() => {

    return selectedMeterObject.value
      ?.measurement_unit
      ?? ''
  })

const utilityType =
  computed(() => {

    return selectedMeterObject.value
      ?.utility_type
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

      loading.value = true

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
        'Failed to load meters:',
        error
      )
    }
    finally {
      loading.value = false
    }
  }
/*
|--------------------------------------------------------------------------
| Fetch Latest Reading
|--------------------------------------------------------------------------
*/
const fetchLatestReading =
  async () => {
    if (
      !form.meter_id
    ) {
      latestReading.value =
        null
      return
    }

    try {

      const response =
        await api.get(
          '/meter-readings',
          {

            params: {

              meter_id:
                form.meter_id,

              per_page: 1,
            },
          }
        )

      latestReading.value =
        response.data.data[0]
        ?? null
    }

    catch (error) {

      console.error(
        'Failed to fetch latest reading:',
        error
      )
    }
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
      previousReading.value
    if (
      !current
    ) {

      consumptionPreview.value = 0

      anomalyWarning.value = null

      return
    }

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
    | Anomaly Detection
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
| Submit Form
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

        '/meter-readings',

        payload,

        {

          headers: {

            'Content-Type':
              'multipart/form-data',
          },
        }
      )

      router.push(
        '/meter-readings'
      )
    }

    catch (error) {

      console.error(
        'Meter reading submission failed:',
        error
      )

      if (
        error.response
          ?.status === 422
      ) {

        Object.assign(

          errors,

          error.response
            .data
            .errors
        )
      }
    }

    finally {

      submitting.value =
        false
    }
  }

/*
|--------------------------------------------------------------------------
| Watchers
|--------------------------------------------------------------------------
*/

watch(

  () => form.meter_id,

  async () => {

    await fetchLatestReading()

    calculateConsumption()
  }
)

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

onMounted(() => {

  fetchMeters()
})
</script>

<template>
   
  <div
    class="
      min-h-screen
      bg-slate-50
      p-6
    "
  >
    <!-- Header -->

    <div
      class="
        mb-8
        flex
        flex-col
        gap-3
        md:flex-row
        md:items-center
        md:justify-between
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
          Capture Meter Reading
        </h1>

        <p
          class="
            mt-1
            text-slate-500
          "
        >
          Operational utility consumption capture workflow
        </p>
      </div>

      <RouterLink
        to="/meter-readings"
        class="
          inline-flex
          items-center
          justify-center
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
        Back to Readings
      </RouterLink>
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
      <!-- Main Form -->

      <div
        class="
          xl:col-span-2
        "
      >
        <div
          class="
            rounded-2xl
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
            <!-- Meter Selection -->

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
                class="
                  w-full
                  rounded-xl
                  border
                  border-slate-300
                  px-4
                  py-3
                  text-sm
                  focus:border-indigo-500
                  focus:ring-indigo-500
                "
              >
                <option value="">
                  Select meter
                </option>

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
                    meter.utility_type
                  }}
                  •
                  {{
                    meter.building?.name
                    || 'N/A'
                  }}
                  •
                  Unit
                  {{
                    meter.apartment?.unit_number
                    || 'N/A'
                  }}
                </option>
              </select>

              <p
                v-if="errors.meter_id"
                class="
                  mt-2
                  text-sm
                  text-red-600
                "
              >
                {{
                  errors.meter_id[0]
                }}
              </p>
            </div>

            <!-- Reading Date -->

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
                  Reading Date
                </label>

                <input
                  v-model="form.reading_date"
                  type="date"
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

              <!-- Current Reading -->

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
                  min="0"
                  placeholder="Enter current reading"
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

            <!-- Reading Type & Source -->

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
                placeholder="
                Enter operational notes,
                inspection details,
                anomaly explanations,
                or meter observations...
                "
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
                Attachment / Evidence
              </label>

              <input
                type="file"
                @change="handleAttachment"
                class="
                  block
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
                to="/meter-readings"
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
                  transition
                  hover:bg-indigo-700
                  disabled:opacity-50
                "
              >
                {{
                  submitting
                    ? 'Processing...'
                    : 'Capture Reading'
                }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Operational Intelligence Panel -->

      <div
        class="
          space-y-6
        "
      >
        <!-- Meter Summary -->

        <div
          class="
            rounded-2xl
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
            Meter Intelligence
          </h2>

          <div
            class="
              mt-5
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
                  mt-1
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
                  || 'N/A'
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
                  mt-1
                  text-2xl
                  font-bold
                  text-slate-900
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
                  mt-1
                  text-3xl
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
                    utilityUnit
                  }}
                </span>
              </h3>
            </div>

            <!-- Anomaly -->

            <div
              v-if="anomalyWarning"
              class="
                rounded-xl
                border
                border-red-200
                bg-red-50
                p-4
              "
            >
              <div
                class="
                  flex
                  items-start
                  gap-3
                "
              >
                <div
                  class="
                    mt-0.5
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
                      font-semibold
                      text-red-700
                    "
                  >
                    Operational Warning
                  </h4>

                  <p
                    class="
                      mt-1
                      text-sm
                      text-red-600
                    "
                  >
                    {{
                      anomalyWarning
                    }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Latest Reading -->

            <div
              v-if="latestReading"
              class="
                rounded-xl
                border
                bg-slate-50
                p-4
              "
            >
              <p
                class="
                  text-xs
                  font-semibold
                  uppercase
                  tracking-wide
                  text-slate-400
                "
              >
                Latest Approved Reading
              </p>

              <div
                class="
                  mt-3
                  space-y-2
                  text-sm
                "
              >
                <div
                  class="
                    flex
                    justify-between
                  "
                >
                  <span
                    class="
                      text-slate-500
                    "
                  >
                    Date
                  </span>

                  <span
                    class="
                      font-medium
                    "
                  >
                    {{
                      latestReading
                        .reading
                        .reading_date
                    }}
                  </span>
                </div>

                <div
                  class="
                    flex
                    justify-between
                  "
                >
                  <span
                    class="
                      text-slate-500
                    "
                  >
                    Reading
                  </span>

                  <span
                    class="
                      font-medium
                    "
                  >
                    {{
                      latestReading
                        .reading
                        .current_reading
                    }}
                  </span>
                </div>

                <div
                  class="
                    flex
                    justify-between
                  "
                >
                  <span
                    class="
                      text-slate-500
                    "
                  >
                    Status
                  </span>

                  <span
                    class="
                      font-medium
                      text-emerald-600
                    "
                  >
                    {{
                      latestReading
                        .status
                        .label
                    }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Operational Notes -->

            <div
              class="
                rounded-xl
                border
                bg-amber-50
                p-4
              "
            >
              <h4
                class="
                  text-sm
                  font-semibold
                  text-amber-700
                "
              >
                Operational Guidance
              </h4>

              <ul
                class="
                  mt-3
                  space-y-2
                  text-sm
                  text-amber-700
                "
              >
                <li>
                  • Verify physical meter before submission
                </li>

                <li>
                  • Capture evidence for anomalies
                </li>

                <li>
                  • Use estimated only when access unavailable
                </li>

                <li>
                  • Confirm reading unit accuracy
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
 
</template>