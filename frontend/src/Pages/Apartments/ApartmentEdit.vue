<template>
    <div class="compact-page">

      <!-- ═══════════════════════════════════════════════════════════════ -->
      <!-- COMPACT HEADER WITH ACTION BAR                                   -->
      <!-- ═══════════════════════════════════════════════════════════════ -->
      <div class="compact-header">
        <div class="compact-header-left">
          <div class="compact-breadcrumb">
            <RouterLink :to="{ name: 'Apartments' }" class="breadcrumb-link">Apartments</RouterLink>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="9 18 15 12 9 6"/>
            </svg>
            <span class="breadcrumb-current">Edit Apartment</span>
          </div>
          <h1 class="compact-title">Edit Apartment</h1>
          <p class="compact-subtitle">Update inventory, pricing, and operational information</p>
        </div>
        <div class="compact-header-right">
          <RouterLink :to="{ name: 'ApartmentShow', params: { id: apartmentId } }" class="btn-outline">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
            View
          </RouterLink>
          <RouterLink :to="{ name: 'Apartments' }" class="btn-secondary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="19" y1="12" x2="5" y2="12"/>
              <polyline points="12 19 5 12 12 5"/>
            </svg>
            Back
          </RouterLink>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════ -->
      <!-- LOADING STATE                                                    -->
      <!-- ═══════════════════════════════════════════════════════════════ -->
      <div v-if="loadingPage" class="compact-loading">
        <div class="loading-spinner"></div>
        <p>Loading apartment information...</p>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════ -->
      <!-- COMPACT TWO-COLUMN LAYOUT                                        -->
      <!-- ═══════════════════════════════════════════════════════════════ -->
      <div v-else class="compact-layout">
        
        <!-- MAIN FORM PANEL -->
        <div class="compact-main">
          <form @submit.prevent="submitForm" class="compact-form">
            
            <!-- STATUS BANNER -->
            <div class="compact-status-banner">
              <div class="status-banner-left">
                <span class="banner-label">Current Status</span>
                <span :class="['compact-status-badge', statusBadgeClass(form.inventory_status)]">
                  <span class="status-dot"></span>
                  {{ formatStatus(form.inventory_status) }}
                </span>
              </div>
              <div class="status-banner-right">
                <span class="banner-label">Listing Type</span>
                <span class="compact-listing-badge">{{ formatListingType(form.listing_type) }}</span>
              </div>
            </div>

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
                  <label class="compact-label">Building</label>
                  <select v-model="form.building_id" class="compact-input">
                    <option value="">Select Building</option>
                    <option v-for="building in buildings" :key="building.id" :value="building.id">
                      {{ building.name }}
                    </option>
                  </select>
                </div>
                <div class="field-group">
                  <label class="compact-label">Unit Number</label>
                  <input v-model="form.unit_number" type="text" class="compact-input" placeholder="e.g., A-101">
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
                    <line x1="4" y1="8" x2="20" y2="8"/>
                    <line x1="4" y1="16" x2="20" y2="16"/>
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
                  </div>
                </div>
                <div class="field-group">
                  <label class="compact-label compact-label--sm">Inventory Status</label>
                  <select v-model="form.inventory_status" class="compact-input">
                    <option value="available">Available</option>
                    <option value="occupied">Occupied</option>
                    <option value="reserved">Reserved</option>
                    <option value="under_contract">Under Contract</option>
                    <option value="sold">Sold</option>
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
                <h3 class="section-title">Pricing & Commercial</h3>
              </div>
              <div class="compact-grid compact-grid--3">
                <div class="field-group" v-if="form.listing_type === 'rental' || form.listing_type === 'hybrid'">
                  <label class="compact-label compact-label--sm">Market Rent Price</label>
                  <div class="input-prefix">
                    <span class="prefix-symbol">{{ form.currency === 'USD' ? '$' : 'Sh' }}</span>
                    <input v-model.number="form.market_rent_price" type="number" step="0.01" class="compact-input compact-input--prefix" placeholder="0.00">
                  </div>
                </div>
                <div class="field-group" v-if="form.listing_type === 'sale' || form.listing_type === 'hybrid'">
                  <label class="compact-label compact-label--sm">Market Sale Price</label>
                  <div class="input-prefix">
                    <span class="prefix-symbol">{{ form.currency === 'USD' ? '$' : 'Sh' }}</span>
                    <input v-model.number="form.market_sale_price" type="number" step="0.01" class="compact-input compact-input--prefix" placeholder="0.00">
                  </div>
                </div>
                <div class="field-group">
                  <label class="compact-label compact-label--sm">Security Deposit</label>
                  <div class="input-prefix">
                    <span class="prefix-symbol">{{ form.currency === 'USD' ? '$' : 'Sh' }}</span>
                    <input v-model.number="form.security_deposit" type="number" step="0.01" class="compact-input compact-input--prefix" placeholder="0.00">
                  </div>
                </div>
              </div>
            </div>

            <!-- SECTION: Features (Checkbox Grid) -->
            <div class="form-section">
              <div class="section-header">
                <div class="section-icon">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                  </svg>
                </div>
                <h3 class="section-title">Amenities & Features</h3>
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
              <textarea v-model="form.notes" rows="3" class="compact-textarea" placeholder="Operational notes, maintenance remarks, or inventory comments..."></textarea>
            </div>

            <!-- ALERTS -->
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

            <!-- FORM ACTIONS -->
            <div class="form-actions">
              <button type="button" @click="deleteApartment" class="btn-danger">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="3 6 5 6 21 6"/>
                  <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                </svg>
                Delete
              </button>
              <button type="submit" :disabled="saving" class="btn-primary">
                <span v-if="saving" class="btn-spinner"></span>
                <span v-else>Save Changes</span>
              </button>
            </div>
          </form>
        </div>

        <!-- COMPACT SIDEBAR -->
        <div class="compact-sidebar">
          
          <!-- Quick Summary Card -->
          <div class="sidebar-card">
            <div class="sidebar-card-header">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
              </svg>
              <span>Quick Summary</span>
            </div>
            <div class="summary-list">
              <div class="summary-item">
                <span class="summary-label">Unit</span>
                <span class="summary-value">{{ form.unit_number || '—' }}</span>
              </div>
              <div class="summary-item">
                <span class="summary-label">Type</span>
                <span class="summary-value capitalize">{{ form.property_type }}</span>
              </div>
              <div class="summary-item">
                <span class="summary-label">Size</span>
                <span class="summary-value">{{ form.area_sqm || 0 }} m²</span>
              </div>
              <div class="summary-item">
                <span class="summary-label">Bed/Bath</span>
                <span class="summary-value">{{ form.bedrooms }}/{{ form.bathrooms }}</span>
              </div>
              <div class="summary-divider"></div>
              <div class="summary-item summary-highlight" v-if="form.listing_type === 'rental'">
                <span class="summary-label">Rent</span>
                <span class="summary-value">{{ formatCurrency(form.market_rent_price, form.currency) }}</span>
              </div>
              <div class="summary-item summary-highlight" v-if="form.listing_type === 'sale'">
                <span class="summary-label">Price</span>
                <span class="summary-value">{{ formatCurrency(form.market_sale_price, form.currency) }}</span>
              </div>
            </div>
          </div>

          <!-- Status Guide -->
          <div class="sidebar-card sidebar-card--info">
            <div class="sidebar-card-header">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="12" x2="12" y2="16"/>
                <line x1="12" y1="8" x2="12.01" y2="8"/>
              </svg>
              <span>Inventory Guide</span>
            </div>
            <ul class="guide-list">
              <li><span class="guide-dot available"></span>Available — Ready for lease/sale</li>
              <li><span class="guide-dot occupied"></span>Occupied — Currently leased</li>
              <li><span class="guide-dot reserved"></span>Reserved — Under negotiation</li>
              <li><span class="guide-dot maintenance"></span>Maintenance — Not available</li>
            </ul>
          </div>

          <!-- Keyboard Shortcuts -->
          <div class="sidebar-card sidebar-card--shortcuts">
            <div class="sidebar-card-header">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2"/>
                <line x1="9" y1="9" x2="15" y2="15"/>
                <line x1="15" y1="9" x2="9" y2="15"/>
              </svg>
              <span>Shortcuts</span>
            </div>
            <div class="shortcuts-grid">
              <div class="shortcut"><kbd>⌘</kbd> + <kbd>S</kbd><span>Save</span></div>
              <div class="shortcut"><kbd>⌘</kbd> + <kbd>←</kbd><span>Back</span></div>
              <div class="shortcut"><kbd>⌘</kbd> + <kbd>D</kbd><span>Delete</span></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '../../services/api'
