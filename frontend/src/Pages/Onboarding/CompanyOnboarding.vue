<template>
  <div class="onboard-root">

    <!-- ══════════════════════════════════════════════════════════
         LEFT PANEL — Brand + Value Props
    ══════════════════════════════════════════════════════════ -->
    <aside class="left-panel" aria-hidden="true">

      <!-- Decorative orbs -->
      <div class="orb orb--1"></div>
      <div class="orb orb--2"></div>
      <div class="orb orb--3"></div>

      <!-- Brand -->
      <div class="left-panel__brand">
        <div class="brand-mark">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="7" width="20" height="14" rx="2"/>
            <path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>
          </svg>
        </div>
        <div>
          <p class="brand-name">AMSAAS</p>
          <p class="brand-tagline">Property Management Platform</p>
        </div>
      </div>

      <!-- Hero copy -->
      <div class="left-panel__hero">
        <div class="hero-eyebrow">
          <span class="hero-eyebrow__dot"></span>
          Workspace Setup
        </div>
        <h1 class="hero-headline">
          Your property<br/>empire starts here.
        </h1>
        <p class="hero-body">
          Configure your company profile, create your admin account,
          and get immediate access to your fully-isolated management workspace.
        </p>
      </div>

      <!-- Feature tiles -->
      <div class="feature-list">

        <div class="feature-item">
          <div class="feature-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>
              <line x1="12" y1="12" x2="12" y2="16"/><line x1="10" y1="14" x2="14" y2="14"/>
            </svg>
          </div>
          <div>
            <p class="feature-title">Multi-Building Management</p>
            <p class="feature-sub">Manage unlimited buildings, floors, and units across any location</p>
          </div>
        </div>

        <div class="feature-item">
          <div class="feature-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
          </div>
          <div>
            <p class="feature-title">Tenant Lifecycle</p>
            <p class="feature-sub">From agreements to renewals — full lifecycle automation</p>
          </div>
        </div>

        <div class="feature-item">
          <div class="feature-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>
            </svg>
          </div>
          <div>
            <p class="feature-title">Financial Operations</p>
            <p class="feature-sub">Invoices, payments, and revenue reports in one place</p>
          </div>
        </div>

        <div class="feature-item">
          <div class="feature-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
          </div>
          <div>
            <p class="feature-title">Isolated & Secure</p>
            <p class="feature-sub">UUID-based multi-tenant architecture with Sanctum auth</p>
          </div>
        </div>

      </div>

      <!-- Trust bar -->
      <div class="trust-bar">
        <div class="trust-item">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          Multi-tenant SaaS
        </div>
        <div class="trust-sep">·</div>
        <div class="trust-item">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          API-first
        </div>
        <div class="trust-sep">·</div>
        <div class="trust-item">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          Enterprise ready
        </div>
      </div>

    </aside>

    <!-- ══════════════════════════════════════════════════════════
         RIGHT PANEL — Form
    ══════════════════════════════════════════════════════════ -->
    <main class="right-panel">
      <div class="form-shell">

        <!-- Mobile brand -->
        <div class="mobile-brand">
          <div class="brand-mark brand-mark--dark">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="2" y="7" width="20" height="14" rx="2"/>
              <path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>
            </svg>
          </div>
          <span class="mobile-brand__name">AMSAAS</span>
        </div>

        <!-- Form heading -->
        <div class="form-heading">
          <h2 class="form-heading__title">Create your workspace</h2>
          <p class="form-heading__sub">Fill in your company details and administrator credentials to get started</p>
        </div>

        <!-- Step progress -->
        <div class="step-progress" role="list" aria-label="Setup steps">
          <div
            v-for="(step, i) in steps"
            :key="step.id"
            class="step-item"
            :class="{
              'step-item--active':    currentStep === i,
              'step-item--complete':  currentStep > i
            }"
            role="listitem"
          >
            <div class="step-bubble">
              <svg v-if="currentStep > i" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              <span v-else>{{ i + 1 }}</span>
            </div>
            <div class="step-label">
              <p class="step-label__title">{{ step.title }}</p>
              <p class="step-label__sub">{{ step.sub }}</p>
            </div>
            <div v-if="i < steps.length - 1" class="step-connector" :class="{ 'step-connector--done': currentStep > i }"></div>
          </div>
        </div>

        <!-- Server error -->
        <Transition name="err-fade">
          <div v-if="serverError" class="server-error" role="alert">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <span>{{ serverError }}</span>
            <button class="server-error__close" @click="serverError = ''">×</button>
          </div>
        </Transition>

        <form @submit.prevent="submitCompany" novalidate>

          <!-- ─── Step 1: Company Information ──────────────────── -->
          <section v-show="currentStep === 0" class="form-section">
            <div class="section-marker">
              <div class="section-marker__icon section-marker__icon--indigo">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>
                </svg>
              </div>
              <div>
                <h3 class="section-marker__title">Company Information</h3>
                <p class="section-marker__sub">Your organization's legal identity and contact details</p>
              </div>
            </div>

            <div class="field-grid">

              <!-- Company Name (full width) -->
              <div class="field-group col-span-2">
                <label class="field-label">
                  Company Name
                  <span class="field-required">*</span>
                </label>
                <div class="input-wrap" :class="{ 'input-wrap--error': errors.company_name, 'input-wrap--filled': form.company_name }">
                  <svg class="input-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>
                  </svg>
                  <input
                    v-model="form.company_name"
                    type="text"
                    required
                    placeholder="e.g. Horizon Property Group"
                    class="field-input"
                    @blur="touchField('company_name')"
                  />
                </div>
                <p v-if="errors.company_name" class="field-error">{{ errors.company_name[0] }}</p>
              </div>

              <!-- Company Email -->
              <div class="field-group">
                <label class="field-label">Company Email <span class="field-required">*</span></label>
                <div class="input-wrap" :class="{ 'input-wrap--error': errors.company_email }">
                  <svg class="input-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
                  </svg>
                  <input v-model="form.company_email" type="email" required placeholder="company@example.com" class="field-input" />
                </div>
                <p v-if="errors.company_email" class="field-error">{{ errors.company_email[0] }}</p>
              </div>

              <!-- Phone -->
              <div class="field-group">
                <label class="field-label">Phone Number</label>
                <div class="input-wrap">
                  <svg class="input-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.15 3.42 2 2 0 0 1 3.12 1.23h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
                  </svg>
                  <input v-model="form.company_phone" type="text" placeholder="+1 (555) 000-0000" class="field-input" />
                </div>
              </div>

              <!-- Country -->
              <div class="field-group">
                <label class="field-label">Country</label>
                <div class="select-wrap">
                  <svg class="input-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="10" r="3"/><path d="M12 21.7C17.3 17 20 13 20 10a8 8 0 1 0-16 0c0 3 2.7 6.9 8 11.7z"/>
                  </svg>
                  <select v-model="form.company_country" class="field-select">
                    <option value="">Select country…</option>
                    <option value="Somalia">Somalia</option>
                    <option value="Kenya">Kenya</option>
                    <option value="Ethiopia">Ethiopia</option>
                    <option value="Djibouti">Djibouti</option>
                    <option value="Uganda">Uganda</option>
                    <option value="Tanzania">Tanzania</option>
                    <option value="Rwanda">Rwanda</option>
                  </select>
                  <svg class="select-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"/>
                  </svg>
                </div>
              </div>

              <!-- City -->
              <div class="field-group">
                <label class="field-label">City</label>
                <div class="input-wrap">
                  <svg class="input-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
                  </svg>
                  <input v-model="form.company_city" type="text" placeholder="e.g. Mogadishu" class="field-input" />
                </div>
              </div>

              <!-- Registration Number -->
              <div class="field-group">
                <label class="field-label">Registration No.
                  <span class="field-optional">optional</span>
                </label>
                <div class="input-wrap">
                  <svg class="input-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                  </svg>
                  <input v-model="form.registration_number" type="text" placeholder="Business reg. number" class="field-input field-input--mono" />
                </div>
              </div>

              <!-- Tax Number -->
              <div class="field-group">
                <label class="field-label">Tax Number
                  <span class="field-optional">optional</span>
                </label>
                <div class="input-wrap">
                  <svg class="input-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>
                  </svg>
                  <input v-model="form.tax_number" type="text" placeholder="VAT / TIN" class="field-input field-input--mono" />
                </div>
              </div>

              <!-- Address (full width) -->
              <div class="field-group col-span-2">
                <label class="field-label">Physical Address
                  <span class="field-optional">optional</span>
                </label>
                <textarea
                  v-model="form.company_address"
                  rows="3"
                  placeholder="Street address, district, postal code…"
                  class="field-textarea"
                ></textarea>
              </div>

            </div>

            <!-- Step nav -->
            <div class="step-actions">
              <div class="step-actions__hint">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Fields marked <span class="step-actions__req">*</span> are required
              </div>
              <button type="button" class="btn-next" @click="goNext">
                Continue to Admin Setup
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                </svg>
              </button>
            </div>
          </section>

          <!-- ─── Step 2: Admin Account ─────────────────────────── -->
          <section v-show="currentStep === 1" class="form-section">
            <div class="section-marker">
              <div class="section-marker__icon section-marker__icon--emerald">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="8" r="4"/><path d="M6 20v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/>
                </svg>
              </div>
              <div>
                <h3 class="section-marker__title">Administrator Account</h3>
                <p class="section-marker__sub">This account will have full control over your workspace</p>
              </div>
            </div>

            <!-- Admin avatar preview -->
            <div class="admin-preview">
              <div class="admin-preview__avatar">
                {{ adminInitials || '?' }}
              </div>
              <div class="admin-preview__info">
                <p class="admin-preview__name">{{ form.name || 'Your Full Name' }}</p>
                <p class="admin-preview__role">
                  <span class="role-badge">Administrator</span>
                  <span class="admin-preview__email">{{ form.email || 'login@email.com' }}</span>
                </p>
              </div>
            </div>

            <div class="field-grid">

              <!-- Full Name -->
              <div class="field-group col-span-2">
                <label class="field-label">Full Name <span class="field-required">*</span></label>
                <div class="input-wrap" :class="{ 'input-wrap--error': errors.name }">
                  <svg class="input-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="8" r="4"/><path d="M6 20v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/>
                  </svg>
                  <input v-model="form.name" type="text" required placeholder="e.g. Ahmed Mohamed Ali" class="field-input" />
                </div>
                <p v-if="errors.name" class="field-error">{{ errors.name[0] }}</p>
              </div>

              <!-- Login Email -->
              <div class="field-group">
                <label class="field-label">Login Email <span class="field-required">*</span></label>
                <div class="input-wrap" :class="{ 'input-wrap--error': errors.email }">
                  <svg class="input-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
                  </svg>
                  <input v-model="form.email" type="email" required placeholder="admin@yourcompany.com" class="field-input" />
                </div>
                <p v-if="errors.email" class="field-error">{{ errors.email[0] }}</p>
              </div>

              <!-- Password -->
              <div class="field-group">
                <label class="field-label">Password <span class="field-required">*</span></label>
                <div class="input-wrap" :class="{ 'input-wrap--error': errors.password }">
                  <svg class="input-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                  </svg>
                  <input
                    v-model="form.password"
                    :type="showPassword ? 'text' : 'password'"
                    required
                    placeholder="Min. 8 characters"
                    class="field-input field-input--password"
                  />
                  <button type="button" class="password-toggle" @click="showPassword = !showPassword" :aria-label="showPassword ? 'Hide password' : 'Show password'">
                    <svg v-if="!showPassword" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                    </svg>
                    <svg v-else width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                      <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                      <line x1="1" y1="1" x2="23" y2="23"/>
                    </svg>
                  </button>
                </div>
                <!-- Password strength -->
                <div v-if="form.password" class="password-strength">
                  <div class="strength-bars">
                    <div
                      v-for="i in 4" :key="i"
                      class="strength-bar"
                      :class="{ 'strength-bar--filled': i <= passwordStrength.score, [`strength-bar--${passwordStrength.level}`]: i <= passwordStrength.score }"
                    ></div>
                  </div>
                  <span class="strength-label" :class="`strength-label--${passwordStrength.level}`">
                    {{ passwordStrength.label }}
                  </span>
                </div>
                <p v-if="errors.password" class="field-error">{{ errors.password[0] }}</p>
              </div>

            </div>

            <!-- Security note -->
            <div class="security-note">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
              </svg>
              <p>Your credentials are encrypted in transit and stored using industry-standard hashing. We never store plain-text passwords.</p>
            </div>

            <!-- Step nav -->
            <div class="step-actions">
              <button type="button" class="btn-back" @click="goBack">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
                </svg>
                Back
              </button>
              <button type="submit" :disabled="isSubmitting" class="btn-submit">
                <svg v-if="isSubmitting" class="btn-spinner" width="16" height="16" viewBox="0 0 24 24" fill="none">
                  <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="32" stroke-linecap="round"/>
                </svg>
                <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="20 6 9 17 4 12"/>
                </svg>
                {{ isSubmitting ? 'Creating Workspace…' : 'Launch Workspace' }}
              </button>
            </div>
          </section>

        </form>

        <!-- Footer -->
        <p class="form-footer">
          Already have an account?
          <router-link to="/login" class="form-footer__link">Sign in instead</router-link>
        </p>

      </div>
    </main>

  </div>
