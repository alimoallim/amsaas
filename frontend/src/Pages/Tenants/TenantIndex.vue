<template>
 
    <div class="tenant-page h-full flex flex-col">

      <!-- ══════════════════════════════════════════════════════════ -->
      <!-- Page Header -->
      <!-- ══════════════════════════════════════════════════════════ -->
      <div class="page-header">
        <div class="page-header__text">
          <div class="eyebrow">Property Operations</div>
          <h1 class="page-title">Tenants</h1>
          <p class="page-subtitle">Manage tenant records, agreements, and operational status</p>
        </div>
        <RouterLink :to="{ name: 'TenantCreate' }" class="btn-primary">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          New Tenant
        </RouterLink>
      </div>

      <!-- ══════════════════════════════════════════════════════════ -->
      <!-- Stats Strip -->
      <!-- ══════════════════════════════════════════════════════════ -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon stat-icon--slate">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
          </div>
          <div class="stat-body">
            <p class="stat-label">Total Tenants</p>
            <p class="stat-value">{{ summary.total }}</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon stat-icon--emerald">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
          </div>
          <div class="stat-body">
            <p class="stat-label">Active</p>
            <p class="stat-value stat-value--emerald">{{ summary.active }}</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon stat-icon--amber">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
            </svg>
          </div>
          <div class="stat-body">
            <p class="stat-label">Pending</p>
            <p class="stat-value stat-value--amber">{{ summary.pending }}</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon stat-icon--red">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
            </svg>
          </div>
          <div class="stat-body">
            <p class="stat-label">Blacklisted</p>
            <p class="stat-value stat-value--red">{{ summary.blacklisted }}</p>
          </div>
        </div>
      </div>

      <!-- ══════════════════════════════════════════════════════════ -->
      <!-- Main Table Card -->
      <!-- ══════════════════════════════════════════════════════════ -->
      <div class="table-card">

        <!-- Toolbar -->
        <div class="toolbar">
          <div class="search-wrap">
            <svg class="search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input
              v-model="filters.search"
              type="text"
              placeholder="Search name, code, email…"
              class="search-input"
            />
          </div>

          <div class="toolbar-filters">
            <select v-model="filters.status" class="filter-select">
              <option value="">All Statuses</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="pending">Pending</option>
              <option value="blacklisted">Blacklisted</option>
            </select>

            <select v-model="filters.tenant_type" class="filter-select">
              <option value="">All Types</option>
              <option value="individual">Individual</option>
              <option value="company">Company</option>
              <option value="government">Government</option>
              <option value="ngo">NGO</option>
            </select>

            <button @click="resetFilters" class="btn-reset">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="1 4 1 10 7 10"/>
                <path d="M3.51 15a9 9 0 1 0 .49-3.51"/>
              </svg>
              Reset
            </button>
          </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="state-view">
          <div class="spinner"></div>
          <p class="state-label">Loading tenants…</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="tenants.length === 0" class="state-view">
          <div class="empty-icon-wrap">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
          </div>
          <h3 class="empty-title">No tenants found</h3>
          <p class="empty-sub">Adjust your filters or create a new tenant record.</p>
          <RouterLink :to="{ name: 'TenantCreate' }" class="btn-primary" style="margin-top: 8px;">New Tenant</RouterLink>
        </div>

        <!-- Table -->
        <div v-else class="table-scroll">
          <table class="data-table">
            <thead>
              <tr>
                <th>Tenant</th>
                <th>Code</th>
                <th>Type</th>
                <th>Contact</th>
                <th>Nationality</th>
                <th>Status</th>
                <th style="text-align:right;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="tenant in tenants" :key="tenant.id" class="table-row">

                <td class="cell">
                  <div class="tenant-cell">
                    <div class="avatar" :class="avatarColorClass(tenant.status?.value)">
                      {{ getInitials(tenant.display_name) }}
                    </div>
                    <div>
                      <p class="cell-primary">{{ tenant.display_name }}</p>
                      <p class="cell-secondary">{{ tenant.contact?.email || '—' }}</p>
                    </div>
                  </div>
                </td>

                <td class="cell">
                  <span class="badge-mono">{{ tenant.tenant_code || 'N/A' }}</span>
                </td>

                <td class="cell">
                  <span :class="['type-pill', typeBadgeClass(tenant.tenant_type)]">
                    {{ formatType(tenant.tenant_type) }}
                  </span>
                </td>

                <td class="cell">
                  <p class="cell-primary">{{ tenant.contact?.phone || '—' }}</p>
                  <p class="cell-secondary">{{ tenant.contact?.email || '' }}</p>
                </td>

                <td class="cell">
                  <span class="cell-text">{{ tenant.identity?.nationality || '—' }}</span>
                </td>

                <td class="cell">
                  <span :class="['status-pill', statusBadgeClass(tenant.status?.value)]">
                    <span class="status-dot"></span>
                    {{ formatStatus(tenant.status?.value) }}
                  </span>
                </td>

                <td class="cell" style="text-align:right;">
                  <div class="actions">
                    <button @click="openViewModal(tenant)" class="act-btn" title="View details">
                      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                      </svg>
                    </button>
                    <RouterLink
                      :to="{ name: 'TenantEdit', params: { id: tenant.id } }"
                      class="act-btn act-btn--edit"
                      title="Edit"
                    >
                      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                      </svg>
                    </RouterLink>
                  </div>
                </td>

              </tr>
            </tbody>
          </table>
        </div>

      </div>
      <!-- /table-card -->

    </div>
    <!-- /tenant-page -->

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- View Modal — Teleported to <body> to escape stacking ctx  -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showViewModal"
          class="modal-overlay"
          @click.self="closeViewModal"
          role="dialog"
          aria-modal="true"
          :aria-label="selectedTenant?.display_name"
        >
          <div class="modal-panel">

            <!-- Modal Header -->
            <div class="modal-header">
              <div class="modal-header-identity">
                <div class="modal-avatar" :class="avatarColorClass(selectedTenant?.status?.value)">
                  {{ getInitials(selectedTenant?.display_name) }}
                </div>
                <div>
                  <h2 class="modal-name">{{ selectedTenant?.display_name }}</h2>
                  <p class="modal-code">{{ selectedTenant?.tenant_code || 'No Code Assigned' }}</p>
                </div>
              </div>
              <div class="modal-header-actions">
                <span :class="['status-pill', statusBadgeClass(selectedTenant?.status?.value)]">
                  <span class="status-dot"></span>
                  {{ formatStatus(selectedTenant?.status?.value) }}
                </span>
                <button @click="closeViewModal" class="modal-close" aria-label="Close">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                  </svg>
                </button>
              </div>
            </div>

            <!-- Modal Body -->
            <div class="modal-body">

              <!-- Key Metrics Row -->
              <div class="metrics-row">
                <div class="metric-tile">
                  <p class="metric-label">Type</p>
                  <p class="metric-val">{{ formatType(selectedTenant?.tenant_type) || '—' }}</p>
                </div>
                <div class="metric-tile">
                  <p class="metric-label">Nationality</p>
                  <p class="metric-val">{{ selectedTenant?.identity?.nationality || '—' }}</p>
                </div>
                <div class="metric-tile">
                  <p class="metric-label">National ID</p>
                  <p class="metric-val metric-val--mono">{{ selectedTenant?.identity?.national_id || '—' }}</p>
                </div>
                <div class="metric-tile">
                  <p class="metric-label">Passport</p>
                  <p class="metric-val metric-val--mono">{{ selectedTenant?.identity?.passport_number || '—' }}</p>
                </div>
              </div>

              <!-- Contact Information -->
              <div class="modal-section">
                <div class="section-header">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.64 1.18h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.91a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7a2 2 0 0 1 1.72 2.02z"/>
                  </svg>
                  Contact Information
                </div>
                <div class="detail-grid">
                  <div class="detail-item">
                    <p class="detail-label">Phone</p>
                    <p class="detail-val">{{ selectedTenant?.contact?.phone || '—' }}</p>
                  </div>
                  <div class="detail-item">
                    <p class="detail-label">Alternate Phone</p>
                    <p class="detail-val">{{ selectedTenant?.contact?.alternate_phone || '—' }}</p>
                  </div>
                  <div class="detail-item">
                    <p class="detail-label">Email</p>
                    <p class="detail-val">{{ selectedTenant?.contact?.email || '—' }}</p>
                  </div>
                  <div class="detail-item">
                    <p class="detail-label">Address</p>
                    <p class="detail-val">{{ selectedTenant?.address?.address || '—' }}</p>
                  </div>
                </div>
              </div>

              <!-- Identity & Classification -->
              <div class="modal-section">
                <div class="section-header">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="4" width="20" height="16" rx="2"/><path d="M7 8h.01M2 12h20M7 16h.01"/>
                  </svg>
                  Identity &amp; Classification
                </div>
                <div class="detail-grid">
                  <div class="detail-item">
                    <p class="detail-label">Tax Number</p>
                    <p class="detail-val">{{ selectedTenant?.identity?.tax_number || '—' }}</p>
                  </div>
                  <div class="detail-item">
                    <p class="detail-label">Occupation</p>
                    <p class="detail-val">{{ selectedTenant?.identity?.occupation || '—' }}</p>
                  </div>
                  <div class="detail-item">
                    <p class="detail-label">Gender</p>
                    <p class="detail-val">{{ selectedTenant?.identity?.gender || '—' }}</p>
                  </div>
                  <div class="detail-item">
                    <p class="detail-label">Date of Birth</p>
                    <p class="detail-val">{{ selectedTenant?.identity?.date_of_birth || '—' }}</p>
                  </div>
                </div>
              </div>

            </div>
            <!-- /modal-body -->

            <!-- Modal Footer -->
            <div class="modal-footer">
              <button @click="closeViewModal" class="btn-ghost">Close</button>
              <RouterLink
                :to="{ name: 'TenantEdit', params: { id: selectedTenant?.id } }"
                class="btn-primary"
                @click="closeViewModal"
              >
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
                Edit Tenant
              </RouterLink>
            </div>

          </div>
        </div>
      </Transition>
    </Teleport>


