<template>
  <form class="space-y-5" @submit.prevent="submit">
    <FormSection compact title="Core details">
      <FormGrid>
        <FormField label="Name" required :error="firstError(errors.name)">
          <input v-model="localModel.name" type="text" class="erp-input" />
        </FormField>
        <FormField label="Code" required :error="firstError(errors.code)">
          <input v-model="localModel.code" type="text" class="erp-input font-mono" />
        </FormField>
        <FormField label="Currency" required :error="firstError(errors.currency)">
          <input v-model="localModel.currency" type="text" maxlength="3" class="erp-input uppercase" />
        </FormField>
        <FormField label="Status" required>
          <select v-model="localModel.status" class="erp-select">
            <option value="draft">Draft</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="archived">Archived</option>
          </select>
        </FormField>
        <FormField label="Description" span="2">
          <textarea v-model="localModel.description" rows="3" class="erp-input" />
        </FormField>
      </FormGrid>
    </FormSection>

    <FormSection compact title="Pricing policy">
      <FormGrid>
        <FormField
          label="Charge type"
          required
          span="2"
          :error="firstError(errors.charge_type_id)"
          hint="Billing category from your charge type catalog"
        >
          <ErpSearchSelect
            v-model="localModel.charge_type_id"
            :options="chargeTypeOptions"
            :loading="chargeTypesLoading"
            placeholder="Select charge type…"
            search-placeholder="Search name or code…"
            empty-text="No charge types found — create one under Finance → Charge Types"
            :has-error="!!firstError(errors.charge_type_id)"
          />
        </FormField>
        <FormField label="Pricing policy" required span="2" :error="firstError(errors.pricing_strategy)">
          <select v-model="localModel.pricing_strategy" class="erp-select">
            <option
              v-for="policy in PRICING_POLICY_OPTIONS"
              :key="policy.value"
              :value="policy.value"
            >
              {{ policy.label }}
            </option>
            <option v-if="isLegacyStrategy" :value="localModel.pricing_strategy">
              {{ pricingPolicyLabel(localModel.pricing_strategy) }} (current)
            </option>
          </select>
        </FormField>
        <div v-if="activePolicyHint" class="sm:col-span-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600">
          {{ activePolicyHint }}
        </div>
        <FormField label="Billing frequency" required>
          <select v-model="localModel.billing_frequency" class="erp-select">
            <option value="one_time">One-time</option>
            <option value="daily">Daily</option>
            <option value="weekly">Weekly</option>
            <option value="monthly">Monthly</option>
            <option value="quarterly">Quarterly</option>
            <option value="yearly">Yearly</option>
          </select>
        </FormField>
        <template v-if="isMetered">
          <FormField label="Meter type" required :error="firstError(errors.meter_type)">
            <select v-model="localModel.meter_type" class="erp-select">
              <option value="">Select…</option>
              <option value="electricity">Electricity</option>
              <option value="water">Water</option>
              <option value="gas">Gas</option>
            </select>
          </FormField>
          <FormField label="Unit rate (per unit)" required :error="firstError(errors.unit_rate)">
            <input v-model.number="localModel.unit_rate" type="number" step="0.0001" min="0" class="erp-input" />
          </FormField>
          <FormField label="Minimum amount">
            <input v-model.number="localModel.minimum_amount" type="number" step="0.0001" class="erp-input" />
          </FormField>
          <FormField label="Maximum amount">
            <input v-model.number="localModel.maximum_amount" type="number" step="0.0001" class="erp-input" />
          </FormField>
        </template>
        <template v-if="isFixed">
          <FormField label="Default amount (optional)" :error="firstError(errors.base_amount)">
            <input v-model.number="localModel.base_amount" type="number" step="0.0001" min="0" class="erp-input" />
          </FormField>
        </template>
        <template v-if="isPercentage">
          <FormField label="Percentage rate (%)" required :error="firstError(errors.percentage_rate)">
            <input v-model.number="localModel.percentage_rate" type="number" step="0.0001" min="0" max="100" class="erp-input" />
          </FormField>
        </template>
        <p v-if="isLegacyStrategy" class="sm:col-span-2 text-xs text-amber-700">
          Formula pricing is not available for auto-billing yet. Choose another strategy.
        </p>
      </FormGrid>

      <div v-if="isTiered" class="mt-4 space-y-3">
        <div class="flex items-center justify-between gap-2">
          <p class="text-sm font-medium text-slate-700">Tier configuration</p>
          <ErpButton type="button" variant="ghost" size="sm" @click="addTier">Add tier</ErpButton>
        </div>
        <div class="space-y-2">
          <div
            v-for="(tier, index) in localModel.tier_configuration"
            :key="index"
            class="erp-form-grid erp-tier-row"
          >
            <input v-model.number="tier.from" type="number" class="erp-input" placeholder="From" />
            <input v-model.number="tier.to" type="number" class="erp-input" placeholder="To (∞ open)" />
            <input v-model.number="tier.rate" type="number" step="0.0001" class="erp-input" placeholder="Rate" />
            <ErpButton type="button" variant="danger" size="sm" @click="removeTier(index)">Remove</ErpButton>
          </div>
        </div>
        <p v-if="tierError || firstError(errors.tier_configuration)" class="text-xs text-red-600">
          {{ tierError || firstError(errors.tier_configuration) }}
        </p>
      </div>
    </FormSection>

    <FormSection compact title="Tax, dates, and controls">
      <FormGrid>
        <FormField label="Effective from" required :error="firstError(errors.effective_from)">
          <ErpDateInput v-model="localModel.effective_from" placeholder="Start date" />
        </FormField>
        <FormField label="Effective to" :error="firstError(errors.effective_to)">
          <ErpDateInput
            v-model="localModel.effective_to"
            placeholder="End date (optional)"
            :min="localModel.effective_from || ''"
          />
        </FormField>
        <FormField label="Tax rate (%)">
          <input v-model.number="localModel.tax_rate" type="number" step="0.0001" class="erp-input" />
        </FormField>
        <FormField label="Late fee value">
          <input v-model.number="localModel.late_fee_value" type="number" step="0.0001" class="erp-input" />
        </FormField>
      </FormGrid>
      <div class="erp-form-grid mt-4">
        <label class="flex items-center gap-2 text-sm text-slate-700">
          <input v-model="localModel.taxable" type="checkbox" class="rounded border-slate-300" />
          Taxable
        </label>
        <label class="flex items-center gap-2 text-sm text-slate-700">
          <input v-model="localModel.proration_enabled" type="checkbox" class="rounded border-slate-300" />
          Proration enabled
        </label>
        <label class="flex items-center gap-2 text-sm text-slate-700">
          <input v-model="localModel.late_fee_enabled" type="checkbox" class="rounded border-slate-300" />
          Late fee enabled
        </label>
        <label class="flex items-center gap-2 text-sm text-slate-700">
          <input v-model="localModel.auto_generate" type="checkbox" class="rounded border-slate-300" />
          Auto generate
        </label>
        <label class="flex items-center gap-2 text-sm text-slate-700 sm:col-span-2">
          <input v-model="localModel.requires_approval" type="checkbox" class="rounded border-slate-300" />
          Requires approval
        </label>
      </div>
    </FormSection>

    <footer class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end">
      <ErpButton type="button" variant="secondary" @click="$emit('cancel')">Cancel</ErpButton>
      <ErpButton type="submit" variant="primary" :loading="submitting" :disabled="submitting">
        {{ submitting ? 'Saving…' : submitLabel }}
      </ErpButton>
    </footer>
  </form>
