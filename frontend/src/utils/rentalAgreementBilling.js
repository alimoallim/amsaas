import { pricingPolicyLabel } from '@/utils/chargeModelForm'

export function emptyRecurringChargeRow() {
  return {
    id: null,
    charge_model_id: '',
    override_amount: null,
    override_unit_rate: null,
    custom_name: '',
  }
}

export function mapBillingFromApi(billing) {
  if (!billing) {
    return {
      rent_charge_model_id: '',
      recurring_charges: [],
    }
  }

  const rawRecurring = billing.recurring_charges
  const list = Array.isArray(rawRecurring)
    ? rawRecurring
    : rawRecurring?.data ?? []

  const recurring = list.map((row) => {
    const item = row.data ?? row
    const model = item.charge_model
    return {
      id: item.id ?? null,
      charge_model_id: item.charge_model_id ?? '',
      override_amount: item.override_amount != null ? Number(item.override_amount) : null,
      override_unit_rate: item.override_unit_rate != null ? Number(item.override_unit_rate) : null,
      custom_name: item.custom_name ?? '',
      preferMetered: model?.pricing_strategy === 'metered',
    }
  })

  const rentCharge = billing.rent_charge?.data ?? billing.rent_charge

  return {
    rent_charge_model_id:
      billing.rent_charge_model_id ?? rentCharge?.charge_model_id ?? '',
    recurring_charges: recurring,
  }
}

export function chargeModelPolicyLabel(model) {
  return pricingPolicyLabel(model?.pricing_strategy ?? '')
}

export function rowNeedsAmount(model) {
  const strategy = model?.pricing_strategy
  return strategy === 'flat_fee' || strategy === 'fixed'
}

export function rowNeedsUnitRate(model) {
  return model?.pricing_strategy === 'metered'
}

