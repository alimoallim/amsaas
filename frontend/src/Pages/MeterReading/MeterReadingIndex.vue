<template>
  <div class="meter-readings-page">
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- PAGE HEADER                                                      -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <div class="page-header">
      <div class="page-header-left">
        <div class="page-breadcrumb">
          <span class="breadcrumb-current">Meter Readings</span>
        </div>
        <div class="header-title-group">
          <h1 class="page-title">Utility Meter Readings</h1>
          <span class="live-indicator">
            <span class="live-dot"></span>
            Live Data
          </span>
        </div>
        <p class="page-subtitle">Operational utility consumption management dashboard</p>
      </div>
      <div class="page-header-right">
        <button class="btn-icon" title="Export Data">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
            <polyline points="7 10 12 15 17 10"/>
            <line x1="12" y1="15" x2="12" y2="3"/>
          </svg>
        </button>
        <button class="btn-icon" title="Refresh" @click="fetchReadings(pagination.current_page)">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M23 4v6h-6"/>
            <path d="M1 20v-6h6"/>
            <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10"/>
            <path d="M20.49 15a9 9 0 0 1-14.85 3.36L1 14"/>
          </svg>
        </button>
        <RouterLink to="/meter-readings/create" class="btn-primary">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          Capture Reading
        </RouterLink>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- COMPACT KPI STRIP                                                -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <div class="kpi-strip">
      <div class="kpi-card">
        <div class="kpi-icon kpi-icon--slate">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="3" width="18" height="18" rx="2"/>
          </svg>
        </div>
        <div class="kpi-content">
          <span class="kpi-label">Total Readings</span>
          <strong class="kpi-value">{{ statistics.total }}</strong>
          <span class="kpi-trend">All time</span>
        </div>
      </div>
      <div class="kpi-card kpi-card--success">
        <div class="kpi-icon kpi-icon--green">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
        </div>
        <div class="kpi-content">
          <span class="kpi-label">Approved</span>
          <strong class="kpi-value kpi-value--green">{{ statistics.approved }}</strong>
          <span class="kpi-trend">Verified</span>
        </div>
      </div>
      <div class="kpi-card kpi-card--warning">
        <div class="kpi-icon kpi-icon--amber">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <polyline points="12 6 12 12 16 14"/>
          </svg>
        </div>
        <div class="kpi-content">
          <span class="kpi-label">Pending</span>
          <strong class="kpi-value kpi-value--amber">{{ statistics.pending }}</strong>
          <span class="kpi-trend">Awaiting review</span>
        </div>
      </div>
      <div class="kpi-card kpi-card--danger">
        <div class="kpi-icon kpi-icon--red">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
        </div>
        <div class="kpi-content">
          <span class="kpi-label">Anomalies</span>
          <strong class="kpi-value kpi-value--red">{{ statistics.anomalies }}</strong>
          <span class="kpi-trend">Requires attention</span>
        </div>
      </div>
      <div class="kpi-card">
        <div class="kpi-icon kpi-icon--blue">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
          </svg>
        </div>
        <div class="kpi-content">
          <span class="kpi-label">Estimated</span>
          <strong class="kpi-value">{{ statistics.estimated }}</strong>
          <span class="kpi-trend">Non-actual</span>
        </div>
      </div>
      <div class="kpi-card">
        <div class="kpi-icon kpi-icon--purple">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M2 20L22 20M4 20L4 14M12 20L12 8M20 20L20 10"/>
          </svg>
        </div>
        <div class="kpi-content">
          <span class="kpi-label">Total Consumption</span>
          <strong class="kpi-value">{{ statistics.totalConsumption.toFixed(1) }}k</strong>
          <span class="kpi-trend">kWh / units</span>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- COMPACT FILTER BAR                                               -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <div class="filter-bar">
      <div class="filter-search">
        <svg class="filter-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <input
          v-model="filters.search"
          type="text"
          placeholder="Search meter number or reader..."
          class="filter-input"
          @keyup.enter="fetchReadings()"
        />
        <button v-if="filters.search" @click="filters.search = ''; fetchReadings()" class="filter-clear">✕</button>
      </div>
      <div class="filter-controls">
        <select v-model="filters.status" class="filter-select" @change="fetchReadings()">
          <option value="">All Statuses</option>
          <option value="draft">Draft</option>
          <option value="verified">Verified</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
        </select>
        <select v-model="filters.utility_type" class="filter-select" @change="fetchReadings()">
          <option value="">All Utilities</option>
          <option value="electricity">⚡ Electricity</option>
          <option value="water">💧 Water</option>
          <option value="gas">🔥 Gas</option>
        </select>
        <label class="filter-checkbox">
          <input v-model="filters.anomalies_only" type="checkbox" @change="fetchReadings()">
          <span>⚠️ Anomalies Only</span>
        </label>
        <button class="btn-reset" @click="resetFilters">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="1 4 1 10 7 10"/>
            <path d="M3.51 15a9 9 0 1 0 .49-3.51"/>
          </svg>
          Reset
        </button>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- DATA TABLE                                                       -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <div class="table-container">
      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th>Meter</th>
              <th>Property</th>
              <th>Reading</th>
              <th>Consumption</th>
              <th>Status</th>
              <th>Anomaly</th>
              <th>Reader</th>
              <th class="th-actions">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="reading in readings" :key="reading.id" :class="{ 'row-anomaly': reading.anomaly.detected }">
              <!-- Meter -->
              <td class="td-meter">
                <div class="meter-info">
                  <span class="meter-number">{{ reading.meter.meter_number }}</span>
                  <span class="meter-type">{{ reading.meter.utility_type.label }}</span>
                </div>
              </td>
              <!-- Property -->
              <td class="td-property">
                <div class="property-info">
                  <span class="building-name">{{ reading.building?.name || 'N/A' }}</span>
                  <span class="unit-number">Unit {{ reading.apartment?.unit_number || 'N/A' }}</span>
                </div>
              </td>
              <!-- Reading -->
              <td class="td-reading">
                <div class="reading-info">
                  <span class="reading-date">{{ formatDate(reading.reading.reading_date) }}</span>
                  <span class="reading-values">
                    {{ reading.reading.previous_reading }} → {{ reading.reading.current_reading }}
                  </span>
                </div>
              </td>
              <!-- Consumption -->
              <td class="td-consumption">
                <strong class="consumption-value">{{ reading.reading.formatted_consumption || reading.reading.consumption + ' kWh' }}</strong>
              </td>
              <!-- Status -->
              <td class="td-status">
                <span :class="['status-badge', statusClasses(reading.status.value)]">
                  <span class="status-dot-small"></span>
                  {{ reading.status.label }}
                </span>
              </td>
              <!-- Anomaly -->
              <td class="td-anomaly">
                <div v-if="reading.anomaly.detected" class="anomaly-badge" :class="anomalySeverityClass(reading.anomaly.severity)">
                  {{ reading.anomaly.severity }}
                </div>
                <div v-else class="anomaly-none">—</div>
                <div v-if="reading.anomaly.reason" class="anomaly-reason">{{ reading.anomaly.reason }}</div>
              </td>
              <!-- Reader -->
              <td class="td-reader">
                <span class="reader-name">{{ reading.reader?.name || '—' }}</span>
              </td>
              <!-- Actions -->
              <td class="td-actions">
                <div class="action-buttons">
                  <RouterLink :to="`/meter-readings/${reading.id}`" class="action-link" title="View Details">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                      <circle cx="12" cy="12" r="3"/>
                    </svg>
                  </RouterLink>
                  <button
                    v-if="reading.controls?.can_approve"
                    @click="approveReading(reading)"
                    :disabled="approving === reading.id"
                    class="action-btn action-btn--approve"
                    title="Approve Reading"
                  >
                    <span v-if="approving === reading.id" class="btn-spinner-small"></span>
                    <span v-else>✓</span>
                  </button>
                  <button
                    @click="rejectReading(reading)"
                    :disabled="rejecting === reading.id"
                    class="action-btn action-btn--reject"
                    title="Reject Reading"
                  >
                    <span v-if="rejecting === reading.id" class="btn-spinner-small"></span>
                    <span v-else>✗</span>
                  </button>
                </div>
              </td>
            </tr>
            <!-- Empty State -->
            <tr v-if="!loading && readings.length === 0">
              <td colspan="8" class="empty-state">
                <div class="empty-icon">📊</div>
                <p>No meter readings found</p>
                <span>Adjust your filters or capture a new reading</span>
              </td>
            </tr>
            <!-- Loading State -->
            <tr v-if="loading">
              <td colspan="8" class="loading-state">
                <div class="loading-spinner-small"></div>
                <span>Loading readings...</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="table-footer">
        <div class="pagination-info">
          Showing page <strong>{{ pagination.current_page }}</strong> of <strong>{{ pagination.last_page }}</strong>
          <span class="pagination-divider">|</span>
          <strong>{{ pagination.total }}</strong> total readings
        </div>
        <div class="pagination-controls">
          <button
            @click="previousPage"
            :disabled="pagination.current_page === 1"
            class="pagination-btn"
          >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="15 18 9 12 15 6"/>
            </svg>
            Previous
          </button>
          <div class="pagination-pages">
            <button
              v-for="page in visiblePages"
              :key="page"
              @click="fetchReadings(page)"
              :class="['pagination-page', { 'pagination-page--active': pagination.current_page === page }]"
            >
              {{ page }}
            </button>
          </div>
          <button
            @click="nextPage"
            :disabled="pagination.current_page === pagination.last_page"
            class="pagination-btn"
          >
            Next
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="9 18 15 12 9 6"/>
            </svg>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import {
  computed,
  onMounted,
  reactive,
  ref,
} from 'vue'
import { RouterLink } from 'vue-router'
import api from '@/services/api'