</template>

<script setup>
import { computed, reactive, watch, onMounted } from 'vue'
import { FormSection, FormGrid, FormField, ErpDateInput, ErpButton, ErpSearchSelect } from '@/components/erp'
import { useChargeTypes } from '@/composables/useChargeTypes'
import {
  PRICING_POLICY_OPTIONS,
  strategyForChargeTypeCategory,
  pricingPolicyLabel,
} from '@/utils/chargeModelForm'

const LEGACY_STRATEGIES = ['formula']

const {
  items: chargeTypes,
  loading: chargeTypesLoading,
  fetchForPicker,
  chargeTypeToOption,
} = useChargeTypes()

const chargeTypeOptions = computed(() => {
  const selectedId = localModel.charge_type_id
  return chargeTypes.value
    .filter((t) => t.status === 'active' || t.id === selectedId)
    .map((t) => chargeTypeToOption(t))
})

const props = defineProps({
  modelValue: { type: Object, required: true },
  errors: { type: Object, default: () => ({}) },
  submitting: { type: Boolean, default: false },
  submitLabel: { type: String, default: 'Save Charge Model' },
})

const emit = defineEmits(['update:modelValue', 'submit', 'cancel'])

const localModel = reactive({ ...props.modelValue })

const activePolicyHint = computed(() => {
  const policy = PRICING_POLICY_OPTIONS.find((p) => p.value === localModel.pricing_strategy)
  return policy?.hint ?? ''
})