</template>

<script setup>
import { reactive, ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const router    = useRouter()
const authStore = useAuthStore()

/* ── UI State ──────────────────────────────────────────────── */
const isSubmitting  = ref(false)
const serverError   = ref('')
const errors        = ref({})
const showPassword  = ref(false)
const currentStep   = ref(0)

const steps = [
  { id: 'company', title: 'Company Info',    sub: 'Organization details' },
  { id: 'admin',   title: 'Admin Account',   sub: 'Credentials & access' },
]

/* ── Form ──────────────────────────────────────────────────── */
const form = reactive({
  company_name: '', company_email: '', company_phone: '',
  company_address: '', company_city: '', company_country: '',
  registration_number: '', tax_number: '',
  name: '', email: '', password: '',
})

/* ── Derived ───────────────────────────────────────────────── */
const adminInitials = computed(() => {
  if (!form.name) return ''
  return form.name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase()
})

const passwordStrength = computed(() => {
  const p = form.password
  if (!p) return { score: 0, level: 'weak', label: '' }
  let score = 0
  if (p.length >= 8)  score++
  if (p.length >= 12) score++
  if (/[A-Z]/.test(p) && /[a-z]/.test(p)) score++
  if (/[0-9]/.test(p) && /[^A-Za-z0-9]/.test(p)) score++
  const levels = ['', 'weak', 'fair', 'good', 'strong']
  const labels = ['', 'Too weak', 'Fair', 'Good', 'Strong']
  return { score, level: levels[score] || 'weak', label: labels[score] || 'Too weak' }
})

/* ── Navigation ────────────────────────────────────────────── */
const goNext = () => {
  if (!form.company_name || !form.company_email) {
    if (!form.company_name) errors.value.company_name = ['Company name is required']
    if (!form.company_email) errors.value.company_email = ['Company email is required']
    return
  }
  errors.value = {}
  currentStep.value = 1
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const goBack = () => {
  currentStep.value = 0
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const touchField = (field) => {
  if (!form[field] && errors.value[field]) {
    // keep existing error visible
  } else if (form[field]) {
    delete errors.value[field]
  }
}

/* ── Submit ────────────────────────────────────────────────── */
const submitCompany = async () => {
  isSubmitting.value = true
  errors.value       = {}
  serverError.value  = ''
  try {
    const response = await api.post('/setup/company', form)
    authStore.setAuth(response.data)
    router.push('/dashboard')
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
      // If company-step errors, go back
      const companyFields = ['company_name', 'company_email', 'company_phone', 'company_country']
      if (companyFields.some(f => errors.value[f])) currentStep.value = 0
    } else {
      serverError.value = error.response?.data?.message || 'Unable to create workspace. Please try again.'
    }
  } finally {
    isSubmitting.value = false
  }
}
</script>

<style scoped>
/* ════════════════════════════════════════════════════════════
   Tokens
════════════════════════════════════════════════════════════ */
.onboard-root {
  --accent:       #4f46e5;
  --accent-lt:    #eef2ff;
  --accent-hover: #4338ca;
  --accent-ring:  rgba(79,70,229,.15);

  --border:   #e5e7eb;
  --surface:  #ffffff;
  --app-bg:   #f6f7fb;

  --text-1: #111827;
  --text-2: #4b5563;
  --text-3: #9ca3af;

  --red:    #ef4444;
  --red-lt: #fef2f2;
  --green:  #059669;
  --green-lt: #ecfdf5;

  --r-sm:  6px;
  --r-md:  10px;
  --r-lg:  14px;
  --r-xl:  18px;
  --r-2xl: 24px;

  --shadow-sm:    0 1px 2px rgba(0,0,0,.05);
  --shadow-form:  0 4px 24px rgba(0,0,0,.07);
  --shadow-panel: 0 32px 80px rgba(79,70,229,.25);

  --ease: 160ms cubic-bezier(.4,0,.2,1);
  --ease-spring: 300ms cubic-bezier(.34,1.26,.64,1);

  font-family: 'DM Sans', ui-sans-serif, system-ui, sans-serif;
  display: flex;
  min-height: 100vh;
  background: var(--app-bg);
}

/* ════════════════════════════════════════════════════════════
   Left Panel
════════════════════════════════════════════════════════════ */
.left-panel {
  display: none;
  flex-direction: column;
  justify-content: space-between;
  width: 420px;
  flex-shrink: 0;
  background: linear-gradient(160deg, #312e81 0%, #1e40af 45%, #0f172a 100%);
  padding: 44px 40px;
  position: sticky;
  top: 0;
  height: 100vh;
  overflow: hidden;
}
@media (min-width: 1024px) { .left-panel { display: flex; } }

/* Orbs */
.orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(60px);
  pointer-events: none;
}
.orb--1 { width: 340px; height: 340px; background: rgba(99,102,241,.25); top: -80px; left: -80px; }
.orb--2 { width: 280px; height: 280px; background: rgba(6,182,212,.15); bottom: 60px; right: -60px; }
.orb--3 { width: 200px; height: 200px; background: rgba(167,139,250,.2); top: 50%; left: 50%; transform: translate(-50%,-50%); }

/* Brand */
.left-panel__brand {
  display: flex;
  align-items: center;
  gap: 14px;
  position: relative;
  z-index: 1;
}
.brand-mark {
  width: 44px; height: 44px;
  border-radius: var(--r-lg);
  background: rgba(255,255,255,.15);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255,255,255,.2);
  display: flex; align-items: center; justify-content: center;
  color: #fff;
  flex-shrink: 0;
}
.brand-mark--dark {
  background: var(--accent);
  border: none;
  box-shadow: 0 2px 12px rgba(79,70,229,.4);
}
.brand-name    { font-size: 16px; font-weight: 800; color: #fff; letter-spacing: .04em; }
.brand-tagline { font-size: 11px; color: rgba(255,255,255,.55); margin-top: 1px; }

/* Hero */
.left-panel__hero {
  position: relative;
  z-index: 1;
}
.hero-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  font-weight: 600;
  letter-spacing: .08em;
  text-transform: uppercase;
  color: rgba(255,255,255,.6);
  margin-bottom: 18px;
}
.hero-eyebrow__dot {
  width: 7px; height: 7px;
  border-radius: 50%;
  background: #34d399;
  box-shadow: 0 0 8px rgba(52,211,153,.5);
  animation: pulse-green 2s ease infinite;
}
@keyframes pulse-green {
  0%, 100% { box-shadow: 0 0 0 0 rgba(52,211,153,.4); }
  50%       { box-shadow: 0 0 0 6px rgba(52,211,153,0); }
}

.hero-headline {
  font-size: 36px;
  font-weight: 800;
  line-height: 1.15;
  letter-spacing: -.03em;
  color: #fff;
}
.hero-body {
  font-size: 15px;
  color: rgba(255,255,255,.6);
  line-height: 1.7;
  margin-top: 16px;
}

/* Features */
.feature-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
  position: relative;
  z-index: 1;
}
.feature-item {
  display: flex;
  align-items: flex-start;
  gap: 14px;
}
.feature-icon {
  width: 34px; height: 34px;
  border-radius: var(--r-md);
  background: rgba(255,255,255,.1);
  border: 1px solid rgba(255,255,255,.12);
  display: flex; align-items: center; justify-content: center;
  color: rgba(255,255,255,.8);
  flex-shrink: 0;
  margin-top: 2px;
}
.feature-title { font-size: 13.5px; font-weight: 700; color: #fff; }
.feature-sub   { font-size: 12px; color: rgba(255,255,255,.5); margin-top: 2px; line-height: 1.5; }

/* Trust bar */
.trust-bar {
  display: flex;
  align-items: center;
  gap: 10px;
  position: relative;
  z-index: 1;
  flex-wrap: wrap;
}
.trust-item {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 11.5px;
  font-weight: 500;
  color: rgba(255,255,255,.45);
}
.trust-sep { color: rgba(255,255,255,.2); font-size: 14px; }

/* ════════════════════════════════════════════════════════════
   Right Panel
════════════════════════════════════════════════════════════ */
.right-panel {
  flex: 1;
  overflow-y: auto;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding: 40px 24px 60px;
}

.form-shell {
  width: 100%;
  max-width: 560px;
  display: flex;
  flex-direction: column;
  gap: 0;
}

/* Mobile brand */
.mobile-brand {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 32px;
}
.mobile-brand__name { font-size: 18px; font-weight: 800; color: var(--text-1); letter-spacing: .04em; }
@media (min-width: 1024px) { .mobile-brand { display: none; } }

/* Form heading */
.form-heading { margin-bottom: 28px; }
.form-heading__title {
  font-size: 26px;
  font-weight: 800;
  letter-spacing: -.03em;
  color: var(--text-1);
  line-height: 1.2;
}
.form-heading__sub {
  font-size: 14px;
  color: var(--text-3);
  margin-top: 6px;
  line-height: 1.5;
}

/* ════════════════════════════════════════════════════════════
   Step Progress
════════════════════════════════════════════════════════════ */
.step-progress {
  display: flex;
  align-items: flex-start;
  gap: 0;
  margin-bottom: 32px;
  padding: 20px 22px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--r-xl);
  box-shadow: var(--shadow-sm);
  position: relative;
}