</template>

<script setup>
import { reactive, ref, onMounted, onUnmounted, watch } from 'vue'
import { RouterLink } from 'vue-router'
import api from '../../services/api'
import DashboardLayout from '../../layouts/DashboardLayout.vue'

/* ─── State ─────────────────────────────────────────────────── */
const loading        = ref(false)
const tenants        = ref([])
const showViewModal  = ref(false)
const selectedTenant = ref(null)
const summary        = ref({ total: 0, active: 0, pending: 0, blacklisted: 0 })
const filters        = reactive({ search: '', status: '', tenant_type: '' })

/* ─── Modal ─────────────────────────────────────────────────── */
const openViewModal  = (tenant) => { selectedTenant.value = tenant; showViewModal.value = true }
const closeViewModal = ()       => { showViewModal.value = false; selectedTenant.value = null }

/* ─── Fetch ─────────────────────────────────────────────────── */
const fetchTenants = async () => {
  loading.value = true
  try {
    const response = await api.get('/tenants', {
      params: {
        search:      filters.search,
        status:      filters.status,
        tenant_type: filters.tenant_type,
      }
    })
    tenants.value = response.data.data || []
    calculateSummary()
  } catch (error) {
    console.error(error)
  } finally {
    loading.value = false
  }
}

/* ─── Summary ───────────────────────────────────────────────── */
const calculateSummary = () => {
  summary.value.total       = tenants.value.length
  summary.value.active      = tenants.value.filter(t => t.status?.value === 'active').length
  summary.value.pending     = tenants.value.filter(t => t.status?.value === 'pending').length
  summary.value.blacklisted = tenants.value.filter(t => t.status?.value === 'blacklisted').length
}

