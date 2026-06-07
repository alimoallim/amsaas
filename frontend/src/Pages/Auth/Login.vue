<template>
  <div class="login-root">

    <!-- ══════════════════════════════════════════════════════════
         LEFT — Brand Panel
    ══════════════════════════════════════════════════════════ -->
    <aside class="brand-panel" aria-hidden="true">

      <!-- Decorative mesh -->
      <div class="mesh" aria-hidden="true">
        <div class="mesh__orb mesh__orb--1"></div>
        <div class="mesh__orb mesh__orb--2"></div>
        <div class="mesh__orb mesh__orb--3"></div>
        <div class="mesh__grid"></div>
      </div>

      <!-- Brand -->
      <div class="brand-panel__brand">
        <div class="brand-mark">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
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
      <div class="brand-panel__hero">
        <div class="live-chip">
          <span class="live-chip__dot"></span>
          Enterprise Operations
        </div>
        <h1 class="hero-headline">
          One platform.<br/>Every property.
        </h1>
        <p class="hero-body">
          From lease agreements and utility billing to apartment sales and financial reporting — AMSAAS manages your entire real estate operation in a single, tenant-isolated workspace.
        </p>
      </div>

      <!-- Feature list -->
      <div class="feature-list">
        <div class="feature-item">
          <div class="feature-icon">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>
            </svg>
          </div>
          <div>
            <p class="feature-title">Multi-Building Management</p>
            <p class="feature-sub">Manage unlimited buildings, floors, and units</p>
          </div>
        </div>
        <div class="feature-item">
          <div class="feature-icon">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
            </svg>
          </div>
          <div>
            <p class="feature-title">Utility Billing Engine</p>
            <p class="feature-sub">Metered readings auto-converted to billable charges</p>
          </div>
        </div>
        <div class="feature-item">
          <div class="feature-icon">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>
            </svg>
          </div>
          <div>
            <p class="feature-title">Full Financial Operations</p>
            <p class="feature-sub">Invoices, payments, receivables, and audit trails</p>
          </div>
        </div>
        <div class="feature-item">
          <div class="feature-icon">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
          </div>
          <div>
            <p class="feature-title">Tenant-Isolated & Secure</p>
            <p class="feature-sub">UUID architecture with Sanctum-powered authentication</p>
          </div>
        </div>
      </div>

      <!-- Trust strip -->
      <div class="trust-strip">
        <div class="trust-item">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          Multi-tenant SaaS
        </div>
        <span class="trust-dot">·</span>
        <div class="trust-item">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          API-first
        </div>
        <span class="trust-dot">·</span>
        <div class="trust-item">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          Enterprise ready
        </div>
      </div>

    </aside>

    <!-- ══════════════════════════════════════════════════════════
         RIGHT — Login Form
    ══════════════════════════════════════════════════════════ -->
    <main class="form-panel">
      <div class="form-shell">

        <!-- Mobile brand -->
        <div class="mobile-brand">
          <div class="brand-mark brand-mark--dark">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <rect x="2" y="7" width="20" height="14" rx="2"/>
              <path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>
            </svg>
          </div>
          <div>
            <p class="brand-name brand-name--dark">AMSAAS</p>
            <p class="brand-tagline brand-tagline--dark">Property Platform</p>
          </div>
        </div>

        <!-- Heading -->
        <div class="form-heading">
          <h2 class="form-heading__title">Welcome back</h2>
          <p class="form-heading__sub">Sign in to your company workspace</p>
        </div>

        <!-- Error banner -->
        <Transition name="err-slide">
          <div v-if="errorMessage" class="error-banner" role="alert">
            <div class="error-banner__icon">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
              </svg>
            </div>
            <span>{{ errorMessage }}</span>
            <button class="error-banner__close" @click="errorMessage = ''" aria-label="Dismiss">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
              </svg>
            </button>
          </div>
        </Transition>

        <!-- Form -->
        <form class="login-form" @submit.prevent="submitLogin" novalidate>

          <!-- Email -->
          <div class="field-group">
            <label class="field-label" for="email">
              Email Address
            </label>
            <div class="input-wrap" :class="{ 'input-wrap--error': fieldErrors.email, 'input-wrap--focus': focusedField === 'email' }">
              <div class="input-wrap__icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
                </svg>
              </div>
              <input
                id="email"
                v-model="form.email"
                type="email"
                required
                autocomplete="email"
                placeholder="you@company.com"
                class="field-input"
                @focus="focusedField = 'email'; fieldErrors.email = ''"
                @blur="focusedField = null; validateEmail()"
              />
            </div>
            <Transition name="err-pop">
              <p v-if="fieldErrors.email" class="field-error">{{ fieldErrors.email }}</p>
            </Transition>
          </div>

          <!-- Password -->
          <div class="field-group">
            <div class="field-label-row">
              <label class="field-label" for="password">Password</label>
              <a href="#" class="forgot-link" tabindex="-1">Forgot password?</a>
            </div>
            <div class="input-wrap" :class="{ 'input-wrap--error': fieldErrors.password, 'input-wrap--focus': focusedField === 'password' }">
              <div class="input-wrap__icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
              </div>
              <input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                required
                autocomplete="current-password"
                placeholder="Enter your password"
                class="field-input"
                @focus="focusedField = 'password'; fieldErrors.password = ''"
                @blur="focusedField = null; validatePassword()"
              />
              <button
                type="button"
                class="password-eye"
                @click="showPassword = !showPassword"
                :aria-label="showPassword ? 'Hide password' : 'Show password'"
              >
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
            <Transition name="err-pop">
              <p v-if="fieldErrors.password" class="field-error">{{ fieldErrors.password }}</p>
            </Transition>
          </div>

          <!-- Remember me -->
          <label class="remember-label">
            <div class="remember-checkbox" :class="{ 'remember-checkbox--checked': rememberMe }" @click="rememberMe = !rememberMe">
              <svg v-if="rememberMe" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
            </div>
            <input v-model="rememberMe" type="checkbox" class="sr-only" />
            <span class="remember-text">Keep me signed in</span>
          </label>

          <!-- Submit -->
          <button type="submit" :disabled="isSubmitting" class="submit-btn">
            <svg v-if="isSubmitting" class="submit-btn__spinner" width="16" height="16" viewBox="0 0 24 24" fill="none">
              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="32" stroke-linecap="round"/>
            </svg>
            <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
            </svg>
            {{ isSubmitting ? 'Signing in…' : 'Sign In' }}
          </button>

        </form>

        <!-- Divider -->
        <div class="form-divider">
          <span class="form-divider__line"></span>
          <span class="form-divider__text">New to AMSAAS?</span>
          <span class="form-divider__line"></span>
        </div>

        <!-- Register link -->
        <router-link to="/onboarding/company" class="register-link">
          Create your company workspace
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
          </svg>
        </router-link>

        <!-- Footer note -->
        <p class="form-footer">
          By signing in you agree to the
          <a href="#" class="footer-link">Terms of Service</a>
          and
          <a href="#" class="footer-link">Privacy Policy</a>
        </p>

      </div>
    </main>

  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router    = useRouter()
