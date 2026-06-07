<template>
  <div class="meter-create">

    <!-- ══════════════════════════════════════════════════════════
         PAGE HEADER
    ══════════════════════════════════════════════════════════ -->
    <div class="page-header">
      <div class="page-header__left">
        <router-link :to="{ name: 'Meters' }" class="back-btn">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
          </svg>
          Meter Registry
        </router-link>
        <div class="breadcrumb-sep">/</div>
        <span class="breadcrumb-current">Register Meter</span>
      </div>
    </div>

    <div class="create-layout">

      <!-- ══════════════════════════════════════════════════════════
           LEFT: FORM
      ══════════════════════════════════════════════════════════ -->
      <div class="form-column">

        <!-- ── Global error banner ─────────────────────────── -->
        <Transition name="err-slide">
          <div v-if="globalError" class="global-error" role="alert">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <span>{{ globalError }}</span>
            <button class="global-error__close" @click="globalError = ''">×</button>
          </div>
        </Transition>

        <form @submit.prevent="submitForm" novalidate>

          <!-- ─── Section 1: Core Identity ──────────────────── -->
          <div class="form-card" id="sec-core">
            <div class="form-card__header">
              <div class="section-icon section-icon--indigo">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/>
                </svg>
              </div>
              <div>
                <h3 class="section-title">Core Identity</h3>
                <p class="section-sub">Meter identification and classification</p>
              </div>
              <div class="section-step">01</div>
            </div>

            <div class="field-grid field-grid--4">

              <!-- Meter Number -->
              <div class="field-group field-group--span2">
                <label class="field-label">
                  Meter Number
                  <span class="req">*</span>
                </label>
                <div class="input-wrap" :class="{ 'input-wrap--error': errors.meter_number, 'input-wrap--valid': form.meter_number && !errors.meter_number }">
                  <svg class="input-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="4" width="20" height="16" rx="2"/><line x1="6" y1="8" x2="10" y2="8"/>
                    <line x1="6" y1="12" x2="14" y2="12"/><line x1="6" y1="16" x2="12" y2="16"/>
                  </svg>
                  <input
                    v-model="form.meter_number"
                    type="text"
                    placeholder="e.g. MTR-0001"
                    class="field-input field-input--mono"
                    @blur="validateField('meter_number')"
                  />
                  <span v-if="form.meter_number && !errors.meter_number" class="input-valid-mark">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                  </span>
                </div>
                <FieldError :error="errors.meter_number" />
              </div>

              <!-- Serial Number -->
              <div class="field-group field-group--span2">
                <label class="field-label">Serial Number</label>
                <div class="input-wrap" :class="{ 'input-wrap--error': errors.serial_number }">
                  <svg class="input-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="4" y1="9" x2="20" y2="9"/><line x1="4" y1="15" x2="20" y2="15"/>
                    <line x1="10" y1="3" x2="8" y2="21"/><line x1="16" y1="3" x2="14" y2="21"/>
                  </svg>
                  <input v-model="form.serial_number" type="text" placeholder="e.g. SN-000001" class="field-input field-input--mono" />
                </div>
                <FieldError :error="errors.serial_number" />
              </div>

              <!-- Utility Type -->
              <div class="field-group field-group--span2">
                <label class="field-label">
                  Utility Type
                  <span class="req">*</span>
                </label>
                <!-- Visual utility picker -->
                <div class="utility-picker">
                  <button
                    v-for="ut in utilityTypes"
                    :key="ut.value"
                    type="button"
                    :class="['utility-chip', `utility-chip--${ut.value}`, { 'utility-chip--active': form.utility_type === ut.value }]"
                    @click="form.utility_type = ut.value; errors.utility_type = null"
                    :title="ut.label"
                  >
                    <span class="utility-chip__icon" v-html="ut.icon"></span>
                    <span class="utility-chip__label">{{ ut.label }}</span>
                  </button>
                </div>
                <FieldError :error="errors.utility_type" />
              </div>

              <!-- Meter Type -->
              <div class="field-group field-group--span2">
                <label class="field-label">
                  Meter Type
                  <span class="req">*</span>
                </label>
                <div class="type-selector">
                  <button
                    v-for="mt in meterTypes"
                    :key="mt.value"
                    type="button"
                    :class="['type-option', { 'type-option--active': form.meter_type === mt.value }]"
                    @click="form.meter_type = mt.value; errors.meter_type = null"
                  >
                    <span class="type-option__icon" v-html="mt.icon"></span>
                    <span class="type-option__name">{{ mt.label }}</span>
                    <span class="type-option__sub">{{ mt.sub }}</span>
                  </button>
                </div>
                <FieldError :error="errors.meter_type" />
              </div>

            </div>
          </div>

          <!-- ─── Section 2: Property Assignment ──────────────── -->
          <div class="form-card" id="sec-property">
            <div class="form-card__header">
              <div class="section-icon section-icon--blue">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
              </div>
              <div>
                <h3 class="section-title">Property Assignment</h3>
                <p class="section-sub">Link to building, apartment, and ownership scope</p>
              </div>
              <div class="section-step">02</div>
            </div>

            <div class="field-grid field-grid--4">

              <!-- Building -->
              <div class="field-group field-group--span2">
                <label class="field-label">Building <span class="req">*</span></label>
                <div class="select-wrap" :class="{ 'select-wrap--error': errors.building_id }">
                  <svg class="input-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>
                  </svg>
                  <select v-model="form.building_id" class="field-select" @change="errors.building_id = null">
                    <option value="">Select building…</option>
                    <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                  </select>
                  <svg class="select-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
                <FieldError :error="errors.building_id" />
              </div>

              <!-- Apartment -->
              <div class="field-group field-group--span2">
                <label class="field-label">
                  Apartment
                  <span v-if="!form.building_id" class="field-label__hint">— select a building first</span>
                </label>
                <div class="select-wrap" :class="{ 'select-wrap--disabled': !form.building_id }">
                  <svg class="input-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
                  </svg>
                  <select v-model="form.apartment_id" :disabled="!form.building_id" class="field-select">
                    <option value="">
                      {{ apartmentsLoading ? 'Loading…' : apartments.length === 0 && form.building_id ? 'No apartments found' : 'Select apartment…' }}
                    </option>
                    <option v-for="apt in apartments" :key="apt.id" :value="apt.id">
                      Unit {{ apt.unit?.unit_number }} · Floor {{ apt.unit?.floor }}
                    </option>
                  </select>
                  <svg v-if="!apartmentsLoading" class="select-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                  <div v-else class="select-loading"></div>
                </div>
                <FieldError :error="errors.apartment_id" />
              </div>

              <!-- Ownership Type -->
              <div class="field-group field-group--span2">
                <label class="field-label">Ownership Type <span class="req">*</span></label>
                <div class="ownership-grid">
                  <button
                    v-for="ot in ownershipTypes"
                    :key="ot.value"
                    type="button"
                    :class="['ownership-option', { 'ownership-option--active': form.ownership_type === ot.value }]"
                    @click="form.ownership_type = ot.value; errors.ownership_type = null"
                  >
                    <span class="ownership-option__icon" v-html="ot.icon"></span>
                    <span class="ownership-option__label">{{ ot.label }}</span>
                  </button>
                </div>
                <FieldError :error="errors.ownership_type" />
              </div>

              <!-- Tenant (conditional) -->
              <Transition name="slide-in">
                <div v-if="form.ownership_type === 'tenant'" class="field-group field-group--span2">
                  <label class="field-label">
                    Tenant
                    <span class="req">*</span>
                    <span class="field-label__badge">Required for Tenant ownership</span>
                  </label>
                  <div class="select-wrap" :class="{ 'select-wrap--error': errors.tenant_id }">
                    <svg class="input-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                    </svg>
                    <select v-model="form.tenant_id" class="field-select">
                      <option value="">Select tenant…</option>
                      <option v-for="t in tenants" :key="t.id" :value="t.id">
                        {{ t.full_name || t.name }}
                      </option>
                    </select>
                    <svg class="select-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                  </div>
                  <FieldError :error="errors.tenant_id" />
                </div>
              </Transition>

              <!-- Measurement Unit -->
              <div class="field-group field-group--span2">
                <label class="field-label">Measurement Unit <span class="req">*</span></label>
                <div class="unit-selector">
                  <button
                    v-for="mu in filteredMeasurementUnits"
                    :key="mu.value"
                    type="button"
                    :class="['unit-chip', { 'unit-chip--active': form.measurement_unit === mu.value }]"
                    @click="form.measurement_unit = mu.value; errors.measurement_unit = null"
                  >
                    <span class="unit-chip__value">{{ mu.label }}</span>
                    <span class="unit-chip__desc">{{ mu.desc }}</span>
                  </button>
                </div>
                <FieldError :error="errors.measurement_unit" />
              </div>

            </div>
          </div>

          <!-- ─── Section 3: Operational Details ──────────────── -->
          <div class="form-card" id="sec-ops">
            <div class="form-card__header">
              <div class="section-icon section-icon--emerald">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="3"/>
                  <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                </svg>
              </div>
              <div>
                <h3 class="section-title">Operational Details</h3>
                <p class="section-sub">Readings, calibration, hardware, and location</p>
              </div>
              <div class="section-step">03</div>
            </div>

            <div class="field-grid field-grid--4">

              <!-- Initial Reading -->
              <div class="field-group">
                <label class="field-label">
                  Initial Reading
                  <span class="field-label__hint">{{ form.measurement_unit || 'unit' }}</span>
                </label>
                <div class="input-wrap">
                  <svg class="input-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                  </svg>
                  <input v-model.number="form.initial_reading" type="number" step="0.0001" min="0" placeholder="0.0000" class="field-input" />
                </div>
                <FieldError :error="errors.initial_reading" />
              </div>

              <!-- Multiplier Factor -->
              <div class="field-group">
                <label class="field-label">
                  Multiplier
                  <span class="field-label__hint">calibration factor</span>
                </label>
                <div class="input-wrap">
                  <svg class="input-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/>
                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                    <line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/>
                  </svg>
                  <input v-model.number="form.multiplier_factor" type="number" step="0.0001" min="0.0001" placeholder="1.0000" class="field-input" />
                </div>
                <p v-if="form.multiplier_factor && form.multiplier_factor !== 1" class="field-hint field-hint--info">
                  Actual = reading × {{ form.multiplier_factor }}
                </p>
                <FieldError :error="errors.multiplier_factor" />
              </div>

              <!-- Installation Date -->
              <div class="field-group">
                <label class="field-label">Installation Date</label>
                <div class="input-wrap">
                  <svg class="input-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                  </svg>
                  <ErpDateInput v-model="form.installation_date" input-class="field-input" placeholder="Installation date" />
                </div>
                <FieldError :error="errors.installation_date" />
              </div>

              <!-- Inspection Due -->
              <div class="field-group">
                <label class="field-label">
                  Inspection Due
                  <span v-if="isInspectionOverdue" class="overdue-badge">Overdue</span>
                </label>
                <div class="input-wrap" :class="{ 'input-wrap--warn': isInspectionOverdue }">
                  <svg class="input-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                  </svg>
                  <ErpDateInput
                    v-model="form.inspection_due_date"
                    input-class="field-input"
                    placeholder="Inspection due"
                    :min="form.installation_date || ''"
                  />
                </div>
                <FieldError :error="errors.inspection_due_date" />
              </div>

              <!-- Manufacturer -->
              <div class="field-group">
                <label class="field-label">Manufacturer</label>
                <div class="input-wrap">
                  <svg class="input-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>
                    <line x1="12" y1="12" x2="12" y2="16"/><line x1="10" y1="14" x2="14" y2="14"/>
                  </svg>
                  <input v-model="form.manufacturer" type="text" placeholder="e.g. Siemens, Landis+Gyr" class="field-input" />
                </div>
                <FieldError :error="errors.manufacturer" />
              </div>

              <!-- Model Number -->
              <div class="field-group">
                <label class="field-label">Model Number</label>
                <div class="input-wrap">
                  <svg class="input-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                  </svg>
                  <input v-model="form.model_number" type="text" placeholder="e.g. XG-200" class="field-input field-input--mono" />
                </div>
                <FieldError :error="errors.model_number" />
              </div>

              <!-- Location Description (span 2) -->
              <div class="field-group field-group--span2">
                <label class="field-label">Location Description</label>
                <div class="input-wrap">
                  <svg class="input-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="10" r="3"/><path d="M12 21.7C17.3 17 20 13 20 10a8 8 0 1 0-16 0c0 3 2.7 6.9 8 11.7z"/>
                  </svg>
                  <input v-model="form.location_description" type="text" placeholder="e.g. Basement utility room, East wall panel B" class="field-input" />
                </div>
                <FieldError :error="errors.location_description" />
              </div>

            </div>

            <!-- Status + Feature Toggles -->
            <div class="toggles-row">

              <!-- Status -->
              <div class="status-toggle-group">
                <span class="toggles-row__label">Status</span>
                <div class="status-toggle">
                  <button
                    type="button"
                    :class="['status-btn', { 'status-btn--active-green': form.status === 'active' }]"
                    @click="form.status = 'active'"
                  >
                    <span class="status-btn__dot"></span>
                    Active
                  </button>
                  <button
                    type="button"
                    :class="['status-btn', { 'status-btn--active-red': form.status === 'inactive' }]"
                    @click="form.status = 'inactive'"
                  >
                    <span class="status-btn__dot"></span>
                    Inactive
                  </button>
                </div>
              </div>

              <div class="feature-toggles">
                <span class="toggles-row__label">Features</span>
                <label :class="['toggle-pill', { 'toggle-pill--on': form.is_shared }]">
                  <input v-model="form.is_shared" type="checkbox" class="toggle-pill__check" />
                  <div class="toggle-switch" :class="{ 'toggle-switch--on': form.is_shared }">
                    <span class="toggle-knob"></span>
                  </div>
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                  </svg>
                  Shared
                </label>

                <label :class="['toggle-pill', { 'toggle-pill--on': form.supports_remote_reading }]">
                  <input v-model="form.supports_remote_reading" type="checkbox" class="toggle-pill__check" />
                  <div class="toggle-switch" :class="{ 'toggle-switch--on': form.supports_remote_reading }">
                    <span class="toggle-knob"></span>
                  </div>
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="2"/><path d="M16.24 7.76a6 6 0 0 1 0 8.49m-8.48-.01a6 6 0 0 1 0-8.49m11.31-2.82a10 10 0 0 1 0 14.14m-14.14 0a10 10 0 0 1 0-14.14"/>
                  </svg>
                  Remote Reading
                </label>

                <label :class="['toggle-pill', { 'toggle-pill--on toggle-pill--warn': form.maintenance_required }]">
                  <input v-model="form.maintenance_required" type="checkbox" class="toggle-pill__check" />
                  <div class="toggle-switch" :class="{ 'toggle-switch--on toggle-switch--warn': form.maintenance_required }">
                    <span class="toggle-knob"></span>
                  </div>
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                  </svg>
                  Needs Maintenance
                </label>
              </div>
            </div>

          </div>

          <!-- ─── Section 4: Notes ──────────────────────────── -->
          <div class="form-card" id="sec-notes">
            <div class="form-card__header">
              <div class="section-icon section-icon--slate">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/>
                  <line x1="8" y1="18" x2="21" y2="18"/>
                  <line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>
                </svg>
              </div>
              <div>
                <h3 class="section-title">Operational Notes</h3>
                <p class="section-sub">Irregularities, history, or maintenance context</p>
              </div>
              <div class="section-step">04</div>
            </div>
            <textarea
              v-model="form.notes"
              rows="4"
              placeholder="Add any specific operational notes, irregularities, or historical context for this meter…"
              class="field-textarea"
            ></textarea>
            <div class="textarea-footer">
              <span class="char-count" :class="{ 'char-count--near': (form.notes?.length || 0) > 400 }">
                {{ form.notes?.length || 0 }} / 500
              </span>
            </div>
            <FieldError :error="errors.notes" />
          </div>

          <!-- ─── Action Bar ─────────────────────────────────── -->
          <div class="action-bar">
            <div class="action-bar__left">
              <div v-if="completionScore < 100" class="completion-hint">
                <div class="completion-bar">
                  <div class="completion-fill" :style="{ width: completionScore + '%' }"></div>
                </div>
                <span>{{ completionScore }}% complete</span>
              </div>
              <div v-else class="completion-ready">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                Ready to register
              </div>
            </div>
            <div class="action-bar__actions">
              <router-link :to="{ name: 'Meters' }" class="btn-ghost">Cancel</router-link>
              <button type="submit" :disabled="processing" class="btn-submit">
                <svg v-if="processing" class="btn-spinner" width="15" height="15" viewBox="0 0 24 24" fill="none">
                  <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="32" stroke-linecap="round"/>
                </svg>
                <svg v-else width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="20 6 9 17 4 12"/>
                </svg>
                {{ processing ? 'Registering…' : 'Register Meter' }}
              </button>
            </div>
          </div>

        </form>
      </div>

      <!-- ══════════════════════════════════════════════════════════
           RIGHT: LIVE PREVIEW CARD
      ══════════════════════════════════════════════════════════ -->
      <aside class="preview-column">
        <div class="preview-card" :class="utilityThemeClass">
          <div class="preview-card__top">
            <div class="preview-utility-icon" v-html="activeUtilityIcon"></div>
            <div class="preview-status-badge" :class="form.status === 'active' ? 'preview-status-badge--active' : 'preview-status-badge--inactive'">
              <span class="preview-status-dot"></span>
              {{ form.status === 'active' ? 'Active' : 'Inactive' }}
            </div>
          </div>

          <div class="preview-number">
            {{ form.meter_number || 'MTR-?????' }}
          </div>
          <div class="preview-serial">{{ form.serial_number || 'No serial' }}</div>

          <div class="preview-divider"></div>

          <div class="preview-grid">
            <div class="preview-cell">
              <p class="preview-cell__label">Utility</p>
              <p class="preview-cell__value">{{ activeUtilityLabel || '—' }}</p>
            </div>
            <div class="preview-cell">
              <p class="preview-cell__label">Type</p>
              <p class="preview-cell__value">{{ form.meter_type ? form.meter_type.charAt(0).toUpperCase() + form.meter_type.slice(1) : '—' }}</p>
            </div>
            <div class="preview-cell">
              <p class="preview-cell__label">Building</p>
              <p class="preview-cell__value">{{ activeBuildingName || '—' }}</p>
            </div>
            <div class="preview-cell">
              <p class="preview-cell__label">Unit</p>
              <p class="preview-cell__value">{{ form.measurement_unit?.toUpperCase() || '—' }}</p>
            </div>
            <div class="preview-cell">
              <p class="preview-cell__label">Initial</p>
              <p class="preview-cell__value preview-cell__value--mono">{{ form.initial_reading != null ? form.initial_reading : '0' }}</p>
            </div>
            <div class="preview-cell">
              <p class="preview-cell__label">Multiplier</p>
              <p class="preview-cell__value preview-cell__value--mono">× {{ form.multiplier_factor || 1 }}</p>
            </div>
          </div>

          <div class="preview-divider"></div>

          <!-- Feature flags -->
          <div class="preview-flags">
            <span :class="['preview-flag', { 'preview-flag--on': form.is_shared }]">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>
              Shared
            </span>
            <span :class="['preview-flag', { 'preview-flag--on': form.supports_remote_reading }]">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="2"/><path d="M16.24 7.76a6 6 0 0 1 0 8.49m-8.48-.01a6 6 0 0 1 0-8.49"/></svg>
              Remote
            </span>
            <span :class="['preview-flag', { 'preview-flag--warn': form.maintenance_required }]">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
              Maintenance
            </span>
          </div>

          <!-- Location -->
          <div v-if="form.location_description" class="preview-location">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="10" r="3"/><path d="M12 21.7C17.3 17 20 13 20 10a8 8 0 1 0-16 0c0 3 2.7 6.9 8 11.7z"/>
            </svg>
            {{ form.location_description }}
          </div>

          <!-- Installation info -->
          <div v-if="form.installation_date" class="preview-install">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            Installed {{ formatDate(form.installation_date) }}
          </div>

        </div>

        <!-- Required fields checklist -->
        <div class="checklist-card">
          <p class="checklist-title">Required Fields</p>
          <div class="checklist">
            <div v-for="field in requiredChecklist" :key="field.key" :class="['check-item', { 'check-item--done': field.filled }]">
              <svg v-if="field.filled" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
              <svg v-else width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/></svg>
              {{ field.label }}
            </div>
          </div>
        </div>

      </aside>

    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { ErpDateInput } from '@/components/erp'

