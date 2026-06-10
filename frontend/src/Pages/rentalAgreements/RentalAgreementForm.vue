<template>
  <form class="agreement-form" :class="{ 'agreement-form--embedded': embedded }" @submit.prevent="handleSubmit">

    <!-- ══════════════════════════════════════════════════════════
         FORM HEADER
    ══════════════════════════════════════════════════════════ -->
    <div v-if="!embedded" class="form-header">
      <div class="form-header__left">
        <div class="form-header__icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
            <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
            <polyline points="10 9 9 9 8 9"/>
          </svg>
        </div>
        <div>
          <h2 class="form-header__title">Rental Agreement</h2>
          <p class="form-header__sub">Configure assignment, lifecycle, and financial terms</p>
        </div>
      </div>
      <span :class="['mode-badge', isReadonly ? 'mode-badge--readonly' : isEdit ? 'mode-badge--edit' : 'mode-badge--create']">
        <span class="mode-badge__dot"></span>
        {{ isReadonly ? 'Read-only' : isEdit ? 'Editing' : 'New Agreement' }}
      </span>
    </div>

    <!-- Compact scroll area + horizontal section tabs -->
    <p
      v-if="isEdit && initialStatus === 'active' && !isReadonly"
      class="form-active-hint"
      role="status"
    >
      Active agreement — unit, tenant, and dates can be corrected. Changes to draft invoices are applied automatically; issued invoices are not modified.
    </p>

    <nav class="section-tabs" aria-label="Form sections">
      <button
        v-for="sec in sections"
        :key="sec.id"
        type="button"
        class="section-tab"
        :class="{ 'section-tab--active': activeSection === sec.id }"
        @click="scrollToSection(sec.id)"
      >
        {{ sec.label }}
      </button>
    </nav>

    <div class="form-shell" ref="sectionsEl">

        <!-- ─── Agreement Information ─────────────────────────── -->
        <section class="form-block" id="sec-agreement" data-section="agreement">
          <header class="form-block__head">
            <h3 class="form-block__title">Agreement Information</h3>
            <p class="form-block__sub">Lifecycle status for this contract</p>
          </header>
          <div class="grid-3">
            <div class="field-group col-span-3">
              <label class="field-label">Status</label>
              <div class="status-select-grid status-select-grid--compact">
                <button
                  v-for="s in statusOptionsForMode"
                  :key="s.value"
                  type="button"
                  :disabled="isReadonly"
                  :class="['status-option', `status-option--${s.value}`, { 'status-option--selected': form.status === s.value }]"
                  @click="!isReadonly && (form.status = s.value)"
                >
                  <span class="status-option__dot"></span>
                  {{ s.label }}
                </button>
              </div>
              <p v-if="errors.status" class="field-error">{{ errors.status[0] }}</p>
            </div>
          </div>
        </section>

        <!-- ─── Property, Unit & Tenant ───────────────────────── -->
        <section class="form-block" id="sec-property" data-section="property">
          <header class="form-block__head">
            <h3 class="form-block__title">Property & Tenant</h3>
            <p class="form-block__sub">Building, unit, and lessee assignment</p>
          </header>
          <div class="grid-3">
            <!-- Building (filters units) -->
            <div class="field-group">
              <label class="field-label">
                Building
                <span class="field-required">*</span>
              </label>
              <div class="select-wrap">
                <svg class="select-wrap__icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
                <select
                  v-model="selectedBuildingId"
                  :disabled="isReadonly"
                  class="field-select field-select--icon"
                >
                  <option value="">Select building first…</option>
                  <option v-for="building in buildings" :key="building.id" :value="building.id">
                    {{ buildingLabel(building) }}
                  </option>
                </select>
                <svg class="select-wrap__chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="6 9 12 15 18 9"/>
                </svg>
              </div>
              <p v-if="!buildings.length" class="field-hint field-hint--warn">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                No buildings available
              </p>
            </div>

            <!-- Apartment (searchable, scoped to building) -->
            <div class="field-group">
              <label class="field-label">
                Apartment / unit
                <span class="field-required">*</span>
              </label>
              <ErpSearchSelect
                v-model="form.apartment_id"
                :options="apartmentOptions"
                :disabled="isReadonly || !selectedBuildingId"
                :loading="apartmentsLoading"
                remote
                placeholder="Select unit…"
                search-placeholder="Search unit number, floor…"
                :empty-text="apartmentEmptyText"
                input-class="field-select field-select--icon"
                :has-error="!!errors.apartment_id"
                @search="onApartmentSearch"
              />
              <p v-if="selectedBuildingId && !apartmentsLoading && apartmentOptions.length === 0" class="field-hint field-hint--warn">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                {{ apartmentEmptyText }}
              </p>
              <p v-else-if="!selectedBuildingId" class="field-hint">
                Choose a building to list rental units
              </p>
              <p v-if="errors.apartment_id" class="field-error">{{ errors.apartment_id[0] }}</p>
            </div>

            <div class="field-group">
              <label class="field-label">
                Tenant
                <span class="field-required">*</span>
              </label>
              <div class="select-wrap">
                <svg class="select-wrap__icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                </svg>
                <select
                  v-model="form.tenant_id"
                  :disabled="isReadonly"
                  class="field-select field-select--icon"
                >
                  <option value="">Select a tenant…</option>
                  <option v-for="tenant in tenants" :key="tenant.id" :value="tenant.id">
                    {{ tenantLabel(tenant) }}
                  </option>
                </select>
                <svg class="select-wrap__chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="6 9 12 15 18 9"/>
                </svg>
              </div>
              <p v-if="tenants.length === 0" class="field-hint field-hint--warn">No tenants available</p>
              <p v-if="errors.tenant_id" class="field-error">{{ errors.tenant_id[0] }}</p>
            </div>
          </div>
        </section>

        <!-- ─── Dates & Terms ─────────────────────────────────── -->
        <section class="form-block" id="sec-terms" data-section="terms">
          <header class="form-block__head">
            <h3 class="form-block__title">Dates & Terms</h3>
            <p class="form-block__sub">Lease period, payment schedule, and renewal policy</p>
          </header>
          <div class="grid-3">
            <div class="field-group">
              <label class="field-label">Start date <span class="field-required">*</span></label>
              <ErpDateInput
                v-model="form.start_date"
                :disabled="isReadonly"
                input-class="field-date field-date--compact"
                placeholder="Start"
              />
              <p v-if="errors.start_date" class="field-error">{{ errors.start_date[0] }}</p>
            </div>
            <div class="field-group">
              <label class="field-label">End date</label>
              <ErpDateInput
                v-model="form.end_date"
                :disabled="isReadonly"
                input-class="field-date field-date--compact"
                placeholder="End"
                :min="form.start_date || ''"
              />
              <p v-if="errors.end_date" class="field-error">{{ errors.end_date[0] }}</p>
            </div>
            <div class="field-group">
              <label class="field-label">Payment due day</label>
              <div class="stepper-wrap stepper-wrap--compact">
                <button
                  type="button"
                  class="stepper-btn"
                  :disabled="isReadonly || form.payment_due_day <= 1"
                  @click="form.payment_due_day = Math.max(1, (form.payment_due_day || 1) - 1)"
                >−</button>
                <input
                  v-model.number="form.payment_due_day"
                  type="number"
                  min="1"
                  max="28"
                  :disabled="isReadonly"
                  placeholder="1"
                  class="field-input field-input--stepper"
                />
                <button
                  type="button"
                  class="stepper-btn"
                  :disabled="isReadonly || form.payment_due_day >= 28"
                  @click="form.payment_due_day = Math.min(28, (form.payment_due_day || 1) + 1)"
                >+</button>
              </div>
              <p v-if="errors.payment_due_day" class="field-error">{{ errors.payment_due_day[0] }}</p>
            </div>
            <div class="field-group">
              <label class="field-label">Renewal notice (days)</label>
              <div class="stepper-wrap stepper-wrap--compact">
                <button type="button" class="stepper-btn" :disabled="isReadonly || form.renewal_notice_days <= 1"
                  @click="form.renewal_notice_days = Math.max(1, (form.renewal_notice_days || 30) - 1)">−</button>
                <input
                  v-model.number="form.renewal_notice_days"
                  type="number"
                  min="1"
                  max="365"
                  :disabled="isReadonly"
                  placeholder="30"
                  class="field-input field-input--stepper"
                />
                <button type="button" class="stepper-btn" :disabled="isReadonly || form.renewal_notice_days >= 365"
                  @click="form.renewal_notice_days = Math.min(365, (form.renewal_notice_days || 30) + 1)">+</button>
              </div>
            </div>
            <div class="field-group">
              <label class="field-label">Auto renewal</label>
              <label class="toggle-chip" :class="{ 'toggle-chip--on': form.auto_renew, 'toggle-chip--disabled': isReadonly }">
                <input v-model="form.auto_renew" type="checkbox" :disabled="isReadonly" class="sr-only" />
                <span>{{ form.auto_renew ? 'Enabled' : 'Disabled' }}</span>
              </label>
            </div>
          </div>
        </section>

        <!-- ─── Charges & Billing ─────────────────────────────── -->
        <section class="form-block" id="sec-billing" data-section="billing">
          <header class="form-block__head">
            <h3 class="form-block__title">Charges & Billing</h3>
            <p class="form-block__sub">Rent, deposits, and charge model lines</p>
          </header>

          <div class="grid-3">
            <div class="field-group">
              <label class="field-label">Monthly Rent <span class="field-required">*</span></label>
              <div class="input-affix-wrap">
                <span class="input-prefix">{{ form.currency || 'USD' }}</span>
                <input
                  v-model="form.monthly_rent"
                  type="number"
                  step="0.01"
                  min="0"
                  :disabled="isReadonly"
                  placeholder="0.00"
                  class="field-input field-input--prefix"
                />
              </div>
              <p v-if="errors.monthly_rent" class="field-error">{{ errors.monthly_rent[0] }}</p>
            </div>

            <!-- Deposit -->
            <div class="field-group">
              <label class="field-label">Security Deposit</label>
              <div class="input-affix-wrap">
                <span class="input-prefix">{{ form.currency || 'USD' }}</span>
                <input
                  v-model="form.security_deposit"
                  type="number"
                  step="0.01"
                  min="0"
                  :disabled="isReadonly"
                  placeholder="0.00"
                  class="field-input field-input--prefix"
                />
              </div>
              <p v-if="errors.security_deposit" class="field-error">{{ errors.security_deposit[0] }}</p>
            </div>

            <!-- Currency -->
            <div class="field-group">
              <label class="field-label">Currency</label>
              <div class="select-wrap">
                <svg class="select-wrap__icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                </svg>
                <select
                  v-model="form.currency"
                  :disabled="isReadonly"
                  class="field-select field-select--icon"
                >
                  <option v-for="c in currencyOptions" :key="c" :value="c">{{ c }}</option>
                </select>
                <svg class="select-wrap__chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="6 9 12 15 18 9"/>
                </svg>
              </div>
            </div>

            <div class="field-group col-span-2">
              <label class="field-label">Rent charge model</label>
              <select
                v-model="form.rent_charge_model_id"
                :disabled="!canEditBilling || !rentChargeModels.length"
                class="field-select"
              >
                <option value="">Default active rent model</option>
                <option v-for="m in rentChargeModels" :key="m.id" :value="m.id">
                  {{ m.name }} ({{ m.code }})
                </option>
              </select>
              <p v-if="!rentChargeModels.length" class="field-hint field-hint--warn">
                Create an active rent charge model under Finance → Charge Models.
              </p>
            </div>
            <div class="field-group">
              <label class="field-label">Rent billed</label>
              <p class="billing-inline-amount">{{ form.currency || 'USD' }} {{ form.monthly_rent || '0' }} / mo</p>
            </div>
          </div>

          <div class="billing-services billing-services--compact">
              <div class="billing-services__head">
                <span class="billing-services__title">Additional charge models</span>
                <div v-if="canEditBilling" class="billing-services__actions">
                  <button type="button" class="btn-add-line" @click="addChargeRow">
                    + Add charge model
                  </button>
                </div>
              </div>
              <p class="field-hint billing-services__intro">
                Select any active charge model (flat fees, fixed amounts, or metered utilities). Metered models bill from approved meter readings at month end.
              </p>

              <div v-if="!form.recurring_charges?.length" class="billing-empty">
                No additional charge models yet. Add fees or metered utilities configured under Finance → Charge Models.
              </div>

              <div
                v-for="(row, index) in form.recurring_charges"
                :key="row.id || `new-${index}`"
                class="billing-line billing-line--compact"
              >
                <div class="grid-3 billing-line__grid">
                  <div class="field-group col-span-2">
                    <label class="field-label">Charge model</label>
                    <select
                      v-model="row.charge_model_id"
                      :disabled="!canEditBilling"
                      class="field-select"
                      @change="onRecurringModelChange(row)"
                    >
                      <option value="">Select…</option>
                      <option
                        v-for="m in modelsForRow(row, index)"
                        :key="m.id"
                        :value="m.id"
                      >
                        {{ m.name }} — {{ chargeModelPolicyLabel(m) }}
                      </option>
                    </select>
                    <p v-if="recurringRowError(index, 'charge_model_id')" class="field-error">
                      {{ recurringRowError(index, 'charge_model_id') }}
                    </p>
                  </div>
                  <div v-if="rowNeedsAmount(resolveModel(row))" class="field-group">
                    <label class="field-label">Monthly amount <span class="field-required">*</span></label>
                    <div class="input-affix-wrap">
                      <span class="input-prefix">{{ form.currency || 'USD' }}</span>
                      <input
                        v-model.number="row.override_amount"
                        type="number"
                        step="0.01"
                        min="0"
                        :disabled="!canEditBilling"
                        class="field-input field-input--prefix"
                      />
                    </div>
                    <p v-if="recurringRowError(index, 'override_amount')" class="field-error">
                      {{ recurringRowError(index, 'override_amount') }}
                    </p>
                  </div>
                  <div v-if="rowNeedsUnitRate(resolveModel(row))" class="field-group">
                    <label class="field-label">Unit rate override</label>
                    <input
                      v-model.number="row.override_unit_rate"
                      type="number"
                      step="0.0001"
                      min="0"
                      :disabled="!canEditBilling"
                      :placeholder="resolveModel(row)?.unit_rate != null ? String(resolveModel(row).unit_rate) : 'Model default'"
                      class="field-input"
                    />
                    <p class="field-hint">Leave blank to use the charge model default rate.</p>
                  </div>
                  <div class="field-group">
                    <label class="field-label">Label (optional)</label>
                    <input
                      v-model="row.custom_name"
                      type="text"
                      :disabled="!canEditBilling"
                      class="field-input"
                      :placeholder="row.preferMetered ? 'e.g. Unit electricity' : 'e.g. Security service'"
                    />
                  </div>
                </div>
                <div v-if="canEditBilling" class="field-group billing-line__actions">
                  <label class="field-label">&nbsp;</label>
                  <button
                    type="button"
                    class="billing-line__remove"
                    aria-label="Remove line"
                    @click="removeRecurringRow(index)"
                  >
                    Remove
                  </button>
                </div>
              </div>
          </div>
        </section>

        <!-- ─── Notes ───────────────────────────────────────────── -->
        <section class="form-block" id="sec-notes" data-section="notes">
          <header class="form-block__head">
            <h3 class="form-block__title">Notes</h3>
            <p class="form-block__sub">Internal remarks and special contract terms</p>
          </header>
          <div class="notes-grid">
            <div class="field-group">
              <label class="field-label">Agreement notes</label>
              <textarea
                v-model="form.notes"
                :disabled="isReadonly"
                rows="3"
                class="field-textarea field-textarea--compact"
                placeholder="Internal notes visible to property managers…"
              />
              <p v-if="errors.notes" class="field-error">{{ errors.notes[0] }}</p>
            </div>
            <div class="field-group">
              <label class="field-label">Special terms</label>
              <textarea
                v-model="form.special_terms"
                :disabled="isReadonly"
                rows="3"
                class="field-textarea field-textarea--compact"
                placeholder="Non-standard clauses or conditions…"
              />
              <p v-if="errors.special_terms" class="field-error">{{ errors.special_terms[0] }}</p>
            </div>
          </div>
        </section>

    </div>
    <!-- /form-shell -->

    <!-- ══════════════════════════════════════════════════════════
         STICKY ACTION BAR
    ══════════════════════════════════════════════════════════ -->
    <div v-if="!isReadonly" class="action-bar">
      <div class="action-bar__inner">
        <div class="action-bar__left">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="action-bar__info-icon">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
          <span class="action-bar__info-text">
            {{ isEdit ? 'You are editing an existing agreement.' : 'You are creating a new rental agreement.' }}
          </span>
        </div>
        <div class="action-bar__actions">
          <button type="button" class="action-btn action-btn--ghost" @click="$emit('cancel')">
            Cancel
          </button>
          <button type="submit" :disabled="loading" class="action-btn action-btn--primary">
            <svg v-if="loading" class="action-btn__spinner" width="14" height="14" viewBox="0 0 24 24" fill="none">
              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="32" stroke-linecap="round"/>
            </svg>
            <svg v-else width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
            {{ isEdit ? 'Update Agreement' : 'Create Agreement' }}
          </button>
        </div>
      </div>
    </div>

  </form>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import api from '@/services/api'