import { useConfirm } from '@/composables/useConfirm'
import DashboardLayout from '../../layouts/DashboardLayout.vue'

const router = useRouter()
const route = useRoute()
const apartmentId = route.params.id

const loadingPage = ref(true)
const saving = ref(false)
const buildings = ref([])
const errorMessage = ref('')
const successMessage = ref('')

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

// Helpers
const formatStatus = (status) => status?.replaceAll('_', ' ') || 'Unknown'
const formatListingType = (type) => {
  const map = { rental: 'For Rent', sale: 'For Sale', hybrid: 'Both', not_listed: 'Not Listed' }
  return map[type] || type
}
const formatCurrency = (value, currency) => {
  if (!value) return '—'
  return currency === 'USD' ? `$${value.toLocaleString()}` : `Sh ${value.toLocaleString()}`
}

const statusBadgeClass = (status) => {
  const map = {
    available: 'badge-available',
    occupied: 'badge-occupied',
    reserved: 'badge-reserved',
    under_contract: 'badge-contract',
    sold: 'badge-sold',
    maintenance: 'badge-maintenance',
    blocked: 'badge-blocked',
  }
  return map[status] || 'badge-default'
}

const increment = (field) => { if (form[field] < 20) form[field]++ }
const decrement = (field) => { if (form[field] > 0) form[field]-- }

