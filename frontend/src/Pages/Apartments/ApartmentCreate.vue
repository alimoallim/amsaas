<template>
  
    <div class="compact-page">

      <!-- ═══════════════════════════════════════════════════════════════ -->
      <!-- COMPACT HEADER                                                   -->
      <!-- ═══════════════════════════════════════════════════════════════ -->
      <div class="compact-header">
        <div class="compact-header-left">
          <div class="compact-breadcrumb">
            <RouterLink :to="{ name: 'Apartments' }" class="breadcrumb-link">Apartments</RouterLink>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="9 18 15 12 9 6"/>
            </svg>
            <span class="breadcrumb-current">Create Apartment</span>
          </div>
          <h1 class="compact-title">Create Apartment</h1>
          <p class="compact-subtitle">Register a new apartment inventory unit into the ERP platform</p>
        </div>
        <div class="compact-header-right">
          <div v-if="loading" class="saving-indicator">
            <div class="saving-spinner"></div>
            <span>Saving apartment...</span>
          </div>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════ -->
      <!-- ALERTS                                                           -->
      <!-- ═══════════════════════════════════════════════════════════════ -->
      <div v-if="errorMessage" class="alert-error">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        {{ errorMessage }}
      </div>
      <div v-if="successMessage" class="alert-success">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="20 6 9 17 4 12"/>
        </svg>
        {{ successMessage }}
      </div>

      <!-- ═══════════════════════════════════════════════════════════════ -->
      <!-- COMPACT FORM                                                     -->
      <!-- ═══════════════════════════════════════════════════════════════ -->
      <form @submit.prevent="submitForm" class="compact-form">

        <!-- SECTION: Unit Information -->
        <div class="form-section">
          <div class="section-header">
            <div class="section-icon">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2"/>
                <line x1="9" y1="3" x2="9" y2="21"/>
                <line x1="15" y1="3" x2="15" y2="21"/>
              </svg>
            </div>
            <h3 class="section-title">Unit Information</h3>
            <span class="section-badge">Required</span>
          </div>
          <div class="compact-grid compact-grid--4">
            <div class="field-group">
              <label class="compact-label">Building *</label>
              <select v-model="form.building_id" class="compact-input" required>
                <option value="">Select Building</option>
                <option v-for="building in buildings" :key="building.id" :value="building.id">
                  {{ building.name }}
                </option>
              </select>
            </div>
            <div class="field-group">
              <label class="compact-label">Unit Number *</label>
              <input v-model="form.unit_number" type="text" class="compact-input" placeholder="e.g., A-101" required>
            </div>
            <div class="field-group">
              <label class="compact-label">Property Type</label>
              <select v-model="form.property_type" class="compact-input">
                <option value="apartment">Apartment</option>
                <option value="villa">Villa</option>
                <option value="office">Office</option>
                <option value="shop">Shop</option>
                <option value="warehouse">Warehouse</option>
              </select>
            </div>
            <div class="field-group">
              <label class="compact-label">Floor</label>
              <input v-model.number="form.floor" type="number" class="compact-input" placeholder="Floor #">
            </div>
          </div>
        </div>

        <!-- SECTION: Layout & Structure -->
        <div class="form-section">
          <div class="section-header">
            <div class="section-icon">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="4" y="4" width="16" height="16" rx="2"/>
                <line x1="8" y1="4" x2="8" y2="20"/>
                <line x1="16" y1="4" x2="16" y2="20"/>
              </svg>
            </div>
            <h3 class="section-title">Layout & Structure</h3>
          </div>
          <div class="compact-grid compact-grid--3">
            <div class="field-group field-group--inline">
              <label class="compact-label compact-label--sm">Bedrooms</label>
              <div class="inline-control">
                <button type="button" class="inline-btn" @click="decrement('bedrooms')">−</button>
                <input v-model.number="form.bedrooms" type="number" class="compact-input compact-input--inline" min="0" max="20">
                <button type="button" class="inline-btn" @click="increment('bedrooms')">+</button>
              </div>
            </div>
            <div class="field-group field-group--inline">
              <label class="compact-label compact-label--sm">Bathrooms</label>
              <div class="inline-control">
                <button type="button" class="inline-btn" @click="decrement('bathrooms')">−</button>
                <input v-model.number="form.bathrooms" type="number" class="compact-input compact-input--inline" min="0" max="20">
                <button type="button" class="inline-btn" @click="increment('bathrooms')">+</button>
              </div>
            </div>
            <div class="field-group">
              <label class="compact-label compact-label--sm">Area (sqm)</label>
              <input v-model.number="form.area_sqm" type="number" step="0.01" class="compact-input" placeholder="Total area">
            </div>
          </div>
        </div>

        <!-- SECTION: Listing & Inventory -->
        <div class="form-section">
          <div class="section-header">
            <div class="section-icon">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 6h18M9 12h6M7 18h10"/>
                <rect x="2" y="3" width="20" height="18" rx="2"/>
              </svg>
            </div>
            <h3 class="section-title">Listing & Inventory</h3>
          </div>
          <div class="compact-grid compact-grid--3">
            <div class="field-group">
              <label class="compact-label compact-label--sm">Listing Type</label>
              <div class="pill-select">
                <button type="button" @click="form.listing_type = 'rental'" :class="['pill-option', { 'pill-active': form.listing_type === 'rental' }]">Rental</button>
                <button type="button" @click="form.listing_type = 'sale'" :class="['pill-option', { 'pill-active': form.listing_type === 'sale' }]">Sale</button>
                <button type="button" @click="form.listing_type = 'hybrid'" :class="['pill-option', { 'pill-active': form.listing_type === 'hybrid' }]">Hybrid</button>
                <button type="button" @click="form.listing_type = 'not_listed'" :class="['pill-option', { 'pill-active': form.listing_type === 'not_listed' }]">Not Listed</button>
              </div>
            </div>
            <div class="field-group">
              <label class="compact-label compact-label--sm">Inventory Status</label>
              <select v-model="form.inventory_status" class="compact-input">
                <option value="available">Available</option>
                <option value="reserved">Reserved</option>
                <option value="maintenance">Maintenance</option>
                <option value="blocked">Blocked</option>
              </select>
            </div>
            <div class="field-group">
              <label class="compact-label compact-label--sm">Currency</label>
              <div class="currency-toggle">
                <button type="button" @click="form.currency = 'USD'" :class="['currency-option', { 'currency-active': form.currency === 'USD' }]">USD</button>
                <button type="button" @click="form.currency = 'SOS'" :class="['currency-option', { 'currency-active': form.currency === 'SOS' }]">SOS</button>
              </div>
            </div>
          </div>
        </div>

        <!-- SECTION: Pricing (Conditional) -->
        <div class="form-section" v-if="form.listing_type !== 'not_listed'">
          <div class="section-header">
            <div class="section-icon">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="6" x2="12" y2="12"/>
                <line x1="12" y1="12" x2="16" y2="14"/>
              </svg>
            </div>
            <h3 class="section-title">{{ pricingSectionTitle }}</h3>
          </div>
          <p v-if="pricingHint" class="mb-3 text-xs text-slate-500">{{ pricingHint }}</p>
          <div class="compact-grid compact-grid--3">
            <div class="field-group" v-if="form.listing_type === 'rental' || form.listing_type === 'hybrid'">
              <label class="compact-label compact-label--sm">
                Market Rent Price <span class="text-red-500">*</span>
              </label>
              <div class="input-prefix">
                <span class="prefix-symbol">{{ form.currency === 'USD' ? '$' : 'Sh' }}</span>
                <input
                  v-model.number="form.market_rent_price"
                  type="number"
                  step="0.01"
                  min="0.01"
                  class="compact-input compact-input--prefix"
                  :class="{ 'input-error': fieldError('market_rent_price') }"
                  placeholder="0.00"
                >
              </div>
              <p v-if="fieldError('market_rent_price')" class="field-error">{{ fieldError('market_rent_price') }}</p>
            </div>
            <div class="field-group" v-if="form.listing_type === 'sale' || form.listing_type === 'hybrid'">
              <label class="compact-label compact-label--sm">
                Market Sale Price <span class="text-red-500">*</span>
              </label>
              <div class="input-prefix">
                <span class="prefix-symbol">{{ form.currency === 'USD' ? '$' : 'Sh' }}</span>
                <input
                  v-model.number="form.market_sale_price"
                  type="number"
                  step="0.01"
                  min="0.01"
                  class="compact-input compact-input--prefix"
                  :class="{ 'input-error': fieldError('market_sale_price') }"
                  placeholder="0.00"
                >
              </div>
              <p v-if="fieldError('market_sale_price')" class="field-error">{{ fieldError('market_sale_price') }}</p>
            </div>
            <div
              v-if="form.listing_type === 'rental' || form.listing_type === 'hybrid'"
              class="field-group"
            >
              <label class="compact-label compact-label--sm">Security Deposit</label>
              <div class="input-prefix">
                <span class="prefix-symbol">{{ form.currency === 'USD' ? '$' : 'Sh' }}</span>
                <input v-model.number="form.security_deposit" type="number" step="0.01" class="compact-input compact-input--prefix" placeholder="0.00">
              </div>
            </div>
          </div>
        </div>

        <!-- SECTION: Features -->
        <div class="form-section">
          <div class="section-header">
            <div class="section-icon">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
              </svg>
            </div>
            <h3 class="section-title">Features</h3>
          </div>
          <div class="feature-grid">
            <label class="feature-item">
              <input v-model="form.has_balcony" type="checkbox" class="feature-check">
              <span class="feature-name">Balcony</span>
            </label>
            <label class="feature-item">
              <input v-model="form.has_parking" type="checkbox" class="feature-check">
              <span class="feature-name">Parking</span>
            </label>
            <label class="feature-item">
              <input v-model="form.has_storage" type="checkbox" class="feature-check">
              <span class="feature-name">Storage</span>
            </label>
            <label class="feature-item">
              <input v-model="form.is_furnished" type="checkbox" class="feature-check">
              <span class="feature-name">Furnished</span>
            </label>
          </div>
        </div>

        <!-- SECTION: Notes -->
        <div class="form-section">
          <div class="section-header">
            <div class="section-icon">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
              </svg>
            </div>
            <h3 class="section-title">Notes</h3>
          </div>
          <textarea v-model="form.notes" rows="3" class="compact-textarea" placeholder="Apartment notes, special instructions, or remarks..."></textarea>
        </div>

        <!-- FORM ACTIONS -->
        <div class="form-actions">
          <p class="footer-note">Apartment inventory records are company-isolated and audit tracked.</p>
          <div class="actions-group">
            <RouterLink :to="{ name: 'Apartments' }" class="btn-secondary">
              Cancel
            </RouterLink>
            <button type="submit" :disabled="loading" class="btn-primary">
              <span v-if="loading" class="btn-spinner"></span>
              <span v-else>Create Apartment</span>
            </button>
          </div>
        </div>

      </form>
    </div>
 