const isMetered = computed(() => localModel.pricing_strategy === 'metered')
const isTiered = computed(() => localModel.pricing_strategy === 'tiered')
const isFixed = computed(() => localModel.pricing_strategy === 'fixed')
const isPercentage = computed(() => localModel.pricing_strategy === 'percentage')
const isLegacyStrategy = computed(() => LEGACY_STRATEGIES.includes(localModel.pricing_strategy))

const tierError = computed(() => {
  if (!isTiered.value) return ''
  const tiers = localModel.tier_configuration || []
  if (!tiers.length) return 'At least one tier is required.'
  for (let i = 0; i < tiers.length; i += 1) {
    const t = tiers[i]
    if (t.from == null || t.from === '' || t.rate == null || t.rate === '') {
      return 'Each tier requires from and rate.'
    }
    if (t.to != null && t.to !== '' && Number(t.from) > Number(t.to)) {
      return 'Tier "from" must be less than or equal to "to".'
    }
    if (i > 0) {
      const prevTo = tiers[i - 1].to
      if (prevTo == null || prevTo === '') return 'Close the previous open-ended tier before adding another.'
      if (Number(prevTo) + 1 !== Number(t.from)) return 'Tiers must be contiguous and non-overlapping.'
    }
  }
  return ''
})

watch(
  () => localModel,
  (value) => emit('update:modelValue', { ...value }),
  { deep: true }
)

watch(
  () => props.modelValue,
  (value) => Object.assign(localModel, value),
  { deep: true }
)

watch(
  () => localModel.charge_type_id,
  (id) => {
    if (!id) return
    fetchForPicker({ ensureId: id })
    const type = chargeTypes.value.find((t) => t.id === id)
    if (!type?.category) return
    const suggested = strategyForChargeTypeCategory(type.category)
    const current = localModel.pricing_strategy
    if (!current || current === 'agreement_rent' || current === 'fixed') {
      localModel.pricing_strategy = suggested
    }
    if (suggested === 'metered' && !localModel.meter_type && type.category === 'utility') {
      const label = `${type.name ?? ''} ${type.code ?? ''}`.toLowerCase()
      if (label.includes('water')) {
        localModel.meter_type = 'water'
      } else if (label.includes('gas')) {
        localModel.meter_type = 'gas'
      } else {
        localModel.meter_type = 'electricity'
      }
    }
  }
)

watch(
  () => localModel.pricing_strategy,
  (strategy) => {
    if (strategy === 'agreement_rent' || strategy === 'flat_fee') {
      localModel.base_amount = null
      localModel.meter_type = strategy === 'flat_fee' ? null : localModel.meter_type
    }
    if (strategy === 'agreement_rent') {
      localModel.meter_type = null
      localModel.unit_rate = null
    }
    if (strategy === 'flat_fee') {
      localModel.meter_type = null
      localModel.unit_rate = null
    }
  }
)

onMounted(() => {
  fetchForPicker({ ensureId: localModel.charge_type_id || undefined })
})

function addTier() {
  if (!Array.isArray(localModel.tier_configuration)) {
    localModel.tier_configuration = []
  }
  const last = localModel.tier_configuration[localModel.tier_configuration.length - 1]
  const nextFrom = last ? Number(last.to) + 1 : 0
  localModel.tier_configuration.push({ from: nextFrom, to: nextFrom, rate: 0 })
}

function removeTier(index) {
  localModel.tier_configuration.splice(index, 1)
}

function submit() {
  if (tierError.value) return
  emit('submit')
}

function firstError(value) {
  return Array.isArray(value) ? value[0] : value
}
</script>

<style scoped>
.erp-tier-row {
  grid-template-columns: 1fr 1fr 1fr auto;
  align-items: end;
}

@media (max-width: 640px) {
  .erp-tier-row {
    grid-template-columns: 1fr;
  }
}
</style>