/* ── Inline sub-component ───────────────────────────────────── */
const FieldError = {
  props: ['error'],
  template: `
    <Transition name="err-pop">
      <p v-if="error" class="field-error-msg">
        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        {{ Array.isArray(error) ? error[0] : error }}
      </p>
    </Transition>
  `
}

/* ── Router / State ─────────────────────────────────────────── */
const router          = useRouter()
const processing      = ref(false)
const errors          = ref({})
const globalError     = ref('')
const apartmentsLoading = ref(false)

const buildings   = ref([])
const apartments  = ref([])
const tenants     = ref([])

/* ── Static config ──────────────────────────────────────────── */
const utilityTypes = [
  { value: 'electricity', label: 'Electricity', icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>' },
  { value: 'water',       label: 'Water',       icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>' },
  { value: 'gas',         label: 'Gas',         icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"/></svg>' },
  { value: 'solar',       label: 'Solar',       icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>' },
  { value: 'chilled_water', label: 'Chilled',  icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="2" x2="12" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>' },
  { value: 'steam',       label: 'Steam',       icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 8c0-2 2-2 2-4"/><path d="M12 8c0-2 2-2 2-4"/><path d="M16 8c0-2 2-2 2-4"/><path d="M4 12h16"/><path d="M4 16h16"/><path d="M4 20h16"/></svg>' },
  { value: 'internet',    label: 'Internet',    icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="2"/><path d="M16.24 7.76a6 6 0 0 1 0 8.49m-8.48-.01a6 6 0 0 1 0-8.49m11.31-2.82a10 10 0 0 1 0 14.14m-14.14 0a10 10 0 0 1 0-14.14"/></svg>' },
]

const meterTypes = [
  { value: 'analog',  label: 'Analog',  sub: 'Dial/rotating',  icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>' },
  { value: 'digital', label: 'Digital', sub: 'LCD/LED display', icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>' },
  { value: 'smart',   label: 'Smart',   sub: 'IoT / remote',   icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/></svg>' },
]

const ownershipTypes = [
  { value: 'apartment', label: 'Apartment', icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>' },
  { value: 'building',  label: 'Building',  icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>' },
  { value: 'shared',    label: 'Shared',    icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>' },
  { value: 'tenant',    label: 'Tenant',    icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>' },
]

const allMeasurementUnits = [
  { value: 'kwh',    label: 'kWh',    desc: 'Kilowatt-hour',  for: ['electricity', 'solar'] },
  { value: 'm3',     label: 'm³',     desc: 'Cubic meter',    for: ['water', 'gas', 'chilled_water', 'steam'] },
  { value: 'liter',  label: 'L',      desc: 'Liter',          for: ['water', 'chilled_water'] },
  { value: 'gallon', label: 'Gal',    desc: 'Gallon',         for: ['water'] },
  { value: 'mbps',   label: 'Mbps',   desc: 'Megabits/sec',   for: ['internet'] },
]

/* ── Form State ─────────────────────────────────────────────── */
const form = reactive({
  meter_number: '', serial_number: '',
  utility_type: '', meter_type: '', measurement_unit: '',
  building_id: '', apartment_id: '', ownership_type: '', tenant_id: '',
  initial_reading: 0, current_reading: 0, multiplier_factor: 1,
  installation_date: '', inspection_due_date: '',
  is_shared: false, supports_remote_reading: false, maintenance_required: false,
  status: 'active',
  notes: '', manufacturer: '', model_number: '', location_description: '',
})

/* ── Computed ───────────────────────────────────────────────── */
const filteredMeasurementUnits = computed(() => {
  if (!form.utility_type) return allMeasurementUnits
  return allMeasurementUnits.filter(u => u.for.includes(form.utility_type))
})

const activeUtility = computed(() => utilityTypes.find(u => u.value === form.utility_type))
const activeUtilityLabel = computed(() => activeUtility.value?.label || '')
const activeUtilityIcon  = computed(() => activeUtility.value?.icon || '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>')

const utilityThemeClass = computed(() => form.utility_type ? `preview-card--${form.utility_type}` : '')

const activeBuildingName = computed(() => {
  const b = buildings.value.find(b => b.id === form.building_id)
  return b?.name || ''
})

const isInspectionOverdue = computed(() => {
  if (!form.inspection_due_date) return false
  return new Date(form.inspection_due_date) < new Date()
})

const requiredChecklist = computed(() => [
  { key: 'meter_number',   label: 'Meter Number',    filled: !!form.meter_number },
  { key: 'utility_type',   label: 'Utility Type',    filled: !!form.utility_type },
  { key: 'meter_type',     label: 'Meter Type',      filled: !!form.meter_type },
  { key: 'building_id',    label: 'Building',         filled: !!form.building_id },
  { key: 'ownership_type', label: 'Ownership Type',  filled: !!form.ownership_type },
  { key: 'measurement_unit', label: 'Measurement Unit', filled: !!form.measurement_unit },
])

const completionScore = computed(() => {
  const filled = requiredChecklist.value.filter(f => f.filled).length
  return Math.round((filled / requiredChecklist.value.length) * 100)
})

/* ── Watchers ───────────────────────────────────────────────── */
watch(() => form.building_id, async (val) => {
  form.apartment_id = ''
  if (!val) { apartments.value = []; return }
  await fetchApartments()
})

watch(() => form.ownership_type, (val) => {
  if (val !== 'tenant') form.tenant_id = ''
})

watch(() => form.utility_type, (val) => {
  // Auto-select the most common unit for the utility
  const units = allMeasurementUnits.filter(u => u.for.includes(val))
  if (units.length === 1) form.measurement_unit = units[0].value
  else if (form.measurement_unit && !units.find(u => u.value === form.measurement_unit)) {
    form.measurement_unit = ''
  }
})

/* ── API calls ──────────────────────────────────────────────── */
const fetchBuildings = async () => {
  try {
    const response = await api.get('/buildings')
    buildings.value = response.data.data || []
  } catch (e) { console.error('Error fetching buildings:', e) }
}

const fetchTenants = async () => {
  try {
    const response = await api.get('/tenants')
    tenants.value = response.data.data || []
  } catch (e) { console.error('Error fetching tenants:', e) }
}

const fetchApartments = async () => {
  apartmentsLoading.value = true
  try {
    const response = await api.get('/apartments', { params: { building_id: form.building_id } })
    apartments.value = response.data.data || []
  } catch (e) { console.error('Error fetching apartments:', e) }
  finally { apartmentsLoading.value = false }
}

/* ── Validation ─────────────────────────────────────────────── */
const validateField = (field) => {
  if (field === 'meter_number' && !form.meter_number) {
    errors.value.meter_number = ['Meter number is required']
  } else if (field === 'meter_number') {
    delete errors.value.meter_number
  }
}

/* ── Submit ─────────────────────────────────────────────────── */
const submitForm = async () => {
  processing.value = true
  errors.value     = {}
  globalError.value = ''

  try {
    const initialReadingNum = Number(form.initial_reading) || 0
    const multiplierNum     = Number(form.multiplier_factor) || 1

    const payload = Object.keys(form).reduce((acc, key) => {
      acc[key] = form[key] === '' ? null : form[key]
      return acc
    }, {})

    payload.initial_reading  = initialReadingNum
    payload.current_reading  = initialReadingNum
    payload.multiplier_factor = multiplierNum
    payload.tenant_id = payload.ownership_type === 'tenant' ? payload.tenant_id : null

    await api.post('/meters', payload)
    router.push({ name: 'Meters' })

  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
      window.scrollTo({ top: 0, behavior: 'smooth' })
    } else {
      globalError.value = error.response?.data?.message || 'Registration failed. Please try again.'
    }
  } finally {
    processing.value = false
  }
}

/* ── Helpers ────────────────────────────────────────────────── */
const formatDate = (d) => {
  if (!d) return ''
  try { return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) }
  catch { return d }
}

onMounted(() => {
  fetchBuildings()
  fetchTenants()
})
</script>

<style scoped>
/* ════════════════════════════════════════════════════════════
   Tokens
════════════════════════════════════════════════════════════ */
.meter-create {
  --accent:      #4f46e5;
  --accent-lt:   #eef2ff;
  --accent-md:   #818cf8;
  --accent-ring: rgba(79,70,229,.14);

  --border:   #e5e7eb;
  --surface:  #ffffff;
  --app-bg:   #f4f6f9;

  --text-1: #111827;
  --text-2: #4b5563;
  --text-3: #9ca3af;

  --green:  #059669; --green-lt: #d1fae5;
  --amber:  #d97706; --amber-lt: #fef3c7;
  --red:    #dc2626; --red-lt:   #fee2e2;
  --blue:   #2563eb; --blue-lt:  #eff6ff;
  --cyan:   #0891b2; --cyan-lt:  #ecfeff;
  --purple: #7c3aed; --purple-lt:#f5f3ff;
  --orange: #ea580c; --orange-lt:#fff7ed;

  --r-sm:  7px; --r-md: 10px; --r-lg: 14px;
  --r-xl:  18px; --r-2xl: 22px;

  --shadow-card: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px -1px rgba(0,0,0,.04);
  --shadow-md:   0 4px 12px rgba(0,0,0,.08);

  --ease: 150ms cubic-bezier(.4,0,.2,1);
  --spring: 280ms cubic-bezier(.34,1.26,.64,1);

  font-family: 'DM Sans', ui-sans-serif, system-ui, sans-serif;
  background: var(--app-bg);
  min-height: 100%;
  padding: 24px 28px 48px;
}

/* ════════════════════════════════════════════════════════════
   Page Header
════════════════════════════════════════════════════════════ */
.page-header {
  display: flex;
  align-items: center;
  margin-bottom: 24px;
}
.page-header__left {
  display: flex; align-items: center; gap: 8px;
  font-size: 13px;
}
.back-btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 7px 12px;
  background: var(--surface); border: 1px solid var(--border);
  border-radius: var(--r-md); color: var(--text-2);
  font-size: 13px; font-weight: 500; text-decoration: none;
  transition: background var(--ease), color var(--ease);
}
.back-btn:hover { background: #f9fafb; color: var(--text-1); }
.breadcrumb-sep { color: var(--text-3); }
.breadcrumb-current { font-size: 13px; font-weight: 600; color: var(--text-1); }

/* ════════════════════════════════════════════════════════════
   Two-column layout
════════════════════════════════════════════════════════════ */
.create-layout {
  display: grid;
  grid-template-columns: 1fr 300px;
  gap: 24px;
  align-items: flex-start;
}
@media (max-width: 1100px) { .create-layout { grid-template-columns: 1fr; } }

.form-column { display: flex; flex-direction: column; gap: 20px; }

/* ════════════════════════════════════════════════════════════
   Global Error
════════════════════════════════════════════════════════════ */
.global-error {
  display: flex; align-items: center; gap: 10px;
  padding: 12px 16px;
  background: var(--red-lt); border: 1px solid #fecaca;
  border-radius: var(--r-lg); color: var(--red);
  font-size: 13.5px; font-weight: 500;
}
.global-error__close {
  margin-left: auto; background: none; border: none;
  color: var(--red); font-size: 18px; cursor: pointer; line-height: 1;
}
.err-slide-enter-active { transition: opacity .2s ease, transform .2s ease; }
.err-slide-leave-active { transition: opacity .15s ease; }
.err-slide-enter-from   { opacity: 0; transform: translateY(-6px); }
.err-slide-leave-to     { opacity: 0; }

/* ════════════════════════════════════════════════════════════
   Form Card
════════════════════════════════════════════════════════════ */
.form-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--r-2xl);
  box-shadow: var(--shadow-card);
  overflow: hidden;
}
.form-card__header {
  display: flex; align-items: center; gap: 14px;
  padding: 20px 24px;
  border-bottom: 1px solid #f3f4f6;
  background: #fafafa;
}
.section-icon {
  width: 36px; height: 36px; border-radius: var(--r-md);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.section-icon--indigo  { background: var(--accent-lt); color: var(--accent); }
.section-icon--blue    { background: var(--blue-lt);   color: var(--blue); }
.section-icon--emerald { background: var(--green-lt);  color: var(--green); }
.section-icon--slate   { background: #f1f5f9; color: #475569; }

.section-title { font-size: 14.5px; font-weight: 700; color: var(--text-1); }
.section-sub   { font-size: 12px; color: var(--text-3); margin-top: 1px; }
.section-step  {
  margin-left: auto; font-size: 11px; font-weight: 700;
  color: var(--text-3); letter-spacing: .1em;
  background: #f1f5f9; padding: 3px 8px; border-radius: 99px;
}

/* ════════════════════════════════════════════════════════════
   Field Grid
════════════════════════════════════════════════════════════ */
.field-grid {
  display: grid;
  gap: 18px 20px;
  padding: 22px 24px;
}
.field-grid--4 { grid-template-columns: 1fr 1fr 1fr 1fr; }
.field-group { display: flex; flex-direction: column; }
.field-group--span2 { grid-column: span 2; }
@media (max-width: 900px) {
  .field-grid--4 { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 600px) {
  .field-grid--4 { grid-template-columns: 1fr; }
  .field-group--span2 { grid-column: span 1; }
}

/* ════════════════════════════════════════════════════════════
   Labels
════════════════════════════════════════════════════════════ */
.field-label {
  display: flex; align-items: center; gap: 5px;
  font-size: 12px; font-weight: 600; color: var(--text-2);
  margin-bottom: 7px;
}
.req { color: var(--red); font-size: 13px; }
.field-label__hint  { font-weight: 400; color: var(--text-3); font-size: 11px; }
.field-label__badge {
  font-size: 10px; font-weight: 600;
  background: var(--amber-lt); color: var(--amber);
  padding: 2px 7px; border-radius: 99px; margin-left: 4px;
}

/* ════════════════════════════════════════════════════════════
   Input Wrap
════════════════════════════════════════════════════════════ */
.input-wrap {
  position: relative; display: flex; align-items: center;
  border: 1px solid var(--border); border-radius: var(--r-md);
  background: var(--surface); box-shadow: var(--shadow-card);
  transition: border-color var(--ease), box-shadow var(--ease);
}
.input-wrap:focus-within {
  border-color: var(--accent-md);
  box-shadow: 0 0 0 3px var(--accent-ring);
}
.input-wrap--error { border-color: var(--red); }
.input-wrap--error:focus-within { box-shadow: 0 0 0 3px rgba(220,38,38,.1); border-color: var(--red); }
.input-wrap--valid { border-color: #6ee7b7; }
.input-wrap--warn  { border-color: var(--amber); }

.input-icon {
  position: absolute; left: 12px;
  color: var(--text-3); pointer-events: none; flex-shrink: 0;
}
.input-valid-mark {
  position: absolute; right: 10px;
  color: var(--green); display: flex;
}

.field-input {
  width: 100%; height: 40px; border: none; outline: none;
  background: transparent; padding: 0 12px 0 36px;
  font-size: 13.5px; color: var(--text-1); font-family: inherit;
}
.field-input::placeholder { color: var(--text-3); }
.field-input--mono { font-family: 'DM Mono', ui-monospace, monospace; font-size: 13px; }

/* Select wrap */
.select-wrap {
  position: relative;
  border: 1px solid var(--border); border-radius: var(--r-md);
  background: var(--surface); box-shadow: var(--shadow-card);
  transition: border-color var(--ease), box-shadow var(--ease);
}
.select-wrap:focus-within {
  border-color: var(--accent-md);
  box-shadow: 0 0 0 3px var(--accent-ring);
}
.select-wrap--error   { border-color: var(--red); }
.select-wrap--disabled { opacity: .5; }

.field-select {
  width: 100%; height: 40px; border: none; outline: none;
  background: transparent; padding: 0 34px 0 36px;
  font-size: 13.5px; color: var(--text-1); font-family: inherit;
  appearance: none; cursor: pointer;
}
.select-chevron {
  position: absolute; right: 11px; top: 50%; transform: translateY(-50%);
  color: var(--text-3); pointer-events: none;
}
.select-loading {
  position: absolute; right: 11px; top: 50%; transform: translateY(-50%);
  width: 14px; height: 14px; border: 2px solid #e5e7eb;
  border-top-color: var(--accent); border-radius: 50%;
  animation: spin .6s linear infinite;
}

/* Textarea */
.field-textarea {
  width: 100%; border: none; outline: none; resize: vertical;
  padding: 14px 24px; font-size: 13.5px; color: var(--text-1);
  font-family: inherit; line-height: 1.65; min-height: 100px;
  background: transparent;
}
.field-textarea::placeholder { color: var(--text-3); }
.textarea-footer {
  display: flex; justify-content: flex-end;
  padding: 6px 24px 16px; border-top: 1px solid #f3f4f6;
}
.char-count { font-size: 11.5px; color: var(--text-3); }
.char-count--near { color: var(--amber); }

/* Field errors */
.field-error-msg {
  display: flex; align-items: center; gap: 5px;
  font-size: 11.5px; color: var(--red); margin-top: 5px;
}
.field-hint { font-size: 11.5px; margin-top: 5px; }
.field-hint--info { color: var(--blue); }

.err-pop-enter-active { transition: opacity .2s ease, transform .15s ease; }
.err-pop-leave-active { transition: opacity .1s ease; }
.err-pop-enter-from   { opacity: 0; transform: translateY(-3px); }
.err-pop-leave-to     { opacity: 0; }

/* ════════════════════════════════════════════════════════════
   Utility Picker
════════════════════════════════════════════════════════════ */
.utility-picker {
  display: flex; flex-wrap: wrap; gap: 7px;
}
.utility-chip {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 6px 12px;
  border: 1.5px solid var(--border);
  border-radius: 99px;
  background: var(--surface);
  font-size: 12.5px; font-weight: 600; color: var(--text-2);
  cursor: pointer; transition: all var(--ease);
  white-space: nowrap;
}
.utility-chip:hover { border-color: #9ca3af; }
.utility-chip__icon { display: flex; }

.utility-chip--electricity.utility-chip--active { border-color: #f59e0b; background: #fffbeb; color: #92400e; }
.utility-chip--water.utility-chip--active       { border-color: #38bdf8; background: #e0f2fe; color: #075985; }
.utility-chip--gas.utility-chip--active         { border-color: #fb923c; background: var(--orange-lt); color: #9a3412; }
.utility-chip--solar.utility-chip--active       { border-color: #fbbf24; background: #fefce8; color: #854d0e; }
.utility-chip--chilled_water.utility-chip--active { border-color: #67e8f9; background: var(--cyan-lt); color: #164e63; }
.utility-chip--steam.utility-chip--active       { border-color: #d8b4fe; background: var(--purple-lt); color: #5b21b6; }
.utility-chip--internet.utility-chip--active    { border-color: var(--accent-md); background: var(--accent-lt); color: var(--accent); }

/* ════════════════════════════════════════════════════════════
   Meter Type Selector
════════════════════════════════════════════════════════════ */
.type-selector {
  display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px;
}
.type-option {
  display: flex; flex-direction: column; align-items: center;
  gap: 5px; padding: 12px 8px;
  border: 1.5px solid var(--border); border-radius: var(--r-lg);
  background: var(--surface); cursor: pointer;
  text-align: center; font-family: inherit;
  transition: all var(--ease);
}
.type-option:hover { border-color: #9ca3af; background: #f9fafb; }
.type-option--active {
  border-color: var(--accent); background: var(--accent-lt);
  box-shadow: 0 0 0 3px var(--accent-ring);
}
.type-option__icon { color: var(--text-3); margin-bottom: 2px; }
.type-option--active .type-option__icon { color: var(--accent); }
.type-option__name { font-size: 12.5px; font-weight: 700; color: var(--text-1); }
.type-option__sub  { font-size: 10.5px; color: var(--text-3); }

/* ════════════════════════════════════════════════════════════
   Ownership Grid
════════════════════════════════════════════════════════════ */
.ownership-grid {
  display: grid; grid-template-columns: 1fr 1fr; gap: 8px;
}
.ownership-option {
  display: flex; align-items: center; gap: 8px;
  padding: 10px 14px;
  border: 1.5px solid var(--border); border-radius: var(--r-md);
  background: var(--surface); cursor: pointer; font-family: inherit;
  font-size: 13px; font-weight: 600; color: var(--text-2);
  transition: all var(--ease);
}
.ownership-option:hover { border-color: #9ca3af; }
.ownership-option--active {
  border-color: var(--accent); background: var(--accent-lt); color: var(--accent);
}
.ownership-option__icon { display: flex; }

/* ════════════════════════════════════════════════════════════
   Unit Selector
════════════════════════════════════════════════════════════ */
.unit-selector { display: flex; flex-wrap: wrap; gap: 7px; }
.unit-chip {
  display: flex; flex-direction: column; align-items: center;
  padding: 8px 14px;
  border: 1.5px solid var(--border); border-radius: var(--r-md);
  background: var(--surface); cursor: pointer; font-family: inherit;
  transition: all var(--ease);
}
.unit-chip--active { border-color: var(--accent); background: var(--accent-lt); }
.unit-chip__value  { font-size: 14px; font-weight: 800; color: var(--text-1); font-family: 'DM Mono', ui-monospace, monospace; }
.unit-chip--active .unit-chip__value { color: var(--accent); }
.unit-chip__desc   { font-size: 10px; color: var(--text-3); margin-top: 1px; white-space: nowrap; }

/* ════════════════════════════════════════════════════════════
   Toggles Row
════════════════════════════════════════════════════════════ */
.toggles-row {
  display: flex; align-items: flex-start; gap: 24px; flex-wrap: wrap;
  padding: 16px 24px 20px;
  border-top: 1px solid #f3f4f6;
  background: #fafafa;
}
.toggles-row__label {
  font-size: 10.5px; font-weight: 700; letter-spacing: .07em;
  text-transform: uppercase; color: var(--text-3);
  display: block; margin-bottom: 10px;
}

.status-toggle-group { display: flex; flex-direction: column; }
.status-toggle { display: flex; border: 1px solid var(--border); border-radius: var(--r-md); overflow: hidden; }
.status-btn {
  display: flex; align-items: center; gap: 7px;
  padding: 8px 16px; font-size: 12.5px; font-weight: 600;
  color: var(--text-3); background: var(--surface);
  border: none; cursor: pointer; font-family: inherit;
  transition: background var(--ease), color var(--ease);
}
.status-btn + .status-btn { border-left: 1px solid var(--border); }
.status-btn:hover { background: #f3f4f6; }
.status-btn__dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; opacity: .3; }
.status-btn--active-green { color: var(--green); background: var(--green-lt); }
.status-btn--active-green .status-btn__dot { opacity: 1; }
.status-btn--active-red   { color: var(--red);   background: var(--red-lt); }
.status-btn--active-red   .status-btn__dot { opacity: 1; }

.feature-toggles { display: flex; flex-direction: column; gap: 6px; flex: 1; }

.toggle-pill {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 7px 12px;
  border: 1px solid var(--border); border-radius: 99px;
  background: var(--surface); cursor: pointer; font-family: inherit;
  font-size: 12.5px; font-weight: 600; color: var(--text-3);
  transition: all var(--ease); user-select: none;
  width: fit-content;
}
.toggle-pill:hover { border-color: #9ca3af; color: var(--text-2); }
.toggle-pill--on { border-color: var(--accent-md); color: var(--accent); background: var(--accent-lt); }
.toggle-pill--warn { border-color: #fcd34d; color: var(--amber); background: var(--amber-lt); }
.toggle-pill__check { display: none; }

.toggle-switch {
  width: 28px; height: 16px; border-radius: 99px;
  background: #d1d5db; position: relative;
  flex-shrink: 0; transition: background var(--ease);
}
.toggle-switch--on { background: var(--accent); }
.toggle-switch--warn { background: var(--amber) !important; }
.toggle-knob {
  position: absolute; top: 2px; left: 2px;
  width: 12px; height: 12px; border-radius: 50%;
  background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.15);
  transition: transform var(--ease);
}
.toggle-switch--on .toggle-knob { transform: translateX(12px); }

/* Tenant slide in */
.slide-in-enter-active { transition: opacity .25s ease, transform .25s var(--spring), max-height .25s ease; }
.slide-in-leave-active { transition: opacity .15s ease, transform .15s ease; }
.slide-in-enter-from   { opacity: 0; transform: translateY(-8px); }
.slide-in-leave-to     { opacity: 0; transform: translateY(-4px); }

/* Overdue badge */
.overdue-badge {
  font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em;
  background: var(--red-lt); color: var(--red);
  padding: 2px 7px; border-radius: 99px;
}

/* ════════════════════════════════════════════════════════════
   Action Bar
════════════════════════════════════════════════════════════ */
.action-bar {
  display: flex; align-items: center; justify-content: space-between; gap: 16px;
  background: rgba(255,255,255,.9); backdrop-filter: blur(10px);
  border: 1px solid var(--border); border-radius: var(--r-2xl);
  padding: 14px 20px; box-shadow: var(--shadow-md);
  position: sticky; bottom: 20px;
}
.action-bar__left { display: flex; align-items: center; gap: 10px; }
.completion-hint  { display: flex; align-items: center; gap: 10px; font-size: 12.5px; color: var(--text-3); }
.completion-bar   { width: 80px; height: 5px; background: #e5e7eb; border-radius: 99px; overflow: hidden; }
.completion-fill  { height: 100%; background: var(--accent); border-radius: 99px; transition: width .4s var(--spring); }
.completion-ready { display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: var(--green); }
.action-bar__actions { display: flex; align-items: center; gap: 10px; }

.btn-ghost {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 9px 18px; background: transparent; color: var(--text-2);
  font-size: 13.5px; font-weight: 500; border-radius: var(--r-lg);
  border: 1px solid var(--border); cursor: pointer; text-decoration: none;
  font-family: inherit; transition: background var(--ease), color var(--ease);
}
.btn-ghost:hover { background: #f9fafb; color: var(--text-1); }

.btn-submit {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 10px 24px; background: var(--accent); color: #fff;
  font-size: 13.5px; font-weight: 700; border-radius: var(--r-lg);
  border: none; cursor: pointer; font-family: inherit;
  transition: background var(--ease), transform var(--ease), box-shadow var(--ease);
  box-shadow: 0 2px 10px rgba(79,70,229,.3);
}
.btn-submit:hover:not(:disabled) {
  background: #4338ca; transform: translateY(-1px);
  box-shadow: 0 4px 16px rgba(79,70,229,.4);
}
.btn-submit:disabled { opacity: .6; cursor: not-allowed; transform: none; }
.btn-spinner { animation: spin .7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* ════════════════════════════════════════════════════════════
   Preview Card
════════════════════════════════════════════════════════════ */
.preview-column { display: flex; flex-direction: column; gap: 16px; }
@media (max-width: 1100px) { .preview-column { order: -1; } }

.preview-card {
  background: #0f172a;
  border-radius: var(--r-2xl);
  padding: 24px;
  color: #f1f5f9;
  box-shadow: 0 8px 32px rgba(0,0,0,.18);
  transition: background .3s ease;
  position: sticky; top: 20px;
}

/* Utility color themes for preview card */
.preview-card--electricity { background: linear-gradient(135deg, #1c1400 0%, #78350f 100%); }
.preview-card--water       { background: linear-gradient(135deg, #0c1a26 0%, #0c4a6e 100%); }
.preview-card--gas         { background: linear-gradient(135deg, #1c0e00 0%, #7c2d12 100%); }
.preview-card--solar       { background: linear-gradient(135deg, #1c1a00 0%, #713f12 100%); }
.preview-card--chilled_water { background: linear-gradient(135deg, #001c1f 0%, #164e63 100%); }
.preview-card--steam       { background: linear-gradient(135deg, #0f0017 0%, #4c1d95 100%); }
.preview-card--internet    { background: linear-gradient(135deg, #0a0015 0%, #312e81 100%); }

.preview-card__top {
  display: flex; align-items: flex-start; justify-content: space-between;
  margin-bottom: 16px;
}
.preview-utility-icon {
  width: 44px; height: 44px; border-radius: var(--r-lg);
  background: rgba(255,255,255,.12);
  display: flex; align-items: center; justify-content: center;
  color: rgba(255,255,255,.8);
}
.preview-status-badge {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 4px 10px; border-radius: 99px;
  font-size: 11.5px; font-weight: 600;
}
.preview-status-badge--active   { background: rgba(16,185,129,.2); color: #6ee7b7; }
.preview-status-badge--inactive { background: rgba(239,68,68,.2);  color: #fca5a5; }
.preview-status-dot {
  width: 6px; height: 6px; border-radius: 50%; background: currentColor;
}

.preview-number {
  font-size: 22px; font-weight: 800; letter-spacing: -.01em;
  color: #fff; font-family: 'DM Mono', ui-monospace, monospace;
  margin-bottom: 4px;
}
.preview-serial { font-size: 12px; color: rgba(255,255,255,.4); margin-bottom: 16px; }
.preview-divider { height: 1px; background: rgba(255,255,255,.08); margin: 16px 0; }

.preview-grid {
  display: grid; grid-template-columns: 1fr 1fr; gap: 12px 16px;
}
.preview-cell__label { font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: .07em; color: rgba(255,255,255,.35); margin-bottom: 3px; }
.preview-cell__value { font-size: 13.5px; font-weight: 600; color: rgba(255,255,255,.85); }
.preview-cell__value--mono { font-family: 'DM Mono', ui-monospace, monospace; font-size: 13px; }

.preview-flags { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px; }
.preview-flag {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 3px 9px; border-radius: 99px;
  font-size: 11px; font-weight: 600;
  background: rgba(255,255,255,.07); color: rgba(255,255,255,.3);
  transition: all var(--ease);
}
.preview-flag--on   { background: rgba(99,102,241,.25); color: #a5b4fc; }
.preview-flag--warn { background: rgba(245,158,11,.25); color: #fcd34d; }

.preview-location, .preview-install {
  display: flex; align-items: flex-start; gap: 7px;
  font-size: 11.5px; color: rgba(255,255,255,.45);
  margin-top: 10px; line-height: 1.5;
}
.preview-location svg, .preview-install svg { flex-shrink: 0; margin-top: 1px; }

/* ════════════════════════════════════════════════════════════
   Checklist Card
════════════════════════════════════════════════════════════ */
.checklist-card {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: var(--r-xl); padding: 18px 20px;
  box-shadow: var(--shadow-card);
}
.checklist-title {
  font-size: 11px; font-weight: 700; text-transform: uppercase;
  letter-spacing: .08em; color: var(--text-3); margin-bottom: 12px;
}
.checklist { display: flex; flex-direction: column; gap: 8px; }
.check-item {
  display: flex; align-items: center; gap: 9px;
  font-size: 13px; font-weight: 500; color: var(--text-3);
  transition: color var(--ease);
}
.check-item--done { color: var(--green); }
</style>