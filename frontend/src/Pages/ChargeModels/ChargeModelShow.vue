<template>
  <div class="space-y-4">
    <div class="top">
      <div>
        <h1>{{ row?.name || 'Charge Model' }}</h1>
        <p>{{ row?.description || 'Charge model details and controls.' }}</p>
      </div>
      <div class="top-actions">
        <router-link :to="{ name: 'ChargeModels' }" class="btn-subtle">Back</router-link>
        <router-link :to="{ name: 'ChargeModelEdit', params: { id } }" class="btn-primary">Edit</router-link>
      </div>
    </div>

    <div v-if="loading" class="card">Loading charge model...</div>
    <div v-else-if="!row" class="card">Charge model not found.</div>
    <div v-else class="card detail-grid">
      <div><strong>Code:</strong> {{ row.code }}</div>
      <div><strong>Status:</strong> {{ row.status }}</div>
      <div><strong>Pricing policy:</strong> {{ policyLabel }}</div>
      <div><strong>Frequency:</strong> {{ row.billing_frequency }}</div>
      <div><strong>Effective:</strong> {{ row.effective_from }} - {{ row.effective_to || 'Open' }}</div>
      <div><strong>Currency:</strong> {{ row.currency }}</div>
      <div v-if="showBaseAmount"><strong>Default amount:</strong> {{ row.base_amount ?? '-' }}</div>
      <div><strong>Unit Rate:</strong> {{ row.unit_rate ?? '-' }}</div>
      <div><strong>Percentage:</strong> {{ row.percentage_rate ?? '-' }}</div>
      <div><strong>Tax Rate:</strong> {{ row.tax_rate ?? '-' }}</div>
      <div><strong>Late Fee:</strong> {{ row.late_fee_enabled ? `${row.late_fee_type} ${row.late_fee_value ?? ''}` : 'Disabled' }}</div>
      <div><strong>Charge Type:</strong> {{ row.charge_type?.name || row.charge_type_id }}</div>
      <div class="full"><strong>Formula:</strong> {{ row.formula_expression || '-' }}</div>
      <div class="full"><strong>Tier Configuration:</strong> <pre>{{ formattedTiers }}</pre></div>
      <div><strong>Controls:</strong> Taxable={{ row.taxable ? 'Yes' : 'No' }}, Proration={{ row.proration_enabled ? 'Yes' : 'No' }}</div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/services/api'
import { pricingPolicyLabel } from '@/utils/chargeModelForm'

const route = useRoute()
const id = computed(() => route.params.id)
const loading = ref(false)
const row = ref(null)

const policyLabel = computed(() =>
  row.value ? pricingPolicyLabel(row.value.pricing_strategy) : '—'
)

const showBaseAmount = computed(() => {
  const s = row.value?.pricing_strategy
  return s === 'fixed' || (s === 'metered' && row.value?.base_amount != null)
})

const formattedTiers = computed(() => {
  if (!row.value?.tier_configuration) return '-'
  return JSON.stringify(row.value.tier_configuration, null, 2)
})

async function load() {
  loading.value = true
  try {
    const response = await api.get(`/charge-models/${id.value}`)
    row.value = response.data.data || response.data
  } finally {
    loading.value = false
  }
}

onMounted(() => load())
</script>

<style scoped>
.top { display: flex; align-items: flex-end; justify-content: space-between; gap: 12px; }
h1 { font-size: 22px; font-weight: 700; }
p { color: #6b7280; font-size: 13px; }
.top-actions { display: flex; gap: 8px; }
.card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 14px; }
.detail-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; font-size: 13px; }
.full { grid-column: 1 / -1; }
pre { white-space: pre-wrap; word-break: break-word; background: #f9fafb; border-radius: 8px; padding: 8px; margin-top: 4px; }
.btn-primary { background: #4f46e5; color: #fff; border-radius: 8px; padding: 8px 12px; text-decoration: none; border: 0; }
.btn-subtle { background: #fff; color: #374151; border: 1px solid #d1d5db; border-radius: 8px; padding: 8px 12px; text-decoration: none; }
@media (max-width: 900px) {
  .detail-grid { grid-template-columns: 1fr; }
}
</style>