.step-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  flex: 1;
  position: relative;
}

.step-bubble {
  width: 30px; height: 30px;
  border-radius: 50%;
  background: #f3f4f6;
  border: 2px solid var(--border);
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 700; color: var(--text-3);
  flex-shrink: 0;
  transition: all var(--ease);
}
.step-item--active .step-bubble {
  background: var(--accent);
  border-color: var(--accent);
  color: #fff;
  box-shadow: 0 0 0 4px var(--accent-ring);
}
.step-item--complete .step-bubble {
  background: var(--green);
  border-color: var(--green);
  color: #fff;
}

.step-label__title {
  font-size: 13px;
  font-weight: 700;
  color: var(--text-3);
  transition: color var(--ease);
  line-height: 1.3;
}
.step-label__sub {
  font-size: 11.5px;
  color: var(--text-3);
  margin-top: 1px;
}
.step-item--active  .step-label__title,
.step-item--complete .step-label__title { color: var(--text-1); }

.step-connector {
  position: absolute;
  left: calc(100% - 10px);
  top: 14px;
  width: 24px;
  height: 2px;
  background: var(--border);
  transition: background var(--ease);
}
.step-connector--done { background: var(--green); }

/* ════════════════════════════════════════════════════════════
   Server Error
════════════════════════════════════════════════════════════ */
.server-error {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 13px 16px;
  background: var(--red-lt);
  border: 1px solid #fecaca;
  border-radius: var(--r-lg);
  color: var(--red);
  font-size: 13.5px;
  font-weight: 500;
  margin-bottom: 20px;
}
.server-error__close {
  margin-left: auto; background: none; border: none;
  color: var(--red); font-size: 18px; cursor: pointer; line-height: 1;
}
.err-fade-enter-active { transition: opacity .2s ease, transform .2s ease; }
.err-fade-leave-active { transition: opacity .15s ease; }
.err-fade-enter-from   { opacity: 0; transform: translateY(-6px); }
.err-fade-leave-to     { opacity: 0; }

