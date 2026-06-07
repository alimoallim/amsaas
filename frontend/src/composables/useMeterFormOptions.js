import { computed } from 'vue'

export const UTILITY_TYPE_OPTIONS = [
  { value: 'electricity', label: 'Electricity' },
  { value: 'water', label: 'Water' },
  { value: 'gas', label: 'Gas' },
  { value: 'steam', label: 'Steam' },
  { value: 'solar', label: 'Solar' },
  { value: 'chilled_water', label: 'Chilled water' },
  { value: 'internet', label: 'Internet' },
]

export const METER_TYPE_OPTIONS = [
  { value: 'analog', label: 'Analog' },
  { value: 'digital', label: 'Digital' },
  { value: 'smart', label: 'Smart (IoT)' },
]

/** Who is charged for consumption recorded on this meter */
export const OWNERSHIP_TYPE_OPTIONS = [
  {
    value: 'building',
    label: 'Whole building',
    hint: 'Master / common-area meter — not billed to tenants unless assigned to a unit',
  },
  {
    value: 'apartment',
    label: 'Apartment / unit',
    hint: 'Sub-meter for one unit — select building and unit',
  },
  {
    value: 'tenant',
    label: 'Tenant',
    hint: 'Billed to a specific tenant (e.g. sub-lease)',
  },
  {
    value: 'shared',
    label: 'Shared allocation',
    hint: 'Split across units — requires a building',
  },
]

export function ownershipNeedsBuilding(type) {
  return ['building', 'apartment', 'shared'].includes(type)
}

export function ownershipNeedsApartment(type) {
  return type === 'apartment'
}

export function ownershipNeedsTenant(type) {
  return type === 'tenant'
}

export const MEASUREMENT_UNIT_OPTIONS = [
  { value: 'kwh', label: 'kWh — kilowatt-hour', for: ['electricity', 'solar'] },
  { value: 'm3', label: 'm³ — cubic meter', for: ['water', 'gas', 'chilled_water', 'steam'] },
  { value: 'liter', label: 'L — liter', for: ['water', 'chilled_water'] },
  { value: 'gallon', label: 'Gal — gallon', for: ['water'] },
  { value: 'gb', label: 'GB — data volume', for: ['internet'] },
  { value: 'ton', label: 'Ton', for: ['steam'] },
]

export function useMeterMeasurementUnits(utilityTypeRef) {
  const filteredMeasurementUnits = computed(() => {
    const utility = utilityTypeRef.value
    if (!utility) return MEASUREMENT_UNIT_OPTIONS
    return MEASUREMENT_UNIT_OPTIONS.filter((u) => u.for.includes(utility))
  })

  function defaultUnitForUtility(utility) {
    const units = MEASUREMENT_UNIT_OPTIONS.filter((u) => u.for.includes(utility))
    if (units.length === 1) return units[0].value
    if (utility === 'electricity' || utility === 'solar') return 'kwh'
    if (utility === 'water') return 'm3'
    if (utility === 'gas') return 'm3'
    if (utility === 'internet') return 'gb'
    if (utility === 'steam') return 'm3'
    return units[0]?.value ?? ''
  }

  return { filteredMeasurementUnits, defaultUnitForUtility }
}
