<template>
    <div class="compact-inventory">

      <!-- ═══════════════════════════════════════════════════════════════ -->
      <!-- COMPACT HEADER WITH INTEGRATED ACTIONS                          -->
      <!-- ═══════════════════════════════════════════════════════════════ -->
      <div class="compact-header">
        <div class="compact-header-left">
          <div class="compact-breadcrumb">
            <span class="breadcrumb-current">Inventory</span>
          </div>
          <div class="header-title-group">
            <h1 class="compact-title">Apartment Inventory</h1>
            <span class="live-badge">
              <span class="live-dot"></span>
              Live
            </span>
          </div>
          <p class="compact-subtitle">Enterprise apartment inventory and lifecycle management</p>
        </div>
        <div class="compact-header-right">
          <button class="btn-icon" title="Export data">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
              <polyline points="7 10 12 15 17 10"/>
              <line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
          </button>
          <button class="btn-icon" title="Refresh">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M23 4v6h-6"/>
              <path d="M1 20v-6h6"/>
              <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10"/>
              <path d="M20.49 15a9 9 0 0 1-14.85 3.36L1 14"/>
            </svg>
          </button>
          <RouterLink :to="{ name: 'ApartmentCreate' }" class="btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <line x1="12" y1="5" x2="12" y2="19"/>
              <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            New Apartment
          </RouterLink>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════ -->
      <!-- COMPACT KPI STRIP                                                -->
      <!-- ═══════════════════════════════════════════════════════════════ -->
      <div class="kpi-strip">
        <div class="kpi-item">
          <div class="kpi-icon kpi-icon--slate">🏢</div>
          <div class="kpi-content">
            <span class="kpi-label">Total</span>
            <strong class="kpi-value">{{ summary.total || 0 }}</strong>
            <span class="kpi-trend">{{ buildings.length }} bldgs</span>
          </div>
        </div>
        <div class="kpi-item kpi-item--green">
          <div class="kpi-icon kpi-icon--green">🟢</div>
          <div class="kpi-content">
            <span class="kpi-label">Available</span>
            <strong class="kpi-value">{{ summary.available || 0 }}</strong>
            <span class="kpi-trend">Ready</span>
          </div>
        </div>
        <div class="kpi-item kpi-item--blue">
          <div class="kpi-icon kpi-icon--blue">🔵</div>
          <div class="kpi-content">
            <span class="kpi-label">Occupied</span>
            <strong class="kpi-value">{{ summary.occupied || 0 }}</strong>
            <span class="kpi-trend">Active</span>
          </div>
        </div>
        <div class="kpi-item kpi-item--purple">
          <div class="kpi-icon kpi-icon--purple">🟣</div>
          <div class="kpi-content">
            <span class="kpi-label">Under Contract</span>
            <strong class="kpi-value">{{ summary.under_contract || 0 }}</strong>
            <span class="kpi-trend">Pending</span>
          </div>
        </div>
        <div class="kpi-item kpi-item--amber">
          <div class="kpi-icon kpi-icon--amber">🟠</div>
          <div class="kpi-content">
            <span class="kpi-label">Maintenance</span>
            <strong class="kpi-value">{{ summary.maintenance || 0 }}</strong>
            <span class="kpi-trend">Out of service</span>
          </div>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════ -->
      <!-- COMPACT FILTER BAR                                               -->
      <!-- ═══════════════════════════════════════════════════════════════ -->
      <div class="filter-bar">
        <div class="filter-search">
          <svg class="filter-search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
          <input
            v-model="search"
            type="text"
            placeholder="Search unit, building, or property type..."
            class="filter-search-input"
          />
          <button v-if="search" @click="search = ''" class="filter-search-clear">✕</button>
        </div>
        <div class="filter-controls">
          <select v-model="filters.building_id" class="filter-select">
            <option value="">All Buildings</option>
            <option v-for="building in buildings" :key="building.id" :value="building.id">
              {{ building.name }}
            </option>
          </select>
          <select v-model="filters.listing_type" class="filter-select">
            <option value="">All Listings</option>
            <option value="rental">Rental</option>
            <option value="sale">Sale</option>
            <option value="hybrid">Hybrid</option>
          </select>
          <select v-model="filters.inventory_status" class="filter-select">
            <option value="">All Statuses</option>
            <option value="available">Available</option>
            <option value="occupied">Occupied</option>
            <option value="reserved">Reserved</option>
            <option value="under_contract">Under Contract</option>
            <option value="sold">Sold</option>
            <option value="maintenance">Maintenance</option>
            <option value="blocked">Blocked</option>
          </select>
          <div class="view-toggle">
            <button @click="viewMode = 'grid'" :class="['view-btn', { 'view-btn-active': viewMode === 'grid' }]" title="Grid view">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="7" height="7"/>
                <rect x="14" y="3" width="7" height="7"/>
                <rect x="3" y="14" width="7" height="7"/>
                <rect x="14" y="14" width="7" height="7"/>
              </svg>
            </button>
            <button @click="viewMode = 'table'" :class="['view-btn', { 'view-btn-active': viewMode === 'table' }]" title="Table view">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2"/>
                <line x1="9" y1="3" x2="9" y2="21"/>
                <line x1="15" y1="3" x2="15" y2="21"/>
                <line x1="3" y1="9" x2="21" y2="9"/>
                <line x1="3" y1="15" x2="21" y2="15"/>
              </svg>
            </button>
          </div>
          <div class="filter-divider"></div>
          <div class="result-count">{{ apartments.length }} units</div>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════ -->
      <!-- LOADING & EMPTY STATES                                           -->
      <!-- ═══════════════════════════════════════════════════════════════ -->
      <div v-if="loading" class="state-container">
        <div class="skeleton-grid">
          <div class="skeleton-card" v-for="i in 6" :key="i">
            <div class="skeleton-line skeleton-line--title"></div>
            <div class="skeleton-line"></div>
            <div class="skeleton-line skeleton-line--short"></div>
          </div>
        </div>
      </div>

      <div v-else-if="!apartments.length" class="empty-state">
        <div class="empty-icon">🏢</div>
        <h3 class="empty-title">No Apartments Found</h3>
        <p class="empty-sub">Create your first apartment inventory record to get started.</p>
        <RouterLink :to="{ name: 'ApartmentCreate' }" class="btn-primary btn-primary--sm mt-4">New Apartment</RouterLink>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════ -->
      <!-- COMPACT GRID VIEW                                                -->
      <!-- ═══════════════════════════════════════════════════════════════ -->
      <div v-else-if="viewMode === 'grid'" class="compact-grid">
        <div v-for="apt in apartments" :key="apt.id" class="grid-card">
          <div class="card-status-bar" :class="statusGradient(apt.listing.inventory_status)"></div>
          <div class="card-header">
            <div class="card-title-group">
              <h3 class="card-title">{{ apt.unit.unit_number }}</h3>
              <span class="card-building">{{ apt.building?.name }}</span>
            </div>
            <span :class="['card-status', statusBadge(apt.listing.inventory_status)]">
              <span class="status-dot-small"></span>
              {{ formatStatus(apt.listing.inventory_status) }}
            </span>
          </div>
          <div class="card-tags">
            <span class="tag">{{ apt.unit.property_type }}</span>
            <span :class="['tag', listingBadge(apt.listing.listing_type)]">{{ apt.listing.listing_type }}</span>
          </div>
          <div class="card-specs">
            <div class="spec">
              <span class="spec-label">Bed</span>
              <strong>{{ apt.layout.bedrooms }}</strong>
            </div>
            <div class="spec">
              <span class="spec-label">Bath</span>
              <strong>{{ apt.layout.bathrooms }}</strong>
            </div>
            <div class="spec">
              <span class="spec-label">Area</span>
              <strong>{{ apt.layout.area_sqm || 0 }}m²</strong>
            </div>
          </div>
          <div class="card-price">
            <span class="price-label">Effective Price</span>
            <strong class="price-value">{{ formatCurrency(apt.pricing.effective_price, apt.pricing.currency) }}</strong>
          </div>
          <div class="card-features">
            <span v-if="apt.features.has_parking" class="feature">🅿️ Parking</span>
            <span v-if="apt.features.has_balcony" class="feature">🌿 Balcony</span>
            <span v-if="apt.features.is_furnished" class="feature">🛋️ Furnished</span>
            <span v-if="apt.features.has_storage" class="feature">📦 Storage</span>
          </div>
          <div class="card-actions">
            <RouterLink :to="{ name: 'ApartmentShow', params: { id: apt.id } }" class="action-link">View</RouterLink>
            <RouterLink :to="{ name: 'ApartmentEdit', params: { id: apt.id } }" class="action-link action-link--edit">Edit</RouterLink>
            <button @click="confirmDelete(apt)" class="action-link action-link--delete">Delete</button>
          </div>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════ -->
      <!-- COMPACT TABLE VIEW                                               -->
      <!-- ═══════════════════════════════════════════════════════════════ -->
      <div v-else class="table-wrapper">
        <table class="compact-table">
          <thead>
            <tr>
              <th>Unit</th>
              <th>Building</th>
              <th>Layout</th>
              <th>Listing</th>
              <th>Status</th>
              <th>Price</th>
              <th class="th-actions">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="apt in apartments" :key="apt.id">
              <td class="td-unit">
                <div>
                  <span class="unit-number">{{ apt.unit.unit_number }}</span>
                  <span class="unit-type">{{ apt.unit.property_type }}</span>
                </div>
              </td>
              <td class="td-building">{{ apt.building?.name }}</td>
              <td class="td-layout">{{ apt.layout.bedrooms }}br • {{ apt.layout.bathrooms }}ba • {{ apt.layout.area_sqm || 0 }}m²</td>
              <td><span :class="['table-badge', listingBadge(apt.listing.listing_type)]">{{ apt.listing.listing_type }}</span></td>
              <td><span :class="['table-badge', statusBadge(apt.listing.inventory_status)]">{{ formatStatus(apt.listing.inventory_status) }}</span></td>
              <td class="td-price">{{ formatCurrency(apt.pricing.effective_price, apt.pricing.currency) }}</td>
              <td class="td-actions">
                <div class="table-actions">
                  <RouterLink :to="{ name: 'ApartmentShow', params: { id: apt.id } }" class="table-action" title="View">👁️</RouterLink>
                  <RouterLink :to="{ name: 'ApartmentEdit', params: { id: apt.id } }" class="table-action" title="Edit">✏️</RouterLink>
                  <button @click="confirmDelete(apt)" class="table-action table-action--delete" title="Delete">🗑️</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>
 
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import api from '../../services/api'
import DashboardLayout from '../../layouts/DashboardLayout.vue'

