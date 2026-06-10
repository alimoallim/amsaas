<template>
  
    <div class="tc-root">

      <!-- Fonts -->
      <component :is="'style'">
        @import url('https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap');
        .tc-root, .tc-root * { box-sizing: border-box; font-family: 'DM Sans', sans-serif; }
        .tc-display { font-family: 'Syne', sans-serif; }
        @keyframes tc-fadeUp { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
        @keyframes tc-spin { to { transform: rotate(360deg); } }
        @keyframes tc-slideIn { from { opacity:0; transform:translateX(-8px); } to { opacity:1; transform:translateX(0); } }
        .tc-panel-enter { animation: tc-fadeUp .22s ease both; }
        .tc-tab-content { animation: tc-slideIn .2s ease both; }
      </component>

      <!-- ═══════════════════ PAGE HEADER ═══════════════════ -->
      <div class="tc-header">
        <div class="tc-header-left">
          <RouterLink :to="{ name: 'Tenants' }" class="tc-back-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round"><polyline points="15 18 9 12 15 6"/></svg>
            Tenants
          </RouterLink>
          <div class="tc-eyebrow">New Record</div>
          <h1 class="tc-title tc-display">Tenant Registration</h1>
          <p class="tc-subtitle">Create a complete tenant identity record for the ERP platform</p>
        </div>

        <!-- Progress Indicator -->
        <div class="tc-progress-wrap">
          <div class="tc-progress-label">Section {{ activeTab + 1 }} of {{ tabs.length }}</div>
          <div class="tc-progress-track">
            <div class="tc-progress-fill" :style="{ width: ((activeTab + 1) / tabs.length * 100) + '%' }"></div>
          </div>
          <div class="tc-progress-tabs">
            <button
              v-for="(tab, i) in tabs"
              :key="i"
              @click="activeTab = i"
              :class="['tc-progress-dot', activeTab === i && 'tc-progress-dot--active', i < activeTab && 'tc-progress-dot--done']"
              :title="tab.label"
            >
              <svg v-if="i < activeTab" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
            </button>
          </div>
        </div>
      </div>

      <!-- ═══════════════════ VALIDATION ALERT ═══════════════════ -->
      <Transition name="tc-alert">
        <div v-if="hasErrors" class="tc-alert">
          <div class="tc-alert-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          </div>
          <div>
            <p class="tc-alert-title">Validation errors found</p>
            <p class="tc-alert-body">{{ Object.keys(errors).length }} field(s) need attention. Check highlighted sections.</p>
          </div>
          <button @click="errors = {}" class="tc-alert-close">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
      </Transition>

      <!-- ═══════════════════ MAIN LAYOUT ═══════════════════ -->
      <div class="tc-layout">

        <!-- ── Left Sidebar Nav ── -->
        <aside class="tc-sidebar">
          <nav class="tc-nav">
            <button
              v-for="(tab, i) in tabs"
              :key="i"
              @click="activeTab = i"
              :class="['tc-nav-item', activeTab === i && 'tc-nav-item--active', tabHasError(i) && 'tc-nav-item--error']"
            >
              <div :class="['tc-nav-icon', activeTab === i && 'tc-nav-icon--active']">
                <component :is="'span'" v-html="tab.icon"></component>
              </div>
              <div class="tc-nav-text">
                <span class="tc-nav-label">{{ tab.label }}</span>
                <span class="tc-nav-count">{{ tab.fields }} fields</span>
              </div>
              <div v-if="tabHasError(i)" class="tc-nav-err-dot"></div>
              <svg v-if="activeTab === i" class="tc-nav-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
          </nav>

          <!-- Sidebar Status Preview -->
          <div class="tc-sidebar-preview">
            <div class="tc-preview-avatar">
              {{ previewInitials }}
            </div>
            <div class="tc-preview-info">
              <p class="tc-preview-name">{{ previewName || 'New Tenant' }}</p>
              <span :class="['tc-preview-status', `tc-preview-status--${form.status}`]">
                <span class="tc-preview-dot"></span>{{ form.status }}
              </span>
            </div>
          </div>
        </aside>

        <!-- ── Form Panel ── -->
        <form @submit.prevent="submitTenant" class="tc-form-wrap">

          <!-- ── TAB 0: Identity ── -->
          <div v-show="activeTab === 0" class="tc-tab-content">
            <div class="tc-section-header">
              <div class="tc-section-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              </div>
              <div>
                <h2 class="tc-section-title tc-display">Identity Information</h2>
                <p class="tc-section-desc">Core tenant profile and classification details</p>
              </div>
            </div>

            <div class="tc-grid tc-grid--4">

              <div class="tc-field">
                <label class="tc-label">Tenant Type <span class="tc-req">*</span></label>
                <div class="tc-select-wrap">
                  <select v-model="form.tenant_type" class="tc-select">
                    <option value="individual">Individual</option>
                    <option value="company">Company</option>
                    <option value="government">Government</option>
                    <option value="ngo">NGO</option>
                  </select>
                  <svg class="tc-select-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
              </div>

              <div class="tc-field">
                <label class="tc-label">Status <span class="tc-req">*</span></label>
                <div class="tc-select-wrap">
                  <select v-model="form.status" :class="['tc-select', `tc-select--${form.status}`]">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="pending">Pending</option>
                    <option value="blacklisted">Blacklisted</option>
                  </select>
                  <svg class="tc-select-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
              </div>

              <div class="tc-field">
                <label class="tc-label">Nationality</label>
                <input v-model="form.nationality" type="text" class="tc-input" placeholder="e.g. Somali" />
              </div>

              <div class="tc-field">
                <label class="tc-label">Gender</label>
                <div class="tc-select-wrap">
                  <select v-model="form.gender" class="tc-select">
                    <option value="">Select gender</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                  </select>
                  <svg class="tc-select-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
              </div>

              <div class="tc-field" :class="{ 'tc-field--error': errors.first_name }">
                <label class="tc-label">First Name <span class="tc-req">*</span></label>
                <input v-model="form.first_name" type="text" :class="['tc-input', errors.first_name && 'tc-input--error']" placeholder="Given name" />
                <p v-if="errors.first_name" class="tc-err-msg">{{ errors.first_name[0] }}</p>
              </div>

              <div class="tc-field">
                <label class="tc-label">Middle Name</label>
                <input v-model="form.middle_name" type="text" class="tc-input" placeholder="Optional" />
              </div>

              <div class="tc-field" :class="{ 'tc-field--error': errors.last_name }">
                <label class="tc-label">Last Name <span class="tc-req">*</span></label>
                <input v-model="form.last_name" type="text" :class="['tc-input', errors.last_name && 'tc-input--error']" placeholder="Family name" />
                <p v-if="errors.last_name" class="tc-err-msg">{{ errors.last_name[0] }}</p>
              </div>

              <div class="tc-field">
                <label class="tc-label">Date of Birth</label>
                <ErpDateInput v-model="form.date_of_birth" input-class="tc-input" placeholder="Date of birth" />
              </div>

              <div class="tc-field tc-field--span2">
                <label class="tc-label">Display Name</label>
                <input v-model="form.display_name" type="text" class="tc-input" placeholder="Visible name in ERP platform" />
                <p class="tc-hint">Auto-filled from first + last name if left empty</p>
              </div>

              <div class="tc-field tc-field--span2">
                <label class="tc-label">Company / Organisation</label>
                <input v-model="form.company_name" type="text" class="tc-input" placeholder="For corporate tenants only" />
              </div>

              <div class="tc-field tc-field--span4">
                <label class="tc-label">Occupation</label>
                <input v-model="form.occupation" type="text" class="tc-input" placeholder="e.g. Software Engineer, Business Owner" />
              </div>

            </div>
          </div>

          <!-- ── TAB 1: Contact ── -->
          <div v-show="activeTab === 1" class="tc-tab-content">
            <div class="tc-section-header">
              <div class="tc-section-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81a19.79 19.79 0 01-3.07-8.72A2 2 0 012 1h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 8.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
              </div>
              <div>
                <h2 class="tc-section-title tc-display">Contact Information</h2>
                <p class="tc-section-desc">Communication channels and reachability</p>
              </div>
            </div>

            <div class="tc-grid tc-grid--3">

              <div class="tc-field" :class="{ 'tc-field--error': errors.email }">
                <label class="tc-label">Email Address <span class="tc-req">*</span></label>
                <div class="tc-input-icon-wrap">
                  <svg class="tc-input-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                  <input v-model="form.email" type="email" :class="['tc-input tc-input--icon', errors.email && 'tc-input--error']" placeholder="tenant@example.com" />
                </div>
                <p v-if="errors.email" class="tc-err-msg">{{ errors.email[0] }}</p>
              </div>

              <div class="tc-field" :class="{ 'tc-field--error': errors.phone }">
                <label class="tc-label">Primary Phone <span class="tc-req">*</span></label>
                <div class="tc-input-icon-wrap">
                  <svg class="tc-input-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81a19.79 19.79 0 01-3.07-8.72A2 2 0 012 1h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 8.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
                  <input v-model="form.phone" type="text" :class="['tc-input tc-input--icon', errors.phone && 'tc-input--error']" placeholder="+252 61 000 0000" />
                </div>
                <p v-if="errors.phone" class="tc-err-msg">{{ errors.phone[0] }}</p>
              </div>

              <div class="tc-field">
                <label class="tc-label">Alternate Phone</label>
                <div class="tc-input-icon-wrap">
                  <svg class="tc-input-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81a19.79 19.79 0 01-3.07-8.72A2 2 0 012 1h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 8.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
                  <input v-model="form.alternate_phone" type="text" class="tc-input tc-input--icon" placeholder="Secondary number" />
                </div>
              </div>

            </div>

            <!-- Emergency Contact -->
            <div class="tc-subsection">
              <div class="tc-subsection-header">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                Emergency Contact
              </div>
              <div class="tc-grid tc-grid--3">
                <div class="tc-field">
                  <label class="tc-label">Contact Name</label>
                  <input v-model="form.emergency_contact_name" type="text" class="tc-input" placeholder="Full name" />
                </div>
                <div class="tc-field">
                  <label class="tc-label">Phone</label>
                  <input v-model="form.emergency_contact_phone" type="text" class="tc-input" placeholder="+252 61 000 0000" />
                </div>
                <div class="tc-field">
                  <label class="tc-label">Relationship</label>
                  <input v-model="form.emergency_contact_relationship" type="text" class="tc-input" placeholder="e.g. Spouse, Parent" />
                </div>
              </div>
            </div>
          </div>

          <!-- ── TAB 2: Legal ── -->
          <div v-show="activeTab === 2" class="tc-tab-content">
            <div class="tc-section-header">
              <div class="tc-section-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
              </div>
              <div>
                <h2 class="tc-section-title tc-display">Legal & Government</h2>
                <p class="tc-section-desc">Official identification and tax documentation</p>
              </div>
            </div>

            <div class="tc-legal-notice">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              Handle all legal identifiers with strict confidentiality. Store only what is operationally necessary.
            </div>

            <div class="tc-grid tc-grid--3">
              <div class="tc-field">
                <label class="tc-label">National ID</label>
                <div class="tc-input-icon-wrap">
                  <svg class="tc-input-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                  <input v-model="form.national_id" type="text" class="tc-input tc-input--icon" placeholder="National identification number" />
                </div>
              </div>
              <div class="tc-field">
                <label class="tc-label">Passport Number</label>
                <div class="tc-input-icon-wrap">
                  <svg class="tc-input-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                  <input v-model="form.passport_number" type="text" class="tc-input tc-input--icon" placeholder="Passport document number" />
                </div>
              </div>
              <div class="tc-field">
                <label class="tc-label">Tax Number</label>
                <div class="tc-input-icon-wrap">
                  <svg class="tc-input-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                  <input v-model="form.tax_number" type="text" class="tc-input tc-input--icon" placeholder="Tax identification number" />
                </div>
              </div>
            </div>
          </div>

          <!-- ── TAB 3: Address ── -->
          <div v-show="activeTab === 3" class="tc-tab-content">
            <div class="tc-section-header">
              <div class="tc-section-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
              </div>
              <div>
                <h2 class="tc-section-title tc-display">Address Information</h2>
                <p class="tc-section-desc">Residential and mailing address details</p>
              </div>
            </div>

            <div class="tc-grid tc-grid--4">
              <div class="tc-field tc-field--span2">
                <label class="tc-label">Country</label>
                <div class="tc-input-icon-wrap">
                  <svg class="tc-input-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>
                  <input v-model="form.country" type="text" class="tc-input tc-input--icon" placeholder="Country" />
                </div>
              </div>
              <div class="tc-field">
                <label class="tc-label">City</label>
                <input v-model="form.city" type="text" class="tc-input" placeholder="City or district" />
              </div>
              <div class="tc-field">
                <label class="tc-label">Postal Code</label>
                <input v-model="form.postal_code" type="text" class="tc-input" placeholder="ZIP / Postal" />
              </div>
              <div class="tc-field tc-field--span4">
                <label class="tc-label">Street Address</label>
                <textarea v-model="form.address" rows="4" class="tc-input tc-textarea" placeholder="Full street address, building, apartment…"></textarea>
              </div>
            </div>
          </div>

          <!-- ── TAB 4: Notes ── -->
          <div v-show="activeTab === 4" class="tc-tab-content">
            <div class="tc-section-header">
              <div class="tc-section-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
              </div>
              <div>
                <h2 class="tc-section-title tc-display">Internal Notes</h2>
                <p class="tc-section-desc">Private staff notes — not visible to the tenant</p>
              </div>
            </div>
            <div class="tc-field">
              <label class="tc-label">Notes</label>
              <textarea v-model="form.notes" rows="10" class="tc-input tc-textarea" placeholder="Add any internal notes about this tenant — payment behaviour, special requirements, flags…"></textarea>
              <p class="tc-hint">Only visible to authorised staff. Not shared with the tenant.</p>
            </div>
          </div>

          <!-- ── Form Footer ── -->
          <div class="tc-form-footer">
            <button v-if="activeTab > 0" type="button" @click="activeTab--" class="tc-btn-ghost">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><polyline points="15 18 9 12 15 6"/></svg>
              Previous
            </button>
            <div class="tc-footer-right">
              <span class="tc-footer-hint">{{ tabs[activeTab].label }} — {{ activeTab + 1 }}/{{ tabs.length }}</span>
              <button
                v-if="activeTab < tabs.length - 1"
                type="button"
                @click="activeTab++"
                class="tc-btn-primary"
              >
                Next: {{ tabs[activeTab + 1]?.label }}
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><polyline points="9 18 15 12 9 6"/></svg>
              </button>
              <button
                v-else
                type="submit"
                :disabled="loading"
                class="tc-btn-submit"
              >
                <span v-if="loading" class="tc-spinner"></span>
                <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><polyline points="20 6 9 17 4 12"/></svg>
                {{ loading ? 'Creating Tenant…' : 'Create Tenant' }}
              </button>
            </div>
          </div>

        </form>
      </div>
    </div>

    <!-- ═══════════════════ STYLES ═══════════════════ -->
    <component :is="'style'" scoped>
      .tc-root {
        --c-bg:        #f3f2f9;
        --c-surface:   #ffffff;
        --c-border:    #e5e2f5;
        --c-border2:   #cfc9ef;
        --c-text:      #18152e;
        --c-muted:     #7870a0;
        --c-accent:    #5b4ce8;
        --c-accent-h:  #4a3dd6;
        --c-accent-bg: #f0eeff;
        --c-green:     #12b374;
        --c-green-bg:  #edfaf4;
        --c-amber:     #d97706;
        --c-amber-bg:  #fffbeb;
        --c-red:       #dc2626;
        --c-red-bg:    #fff1f2;
        --c-blue:      #2563eb;
        --c-blue-bg:   #eff6ff;
        --c-shadow:    0 2px 8px rgba(30,20,80,.07), 0 1px 2px rgba(30,20,80,.04);
        --c-shadow-lg: 0 8px 32px rgba(30,20,80,.12);
        min-height: 100vh;
        background: var(--c-bg);
        padding: 28px;
        animation: tc-fadeUp .25s ease both;
      }

      /* Header */
      .tc-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 24px;
        flex-wrap: wrap;
      }
      .tc-back-link {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 13px;
        color: var(--c-muted);
        text-decoration: none;
        margin-bottom: 10px;
        transition: color .15s;
      }
      .tc-back-link:hover { color: var(--c-accent); }
      .tc-eyebrow {
        font-size: 11px;
        font-weight: 600;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: var(--c-accent);
        margin-bottom: 5px;
      }
      .tc-title {
        font-size: 30px;
        font-weight: 800;
        letter-spacing: -.02em;
        color: var(--c-text);
        margin: 0;
        line-height: 1;
      }
      .tc-subtitle { font-size: 13px; color: var(--c-muted); margin-top: 5px; }

      /* Progress */
      .tc-progress-wrap {
        min-width: 240px;
        max-width: 300px;
        flex: 0 0 auto;
        background: var(--c-surface);
        border: 1.5px solid var(--c-border);
        border-radius: 18px;
        padding: 16px 18px;
        box-shadow: var(--c-shadow);
      }
      .tc-progress-label { font-size: 11px; color: var(--c-muted); font-weight: 500; margin-bottom: 8px; }
      .tc-progress-track {
        height: 5px;
        background: var(--c-border);
        border-radius: 99px;
        overflow: hidden;
        margin-bottom: 10px;
      }
      .tc-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--c-accent), #9b8df7);
        border-radius: 99px;
        transition: width .35s cubic-bezier(.4,0,.2,1);
      }
      .tc-progress-tabs { display: flex; gap: 6px; }
      .tc-progress-dot {
        width: 22px; height: 22px;
        border-radius: 50%;
        border: 2px solid var(--c-border2);
        background: var(--c-surface);
        cursor: pointer;
        transition: all .2s;
        display: flex; align-items: center; justify-content: center;
        color: transparent;
        font-size: 0;
      }
      .tc-progress-dot--active { border-color: var(--c-accent); background: var(--c-accent-bg); }
      .tc-progress-dot--done   { border-color: var(--c-green); background: var(--c-green-bg); color: var(--c-green); }

      /* Alert */
      .tc-alert {
        display: flex;
        align-items: center;
        gap: 12px;
        background: var(--c-red-bg);
        border: 1.5px solid #fecaca;
        border-radius: 16px;
        padding: 14px 16px;
        margin-bottom: 20px;
      }
      .tc-alert-icon {
        width: 36px; height: 36px;
        background: #fee2e2;
        color: var(--c-red);
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
      }
      .tc-alert-title { font-size: 13px; font-weight: 600; color: var(--c-red); }
      .tc-alert-body  { font-size: 12px; color: #b91c1c; margin-top: 1px; }
      .tc-alert-close {
        margin-left: auto;
        width: 28px; height: 28px;
        background: none; border: none;
        color: #b91c1c; cursor: pointer;
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        transition: background .15s;
      }
      .tc-alert-close:hover { background: #fee2e2; }
      .tc-alert-enter-active, .tc-alert-leave-active { transition: all .2s ease; }
      .tc-alert-enter-from, .tc-alert-leave-to { opacity: 0; transform: translateY(-8px); }

      /* Layout */
      .tc-layout {
        display: grid;
        grid-template-columns: 220px 1fr;
        gap: 18px;
        align-items: start;
      }
      @media (max-width: 900px) {
        .tc-layout { grid-template-columns: 1fr; }
        .tc-sidebar { display: flex; flex-direction: row; overflow-x: auto; gap: 6px; }
        .tc-sidebar > nav { display: flex; flex-direction: row; gap: 6px; }
        .tc-nav-item { flex-direction: column; padding: 10px 12px; min-width: 90px; }
        .tc-nav-text { display: none; }
        .tc-sidebar-preview { display: none; }
      }

      /* Sidebar */
      .tc-sidebar {
        position: sticky;
        top: 20px;
        display: flex;
        flex-direction: column;
        gap: 10px;
      }
      .tc-nav {
        background: var(--c-surface);
        border: 1.5px solid var(--c-border);
        border-radius: 20px;
        overflow: hidden;
        box-shadow: var(--c-shadow);
      }
      .tc-nav-item {
        width: 100%;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 14px;
        border: none;
        background: none;
        cursor: pointer;
        text-align: left;
        transition: background .15s;
        border-bottom: 1px solid var(--c-border);
        position: relative;
      }
      .tc-nav-item:last-child { border-bottom: none; }
      .tc-nav-item:hover { background: var(--c-accent-bg); }
      .tc-nav-item--active { background: var(--c-accent-bg); }
      .tc-nav-item--error  { background: var(--c-red-bg); }
      .tc-nav-icon {
        width: 32px; height: 32px;
        border-radius: 10px;
        background: var(--c-bg);
        color: var(--c-muted);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        transition: all .15s;
      }
      .tc-nav-icon--active { background: var(--c-accent); color: #fff; }
      .tc-nav-text { flex: 1; min-width: 0; }
      .tc-nav-label { display: block; font-size: 12.5px; font-weight: 600; color: var(--c-text); white-space: nowrap; }
      .tc-nav-count { display: block; font-size: 10px; color: var(--c-muted); margin-top: 1px; }
      .tc-nav-err-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--c-red); }
      .tc-nav-arrow { color: var(--c-accent); flex-shrink: 0; }

      /* Sidebar Preview */
      .tc-sidebar-preview {
        background: var(--c-surface);
        border: 1.5px solid var(--c-border);
        border-radius: 18px;
        padding: 16px;
        box-shadow: var(--c-shadow);
        display: flex;
        align-items: center;
        gap: 12px;
      }
      .tc-preview-avatar {
        width: 42px; height: 42px;
        border-radius: 14px;
        background: var(--c-accent-bg);
        color: var(--c-accent);
        display: flex; align-items: center; justify-content: center;
        font-size: 15px;
        font-weight: 700;
        flex-shrink: 0;
        font-family: 'Syne', sans-serif;
      }
      .tc-preview-name { font-size: 12px; font-weight: 600; color: var(--c-text); }
      .tc-preview-status {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 11px;
        font-weight: 500;
        padding: 2px 8px;
        border-radius: 99px;
        margin-top: 4px;
        text-transform: capitalize;
      }
      .tc-preview-status--active      { background: var(--c-green-bg); color: var(--c-green); }
      .tc-preview-status--inactive    { background: var(--c-border); color: var(--c-muted); }
      .tc-preview-status--pending     { background: var(--c-amber-bg); color: var(--c-amber); }
      .tc-preview-status--blacklisted { background: var(--c-red-bg); color: var(--c-red); }
      .tc-preview-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

      /* Form Wrap */
      .tc-form-wrap {
        background: var(--c-surface);
        border: 1.5px solid var(--c-border);
        border-radius: 24px;
        overflow: hidden;
        box-shadow: var(--c-shadow);
      }

      /* Section Header */
      .tc-section-header {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 20px 24px;
        border-bottom: 1.5px solid var(--c-border);
        background: #faf9ff;
      }
      .tc-section-icon {
        width: 44px; height: 44px;
        border-radius: 14px;
        background: var(--c-accent-bg);
        color: var(--c-accent);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        border: 1.5px solid var(--c-border);
      }
      .tc-section-title { font-size: 20px; font-weight: 700; color: var(--c-text); margin: 0; letter-spacing: -.01em; }
      .tc-section-desc  { font-size: 12.5px; color: var(--c-muted); margin-top: 3px; }

      /* Subsection */
      .tc-subsection {
        margin: 0 24px 20px;
        border: 1.5px solid var(--c-border);
        border-radius: 16px;
        overflow: hidden;
      }
      .tc-subsection-header {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 16px;
        font-size: 12px;
        font-weight: 600;
        color: var(--c-muted);
        background: var(--c-bg);
        border-bottom: 1px solid var(--c-border);
        text-transform: uppercase;
        letter-spacing: .06em;
      }
      .tc-subsection .tc-grid { padding: 16px; margin: 0; }

      /* Grids */
      .tc-grid {
        display: grid;
        gap: 16px;
        padding: 20px 24px;
      }
      .tc-grid--3 { grid-template-columns: repeat(3, 1fr); }
      .tc-grid--4 { grid-template-columns: repeat(4, 1fr); }
      @media (max-width: 1100px) { .tc-grid--4 { grid-template-columns: repeat(2, 1fr); } }
      @media (max-width: 720px)  { .tc-grid--3, .tc-grid--4 { grid-template-columns: 1fr; } }

      .tc-field--span2 { grid-column: span 2; }
      .tc-field--span4 { grid-column: span 4; }
      @media (max-width: 1100px) { .tc-field--span4 { grid-column: span 2; } }
      @media (max-width: 720px)  { .tc-field--span2, .tc-field--span4 { grid-column: span 1; } }

      @media (max-width: 720px) {
        .tc-root { padding: 16px; }
        .tc-title { font-size: 24px; }
        .tc-progress-wrap { min-width: 100%; max-width: 100%; }
        .tc-section-header { padding: 16px; }
        .tc-section-title { font-size: 17px; }
        .tc-grid { padding: 16px; }
        .tc-subsection { margin: 0 16px 16px; }
        .tc-legal-notice { margin: 0 16px 4px; }
        .tc-form-footer {
          flex-direction: column-reverse;
          align-items: stretch;
          padding: 16px;
        }
        .tc-footer-right {
          flex-direction: column;
          width: 100%;
        }
        .tc-footer-hint { text-align: center; }
        .tc-btn-ghost,
        .tc-btn-primary,
        .tc-btn-submit {
          width: 100%;
          justify-content: center;
        }
        .tc-input,
        .tc-select {
          font-size: 16px;
          min-height: 44px;
        }
      }

      /* Fields */
      .tc-field { display: flex; flex-direction: column; }
      .tc-field--error .tc-label { color: var(--c-red); }

      .tc-label {
        font-size: 12px;
        font-weight: 600;
        color: var(--c-text);
        margin-bottom: 6px;
        letter-spacing: .01em;
      }
      .tc-req { color: var(--c-red); margin-left: 2px; }

      .tc-input {
        width: 100%;
        padding: 10px 13px;
        border-radius: 12px;
        border: 1.5px solid var(--c-border);
        background: var(--c-bg);
        font-size: 14px;
        color: var(--c-text);
        outline: none;
        transition: border-color .15s, box-shadow .15s, background .15s;
        font-family: 'DM Sans', sans-serif;
        -webkit-appearance: none;
      }
      .tc-input::placeholder { color: var(--c-muted); font-weight: 300; }
      .tc-input:focus {
        border-color: var(--c-accent);
        box-shadow: 0 0 0 3px rgba(91,76,232,.1);
        background: #fff;
      }
      .tc-input--error {
        border-color: var(--c-red);
        background: var(--c-red-bg);
      }
      .tc-input--error:focus { box-shadow: 0 0 0 3px rgba(220,38,38,.1); }
      .tc-textarea { resize: vertical; min-height: 100px; }
      .tc-input--icon { padding-left: 38px; }

      .tc-input-icon-wrap { position: relative; }
      .tc-input-icon {
        position: absolute;
        left: 12px; top: 50%;
        transform: translateY(-50%);
        color: var(--c-muted);
        pointer-events: none;
      }

      .tc-select-wrap { position: relative; }
      .tc-select {
        width: 100%;
        padding: 10px 36px 10px 13px;
        border-radius: 12px;
        border: 1.5px solid var(--c-border);
        background: var(--c-bg);
        font-size: 14px;
        color: var(--c-text);
        outline: none;
        -webkit-appearance: none;
        appearance: none;
        cursor: pointer;
        transition: border-color .15s, box-shadow .15s;
        font-family: 'DM Sans', sans-serif;
      }
      .tc-select:focus { border-color: var(--c-accent); box-shadow: 0 0 0 3px rgba(91,76,232,.1); background: #fff; }
      .tc-select--active     { color: var(--c-green); }
      .tc-select--inactive   { color: var(--c-muted); }
      .tc-select--pending    { color: var(--c-amber); }
      .tc-select--blacklisted{ color: var(--c-red); }
      .tc-select-arrow { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: var(--c-muted); pointer-events: none; }

      .tc-err-msg { font-size: 11px; color: var(--c-red); margin-top: 4px; font-weight: 500; }
      .tc-hint    { font-size: 11px; color: var(--c-muted); margin-top: 4px; }

      /* Legal Notice */
      .tc-legal-notice {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0 24px 4px;
        padding: 10px 14px;
        background: var(--c-amber-bg);
        border: 1px solid #fde68a;
        border-radius: 12px;
        font-size: 12px;
        color: var(--c-amber);
        font-weight: 500;
      }

      /* Footer */
      .tc-form-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 24px;
        border-top: 1.5px solid var(--c-border);
        background: #faf9ff;
        gap: 12px;
      }
      .tc-footer-right { display: flex; align-items: center; gap: 12px; }
      .tc-footer-hint  { font-size: 12px; color: var(--c-muted); }

      /* Buttons */
      .tc-btn-ghost {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 10px 18px; border-radius: 12px;
        border: 1.5px solid var(--c-border2);
        background: var(--c-surface);
        color: var(--c-text); font-size: 14px; font-weight: 500;
        cursor: pointer; transition: all .15s;
        font-family: 'DM Sans', sans-serif;
        text-decoration: none;
      }
      .tc-btn-ghost:hover { border-color: var(--c-accent); background: var(--c-accent-bg); color: var(--c-accent); }

      .tc-btn-primary {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 10px 20px; border-radius: 12px;
        border: none; background: var(--c-accent);
        color: #fff; font-size: 14px; font-weight: 600;
        cursor: pointer; transition: all .15s;
        box-shadow: 0 4px 12px rgba(91,76,232,.3);
        font-family: 'DM Sans', sans-serif;
      }
      .tc-btn-primary:hover { background: var(--c-accent-h); transform: translateY(-1px); box-shadow: 0 6px 18px rgba(91,76,232,.4); }

      .tc-btn-submit {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 11px 22px; border-radius: 12px;
        border: none; background: linear-gradient(135deg, var(--c-accent), #7c6cf5);
        color: #fff; font-size: 14px; font-weight: 700;
        cursor: pointer; transition: all .15s;
        box-shadow: 0 4px 14px rgba(91,76,232,.35);
        font-family: 'Syne', sans-serif;
        letter-spacing: .01em;
      }
      .tc-btn-submit:hover { transform: translateY(-1px); box-shadow: 0 8px 22px rgba(91,76,232,.45); }
      .tc-btn-submit:disabled { opacity: .6; cursor: not-allowed; transform: none; }

      .tc-spinner {
        width: 16px; height: 16px;
        border: 2px solid rgba(255,255,255,.3);
        border-top-color: #fff;
        border-radius: 50%;
        animation: tc-spin .7s linear infinite;
        display: inline-block;
      }
    </component>

 
</template>

<script setup>
import { reactive, ref, computed } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import api from '../../services/api'
import DashboardLayout from '../../layouts/DashboardLayout.vue'
import { ErpDateInput } from '@/components/erp'

const router  = useRouter()
const loading = ref(false)
const errors  = ref({})

const activeTab = ref(0)

const tabs = [
  { label: 'Identity',  fields: 11, icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>' },
  { label: 'Contact',   fields:  6, icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81a19.79 19.79 0 01-3.07-8.72A2 2 0 012 1h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 8.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>' },
  { label: 'Legal',     fields:  3, icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>' },
  { label: 'Address',   fields:  4, icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>' },
  { label: 'Notes',     fields:  1, icon: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/></svg>' },
]

// Which tabs have validation errors
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
  tenant_type: 'individual',
  status: 'active',
  first_name: '', middle_name: '', last_name: '',
  display_name: '', company_name: '',
  email: '', phone: '', alternate_phone: '',
  national_id: '', passport_number: '', tax_number: '',
  nationality: '', date_of_birth: '',
  gender: '', occupation: '',
  country: '', city: '', address: '', postal_code: '',
  emergency_contact_name: '', emergency_contact_phone: '',
  emergency_contact_relationship: '',
  notes: '',
})

// Sidebar preview
const previewInitials = computed(() => {
  const f = form.first_name?.[0] || ''
  const l = form.last_name?.[0]  || ''
  return (f + l).toUpperCase() || '?'
})
const previewName = computed(() =>
  [form.first_name, form.last_name].filter(Boolean).join(' ')
)

const submitTenant = async () => {
  loading.value = true
  errors.value  = {}
  try {
    await api.post('/tenants', { ...form })
    router.push({ name: 'TenantsIndex' })
  } catch (error) {
    console.error(error)
    console.log(error.response?.data)
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
      // Jump to first errored tab
      const errTab = tabFieldMap.findIndex(fields => fields.some(f => errors.value[f]))
      if (errTab !== -1) activeTab.value = errTab
    }
  } finally {
    loading.value = false
  }
}
</script>