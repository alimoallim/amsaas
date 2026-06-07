<template>
  <div class="compact-meter-page">
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- COMPACT HEADER                                                   -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <div class="compact-header">
      <div class="compact-header-left">
        <div class="compact-breadcrumb">
          <RouterLink :to="{ name: 'Meters' }" class="breadcrumb-link">Meters</RouterLink>
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="9 18 15 12 9 6"/>
          </svg>
          <span class="breadcrumb-current">Edit {{ form.meter_number || 'Meter' }}</span>
        </div>
        <div class="header-title-group">
          <h1 class="compact-title">Edit Meter</h1>
          <span :class="['compact-status-badge', statusBadgeClass]">
            <span class="status-dot"></span>
            {{ form.status || 'N/A' }}
          </span>
        </div>
        <p class="compact-subtitle">Update utility meter operational and lifecycle information</p>
      </div>
      <div class="compact-header-right">
        <RouterLink
          :to="{ name: 'MeterShow', params: { id: route.params.id } }"
          class="btn-outline"
        >
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>
          </svg>
          View
        </RouterLink>
        <RouterLink :to="{ name: 'Meters' }" class="btn-secondary">
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
    <div v-if="loading" class="compact-loading">
      <div class="loading-spinner"></div>
      <p>Loading meter details...</p>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- COMPACT FORM                                                     -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <form v-else @submit.prevent="submitForm" class="compact-form">

      <!-- SECTION: Core Information -->
      <div class="form-section">
        <div class="section-header">
          <div class="section-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/>
              <line x1="12" y1="8" x2="12" y2="12"/>
              <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
          </div>
          <h3 class="section-title">Core Information</h3>
          <span class="section-badge">Required</span>
        </div>
        <div class="compact-grid compact-grid--4">
          <div class="field-group">
            <label class="compact-label">Meter Number *</label>
            <input v-model="form.meter_number" type="text" class="compact-input" placeholder="MTR-001">
            <p v-if="errors.meter_number" class="compact-error">{{ errors.meter_number[0] }}</p>
          </div>
          <div class="field-group">
            <label class="compact-label">Serial Number</label>
            <input v-model="form.serial_number" type="text" class="compact-input" placeholder="SN-123456">
          </div>
          <div class="field-group">
            <label class="compact-label">Utility Type *</label>
            <select v-model="form.utility_type" class="compact-input">
              <option value="electricity">Electricity</option>
              <option value="water">Water</option>
              <option value="gas">Gas</option>
              <option value="solar">Solar</option>
              <option value="chilled_water">Chilled Water</option>
            </select>
          </div>
          <div class="field-group">
            <label class="compact-label">Meter Type *</label>
            <select v-model="form.meter_type" class="compact-input">
              <option value="analog">Analog</option>
              <option value="digital">Digital</option>
              <option value="smart">Smart</option>
            </select>
          </div>
        </div>
      </div>

      <!-- SECTION: Property Assignment -->
      <div class="form-section">
        <div class="section-header">
          <div class="section-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="3" width="18" height="18" rx="2"/>
              <line x1="9" y1="3" x2="9" y2="21"/>
            </svg>
          </div>
          <h3 class="section-title">Property Assignment</h3>
        </div>
        <div class="compact-grid compact-grid--4">
          <div class="field-group">
            <label class="compact-label">Building</label>
            <select v-model="form.building_id" class="compact-input" @change="fetchApartments">
              <option value="">Select Building</option>
              <option v-for="building in buildings" :key="building.id" :value="building.id">
                {{ building.name }}
              </option>
            </select>
          </div>
          <div class="field-group">
            <label class="compact-label">Apartment</label>
            <select v-model="form.apartment_id" class="compact-input">
              <option value="">Select Apartment</option>
              <option v-for="apartment in apartments" :key="apartment.id" :value="apartment.id">
                Unit {{ apartment.unit_number }}
              </option>
            </select>
          </div>
          <div class="field-group">
            <label class="compact-label">Ownership Type</label>
            <select v-model="form.ownership_type" class="compact-input">
              <option value="apartment">Apartment</option>
              <option value="building">Building</option>
              <option value="shared">Shared</option>
              <option value="tenant">Tenant</option>
            </select>
          </div>
          <div class="field-group">
            <label class="compact-label">Lifecycle Status</label>
            <select v-model="form.status" class="compact-input">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="faulty">Faulty</option>
              <option value="under_maintenance">Under Maintenance</option>
              <option value="decommissioned">Decommissioned</option>
            </select>
          </div>
        </div>
      </div>

      <!-- SECTION: Operational Information -->
      <div class="form-section">
        <div class="section-header">
          <div class="section-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 2v4M12 22v-4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M22 12h-4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
            </svg>
          </div>
          <h3 class="section-title">Operational Information</h3>
        </div>
        <div class="compact-grid compact-grid--4">
          <div class="field-group">
            <label class="compact-label">Current Reading</label>
            <div class="input-prefix">
              <span class="prefix-symbol">kWh</span>
              <input v-model.number="form.current_reading" type="number" step="0.0001" class="compact-input compact-input--prefix" placeholder="0.00">
            </div>
          </div>
          <div class="field-group">
            <label class="compact-label">Multiplier Factor</label>
            <input v-model.number="form.multiplier_factor" type="number" step="0.0001" class="compact-input" placeholder="1.00">
          </div>
          <div class="field-group">
            <label class="compact-label">Installation Date</label>
            <ErpDateInput v-model="form.installation_date" input-class="compact-input" placeholder="Installation date" />
          </div>
          <div class="field-group">
            <label class="compact-label">Inspection Due</label>
            <ErpDateInput
              v-model="form.inspection_due_date"
              input-class="compact-input"
              placeholder="Inspection due"
              :min="form.installation_date || ''"
            />
          </div>
        </div>

        <!-- Feature Toggles -->
        <div class="feature-toggles">
          <label class="toggle-item">
            <input v-model="form.is_shared" type="checkbox" class="toggle-checkbox">
            <span class="toggle-label-text">Shared Meter</span>
          </label>
          <label class="toggle-item">
            <input v-model="form.supports_remote_reading" type="checkbox" class="toggle-checkbox">
            <span class="toggle-label-text">Remote Reading</span>
          </label>
          <label class="toggle-item">
            <input v-model="form.maintenance_required" type="checkbox" class="toggle-checkbox">
            <span class="toggle-label-text">Requires Maintenance</span>
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
          <h3 class="section-title">Operational Notes</h3>
        </div>
        <textarea v-model="form.notes" rows="3" class="compact-textarea" placeholder="Maintenance notes, inspections, and operational comments..."></textarea>
      </div>

      <!-- FORM ACTIONS -->
      <div class="form-actions">
        <button type="button" @click="decommissionMeter" class="btn-danger">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
          Decommission Meter
        </button>
        <div class="actions-group">
          <RouterLink :to="{ name: 'Meters' }" class="btn-secondary">
            Cancel
          </RouterLink>
          <button type="submit" :disabled="processing" class="btn-primary">
            <span v-if="processing" class="btn-spinner"></span>
            <span v-else>Update Meter</span>
          </button>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { RouterLink } from 'vue-router'