/* ════════════════════════════════════════════════════════════
   Section Marker
════════════════════════════════════════════════════════════ */
.form-section {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--r-2xl);
  padding: 28px;
  box-shadow: var(--shadow-form);
  display: flex;
  flex-direction: column;
  gap: 22px;
}

.section-marker {
  display: flex;
  align-items: flex-start;
  gap: 14px;
}
.section-marker__icon {
  width: 38px; height: 38px;
  border-radius: var(--r-lg);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.section-marker__icon--indigo  { background: var(--accent-lt); color: var(--accent); }
.section-marker__icon--emerald { background: var(--green-lt); color: var(--green); }

.section-marker__title { font-size: 16px; font-weight: 700; color: var(--text-1); letter-spacing: -.01em; }
.section-marker__sub   { font-size: 13px; color: var(--text-3); margin-top: 2px; }

/* ════════════════════════════════════════════════════════════
   Field Grid
════════════════════════════════════════════════════════════ */
.field-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px 18px;
}
.col-span-2 { grid-column: span 2; }

.field-group { display: flex; flex-direction: column; }

.field-label {
  display: flex; align-items: center; gap: 6px;
  font-size: 12.5px; font-weight: 600;
  color: var(--text-2);
  margin-bottom: 7px;
}
.field-required { color: var(--red); font-size: 13px; }
.field-optional { font-size: 11px; font-weight: 400; color: var(--text-3); }