import { useConfirm } from '@/composables/useConfirm'
import { ErpDateInput, ErpSearchSelect } from '@/components/erp'
import { useBuildingApartments } from '@/composables/useBuildingApartments'
import { tenantDisplayName } from '@/utils/tenantDisplayName'
import {
  emptyRecurringChargeRow,
  chargeModelPolicyLabel,
  rowNeedsAmount,
  rowNeedsUnitRate,
} from '@/utils/rentalAgreementBilling'

const props = defineProps({
  form:       { type: Object,  required: true },
  errors:     { type: Object,  default: () => ({}) },
  loading:    { type: Boolean, default: false },
  mode:       { type: String,  default: 'create' },
  buildings:  { type: Array,   default: () => [] },
  /** @deprecated use buildings + building-scoped fetch */
  apartments: { type: Array,   default: () => [] },
  tenants:    { type: Array,   default: () => [] },
  initialBuildingId: { type: [String, Number], default: '' },
  initialStatus: {
    type: String,
    default: '',
  },
  embedded: { type: Boolean, default: false },
})
const emit = defineEmits(['submit', 'cancel'])

const { confirm } = useConfirm()

const selectedBuildingId = ref('')
const { apartments: buildingApartments, loading: apartmentsLoading, fetchApartments, apartmentToOption } = useBuildingApartments()