// State
const loading = ref(false)
const apartments = ref([])
const buildings = ref([])
const viewMode = ref('grid')
const search = ref('')

const filters = reactive({
  building_id: '',
  listing_type: '',
  inventory_status: '',
  page: 1,
})

const summary = reactive({
  total: 0,
  available: 0,
  occupied: 0,
  reserved: 0,
  under_contract: 0,
  sold: 0,
  maintenance: 0,
})

// Fetch methods (unchanged API logic)
const fetchApartments = async () => {
  loading.value = true
  try {
    const response = await api.get('/apartments', {
      params: {
        search: search.value,
        building_id: filters.building_id,
        listing_type: filters.listing_type,
        inventory_status: filters.inventory_status,
        page: filters.page,
      }
    })
    apartments.value = response.data.data || []
  } catch (error) {
    console.error(error)
  } finally {
    loading.value = false
  }
}

const fetchBuildings = async () => {
  try {
    const response = await api.get('/buildings')
    buildings.value = response.data.data || []
  } catch (error) {
    console.error(error)
  }
}

const fetchSummary = async () => {
  try {
    const response = await api.get('/apartments/summary')
    Object.assign(summary, response.data.data || {})
  } catch (error) {
    console.error(error)
  }
}

const confirmDelete = async (apartment) => {
  if (!confirm(`Delete apartment ${apartment.unit.unit_number}?`)) return
  try {
    await api.delete(`/apartments/${apartment.id}`)
    await fetchApartments()
    await fetchSummary()
  } catch (error) {
    console.error(error)
  }
}