// State
const loading = ref(false)
const approving = ref(null)
const rejecting = ref(null)
const readings = ref([])

const statistics = reactive({
  total: 0,
  approved: 0,
  pending: 0,
  anomalies: 0,
  estimated: 0,
  totalConsumption: 0,
})

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
})

const filters = reactive({
  search: '',
  status: '',
  utility_type: '',
  anomalies_only: false,
})

// Computed: Visible page numbers
const visiblePages = computed(() => {
  const current = pagination.current_page
  const last = pagination.last_page
  const delta = 2
  const range = []
  for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
    range.push(i)
  }
  if (current - delta > 2) range.unshift('...')
  if (current + delta < last - 1) range.push('...')
  return [1, ...range, last]
})

// Fetch readings
const fetchReadings = async (page = 1) => {
  try {
    loading.value = true
    const response = await api.get('/meter-readings', {
      params: {
        page,
        search: filters.search,
        status: filters.status,
        utility_type: filters.utility_type,
        anomalies_only: filters.anomalies_only,
      }
    })
    readings.value = response.data.data
    pagination.current_page = response.data.meta.current_page
    pagination.last_page = response.data.meta.last_page
    pagination.per_page = response.data.meta.per_page
    pagination.total = response.data.meta.total
    calculateStatistics()
  } catch (error) {
    console.error('Meter readings fetch failed:', error)
  } finally {
    loading.value = false
  }
}