const apartmentOptions = computed(() =>
  buildingApartments.value.map((apt) => apartmentToOption(apt))
)

const apartmentEmptyText = computed(() => {
  if (!selectedBuildingId.value) return 'Select a building first'
  return props.mode === 'create'
    ? 'No available rental units in this building'
    : 'No rental units found in this building'
})

let apartmentSearchDebounce = null
function onApartmentSearch(query) {
  clearTimeout(apartmentSearchDebounce)
  apartmentSearchDebounce = setTimeout(() => reloadApartments(query), 280)
}

async function reloadApartments(search = '') {
  if (!selectedBuildingId.value) return
  await fetchApartments(selectedBuildingId.value, {
    search,
    mode: props.mode,
    ensureId: props.form.apartment_id || undefined,
  })
}

function buildingLabel(building) {
  const parts = [building.name, building.city, building.code].filter(Boolean)
  return parts.join(' · ')
}

watch(
  () => props.initialBuildingId,
  (id) => {
    if (id && String(id) !== selectedBuildingId.value) {
      selectedBuildingId.value = String(id)
    }
  },
  { immediate: true }
)

watch(selectedBuildingId, async (id, prev) => {
  if (!id) {
    buildingApartments.value = []
    return
  }
  if (prev && id !== prev) {
    props.form.apartment_id = ''
  }
  await reloadApartments()
})
/* ── Computed modes ──────────────────────────────────────────── */
const isReadonly = computed(() => props.mode === 'readonly')
const isEdit     = computed(() => props.mode === 'edit')
/** Billing lines stay editable unless the agreement is read-only (e.g. terminated). */
const canEditBilling = computed(() => !isReadonly.value)