/* ─── Filters ───────────────────────────────────────────────── */
const resetFilters = () => {
  filters.search = ''; filters.status = ''; filters.tenant_type = ''
  fetchTenants()
}

let debounceTimeout = null
watch(
  () => ({ ...filters }),
  () => { clearTimeout(debounceTimeout); debounceTimeout = setTimeout(fetchTenants, 350) },
  { deep: true }
)

/* ─── Escape key to close modal ─────────────────────────────── */
const onKeyDown = (e) => { if (e.key === 'Escape' && showViewModal.value) closeViewModal() }
onMounted  (() => { fetchTenants(); window.addEventListener('keydown', onKeyDown) })
onUnmounted(() => { window.removeEventListener('keydown', onKeyDown) })

/* ─── Helpers ───────────────────────────────────────────────── */
const getInitials  = (name = '') => name ? name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase() : '?'
const formatType   = (type)   => type   ? type.charAt(0).toUpperCase()   + type.slice(1)   : '—'
const formatStatus = (status) => status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Unknown'

const statusBadgeClass = (s) => ({ active:'status--active', pending:'status--pending', blacklisted:'status--blacklisted', inactive:'status--inactive' }[s] || 'status--default')
const typeBadgeClass   = (t) => ({ individual:'type--individual', company:'type--company', government:'type--government', ngo:'type--ngo' }[t] || 'type--default')
const avatarColorClass = (s) => ({ active:'av--emerald', pending:'av--amber', blacklisted:'av--red', inactive:'av--slate' }[s] || 'av--indigo')
</script>

