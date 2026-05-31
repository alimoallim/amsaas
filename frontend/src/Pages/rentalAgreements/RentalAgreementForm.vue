<template>
  <form class="agreement-form" @submit.prevent="handleSubmit">

    <!-- ══════════════════════════════════════════════════════════
         FORM HEADER
    ══════════════════════════════════════════════════════════ -->
    <div class="form-header">
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

    <!-- ══════════════════════════════════════════════════════════
         TWO-PANEL BODY
    ══════════════════════════════════════════════════════════ -->
    <div class="form-body">

      <!-- ── Section Navigation (left) ────────────────────────── -->
      <nav class="section-nav" aria-label="Form sections">
        <div class="section-nav__inner">
          <a
            v-for="(sec, i) in sections"
            :key="sec.id"
            :href="`#sec-${sec.id}`"
            class="sec-nav-item"
            :class="{ 'sec-nav-item--active': activeSection === sec.id }"
            @click.prevent="scrollToSection(sec.id)"
          >
            <span class="sec-nav-item__num">{{ i + 1 }}</span>
            <span class="sec-nav-item__icon" v-html="sec.icon"></span>
            <span class="sec-nav-item__label">{{ sec.label }}</span>
          </a>
        </div>
      </nav>

      <!-- ── Scrollable Form Sections (right) ─────────────────── -->
      <div class="form-sections" ref="sectionsEl">

        <!-- ─── 1. Assignment ──────────────────────────────────── -->
        <section class="form-section" id="sec-assignment" data-section="assignment">
          <div class="section-header">
            <div class="section-header__icon section-header__icon--indigo">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
              </svg>
            </div>
            <div>
              <h3 class="section-header__title">Assignment</h3>
              <p class="section-header__sub">Link an apartment and tenant to this agreement</p>
            </div>
          </div>

          <div class="field-grid">
            <!-- Apartment -->
            <div class="field-group">
              <label class="field-label">
                Apartment
                <span class="field-required">*</span>
              </label>
              <div class="select-wrap">
                <svg class="select-wrap__icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>
                </svg>
                <select
                  v-model="form.apartment_id"
                  :disabled="isReadonly || !canEditCoreFields"
                  class="field-select field-select--icon"
                >
                  <option value="">Select an apartment…</option>
                  <option v-for="apartment in apartments" :key="apartment.id" :value="apartment.id">
                    {{ apartmentLabel(apartment) }}
                  </option>
                </select>
                <svg class="select-wrap__chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="6 9 12 15 18 9"/>
                </svg>
              </div>
              <p v-if="apartments.length === 0" class="field-hint field-hint--warn">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                No rental-enabled apartments available
              </p>
              <p v-if="errors.apartment_id" class="field-error">{{ errors.apartment_id[0] }}</p>
            </div>

            <!-- Tenant -->
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
                  :disabled="isReadonly || !canEditCoreFields"
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
              <p v-if="tenants.length === 0" class="field-hint field-hint--warn">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                No tenants available
              </p>
              <p v-if="errors.tenant_id" class="field-error">{{ errors.tenant_id[0] }}</p>
            </div>
          </div>
        </section>

        <!-- ─── 2. Agreement Period ────────────────────────────── -->
        <section class="form-section" id="sec-period" data-section="period">
          <div class="section-header">
            <div class="section-header__icon section-header__icon--blue">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
              </svg>
            </div>
            <div>
              <h3 class="section-header__title">Agreement Period</h3>
              <p class="section-header__sub">Set the duration and current status of this agreement</p>
            </div>
          </div>

          <!-- Date range visual -->
          <div class="date-range-card">
            <div class="date-range-card__field">
              <p class="date-range-card__label">Start Date</p>
              <input
                v-model="form.start_date"
                type="date"
                :disabled="isReadonly || !canEditCoreFields"
                class="field-date"
              />
              <p v-if="errors.start_date" class="field-error">{{ errors.start_date[0] }}</p>
            </div>
            <div class="date-range-card__arrow">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
              </svg>
            </div>
            <div class="date-range-card__field">
              <p class="date-range-card__label">End Date</p>
              <input
                v-model="form.end_date"
                type="date"
                :disabled="isReadonly"
                class="field-date"
              />
              <p v-if="errors.end_date" class="field-error">{{ errors.end_date[0] }}</p>
            </div>
          </div>

          <!-- Status -->
          <div class="field-group" style="max-width: 320px; margin-top: 16px;">
            <label class="field-label">Agreement Status</label>
            <div class="status-select-grid">
              <button
                v-for="s in statusOptions"
                :key="s.value"
                type="button"
                :disabled="isReadonly || !canEditCoreFields"
                :class="['status-option', `status-option--${s.value}`, { 'status-option--selected': form.status === s.value }]"
                @click="!isReadonly && canEditCoreFields && (form.status = s.value)"
              >
                <span class="status-option__dot"></span>
                {{ s.label }}
              </button>
            </div>
            <p v-if="errors.status" class="field-error">{{ errors.status[0] }}</p>
          </div>
        </section>

        <!-- ─── 3. Financial Terms ─────────────────────────────── -->
        <section class="form-section" id="sec-financial" data-section="financial">
          <div class="section-header">
            <div class="section-header__icon section-header__icon--emerald">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>
              </svg>
            </div>
            <div>
              <h3 class="section-header__title">Financial Terms</h3>
              <p class="section-header__sub">Monthly rent, deposit, currency, and payment schedule</p>
            </div>
          </div>

          <div class="field-grid">

            <!-- Monthly Rent -->
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

            <!-- Payment Due Day -->
            <div class="field-group">
              <label class="field-label">Payment Due Day
                <span class="field-hint-inline">of each month (1–28)</span>
              </label>
              <div class="stepper-wrap">
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
          </div>
        </section>

        <!-- ─── 4. Utilities ───────────────────────────────────── -->
        <section class="form-section" id="sec-utilities" data-section="utilities">
          <div class="section-header">
            <div class="section-header__icon section-header__icon--amber">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
              </svg>
            </div>
            <div>
              <h3 class="section-header__title">Utilities & Coverage</h3>
              <p class="section-header__sub">Select which services are included in the rent</p>
            </div>
          </div>

          <div class="utility-grid">

            <label :class="['utility-card', { 'utility-card--checked': form.includes_water, 'utility-card--disabled': isReadonly }]">
              <input v-model="form.includes_water" type="checkbox" :disabled="isReadonly" class="utility-card__check" />
              <div class="utility-card__icon utility-card__icon--blue">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                </svg>
              </div>
              <div class="utility-card__body">
                <span class="utility-card__name">Water</span>
                <span class="utility-card__sub">Included in rent</span>
              </div>
              <div class="utility-card__toggle" :class="{ 'utility-card__toggle--on': form.includes_water }">
                <span class="utility-card__toggle-knob"></span>
              </div>
            </label>

            <label :class="['utility-card', { 'utility-card--checked': form.includes_electricity, 'utility-card--disabled': isReadonly }]">
              <input v-model="form.includes_electricity" type="checkbox" :disabled="isReadonly" class="utility-card__check" />
              <div class="utility-card__icon utility-card__icon--amber">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                </svg>
              </div>
              <div class="utility-card__body">
                <span class="utility-card__name">Electricity</span>
                <span class="utility-card__sub">Included in rent</span>
              </div>
              <div class="utility-card__toggle" :class="{ 'utility-card__toggle--on': form.includes_electricity }">
                <span class="utility-card__toggle-knob"></span>
              </div>
            </label>

            <label :class="['utility-card', { 'utility-card--checked': form.includes_internet, 'utility-card--disabled': isReadonly }]">
              <input v-model="form.includes_internet" type="checkbox" :disabled="isReadonly" class="utility-card__check" />
              <div class="utility-card__icon utility-card__icon--purple">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="2"/><path d="M16.24 7.76a6 6 0 0 1 0 8.49m-8.48-.01a6 6 0 0 1 0-8.49m11.31-2.82a10 10 0 0 1 0 14.14m-14.14 0a10 10 0 0 1 0-14.14"/>
                </svg>
              </div>
              <div class="utility-card__body">
                <span class="utility-card__name">Internet</span>
                <span class="utility-card__sub">Included in rent</span>
              </div>
              <div class="utility-card__toggle" :class="{ 'utility-card__toggle--on': form.includes_internet }">
                <span class="utility-card__toggle-knob"></span>
              </div>
            </label>

          </div>
        </section>

        <!-- ─── 5. Renewal Policy ─────────────────────────────── -->
        <section class="form-section" id="sec-renewal" data-section="renewal">
          <div class="section-header">
            <div class="section-header__icon section-header__icon--teal">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/>
                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
              </svg>
            </div>
            <div>
              <h3 class="section-header__title">Renewal Policy</h3>
              <p class="section-header__sub">Configure automatic renewal and notice period</p>
            </div>
          </div>

          <div class="renewal-row">
            <!-- Auto-renew toggle card -->
            <label :class="['auto-renew-card', { 'auto-renew-card--on': form.auto_renew, 'auto-renew-card--disabled': isReadonly }]">
              <input v-model="form.auto_renew" type="checkbox" :disabled="isReadonly" class="utility-card__check" />
              <div>
                <p class="auto-renew-card__title">Auto Renewal</p>
                <p class="auto-renew-card__sub">Agreement renews automatically at expiry</p>
              </div>
              <div class="auto-renew-card__badge" :class="{ 'auto-renew-card__badge--on': form.auto_renew }">
                {{ form.auto_renew ? 'Enabled' : 'Disabled' }}
              </div>
            </label>

            <!-- Notice days -->
            <div class="field-group" style="flex: 1; min-width: 200px;">
              <label class="field-label">Renewal Notice Period
                <span class="field-hint-inline">days before expiry</span>
              </label>
              <div class="stepper-wrap">
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
          </div>
        </section>

        <!-- ─── 6. Documentation ──────────────────────────────── -->
        <section class="form-section" id="sec-docs" data-section="docs">
          <div class="section-header">
            <div class="section-header__icon section-header__icon--rose">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>
              </svg>
            </div>
            <div>
              <h3 class="section-header__title">Documentation</h3>
              <p class="section-header__sub">Attach the signed contract PDF</p>
            </div>
          </div>

          <label
            class="file-drop-zone"
            :class="{ 'file-drop-zone--disabled': isReadonly, 'file-drop-zone--has-file': !!form.contract_file }"
            @dragover.prevent
            @drop.prevent="!isReadonly && (form.contract_file = $event.dataTransfer.files[0])"
          >
            <input
              type="file"
              accept=".pdf"
              :disabled="isReadonly"
              @change="form.contract_file = $event.target.files[0]"
              class="file-drop-zone__input"
            />
            <div v-if="!form.contract_file" class="file-drop-zone__placeholder">
              <div class="file-drop-zone__icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                  <polyline points="14 2 14 8 20 8"/>
                  <line x1="12" y1="12" x2="12" y2="18"/>
                  <line x1="9" y1="15" x2="15" y2="15"/>
                </svg>
              </div>
              <p class="file-drop-zone__title">Drop PDF here or <span class="file-drop-zone__link">browse</span></p>
              <p class="file-drop-zone__hint">PDF files only · Max 10 MB</p>
            </div>
            <div v-else class="file-drop-zone__preview">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
              </svg>
              <span class="file-drop-zone__name">{{ form.contract_file?.name }}</span>
              <button type="button" class="file-drop-zone__remove" @click.prevent="form.contract_file = null">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
              </button>
            </div>
          </label>
        </section>

        <!-- ─── 7. Notes & Legal ───────────────────────────────── -->
        <section class="form-section" id="sec-notes" data-section="notes">
          <div class="section-header">
            <div class="section-header__icon section-header__icon--slate">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/>
                <line x1="8" y1="18" x2="21" y2="18"/>
                <line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>
              </svg>
            </div>
            <div>
              <h3 class="section-header__title">Notes & Legal Terms</h3>
              <p class="section-header__sub">Special clauses and internal operational notes</p>
            </div>
          </div>

          <div class="notes-grid">
            <div class="field-group">
              <label class="field-label">Special Terms & Legal Clauses</label>
              <textarea
                v-model="form.special_terms"
                rows="5"
                :disabled="isReadonly"
                placeholder="Enter any special terms, legal clauses, or conditions that apply to this agreement…"
                class="field-textarea"
              />
            </div>
            <div class="field-group">
              <label class="field-label">
                Internal Notes
                <span class="field-hint-inline">not visible to tenant</span>
              </label>
              <textarea
                v-model="form.notes"
                rows="4"
                :disabled="isReadonly"
                placeholder="Operational notes, reminders, or internal context…"
                class="field-textarea field-textarea--internal"
              />
            </div>
          </div>
        </section>

      </div>
      <!-- /form-sections -->

    </div>
    <!-- /form-body -->

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
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  form:       { type: Object,  required: true },
  errors:     { type: Object,  default: () => ({}) },
  loading:    { type: Boolean, default: false },
  mode:       { type: String,  default: 'create' },
  apartments: { type: Array,   default: () => [] },
  tenants:    { type: Array,   default: () => [] },
  initialStatus: {
  type: String,
  default: '',
},
})
const emit = defineEmits(['submit', 'cancel'])
/* ── Computed modes ──────────────────────────────────────────── */
const isReadonly = computed(() => props.mode === 'readonly')
const isEdit     = computed(() => props.mode === 'edit')
//const canEditCoreFields = computed(() => props.form?.status !== 'active')
const canEditCoreFields = computed(() => {
  return props.initialStatus !== 'active'
})
/* ── Section nav ─────────────────────────────────────────────── */
const sections = [
  { id: 'assignment', label: 'Assignment',    icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>' },
  { id: 'period',     label: 'Period',        icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>' },
  { id: 'financial',  label: 'Financial',     icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>' },
  { id: 'utilities',  label: 'Utilities',     icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>' },
  { id: 'renewal',    label: 'Renewal',       icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>' },
  { id: 'docs',       label: 'Documents',     icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>' },
  { id: 'notes',      label: 'Notes',         icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/></svg>' },
]

const activeSection = ref('assignment')
const sectionsEl    = ref(null)

const scrollToSection = (id) => {
  const el = document.getElementById(`sec-${id}`)
  if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' })
  activeSection.value = id
}

let observer = null
onMounted(() => {
  observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) activeSection.value = entry.target.dataset.section
    })
  }, { root: sectionsEl.value, threshold: 0.4 })

  document.querySelectorAll('[data-section]').forEach(el => observer.observe(el))
})
onUnmounted(() => observer?.disconnect())

/* ── Static options ──────────────────────────────────────────── */
const statusOptions = [
  { value: 'draft',      label: 'Draft'      },
  { value: 'pending',    label: 'Pending'    },
  { value: 'active',     label: 'Active'     },
  { value: 'terminated', label: 'Terminated' },
  { value: 'expired',    label: 'Expired'    },
]

const currencyOptions = ['USD', 'EUR', 'GBP', 'AED', 'SAR', 'TRY', 'KES', 'NGN', 'ZAR', 'EGP', 'SOS']

/* ── Label helpers (unchanged) ───────────────────────────────── */
function apartmentLabel(apartment) {
  const building = apartment.building?.name || ''
  const unit     = apartment.unit?.unit_number || apartment.unit_number || ''
  const floor    = apartment.unit?.floor || apartment.floor || null
  return [building, unit ? `Unit ${unit}` : null, floor ? `Floor ${floor}` : null].filter(Boolean).join(' — ')
}

function tenantLabel(tenant) {
  return (
    tenant.display_name ||
    tenant.full_display_name ||
    tenant.full_name ||
    ((tenant.name?.first_name && tenant.name?.last_name) ? `${tenant.name.first_name} ${tenant.name.last_name}` : null) ||
    tenant.name || tenant.email || tenant.tenant_code || tenant.id
  )
}

function handleSubmit() { emit('submit') }
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
  gap: 16px;
  padding: 20px 28px;
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
   Two-panel body
════════════════════════════════════════════════════════════ */
.form-body {
  display: flex;
  flex: 1;
  min-height: 0;
  overflow: hidden;
}

/* ── Section Nav ─────────────────────────────────────────────── */
.section-nav {
  width: 192px;
  flex-shrink: 0;
  border-right: 1px solid var(--border);
  background: #fafafa;
  overflow-y: auto;
}

.section-nav__inner {
  padding: 20px 12px;
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.sec-nav-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 10px;
  border-radius: var(--r-md);
  text-decoration: none;
  color: var(--text-3);
  font-size: 13px;
  font-weight: 500;
  transition: background var(--ease), color var(--ease);
  cursor: pointer;
}
.sec-nav-item:hover { background: #f0f0f0; color: var(--text-2); }
.sec-nav-item--active { background: var(--accent-lt); color: var(--accent); }

.sec-nav-item__num {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: currentColor;
  color: #fff;
  font-size: 10px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  opacity: .5;
}
.sec-nav-item--active .sec-nav-item__num { opacity: 1; }

.sec-nav-item__icon { display: flex; align-items: center; flex-shrink: 0; }
.sec-nav-item__label { white-space: nowrap; }

/* ── Form Sections (scrollable) ──────────────────────────────── */
.form-sections {
  flex: 1;
  overflow-y: auto;
  min-width: 0;
  padding: 0 0 120px; /* space for action bar */
}

/* ════════════════════════════════════════════════════════════
   Section
════════════════════════════════════════════════════════════ */
.form-section {
  padding: 28px 32px;
  border-bottom: 1px solid var(--border);
}
.form-section:last-child { border-bottom: none; }

/* Section Header */
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
  grid-template-columns: 1fr 1fr;
  gap: 18px 24px;
}

.field-group { display: flex; flex-direction: column; gap: 0; }

.field-label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12.5px;
  font-weight: 600;
  color: var(--text-2);
  margin-bottom: 7px;
}
.field-required { color: var(--red); font-size: 13px; }
.field-hint-inline { font-weight: 400; color: var(--text-3); font-size: 11.5px; }

/* Base input */
.field-input {
  height: 42px;
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
  height: 42px;
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
  height: 46px;
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
  height: 42px;
}
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
  gap: 8px;
  margin-top: 2px;
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
  grid-template-columns: 1fr 1fr;
  gap: 20px;
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
  padding: 14px 28px;
  gap: 16px;
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
   Responsive
════════════════════════════════════════════════════════════ */
@media (max-width: 1024px) {
  .section-nav { width: 160px; }
  .field-grid  { grid-template-columns: 1fr; }
  .notes-grid  { grid-template-columns: 1fr; }
  .utility-grid { grid-template-columns: 1fr 1fr; }
}

@media (max-width: 768px) {
  .section-nav     { display: none; }
  .form-header     { padding: 16px 18px; }
  .form-section    { padding: 20px 18px; }
  .action-bar__inner { padding: 12px 18px; }
  .utility-grid    { grid-template-columns: 1fr; }
  .date-range-card { flex-direction: column; gap: 14px; }
  .date-range-card__arrow { transform: rotate(90deg); margin: 0; }
  .action-bar__info-text { display: none; }
  .renewal-row { flex-direction: column; }
}

@media (max-width: 480px) {
  .form-header__sub { display: none; }
  .status-select-grid { gap: 6px; }
  .status-option { padding: 5px 10px; font-size: 11.5px; }
}
</style>