const coreBaseline = ref(null)

function captureCoreBaseline() {
  if (coreBaseline.value || !isEdit.value) return
  if (!props.form.apartment_id && !props.form.start_date) return

  coreBaseline.value = {
    apartment_id: String(props.form.apartment_id || ''),
    tenant_id: String(props.form.tenant_id || ''),
    start_date: props.form.start_date || '',
  }
}

watch(
  () => [props.form.apartment_id, props.form.tenant_id, props.form.start_date],
  captureCoreBaseline,
  { immediate: true },
)

function hasCriticalCoreChanges() {
  if (!coreBaseline.value) return false

  return (
    String(props.form.apartment_id || '') !== coreBaseline.value.apartment_id
    || String(props.form.tenant_id || '') !== coreBaseline.value.tenant_id
    || (props.form.start_date || '') !== coreBaseline.value.start_date
  )
}

function criticalChangeMessages() {
  const lines = []
  if (!coreBaseline.value) return lines

  if (String(props.form.apartment_id || '') !== coreBaseline.value.apartment_id) {
    lines.push('Unit assignment will change; draft invoices will point to the new unit.')
  }
  if (String(props.form.tenant_id || '') !== coreBaseline.value.tenant_id) {
    lines.push('Tenant assignment will change for this agreement.')
  }
  if ((props.form.start_date || '') !== coreBaseline.value.start_date) {
    lines.push('Start date will change; charge billing schedules will be recalculated.')
  }

  return lines
}
/* ── Section nav ─────────────────────────────────────────────── */
const chargeModels = ref([])

const rentChargeModels = computed(() =>
  chargeModels.value.filter((m) => m.pricing_strategy === 'agreement_rent')
)

const recurringChargeModels = computed(() =>
  chargeModels.value.filter((m) => m.pricing_strategy !== 'agreement_rent')
)

function modelsForRow(row, index) {
  const usedElsewhere = new Set(
    (props.form.recurring_charges ?? [])
      .filter((_, i) => i !== index)
      .map((r) => r.charge_model_id)
      .filter(Boolean),
  )

  return recurringChargeModels.value.filter(
    (m) => m.id === row.charge_model_id || !usedElsewhere.has(m.id),
  )
}

async function loadChargeModels() {
  try {
    const { data } = await api.get('/charge-models', {
      params: { status: 'active', per_page: 50 },
    })
    chargeModels.value = data.data ?? []
  } catch (error) {
    chargeModels.value = []
    if (error.response?.status !== 403) {
      console.warn('Could not load charge models for billing section.', error.response?.status)
    }
  }
}

function ensureBillingFields() {
  if (!Array.isArray(props.form.recurring_charges)) {
    props.form.recurring_charges = []
  }
  if (props.form.rent_charge_model_id === undefined) {
    props.form.rent_charge_model_id = ''
  }
}

function addChargeRow() {
  ensureBillingFields()
  props.form.recurring_charges.push(emptyRecurringChargeRow())
}

function removeRecurringRow(index) {
  props.form.recurring_charges.splice(index, 1)
}

function resolveModel(row) {
  return chargeModels.value.find((m) => m.id === row.charge_model_id) ?? null
}

function onRecurringModelChange(row) {
  const model = resolveModel(row)
  if (!model) return
  row.preferMetered = model.pricing_strategy === 'metered'
  if (!rowNeedsAmount(model)) row.override_amount = null
  if (!rowNeedsUnitRate(model)) row.override_unit_rate = null
}

function recurringRowError(index, field) {
  const key = `recurring_charges.${index}.${field}`
  const err = props.errors[key]
  return Array.isArray(err) ? err[0] : err
}

const sections = [
  { id: 'agreement', label: 'Agreement' },
  { id: 'property', label: 'Property & Tenant' },
  { id: 'terms', label: 'Dates & Terms' },
  { id: 'billing', label: 'Charges' },
  { id: 'notes', label: 'Notes' },
]

const activeSection = ref('property')
const sectionsEl    = ref(null)

const scrollToSection = (id) => {
  const el = document.getElementById(`sec-${id}`)
  if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' })
  activeSection.value = id
}

let observer = null
onMounted(() => {
  ensureBillingFields()
  loadChargeModels()
  observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) activeSection.value = entry.target.dataset.section
    })
  }, { root: sectionsEl.value, threshold: 0.4 })

  document.querySelectorAll('[data-section]').forEach(el => observer.observe(el))
})
onUnmounted(() => observer?.disconnect())

/* ── Static options ──────────────────────────────────────────── */
const allStatusOptions = [
  { value: 'draft',      label: 'Draft'      },
  { value: 'pending',    label: 'Pending'    },
  { value: 'active',     label: 'Active'     },
  { value: 'terminated', label: 'Terminated' },
  { value: 'expired',    label: 'Expired'    },
]

const statusOptionsForMode = computed(() => {
  if (props.mode === 'create') {
    return allStatusOptions.filter((s) =>
      ['draft', 'pending', 'active'].includes(s.value)
    )
  }
  return allStatusOptions
})

const currencyOptions = ['USD', 'EUR', 'GBP', 'AED', 'SAR', 'TRY', 'KES', 'NGN', 'ZAR', 'EGP', 'SOS']

function tenantLabel(tenant) {
  return tenantDisplayName(tenant) || tenant.email || tenant.tenant_code || tenant.id
}


