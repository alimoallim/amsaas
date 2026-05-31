<template>
  
    <div class="te-root">

      <!-- Fonts -->
      <component :is="'style'">
        @import url('https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap');
        .te-root, .te-root * { box-sizing: border-box; font-family: 'DM Sans', sans-serif; }
        .te-display { font-family: 'Syne', sans-serif; }
        @keyframes te-fadeUp  { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
        @keyframes te-spin    { to { transform: rotate(360deg); } }
        @keyframes te-slideIn { from { opacity:0; transform:translateX(-8px); } to { opacity:1; transform:translateX(0); } }
        .te-tab-content { animation: te-slideIn .2s ease both; }
      </component>

      <!-- ═══════════════════ PAGE HEADER ═══════════════════ -->
      <div class="te-header">
        <div class="te-header-left">
          <RouterLink :to="{ name: 'TenantsIndex' }" class="te-back-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round"><polyline points="15 18 9 12 15 6"/></svg>
            Tenants
          </RouterLink>
          <div class="te-eyebrow">Editing Record</div>
          <div class="te-title-row">
            <h1 class="te-title te-display">Edit Tenant</h1>
            <span v-if="!fetchLoading && form.first_name" :class="['te-status-badge', `te-status-badge--${form.status}`]">
              <span class="te-status-dot"></span>{{ form.status }}
            </span>
          </div>
          <p class="te-subtitle">
            <span v-if="fetchLoading">Loading tenant data…</span>
            <span v-else-if="form.first_name">
              Updating <strong>{{ [form.first_name, form.last_name].filter(Boolean).join(' ') }}</strong>
            </span>
            <span v-else>Update tenant identity record on the ERP platform</span>
          </p>
        </div>

        <!-- Progress -->
        <div class="te-progress-wrap">
          <div class="te-progress-label">Section {{ activeTab + 1 }} of {{ tabs.length }}</div>
          <div class="te-progress-track">
            <div class="te-progress-fill" :style="{ width: ((activeTab + 1) / tabs.length * 100) + '%' }"></div>
          </div>
          <div class="te-progress-tabs">
            <button
              v-for="(tab, i) in tabs"
              :key="i"
              @click="activeTab = i"
              :class="['te-progress-dot', activeTab === i && 'te-progress-dot--active', i < activeTab && 'te-progress-dot--done']"
              :title="tab.label"
            >
              <svg v-if="i < activeTab" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
            </button>
          </div>
        </div>
      </div>

      <!-- ═══════════════════ ALERTS ═══════════════════ -->
      <Transition name="te-alert">
        <div v-if="fetchError" class="te-alert te-alert--warn">
          <div class="te-alert-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          </div>
          <div>
            <p class="te-alert-title">Failed to load tenant</p>
            <p class="te-alert-body">{{ fetchError }}</p>
          </div>
          <button @click="loadTenant" class="te-btn-ghost" style="margin-left:auto">Retry</button>
        </div>
      </Transition>

      <Transition name="te-alert">
        <div v-if="hasErrors" class="te-alert">
          <div class="te-alert-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          </div>
          <div>
            <p class="te-alert-title">Validation errors found</p>
            <p class="te-alert-body">{{ Object.keys(errors).length }} field(s) need attention.</p>
          </div>
          <button @click="errors = {}" class="te-alert-close">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
      </Transition>

      <!-- ═══════════════════ FETCH LOADING ═══════════════════ -->
      <div v-if="fetchLoading" class="te-fetch-loading">
        <div class="te-spinner-lg"></div>
        <p>Loading tenant record…</p>
      </div>

      <!-- ═══════════════════ MAIN LAYOUT ═══════════════════ -->
      <div v-else class="te-layout">

        <!-- ── Left Sidebar Nav ── -->
        <aside class="te-sidebar">
          <nav class="te-nav">
            <button
              v-for="(tab, i) in tabs"
              :key="i"
              @click="activeTab = i"
              :class="['te-nav-item', activeTab === i && 'te-nav-item--active', tabHasError(i) && 'te-nav-item--error']"
            >
              <div :class="['te-nav-icon', activeTab === i && 'te-nav-icon--active']">
                <component :is="'span'" v-html="tab.icon"></component>
              </div>
              <div class="te-nav-text">
                <span class="te-nav-label">{{ tab.label }}</span>
                <span class="te-nav-count">{{ tab.fields }} fields</span>
              </div>
              <div v-if="tabHasError(i)" class="te-nav-err-dot"></div>
              <svg v-if="activeTab === i" class="te-nav-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
          </nav>

          <!-- Tenant Summary Card -->
          <div class="te-summary-card">
            <div class="te-summary-avatar">{{ previewInitials }}</div>
            <div>
              <p class="te-summary-name">{{ previewName || 'Tenant Name' }}</p>
              <p class="te-summary-type">{{ form.tenant_type || 'individual' }}</p>
              <span :class="['te-summary-status', `te-summary-status--${form.status}`]">
                <span class="te-summary-dot"></span>{{ form.status }}
              </span>
            </div>
          </div>

          <!-- Danger Zone -->
          <div class="te-danger-zone">
            <p class="te-danger-title">Danger Zone</p>
            <button type="button" @click="confirmDelete" class="te-btn-danger">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
              Delete Tenant
            </button>
          </div>
        </aside>

        <!-- ── Form ── -->
        <form @submit.prevent="submitUpdate" class="te-form-wrap">

          <!-- ── TAB 0: Identity ── -->
          <div v-show="activeTab === 0" class="te-tab-content">
            <div class="te-section-header">
              <div class="te-section-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              </div>
              <div>
                <h2 class="te-section-title te-display">Identity Information</h2>
                <p class="te-section-desc">Core tenant profile and classification details</p>
              </div>
            </div>

            <div class="te-grid te-grid--4">

              <div class="te-field">
                <label class="te-label">Tenant Type <span class="te-req">*</span></label>
                <div class="te-select-wrap">
                  <select v-model="form.tenant_type" class="te-select">
                    <option value="individual">Individual</option>
                    <option value="company">Company</option>
                    <option value="government">Government</option>
                    <option value="ngo">NGO</option>
                  </select>
                  <svg class="te-select-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
              </div>

              <div class="te-field">
                <label class="te-label">Status <span class="te-req">*</span></label>
                <div class="te-select-wrap">
                  <select v-model="form.status" :class="['te-select', `te-select--${form.status}`]">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="pending">Pending</option>
                    <option value="blacklisted">Blacklisted</option>
                  </select>
                  <svg class="te-select-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
              </div>

              <div class="te-field">
                <label class="te-label">Nationality</label>
                <input v-model="form.nationality" type="text" class="te-input" placeholder="e.g. Somali" />
              </div>

              <div class="te-field">
                <label class="te-label">Gender</label>
                <div class="te-select-wrap">
                  <select v-model="form.gender" class="te-select">
                    <option value="">Select gender</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                  </select>
                  <svg class="te-select-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
              </div>

              <div class="te-field" :class="{ 'te-field--error': errors.first_name }">
                <label class="te-label">First Name <span class="te-req">*</span></label>
                <input v-model="form.first_name" type="text" :class="['te-input', errors.first_name && 'te-input--error']" placeholder="Given name" />
                <p v-if="errors.first_name" class="te-err-msg">{{ errors.first_name[0] }}</p>
              </div>

              <div class="te-field">
                <label class="te-label">Middle Name</label>
                <input v-model="form.middle_name" type="text" class="te-input" placeholder="Optional" />
              </div>

              <div class="te-field" :class="{ 'te-field--error': errors.last_name }">
                <label class="te-label">Last Name <span class="te-req">*</span></label>
                <input v-model="form.last_name" type="text" :class="['te-input', errors.last_name && 'te-input--error']" placeholder="Family name" />
                <p v-if="errors.last_name" class="te-err-msg">{{ errors.last_name[0] }}</p>
              </div>

              <div class="te-field">
                <label class="te-label">Date of Birth</label>
                <input v-model="form.date_of_birth" type="date" class="te-input" />
              </div>

              <div class="te-field te-field--span2">
                <label class="te-label">Display Name</label>
                <input v-model="form.display_name" type="text" class="te-input" placeholder="Visible name in ERP platform" />
              </div>

              <div class="te-field te-field--span2">
                <label class="te-label">Company / Organisation</label>
                <input v-model="form.company_name" type="text" class="te-input" placeholder="For corporate tenants only" />
              </div>

              <div class="te-field te-field--span4">
                <label class="te-label">Occupation</label>
                <input v-model="form.occupation" type="text" class="te-input" placeholder="e.g. Software Engineer, Business Owner" />
              </div>

            </div>
          </div>

          <!-- ── TAB 1: Contact ── -->
          <div v-show="activeTab === 1" class="te-tab-content">
            <div class="te-section-header">
              <div class="te-section-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81a19.79 19.79 0 01-3.07-8.72A2 2 0 012 1h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 8.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
              </div>
              <div>
                <h2 class="te-section-title te-display">Contact Information</h2>
                <p class="te-section-desc">Communication channels and reachability</p>
              </div>
            </div>

            <div class="te-grid te-grid--3">
              <div class="te-field" :class="{ 'te-field--error': errors.email }">
                <label class="te-label">Email Address <span class="te-req">*</span></label>
                <div class="te-input-icon-wrap">
                  <svg class="te-input-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                  <input v-model="form.email" type="email" :class="['te-input te-input--icon', errors.email && 'te-input--error']" placeholder="tenant@example.com" />
                </div>
                <p v-if="errors.email" class="te-err-msg">{{ errors.email[0] }}</p>
              </div>
              <div class="te-field" :class="{ 'te-field--error': errors.phone }">
                <label class="te-label">Primary Phone <span class="te-req">*</span></label>
                <div class="te-input-icon-wrap">
                  <svg class="te-input-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81a19.79 19.79 0 01-3.07-8.72A2 2 0 012 1h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 8.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
                  <input v-model="form.phone" type="text" :class="['te-input te-input--icon', errors.phone && 'te-input--error']" placeholder="+252 61 000 0000" />
                </div>
                <p v-if="errors.phone" class="te-err-msg">{{ errors.phone[0] }}</p>
              </div>
              <div class="te-field">
                <label class="te-label">Alternate Phone</label>
                <div class="te-input-icon-wrap">
                  <svg class="te-input-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81a19.79 19.79 0 01-3.07-8.72A2 2 0 012 1h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 8.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
                  <input v-model="form.alternate_phone" type="text" class="te-input te-input--icon" placeholder="Secondary number" />
                </div>
              </div>
            </div>

            <div class="te-subsection">
              <div class="te-subsection-header">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                Emergency Contact
              </div>
              <div class="te-grid te-grid--3">
                <div class="te-field">
                  <label class="te-label">Contact Name</label>
                  <input v-model="form.emergency_contact_name" type="text" class="te-input" placeholder="Full name" />
                </div>
                <div class="te-field">
                  <label class="te-label">Phone</label>
                  <input v-model="form.emergency_contact_phone" type="text" class="te-input" placeholder="+252 61 000 0000" />
                </div>
                <div class="te-field">
                  <label class="te-label">Relationship</label>
                  <input v-model="form.emergency_contact_relationship" type="text" class="te-input" placeholder="e.g. Spouse, Parent" />
                </div>
              </div>
            </div>
          </div>

          <!-- ── TAB 2: Legal ── -->
          <div v-show="activeTab === 2" class="te-tab-content">
            <div class="te-section-header">
              <div class="te-section-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
              </div>
              <div>
                <h2 class="te-section-title te-display">Legal & Government</h2>
                <p class="te-section-desc">Official identification and tax documentation</p>
              </div>
            </div>
            <div class="te-legal-notice">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              Handle all legal identifiers with strict confidentiality. Store only what is operationally necessary.
            </div>
            <div class="te-grid te-grid--3">
              <div class="te-field">
                <label class="te-label">National ID</label>
                <div class="te-input-icon-wrap">
                  <svg class="te-input-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                  <input v-model="form.national_id" type="text" class="te-input te-input--icon" placeholder="National identification number" />
                </div>
              </div>
              <div class="te-field">
                <label class="te-label">Passport Number</label>
                <div class="te-input-icon-wrap">
                  <svg class="te-input-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                  <input v-model="form.passport_number" type="text" class="te-input te-input--icon" placeholder="Passport document number" />
                </div>
              </div>
              <div class="te-field">
                <label class="te-label">Tax Number</label>
                <div class="te-input-icon-wrap">
                  <svg class="te-input-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                  <input v-model="form.tax_number" type="text" class="te-input te-input--icon" placeholder="Tax identification number" />
                </div>
              </div>
            </div>
          </div>

          <!-- ── TAB 3: Address ── -->
          <div v-show="activeTab === 3" class="te-tab-content">
            <div class="te-section-header">
              <div class="te-section-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
              </div>
              <div>
                <h2 class="te-section-title te-display">Address Information</h2>
                <p class="te-section-desc">Residential and mailing address details</p>
              </div>
            </div>
            <div class="te-grid te-grid--4">
              <div class="te-field te-field--span2">
                <label class="te-label">Country</label>
                <div class="te-input-icon-wrap">
                  <svg class="te-input-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>
                  <input v-model="form.country" type="text" class="te-input te-input--icon" placeholder="Country" />
                </div>
              </div>
              <div class="te-field">
                <label class="te-label">City</label>
                <input v-model="form.city" type="text" class="te-input" placeholder="City or district" />
              </div>
              <div class="te-field">
                <label class="te-label">Postal Code</label>
                <input v-model="form.postal_code" type="text" class="te-input" placeholder="ZIP / Postal" />
              </div>
              <div class="te-field te-field--span4">
                <label class="te-label">Street Address</label>
                <textarea v-model="form.address" rows="4" class="te-input te-textarea" placeholder="Full street address, building, apartment…"></textarea>
              </div>
            </div>
          </div>

          <!-- ── TAB 4: Notes ── -->
          <div v-show="activeTab === 4" class="te-tab-content">
            <div class="te-section-header">
              <div class="te-section-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/></svg>
              </div>
              <div>
                <h2 class="te-section-title te-display">Internal Notes</h2>
                <p class="te-section-desc">Private staff notes — not visible to the tenant</p>
              </div>
            </div>
            <div class="te-grid te-grid--1">
              <div class="te-field">
                <label class="te-label">Notes</label>
                <textarea v-model="form.notes" rows="10" class="te-input te-textarea" placeholder="Internal notes about this tenant…"></textarea>
                <p class="te-hint">Only visible to authorised staff. Not shared with the tenant.</p>
              </div>
            </div>
          </div>

          <!-- ── Form Footer ── -->
          <div class="te-form-footer">
            <button v-if="activeTab > 0" type="button" @click="activeTab--" class="te-btn-ghost">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><polyline points="15 18 9 12 15 6"/></svg>
              Previous
            </button>

            <div class="te-footer-right">
              <span class="te-footer-hint">{{ tabs[activeTab].label }} — {{ activeTab + 1 }}/{{ tabs.length }}</span>

              <button
                v-if="activeTab < tabs.length - 1"
                type="button"
                @click="activeTab++"
                class="te-btn-primary"
              >
                Next: {{ tabs[activeTab + 1]?.label }}
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><polyline points="9 18 15 12 9 6"/></svg>
              </button>

              <button
                v-else
                type="submit"
                :disabled="loading"
                class="te-btn-submit"
              >
                <span v-if="loading" class="te-spinner"></span>
                <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><polyline points="20 6 9 17 4 12"/></svg>
                {{ loading ? 'Saving Changes…' : 'Save Changes' }}
              </button>
            </div>
          </div>

        </form>
      </div>

      <!-- ═══════════════════ DELETE CONFIRM MODAL ═══════════════════ -->
      <Teleport to="body">
        <Transition name="te-modal">
          <div v-if="showDeleteModal" class="te-backdrop" @click.self="showDeleteModal = false">
            <div class="te-modal">
              <div class="te-modal-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
              </div>
              <h3 class="te-modal-title te-display">Delete Tenant?</h3>
              <p class="te-modal-body">
                This will permanently delete <strong>{{ previewName }}</strong> and all associated records. This action cannot be undone.
              </p>
              <div class="te-modal-actions">
                <button @click="showDeleteModal = false" class="te-btn-ghost">Cancel</button>
                <button @click="deleteTenant" :disabled="deleteLoading" class="te-btn-delete">
                  <span v-if="deleteLoading" class="te-spinner"></span>
                  <svg v-else width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg>
                  {{ deleteLoading ? 'Deleting…' : 'Yes, Delete' }}
                </button>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>

    </div>

    <!-- ═══════════════════ STYLES ═══════════════════ -->
    <component :is="'style'" scoped>
      .te-root {
        --c-bg:         #f3f2f9;
        --c-surface:    #ffffff;
        --c-border:     #e5e2f5;
        --c-border2:    #cfc9ef;
        --c-text:       #18152e;
        --c-muted:      #7870a0;
        --c-accent:     #5b4ce8;
        --c-accent-h:   #4a3dd6;
        --c-accent-bg:  #f0eeff;
        --c-green:      #12b374;
        --c-green-bg:   #edfaf4;
        --c-amber:      #d97706;
        --c-amber-bg:   #fffbeb;
        --c-red:        #dc2626;
        --c-red-bg:     #fff1f2;
        --c-shadow:     0 2px 8px rgba(30,20,80,.07), 0 1px 2px rgba(30,20,80,.04);
        --c-shadow-lg:  0 20px 60px rgba(30,20,80,.18);
        min-height: 100vh;
        background: var(--c-bg);
        padding: 28px;
        animation: te-fadeUp .25s ease both;
      }

      /* Header */
      .te-header {
        display: flex; align-items: flex-start; justify-content: space-between;
        gap: 20px; margin-bottom: 24px; flex-wrap: wrap;
      }
      .te-back-link {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 13px; color: var(--c-muted); text-decoration: none; margin-bottom: 10px;
        transition: color .15s;
      }
      .te-back-link:hover { color: var(--c-accent); }
      .te-eyebrow {
        font-size: 11px; font-weight: 600; letter-spacing: .12em;
        text-transform: uppercase; color: var(--c-amber); margin-bottom: 5px;
      }
      .te-title-row { display: flex; align-items: center; gap: 12px; margin-bottom: 4px; }
      .te-title { font-size: 30px; font-weight: 800; letter-spacing: -.02em; color: var(--c-text); margin: 0; line-height: 1; }
      .te-subtitle { font-size: 13px; color: var(--c-muted); }

      .te-status-badge {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 4px 12px; border-radius: 99px; font-size: 12px; font-weight: 600;
        text-transform: capitalize;
      }
      .te-status-badge--active      { background: var(--c-green-bg); color: var(--c-green); }
      .te-status-badge--inactive    { background: var(--c-border); color: var(--c-muted); }
      .te-status-badge--pending     { background: var(--c-amber-bg); color: var(--c-amber); }
      .te-status-badge--blacklisted { background: var(--c-red-bg); color: var(--c-red); }
      .te-status-dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; }

      /* Progress */
      .te-progress-wrap {
        min-width: 240px; max-width: 300px; flex: 0 0 auto;
        background: var(--c-surface); border: 1.5px solid var(--c-border);
        border-radius: 18px; padding: 16px 18px; box-shadow: var(--c-shadow);
      }
      .te-progress-label { font-size: 11px; color: var(--c-muted); font-weight: 500; margin-bottom: 8px; }
      .te-progress-track { height: 5px; background: var(--c-border); border-radius: 99px; overflow: hidden; margin-bottom: 10px; }
      .te-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--c-amber), #f59e0b);
        border-radius: 99px; transition: width .35s cubic-bezier(.4,0,.2,1);
      }
      .te-progress-tabs { display: flex; gap: 6px; }
      .te-progress-dot {
        width: 22px; height: 22px; border-radius: 50%;
        border: 2px solid var(--c-border2); background: var(--c-surface);
        cursor: pointer; transition: all .2s;
        display: flex; align-items: center; justify-content: center;
        color: transparent;
      }
      .te-progress-dot--active { border-color: var(--c-amber); background: var(--c-amber-bg); }
      .te-progress-dot--done   { border-color: var(--c-green); background: var(--c-green-bg); color: var(--c-green); }

      /* Fetch Loading */
      .te-fetch-loading {
        display: flex; flex-direction: column; align-items: center;
        justify-content: center; padding: 100px 20px; gap: 16px;
        color: var(--c-muted); font-size: 14px;
      }
      .te-spinner-lg {
        width: 48px; height: 48px;
        border: 3px solid var(--c-border);
        border-top-color: var(--c-accent);
        border-radius: 50%;
        animation: te-spin .8s linear infinite;
      }

      /* Alerts */
      .te-alert {
        display: flex; align-items: center; gap: 12px;
        background: var(--c-red-bg); border: 1.5px solid #fecaca;
        border-radius: 16px; padding: 14px 16px; margin-bottom: 20px;
      }
      .te-alert--warn { background: var(--c-amber-bg); border-color: #fde68a; }
      .te-alert-icon {
        width: 36px; height: 36px; background: #fee2e2; color: var(--c-red);
        border-radius: 10px; display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
      }
      .te-alert--warn .te-alert-icon { background: #fef3c7; color: var(--c-amber); }
      .te-alert-title { font-size: 13px; font-weight: 600; color: var(--c-red); }
      .te-alert--warn .te-alert-title { color: var(--c-amber); }
      .te-alert-body  { font-size: 12px; color: #b91c1c; margin-top: 1px; }
      .te-alert--warn .te-alert-body { color: #92400e; }
      .te-alert-close {
        margin-left: auto; width: 28px; height: 28px;
        background: none; border: none; color: #b91c1c; cursor: pointer;
        border-radius: 8px; display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; transition: background .15s;
      }
      .te-alert-close:hover { background: #fee2e2; }
      .te-alert-enter-active, .te-alert-leave-active { transition: all .2s ease; }
      .te-alert-enter-from, .te-alert-leave-to { opacity: 0; transform: translateY(-8px); }

      /* Layout */
      .te-layout { display: grid; grid-template-columns: 220px 1fr; gap: 18px; align-items: start; }
      @media (max-width: 900px) {
        .te-layout { grid-template-columns: 1fr; }
        .te-sidebar { flex-direction: row; overflow-x: auto; }
      }

      /* Sidebar */
      .te-sidebar { position: sticky; top: 20px; display: flex; flex-direction: column; gap: 10px; }
      .te-nav { background: var(--c-surface); border: 1.5px solid var(--c-border); border-radius: 20px; overflow: hidden; box-shadow: var(--c-shadow); }
      .te-nav-item {
        width: 100%; display: flex; align-items: center; gap: 10px;
        padding: 12px 14px; border: none; background: none; cursor: pointer;
        text-align: left; transition: background .15s;
        border-bottom: 1px solid var(--c-border); position: relative;
      }
      .te-nav-item:last-child { border-bottom: none; }
      .te-nav-item:hover { background: var(--c-accent-bg); }
      .te-nav-item--active { background: var(--c-accent-bg); }
      .te-nav-item--error  { background: var(--c-red-bg); }
      .te-nav-icon {
        width: 32px; height: 32px; border-radius: 10px;
        background: var(--c-bg); color: var(--c-muted);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; transition: all .15s;
      }
      .te-nav-icon--active { background: var(--c-accent); color: #fff; }
      .te-nav-text { flex: 1; min-width: 0; }
      .te-nav-label { display: block; font-size: 12.5px; font-weight: 600; color: var(--c-text); white-space: nowrap; }
      .te-nav-count { display: block; font-size: 10px; color: var(--c-muted); margin-top: 1px; }
      .te-nav-err-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--c-red); }
      .te-nav-arrow { color: var(--c-accent); flex-shrink: 0; }

      /* Summary Card */
      .te-summary-card {
        background: var(--c-surface); border: 1.5px solid var(--c-border);
        border-radius: 18px; padding: 14px; box-shadow: var(--c-shadow);
        display: flex; align-items: center; gap: 12px;
      }
      .te-summary-avatar {
        width: 44px; height: 44px; border-radius: 14px;
        background: linear-gradient(135deg, var(--c-accent-bg), #e0d9ff);
        color: var(--c-accent); display: flex; align-items: center; justify-content: center;
        font-size: 15px; font-weight: 700; flex-shrink: 0;
        font-family: 'Syne', sans-serif;
        border: 1.5px solid var(--c-border);
      }
      .te-summary-name { font-size: 12px; font-weight: 600; color: var(--c-text); }
      .te-summary-type { font-size: 11px; color: var(--c-muted); margin-top: 1px; text-transform: capitalize; }
      .te-summary-status {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 11px; font-weight: 500; padding: 2px 8px;
        border-radius: 99px; margin-top: 5px; text-transform: capitalize;
      }
      .te-summary-status--active      { background: var(--c-green-bg); color: var(--c-green); }
      .te-summary-status--inactive    { background: var(--c-border); color: var(--c-muted); }
      .te-summary-status--pending     { background: var(--c-amber-bg); color: var(--c-amber); }
      .te-summary-status--blacklisted { background: var(--c-red-bg); color: var(--c-red); }
      .te-summary-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

      /* Danger Zone */
      .te-danger-zone {
        background: var(--c-surface); border: 1.5px solid #fecaca;
        border-radius: 18px; padding: 14px; box-shadow: var(--c-shadow);
      }
      .te-danger-title { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: var(--c-red); margin-bottom: 10px; }
      .te-btn-danger {
        width: 100%; display: flex; align-items: center; justify-content: center; gap: 7px;
        padding: 9px 14px; border-radius: 10px;
        border: 1.5px solid #fecaca; background: var(--c-red-bg);
        color: var(--c-red); font-size: 13px; font-weight: 600;
        cursor: pointer; transition: all .15s; font-family: 'DM Sans', sans-serif;
      }
      .te-btn-danger:hover { background: var(--c-red); color: #fff; border-color: var(--c-red); }

      /* Form Wrap */
      .te-form-wrap {
        background: var(--c-surface); border: 1.5px solid var(--c-border);
        border-radius: 24px; overflow: hidden; box-shadow: var(--c-shadow);
      }

      /* Section Header */
      .te-section-header {
        display: flex; align-items: center; gap: 14px;
        padding: 20px 24px; border-bottom: 1.5px solid var(--c-border); background: #faf9ff;
      }
      .te-section-icon {
        width: 44px; height: 44px; border-radius: 14px;
        background: var(--c-accent-bg); color: var(--c-accent);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; border: 1.5px solid var(--c-border);
      }
      .te-section-title { font-size: 20px; font-weight: 700; color: var(--c-text); margin: 0; letter-spacing: -.01em; }
      .te-section-desc  { font-size: 12.5px; color: var(--c-muted); margin-top: 3px; }

      /* Subsection */
      .te-subsection { margin: 0 24px 20px; border: 1.5px solid var(--c-border); border-radius: 16px; overflow: hidden; }
      .te-subsection-header {
        display: flex; align-items: center; gap: 8px;
        padding: 10px 16px; font-size: 12px; font-weight: 600;
        color: var(--c-muted); background: var(--c-bg);
        border-bottom: 1px solid var(--c-border);
        text-transform: uppercase; letter-spacing: .06em;
      }
      .te-subsection .te-grid { padding: 16px; margin: 0; }

      /* Grids */
      .te-grid { display: grid; gap: 16px; padding: 20px 24px; }
      .te-grid--1 { grid-template-columns: 1fr; }
      .te-grid--3 { grid-template-columns: repeat(3, 1fr); }
      .te-grid--4 { grid-template-columns: repeat(4, 1fr); }
      @media (max-width: 1100px) { .te-grid--4 { grid-template-columns: repeat(2, 1fr); } }
      @media (max-width: 720px)  { .te-grid--3, .te-grid--4 { grid-template-columns: 1fr; } }
      .te-field--span2 { grid-column: span 2; }
      .te-field--span4 { grid-column: span 4; }
      @media (max-width: 1100px) { .te-field--span4 { grid-column: span 2; } }
      @media (max-width: 720px)  { .te-field--span2, .te-field--span4 { grid-column: span 1; } }

      /* Fields */
      .te-field { display: flex; flex-direction: column; }
      .te-field--error .te-label { color: var(--c-red); }
      .te-label { font-size: 12px; font-weight: 600; color: var(--c-text); margin-bottom: 6px; letter-spacing: .01em; }
      .te-req { color: var(--c-red); margin-left: 2px; }

      .te-input {
        width: 100%; padding: 10px 13px; border-radius: 12px;
        border: 1.5px solid var(--c-border); background: var(--c-bg);
        font-size: 14px; color: var(--c-text); outline: none;
        transition: border-color .15s, box-shadow .15s, background .15s;
        font-family: 'DM Sans', sans-serif; -webkit-appearance: none;
      }
      .te-input::placeholder { color: var(--c-muted); font-weight: 300; }
      .te-input:focus { border-color: var(--c-accent); box-shadow: 0 0 0 3px rgba(91,76,232,.1); background: #fff; }
      .te-input--error { border-color: var(--c-red); background: var(--c-red-bg); }
      .te-textarea { resize: vertical; min-height: 100px; }
      .te-input--icon { padding-left: 38px; }
      .te-input-icon-wrap { position: relative; }
      .te-input-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--c-muted); pointer-events: none; }

      .te-select-wrap { position: relative; }
      .te-select {
        width: 100%; padding: 10px 36px 10px 13px; border-radius: 12px;
        border: 1.5px solid var(--c-border); background: var(--c-bg);
        font-size: 14px; color: var(--c-text); outline: none;
        -webkit-appearance: none; appearance: none; cursor: pointer;
        transition: border-color .15s, box-shadow .15s;
        font-family: 'DM Sans', sans-serif;
      }
      .te-select:focus { border-color: var(--c-accent); box-shadow: 0 0 0 3px rgba(91,76,232,.1); background: #fff; }
      .te-select--active      { color: var(--c-green); }
      .te-select--inactive    { color: var(--c-muted); }
      .te-select--pending     { color: var(--c-amber); }
      .te-select--blacklisted { color: var(--c-red); }
      .te-select-arrow { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: var(--c-muted); pointer-events: none; }

      .te-err-msg { font-size: 11px; color: var(--c-red); margin-top: 4px; font-weight: 500; }
      .te-hint    { font-size: 11px; color: var(--c-muted); margin-top: 4px; }

      /* Legal notice */
      .te-legal-notice {
        display: flex; align-items: center; gap: 8px;
        margin: 0 24px 4px; padding: 10px 14px;
        background: var(--c-amber-bg); border: 1px solid #fde68a;
        border-radius: 12px; font-size: 12px; color: var(--c-amber); font-weight: 500;
      }

      /* Footer */
      .te-form-footer {
        display: flex; align-items: center; justify-content: space-between;
        padding: 16px 24px; border-top: 1.5px solid var(--c-border); background: #faf9ff; gap: 12px;
      }
      .te-footer-right { display: flex; align-items: center; gap: 12px; }
      .te-footer-hint  { font-size: 12px; color: var(--c-muted); }

      /* Buttons */
      .te-btn-ghost {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 10px 18px; border-radius: 12px;
        border: 1.5px solid var(--c-border2); background: var(--c-surface);
        color: var(--c-text); font-size: 14px; font-weight: 500;
        cursor: pointer; transition: all .15s; font-family: 'DM Sans', sans-serif;
        text-decoration: none;
      }
      .te-btn-ghost:hover { border-color: var(--c-accent); background: var(--c-accent-bg); color: var(--c-accent); }

      .te-btn-primary {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 10px 20px; border-radius: 12px;
        border: none; background: var(--c-accent); color: #fff;
        font-size: 14px; font-weight: 600; cursor: pointer; transition: all .15s;
        box-shadow: 0 4px 12px rgba(91,76,232,.3); font-family: 'DM Sans', sans-serif;
      }
      .te-btn-primary:hover { background: var(--c-accent-h); transform: translateY(-1px); }

      .te-btn-submit {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 11px 22px; border-radius: 12px;
        border: none; background: linear-gradient(135deg, #d97706, #f59e0b);
        color: #fff; font-size: 14px; font-weight: 700;
        cursor: pointer; transition: all .15s;
        box-shadow: 0 4px 14px rgba(217,119,6,.35);
        font-family: 'Syne', sans-serif; letter-spacing: .01em;
      }
      .te-btn-submit:hover { transform: translateY(-1px); box-shadow: 0 8px 22px rgba(217,119,6,.45); }
      .te-btn-submit:disabled { opacity: .6; cursor: not-allowed; transform: none; }

      .te-spinner {
        width: 16px; height: 16px;
        border: 2px solid rgba(255,255,255,.3);
        border-top-color: #fff; border-radius: 50%;
        animation: te-spin .7s linear infinite; display: inline-block;
      }

      /* Delete Modal */
      .te-backdrop {
        position: fixed; inset: 0; z-index: 1000;
        background: rgba(20,16,48,.55); backdrop-filter: blur(6px);
        display: flex; align-items: center; justify-content: center; padding: 20px;
      }
      .te-modal {
        background: var(--c-surface); border-radius: 24px;
        padding: 32px; max-width: 420px; width: 100%;
        box-shadow: var(--c-shadow-lg);
        display: flex; flex-direction: column; align-items: center; text-align: center; gap: 12px;
      }
      .te-modal-icon {
        width: 64px; height: 64px; border-radius: 20px;
        background: var(--c-red-bg); color: var(--c-red);
        display: flex; align-items: center; justify-content: center;
        border: 2px solid #fecaca;
      }
      .te-modal-title { font-size: 22px; font-weight: 800; color: var(--c-text); margin: 0; }
      .te-modal-body  { font-size: 14px; color: var(--c-muted); line-height: 1.6; }
      .te-modal-actions { display: flex; gap: 10px; margin-top: 8px; }
      .te-btn-delete {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 10px 20px; border-radius: 12px;
        border: none; background: var(--c-red); color: #fff;
        font-size: 14px; font-weight: 600; cursor: pointer;
        transition: all .15s; box-shadow: 0 4px 12px rgba(220,38,38,.3);
        font-family: 'DM Sans', sans-serif;
      }
      .te-btn-delete:hover { background: #b91c1c; }
      .te-btn-delete:disabled { opacity: .6; cursor: not-allowed; }

      .te-modal-enter-active, .te-modal-leave-active { transition: opacity .2s; }
      .te-modal-enter-from, .te-modal-leave-to { opacity: 0; }
    </component>

  
</template>

<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import { useRouter, useRoute, RouterLink } from 'vue-router'
import api from '../../services/api'
import DashboardLayout from '../../layouts/DashboardLayout.vue'

const router = useRouter()
const route  = useRoute()

const tenantId = computed(() => route.params.id)

const loading      = ref(false)
const fetchLoading = ref(true)
const fetchError   = ref(null)
const deleteLoading = ref(false)
const showDeleteModal = ref(false)
const errors  = ref({})
const activeTab = ref(0)

const tabs = [
  { label: 'Identity', fields: 11, icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>' },
  { label: 'Contact',  fields:  6, icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81a19.79 19.79 0 01-3.07-8.72A2 2 0 012 1h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 8.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>' },
  { label: 'Legal',    fields:  3, icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>' },
  { label: 'Address',  fields:  4, icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>' },
  { label: 'Notes',    fields:  1, icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/></svg>' },
]

const tabFieldMap = [
  ['first_name','last_name','display_name'],
  ['email','phone'],
  ['national_id','passport_number','tax_number'],
  ['country','city','address'],
  ['notes'],
]
const tabHasError = (i) => tabFieldMap[i].some(f => errors.value[f])
const hasErrors = computed(() => Object.keys(errors.value).length > 0)

const form = reactive({
  tenant_type: 'individual', status: 'active',
  first_name: '', middle_name: '', last_name: '',
  display_name: '', company_name: '',
  email: '', phone: '', alternate_phone: '',
  national_id: '', passport_number: '', tax_number: '',
  nationality: '', date_of_birth: '', gender: '', occupation: '',
  country: '', city: '', address: '', postal_code: '',
  emergency_contact_name: '', emergency_contact_phone: '',
  emergency_contact_relationship: '',
  notes: '',
})

const previewInitials = computed(() => {
  const f = form.first_name?.[0] || ''
  const l = form.last_name?.[0]  || ''
  return (f + l).toUpperCase() || '?'
})
const previewName = computed(() =>
  [form.first_name, form.last_name].filter(Boolean).join(' ')
)

// Load tenant
/*
|--------------------------------------------------------------------------
| Load Tenant
|--------------------------------------------------------------------------
|
| Hydrate nested API resource into flat ERP form state
|
*/

const loadTenant = async () => {

  fetchLoading.value = true

  fetchError.value = null

  try {

    const response =
      await api.get(

        `/tenants/${tenantId.value}`
      )

    const data =
      response.data.data || response.data

    /*
    |--------------------------------------------------------------------------
    | Core
    |--------------------------------------------------------------------------
    */

    form.tenant_type =
      data.tenant_type || 'individual'

    form.status =
      data.status?.value || 'active'

    form.display_name =
      data.display_name || ''

    form.company_name =
      data.company_name || ''

    /*
    |--------------------------------------------------------------------------
    | Name
    |--------------------------------------------------------------------------
    */

    form.first_name =
      data.name?.first_name || ''

    form.middle_name =
      data.name?.middle_name || ''

    form.last_name =
      data.name?.last_name || ''

    /*
    |--------------------------------------------------------------------------
    | Contact
    |--------------------------------------------------------------------------
    */

    form.email =
      data.contact?.email || ''

    form.phone =
      data.contact?.phone || ''

    form.alternate_phone =
      data.contact?.alternate_phone || ''

    /*
    |--------------------------------------------------------------------------
    | Identity
    |--------------------------------------------------------------------------
    */

    form.national_id =
      data.identity?.national_id || ''

    form.passport_number =
      data.identity?.passport_number || ''

    form.tax_number =
      data.identity?.tax_number || ''

    form.nationality =
      data.identity?.nationality || ''

    form.date_of_birth =
      data.identity?.date_of_birth || ''

    form.gender =
      data.identity?.gender || ''

    form.occupation =
      data.identity?.occupation || ''

    /*
    |--------------------------------------------------------------------------
    | Address
    |--------------------------------------------------------------------------
    */

    form.country =
      data.address?.country || ''

    form.city =
      data.address?.city || ''

    form.address =
      data.address?.address || ''

    form.postal_code =
      data.address?.postal_code || ''

    /*
    |--------------------------------------------------------------------------
    | Emergency Contact
    |--------------------------------------------------------------------------
    */

    form.emergency_contact_name =
      data.emergency_contact?.name || ''

    form.emergency_contact_phone =
      data.emergency_contact?.phone || ''

    form.emergency_contact_relationship =
      data.emergency_contact?.relationship || ''

    /*
    |--------------------------------------------------------------------------
    | Notes
    |--------------------------------------------------------------------------
    */

    form.notes =
      data.notes || ''

  } catch (err) {

    console.error(err)

    fetchError.value =

      err.response?.data?.message
      || 'Unable to load tenant record.'

  } finally {

    fetchLoading.value = false
  }
}

// Submit update
const submitUpdate = async () => {
  loading.value = true
  errors.value  = {}
  try {
    await api.put(`/tenants/${tenantId.value}`, { ...form })
    router.push({ name: 'TenantsIndex' })
  } catch (error) {
    console.error(error)
    console.log(error.response?.data)
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
      const errTab = tabFieldMap.findIndex(fields => fields.some(f => errors.value[f]))
      if (errTab !== -1) activeTab.value = errTab
    }
  } finally {
    loading.value = false
  }
}

// Delete
const confirmDelete = () => { showDeleteModal.value = true }

const deleteTenant = async () => {
  deleteLoading.value = true
  try {
    await api.delete(`/tenants/${tenantId.value}`)
    router.push({ name: 'TenantsIndex' })
  } catch (err) {
    console.error(err)
    alert('Failed to delete tenant. Please try again.')
  } finally {
    deleteLoading.value = false
    showDeleteModal.value = false
  }
}

onMounted(() => {
  loadTenant()
})
</script>