/* Input wrap */
.input-wrap {
  position: relative;
  display: flex;
  align-items: center;
  border: 1px solid var(--border);
  border-radius: var(--r-md);
  background: var(--surface);
  transition: border-color var(--ease), box-shadow var(--ease);
  box-shadow: var(--shadow-sm);
}
.input-wrap:focus-within {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px var(--accent-ring);
}
.input-wrap--error { border-color: var(--red); }
.input-wrap--error:focus-within { box-shadow: 0 0 0 3px rgba(239,68,68,.12); border-color: var(--red); }

.input-icon {
  position: absolute; left: 13px;
  color: var(--text-3); pointer-events: none; flex-shrink: 0;
}

.field-input {
  width: 100%;
  height: 42px;
  border: none;
  outline: none;
  background: transparent;
  padding: 0 14px 0 38px;
  font-size: 13.5px;
  color: var(--text-1);
  font-family: inherit;
}
.field-input::placeholder { color: var(--text-3); }
.field-input--mono { font-family: 'DM Mono', ui-monospace, monospace; font-size: 13px; }
.field-input--password { padding-right: 44px; }

/* Select wrap */
.select-wrap {
  position: relative;
  border: 1px solid var(--border);
  border-radius: var(--r-md);
  background: var(--surface);
  box-shadow: var(--shadow-sm);
  transition: border-color var(--ease), box-shadow var(--ease);
}
.select-wrap:focus-within {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px var(--accent-ring);
}
.field-select {
  width: 100%;
  height: 42px;
  border: none;
  outline: none;
  background: transparent;
  padding: 0 36px 0 38px;
  font-size: 13.5px;
  color: var(--text-1);
  font-family: inherit;
  appearance: none;
  cursor: pointer;
}
.select-chevron {
  position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
  color: var(--text-3); pointer-events: none;
}