// Calculate statistics
const calculateStatistics = () => {
  statistics.total = readings.value.length
  statistics.approved = readings.value.filter(r => r.status.value === 'approved').length
  statistics.pending = readings.value.filter(r => r.status.value !== 'approved').length
  statistics.anomalies = readings.value.filter(r => r.anomaly.detected).length
  statistics.estimated = readings.value.filter(r => r.reading_type?.value === 'estimated').length
  statistics.totalConsumption = readings.value.reduce((total, item) => total + Number(item.reading.consumption), 0)
}

// Reset filters
const resetFilters = () => {
  filters.search = ''
  filters.status = ''
  filters.utility_type = ''
  filters.anomalies_only = false
  fetchReadings()
}

// Approve reading
const approveReading = async (reading) => {
  try {
    approving.value = reading.id
    await api.post(`/meter-readings/${reading.id}/approve`)
    await fetchReadings(pagination.current_page)
  } catch (error) {
    console.error('Approval failed:', error)
  } finally {
    approving.value = null
  }
}

// Reject reading
const rejectReading = async (reading) => {
  const reason = prompt('Rejection reason')
  if (reason === null) return
  try {
    rejecting.value = reading.id
    await api.post(`/meter-readings/${reading.id}/reject`, { reason })
    await fetchReadings(pagination.current_page)
  } catch (error) {
    console.error('Rejection failed:', error)
  } finally {
    rejecting.value = null
  }
}

// Pagination
const nextPage = () => {
  if (pagination.current_page < pagination.last_page) {
    fetchReadings(pagination.current_page + 1)
  }
}
const previousPage = () => {
  if (pagination.current_page > 1) {
    fetchReadings(pagination.current_page - 1)
  }
}

// Helper functions
const formatDate = (dateString) => {
  if (!dateString) return '—'
  return new Date(dateString).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}

const statusClasses = (status) => {
  const map = {
    approved: 'badge-approved',
    verified: 'badge-verified',
    draft: 'badge-draft',
    rejected: 'badge-rejected',
  }
  return map[status] || 'badge-default'
}

const anomalySeverityClass = (severity) => {
  const map = {
    high: 'anomaly-high',
    medium: 'anomaly-medium',
    low: 'anomaly-low',
  }
  return map[severity] || 'anomaly-default'
}

// Lifecycle
onMounted(() => {
  fetchReadings()
})
</script>