const authStore = useAuthStore()

/* ── State ─────────────────────────────────────────────────── */
const isSubmitting = ref(false)
const errorMessage = ref('')
const showPassword = ref(false)
const rememberMe   = ref(false)
const focusedField = ref(null)

const form = reactive({ email: '', password: '' })

const fieldErrors = reactive({ email: '', password: '' })

/* ── Validation ─────────────────────────────────────────────── */
const validateEmail = () => {
  if (!form.email) { fieldErrors.email = 'Email address is required'; return false }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) { fieldErrors.email = 'Enter a valid email address'; return false }
  fieldErrors.email = ''; return true
}

const validatePassword = () => {
  if (!form.password) { fieldErrors.password = 'Password is required'; return false }
  if (form.password.length < 6) { fieldErrors.password = 'Password must be at least 6 characters'; return false }
  fieldErrors.password = ''; return true
}

/* ── Submit ─────────────────────────────────────────────────── */
const submitLogin = async () => {
  const emailOk    = validateEmail()
  const passwordOk = validatePassword()
  if (!emailOk || !passwordOk) return

  isSubmitting.value = true
  errorMessage.value = ''

  try {
    await authStore.login(form)
    router.push('/dashboard')
  } catch (error) {
    errorMessage.value =
      error.response?.data?.message ||
      'Invalid email or password. Please try again.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<style scoped>
/* ════════════════════════════════════════════════════════════
   Tokens
════════════════════════════════════════════════════════════ */
.login-root {
  --accent:       #4f46e5;
  --accent-hover: #4338ca;
  --accent-ring:  rgba(79,70,229,.16);

  --border:  #e5e7eb;
  --surface: #ffffff;
  --app-bg:  #f4f6f9;

  --text-1: #111827;
  --text-2: #4b5563;
  --text-3: #9ca3af;

  --red:    #ef4444;
  --red-lt: #fef2f2;

  --r-sm:  6px;  --r-md:  10px;
  --r-lg:  14px; --r-xl:  20px;
  --r-2xl: 26px;

  --shadow-panel: 0 24px 80px rgba(0,0,0,.12), 0 2px 8px rgba(0,0,0,.06);
  --ease: 160ms cubic-bezier(.4,0,.2,1);
  --spring: 300ms cubic-bezier(.34,1.26,.64,1);

  font-family: 'DM Sans', ui-sans-serif, system-ui, sans-serif;
  min-height: 100vh;
  display: flex;
  background: var(--app-bg);
}

/* ════════════════════════════════════════════════════════════
   Brand Panel (Left)
════════════════════════════════════════════════════════════ */
.brand-panel {
  display: none;
  position: relative;
  flex-direction: column;
  justify-content: space-between;
  width: 420px;
  flex-shrink: 0;
  background: linear-gradient(160deg, #1e1b4b 0%, #1d4ed8 55%, #0c4a6e 100%);
  padding: 44px 40px;
  overflow: hidden;
}
@media (min-width: 1024px) { .brand-panel { display: flex; } }

/* Decorative mesh */
.mesh { position: absolute; inset: 0; pointer-events: none; }
.mesh__orb {
  position: absolute; border-radius: 50%;
  filter: blur(70px); opacity: .22;
}
.mesh__orb--1 { width: 400px; height: 400px; background: #818cf8; top: -100px; left: -100px; }
.mesh__orb--2 { width: 320px; height: 320px; background: #06b6d4; bottom: -60px; right: -80px; }
.mesh__orb--3 { width: 220px; height: 220px; background: #a78bfa; top: 45%; left: 30%; }
.mesh__grid {
  position: absolute; inset: 0;
  background-image:
    linear-gradient(rgba(255,255,255,.04) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,.04) 1px, transparent 1px);
  background-size: 32px 32px;
}

/* Brand */
.brand-panel__brand {
  display: flex; align-items: center; gap: 14px;
  position: relative; z-index: 1;
}
.brand-mark {
  width: 46px; height: 46px; border-radius: var(--r-lg);
  background: rgba(255,255,255,.15);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255,255,255,.2);
  display: flex; align-items: center; justify-content: center;
  color: #fff; flex-shrink: 0;
}
.brand-mark--dark {
  background: var(--accent);
  border: none;
  box-shadow: 0 2px 12px rgba(79,70,229,.4);
}
.brand-name { font-size: 17px; font-weight: 800; color: #fff; letter-spacing: .05em; }
.brand-name--dark { color: var(--text-1); }
.brand-tagline { font-size: 11px; color: rgba(255,255,255,.55); margin-top: 1px; }
.brand-tagline--dark { color: var(--text-3); }

/* Hero copy */
.brand-panel__hero { position: relative; z-index: 1; }

.live-chip {
  display: inline-flex; align-items: center; gap: 8px;
  font-size: 11px; font-weight: 600; letter-spacing: .08em;
  text-transform: uppercase; color: rgba(255,255,255,.6);
  margin-bottom: 20px;
}
.live-chip__dot {
  width: 8px; height: 8px; border-radius: 50%;
  background: #34d399;
  box-shadow: 0 0 0 3px rgba(52,211,153,.25);
  animation: live-pulse 2s ease infinite;
}
@keyframes live-pulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(52,211,153,.4); }
  50%       { box-shadow: 0 0 0 6px rgba(52,211,153,0); }
}

.hero-headline {
  font-size: 38px; font-weight: 800;
  line-height: 1.15; letter-spacing: -.03em;
  color: #fff;
}
.hero-body {
  font-size: 14.5px; color: rgba(255,255,255,.6);
  line-height: 1.75; margin-top: 18px;
}

/* Features */
.feature-list { display: flex; flex-direction: column; gap: 16px; position: relative; z-index: 1; }
.feature-item { display: flex; align-items: flex-start; gap: 14px; }
.feature-icon {
  width: 34px; height: 34px; border-radius: var(--r-md);
  background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.12);
  display: flex; align-items: center; justify-content: center;
  color: rgba(255,255,255,.8); flex-shrink: 0; margin-top: 1px;
}
.feature-title { font-size: 13.5px; font-weight: 700; color: #fff; }
.feature-sub   { font-size: 12px; color: rgba(255,255,255,.5); margin-top: 2px; line-height: 1.4; }

/* Trust strip */
.trust-strip { display: flex; align-items: center; gap: 10px; position: relative; z-index: 1; flex-wrap: wrap; }
.trust-item  { display: flex; align-items: center; gap: 5px; font-size: 11.5px; font-weight: 500; color: rgba(255,255,255,.4); }
.trust-dot   { color: rgba(255,255,255,.2); font-size: 14px; }

/* ════════════════════════════════════════════════════════════
   Form Panel (Right)
════════════════════════════════════════════════════════════ */
.form-panel {
  flex: 1; display: flex; align-items: center; justify-content: center;
  padding: 40px 24px 52px;
  overflow-y: auto;
}

.form-shell {
  width: 100%; max-width: 420px;
  display: flex; flex-direction: column; gap: 0;
}

/* Mobile brand */
.mobile-brand {
  display: flex; align-items: center; gap: 12px;
  margin-bottom: 36px;
}
@media (min-width: 1024px) { .mobile-brand { display: none; } }

/* Heading */
.form-heading { margin-bottom: 28px; }
.form-heading__title {
  font-size: 28px; font-weight: 800;
  letter-spacing: -.03em; color: var(--text-1); line-height: 1.2;
}
.form-heading__sub { font-size: 14px; color: var(--text-3); margin-top: 6px; }

/* ── Error Banner ───────────────────────────────────────────── */
.error-banner {
  display: flex; align-items: center; gap: 11px;
  padding: 12px 16px;
  background: var(--red-lt); border: 1px solid #fecaca;
  border-radius: var(--r-lg);
  color: var(--red); font-size: 13.5px; font-weight: 500;
  margin-bottom: 22px;
}
.error-banner__icon { display: flex; flex-shrink: 0; }
.error-banner__close {
  margin-left: auto; background: none; border: none;
  color: var(--red); cursor: pointer; opacity: .7; padding: 2px;
  display: flex; transition: opacity var(--ease);
}
.error-banner__close:hover { opacity: 1; }

/* ── Form ───────────────────────────────────────────────────── */
.login-form { display: flex; flex-direction: column; gap: 18px; margin-bottom: 24px; }

.field-group { display: flex; flex-direction: column; gap: 6px; }

.field-label {
  font-size: 13px; font-weight: 600; color: var(--text-2);
}
.field-label-row {
  display: flex; align-items: center; justify-content: space-between;
}

.forgot-link {
  font-size: 12.5px; font-weight: 500; color: var(--accent);
  text-decoration: none; transition: color var(--ease);
}
.forgot-link:hover { color: var(--accent-hover); }

/* Input wrap */
.input-wrap {
  position: relative; display: flex; align-items: center;
  border: 1.5px solid var(--border);
  border-radius: var(--r-lg);
  background: var(--surface);
  transition: border-color var(--ease), box-shadow var(--ease);
  box-shadow: 0 1px 2px rgba(0,0,0,.04);
}
.input-wrap--focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px var(--accent-ring);
}
.input-wrap--error { border-color: var(--red); }
.input-wrap--error.input-wrap--focus { box-shadow: 0 0 0 3px rgba(239,68,68,.12); }

.input-wrap__icon {
  position: absolute; left: 14px;
  color: var(--text-3); display: flex; pointer-events: none;
  transition: color var(--ease);
}
.input-wrap--focus .input-wrap__icon { color: var(--accent); }

.field-input {
  width: 100%; height: 46px;
  border: none; outline: none; background: transparent;
  padding: 0 14px 0 42px;
  font-size: 14px; color: var(--text-1); font-family: inherit;
  border-radius: var(--r-lg);
}
.field-input::placeholder { color: var(--text-3); }

/* Password eye toggle */
.password-eye {
  position: absolute; right: 12px;
  background: none; border: none; padding: 4px;
  color: var(--text-3); cursor: pointer; display: flex;
  border-radius: var(--r-sm);
  transition: color var(--ease), background var(--ease);
}
.password-eye:hover { color: var(--text-2); background: #f3f4f6; }

.field-error {
  font-size: 12px; color: var(--red);
  display: flex; align-items: center; gap: 4px;
}
.field-error::before { content: '↑'; font-size: 10px; }

/* Remember me */
.remember-label {
  display: flex; align-items: center; gap: 10px;
  cursor: pointer; user-select: none;
}
.sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0,0,0,0); }

.remember-checkbox {
  width: 18px; height: 18px; border-radius: 5px;
  border: 1.5px solid var(--border);
  background: var(--surface);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  transition: border-color var(--ease), background var(--ease);
}
.remember-checkbox--checked { background: var(--accent); border-color: var(--accent); color: #fff; }
.remember-text { font-size: 13.5px; color: var(--text-2); }

/* Submit button */
.submit-btn {
  display: flex; align-items: center; justify-content: center; gap: 9px;
  width: 100%; height: 48px;
  background: var(--accent); color: #fff;
  font-size: 14.5px; font-weight: 700;
  border: none; border-radius: var(--r-lg);
  cursor: pointer; font-family: inherit;
  transition: background var(--ease), transform var(--ease), box-shadow var(--ease);
  box-shadow: 0 2px 12px rgba(79,70,229,.3);
  margin-top: 4px;
}
.submit-btn:hover:not(:disabled) {
  background: var(--accent-hover);
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(79,70,229,.4);
}
.submit-btn:active:not(:disabled) { transform: translateY(0); }
.submit-btn:disabled { opacity: .6; cursor: not-allowed; transform: none; }

.submit-btn__spinner { animation: spin .7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* Divider */
.form-divider {
  display: flex; align-items: center; gap: 12px;
  margin: 24px 0 16px;
}
.form-divider__line { flex: 1; height: 1px; background: var(--border); }
.form-divider__text { font-size: 12.5px; color: var(--text-3); white-space: nowrap; }

/* Register link */
.register-link {
  display: flex; align-items: center; justify-content: center; gap: 7px;
  padding: 12px 20px; border-radius: var(--r-lg);
  border: 1.5px solid var(--border);
  background: var(--surface);
  font-size: 13.5px; font-weight: 600; color: var(--accent);
  text-decoration: none;
  transition: border-color var(--ease), background var(--ease), box-shadow var(--ease);
}
.register-link:hover {
  border-color: var(--accent);
  background: #f5f5ff;
  box-shadow: 0 0 0 3px var(--accent-ring);
}

/* Footer */
.form-footer {
  font-size: 12px; color: var(--text-3); text-align: center;
  margin-top: 20px; line-height: 1.6;
}
.footer-link {
  color: var(--accent); text-decoration: none; font-weight: 500;
  transition: color var(--ease);
}
.footer-link:hover { color: var(--accent-hover); }

/* ════════════════════════════════════════════════════════════
   Transitions
════════════════════════════════════════════════════════════ */
.err-slide-enter-active { transition: opacity .2s ease, transform .2s ease, max-height .2s ease; }
.err-slide-leave-active { transition: opacity .15s ease, max-height .15s ease; }
.err-slide-enter-from   { opacity: 0; transform: translateY(-6px); max-height: 0; }
.err-slide-leave-to     { opacity: 0; max-height: 0; }

.err-pop-enter-active { transition: opacity .18s ease, transform .15s ease; }
.err-pop-leave-active { transition: opacity .1s ease; }
.err-pop-enter-from   { opacity: 0; transform: translateY(-3px); }
.err-pop-leave-to     { opacity: 0; }

/* ════════════════════════════════════════════════════════════
   Responsive
════════════════════════════════════════════════════════════ */
@media (max-width: 640px) {
  .form-panel  { padding: 28px 16px 40px; }
  .form-shell  { max-width: 100%; }
}
</style>