// Helpers
const formatCurrency = (value, currency = 'USD') => {
  if (!value) return `${currency} 0`
  return new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(value)
}

const formatStatus = (status) => status?.replaceAll('_', ' ') || 'Unknown'

const statusBadge = (status) => {
  const map = {
    available: 'badge-green',
    occupied: 'badge-blue',
    reserved: 'badge-amber',
    under_contract: 'badge-purple',
    sold: 'badge-red',
    maintenance: 'badge-orange',
    blocked: 'badge-slate',
  }
  return map[status] || 'badge-slate'
}

const statusGradient = (status) => {
  const map = {
    available: 'gradient-green',
    occupied: 'gradient-blue',
    reserved: 'gradient-amber',
    under_contract: 'gradient-purple',
    sold: 'gradient-red',
    maintenance: 'gradient-orange',
    blocked: 'gradient-slate',
  }
  return map[status] || 'gradient-slate'
}

const listingBadge = (type) => {
  const map = {
    rental: 'badge-blue-light',
    sale: 'badge-green-light',
    hybrid: 'badge-purple-light',
  }
  return map[type] || 'badge-slate'
}

// Watchers
watch([search, () => filters.building_id, () => filters.listing_type, () => filters.inventory_status], () => {
  fetchApartments()
})

// Lifecycle
onMounted(async () => {
  await Promise.all([fetchBuildings(), fetchSummary(), fetchApartments()])
})
</script>