import api from '@/services/api'
import { ErpDateInput } from '@/components/erp'
import { useConfirm } from '@/composables/useConfirm'

const route = useRoute()
const router = useRouter()

const loading = ref(true)
const processing = ref(false)
const errors = ref({})
const buildings = ref([])
const apartments = ref([])

const form = reactive({
  meter_number: '',
  serial_number: '',
  utility_type: '',
  ownership_type: '',
  meter_type: '',
  measurement_unit: '',
  building_id: '',
  apartment_id: '',
  tenant_id: '',
  current_reading: 0,
  multiplier_factor: 1,
  installation_date: '',
  inspection_due_date: '',
  status: 'active',
  is_shared: false,
  supports_remote_reading: false,
  maintenance_required: false,
  notes: '',
})

const statusBadgeClass = computed(() => {
  switch (form.status) {
    case 'active': return 'badge-green'
    case 'faulty': return 'badge-red'
    case 'under_maintenance': return 'badge-amber'
    case 'decommissioned': return 'badge-slate'
    default: return 'badge-slate'
  }
})

const fetchMeter = async () => {
  loading.value = true
  try {
    const response = await api.get(`/meters/${route.params.id}`)
    const meter = response.data.data
    Object.assign(form, {
      meter_number: meter.meter_number,
      serial_number: meter.serial_number,
      utility_type: meter.utility_type?.value,
      ownership_type: meter.ownership_type?.value,
      meter_type: meter.meter_type?.value,
      measurement_unit: meter.measurement_unit,
      building_id: meter.building?.id,
      apartment_id: meter.apartment?.id,
      tenant_id: meter.tenant?.id,
      current_reading: meter.readings?.current_reading,
      multiplier_factor: meter.readings?.multiplier_factor,
      installation_date: meter.lifecycle?.installation_date,
      inspection_due_date: meter.lifecycle?.inspection_due_date,
      status: meter.status?.value,
      is_shared: meter.operational_indicators?.is_shared,
      supports_remote_reading: meter.smart_features?.supports_remote_reading,
      maintenance_required: meter.lifecycle?.maintenance_required,
      notes: meter.notes,
    })
    await fetchApartments()
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

const fetchApartments = async () => {
  if (!form.building_id) {
    apartments.value = []
    return
  }
  try {
    const response = await api.get('/apartments', { params: { building_id: form.building_id } })
    apartments.value = response.data.data || []
  } catch (error) {
    console.error(error)
  }
}

const submitForm = async () => {
  processing.value = true
  errors.value = {}
  try {
    await api.put(`/meters/${route.params.id}`, form)
    router.push({ name: 'MeterShow', params: { id: route.params.id } })
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors
    }
    console.error(error)
  } finally {
    processing.value = false
  }
}

const decommissionMeter = async () => {
  const { confirm } = useConfirm()
  const ok = await confirm({
    title: 'Decommission meter',
    message: 'This meter will be marked decommissioned. Continue?',
    confirmLabel: 'Decommission',
  })
  if (!ok) return
  try {
    await api.post(`/meters/${route.params.id}/decommission`)
    fetchMeter()
  } catch (error) {
    console.error(error)
  }
}

onMounted(async () => {
  await fetchBuildings()
  await fetchMeter()
})
</script>

<style scoped>
/* ═══════════════════════════════════════════════════════════════════════════ */
/* COMPACT METER PAGE — PRODUCTION GRADE                                       */
/* ═══════════════════════════════════════════════════════════════════════════ */

.compact-meter-page {
  max-width: 1280px;
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

.status-dot {
  width: 0.375rem;
  height: 0.375rem;
  border-radius: 50%;
  background: currentColor;
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
/* FORM                                                                       */
/* ────────────────────────────────────────────────────────────────────────── */

.compact-form {
  background: white;
  border-radius: 1rem;
  border: 1px solid #e2e8f0;
  overflow: hidden;
  padding: 1.5rem;
}

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

/* Grid */
.compact-grid {
  display: grid;
  gap: 1rem;
}
.compact-grid--4 { grid-template-columns: repeat(4, 1fr); }
@media (max-width: 768px) { .compact-grid--4 { grid-template-columns: 1fr; } }

/* Form Elements */
.field-group { display: flex; flex-direction: column; gap: 0.375rem; }

.compact-label {
  font-size: 0.7rem;
  font-weight: 600;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

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

.compact-error {
  font-size: 0.65rem;
  color: #dc2626;
  margin-top: 0.25rem;
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
  font-size: 0.7rem;
  font-weight: 500;
  color: #64748b;
}
.compact-input--prefix {
  padding-left: 2.5rem;
  width: 100%;
}

/* Feature Toggles */
.feature-toggles {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  margin-top: 1rem;
  padding-top: 0.5rem;
}

.toggle-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
}

.toggle-checkbox {
  width: 1rem;
  height: 1rem;
  accent-color: #3b82f6;
  cursor: pointer;
}

.toggle-label-text {
  font-size: 0.75rem;
  font-weight: 500;
  color: #475569;
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

.actions-group {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.btn-primary, .btn-danger, .btn-secondary {
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

.btn-danger {
  background: white;
  color: #dc2626;
  border: 1px solid #fecaca;
}
.btn-danger:hover { background: #fef2f2; border-color: #fecaca; }

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

/* Badges */
.badge-green { background: #dcfce7; color: #166534; }
.badge-red { background: #fee2e2; color: #991b1b; }
.badge-amber { background: #fed7aa; color: #9a3412; }
.badge-slate { background: #f1f5f9; color: #475569; }
</style>