function formatMoney(amount, currency = 'USD') {
  const value = Number(amount)
  if (Number.isNaN(value)) return '—'
  try {
    return new Intl.NumberFormat(undefined, {
      style: 'currency',
      currency: currency || 'USD',
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(value)
  } catch {
    return `${currency} ${value.toFixed(2)}`
  }
}

function normalizeKey(value) {
  return String(value || '').trim().toLowerCase().replace(/\s+/g, '_')
}

/**
 * Match a metered agreement charge to the latest utility reading row.
 */
export function findUsageForMeteredCharge(item, model, utilityByType, utilityItems) {
  const meterType = model?.meter_type
  if (meterType && utilityByType[meterType]) {
    return utilityByType[meterType]
  }

  const labels = [
    item?.custom_name,
    model?.name,
    model?.code,
    meterType,
  ]
    .map(normalizeKey)
    .filter(Boolean)

  return (
    utilityItems.find((usage) => {
      const usageType = normalizeKey(usage.utility_type)
      const usageLabel = normalizeKey(usage.utility_label)
      return labels.some(
        (label) =>
          label === usageType
          || label === usageLabel
          || label.includes(usageType)
          || usageLabel.includes(label),
      )
    }) ?? null
  )
}

/**
 * Resolve billable amount for a metered line (charge total or consumption × rate).
 */
export function resolveMeteredBillableAmount(usage, item, model) {
  if (usage?.amount != null && !Number.isNaN(Number(usage.amount))) {
    return {
      amount: Number(usage.amount),
      estimated: Boolean(usage.amount_is_estimated),
    }
  }

  const consumption =
    usage?.consumption != null && !Number.isNaN(Number(usage.consumption))
      ? Number(usage.consumption)
      : null

  const rateCandidates = [usage?.unit_rate, item?.override_unit_rate, model?.unit_rate]
  const rate = rateCandidates
    .map((v) => (v != null && v !== '' ? Number(v) : null))
    .find((v) => v != null && !Number.isNaN(v) && v > 0) ?? null

  if (consumption != null && rate != null) {
    return { amount: consumption * rate, estimated: true }
  }

  return { amount: null, estimated: false }
}

function isOneTimeCharge(item, model) {
  const category = item?.charge_type?.category ?? model?.charge_type?.category
  if (category === 'deposit') {
    return true
  }
  const label = normalizeKey(item?.custom_name || model?.name || model?.code)
  return label.includes('deposit')
}

function formatMeteredExpectedDisplay(usage, amount, estimated, currency) {
  if (!usage || usage.consumption == null) {
    return '—'
  }

  const unit = usage.measurement_unit ? ` ${usage.measurement_unit}` : ''
  const consumptionPart = `${Number(usage.consumption).toFixed(4)}${unit} consumed`

  if (amount != null) {
    const est = estimated ? ' (est.)' : ''
    return `${consumptionPart} → ${formatMoney(amount, currency)}${est}`
  }

  return consumptionPart
}

/**
 * Read-only billing summary rows for agreement show page.
 * @param {object|null} agreement Rental agreement API payload
 */
export function buildBillingSummary(agreement) {
  if (!agreement) {
    return { lines: [], currency: 'USD', fixedMonthlyTotal: 0, hasVariableCharges: false }
  }

  const currency = agreement.financials?.currency || 'USD'
  const monthlyRent = Number(agreement.financials?.monthly_rent ?? 0)
  const billing = agreement.billing
  const utilityItems = agreement.utility_usage?.items ?? []
  const utilityByType = Object.fromEntries(
    utilityItems.map((row) => [row.utility_type, row])
  )
  const lines = []

  const rentCharge = billing?.rent_charge?.data ?? billing?.rent_charge
  if (monthlyRent > 0 || rentCharge) {
    const model = rentCharge?.charge_model
    lines.push({
      id: rentCharge?.id ?? 'rent-summary',
      name: rentCharge?.custom_name || model?.name || 'Monthly rent',
      code: model?.code ?? null,
      policy: 'agreement_rent',
      policyLabel: chargeModelPolicyLabel({ pricing_strategy: 'agreement_rent' }),
      status: rentCharge?.status ?? null,
      expectedDisplay: `${formatMoney(monthlyRent, currency)} / month`,
      fixedAmount: monthlyRent,
      billableAmount: monthlyRent,
      note: 'Amount comes from monthly rent on this agreement.',
    })
  }

  const rawRecurring = billing?.recurring_charges
  const recurringList = Array.isArray(rawRecurring)
    ? rawRecurring
    : rawRecurring?.data ?? []

  for (const row of recurringList) {
    const item = row.data ?? row
    const model = item.charge_model
    const strategy = model?.pricing_strategy ?? 'unknown'

    if (strategy === 'agreement_rent') {
      continue
    }

    const line = {
      id: item.id,
      name: item.custom_name || model?.name || 'Charge',
      code: model?.code ?? null,
      policy: strategy,
      policyLabel: chargeModelPolicyLabel(model),
      status: item.status ?? null,
      expectedDisplay: '—',
      fixedAmount: null,
      note: '',
    }

    if (strategy === 'flat_fee' || strategy === 'fixed') {
      const amount = Number(item.override_amount ?? 0)
      const oneTime = isOneTimeCharge(item, model)
      line.expectedDisplay = oneTime
        ? `${formatMoney(amount, currency)} (one-time)`
        : `${formatMoney(amount, currency)} / month`
      line.fixedAmount = oneTime ? null : amount
      line.billableAmount = oneTime ? null : amount
      line.isOneTime = oneTime
      line.note = oneTime
        ? 'One-time charge — excluded from monthly billing estimate.'
        : strategy === 'flat_fee'
          ? 'Flat fee set on this agreement line.'
          : 'Fixed amount from agreement override or model default.'
    } else if (strategy === 'metered') {
      const rate = item.override_unit_rate ?? model?.unit_rate
      const usage = findUsageForMeteredCharge(item, model, utilityByType, utilityItems)
      const billable = resolveMeteredBillableAmount(usage, item, model)

      line.billableAmount = billable.amount
      line.fixedAmount = billable.amount
      line.amountIsEstimated = billable.estimated

      if (usage?.consumption != null) {
        line.expectedDisplay = formatMeteredExpectedDisplay(
          usage,
          billable.amount,
          billable.estimated,
          currency,
        )
        line.note = [
          usage.reading_date && `Reading ${usage.reading_date}`,
          usage.reading_status_label && `Status: ${usage.reading_status_label}`,
          usage.charge_number && `Charge ${usage.charge_number}`,
        ]
          .filter(Boolean)
          .join(' · ')
      } else {
        line.expectedDisplay =
          rate != null ? `${formatMoney(rate, currency)} per unit` : 'Per model rate'
        line.note = model?.meter_type
          ? `${model.meter_type} — capture a meter reading to calculate consumption × rate.`
          : 'Capture a meter reading to calculate consumption × rate.'
      }
    } else if (strategy === 'percentage') {
      line.expectedDisplay = model?.percentage_rate != null ? `${model.percentage_rate}% of base` : 'Percentage'
      line.note = 'Calculated from configured percentage rules.'
    } else {
      line.note = 'Legacy or custom pricing strategy.'
    }

    lines.push(line)
  }

  const meteredTypesOnAgreement = new Set()
  for (const row of recurringList) {
    const item = row.data ?? row
    const strategy = item.charge_model?.pricing_strategy
    if (strategy === 'metered' && item.charge_model?.meter_type) {
      meteredTypesOnAgreement.add(item.charge_model.meter_type)
    }
  }

  for (const usage of utilityItems) {
    if (meteredTypesOnAgreement.has(usage.utility_type)) {
      continue
    }
    const billable = resolveMeteredBillableAmount(usage, null, null)
    lines.push({
      id: usage.reading_id ?? `utility-${usage.utility_type}`,
      name: usage.utility_label || usage.utility_type,
      code: usage.meter_number ?? null,
      policy: 'metered',
      policyLabel: 'Metered utility',
      status: usage.reading_status,
      expectedDisplay: formatMeteredExpectedDisplay(
        usage,
        billable.amount,
        billable.estimated,
        currency,
      ),
      billableAmount: billable.amount,
      fixedAmount: billable.amount,
      amountIsEstimated: billable.estimated,
      note: [
        usage.reading_date && `Reading ${usage.reading_date}`,
        usage.reading_status_label,
      ]
        .filter(Boolean)
        .join(' · '),
    })
  }

  const estimatedMonthlyTotal = lines.reduce(
    (sum, line) => sum + (line.billableAmount != null ? line.billableAmount : 0),
    0,
  )

  const hasVariableCharges = lines.some(
    (line) => line.policy === 'metered' && line.billableAmount == null,
  ) || utilityItems.some((u) => {
    const billable = resolveMeteredBillableAmount(u, null, null)
    return u.consumption != null && billable.amount == null
  })

  const hasEstimatedAmounts = lines.some((line) => line.amountIsEstimated)
  const hasOneTimeCharges = lines.some((line) => line.isOneTime)

  return {
    lines,
    currency,
    fixedMonthlyTotal: estimatedMonthlyTotal,
    estimatedMonthlyTotal,
    hasVariableCharges,
    hasEstimatedAmounts,
    hasOneTimeCharges,
    utilityUsage: utilityItems,
    utilityTotals: agreement.utility_usage?.totals ?? {},
    formatMoney: (amount) => formatMoney(amount, currency),
  }
}

export function firstFormError(errors) {
  if (!errors || typeof errors !== 'object') return ''
  for (const messages of Object.values(errors)) {
    const text = Array.isArray(messages) ? messages[0] : messages
    if (text) return text
  }
  return ''
}

/**
 * Keep one row per charge_model_id (prefer rows that already have a persisted id).
 */
export function dedupeRecurringChargesByModel(rows) {
  const byModel = new Map()

  for (const row of rows) {
    const modelId = row?.charge_model_id
    if (!modelId) {
      continue
    }

    const existing = byModel.get(modelId)
    if (!existing || (!existing.id && row.id)) {
      byModel.set(modelId, row)
    }
  }

  return Array.from(byModel.values())
}

export function billingFieldsForApi(form) {
  const rentModelId = form.rent_charge_model_id
  return {
    rent_charge_model_id: rentModelId && String(rentModelId).trim() !== '' ? rentModelId : null,
    recurring_charges: dedupeRecurringChargesByModel(form.recurring_charges ?? [])
      .filter((row) => row.charge_model_id)
      .map((row) => {
        const entry = {
          charge_model_id: row.charge_model_id,
        }
        if (row.id) entry.id = row.id
        if (row.override_amount != null && row.override_amount !== '') {
          entry.override_amount = row.override_amount
        }
        if (row.override_unit_rate != null && row.override_unit_rate !== '') {
          entry.override_unit_rate = row.override_unit_rate
        }
        if (row.custom_name?.trim()) {
          entry.custom_name = row.custom_name.trim()
        }
        return entry
      }),
  }
}

/**
 * JSON body for create / update (non-multipart).
 */
export function buildRentalAgreementPayload(
  form,
  { forUpdate = false, confirmCriticalChanges = false } = {},
) {
  const payload = {
    apartment_id: form.apartment_id,
    tenant_id: form.tenant_id,
    start_date: form.start_date,
    end_date: form.end_date || null,
    monthly_rent: form.monthly_rent,
    security_deposit: form.security_deposit === '' || form.security_deposit == null
      ? null
      : form.security_deposit,
    currency: form.currency,
    payment_due_day: form.payment_due_day,
    auto_renew: form.auto_renew,
    renewal_notice_days: form.renewal_notice_days,
    notes: form.notes || null,
    special_terms: form.special_terms || null,
    ...billingFieldsForApi(form),
  }

  if (!forUpdate) {
    payload.status = form.status || 'draft'
  }

  if (confirmCriticalChanges) {
    payload.confirm_critical_changes = true
  }

  return payload
}
