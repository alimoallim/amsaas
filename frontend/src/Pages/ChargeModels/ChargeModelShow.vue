<template>
  <div v-if="loading" class="erp-page py-12 text-center text-slate-500">Loading charge model…</div>

  <ObjectPageLayout
    v-else-if="model"
    :breadcrumbs="breadcrumbs"
    :title="model.name"
    :subtitle="model.code"
    :status="model.status"
    :status-label="model.status"
    :attributes="attributes"
    :tabs="tabs"
    initial-tab="overview"
  >
    <template #actions>
      <ErpButton variant="ghost" size="sm" :to="{ name: 'ChargeModels' }">Back</ErpButton>
      <ErpButton
        v-if="model.controls?.can_clone"
        variant="secondary"
        size="sm"
        :loading="cloning"
        @click="onClone"
      >
        Clone
      </ErpButton>
      <ErpButton
        v-if="model.controls?.can_edit !== false"
        size="sm"
        :to="{ name: 'ChargeModelEdit', params: { id: model.id } }"
      >
        Edit
      </ErpButton>
    </template>

    <template #overview>
      <KpiStrip class="mb-6 grid-cols-2 lg:grid-cols-4">
        <KpiCard label="Strategy" :value="policyLabel" />
        <KpiCard label="Frequency" :value="model.billing_frequency || '—'" />
        <KpiCard label="Currency" :value="model.currency || 'USD'" mono />
        <KpiCard
          label="Effective"
          :value="model.effective_from || '—'"
          :caption="model.effective_to ? `Until ${model.effective_to}` : 'Open-ended'"
        />
      </KpiStrip>

      <div class="grid gap-6 lg:grid-cols-2">
        <FormSection title="Pricing">
          <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
            <div><dt class="text-slate-500">Base amount</dt><dd class="font-medium tabular-nums">{{ showBaseAmount ? (model.base_amount ?? '—') : '—' }}</dd></div>
            <div><dt class="text-slate-500">Unit rate</dt><dd class="font-medium tabular-nums">{{ model.unit_rate ?? '—' }}</dd></div>
            <div><dt class="text-slate-500">Percentage</dt><dd class="font-medium tabular-nums">{{ model.percentage_rate != null ? `${model.percentage_rate}%` : '—' }}</dd></div>
            <div><dt class="text-slate-500">Min / max</dt><dd class="font-medium tabular-nums">{{ model.minimum_amount ?? '—' }} / {{ model.maximum_amount ?? '—' }}</dd></div>
            <div><dt class="text-slate-500">Utility type</dt><dd class="font-medium capitalize">{{ model.utility_type || model.meter_type || '—' }}</dd></div>
            <div><dt class="text-slate-500">Auto-generate</dt><dd class="font-medium">{{ model.auto_generate ? 'Yes' : 'No' }}</dd></div>
          </dl>
        </FormSection>

        <FormSection title="Charge type & behavior">
          <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
            <div><dt class="text-slate-500">Charge type</dt><dd class="font-medium">{{ model.charge_type?.name || model.charge_type_id || '—' }}</dd></div>
            <div><dt class="text-slate-500">Taxable</dt><dd class="font-medium">{{ model.taxable ? `Yes (${model.tax_rate ?? 0}%)` : 'No' }}</dd></div>
            <div><dt class="text-slate-500">Proration</dt><dd class="font-medium">{{ model.proration_enabled ? 'Enabled' : 'Disabled' }}</dd></div>
            <div><dt class="text-slate-500">Grace period</dt><dd class="font-medium">{{ model.grace_period_days ?? 0 }} days</dd></div>
            <div><dt class="text-slate-500">Late fee</dt><dd class="font-medium">{{ lateFeeLabel }}</dd></div>
            <div><dt class="text-slate-500">Requires approval</dt><dd class="font-medium">{{ model.requires_approval ? 'Yes' : 'No' }}</dd></div>
          </dl>
        </FormSection>
      </div>

      <FormSection v-if="model.description" title="Description" class="mt-6">
        <p class="text-sm text-slate-600">{{ model.description }}</p>
      </FormSection>
    </template>

    <template #rules>
      <FormSection v-if="model.formula_expression" title="Formula">
        <code class="block rounded-lg bg-slate-100 px-3 py-2 text-sm dark:bg-slate-800">{{ model.formula_expression }}</code>
        <p v-if="model.controls?.formula_supported === false" class="mt-2 text-xs text-amber-700">
          Formula strategy is not fully supported in billing yet.
        </p>
      </FormSection>

      <FormSection title="Tier configuration" class="mt-6">
        <pre
          v-if="model.tier_configuration?.length"
          class="overflow-x-auto rounded-lg bg-slate-50 p-3 text-xs dark:bg-slate-800"
        >{{ formattedTiers }}</pre>
        <p v-else class="text-sm text-slate-500">No tiers configured.</p>
      </FormSection>
    </template>
  </ObjectPageLayout>

  <div v-else class="erp-page py-12 text-center text-slate-500">Charge model not found.</div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/services/api'
import { pricingPolicyLabel } from '@/utils/chargeModelForm'
import {
  ObjectPageLayout,
  ErpButton,
  FormSection,
  KpiCard,
  KpiStrip,
} from '@/components/erp'

const route = useRoute()

const loading = ref(true)
const cloning = ref(false)
const model = ref(null)

const policyLabel = computed(() =>
  model.value ? pricingPolicyLabel(model.value.pricing_strategy) : '—',
)

const showBaseAmount = computed(() => {
  const s = model.value?.pricing_strategy
  return s === 'fixed' || (s === 'metered' && model.value?.base_amount != null)
})

const lateFeeLabel = computed(() => {
  if (!model.value?.late_fee_enabled) return 'Disabled'
  return `${model.value.late_fee_type || 'fee'} ${model.value.late_fee_value ?? ''}`.trim()
})

const formattedTiers = computed(() => {
  if (!model.value?.tier_configuration?.length) return ''
  return JSON.stringify(model.value.tier_configuration, null, 2)
})

const breadcrumbs = computed(() => [
  { label: 'Charge models', to: { name: 'ChargeModels' } },
  { label: model.value?.name || '…' },
])

const attributes = computed(() => {
  if (!model.value) return []
  return [
    model.value.charge_type?.name,
    policyLabel.value,
    model.value.billing_frequency,
  ].filter(Boolean)
})

const tabs = [
  { id: 'overview', label: 'Overview' },
  { id: 'rules', label: 'Rules' },
]

async function load() {
  loading.value = true
  try {
    const { data } = await api.get(`/charge-models/${route.params.id}`)
    model.value = data.data ?? data
  } finally {
    loading.value = false
  }
}

async function onClone() {
  cloning.value = true
  try {
    await api.post(`/charge-models/${model.value.id}/clone`)
    await load()
  } finally {
    cloning.value = false
  }
}

onMounted(load)
</script>
