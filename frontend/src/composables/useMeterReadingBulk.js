import { ref, reactive, computed } from 'vue'
import api from '@/services/api'

export function useMeterReadingBulk() {
  const rows = ref([])
  const loading = ref(false)
  const saving = ref(false)
  const meta = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
    from: 0,
    to: 0,
    per_page: 50,
  })

  const filters = reactive({
    building_id: '',
    utility_type: 'water',
    reading_date: new Date().toISOString().slice(0, 10),
  })

  const inputs = ref({})

  function syncInputsFromRows(list) {
    const next = { ...inputs.value }
    for (const row of list) {
      if (next[row.meter_id] === undefined) {
        next[row.meter_id] = row.existing_reading?.current_reading ?? ''
      }
    }
    inputs.value = next
  }

  async function fetchGrid(page = 1) {
    loading.value = true
    try {
      const { data } = await api.get('/meter-readings/entry-grid', {
        params: {
          page,
          per_page: meta.value.per_page,
          reading_date: filters.reading_date,
          building_id: filters.building_id || undefined,
          utility_type: filters.utility_type || undefined,
        },
      })
      rows.value = data.data || []
      const m = data.meta || {}
      meta.value = {
        current_page: m.current_page || 1,
        last_page: m.last_page || 1,
        total: m.total ?? rows.value.length,
        from: m.from ?? 1,
        to: m.to ?? rows.value.length,
        per_page: m.per_page || 50,
      }
      syncInputsFromRows(rows.value)
    } finally {
      loading.value = false
    }
  }

  function parseNumber(value) {
    if (value === '' || value == null) return null
    const n = Number(String(value).replace(/,/g, ''))
    return Number.isFinite(n) ? n : null
  }

  function formatReading(val) {
    if (val == null || val === '') return '—'
    return Number(val).toLocaleString(undefined, { maximumFractionDigits: 4 })
  }

  function computeConsumption(previous, current) {
    const prev = parseNumber(previous)
    const curr = parseNumber(current)
    if (prev == null || curr == null) return null
    if (curr < prev) return null
    return curr - prev
  }

  function detectAnomaly(row, consumption) {
    if (consumption == null) return null
    if (consumption < 0) return { type: 'error', label: 'Invalid' }
    if (consumption === 0) return { type: 'warning', label: 'Zero' }

    const avg = parseNumber(row.average_consumption)
    if (avg != null && avg > 0) {
      if (consumption > avg * 3) return { type: 'warning', label: 'Spike' }
      if (consumption < avg * 0.1) return { type: 'warning', label: 'Low' }
    }

    return { type: 'ok', label: 'OK' }
  }

  const pageStats = computed(() => {
    let entered = 0
    let anomalies = 0

    for (const row of rows.value) {
      const value = inputs.value[row.meter_id]
      if (value === '' || value == null) continue
      entered++
      const consumption = computeConsumption(row.previous_reading, value)
      const flag = detectAnomaly(row, consumption)
      if (flag?.type === 'warning' || flag?.type === 'error') anomalies++
    }

    return { entered, anomalies }
  })

  const sessionStats = computed(() => {
    let entered = 0
    for (const value of Object.values(inputs.value)) {
      if (value !== '' && value != null) entered++
    }

    return { entered }
  })

  function rowsToSave() {
    return Object.entries(inputs.value)
      .filter(([, value]) => value !== '' && value != null)
      .map(([meterId, value]) => ({
        meter_id: meterId,
        current_reading: parseNumber(value),
      }))
      .filter((row) => row.current_reading != null)
  }

  async function saveEntered() {
    const readings = rowsToSave()
    if (!readings.length) {
      return { saved: 0, skipped: 0, failed: 0, results: [] }
    }

    saving.value = true
    try {
      const { data } = await api.post('/meter-readings/bulk', {
        reading_date: filters.reading_date,
        readings,
      })
      await fetchGrid(meta.value.current_page)
      return data.data
    } finally {
      saving.value = false
    }
  }

  return {
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
    parseNumber,
    formatReading,
    computeConsumption,
    detectAnomaly,
    rowsToSave,
  }
}
