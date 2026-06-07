/** Business pricing policies (charge model defines how to calculate, not always the amount). */
export const PRICING_POLICY_OPTIONS = [
  {
    value: 'agreement_rent',
    label: 'Rent from rental agreement',
    hint: 'Uses the monthly rent on each active rental agreement. Do not enter an amount here.',
  },
  {
    value: 'metered',
    label: 'Metered utility',
    hint: 'Water and electricity: consumption × unit rate, billed at month end from approved meter readings.',
  },
  {
    value: 'flat_fee',
    label: 'Flat monthly service fee',
    hint: 'Security, cleaning, maintenance, etc. Set the amount on each agreement charge line when linking to a lease.',
  },
  {
    value: 'tiered',
    label: 'Tiered consumption',
    hint: 'Progressive rates by consumption band. Last tier can be open-ended (leave "to" empty).',
  },
  {
    value: 'percentage',
    label: 'Percentage of base',
    hint: 'Percentage of a base amount supplied at billing time (e.g. rent or service subtotal).',
  },
  {
    value: 'fixed',
    label: 'Fixed amount (legacy)',
    hint: 'Default amount on the model, or override on each agreement charge line.',
  },
]

export function strategyForChargeTypeCategory(category) {
  switch (category) {
    case 'rent':
      return 'agreement_rent'
    case 'utility':
      return 'metered'
    case 'service':
    case 'miscellaneous':
      return 'flat_fee'
    default:
      return 'flat_fee'
  }
}

export function pricingPolicyLabel(strategy) {
  const match = PRICING_POLICY_OPTIONS.find((o) => o.value === strategy)
  if (match) return match.label
  const legacy = {
    fixed: 'Fixed (legacy)',
    tiered: 'Tiered',
    percentage: 'Percentage',
    formula: 'Formula',
  }
  return legacy[strategy] || strategy
}

/** Writable API fields for charge model create/update. */
export const CHARGE_MODEL_PAYLOAD_KEYS = [
  'charge_type_id',
  'code',
  'name',
  'description',
  'currency',
  'pricing_strategy',
  'billing_frequency',
  'meter_type',
  'base_amount',
  'minimum_amount',
  'maximum_amount',
  'unit_rate',
  'percentage_rate',
  'tier_configuration',
  'formula_expression',
  'proration_enabled',
  'grace_period_days',
  'late_fee_enabled',
  'late_fee_type',
  'late_fee_value',
  'taxable',
  'tax_rate',
  'effective_from',
  'effective_to',
  'auto_generate',
  'requires_approval',
  'status',
  'sort_order',
  'metadata',
]

export function defaultChargeModelForm() {
  return {
    charge_type_id: '',
    code: '',
    name: '',
    description: '',
    currency: 'USD',
    pricing_strategy: 'agreement_rent',
    billing_frequency: 'monthly',
    meter_type: '',
    base_amount: null,
    minimum_amount: null,
    maximum_amount: null,
    unit_rate: null,
    percentage_rate: null,
    tier_configuration: [],
    formula_expression: '',
    proration_enabled: false,
    grace_period_days: 0,
    late_fee_enabled: false,
    late_fee_type: 'fixed',
    late_fee_value: null,
    taxable: false,
    tax_rate: null,
    effective_from: new Date().toISOString().slice(0, 10),
    effective_to: null,
    auto_generate: true,
    requires_approval: false,
    status: 'draft',
    sort_order: 0,
    metadata: {},
  }
}

export function buildChargeModelPayload(form) {
  const payload = {}
  for (const key of CHARGE_MODEL_PAYLOAD_KEYS) {
    if (Object.prototype.hasOwnProperty.call(form, key)) {
      payload[key] = form[key]
    }
  }
  if (payload.meter_type === '') payload.meter_type = null
  if (payload.effective_to === '') payload.effective_to = null
  if (payload.description === '') payload.description = null
  if (payload.formula_expression === '') payload.formula_expression = null
  if (payload.pricing_strategy === 'agreement_rent' || payload.pricing_strategy === 'flat_fee') {
    payload.base_amount = null
  }
  return payload
}

export function firstValidationMessage(errors) {
  if (!errors || typeof errors !== 'object') return ''
  for (const messages of Object.values(errors)) {
    const text = Array.isArray(messages) ? messages[0] : messages
    if (text) return text
  }
  return ''
}