</template>

<script setup>
import { computed, reactive, ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../services/api'
import DashboardLayout from '../../layouts/DashboardLayout.vue'

const router = useRouter()
const loading = ref(false)
const buildings = ref([])
const errorMessage = ref('')
const successMessage = ref('')
const fieldErrors = ref({})

const form = reactive({
  building_id: '',
  unit_number: '',
  floor: null,
  property_type: 'apartment',
  bedrooms: 1,
  bathrooms: 1,
  area_sqm: null,
  listing_type: 'rental',
  inventory_status: 'available',
  market_rent_price: null,
  market_sale_price: null,
  security_deposit: null,
  currency: 'USD',
  has_balcony: false,
  has_parking: false,
  has_storage: false,
  is_furnished: false,
  notes: '',
})

const pricingSectionTitle = computed(() => {
  if (form.listing_type === 'sale') return 'Sale pricing'
  if (form.listing_type === 'rental') return 'Rental pricing'
  if (form.listing_type === 'hybrid') return 'Rental & sale pricing'
  return 'Pricing'
})

const pricingHint = computed(() => {
  if (form.listing_type === 'sale') {
    return 'Enter the asking sale price for this unit.'
  }
  if (form.listing_type === 'rental') {
    return 'Enter the monthly market rent for this unit.'
  }
  if (form.listing_type === 'hybrid') {
    return 'Enter both monthly rent and sale price for this hybrid listing.'
  }
  return ''
})

// Helpers
const increment = (field) => { if (form[field] < 20) form[field]++ }
const decrement = (field) => { if (form[field] > 0) form[field]-- }

function fieldError(key) {
  const e = fieldErrors.value[key]
  return Array.isArray(e) ? e[0] : e || ''
}

function priceForPayload(value) {
  const n = Number(value)
  return Number.isFinite(n) && n > 0 ? n : null
}

function validatePricing() {
  const errors = {}
  const needsRent = form.listing_type === 'rental' || form.listing_type === 'hybrid'
  const needsSale = form.listing_type === 'sale' || form.listing_type === 'hybrid'

  if (needsRent && !priceForPayload(form.market_rent_price)) {
    errors.market_rent_price = 'Market rent price is required for rental listings.'
  }
  if (needsSale && !priceForPayload(form.market_sale_price)) {
    errors.market_sale_price = 'Market sale price is required for sale listings.'
  }

  fieldErrors.value = errors
  return Object.keys(errors).length === 0
}

watch(
  () => form.listing_type,
  (type) => {
    if (type !== 'rental' && type !== 'hybrid') form.market_rent_price = null
    if (type !== 'sale' && type !== 'hybrid') form.market_sale_price = null
    fieldErrors.value = {}
  },
)

// API Methods (unchanged)
const fetchBuildings = async () => {
  try {
    const response = await api.get('/buildings')
    buildings.value = response.data.data || []
  } catch (error) {
    console.error(error)
    errorMessage.value = 'Failed to load buildings.'
  }
}

const submitForm = async () => {
  loading.value = true
  errorMessage.value = ''
  successMessage.value = ''
  fieldErrors.value = {}

  if (!validatePricing()) {
    errorMessage.value = 'Please enter the required pricing for the selected listing type.'
    loading.value = false
    return
  }

  try {
    const payload = {
      ...form,
      floor: form.floor || null,
      area_sqm: form.area_sqm || null,
      market_rent_price:
        form.listing_type === 'rental' || form.listing_type === 'hybrid'
          ? priceForPayload(form.market_rent_price)
          : null,
      market_sale_price:
        form.listing_type === 'sale' || form.listing_type === 'hybrid'
          ? priceForPayload(form.market_sale_price)
          : null,
    }

    const response = await api.post('/apartments', payload)
    successMessage.value = response.data.message || 'Apartment created successfully.'

    setTimeout(() => {
      router.push({ name: 'Apartments' })
    }, 1200)
  } catch (error) {
    console.error(error)
    if (error?.response?.status === 422) {
      fieldErrors.value = error.response.data.errors ?? {}
      const firstField = Object.values(fieldErrors.value).flat()[0]
      errorMessage.value =
        firstField || error.response.data.message || 'Please fix the highlighted fields.'
      return
    }
    errorMessage.value = error?.response?.data?.message || 'Failed to create apartment.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchBuildings()
})
</script>

<style scoped>
/* ═══════════════════════════════════════════════════════════════════════════ */
/* COMPACT DESIGN SYSTEM — PRODUCTION GRADE                                   */
/* ═══════════════════════════════════════════════════════════════════════════ */

.compact-page {
  max-width: 1280px;
  margin: 0 auto;
  padding: 1.25rem 1.5rem;
  background: #f8fafc;
  min-height: 100vh;
}

/* ────────────────────────────────────────────────────────────────────────── */
/* HEADER                                                                      */
/* ────────────────────────────────────────────────────────────────────────── */

.compact-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.compact-breadcrumb {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.75rem;
  margin-bottom: 0.5rem;
  color: #64748b;
}

.breadcrumb-link {
  color: #3b82f6;
  text-decoration: none;
}
.breadcrumb-link:hover { text-decoration: underline; }

.breadcrumb-current { color: #1e293b; }

.compact-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #0f172a;
  letter-spacing: -0.02em;
  margin: 0;
}

.compact-subtitle {
  font-size: 0.8rem;
  color: #64748b;
  margin-top: 0.25rem;
}

.saving-indicator {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: #eff6ff;
  border-radius: 2rem;
  font-size: 0.75rem;
  font-weight: 500;
  color: #2563eb;
}

.saving-spinner {
  width: 0.875rem;
  height: 0.875rem;
  border: 2px solid #bfdbfe;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ────────────────────────────────────────────────────────────────────────── */
/* ALERTS                                                                     */
/* ────────────────────────────────────────────────────────────────────────── */

.alert-error, .alert-success {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border-radius: 0.75rem;
  font-size: 0.75rem;
  margin-bottom: 1.5rem;
}
.alert-error {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #dc2626;
}
.alert-success {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  color: #16a34a;
}

/* ────────────────────────────────────────────────────────────────────────── */
/* MAIN FORM                                                                  */
/* ────────────────────────────────────────────────────────────────────────── */

.compact-form {
  background: white;
  border-radius: 1rem;
  border: 1px solid #e2e8f0;
  overflow: hidden;
  padding: 1.5rem;
}

/* Form Sections */
.form-section {
  margin-bottom: 1.75rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #f1f5f9;
}
.form-section:last-of-type { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }

.section-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.section-icon {
  width: 1.75rem;
  height: 1.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #eef2ff;
  border-radius: 0.5rem;
  color: #3b82f6;
}

.section-title {
  font-size: 0.8rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #334155;
  margin: 0;
}

.section-badge {
  font-size: 0.6rem;
  padding: 0.125rem 0.5rem;
  background: #f1f5f9;
  border-radius: 2rem;
  color: #64748b;
  margin-left: auto;
}

/* Grid System */
.compact-grid {
  display: grid;
  gap: 1rem;
}
.compact-grid--4 { grid-template-columns: repeat(4, 1fr); }
.compact-grid--3 { grid-template-columns: repeat(3, 1fr); }
@media (max-width: 768px) {
  .compact-grid--4, .compact-grid--3 { grid-template-columns: 1fr; }
}

/* Form Elements */
.field-group { display: flex; flex-direction: column; gap: 0.375rem; }
.field-group--inline { flex-direction: row; align-items: center; justify-content: space-between; }

.compact-label {
  font-size: 0.7rem;
  font-weight: 600;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}
.compact-label--sm { font-size: 0.65rem; }

.compact-input {
  padding: 0.5rem 0.75rem;
  font-size: 0.8rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  background: white;
  transition: all 0.15s;
  outline: none;
}
.compact-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,0.1); }
.compact-input--inline { width: 60px; text-align: center; }

