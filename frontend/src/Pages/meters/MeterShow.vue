<template>
  <div class="compact-meter-show">
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- LOADING STATE                                                    -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <div v-if="loading" class="compact-loading">
      <div class="loading-spinner"></div>
      <p>Loading meter details...</p>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- MAIN CONTENT                                                     -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <div v-else>
      <!-- ────────────────────────────────────────────────────────────── -->
      <!-- COMPACT HEADER                                                  -->
      <!-- ────────────────────────────────────────────────────────────── -->
      <div class="compact-header">
        <div class="compact-header-left">
          <div class="compact-breadcrumb">
            <RouterLink :to="{ name: 'Meters' }" class="breadcrumb-link">Meters</RouterLink>
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="9 18 15 12 9 6"/>
            </svg>
            <span class="breadcrumb-current">{{ meter.meter_number || 'Details' }}</span>
          </div>
          <div class="header-title-group">
            <h1 class="compact-title">{{ meter.meter_number }}</h1>
            <span :class="['compact-status-badge', statusBadgeClass(meter.status?.value)]">
              <span class="status-dot"></span>
              {{ meter.status?.label || 'Unknown' }}
            </span>
            <span v-if="meter.smart_features?.is_smart_meter" class="smart-badge">
              <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="3"/>
                <path d="M19.4 4.6C18.3 3.5 17 2.8 15.5 2.5M4.6 4.6C3.5 5.7 2.8 7 2.5 8.5M19.4 19.4c1.1-1.1 1.8-2.4 2.1-3.9M4.6 19.4c-1.1-1.1-1.8-2.4-2.1-3.9"/>
              </svg>
              Smart Meter
            </span>
          </div>
          <p class="compact-subtitle">Enterprise utility meter operational intelligence</p>
        </div>
        <div class="compact-header-right">
          <RouterLink :to="{ name: 'Meters' }" class="btn-outline">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="19" y1="12" x2="5" y2="12"/>
              <polyline points="12 19 5 12 12 5"/>
            </svg>
            Back
          </RouterLink>
          <RouterLink :to="{ name: 'MeterEdit', params: { id: meter.id } }" class="btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
              <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
            Edit
          </RouterLink>
        </div>
      </div>

      <!-- ────────────────────────────────────────────────────────────── -->
      <!-- COMPACT KPI STRIP                                              -->
      <!-- ────────────────────────────────────────────────────────────── -->
      <div class="kpi-strip">
        <div class="kpi-card">
          <div class="kpi-icon kpi-icon--blue">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/>
              <polyline points="12 6 12 12 16 14"/>
            </svg>
          </div>
          <div class="kpi-content">
            <span class="kpi-label">Current Reading</span>
            <strong class="kpi-value">{{ formatNumber(meter.readings?.current_reading) }}</strong>
            <span class="kpi-unit">{{ meter.measurement_unit || 'kWh' }}</span>
          </div>
        </div>
        <div class="kpi-card">
          <div class="kpi-icon kpi-icon--green">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M2 20L22 20M4 20L4 14M12 20L12 8M20 20L20 10"/>
            </svg>
          </div>
          <div class="kpi-content">
            <span class="kpi-label">Multiplier Factor</span>
            <strong class="kpi-value">{{ meter.readings?.multiplier_factor || 1 }}</strong>
            <span class="kpi-unit">Billing Scale</span>
          </div>
        </div>
        <div class="kpi-card">
          <div class="kpi-icon kpi-icon--amber">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/>
              <line x1="12" y1="8" x2="12" y2="12"/>
              <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
          </div>
          <div class="kpi-content">
            <span class="kpi-label">Inspection Due</span>
            <strong class="kpi-value kpi-value--sm">{{ formatDate(meter.lifecycle?.inspection_due_date) || 'N/A' }}</strong>
            <span class="kpi-unit" :class="{ 'kpi-warning': meter.lifecycle?.inspection_due }">
              {{ meter.lifecycle?.inspection_due ? '⚠️ Overdue' : 'Scheduled' }}
            </span>
          </div>
        </div>
        <div class="kpi-card">
          <div class="kpi-icon kpi-icon--purple">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
              <line x1="16" y1="2" x2="16" y2="6"/>
              <line x1="8" y1="2" x2="8" y2="6"/>
              <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
          </div>
          <div class="kpi-content">
            <span class="kpi-label">Last Reading</span>
            <strong class="kpi-value kpi-value--sm">{{ formatDate(meter.readings?.last_reading_at) || 'N/A' }}</strong>
            <span class="kpi-unit">Activity</span>
          </div>
        </div>
      </div>

      <!-- ────────────────────────────────────────────────────────────── -->
      <!-- TWO-COLUMN LAYOUT                                               -->
      <!-- ────────────────────────────────────────────────────────────── -->
      <div class="compact-layout">
        
        <!-- MAIN CONTENT PANEL -->
        <div class="compact-main">
          
          <!-- Core Information -->
          <div class="info-card">
            <div class="card-header">
              <div class="card-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="10"/>
                  <line x1="12" y1="8" x2="12" y2="12"/>
                  <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
              </div>
              <h3 class="card-title">Meter Information</h3>
            </div>
            <div class="info-grid info-grid--2">
              <div class="info-row">
                <span class="info-label">Meter Number</span>
                <span class="info-value">{{ meter.meter_number }}</span>
              </div>
              <div class="info-row">
                <span class="info-label">Serial Number</span>
                <span class="info-value">{{ meter.serial_number || '—' }}</span>
              </div>
              <div class="info-row">
                <span class="info-label">Utility Type</span>
                <span class="info-value capitalize">{{ meter.utility_type?.label }}</span>
              </div>
              <div class="info-row">
                <span class="info-label">Meter Type</span>
                <span class="info-value capitalize">{{ meter.meter_type?.label }}</span>
              </div>
              <div class="info-row">
                <span class="info-label">Ownership</span>
                <span class="info-value capitalize">{{ meter.ownership_type?.label }}</span>
              </div>
              <div class="info-row">
                <span class="info-label">Measurement Unit</span>
                <span class="info-value">{{ meter.measurement_unit }}</span>
              </div>
            </div>
          </div>

          <!-- Property Assignment -->
          <div class="info-card">
            <div class="card-header">
              <div class="card-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <rect x="3" y="3" width="18" height="18" rx="2"/>
                  <line x1="9" y1="3" x2="9" y2="21"/>
                </svg>
              </div>
              <h3 class="card-title">Property Assignment</h3>
            </div>
            <div class="info-grid info-grid--2">
              <div class="info-row">
                <span class="info-label">Building</span>
                <span class="info-value">{{ meter.building?.name || '—' }}</span>
              </div>
              <div class="info-row">
                <span class="info-label">Apartment</span>
                <span class="info-value">Unit {{ meter.apartment?.unit_number || '—' }}</span>
              </div>
              <div class="info-row">
                <span class="info-label">Tenant</span>
                <span class="info-value">{{ meter.tenant?.name || '—' }}</span>
              </div>
              <div class="info-row">
                <span class="info-label">Shared Meter</span>
                <span class="info-value">
                  <span :class="meter.operational_indicators?.is_shared ? 'badge-yes' : 'badge-no'">
                    {{ meter.operational_indicators?.is_shared ? 'Yes' : 'No' }}
                  </span>
                </span>
              </div>
            </div>
          </div>

          <!-- Lifecycle Information -->
          <div class="info-card">
            <div class="card-header">
              <div class="card-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M12 2v4M12 22v-4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M22 12h-4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                </svg>
              </div>
              <h3 class="card-title">Lifecycle Information</h3>
            </div>
            <div class="info-grid info-grid--2">
              <div class="info-row">
                <span class="info-label">Installation Date</span>
                <span class="info-value">{{ formatDate(meter.lifecycle?.installation_date) || '—' }}</span>
              </div>
              <div class="info-row">
                <span class="info-label">Last Maintenance</span>
                <span class="info-value">{{ formatDate(meter.lifecycle?.last_maintenance_at) || '—' }}</span>
              </div>
              <div class="info-row">
                <span class="info-label">Last Inspection</span>
                <span class="info-value">{{ formatDate(meter.lifecycle?.last_inspected_at) || '—' }}</span>
              </div>
              <div class="info-row">
                <span class="info-label">Maintenance Required</span>
                <span class="info-value">
                  <span :class="meter.lifecycle?.maintenance_required ? 'badge-warning' : 'badge-success'">
                    {{ meter.lifecycle?.maintenance_required ? 'Yes' : 'No' }}
                  </span>
                </span>
              </div>
            </div>
          </div>

          <!-- Notes -->
          <div class="info-card" v-if="meter.notes">
            <div class="card-header">
              <div class="card-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
                </svg>
              </div>
              <h3 class="card-title">Operational Notes</h3>
            </div>
            <div class="notes-content">
              <p>{{ meter.notes }}</p>
            </div>
          </div>
        </div>

        <!-- SIDEBAR -->
        <div class="compact-sidebar">
          
          <!-- Operational Controls -->
          <div class="action-card">
            <div class="action-card-header">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
              </svg>
              <span>Operational Controls</span>
            </div>
            <div class="action-list">
              <button @click="activateMeter" class="action-btn action-btn--green">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="20 6 9 17 4 12"/>
                </svg>
                Activate Meter
              </button>
              <button @click="markFaulty" class="action-btn action-btn--red">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="10"/>
                  <line x1="12" y1="8" x2="12" y2="12"/>
                  <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                Mark Faulty
              </button>
              <button @click="maintenanceMeter" class="action-btn action-btn--amber">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                  <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                Under Maintenance
              </button>
              <button @click="completeInspection" class="action-btn action-btn--blue">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                  <polyline points="14 2 14 8 20 8"/>
                  <line x1="16" y1="13" x2="8" y2="13"/>
                  <line x1="16" y1="17" x2="8" y2="17"/>
                </svg>
                Complete Inspection
              </button>
              <button @click="decommissionMeter" class="action-btn action-btn--gray">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="3 6 5 6 21 6"/>
                  <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                </svg>
                Decommission
              </button>
            </div>
          </div>

          <!-- Smart Features -->
          <div class="info-sidebar-card" v-if="meter.smart_features?.is_smart_meter">
            <div class="sidebar-card-header">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="3"/>
                <path d="M19.4 4.6C18.3 3.5 17 2.8 15.5 2.5M4.6 4.6C3.5 5.7 2.8 7 2.5 8.5M19.4 19.4c1.1-1.1 1.8-2.4 2.1-3.9M4.6 19.4c-1.1-1.1-1.8-2.4-2.1-3.9"/>
              </svg>
              <span>Smart Features</span>
            </div>
            <div class="sidebar-list">
              <div class="sidebar-row">
                <span class="sidebar-label">Smart Meter</span>
                <span class="sidebar-value badge-enabled">{{ meter.smart_features?.is_smart_meter ? 'Enabled' : 'No' }}</span>
              </div>
              <div class="sidebar-row">
                <span class="sidebar-label">Remote Reading</span>
                <span class="sidebar-value">{{ meter.smart_features?.supports_remote_reading ? 'Supported' : 'Not Supported' }}</span>
              </div>
            </div>
          </div>

          <!-- Audit Trail -->
          <div class="info-sidebar-card">
            <div class="sidebar-card-header">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 8v4l3 3M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20z"/>
              </svg>
              <span>Audit Trail</span>
            </div>
            <div class="sidebar-list">
              <div class="sidebar-row">
                <span class="sidebar-label">Created</span>
                <span class="sidebar-value">{{ formatDate(meter.audit?.created_at) }}</span>
              </div>
              <div class="sidebar-row">
                <span class="sidebar-label">Updated</span>
                <span class="sidebar-value">{{ formatDate(meter.audit?.updated_at) }}</span>
              </div>
              <div class="sidebar-row">
                <span class="sidebar-label">Created By</span>
                <span class="sidebar-value">{{ meter.audit?.created_by?.name || '—' }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ────────────────────────────────────────────────────────────── -->
    <!-- ERROR STATE                                                     -->
    <!-- ────────────────────────────────────────────────────────────── -->
    <div v-if="error" class="error-alert">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/>
        <line x1="12" y1="8" x2="12" y2="12"/>
        <line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      {{ error }}
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { RouterLink } from 'vue-router'
import api from '@/services/api'
import { useConfirm } from '@/composables/useConfirm'

const route = useRoute()
const loading = ref(true)
const error = ref(null)
const meter = ref({})

const fetchMeter = async () => {
  loading.value = true
  error.value = null
  try {
    const response = await api.get(`/meters/${route.params.id}`)
    meter.value = response.data.data
  } catch (err) {
    console.error('Meter Show Error:', err)
    error.value = 'Failed to load meter details.'
  } finally {
    loading.value = false
  }
}

const lifecycleAction = async (endpoint) => {
  try {
    await api.post(`/meters/${route.params.id}/${endpoint}`)
    fetchMeter()
  } catch (error) {
    console.error(error)
  }
}

const activateMeter = () => lifecycleAction('activate')
const markFaulty = () => lifecycleAction('faulty')
const maintenanceMeter = () => lifecycleAction('maintenance')
const completeInspection = () => lifecycleAction('inspection/complete')
const decommissionMeter = async () => {
  const { confirm } = useConfirm()
  const ok = await confirm({
    title: 'Decommission meter',
    message: 'This meter will be marked decommissioned and removed from active billing. Continue?',
    confirmLabel: 'Decommission',
  })
  if (!ok) return
  lifecycleAction('decommission')
}

const formatNumber = (value) => {
  if (!value && value !== 0) return '—'
  return value.toLocaleString()
}

const formatDate = (dateString) => {
  if (!dateString) return null
  return new Date(dateString).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

const statusBadgeClass = (status) => {
  const map = {
    active: 'badge-green',
    faulty: 'badge-red',
    under_maintenance: 'badge-amber',
    decommissioned: 'badge-slate',
  }
  return map[status] || 'badge-slate'
}

onMounted(() => {
  fetchMeter()
})
</script>

<style scoped>
/* ═══════════════════════════════════════════════════════════════════════════ */
/* COMPACT METER SHOW — PRODUCTION GRADE ERP                                   */
/* ═══════════════════════════════════════════════════════════════════════════ */

.compact-meter-show {
  max-width: 1400px;
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

.header-title-group {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
  margin-bottom: 0.25rem;
}

.compact-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #0f172a;
  letter-spacing: -0.02em;
  margin: 0;
}

.compact-status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.25rem 0.75rem;
  border-radius: 2rem;
  font-size: 0.7rem;
  font-weight: 600;
}
.status-dot { width: 0.375rem; height: 0.375rem; border-radius: 50%; background: currentColor; }

.smart-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.25rem 0.75rem;
  background: #dcfce7;
  border-radius: 2rem;
  font-size: 0.65rem;
  font-weight: 600;
  color: #166534;
}