async function handleSubmit() {
  if (Array.isArray(props.form.recurring_charges)) {
    props.form.recurring_charges = props.form.recurring_charges.filter(
      (row) => row.charge_model_id
    )
  }
  if (!props.form.status || !statusOptionsForMode.value.some((s) => s.value === props.form.status)) {
    props.form.status = 'draft'
  }

  let confirmCriticalChanges = false

  if (isEdit.value && props.initialStatus === 'active' && hasCriticalCoreChanges()) {
    const detail = criticalChangeMessages().join(' ')
    const ok = await confirm({
      title: 'Confirm agreement changes',
      message: detail
        ? `${detail} Issued invoices will not be changed. Save anyway?`
        : 'These changes may affect future billing. Save anyway?',
      confirmLabel: 'Save changes',
      variant: 'primary',
    })
    if (!ok) return
    confirmCriticalChanges = true
  }

  emit('submit', { confirmCriticalChanges })
}
</script>

<style scoped>
/* ════════════════════════════════════════════════════════════
   Tokens
════════════════════════════════════════════════════════════ */
.agreement-form {
  --accent:        #4f46e5;
  --accent-lt:     #eef2ff;
  --accent-ring:   rgba(79,70,229,.15);
  --accent-hover:  #4338ca;

  --border:        #e5e7eb;
  --border-focus:  #818cf8;
  --surface:       #ffffff;
  --surface-alt:   #f9fafb;

  --text-1:  #111827;
  --text-2:  #4b5563;
  --text-3:  #9ca3af;

  --red:     #ef4444;
  --red-lt:  #fef2f2;
  --amber:   #d97706;
  --amber-lt:#fffbeb;
  --green:   #059669;

  --r-sm: 7px;
  --r-md: 10px;
  --r-lg: 14px;
  --r-xl: 18px;

  --shadow-sm: 0 1px 2px rgba(0,0,0,.05);
  --shadow-md: 0 4px 12px rgba(0,0,0,.08);

  --ease: 150ms cubic-bezier(.4,0,.2,1);

  font-family: 'DM Sans', ui-sans-serif, system-ui, sans-serif;
  display: flex;
  flex-direction: column;
  min-height: 0;
  height: 100%;
}

/* ════════════════════════════════════════════════════════════
   Form Header
════════════════════════════════════════════════════════════ */
.form-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 12px 18px;
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  flex-shrink: 0;
}

.form-header__left {
  display: flex;
  align-items: center;
  gap: 14px;
}