// API Methods (unchanged)
const fetchBuildings = async () => {
  try {
    const response = await api.get('/buildings', { params: { per_page: 100 } })
    buildings.value = response.data.data || []
  } catch (error) { console.error(error) }
}

const fetchApartment = async () => {
  loadingPage.value = true
  try {
    const response = await api.get(`/apartments/${apartmentId}`)
    const apartment = response.data.data
    Object.assign(form, {
      building_id: apartment.building?.id || '',
      unit_number: apartment.unit?.unit_number || '',
      floor: apartment.unit?.floor,
      property_type: apartment.unit?.property_type || 'apartment',
      bedrooms: apartment.layout?.bedrooms || 1,
      bathrooms: apartment.layout?.bathrooms || 1,
      area_sqm: apartment.layout?.area_sqm,
      listing_type: apartment.listing?.listing_type || 'rental',
      inventory_status: apartment.listing?.inventory_status || 'available',
      market_rent_price: apartment.pricing?.market_rent_price,
      market_sale_price: apartment.pricing?.market_sale_price,
      security_deposit: apartment.pricing?.security_deposit,
      currency: apartment.pricing?.currency || 'USD',
      has_balcony: apartment.features?.has_balcony || false,
      has_parking: apartment.features?.has_parking || false,
      has_storage: apartment.features?.has_storage || false,
      is_furnished: apartment.features?.is_furnished || false,
      notes: apartment.notes || '',
    })
  } catch (error) {
    console.error(error)
    errorMessage.value = 'Failed to load apartment.'
  } finally {
    loadingPage.value = false
  }
}