.compact-subtitle {
  font-size: 0.75rem;
  color: #64748b;
}

.compact-header-right {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-outline, .btn-primary {
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
.btn-outline:hover { background: #f8fafc; }
.btn-primary {
  background: #3b82f6;
  color: white;
}
.btn-primary:hover { background: #2563eb; transform: translateY(-1px); }

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
/* KPI STRIP                                                                  */
/* ────────────────────────────────────────────────────────────────────────── */

.kpi-strip {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
  margin-bottom: 1.5rem;
}
@media (max-width: 768px) { .kpi-strip { grid-template-columns: repeat(2, 1fr); } }

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
.kpi-icon--blue { background: #dbeafe; color: #2563eb; }
.kpi-icon--green { background: #dcfce7; color: #16a34a; }
.kpi-icon--amber { background: #fed7aa; color: #d97706; }
.kpi-icon--purple { background: #f3e8ff; color: #9333ea; }

.kpi-content { display: flex; flex-direction: column; }
.kpi-label { font-size: 0.6rem; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.03em; }
.kpi-value { font-size: 1.25rem; font-weight: 700; color: #0f172a; line-height: 1.3; }
.kpi-value--sm { font-size: 0.9rem; }
.kpi-unit { font-size: 0.6rem; color: #94a3b8; }
.kpi-warning { color: #dc2626; }

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

/* MAIN CONTENT */
.compact-main { display: flex; flex-direction: column; gap: 1.25rem; }

.info-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  overflow: hidden;
}
.card-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.875rem 1.25rem;
  background: #fafafa;
  border-bottom: 1px solid #f1f5f9;
}
.card-icon {
  width: 1.75rem;
  height: 1.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #eef2ff;
  border-radius: 0.5rem;
  color: #3b82f6;
}
.card-title {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #334155;
  margin: 0;
}

.info-grid {
  display: grid;
  gap: 0.5rem;
  padding: 1rem 1.25rem;
}
.info-grid--2 { grid-template-columns: repeat(2, 1fr); }
@media (max-width: 640px) { .info-grid--2 { grid-template-columns: 1fr; } }

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
  border-bottom: 1px solid #f8fafc;
}
.info-row:last-child { border-bottom: none; }
.info-label { font-size: 0.7rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.03em; }
.info-value { font-size: 0.85rem; font-weight: 500; color: #0f172a; }

.notes-content { padding: 1.25rem; }
.notes-content p { font-size: 0.8rem; line-height: 1.5; color: #475569; margin: 0; }

/* SIDEBAR */
.compact-sidebar { display: flex; flex-direction: column; gap: 1rem; }

.action-card, .info-sidebar-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  overflow: hidden;
}
.action-card-header, .sidebar-card-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  background: #fafafa;
  border-bottom: 1px solid #f1f5f9;
  font-size: 0.65rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #64748b;
}

.action-list { padding: 0.75rem 1rem; display: flex; flex-direction: column; gap: 0.5rem; }
.action-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: none;
  border-radius: 0.5rem;
  font-size: 0.7rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.1s;
}
.action-btn--green { background: #dcfce7; color: #166534; }
.action-btn--green:hover { background: #bbf7d0; transform: translateX(2px); }
.action-btn--red { background: #fee2e2; color: #991b1b; }
.action-btn--red:hover { background: #fecaca; transform: translateX(2px); }
.action-btn--amber { background: #fed7aa; color: #9a3412; }
.action-btn--amber:hover { background: #fde68a; transform: translateX(2px); }
.action-btn--blue { background: #dbeafe; color: #1e40af; }
.action-btn--blue:hover { background: #bfdbfe; transform: translateX(2px); }
.action-btn--gray { background: #f1f5f9; color: #475569; }
.action-btn--gray:hover { background: #e2e8f0; transform: translateX(2px); }

.sidebar-list { padding: 0.75rem 1rem; }
.sidebar-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
  border-bottom: 1px solid #f8fafc;
}
.sidebar-row:last-child { border-bottom: none; }
.sidebar-label { font-size: 0.7rem; color: #64748b; }
.sidebar-value { font-size: 0.75rem; font-weight: 500; color: #0f172a; }

/* Badges */
.badge-green { background: #dcfce7; color: #166534; }
.badge-red { background: #fee2e2; color: #991b1b; }
.badge-amber { background: #fed7aa; color: #9a3412; }
.badge-slate { background: #f1f5f9; color: #475569; }
.badge-yes { background: #dcfce7; color: #166534; padding: 0.125rem 0.5rem; border-radius: 2rem; font-size: 0.7rem; }
.badge-no { background: #f1f5f9; color: #64748b; padding: 0.125rem 0.5rem; border-radius: 2rem; font-size: 0.7rem; }
.badge-warning { background: #fed7aa; color: #9a3412; padding: 0.125rem 0.5rem; border-radius: 2rem; font-size: 0.7rem; }
.badge-success { background: #dcfce7; color: #166534; padding: 0.125rem 0.5rem; border-radius: 2rem; font-size: 0.7rem; }
.badge-enabled { background: #dcfce7; color: #166534; padding: 0.125rem 0.5rem; border-radius: 2rem; font-size: 0.7rem; }

.capitalize { text-transform: capitalize; }

/* Error Alert */
.error-alert {
  margin-top: 1.5rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 0.75rem;
  font-size: 0.75rem;
  color: #dc2626;
}
</style>