<style scoped>
/* ═══════════════════════════════════════════════════════════
   Design Tokens
═══════════════════════════════════════════════════════════ */
.tenant-page {
  /* Palette */
  --c-bg:          #f8f9fb;
  --c-surface:     #ffffff;
  --c-border:      #e4e7ec;
  --c-border-soft: #f0f2f5;
  --c-text:        #101828;
  --c-muted:       #667085;
  --c-subtle:      #98a2b3;

  --c-indigo:      #4f46e5;
  --c-indigo-dk:   #4338ca;
  --c-indigo-lt:   #eef2ff;
  --c-indigo-ring: rgba(79,70,229,.15);

  --c-emerald:     #039855;
  --c-emerald-lt:  #d1fadf;
  --c-emerald-txt: #054f31;

  --c-amber:       #dc6803;
  --c-amber-lt:    #fef0c7;
  --c-amber-txt:   #7a2e0e;

  --c-red:         #d92d20;
  --c-red-lt:      #fee4e2;
  --c-red-txt:     #7a271a;

  /* Radii */
  --r-sm:  6px;
  --r-md:  10px;
  --r-lg:  14px;
  --r-xl:  18px;
  --r-2xl: 22px;

  /* Shadows */
  --sh-card:  0 1px 3px rgba(16,24,40,.06), 0 1px 2px rgba(16,24,40,.04);
  --sh-modal: 0 20px 60px -10px rgba(16,24,40,.25), 0 0 0 1px rgba(16,24,40,.06);

  font-family: 'DM Sans', ui-sans-serif, system-ui, sans-serif;
  color: var(--c-text);
}

/* ═══════════════════════════════════════════════════════════
   Page Header
═══════════════════════════════════════════════════════════ */
.page-header {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-bottom: 28px;
}
@media (min-width: 768px) {
  .page-header {
    flex-direction: row;
    align-items: flex-end;
    justify-content: space-between;
  }
}
.eyebrow {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .08em;
  text-transform: uppercase;
  color: var(--c-indigo);
  margin-bottom: 4px;
}
.page-title {
  font-size: 26px;
  font-weight: 700;
  letter-spacing: -.025em;
  color: var(--c-text);
  line-height: 1.2;
  margin: 0;
}
.page-subtitle {
  margin-top: 5px;
  font-size: 14px;
  color: var(--c-muted);
}

/* ═══════════════════════════════════════════════════════════
   Buttons
═══════════════════════════════════════════════════════════ */
.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 10px 18px;
  background: var(--c-indigo);
  color: #fff;
  font-size: 13.5px;
  font-weight: 600;
  border-radius: var(--r-lg);
  border: none;
  cursor: pointer;
  text-decoration: none;
  white-space: nowrap;
  box-shadow: 0 1px 3px rgba(79,70,229,.35), 0 1px 2px rgba(79,70,229,.2);
  transition: background .15s, box-shadow .15s, transform .1s;
}
.btn-primary:hover {
  background: var(--c-indigo-dk);
  box-shadow: 0 4px 12px rgba(79,70,229,.4);
  transform: translateY(-1px);
}
.btn-primary:active { transform: translateY(0); }