/* Textarea */
.field-textarea {
  width: 100%;
  border: 1px solid var(--border);
  border-radius: var(--r-md);
  background: var(--surface);
  padding: 11px 14px;
  font-size: 13.5px;
  color: var(--text-1);
  font-family: inherit;
  resize: vertical;
  min-height: 88px;
  outline: none;
  transition: border-color var(--ease), box-shadow var(--ease);
  box-shadow: var(--shadow-sm);
  line-height: 1.6;
}
.field-textarea::placeholder { color: var(--text-3); }
.field-textarea:focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px var(--accent-ring);
}

/* Error text */
.field-error { font-size: 12px; color: var(--red); margin-top: 5px; }

/* Password toggle */
.password-toggle {
  position: absolute; right: 12px;
  background: none; border: none;
  color: var(--text-3); cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  padding: 4px;
  border-radius: var(--r-sm);
  transition: color var(--ease), background var(--ease);
}
.password-toggle:hover { color: var(--text-2); background: #f3f4f6; }

/* Password strength */
.password-strength {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 8px;
}
.strength-bars {
  display: flex; gap: 4px; flex: 1;
}
.strength-bar {
  height: 4px; flex: 1; border-radius: 99px;
  background: #e5e7eb;
  transition: background var(--ease);
}
.strength-bar--filled.strength-bar--weak   { background: var(--red); }
.strength-bar--filled.strength-bar--fair   { background: var(--amber, #d97706); }
.strength-bar--filled.strength-bar--good   { background: #f59e0b; }
.strength-bar--filled.strength-bar--strong { background: var(--green); }

.strength-label { font-size: 11.5px; font-weight: 600; white-space: nowrap; }
.strength-label--weak   { color: var(--red); }
.strength-label--fair   { color: #d97706; }
.strength-label--good   { color: #f59e0b; }
.strength-label--strong { color: var(--green); }

/* ════════════════════════════════════════════════════════════
   Admin Preview
════════════════════════════════════════════════════════════ */
.admin-preview {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px 18px;
  background: #fafafa;
  border: 1px solid var(--border);
  border-radius: var(--r-lg);
}
.admin-preview__avatar {
  width: 46px; height: 46px;
  border-radius: 50%;
  background: linear-gradient(135deg, #4f46e5, #818cf8);
  display: flex; align-items: center; justify-content: center;
  font-size: 15px; font-weight: 800; color: #fff;
  letter-spacing: .03em; flex-shrink: 0;
}
.admin-preview__name {
  font-size: 14px; font-weight: 700; color: var(--text-1);
  transition: color .1s;
}
.admin-preview__role { display: flex; align-items: center; gap: 8px; margin-top: 4px; }
.role-badge {
  font-size: 10.5px; font-weight: 700;
  background: var(--accent-lt); color: var(--accent);
  padding: 2px 8px; border-radius: 99px;
}
.admin-preview__email { font-size: 12px; color: var(--text-3); }

/* ════════════════════════════════════════════════════════════
   Security Note
════════════════════════════════════════════════════════════ */
.security-note {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 14px 16px;
  background: var(--green-lt);
  border: 1px solid #a7f3d0;
  border-radius: var(--r-lg);
  color: #065f46;
}
.security-note svg { flex-shrink: 0; margin-top: 1px; }
.security-note p { font-size: 13px; line-height: 1.55; }

/* ════════════════════════════════════════════════════════════
   Step Actions
════════════════════════════════════════════════════════════ */
.step-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding-top: 8px;
}
.step-actions__hint {
  display: flex; align-items: center; gap: 5px;
  font-size: 12px; color: var(--text-3);
}
.step-actions__req { color: var(--red); font-weight: 700; }

.btn-next {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 11px 24px;
  background: var(--accent); color: #fff;
  font-size: 14px; font-weight: 700;
  border-radius: var(--r-lg); border: none; cursor: pointer;
  font-family: inherit;
  transition: background var(--ease), transform var(--ease), box-shadow var(--ease);
  box-shadow: 0 2px 10px rgba(79,70,229,.3);
}
.btn-next:hover {
  background: var(--accent-hover);
  transform: translateY(-1px);
  box-shadow: 0 4px 16px rgba(79,70,229,.4);
}

.btn-back {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 11px 18px;
  background: transparent; color: var(--text-2);
  font-size: 14px; font-weight: 600;
  border-radius: var(--r-lg); border: 1px solid var(--border);
  cursor: pointer; font-family: inherit;
  transition: background var(--ease), color var(--ease);
}
.btn-back:hover { background: #f9fafb; color: var(--text-1); }

.btn-submit {
  display: inline-flex; align-items: center; gap: 9px;
  padding: 12px 28px;
  background: var(--accent); color: #fff;
  font-size: 14.5px; font-weight: 700;
  border-radius: var(--r-lg); border: none; cursor: pointer;
  font-family: inherit;
  transition: background var(--ease), transform var(--ease), box-shadow var(--ease);
  box-shadow: 0 3px 14px rgba(79,70,229,.35);
}
.btn-submit:hover:not(:disabled) {
  background: var(--accent-hover);
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(79,70,229,.45);
}
.btn-submit:disabled { opacity: .6; cursor: not-allowed; transform: none; }

.btn-spinner { animation: spin .7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* ════════════════════════════════════════════════════════════
   Footer
════════════════════════════════════════════════════════════ */
.form-footer {
  text-align: center;
  font-size: 13.5px;
  color: var(--text-3);
  margin-top: 24px;
}
.form-footer__link {
  color: var(--accent);
  font-weight: 600;
  text-decoration: none;
  transition: color var(--ease);
}
.form-footer__link:hover { color: var(--accent-hover); }

/* ════════════════════════════════════════════════════════════
   Responsive
════════════════════════════════════════════════════════════ */
@media (max-width: 640px) {
  .right-panel { padding: 24px 16px 48px; }
  .form-section { padding: 22px 18px; }
  .field-grid { grid-template-columns: 1fr; }
  .col-span-2 { grid-column: span 1; }
  .step-progress { flex-direction: column; gap: 14px; }
  .step-connector { display: none; }
  .step-actions { flex-direction: column; align-items: stretch; }
  .btn-next, .btn-submit { justify-content: center; width: 100%; }
  .btn-back { justify-content: center; }
  .step-actions__hint { justify-content: center; }
}
</style>