<style scoped>
/* ═══════════════════════════════════════════════════════════════════════════ */
/* METER READINGS PAGE — PRODUCTION GRADE ERP                                 */
/* ═══════════════════════════════════════════════════════════════════════════ */

.meter-readings-page {
  max-width: 1600px;
  margin: 0 auto;
  padding: 1rem 1.5rem 2rem;
  background: #f8fafc;
  min-height: 100vh;
}

/* ────────────────────────────────────────────────────────────────────────── */
/* PAGE HEADER                                                                */
/* ────────────────────────────────────────────────────────────────────────── */

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.page-breadcrumb {
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
  flex-wrap: wrap;
  margin-bottom: 0.25rem;
}
.page-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #0f172a;
  letter-spacing: -0.02em;
  margin: 0;
}
.live-indicator {
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

.page-subtitle {
  font-size: 0.75rem;
  color: #64748b;
}

.page-header-right {
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
.btn-icon:hover { background: #f8fafc; color: #1e293b; }

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

/* ────────────────────────────────────────────────────────────────────────── */
/* KPI STRIP                                                                  */
/* ────────────────────────────────────────────────────────────────────────── */

.kpi-strip {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 1rem;
  margin-bottom: 1.5rem;
}
@media (max-width: 1024px) { .kpi-strip { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 640px) { .kpi-strip { grid-template-columns: repeat(2, 1fr); } }

.kpi-card {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.875rem 1rem;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  transition: all 0.15s;
}
.kpi-card:hover { border-color: #cbd5e1; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }

.kpi-icon {
  width: 2.5rem;
  height: 2.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 0.5rem;
}
.kpi-icon--slate { background: #f1f5f9; color: #475569; }
.kpi-icon--green { background: #dcfce7; color: #16a34a; }
.kpi-icon--amber { background: #fed7aa; color: #d97706; }
.kpi-icon--red { background: #fee2e2; color: #dc2626; }
.kpi-icon--blue { background: #dbeafe; color: #2563eb; }
.kpi-icon--purple { background: #f3e8ff; color: #9333ea; }

.kpi-content { display: flex; flex-direction: column; }
.kpi-label { font-size: 0.6rem; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.03em; }
.kpi-value { font-size: 1.25rem; font-weight: 700; color: #0f172a; line-height: 1.3; }
.kpi-value--green { color: #16a34a; }
.kpi-value--amber { color: #d97706; }
.kpi-value--red { color: #dc2626; }
.kpi-trend { font-size: 0.6rem; color: #94a3b8; }

/* ────────────────────────────────────────────────────────────────────────── */
/* FILTER BAR                                                                */
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
  padding: 0.75rem 1rem;
  margin-bottom: 1.5rem;
}

.filter-search {
  position: relative;
  flex: 1;
  min-width: 200px;
  max-width: 300px;
}
.filter-icon {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
}
.filter-input {
  width: 100%;
  padding: 0.5rem 0.75rem 0.5rem 2rem;
  font-size: 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  background: #fafafa;
  outline: none;
  transition: all 0.15s;
}
.filter-input:focus { border-color: #3b82f6; background: white; box-shadow: 0 0 0 2px rgba(59,130,246,0.1); }
.filter-clear {
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

.filter-checkbox {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  font-size: 0.75rem;
  cursor: pointer;
  border-radius: 0.5rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
}
.filter-checkbox input { cursor: pointer; }

.btn-reset {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.5rem 0.75rem;
  font-size: 0.7rem;
  font-weight: 500;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  cursor: pointer;
  color: #64748b;
  transition: all 0.1s;
}
.btn-reset:hover { background: #f8fafc; color: #1e293b; }

/* ────────────────────────────────────────────────────────────────────────── */
/* DATA TABLE                                                                */
/* ────────────────────────────────────────────────────────────────────────── */

.table-container {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  overflow: hidden;
}

.table-wrapper {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.75rem;
}

.data-table thead th {
  text-align: left;
  padding: 0.875rem 1rem;
  background: #f8fafc;
  font-weight: 600;
  color: #475569;
  border-bottom: 1px solid #e2e8f0;
}

.data-table tbody td {
  padding: 0.875rem 1rem;
  border-bottom: 1px solid #f1f5f9;
  color: #334155;
}

.data-table tbody tr:hover td { background: #fafbff; }
.row-anomaly td { background: #fef2f2; }

.th-actions { text-align: right; }

/* Cell Styles */
.td-meter .meter-info { display: flex; flex-direction: column; gap: 0.25rem; }
.meter-number { font-weight: 600; color: #0f172a; }
.meter-type { font-size: 0.65rem; color: #64748b; }

.td-property .property-info { display: flex; flex-direction: column; gap: 0.25rem; }
.building-name { font-weight: 500; }
.unit-number { font-size: 0.65rem; color: #64748b; }

.td-reading .reading-info { display: flex; flex-direction: column; gap: 0.25rem; }
.reading-date { font-weight: 500; }
.reading-values { font-size: 0.65rem; color: #64748b; }

.td-consumption .consumption-value { font-size: 0.9rem; font-weight: 700; color: #0f172a; }

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.25rem 0.625rem;
  border-radius: 2rem;
  font-size: 0.65rem;
  font-weight: 600;
}
.status-dot-small { width: 0.25rem; height: 0.25rem; border-radius: 50%; background: currentColor; }

.badge-approved { background: #dcfce7; color: #166534; }
.badge-verified { background: #dbeafe; color: #1e40af; }
.badge-draft { background: #fed7aa; color: #9a3412; }
.badge-rejected { background: #fee2e2; color: #991b1b; }
.badge-default { background: #f1f5f9; color: #475569; }

.td-anomaly { text-align: center; }
.anomaly-badge {
  display: inline-block;
  padding: 0.25rem 0.625rem;
  border-radius: 0.375rem;
  font-size: 0.65rem;
  font-weight: 600;
}
.anomaly-high { background: #fee2e2; color: #991b1b; }
.anomaly-medium { background: #fed7aa; color: #9a3412; }
.anomaly-low { background: #fef3c7; color: #92400e; }
.anomaly-none { color: #cbd5e1; }
.anomaly-reason { font-size: 0.6rem; color: #dc2626; margin-top: 0.25rem; }

.td-reader .reader-name { font-weight: 500; }

.td-actions { text-align: right; }
.action-buttons {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}
.action-link {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.75rem;
  height: 1.75rem;
  border-radius: 0.375rem;
  background: #f8fafc;
  color: #64748b;
  text-decoration: none;
  transition: all 0.1s;
}
.action-link:hover { background: #eef2ff; color: #3b82f6; }

.action-btn {
  width: 1.75rem;
  height: 1.75rem;
  border: none;
  border-radius: 0.375rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.1s;
}
.action-btn--approve { background: #dcfce7; color: #166534; }
.action-btn--approve:hover { background: #bbf7d0; transform: scale(1.05); }
.action-btn--reject { background: #fee2e2; color: #991b1b; }
.action-btn--reject:hover { background: #fecaca; transform: scale(1.05); }
.action-btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

.btn-spinner-small {
  width: 0.75rem;
  height: 0.75rem;
  border: 2px solid currentColor;
  border-top-color: transparent;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
  display: inline-block;
}

/* Empty & Loading States */
.empty-state, .loading-state {
  text-align: center;
  padding: 3rem !important;
  color: #94a3b8;
}
.empty-state p { font-size: 0.9rem; font-weight: 500; color: #64748b; margin: 0.5rem 0; }
.empty-state span { font-size: 0.7rem; }
.empty-icon { font-size: 2.5rem; margin-bottom: 0.5rem; }

.loading-state { display: flex; align-items: center; justify-content: center; gap: 0.5rem; }
.loading-spinner-small {
  width: 1rem;
  height: 1rem;
  border: 2px solid #e2e8f0;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

/* Table Footer & Pagination */
.table-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 1rem;
  border-top: 1px solid #e2e8f0;
  background: #fafafa;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.pagination-info { font-size: 0.7rem; color: #64748b; }
.pagination-divider { margin: 0 0.5rem; color: #cbd5e1; }

.pagination-controls { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
.pagination-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.375rem 0.75rem;
  font-size: 0.7rem;
  font-weight: 500;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 0.375rem;
  cursor: pointer;
  color: #475569;
  transition: all 0.1s;
}
.pagination-btn:hover:not(:disabled) { background: #f8fafc; border-color: #cbd5e1; }
.pagination-btn:disabled { opacity: 0.4; cursor: not-allowed; }

.pagination-pages { display: flex; gap: 0.25rem; }
.pagination-page {
  width: 2rem;
  padding: 0.375rem 0;
  text-align: center;
  font-size: 0.7rem;
  font-weight: 500;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 0.375rem;
  cursor: pointer;
  color: #475569;
  transition: all 0.1s;
}
.pagination-page:hover:not(.pagination-page--active) { background: #f8fafc; border-color: #cbd5e1; }
.pagination-page--active {
  background: #3b82f6;
  border-color: #3b82f6;
  color: white;
}

@keyframes spin { to { transform: rotate(360deg); } }
</style>