<template>
  <div v-if="loading" class="py-8 text-center text-sm text-slate-500">Loading…</div>
  <form v-else class="space-y-5" @submit.prevent="submit">
    <AlertBanner v-if="serverError" :message="serverError" variant="error" @dismiss="serverError = ''" />
    <FormSection compact title="Unit">
      <FormGrid>
        <FormField label="Building" required :error="fieldError('building_id')">
          <select v-model="form.building_id" class="erp-select">
            <option value="">Select building</option>
            <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
          </select>
        </FormField>
        <FormField label="Unit number" required :error="fieldError('unit_number')">
          <input v-model="form.unit_number" type="text" class="erp-input" />
        </FormField>
        <FormField label="Floor" :error="fieldError('floor')">
          <input v-model.number="form.floor" type="number" class="erp-input" />
        </FormField>
        <FormField label="Property type" :error="fieldError('property_type')">
          <select v-model="form.property_type" class="erp-select">
            <option value="apartment">Apartment</option>
            <option value="studio">Studio</option>
            <option value="penthouse">Penthouse</option>
          </select>
        </FormField>
        <FormField label="Bedrooms" :error="fieldError('bedrooms')">
          <input v-model.number="form.bedrooms" type="number" min="0" class="erp-input" />
        </FormField>
        <FormField label="Bathrooms" :error="fieldError('bathrooms')">
          <input v-model.number="form.bathrooms" type="number" min="0" class="erp-input" />
        </FormField>
        <FormField label="Area (m²)" :error="fieldError('area_sqm')">
          <input v-model.number="form.area_sqm" type="number" min="0" class="erp-input" />
        </FormField>
      </FormGrid>
    </FormSection>
    <FormSection compact title="Listing">
      <FormGrid>
        <FormField label="Listing type" :error="fieldError('listing_type')">
          <select v-model="form.listing_type" class="erp-select">
            <option value="rental">Rental</option>
            <option value="sale">Sale</option>
            <option value="hybrid">Hybrid</option>
          </select>
        </FormField>
        <FormField label="Inventory status" :error="fieldError('inventory_status')">
          <select v-model="form.inventory_status" class="erp-select">
            <option value="available">Available</option>
            <option value="occupied">Occupied</option>
            <option value="reserved">Reserved</option>
            <option value="maintenance">Maintenance</option>
          </select>
        </FormField>
        <FormField label="Market rent" :error="fieldError('market_rent_price')">
          <input v-model.number="form.market_rent_price" type="number" step="0.01" class="erp-input" />
        </FormField>
        <FormField label="Currency" :error="fieldError('currency')">
          <input v-model="form.currency" type="text" maxlength="3" class="erp-input uppercase" />
        </FormField>
      </FormGrid>
    </FormSection>
  </form>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import api from '@/services/api'
import { FormSection, FormGrid, FormField, AlertBanner } from '@/components/erp'

const props = defineProps({ entityId: { type: [String, Number], default: null } })
const emit = defineEmits(['saved'])

const loading = ref(false)
const buildings = ref([])
const serverError = ref('')
const fieldErrors = ref({})

const defaults = () => ({
  building_id: '',
  unit_number: '',
  floor: null,
  property_type: 'apartment',
  bedrooms: 1,
  bathrooms: 1,
  area_sqm: null,
  listing_type: 'rental',
  inventory_status: 'available',
  market_rent_price: null,
  currency: 'USD',
})

const form = reactive(defaults())

function fieldError(key) {
  const e = fieldErrors.value[key]
  return Array.isArray(e) ? e[0] : e || ''
}

async function loadBuildings() {
  const { data } = await api.get('/buildings')
  buildings.value = data.data || []
}

async function load() {
  if (!props.entityId) {
    Object.assign(form, defaults())
    return
  }
  loading.value = true
  try {
    const { data } = await api.get(`/apartments/${props.entityId}`)
    const a = data.data ?? data
    Object.assign(form, {
      building_id: a.building_id ?? a.building?.id ?? '',
      unit_number: a.unit?.unit_number ?? a.unit_number ?? '',
      floor: a.unit?.floor ?? a.floor ?? null,
      property_type: a.unit?.property_type ?? 'apartment',
      bedrooms: a.layout?.bedrooms ?? 1,
      bathrooms: a.layout?.bathrooms ?? 1,
      area_sqm: a.layout?.area_sqm ?? null,
      listing_type: a.listing?.listing_type ?? 'rental',
      inventory_status: a.listing?.inventory_status ?? 'available',
      market_rent_price: a.pricing?.market_rent_price ?? a.pricing?.effective_price ?? null,
      currency: a.pricing?.currency ?? 'USD',
    })
  } finally {
    loading.value = false
  }
}

async function submit() {
  fieldErrors.value = {}
  serverError.value = ''
  try {
    if (props.entityId) {
      await api.put(`/apartments/${props.entityId}`, { ...form })
    } else {
      await api.post('/apartments', { ...form })
    }
    emit('saved')
  } catch (e) {
    if (e.response?.status === 422) {
      fieldErrors.value = e.response.data.errors || {}
      serverError.value = 'Please fix the highlighted fields.'
    } else {
      serverError.value = e.response?.data?.message || 'Save failed.'
    }
  }
}

onMounted(async () => {
  await loadBuildings()
  await load()
})

defineExpose({ submit })
</script>
