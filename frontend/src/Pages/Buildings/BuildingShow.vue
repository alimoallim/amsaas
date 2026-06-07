<template>
  <ObjectPageLayout
    v-if="!loading && building"
    :breadcrumbs="breadcrumbs"
    :title="building.name"
    :status="building.is_active ? 'active' : 'inactive'"
    :attributes="attributes"
    :tabs="tabs"
    initial-tab="overview"
  >
    <template #actions>
      <ErpButton variant="secondary" :to="{ name: 'Buildings' }">Back</ErpButton>
      <ErpButton :to="{ name: 'BuildingEdit', params: { id: building.id } }">Edit</ErpButton>
    </template>

    <template #overview>
      <div class="grid gap-6 md:grid-cols-2">
        <FormSection title="General">
          <dl class="space-y-3 text-sm">
            <div><dt class="text-slate-500">Code</dt><dd class="font-medium">{{ building.code || '—' }}</dd></div>
            <div><dt class="text-slate-500">Address</dt><dd class="font-medium">{{ building.address || '—' }}</dd></div>
            <div><dt class="text-slate-500">City / Country</dt><dd class="font-medium">{{ [building.city, building.country].filter(Boolean).join(', ') || '—' }}</dd></div>
          </dl>
        </FormSection>
        <FormSection title="Operations">
          <dl class="space-y-3 text-sm">
            <div><dt class="text-slate-500">Floors</dt><dd class="font-medium">{{ building.total_floors ?? '—' }}</dd></div>
            <div><dt class="text-slate-500">Units</dt><dd class="font-medium">{{ building.total_units ?? '—' }}</dd></div>
            <div><dt class="text-slate-500">Currency</dt><dd class="font-medium">{{ building.operating_currency || 'USD' }}</dd></div>
            <div><dt class="text-slate-500">Timezone</dt><dd class="font-medium">{{ building.timezone || '—' }}</dd></div>
          </dl>
        </FormSection>
      </div>
      <p v-if="building.description" class="mt-4 text-sm text-slate-600">{{ building.description }}</p>
    </template>

    <template #apartments>
      <p class="text-sm text-slate-600">
        <RouterLink :to="{ name: 'Apartments', query: { building_id: building.id } }" class="text-indigo-600 hover:underline">
          View apartments for this building
        </RouterLink>
      </p>
    </template>
  </ObjectPageLayout>

  <div v-else-if="loading" class="erp-page py-12 text-center text-slate-500">Loading…</div>
  <div v-else class="erp-page py-12 text-center text-slate-500">Building not found.</div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import api from '@/services/api'
import {
  ObjectPageLayout,
  ErpButton,
  FormSection,
} from '@/components/erp'

const props = defineProps({ id: { type: [String, Number], required: true } })
const building = ref(null)
const loading = ref(true)

const breadcrumbs = computed(() => [
  { label: 'Buildings', to: '/buildings' },
  { label: building.value?.name || '…' },
])

const attributes = computed(() => {
  if (!building.value) return []
  return [
    building.value.building_type || 'Property',
    `${building.value.total_floors || 0} floors`,
    building.value.city,
  ].filter(Boolean)
})

const tabs = [
  { id: 'overview', label: 'Overview' },
  { id: 'apartments', label: 'Apartments' },
]

onMounted(async () => {
  try {
    const { data } = await api.get(`/buildings/${props.id}`)
    building.value = data.data ?? data
  } finally {
    loading.value = false
  }
})
</script>