.compact-textarea {
  width: 100%;
  padding: 0.6rem 0.75rem;
  font-size: 0.8rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  resize: vertical;
  font-family: inherit;
}
.compact-textarea:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,0.1); }

/* Inline Controls */
.inline-control {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  background: #f8fafc;
  border-radius: 0.5rem;
  border: 1px solid #e2e8f0;
}
.inline-btn {
  width: 1.75rem;
  height: 1.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: none;
  font-size: 1rem;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
  border-radius: 0.375rem;
}
.inline-btn:hover { background: #e2e8f0; color: #1e293b; }

/* Pill Select */
.pill-select {
  display: flex;
  gap: 0.25rem;
  background: #f1f5f9;
  padding: 0.25rem;
  border-radius: 0.5rem;
  flex-wrap: wrap;
}
.pill-option {
  flex: 1;
  padding: 0.375rem 0.5rem;
  font-size: 0.7rem;
  font-weight: 600;
  background: transparent;
  border: none;
  border-radius: 0.375rem;
  cursor: pointer;
  color: #64748b;
  transition: all 0.1s;
  white-space: nowrap;
}
.pill-active {
  background: white;
  color: #3b82f6;
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

/* Currency Toggle */
.currency-toggle {
  display: flex;
  gap: 0.25rem;
  background: #f1f5f9;
  padding: 0.25rem;
  border-radius: 0.5rem;
  width: fit-content;
}
.currency-option {
  padding: 0.375rem 0.75rem;
  font-size: 0.7rem;
  font-weight: 600;
  background: transparent;
  border: none;
  border-radius: 0.375rem;
  cursor: pointer;
  color: #64748b;
}
.currency-active {
  background: white;
  color: #3b82f6;
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

/* Input Prefix */
.input-prefix {
  position: relative;
  display: flex;
  align-items: center;
}
.prefix-symbol {
  position: absolute;
  left: 0.75rem;
  font-size: 0.75rem;
  font-weight: 500;
  color: #64748b;
}
.compact-input--prefix {
  padding-left: 1.75rem;
  width: 100%;
}
.input-error {
  border-color: #f87171 !important;
}
.field-error {
  margin-top: 0.25rem;
  font-size: 0.7rem;
  color: #dc2626;
}

/* Feature Grid */
.feature-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0.75rem;
}
@media (max-width: 640px) { .feature-grid { grid-template-columns: repeat(2, 1fr); } }

.feature-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  cursor: pointer;
  transition: all 0.1s;
}
.feature-item:hover { background: #f1f5f9; border-color: #cbd5e1; }

.feature-check {
  width: 1rem;
  height: 1rem;
  accent-color: #3b82f6;
  cursor: pointer;
}

.feature-name {
  font-size: 0.75rem;
  font-weight: 500;
  color: #334155;
}

/* Form Actions */
.form-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 1.75rem;
  padding-top: 1.25rem;
  border-top: 1px solid #e2e8f0;
}

.footer-note {
  font-size: 0.7rem;
  color: #94a3b8;
  margin: 0;
}

.actions-group {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.btn-primary, .btn-secondary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1.25rem;
  border-radius: 0.5rem;
  font-size: 0.8rem;
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
  transition: all 0.15s;
  border: none;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}
.btn-primary:hover:not(:disabled) { background: #2563eb; transform: translateY(-1px); }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-secondary {
  background: white;
  color: #475569;
  border: 1px solid #e2e8f0;
}
.btn-secondary:hover { background: #f8fafc; border-color: #cbd5e1; }

.btn-spinner {
  width: 1rem;
  height: 1rem;
  border: 2px solid white;
  border-top-color: transparent;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}
</style>