const submitForm = async () => {
  saving.value = true
  errorMessage.value = ''
  successMessage.value = ''
  try {
    const payload = { ...form, floor: form.floor || null, area_sqm: form.area_sqm || null }
    if (form.listing_type !== 'rental' && form.listing_type !== 'hybrid') payload.market_rent_price = null
    if (form.listing_type !== 'sale' && form.listing_type !== 'hybrid') payload.market_sale_price = null
    const response = await api.put(`/apartments/${apartmentId}`, payload)
    successMessage.value = response.data.message || 'Apartment updated successfully.'
    setTimeout(() => { successMessage.value = '' }, 3000)
  } catch (error) {
    console.error(error)
    errorMessage.value = error?.response?.data?.message || 'Failed to update apartment.'
  } finally {
    saving.value = false
  }
}

const deleteApartment = async () => {
  const { confirm } = useConfirm()
  const ok = await confirm({
    title: 'Delete apartment',
    message: 'Are you sure you want to delete this apartment? This cannot be undone.',
    confirmLabel: 'Delete',
  })
  if (!ok) return
  try {
    await api.delete(`/apartments/${apartmentId}`)
    router.push({ name: 'Apartments' })
  } catch (error) {
    console.error(error)
    errorMessage.value = 'Failed to delete apartment.'
  }
}

onMounted(async () => {
  await fetchBuildings()
  await fetchApartment()
})
</script>

<style scoped>
/* ═══════════════════════════════════════════════════════════════════════════ */
/* COMPACT DESIGN SYSTEM — PRODUCTION GRADE                                   */
/* ═══════════════════════════════════════════════════════════════════════════ */

.compact-page {
  max-width: 1440px;
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
  align-items: flex-start;
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

.compact-header-right {
  display: flex;
  gap: 0.75rem;
}

.btn-outline, .btn-secondary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 0.5rem;
  font-size: 0.8rem;
  font-weight: 500;
  text-decoration: none;
  transition: all 0.15s;
}