.btn-ghost {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 9px 16px;
  background: transparent;
  color: var(--c-muted);
  font-size: 13.5px;
  font-weight: 500;
  border-radius: var(--r-lg);
  border: 1px solid var(--c-border);
  cursor: pointer;
  text-decoration: none;
  transition: background .12s, color .12s;
}
.btn-ghost:hover { background: #f9fafb; color: var(--c-text); }

.btn-reset {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 9px 14px;
  background: transparent;
  color: var(--c-muted);
  font-size: 13px;
  font-weight: 500;
  border-radius: var(--r-md);
  border: 1px solid var(--c-border);
  cursor: pointer;
  white-space: nowrap;
  transition: background .12s, color .12s;
}
.btn-reset:hover { background: #f4f5f7; color: var(--c-text); }

/* ═══════════════════════════════════════════════════════════
   Stats
═══════════════════════════════════════════════════════════ */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
  gap: 14px;
  margin-bottom: 24px;
}
.stat-card {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 18px 20px;
  background: var(--c-surface);
  border: 1px solid var(--c-border);
  border-radius: var(--r-xl);
  box-shadow: var(--sh-card);
  transition: box-shadow .18s, transform .18s;
}
.stat-card:hover { box-shadow: 0 4px 14px rgba(16,24,40,.09); transform: translateY(-1px); }

.stat-icon {
  width: 40px; height: 40px;
  border-radius: var(--r-md);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.stat-icon--slate   { background: #f1f5f9; color: #475569; }
.stat-icon--emerald { background: var(--c-emerald-lt); color: var(--c-emerald); }
.stat-icon--amber   { background: var(--c-amber-lt);   color: var(--c-amber); }
.stat-icon--red     { background: var(--c-red-lt);     color: var(--c-red); }

.stat-body { display: flex; flex-direction: column; gap: 3px; }
.stat-label { font-size: 12px; font-weight: 500; color: var(--c-muted); }
.stat-value {
  font-size: 24px; font-weight: 700;
  letter-spacing: -.025em; line-height: 1;
  color: var(--c-text);
}
.stat-value--emerald { color: var(--c-emerald); }
.stat-value--amber   { color: var(--c-amber); }
.stat-value--red     { color: var(--c-red); }

/* ═══════════════════════════════════════════════════════════
   Table Card
═══════════════════════════════════════════════════════════ */
.table-card {
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
  background: var(--c-surface);
  border: 1px solid var(--c-border);
  border-radius: var(--r-2xl);
  box-shadow: var(--sh-card);
  overflow: hidden;
}

/* Toolbar */
.toolbar {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 18px;
  border-bottom: 1px solid var(--c-border-soft);
  flex-wrap: wrap;
}
.search-wrap {
  position: relative;
  flex: 1;
  min-width: 180px;
  max-width: 320px;
}
.search-icon {
  position: absolute;
  left: 12px; top: 50%;
  transform: translateY(-50%);
  color: var(--c-subtle);
  pointer-events: none;
}
.search-input {
  width: 100%;
  padding: 9px 12px 9px 36px;
  font-size: 13.5px;
  color: var(--c-text);
  background: #fafafa;
  border: 1px solid var(--c-border);
  border-radius: var(--r-md);
  outline: none;
  transition: border-color .15s, box-shadow .15s, background .15s;
}
.search-input::placeholder { color: var(--c-subtle); }
.search-input:focus {
  background: #fff;
  border-color: var(--c-indigo);
  box-shadow: 0 0 0 3px var(--c-indigo-ring);
}
.toolbar-filters {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-left: auto;
  flex-wrap: wrap;
}
.filter-select {
  padding: 9px 32px 9px 12px;
  font-size: 13.5px;
  color: var(--c-text);
  background: #fafafa;
  background-image: url("data:image/svg+xml,%3Csvg width='10' height='6' viewBox='0 0 10 6' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%2398a2b3' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 12px center;
  border: 1px solid var(--c-border);
  border-radius: var(--r-md);
  outline: none;
  appearance: none;
  cursor: pointer;
  transition: border-color .15s, box-shadow .15s;
}
.filter-select:focus {
  border-color: var(--c-indigo);
  box-shadow: 0 0 0 3px var(--c-indigo-ring);
}

/* ═══════════════════════════════════════════════════════════
   States (loading / empty)
═══════════════════════════════════════════════════════════ */
.state-view {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 24px;
  gap: 10px;
}
.spinner {
  width: 36px; height: 36px;
  border: 3px solid #e4e7ec;
  border-top-color: var(--c-indigo);
  border-radius: 50%;
  animation: spin .65s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
.state-label { font-size: 14px; color: var(--c-muted); }

.empty-icon-wrap {
  width: 64px; height: 64px;
  background: var(--c-indigo-lt);
  color: var(--c-indigo);
  border-radius: var(--r-xl);
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 4px;
}
.empty-title { font-size: 17px; font-weight: 600; color: var(--c-text); margin: 0; }
.empty-sub   { font-size: 14px; color: var(--c-muted); margin: 0; }

/* ═══════════════════════════════════════════════════════════
   Data Table
═══════════════════════════════════════════════════════════ */
.table-scroll { flex: 1; overflow: auto; }

.data-table {
  width: 100%;
  border-collapse: collapse;
}
.data-table thead tr {
  background: #fafafa;
  position: sticky; top: 0; z-index: 1;
}
.data-table th {
  padding: 11px 20px;
  text-align: left;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: .06em;
  text-transform: uppercase;
  color: var(--c-muted);
  border-bottom: 1px solid var(--c-border);
  white-space: nowrap;
}
.table-row {
  border-bottom: 1px solid var(--c-border-soft);
  transition: background .1s;
}
.table-row:last-child { border-bottom: none; }
.table-row:hover { background: #f7f8ff; }

.cell { padding: 14px 20px; vertical-align: middle; }

.tenant-cell { display: flex; align-items: center; gap: 12px; }

/* Avatar */
.avatar {
  width: 38px; height: 38px;
  border-radius: var(--r-md);
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; font-weight: 700;
  letter-spacing: -.01em;
  flex-shrink: 0;
}
.av--emerald { background: var(--c-emerald-lt); color: var(--c-emerald-txt); }
.av--amber   { background: var(--c-amber-lt);   color: var(--c-amber-txt); }
.av--red     { background: var(--c-red-lt);     color: var(--c-red-txt); }
.av--slate   { background: #f1f5f9; color: #475569; }
.av--indigo  { background: var(--c-indigo-lt);  color: var(--c-indigo); }

.cell-primary   { font-size: 13.5px; font-weight: 600; color: var(--c-text); margin: 0; }
.cell-secondary { font-size: 12px;   color: var(--c-subtle); margin-top: 1px; }
.cell-text      { font-size: 13.5px; color: #374151; }

/* Mono badge (code) */
.badge-mono {
  display: inline-block;
  padding: 3px 9px;
  background: #f1f5f9;
  color: #475569;
  font-family: 'DM Mono', ui-monospace, monospace;
  font-size: 12px;
  font-weight: 600;
  border-radius: var(--r-sm);
}

/* Status pill */
.status-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 11px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
  white-space: nowrap;
}
.status--active      { background: var(--c-emerald-lt); color: var(--c-emerald-txt); }
.status--pending     { background: var(--c-amber-lt);   color: var(--c-amber-txt); }
.status--blacklisted { background: var(--c-red-lt);     color: var(--c-red-txt); }
.status--inactive    { background: #f1f5f9; color: #475569; }
.status--default     { background: #f1f5f9; color: #475569; }

.status-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
.status--active      .status-dot { background: var(--c-emerald); }
.status--pending     .status-dot { background: var(--c-amber); }
.status--blacklisted .status-dot { background: var(--c-red); }
.status--inactive    .status-dot { background: #9ca3af; }
.status--default     .status-dot { background: #9ca3af; }

/* Type pill */
.type-pill {
  display: inline-flex;
  align-items: center;
  padding: 3px 10px;
  border-radius: var(--r-sm);
  font-size: 12px;
  font-weight: 600;
  white-space: nowrap;
}
.type--individual { background: #eff6ff; color: #1d4ed8; }
.type--company    { background: #f0fdf4; color: #15803d; }
.type--government { background: #faf5ff; color: #7e22ce; }
.type--ngo        { background: #fff7ed; color: #c2410c; }
.type--default    { background: #f1f5f9; color: #475569; }

/* Action buttons */
.actions { display: flex; align-items: center; justify-content: flex-end; gap: 6px; }
.act-btn {
  width: 32px; height: 32px;
  display: inline-flex; align-items: center; justify-content: center;
  border-radius: var(--r-sm);
  border: 1px solid var(--c-border);
  background: transparent;
  color: var(--c-muted);
  cursor: pointer;
  text-decoration: none;
  transition: background .12s, color .12s, border-color .12s, transform .1s;
}
.act-btn:hover { background: #f3f4f6; color: var(--c-text); transform: translateY(-1px); }
.act-btn--edit:hover { background: var(--c-indigo-lt); border-color: #c7d2fe; color: var(--c-indigo); }

/* ═══════════════════════════════════════════════════════════
   Modal
   KEY FIX: overlay is position:fixed, panel has no position
   so it's always visible inside the flex overlay.
   No conflicting .modal-backdrop vs .modal-overlay classes.
═══════════════════════════════════════════════════════════ */
.modal-overlay {
  /* Fixed layer over entire viewport */
  position: fixed;
  inset: 0;
  z-index: 9999;

  /* Frosted backdrop */
  background: rgba(8, 14, 36, 0.55);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);

  /* Center the panel */
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  overflow-y: auto;
}

.modal-panel {
  /* Panel sits inside the overlay flex — no extra position needed */
  width: 100%;
  max-width: 680px;
  max-height: calc(100vh - 40px);
  display: flex;
  flex-direction: column;

  background: #ffffff;
  border-radius: var(--r-2xl);
  box-shadow: var(--sh-modal);
  border: 1px solid rgba(255,255,255,.9);
  overflow: hidden;
}

/* Modal Header */
.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 22px 26px;
  border-bottom: 1px solid var(--c-border);
  flex-shrink: 0;
}
.modal-header-identity { display: flex; align-items: center; gap: 14px; }
.modal-header-actions  { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }

.modal-avatar {
  width: 52px; height: 52px;
  border-radius: var(--r-lg);
  display: flex; align-items: center; justify-content: center;
  font-size: 16px; font-weight: 700; letter-spacing: -.01em;
  flex-shrink: 0;
}

.modal-name { font-size: 18px; font-weight: 700; letter-spacing: -.02em; color: var(--c-text); margin: 0; }
.modal-code { font-size: 12.5px; color: var(--c-muted); margin-top: 3px; }

.modal-close {
  width: 34px; height: 34px;
  display: flex; align-items: center; justify-content: center;
  border-radius: var(--r-sm);
  border: 1px solid var(--c-border);
  background: transparent;
  color: var(--c-muted);
  cursor: pointer;
  flex-shrink: 0;
  transition: background .12s, color .12s;
}
.modal-close:hover { background: #f3f4f6; color: var(--c-text); }

/* Modal Body */
.modal-body {
  flex: 1;
  overflow-y: auto;
  padding: 22px 26px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

/* Metrics Row */
.metrics-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 10px;
}
.metric-tile {
  padding: 14px 16px;
  background: #fafafa;
  border: 1px solid var(--c-border);
  border-radius: var(--r-lg);
}
.metric-label {
  font-size: 10.5px;
  font-weight: 600;
  letter-spacing: .06em;
  text-transform: uppercase;
  color: var(--c-subtle);
  margin-bottom: 6px;
}
.metric-val {
  font-size: 18px;
  font-weight: 700;
  letter-spacing: -.02em;
  color: var(--c-text);
}
.metric-val--mono {
  font-size: 14px;
  font-family: 'DM Mono', ui-monospace, monospace;
}

/* Section */
.modal-section { display: flex; flex-direction: column; gap: 10px; }
.section-header {
  display: flex;
  align-items: center;
  gap: 7px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .07em;
  text-transform: uppercase;
  color: var(--c-muted);
}

/* Detail Grid */
.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
  gap: 8px;
}
.detail-item {
  padding: 13px 15px;
  background: #fafafa;
  border: 1px solid var(--c-border);
  border-radius: var(--r-lg);
}
.detail-label { font-size: 11px; color: var(--c-subtle); font-weight: 500; margin-bottom: 4px; }
.detail-val   { font-size: 13.5px; font-weight: 600; color: var(--c-text); }

/* Modal Footer */
.modal-footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 10px;
  padding: 16px 26px;
  border-top: 1px solid var(--c-border);
  background: #fafafa;
  flex-shrink: 0;
}

/* ═══════════════════════════════════════════════════════════
   Modal Transition
═══════════════════════════════════════════════════════════ */
.modal-enter-active { transition: opacity .2s ease; }
.modal-leave-active { transition: opacity .18s ease; }
.modal-enter-from,
.modal-leave-to     { opacity: 0; }

.modal-enter-active .modal-panel,
.modal-leave-active .modal-panel {
  transition: transform .28s cubic-bezier(.34,1.26,.64,1), opacity .2s ease;
}
.modal-enter-from .modal-panel { transform: scale(.95) translateY(12px); opacity: 0; }
.modal-leave-to   .modal-panel { transform: scale(.97) translateY(6px);  opacity: 0; }
</style>