<style scoped>
/* ═══════════════════════════════════════════════════════════════════════════ */
/* COMPACT INVENTORY SYSTEM — PRODUCTION GRADE                                 */
/* ═══════════════════════════════════════════════════════════════════════════ */

.compact-inventory {
  max-width: 1600px;
  margin: 0 auto;
  padding: 1rem 1.5rem 2rem;
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
  margin-bottom: 0.5rem;
}

.breadcrumb-current {
  font-size: 0.75rem;
  font-weight: 500;
  color: #3b82f6;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.header-title-group {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.25rem;
  flex-wrap: wrap;
}

.compact-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #0f172a;
  letter-spacing: -0.02em;
  margin: 0;
}

.live-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.25rem 0.625rem;
  background: #dcfce7;
  border-radius: 2rem;
  font-size: 0.6rem;
  font-weight: 600;
  color: #166534;
}

.live-dot {
  width: 0.375rem;
  height: 0.375rem;
  background: #22c55e;
  border-radius: 50%;
  animation: pulse 1.5s infinite;
}
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }

.compact-subtitle {
  font-size: 0.75rem;
  color: #64748b;
}

.compact-header-right {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-icon {
  width: 2.25rem;
  height: 2.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  cursor: pointer;
  color: #64748b;
  transition: all 0.15s;
}
.btn-icon:hover { background: #f8fafc; border-color: #cbd5e1; color: #1e293b; }

.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: #3b82f6;
  color: white;
  font-size: 0.8rem;
  font-weight: 600;
  border-radius: 0.5rem;
  text-decoration: none;
  transition: all 0.15s;
}
.btn-primary:hover { background: #2563eb; transform: translateY(-1px); }
.btn-primary--sm { padding: 0.375rem 0.875rem; font-size: 0.75rem; }

/* ────────────────────────────────────────────────────────────────────────── */
/* KPI STRIP                                                                  */
/* ────────────────────────────────────────────────────────────────────────── */

.kpi-strip {
  display: flex;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}

.kpi-item {
  flex: 1;
  min-width: 140px;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  transition: all 0.15s;
}
.kpi-item:hover { border-color: #cbd5e1; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }

.kpi-icon {
  width: 2.25rem;
  height: 2.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 0.5rem;
  font-size: 1.25rem;
}
.kpi-icon--slate { background: #f1f5f9; }
.kpi-icon--green { background: #dcfce7; }
.kpi-icon--blue { background: #dbeafe; }
.kpi-icon--purple { background: #f3e8ff; }
.kpi-icon--amber { background: #fed7aa; }

.kpi-content {
  display: flex;
  flex-direction: column;
}
.kpi-label { font-size: 0.6rem; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.03em; }
.kpi-value { font-size: 1.5rem; font-weight: 700; color: #0f172a; line-height: 1.2; }
.kpi-trend { font-size: 0.6rem; color: #94a3b8; }

/* ────────────────────────────────────────────────────────────────────────── */
/* FILTER BAR                                                                 */
/* ────────────────────────────────────────────────────────────────────────── */

.filter-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  padding: 0.5rem 1rem;
  margin-bottom: 1.5rem;
}

.filter-search {
  position: relative;
  flex: 1;
  min-width: 200px;
  max-width: 280px;
}
.filter-search-icon {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
}
.filter-search-input {
  width: 100%;
  padding: 0.5rem 0.75rem 0.5rem 2rem;
  font-size: 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  background: #fafafa;
  outline: none;
  transition: all 0.15s;
}
.filter-search-input:focus { border-color: #3b82f6; background: white; box-shadow: 0 0 0 2px rgba(59,130,246,0.1); }
.filter-search-clear {
  position: absolute;
  right: 0.5rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  font-size: 0.75rem;
  color: #94a3b8;
  cursor: pointer;
}

.filter-controls {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.filter-select {
  padding: 0.5rem 2rem 0.5rem 0.75rem;
  font-size: 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  background: white;
  cursor: pointer;
  outline: none;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg width='10' height='6' viewBox='0 0 10 6' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%2394a3b8' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 0.75rem center;
}
.filter-select:focus { border-color: #3b82f6; }

.view-toggle {
  display: flex;
  background: #f1f5f9;
  border-radius: 0.5rem;
  padding: 0.125rem;
}
.view-btn {
  padding: 0.375rem 0.625rem;
  background: transparent;
  border: none;
  border-radius: 0.375rem;
  cursor: pointer;
  color: #64748b;
  transition: all 0.1s;
}
.view-btn-active {
  background: white;
  color: #3b82f6;
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}
.filter-divider {
  width: 1px;
  height: 1.5rem;
  background: #e2e8f0;
}
.result-count {
  font-size: 0.7rem;
  font-weight: 500;
  color: #64748b;
}

/* ────────────────────────────────────────────────────────────────────────── */
/* STATE CONTAINERS                                                           */
/* ────────────────────────────────────────────────────────────────────────── */

.state-container, .empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  background: white;
  border-radius: 1rem;
  border: 1px solid #e2e8f0;
}

.empty-icon {
  font-size: 4rem;
  opacity: 0.5;
  margin-bottom: 1rem;
}
.empty-title { font-size: 1.25rem; font-weight: 700; color: #0f172a; margin-bottom: 0.25rem; }
.empty-sub { font-size: 0.8rem; color: #64748b; }

.skeleton-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1rem;
  width: 100%;
}
.skeleton-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  padding: 1rem;
}
.skeleton-line {
  height: 0.75rem;
  background: #f1f5f9;
  border-radius: 0.25rem;
  margin-bottom: 0.75rem;
  animation: pulse 1s infinite;
}
.skeleton-line--title { height: 1.25rem; width: 60%; }
.skeleton-line--short { width: 40%; }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.6; } }

/* ────────────────────────────────────────────────────────────────────────── */
/* COMPACT GRID                                                               */
/* ────────────────────────────────────────────────────────────────────────── */

.compact-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1rem;
}

.grid-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  overflow: hidden;
  transition: all 0.15s;
}
.grid-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.08); }

.card-status-bar { height: 0.25rem; }
.gradient-green { background: linear-gradient(90deg, #22c55e, #10b981); }
.gradient-blue { background: linear-gradient(90deg, #3b82f6, #6366f1); }
.gradient-amber { background: linear-gradient(90deg, #f59e0b, #f97316); }
.gradient-purple { background: linear-gradient(90deg, #a855f7, #8b5cf6); }
.gradient-red { background: linear-gradient(90deg, #ef4444, #f43f5e); }
.gradient-orange { background: linear-gradient(90deg, #ea580c, #eab308); }
.gradient-slate { background: linear-gradient(90deg, #64748b, #475569); }

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 0.875rem 1rem 0.5rem;
}
.card-title-group { display: flex; flex-direction: column; }
.card-title { font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0; }
.card-building { font-size: 0.65rem; color: #64748b; }
.card-status {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.25rem 0.625rem;
  border-radius: 2rem;
  font-size: 0.65rem;
  font-weight: 600;
}
.status-dot-small { width: 0.25rem; height: 0.25rem; border-radius: 50%; background: currentColor; }

.card-tags { display: flex; gap: 0.5rem; padding: 0 1rem 0.75rem; }
.tag {
  padding: 0.25rem 0.625rem;
  background: #f1f5f9;
  border-radius: 0.375rem;
  font-size: 0.65rem;
  font-weight: 500;
  color: #475569;
}

.card-specs {
  display: flex;
  justify-content: space-around;
  padding: 0.75rem 1rem;
  border-top: 1px solid #f1f5f9;
  border-bottom: 1px solid #f1f5f9;
}
.spec { text-align: center; }
.spec-label { display: block; font-size: 0.6rem; text-transform: uppercase; color: #94a3b8; }
.spec strong { font-size: 1rem; color: #0f172a; }

.card-price {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #f1f5f9;
}
.price-label { display: block; font-size: 0.6rem; text-transform: uppercase; color: #94a3b8; }
.price-value { font-size: 1.125rem; font-weight: 700; color: #0f172a; }

.card-features {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  font-size: 0.65rem;
  color: #475569;
}

.card-actions {
  display: flex;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border-top: 1px solid #f1f5f9;
}
.action-link {
  flex: 1;
  text-align: center;
  padding: 0.375rem;
  border-radius: 0.375rem;
  font-size: 0.7rem;
  font-weight: 600;
  text-decoration: none;
  background: #f8fafc;
  color: #3b82f6;
  transition: all 0.1s;
}
.action-link:hover { background: #eef2ff; }
.action-link--edit { color: #64748b; }
.action-link--delete { color: #ef4444; background: #fef2f2; }
.action-link--delete:hover { background: #fee2e2; }

/* ────────────────────────────────────────────────────────────────────────── */
/* COMPACT TABLE                                                              */
/* ────────────────────────────────────────────────────────────────────────── */

.table-wrapper {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  overflow-x: auto;
}

.compact-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.75rem;
}
.compact-table th {
  text-align: left;
  padding: 0.875rem 1rem;
  background: #f8fafc;
  font-weight: 600;
  color: #475569;
  border-bottom: 1px solid #e2e8f0;
}
.compact-table td {
  padding: 0.875rem 1rem;
  border-bottom: 1px solid #f1f5f9;
  color: #334155;
}
.compact-table tr:last-child td { border-bottom: none; }
.compact-table tr:hover { background: #f8fafc; }

.th-actions { text-align: right; }
.td-unit .unit-number { font-weight: 600; color: #0f172a; display: block; }
.td-unit .unit-type { font-size: 0.65rem; color: #64748b; }
.td-price { font-weight: 600; color: #0f172a; }
.td-actions { text-align: right; }

.table-actions {
  display: flex;
  gap: 0.5rem;
  justify-content: flex-end;
}
.table-action {
  text-decoration: none;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  background: #f1f5f9;
  font-size: 0.75rem;
  transition: all 0.1s;
}
.table-action--delete { background: #fef2f2; cursor: pointer; border: none; }

/* Badges */
.badge-green { background: #dcfce7; color: #166534; }
.badge-blue { background: #dbeafe; color: #1e40af; }
.badge-amber { background: #fed7aa; color: #9a3412; }
.badge-purple { background: #f3e8ff; color: #6b21a5; }
.badge-red { background: #fee2e2; color: #991b1b; }
.badge-orange { background: #ffedd5; color: #9a3412; }
.badge-slate { background: #f1f5f9; color: #475569; }
.badge-blue-light { background: #dbeafe; color: #1e40af; }
.badge-green-light { background: #dcfce7; color: #166534; }
.badge-purple-light { background: #f3e8ff; color: #6b21a5; }

.table-badge {
  display: inline-block;
  padding: 0.25rem 0.625rem;
  border-radius: 0.375rem;
  font-size: 0.7rem;
  font-weight: 600;
}
</style>