.btn-outline {
  background: white;
  border: 1px solid #e2e8f0;
  color: #475569;
}
.btn-outline:hover { background: #f8fafc; border-color: #cbd5e1; }

.btn-secondary {
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  color: #1e293b;
}
.btn-secondary:hover { background: #e2e8f0; }

/* ────────────────────────────────────────────────────────────────────────── */
/* LOADING                                                                    */
/* ────────────────────────────────────────────────────────────────────────── */

.compact-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  background: white;
  border-radius: 1rem;
  border: 1px solid #e2e8f0;
}

.loading-spinner {
  width: 2rem;
  height: 2rem;
  border: 3px solid #e2e8f0;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
  margin-bottom: 1rem;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ────────────────────────────────────────────────────────────────────────── */
/* TWO-COLUMN LAYOUT                                                          */
/* ────────────────────────────────────────────────────────────────────────── */

.compact-layout {
  display: grid;
  grid-template-columns: 1fr 280px;
  gap: 1.5rem;
}

@media (max-width: 1024px) {
  .compact-layout { grid-template-columns: 1fr; }
  .compact-sidebar { order: 2; }
}

/* ────────────────────────────────────────────────────────────────────────── */
/* MAIN FORM                                                                  */
/* ────────────────────────────────────────────────────────────────────────── */

.compact-main {
  background: white;
  border-radius: 1rem;
  border: 1px solid #e2e8f0;
  overflow: hidden;
}

.compact-form {
  padding: 1.25rem 1.5rem;
}

/* Status Banner */
.compact-status-banner {
  background: #f8fafc;
  border-radius: 0.75rem;
  padding: 0.75rem 1rem;
  margin-bottom: 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
  border: 1px solid #e2e8f0;
}

.status-banner-left, .status-banner-right {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.banner-label {
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #64748b;
}

.compact-status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.25rem 0.75rem;
  border-radius: 2rem;
  font-size: 0.75rem;
  font-weight: 600;
}

.compact-listing-badge {
  font-size: 0.75rem;
  font-weight: 600;
  color: #475569;
  background: #e2e8f0;
  padding: 0.25rem 0.75rem;
  border-radius: 2rem;
}

.status-dot {
  width: 0.375rem;
  height: 0.375rem;
  border-radius: 50%;
  background: currentColor;
}

.badge-available { background: #d1fae5; color: #065f46; }
.badge-occupied { background: #dbeafe; color: #1e40af; }
.badge-reserved { background: #fed7aa; color: #9a3412; }
.badge-contract { background: #e9d5ff; color: #6b21a5; }
.badge-sold { background: #fee2e2; color: #991b1b; }
.badge-maintenance { background: #ffedd5; color: #9a3412; }
.badge-blocked { background: #f1f5f9; color: #475569; }
.badge-default { background: #f1f5f9; color: #475569; }

/* Form Sections */
.form-section {
  margin-bottom: 1.5rem;
  padding-bottom: 1.25rem;
  border-bottom: 1px solid #f1f5f9;
}
.form-section:last-of-type { border-bottom: none; margin-bottom: 0; }

.section-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.section-icon {
  width: 1.5rem;
  height: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #eef2ff;
  border-radius: 0.375rem;
  color: #3b82f6;
}

.section-title {
  font-size: 0.75rem;
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

/* Alerts */
.alert-error, .alert-success {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border-radius: 0.5rem;
  font-size: 0.75rem;
  margin: 1rem 0;
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

/* Form Actions */
.form-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 1.5rem;
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
}

.btn-primary, .btn-danger {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1.25rem;
  border-radius: 0.5rem;
  font-size: 0.8rem;
  font-weight: 600;
  border: none;
  cursor: pointer;
  transition: all 0.15s;
}
.btn-primary {
  background: #3b82f6;
  color: white;
}
.btn-primary:hover:not(:disabled) { background: #2563eb; transform: translateY(-1px); }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-danger {
  background: white;
  color: #dc2626;
  border: 1px solid #fecaca;
}
.btn-danger:hover { background: #fef2f2; border-color: #fecaca; }

.btn-spinner {
  width: 1rem;
  height: 1rem;
  border: 2px solid white;
  border-top-color: transparent;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

/* ────────────────────────────────────────────────────────────────────────── */
/* SIDEBAR                                                                    */
/* ────────────────────────────────────────────────────────────────────────── */

.compact-sidebar { display: flex; flex-direction: column; gap: 1rem; }

.sidebar-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  padding: 1rem;
}

.sidebar-card--info { background: #fefce8; border-color: #fef08a; }
.sidebar-card--shortcuts { background: #f8fafc; }

.sidebar-card-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #64748b;
  margin-bottom: 0.75rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid #e2e8f0;
}

.summary-list { display: flex; flex-direction: column; gap: 0.5rem; }
.summary-item {
  display: flex;
  justify-content: space-between;
  font-size: 0.75rem;
}
.summary-label { color: #64748b; }
.summary-value { font-weight: 600; color: #1e293b; }
.summary-divider { height: 1px; background: #e2e8f0; margin: 0.25rem 0; }
.summary-highlight .summary-value { color: #3b82f6; font-size: 0.875rem; }
.capitalize { text-transform: capitalize; }

.guide-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.guide-list li {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.7rem;
  color: #475569;
}
.guide-dot {
  width: 0.5rem;
  height: 0.5rem;
  border-radius: 50%;
}
.guide-dot.available { background: #059669; }
.guide-dot.occupied { background: #2563eb; }
.guide-dot.reserved { background: #d97706; }
.guide-dot.maintenance { background: #ea580c; }

.shortcuts-grid { display: flex; flex-direction: column; gap: 0.5rem; }
.shortcut {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.7rem;
}
.shortcut kbd {
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  border-radius: 0.25rem;
  padding: 0.125rem 0.375rem;
  font-family: monospace;
  font-size: 0.65rem;
  font-weight: 600;
  color: #475569;
}
.shortcut span { color: #94a3b8; }
</style>