.form-header__icon {
  width: 44px;
  height: 44px;
  border-radius: var(--r-lg);
  background: var(--accent-lt);
  color: var(--accent);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.form-header__title {
  font-size: 17px;
  font-weight: 700;
  letter-spacing: -.02em;
  color: var(--text-1);
}

.form-header__sub {
  font-size: 13px;
  color: var(--text-3);
  margin-top: 2px;
}

/* Mode badge */
.mode-badge {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 6px 14px;
  border-radius: 99px;
  font-size: 12.5px;
  font-weight: 600;
  flex-shrink: 0;
}
.mode-badge__dot {
  width: 7px; height: 7px;
  border-radius: 50%;
}
.mode-badge--create  { background: #ecfdf5; color: #065f46; }
.mode-badge--create  .mode-badge__dot { background: var(--green); }
.mode-badge--edit    { background: #eff6ff; color: #1e40af; }
.mode-badge--edit    .mode-badge__dot { background: #3b82f6; }
.mode-badge--readonly{ background: #f9fafb; color: #6b7280; }
.mode-badge--readonly .mode-badge__dot { background: #9ca3af; }

/* ════════════════════════════════════════════════════════════
   Compact layout shell
════════════════════════════════════════════════════════════ */
.form-active-hint {
  margin: 0;
  padding: 8px 14px;
  font-size: 12px;
  line-height: 1.45;
  color: #92400e;
  background: #fffbeb;
  border-bottom: 1px solid #fde68a;
}

.section-tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  padding: 8px 14px;
  border-bottom: 1px solid var(--border);
  background: #f8fafc;
  position: sticky;
  top: 0;
  z-index: 8;
  flex-shrink: 0;
}

.section-tab {
  border: 1px solid var(--border);
  background: var(--surface);
  color: var(--text-2);
  font-size: 12px;
  font-weight: 600;
  padding: 5px 10px;
  border-radius: 999px;
  cursor: pointer;
  transition: background var(--ease), color var(--ease), border-color var(--ease);
}
.section-tab:hover { border-color: #c7d2fe; color: var(--accent); }
.section-tab--active {
  background: var(--accent-lt);
  border-color: #c7d2fe;
  color: var(--accent);
}

.form-shell {
  flex: 1;
  overflow-y: auto;
  min-width: 0;
  padding: 12px 14px 88px;
}

.form-block {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--r-md);
  padding: 12px 14px;
  margin-bottom: 10px;
}

.form-block__head {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  justify-content: space-between;
  gap: 4px 12px;
  margin-bottom: 10px;
  padding-bottom: 8px;
  border-bottom: 1px solid #f1f5f9;
}

.form-block__title {
  font-size: 13.5px;
  font-weight: 700;
  color: var(--text-1);
  letter-spacing: -.01em;
}

.form-block__sub {
  font-size: 11.5px;
  color: var(--text-3);
}

.grid-3 {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px 14px;
}

.col-span-2 { grid-column: span 2; }
.col-span-3 { grid-column: span 3; }

.toggle-chip {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 36px;
  padding: 0 14px;
  border-radius: var(--r-md);
  border: 1px solid var(--border);
  background: var(--surface-alt);
  font-size: 12.5px;
  font-weight: 600;
  color: var(--text-2);
  cursor: pointer;
  transition: all var(--ease);
}
.toggle-chip--on {
  border-color: var(--accent);
  background: var(--accent-lt);
  color: var(--accent);
}
.toggle-chip--disabled { opacity: .6; cursor: not-allowed; }

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  border: 0;
}

.billing-inline-amount {
  font-size: 14px;
  font-weight: 700;
  color: var(--text-1);
  margin: 0;
  padding-top: 8px;
}

.billing-services--compact { margin-top: 10px; }

/* Legacy section header (unused) */
.section-header {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  margin-bottom: 22px;
}

.section-header__icon {
  width: 36px;
  height: 36px;
  border-radius: var(--r-md);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.section-header__icon--indigo  { background: #eef2ff; color: #4f46e5; }
.section-header__icon--blue    { background: #eff6ff; color: #2563eb; }
.section-header__icon--emerald { background: #ecfdf5; color: #059669; }
.section-header__icon--violet { background: #ede9fe; color: #6d28d9; }

.billing-panel { display: flex; flex-direction: column; gap: 16px; }
.billing-rent-card {
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 14px;
  background: #fafafa;
}
.billing-rent-card__head { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 12px; }
.billing-rent-card__title { font-size: 14px; font-weight: 600; color: var(--text); }
.billing-rent-card__badge { font-size: 11px; font-weight: 500; color: #6d28d9; background: #ede9fe; padding: 2px 8px; border-radius: 999px; }
.billing-rent-card__amount { font-size: 18px; font-weight: 700; color: var(--text); margin: 4px 0 0; }
.billing-services__head { display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap; }
.billing-services__title { font-size: 14px; font-weight: 600; color: var(--text); }
.billing-services__actions { display: flex; flex-wrap: wrap; gap: 6px; }
.billing-services__intro { margin: 6px 0 12px; }
.btn-add-line {
  font-size: 12px;
  font-weight: 600;
  color: var(--primary);
  background: #eef2ff;
  border: 0;
  border-radius: 8px;
  padding: 6px 10px;
  cursor: pointer;
}
.btn-add-line--utility {
  color: #b45309;
  background: #fffbeb;
}
.billing-empty {
  font-size: 13px;
  color: var(--text-muted);
  border: 1px dashed var(--border);
  border-radius: 10px;
  padding: 14px;
  text-align: center;
}
.billing-line {
  border: 1px solid var(--border);
  border-radius: var(--r-md);
  padding: 10px;
  margin-bottom: 8px;
  background: #fafafa;
}
.billing-line--compact .billing-line__grid {
  align-items: end;
}
.billing-line__remove {
  height: 36px;
  width: 100%;
  font-size: 12px;
  color: #b91c1c;
  background: #fff;
  border: 1px solid #fecaca;
  border-radius: var(--r-sm);
  cursor: pointer;
}
.billing-line__remove:hover { background: #fef2f2; }
.field-group--full { grid-column: 1 / -1; }
.section-header__icon--amber   { background: #fffbeb; color: #d97706; }
.section-header__icon--teal    { background: #f0fdfa; color: #0d9488; }
.section-header__icon--rose    { background: #fff1f2; color: #e11d48; }
.section-header__icon--slate   { background: #f8fafc; color: #475569; }

.section-header__title {
  font-size: 15px;
  font-weight: 700;
  color: var(--text-1);
  letter-spacing: -.01em;
}
.section-header__sub {
  font-size: 13px;
  color: var(--text-3);
  margin-top: 2px;
  line-height: 1.5;
}

/* ════════════════════════════════════════════════════════════
   Field Grid
════════════════════════════════════════════════════════════ */
.field-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px 14px;
}

.field-group {
  display: flex;
  flex-direction: column;
  gap: 0;
  min-width: 0;
}

.field-label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11.5px;
  font-weight: 600;
  color: var(--text-2);
  margin-bottom: 4px;
}
.field-required { color: var(--red); font-size: 13px; }
.field-hint-inline { font-weight: 400; color: var(--text-3); font-size: 11.5px; }

/* Base input */
.field-input {
  height: 36px;
  width: 100%;
  border: 1px solid var(--border);
  border-radius: var(--r-md);
  background: var(--surface);
  padding: 0 14px;
  font-size: 13.5px;
  color: var(--text-1);
  outline: none;
  font-family: inherit;
  transition: border-color var(--ease), box-shadow var(--ease);
  box-shadow: var(--shadow-sm);
}
.field-input:focus {
  border-color: var(--border-focus);
  box-shadow: 0 0 0 3px var(--accent-ring);
}
.field-input:disabled {
  background: var(--surface-alt);
  color: var(--text-3);
  cursor: not-allowed;
  box-shadow: none;
}

/* Select */
.select-wrap {
  position: relative;
  display: flex;
  align-items: center;
}
.select-wrap__icon {
  position: absolute;
  left: 13px;
  color: var(--text-3);
  pointer-events: none;
  z-index: 1;
}
.select-wrap__chevron {
  position: absolute;
  right: 13px;
  color: var(--text-3);
  pointer-events: none;
  z-index: 1;
}
.field-select {
  height: 36px;
  width: 100%;
  border: 1px solid var(--border);
  border-radius: var(--r-md);
  background: var(--surface);
  padding: 0 36px 0 14px;
  font-size: 13.5px;
  color: var(--text-1);
  outline: none;
  appearance: none;
  cursor: pointer;
  font-family: inherit;
  transition: border-color var(--ease), box-shadow var(--ease);
  box-shadow: var(--shadow-sm);
}
.field-select--icon { padding-left: 36px; }
.field-select:focus {
  border-color: var(--border-focus);
  box-shadow: 0 0 0 3px var(--accent-ring);
}
.field-select:disabled {
  background: var(--surface-alt);
  color: var(--text-3);
  cursor: not-allowed;
}

/* Prefix/suffix input */
.input-affix-wrap {
  display: flex;
  align-items: center;
  position: relative;
}
.input-prefix {
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 52px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--surface-alt);
  border: 1px solid var(--border);
  border-right: none;
  border-radius: var(--r-md) 0 0 var(--r-md);
  font-size: 12px;
  font-weight: 600;
  color: var(--text-2);
  pointer-events: none;
}
.field-input--prefix {
  padding-left: 62px;
  border-radius: 0 var(--r-md) var(--r-md) 0;
}
.field-input--prefix:focus { z-index: 1; }

/* Textarea */
.field-textarea {
  width: 100%;
  border: 1px solid var(--border);
  border-radius: var(--r-md);
  background: var(--surface);
  padding: 12px 14px;
  font-size: 13.5px;
  color: var(--text-1);
  outline: none;
  resize: vertical;
  min-height: 100px;
  font-family: inherit;
  line-height: 1.6;
  transition: border-color var(--ease), box-shadow var(--ease);
  box-shadow: var(--shadow-sm);
}
.field-textarea:focus {
  border-color: var(--border-focus);
  box-shadow: 0 0 0 3px var(--accent-ring);
}
.field-textarea:disabled {
  background: var(--surface-alt);
  color: var(--text-3);
  cursor: not-allowed;
}
.field-textarea--internal { background: #fffdf7; border-color: #fde68a; }
.field-textarea--internal:focus { border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,.12); }

/* Date */
.field-date {
  height: 36px;
  width: 100%;
  border: 1px solid var(--border);
  border-radius: var(--r-md);
  background: var(--surface);
  padding: 0 14px;
  font-size: 14px;
  color: var(--text-1);
  outline: none;
  font-family: inherit;
  transition: border-color var(--ease), box-shadow var(--ease);
  box-shadow: var(--shadow-sm);
}
.field-date:focus { border-color: var(--border-focus); box-shadow: 0 0 0 3px var(--accent-ring); }
.field-date:disabled { background: var(--surface-alt); color: var(--text-3); cursor: not-allowed; }

/* Stepper */
.stepper-wrap {
  display: flex;
  align-items: center;
  border: 1px solid var(--border);
  border-radius: var(--r-md);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
  height: 36px;
}
.stepper-wrap--compact { max-width: 160px; }
.stepper-btn {
  width: 40px;
  height: 100%;
  background: var(--surface-alt);
  border: none;
  font-size: 18px;
  font-weight: 500;
  color: var(--text-2);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background var(--ease), color var(--ease);
  flex-shrink: 0;
}
.stepper-btn:hover:not(:disabled) { background: #f0f0f0; color: var(--text-1); }
.stepper-btn:disabled { color: var(--text-3); cursor: not-allowed; }
.field-input--stepper {
  flex: 1;
  height: 100%;
  border: none;
  border-left: 1px solid var(--border);
  border-right: 1px solid var(--border);
  border-radius: 0;
  text-align: center;
  box-shadow: none;
  min-width: 0;
}
.field-input--stepper:focus { box-shadow: none; border-color: var(--border); }

/* Hints / Errors */
.field-hint {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 12px;
  color: var(--text-3);
  margin-top: 5px;
}
.field-hint--warn { color: var(--amber); }
.field-error { font-size: 12px; color: var(--red); margin-top: 5px; }

/* ════════════════════════════════════════════════════════════
   Date Range Card
════════════════════════════════════════════════════════════ */
.date-range-card {
  display: flex;
  align-items: center;
  gap: 0;
  background: #fafafa;
  border: 1px solid var(--border);
  border-radius: var(--r-lg);
  padding: 20px 24px;
  gap: 16px;
}
.date-range-card__field {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.date-range-card__label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .07em;
  color: var(--text-3);
}
.date-range-card__arrow {
  color: var(--text-3);
  flex-shrink: 0;
  margin-top: 24px;
}

/* ════════════════════════════════════════════════════════════
   Status Select Grid
════════════════════════════════════════════════════════════ */
.status-select-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 2px;
}
.status-select-grid--compact .status-option {
  padding: 4px 10px;
  font-size: 11.5px;
}
.status-option {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 6px 14px;
  border-radius: 99px;
  border: 1.5px solid var(--border);
  background: var(--surface);
  font-size: 12.5px;
  font-weight: 600;
  color: var(--text-2);
  cursor: pointer;
  transition: all var(--ease);
}
.status-option:hover:not(:disabled) { border-color: #9ca3af; }
.status-option:disabled { cursor: not-allowed; opacity: .5; }

.status-option__dot {
  width: 7px; height: 7px;
  border-radius: 50%;
  background: currentColor;
  opacity: .5;
}
.status-option--selected .status-option__dot { opacity: 1; }

.status-option--draft.status-option--selected          { border-color: #6b7280; background: #f9fafb; color: #374151; }
.status-option--pending.status-option--selected        { border-color: #f59e0b; background: #fffbeb; color: #92400e; }
.status-option--active.status-option--selected         { border-color: #10b981; background: #ecfdf5; color: #065f46; }
.status-option--terminated.status-option--selected     { border-color: #ef4444; background: #fef2f2; color: #991b1b; }
.status-option--expired.status-option--selected        { border-color: #8b5cf6; background: #f5f3ff; color: #5b21b6; }

/* ════════════════════════════════════════════════════════════
   Utility Cards
════════════════════════════════════════════════════════════ */
.utility-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 14px;
}

.utility-card {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px 18px;
  border: 1.5px solid var(--border);
  border-radius: var(--r-lg);
  background: var(--surface);
  cursor: pointer;
  transition: border-color var(--ease), background var(--ease), box-shadow var(--ease);
}
.utility-card:hover:not(.utility-card--disabled) {
  border-color: #c7d2fe;
  box-shadow: var(--shadow-md);
}
.utility-card--checked {
  border-color: var(--accent);
  background: var(--accent-lt);
}
.utility-card--disabled { cursor: not-allowed; opacity: .6; }

.utility-card__check { display: none; }

.utility-card__icon {
  width: 38px;
  height: 38px;
  border-radius: var(--r-md);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.utility-card__icon--blue   { background: #eff6ff; color: #2563eb; }
.utility-card__icon--amber  { background: #fffbeb; color: #d97706; }
.utility-card__icon--purple { background: #f5f3ff; color: #7c3aed; }

.utility-card__body { flex: 1; min-width: 0; }
.utility-card__name { font-size: 13.5px; font-weight: 600; color: var(--text-1); }
.utility-card__sub  { font-size: 11.5px; color: var(--text-3); margin-top: 1px; }

/* Toggle pill */
.utility-card__toggle {
  width: 36px;
  height: 20px;
  border-radius: 99px;
  background: #d1d5db;
  position: relative;
  flex-shrink: 0;
  transition: background var(--ease);
}
.utility-card__toggle--on { background: var(--accent); }
.utility-card__toggle-knob {
  position: absolute;
  top: 2px;
  left: 2px;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: #fff;
  box-shadow: 0 1px 3px rgba(0,0,0,.15);
  transition: transform var(--ease);
}
.utility-card__toggle--on .utility-card__toggle-knob { transform: translateX(16px); }

/* ════════════════════════════════════════════════════════════
   Renewal Row
════════════════════════════════════════════════════════════ */
.renewal-row {
  display: flex;
  align-items: flex-start;
  gap: 20px;
  flex-wrap: wrap;
}

.auto-renew-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 18px 20px;
  border: 1.5px solid var(--border);
  border-radius: var(--r-lg);
  background: var(--surface);
  cursor: pointer;
  flex: 1;
  min-width: 240px;
  transition: border-color var(--ease), background var(--ease);
}
.auto-renew-card--on { border-color: var(--accent); background: var(--accent-lt); }
.auto-renew-card--disabled { cursor: not-allowed; opacity: .6; }

.auto-renew-card__title { font-size: 14px; font-weight: 700; color: var(--text-1); }
.auto-renew-card__sub   { font-size: 12.5px; color: var(--text-3); margin-top: 2px; }

.auto-renew-card__badge {
  margin-left: auto;
  padding: 5px 12px;
  border-radius: 99px;
  font-size: 12px;
  font-weight: 600;
  background: #f3f4f6;
  color: var(--text-3);
  flex-shrink: 0;
  transition: background var(--ease), color var(--ease);
}
.auto-renew-card__badge--on { background: #e0e7ff; color: var(--accent); }

/* ════════════════════════════════════════════════════════════
   File Drop Zone
════════════════════════════════════════════════════════════ */
.file-drop-zone {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px dashed var(--border);
  border-radius: var(--r-lg);
  padding: 36px 24px;
  cursor: pointer;
  transition: border-color var(--ease), background var(--ease);
  background: #fafafa;
}
.file-drop-zone:hover:not(.file-drop-zone--disabled) {
  border-color: #818cf8;
  background: var(--accent-lt);
}
.file-drop-zone--has-file {
  border-style: solid;
  border-color: #a5b4fc;
  background: var(--accent-lt);
  padding: 20px 24px;
}
.file-drop-zone--disabled { cursor: not-allowed; opacity: .6; }

.file-drop-zone__input {
  position: absolute;
  inset: 0;
  opacity: 0;
  cursor: pointer;
  width: 100%;
  height: 100%;
}

.file-drop-zone__placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  pointer-events: none;
}
.file-drop-zone__icon {
  width: 56px;
  height: 56px;
  border-radius: var(--r-lg);
  background: #e0e7ff;
  color: var(--accent);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 4px;
}
.file-drop-zone__title { font-size: 14px; font-weight: 600; color: var(--text-2); }
.file-drop-zone__link  { color: var(--accent); text-decoration: underline; }
.file-drop-zone__hint  { font-size: 12px; color: var(--text-3); }

.file-drop-zone__preview {
  display: flex;
  align-items: center;
  gap: 12px;
  color: var(--accent);
  pointer-events: none;
}
.file-drop-zone__name { font-size: 13.5px; font-weight: 600; color: var(--text-1); flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.file-drop-zone__remove {
  width: 28px;
  height: 28px;
  border-radius: var(--r-sm);
  border: 1px solid #fecaca;
  background: #fff;
  color: var(--red);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  pointer-events: auto;
  transition: background var(--ease);
  flex-shrink: 0;
}
.file-drop-zone__remove:hover { background: #fef2f2; }

/* ════════════════════════════════════════════════════════════
   Notes Grid
════════════════════════════════════════════════════════════ */
.notes-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px 14px;
}

.field-textarea--compact {
  min-height: 72px;
  padding: 8px 10px;
  font-size: 13px;
  line-height: 1.45;
  resize: vertical;
}

/* ════════════════════════════════════════════════════════════
   Sticky Action Bar
════════════════════════════════════════════════════════════ */
.action-bar {
  position: sticky;
  bottom: 0;
  background: rgba(255,255,255,.95);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border-top: 1px solid var(--border);
  z-index: 20;
  flex-shrink: 0;
}
.action-bar__inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 16px;
  gap: 12px;
}
.action-bar__left {
  display: flex;
  align-items: center;
  gap: 8px;
}
.action-bar__info-icon { color: var(--text-3); flex-shrink: 0; }
.action-bar__info-text { font-size: 13px; color: var(--text-3); }
.action-bar__actions { display: flex; align-items: center; gap: 10px; }

.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 22px;
  border-radius: var(--r-md);
  font-size: 13.5px;
  font-weight: 600;
  cursor: pointer;
  border: none;
  transition: background var(--ease), color var(--ease), transform var(--ease), box-shadow var(--ease);
  font-family: inherit;
}
.action-btn--ghost {
  background: transparent;
  border: 1px solid var(--border);
  color: var(--text-2);
}
.action-btn--ghost:hover { background: var(--surface-alt); color: var(--text-1); }

.action-btn--primary {
  background: var(--accent);
  color: #fff;
  box-shadow: 0 2px 8px rgba(79,70,229,.3);
}
.action-btn--primary:hover:not(:disabled) {
  background: var(--accent-hover);
  transform: translateY(-1px);
  box-shadow: 0 4px 14px rgba(79,70,229,.4);
}
.action-btn--primary:disabled { opacity: .6; cursor: not-allowed; transform: none; }

.action-btn__spinner {
  animation: spin .7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ════════════════════════════════════════════════════════════
   Embedded in FormModal
════════════════════════════════════════════════════════════ */
.agreement-form--embedded {
  height: auto;
  font-family: inherit;
}

.agreement-form--embedded .form-shell {
  padding-bottom: 0;
}

.agreement-form--embedded .form-block {
  border-left: none;
  border-right: none;
  border-radius: 0;
  padding-left: 0;
  padding-right: 0;
}

.agreement-form--embedded .grid-3,
.agreement-form--embedded .field-grid,
.agreement-form--embedded .notes-grid,
.agreement-form--embedded .utility-grid {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.agreement-form--embedded .field-group {
  max-width: none !important;
  flex: initial !important;
  min-width: 0;
}

.agreement-form--embedded .field-input,
.agreement-form--embedded .field-select,
.agreement-form--embedded .field-textarea,
.agreement-form--embedded .field-select--icon {
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
}

.agreement-form--embedded .action-bar {
  position: sticky;
  bottom: 0;
  margin: 0 -0.25rem;
  background: #fff;
  border-top: 1px solid var(--border);
}

.agreement-form--embedded .action-bar__inner {
  padding: 12px 0;
}

/* ════════════════════════════════════════════════════════════
   Responsive
════════════════════════════════════════════════════════════ */
@media (max-width: 1024px) {
  .grid-3,
  .field-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .col-span-2,
  .col-span-3 { grid-column: span 2; }
}

@media (max-width: 768px) {
  .form-header { padding: 10px 14px; }
  .form-shell { padding: 10px 12px 72px; }
  .grid-3,
  .field-grid,
  .notes-grid,
  .utility-grid { grid-template-columns: 1fr; }
  .col-span-2,
  .col-span-3 { grid-column: span 1; }
  .section-tabs { padding: 6px 10px; }
  .action-bar__info-text { display: none; }
  .stepper-wrap--compact { max-width: none; }
}

@media (max-width: 480px) {
  .form-header__sub { display: none; }
  .form-block__sub { display: none; }
  .status-option { padding: 4px 8px; font-size: 11px; }
}
</style>