<template>

    <div class="meter-page h-full flex flex-col">

      <!-- ══════════════════════════════════════════════════════════
           Page Header
      ══════════════════════════════════════════════════════════ -->
      <div class="page-header flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6 mb-8">
        <div>
          <div class="eyebrow">Utility Infrastructure</div>
          <h1 class="page-title">Meter Management</h1>
          <p class="page-subtitle">Centralized utility meter registry and operational monitoring</p>
        </div>
        <div class="flex items-center gap-3">
          <button @click="fetchMeters(pagination.current_page)" class="btn-ghost" title="Refresh">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/>
              <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
            </svg>
            Refresh
          </button>
          <router-link :to="{ name: 'MeterCreate' }" class="btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Register Meter
          </router-link>
        </div>
      </div>

      <!-- ══════════════════════════════════════════════════════════
           KPI Strip
      ══════════════════════════════════════════════════════════ -->
      <div class="stats-grid mb-8">

        <div class="stat-card">
          <div class="stat-icon stat-icon--slate">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="2" y="4" width="20" height="16" rx="2"/>
              <line x1="6" y1="8" x2="10" y2="8"/><line x1="6" y1="12" x2="14" y2="12"/><line x1="6" y1="16" x2="12" y2="16"/>
            </svg>
          </div>
          <div>
            <p class="stat-label">Total Meters</p>
            <p class="stat-value">{{ summary.total || 0 }}</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon stat-icon--emerald">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
          </div>
          <div>
            <p class="stat-label">Active</p>
            <p class="stat-value stat-value--emerald">{{ summary.active || 0 }}</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon stat-icon--red">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
          </div>
          <div>
            <p class="stat-label">Faulty</p>
            <p class="stat-value stat-value--red">{{ summary.faulty || 0 }}</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon stat-icon--amber">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
            </svg>
          </div>
          <div>
            <p class="stat-label">Maintenance</p>
            <p class="stat-value stat-value--amber">{{ summary.maintenance || 0 }}</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon stat-icon--indigo">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/>
              <path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/>
            </svg>
          </div>
          <div>
            <p class="stat-label">Smart</p>
            <p class="stat-value stat-value--indigo">{{ summary.smart || 0 }}</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon stat-icon--teal">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
          </div>
          <div>
            <p class="stat-label">Shared</p>
            <p class="stat-value">{{ summary.shared || 0 }}</p>
          </div>
        </div>

      </div>

      <!-- ══════════════════════════════════════════════════════════
           Main Table Card
      ══════════════════════════════════════════════════════════ -->
      <div class="table-card flex-1 min-h-0 flex flex-col">

        <!-- Toolbar -->
        <div class="toolbar">
          <!-- Search -->
          <div class="search-wrap">
            <svg class="search-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input
              v-model="filters.search"
              type="text"
              placeholder="Search by meter number, serial, building…"
              class="search-input"
              @keydown.enter="fetchMeters(1)"
            />
          </div>

          <div class="toolbar-filters">

            <!-- Utility quick-filter chips -->
            <div class="utility-filter-chips">
              <button
                v-for="ut in utilityFilterOptions"
                :key="ut.value"
                type="button"
                :class="['utility-chip', `utility-chip--${ut.value}`, { 'utility-chip--active': filters.utility_type === ut.value }]"
                @click="toggleUtility(ut.value)"
                :title="ut.label"
              >
                <span v-html="ut.icon"></span>
                <span class="utility-chip__label">{{ ut.label }}</span>
              </button>
            </div>

            <select v-model="filters.status" class="filter-select" @change="fetchMeters(1)">
              <option value="">All Statuses</option>
              <option value="active">Active</option>
              <option value="faulty">Faulty</option>
              <option value="under_maintenance">Maintenance</option>
              <option value="decommissioned">Decommissioned</option>
            </select>

            <select v-model="filters.smart_meter" class="filter-select" @change="fetchMeters(1)">
              <option value="">All Types</option>
              <option :value="1">Smart</option>
              <option :value="0">Standard</option>
            </select>

            <button @click="resetFilters" class="btn-ghost btn-ghost--sm">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.51"/>
              </svg>
              Reset
            </button>

          </div>
        </div>

        <!-- Table meta row -->
        <div class="table-meta">
          <div class="flex items-center gap-3">
            <p class="table-meta__count">
              <span class="table-meta__num">{{ pagination.total || 0 }}</span>
              meter{{ (pagination.total || 0) !== 1 ? 's' : '' }}
              <span v-if="activeFilterCount > 0">· filtered</span>
            </p>
            <!-- Active filter pills -->
            <div v-if="activeFilterCount > 0" class="flex items-center gap-2 flex-wrap">
              <span v-if="filters.utility_type" class="filter-pill">
                {{ utilityFilterOptions.find(u => u.value === filters.utility_type)?.label }}
                <button @click="filters.utility_type = ''; fetchMeters(1)" class="filter-pill__remove">×</button>
              </span>
              <span v-if="filters.status" class="filter-pill">
                {{ filters.status.replace('_', ' ') }}
                <button @click="filters.status = ''; fetchMeters(1)" class="filter-pill__remove">×</button>
              </span>
              <span v-if="filters.smart_meter !== ''" class="filter-pill">
                {{ filters.smart_meter === 1 ? 'Smart' : 'Standard' }}
                <button @click="filters.smart_meter = ''; fetchMeters(1)" class="filter-pill__remove">×</button>
              </span>
              <span v-if="filters.search" class="filter-pill">
                "{{ filters.search }}"
                <button @click="filters.search = ''; fetchMeters(1)" class="filter-pill__remove">×</button>
              </span>
            </div>
          </div>
          <div class="table-meta__right">
            <span class="table-meta__page-info">
              Page {{ pagination.current_page || 1 }} of {{ pagination.last_page || 1 }}
            </span>
          </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="state-container">
          <div class="spinner-ring"></div>
          <p class="state-text">Loading meters…</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="meters.length === 0" class="state-container">
          <div class="empty-icon">
            <svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
            </svg>
          </div>
          <h3 class="empty-title">No meters found</h3>
          <p class="empty-sub">{{ activeFilterCount > 0 ? 'Try adjusting your filters' : 'Register your first utility meter to begin tracking' }}</p>
          <router-link v-if="activeFilterCount === 0" :to="{ name: 'MeterCreate' }" class="btn-primary mt-2">Register Meter</router-link>
          <button v-else @click="resetFilters" class="btn-ghost mt-2">Clear Filters</button>
        </div>

        <!-- Table -->
        <div v-else class="flex-1 overflow-auto">
          <table class="data-table">
            <thead>
              <tr>
                <th>Meter</th>
                <th>Utility</th>
                <th>Location</th>
                <th>Reading</th>
                <th>Type</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="meter in meters" :key="meter.id" class="table-row">

                <!-- Meter -->
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="meter-avatar" :class="`meter-avatar--${meter.utility_type?.value || 'default'}`">
                      <span v-html="getUtilityIcon(meter.utility_type?.value)"></span>
                    </div>
                    <div>
                      <p class="row-code">{{ meter.meter_number }}</p>
                      <p class="row-sub">{{ meter.serial_number || '—' }}</p>
                    </div>
                  </div>
                </td>

                <!-- Utility -->
                <td class="px-6 py-4">
                  <span :class="['utility-badge', `utility-badge--${meter.utility_type?.value}`]">
                    {{ meter.utility_type?.label || '—' }}
                  </span>
                </td>

                <!-- Location -->
                <td class="px-6 py-4">
                  <p class="row-title">{{ meter.building?.name || '—' }}</p>
                  <p class="row-sub">{{ meter.apartment ? `Unit ${meter.apartment.unit_number}` : 'No unit' }}</p>
                </td>

                <!-- Reading -->
                <td class="px-6 py-4">
                  <p class="row-reading">{{ formatReading(meter.readings?.current_reading) }}</p>
                  <p class="row-sub">{{ meter.measurement_unit?.toUpperCase() || '—' }}</p>
                </td>

                <!-- Type -->
                <td class="px-6 py-4">
                  <div class="flex flex-col gap-1">
                    <span :class="['meter-type-badge', `meter-type-badge--${meter.meter_type?.value}`]">
                      {{ meter.meter_type?.label || '—' }}
                    </span>
                    <span v-if="meter.smart_features?.is_smart_meter" class="smart-badge">
                      <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/>
                        <path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/>
                      </svg>
                      Smart
                    </span>
                  </div>
                </td>

                <!-- Status -->
                <td class="px-6 py-4">
                  <span :class="['status-badge', statusBadgeClass(meter.status?.value)]">
                    <span class="status-dot"></span>
                    {{ meter.status?.label || '—' }}
                  </span>
                </td>

                <!-- Actions -->
                <td class="px-6 py-4">
                  <div class="flex items-center justify-end gap-1">
                    <router-link :to="{ name: 'MeterShow', params: { id: meter.id } }" class="action-btn" title="View meter">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                      </svg>
                    </router-link>
                    <router-link :to="{ name: 'MeterEdit', params: { id: meter.id } }" class="action-btn action-btn--edit" title="Edit meter">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                      </svg>
                    </router-link>
                  </div>
                </td>

              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="!loading && meters.length > 0" class="pagination-bar">
          <p class="pagination-bar__info">
            Showing
            <strong>{{ pagination.from || 0 }}</strong>–<strong>{{ pagination.to || 0 }}</strong>
            of <strong>{{ pagination.total || 0 }}</strong> meters
          </p>
          <div class="pagination-controls">
            <button
              class="page-btn"
              :disabled="pagination.current_page <= 1"
              @click="changePage(pagination.current_page - 1)"
              title="Previous page"
            >
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"/>
              </svg>
            </button>

            <!-- Page number pills -->
            <div class="page-pills">
              <button
                v-for="page in visiblePages"
                :key="page"
                :class="['page-pill', { 'page-pill--active': page === pagination.current_page, 'page-pill--ellipsis': page === '…' }]"
                :disabled="page === '…'"
                @click="page !== '…' && changePage(page)"
              >{{ page }}</button>
            </div>

            <button
              class="page-btn"
              :disabled="pagination.current_page >= pagination.last_page"
              @click="changePage(pagination.current_page + 1)"
              title="Next page"
            >
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="9 18 15 12 9 6"/>
              </svg>
            </button>
          </div>
        </div>

      </div>
      <!-- /table-card -->
       

    </div>

  
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import api from '@/services/api'
import DashboardLayout from '@/layouts/DashboardLayout.vue'

/* ── State ─────────────────────────────────────────────────── */
const loading    = ref(false)
const meters     = ref([])
const summary    = ref({})
const pagination = ref({ current_page: 1, last_page: 1, total: 0, from: 0, to: 0 })

const filters = reactive({ search: '', utility_type: '', status: '', smart_meter: '' })

/* ── Static config ──────────────────────────────────────────── */
const utilityFilterOptions = [
  { value: 'electricity', label: 'Electricity', icon: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>' },
  { value: 'water',       label: 'Water',       icon: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>' },
  { value: 'gas',         label: 'Gas',         icon: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"/></svg>' },
  { value: 'solar',       label: 'Solar',       icon: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>' },
  { value: 'internet',    label: 'Internet',    icon: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="2"/><path d="M16.24 7.76a6 6 0 0 1 0 8.49m-8.48-.01a6 6 0 0 1 0-8.49"/></svg>' },
]

const utilityIconMap = {
  electricity: '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
  water:       '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>',
  gas:         '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"/></svg>',
  solar:       '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/></svg>',
  chilled_water:'<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="2" x2="12" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>',
  internet:    '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="2"/><path d="M16.24 7.76a6 6 0 0 1 0 8.49m-8.48-.01a6 6 0 0 1 0-8.49"/></svg>',
}

/* ── Computed ───────────────────────────────────────────────── */
const activeFilterCount = computed(() =>
  [filters.utility_type, filters.status, filters.smart_meter !== '' ? filters.smart_meter : '', filters.search]
    .filter(Boolean).length
)

const visiblePages = computed(() => {
  const cur  = pagination.value.current_page || 1
  const last = pagination.value.last_page    || 1
  if (last <= 7) return Array.from({ length: last }, (_, i) => i + 1)
  const pages = []
  if (cur <= 4) {
    pages.push(1, 2, 3, 4, 5, '…', last)
  } else if (cur >= last - 3) {
    pages.push(1, '…', last - 4, last - 3, last - 2, last - 1, last)
  } else {
    pages.push(1, '…', cur - 1, cur, cur + 1, '…', last)
  }
  return pages
})

/* ── API ────────────────────────────────────────────────────── */
const fetchMeters = async (page = 1) => {
  loading.value = true
  try {
    const response = await api.get('/meters', { params: { page, ...filters } })
    meters.value  = response.data.data    || []
    summary.value = response.data.summary || {}
    const meta    = response.data.meta    || {}
    pagination.value = {
      current_page: meta.current_page || 1,
      last_page:    meta.last_page    || 1,
      per_page:     meta.per_page     || 15,
      total:        meta.total        || 0,
      from: ((meta.current_page - 1) * meta.per_page) + 1,
      to:   Math.min(meta.current_page * meta.per_page, meta.total),
    }
  } catch (error) {
    console.error('Failed to fetch meters', error)
  } finally {
    loading.value = false
  }
}

const resetFilters = () => {
  filters.search = ''; filters.utility_type = ''; filters.status = ''; filters.smart_meter = ''
  fetchMeters(1)
}

const changePage = (page) => fetchMeters(page)

const toggleUtility = (value) => {
  filters.utility_type = filters.utility_type === value ? '' : value
  fetchMeters(1)
}

/* ── Helpers ────────────────────────────────────────────────── */
const getUtilityIcon = (type) => utilityIconMap[type] || '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>'

const formatReading = (val) => {
  if (val == null || val === '') return '—'
  return Number(val).toLocaleString(undefined, { maximumFractionDigits: 4 })
}

const statusBadgeClass = (status) => ({
  active:             'status-badge--active',
  faulty:             'status-badge--faulty',
  under_maintenance:  'status-badge--maintenance',
  decommissioned:     'status-badge--decommissioned',
}[status] || 'status-badge--default')

onMounted(() => fetchMeters())
</script>

<style scoped>
/* ════════════════════════════════════════════════════════════
   Tokens
════════════════════════════════════════════════════════════ */
.meter-page {
  --accent:     #4f46e5;
  --accent-lt:  #eef2ff;
  --accent-md:  #818cf8;
  --accent-ring:rgba(79,70,229,.12);

  --border:     #e8eaed;
  --surface:    #ffffff;
  --app-bg:     #f4f6f9;

  --text-1: #111827;
  --text-2: #6b7280;
  --text-3: #9ca3af;

  --emerald: #059669; --emerald-lt: #d1fae5;
  --amber:   #d97706; --amber-lt:   #fef3c7;
  --red:     #dc2626; --red-lt:     #fee2e2;
  --blue:    #2563eb; --blue-lt:    #eff6ff;
  --teal:    #0d9488; --teal-lt:    #f0fdfa;
  --orange:  #ea580c; --orange-lt:  #fff7ed;
  --yellow:  #ca8a04; --yellow-lt:  #fefce8;
  --purple:  #7c3aed; --purple-lt:  #f5f3ff;
  --cyan:    #0891b2; --cyan-lt:    #ecfeff;

  --shadow-card: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px -1px rgba(0,0,0,.04);
  --r-sm: 7px; --r-md: 10px; --r-lg: 14px; --r-xl: 18px; --r-2xl: 22px;
  --ease: 150ms cubic-bezier(.4,0,.2,1);

  font-family: 'DM Sans', ui-sans-serif, system-ui, sans-serif;
}

/* ════════════════════════════════════════════════════════════
   Header
════════════════════════════════════════════════════════════ */
.eyebrow { font-size: 11px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--accent); margin-bottom: 6px; }
.page-title { font-size: 28px; font-weight: 700; letter-spacing: -.02em; color: var(--text-1); line-height: 1.2; }
.page-subtitle { font-size: 14px; color: var(--text-2); margin-top: 6px; }

/* ════════════════════════════════════════════════════════════
   Buttons
════════════════════════════════════════════════════════════ */
.btn-primary {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 10px 20px; background: var(--accent); color: #fff;
  font-size: 13.5px; font-weight: 600; border-radius: var(--r-lg);
  border: none; cursor: pointer; text-decoration: none;
  transition: background var(--ease), transform var(--ease), box-shadow var(--ease);
  box-shadow: 0 2px 8px rgba(79,70,229,.28);
}
.btn-primary:hover { background: #4338ca; transform: translateY(-1px); box-shadow: 0 4px 14px rgba(79,70,229,.38); }

.btn-ghost {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 10px 16px; background: transparent; color: var(--text-2);
  font-size: 13.5px; font-weight: 500; border-radius: var(--r-lg);
  border: 1px solid var(--border); cursor: pointer; text-decoration: none;
  transition: background var(--ease), color var(--ease);
}
.btn-ghost:hover { background: #f9fafb; color: var(--text-1); }
.btn-ghost--sm { padding: 8px 13px; font-size: 13px; }

/* ════════════════════════════════════════════════════════════
   Stats
════════════════════════════════════════════════════════════ */
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px; }
.stat-card {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: var(--r-xl); padding: 18px 20px;
  display: flex; align-items: center; gap: 14px;
  box-shadow: var(--shadow-card);
  transition: box-shadow var(--ease), transform var(--ease);
}
.stat-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.08); transform: translateY(-1px); }

.stat-icon { width: 40px; height: 40px; border-radius: var(--r-md); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.stat-icon--slate   { background: #f1f5f9; color: #475569; }
.stat-icon--emerald { background: var(--emerald-lt); color: var(--emerald); }
.stat-icon--red     { background: var(--red-lt);     color: var(--red); }
.stat-icon--amber   { background: var(--amber-lt);   color: var(--amber); }
.stat-icon--indigo  { background: var(--accent-lt);  color: var(--accent); }
.stat-icon--teal    { background: var(--teal-lt);    color: var(--teal); }

.stat-label { font-size: 11.5px; font-weight: 500; color: var(--text-2); margin-bottom: 4px; }
.stat-value { font-size: 24px; font-weight: 700; letter-spacing: -.02em; color: var(--text-1); line-height: 1; }
.stat-value--emerald { color: var(--emerald); }
.stat-value--red     { color: var(--red); }
.stat-value--amber   { color: var(--amber); }
.stat-value--indigo  { color: var(--accent); }

/* ════════════════════════════════════════════════════════════
   Table Card
════════════════════════════════════════════════════════════ */
.table-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--r-2xl); box-shadow: var(--shadow-card); overflow: hidden; }

/* Toolbar */
.toolbar {
  padding: 14px 18px; border-bottom: 1px solid #f3f4f6;
  display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
}
.search-wrap { position: relative; flex: 1; min-width: 200px; max-width: 340px; }
.search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-3); pointer-events: none; }
.search-input {
  width: 100%; padding: 9px 14px 9px 36px; font-size: 13.5px;
  border: 1px solid var(--border); border-radius: var(--r-lg);
  outline: none; background: #fafafa; color: var(--text-1); font-family: inherit;
  transition: border-color var(--ease), box-shadow var(--ease), background var(--ease);
}
.search-input::placeholder { color: var(--text-3); }
.search-input:focus { background: #fff; border-color: var(--accent-md); box-shadow: 0 0 0 3px var(--accent-ring); }

.toolbar-filters { display: flex; align-items: center; gap: 10px; margin-left: auto; flex-wrap: wrap; }

/* Utility filter chips */
.utility-filter-chips { display: flex; gap: 5px; }
.utility-chip {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 5px 10px; border-radius: 99px;
  border: 1.5px solid var(--border); background: var(--surface);
  font-size: 12px; font-weight: 600; color: var(--text-3);
  cursor: pointer; transition: all var(--ease); white-space: nowrap;
}
.utility-chip:hover { border-color: #9ca3af; color: var(--text-2); }
.utility-chip__label { display: none; }
@media (min-width: 1200px) { .utility-chip__label { display: inline; } }

.utility-chip--electricity.utility-chip--active { border-color: #f59e0b; background: #fffbeb; color: #92400e; }
.utility-chip--water.utility-chip--active       { border-color: #38bdf8; background: #e0f2fe; color: #075985; }
.utility-chip--gas.utility-chip--active         { border-color: #fb923c; background: var(--orange-lt); color: #9a3412; }
.utility-chip--solar.utility-chip--active       { border-color: #fbbf24; background: var(--yellow-lt); color: #854d0e; }
.utility-chip--internet.utility-chip--active    { border-color: var(--accent-md); background: var(--accent-lt); color: var(--accent); }

.filter-select {
  padding: 8px 32px 8px 12px; font-size: 13px;
  border: 1px solid var(--border); border-radius: var(--r-lg);
  outline: none; background: #fafafa; color: var(--text-1); font-family: inherit;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg width='10' height='6' viewBox='0 0 10 6' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%239ca3af' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 12px center;
  cursor: pointer; transition: border-color var(--ease), box-shadow var(--ease);
}
.filter-select:focus { border-color: var(--accent-md); box-shadow: 0 0 0 3px var(--accent-ring); }

/* Table meta bar */
.table-meta {
  padding: 9px 22px; border-bottom: 1px solid #f3f4f6;
  display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
}
.table-meta__count { font-size: 13px; color: var(--text-3); }
.table-meta__num   { font-weight: 700; color: var(--text-2); }
.table-meta__right { display: flex; align-items: center; }
.table-meta__page-info { font-size: 12px; color: var(--text-3); }

.filter-pill {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 3px 10px; background: var(--accent-lt); color: var(--accent);
  border-radius: 99px; font-size: 12px; font-weight: 600;
}
.filter-pill__remove { background: none; border: none; cursor: pointer; color: var(--accent-md); font-size: 14px; line-height: 1; padding: 0; margin-left: 2px; }
.filter-pill__remove:hover { color: var(--accent); }

/* ════════════════════════════════════════════════════════════
   States
════════════════════════════════════════════════════════════ */
.state-container { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 64px 24px; gap: 12px; }
.spinner-ring { width: 38px; height: 38px; border: 3px solid #e5e7eb; border-top-color: var(--accent); border-radius: 50%; animation: spin .7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.state-text  { font-size: 14px; color: var(--text-3); }
.empty-icon  { width: 68px; height: 68px; border-radius: var(--r-xl); background: var(--accent-lt); color: var(--accent-md); display: flex; align-items: center; justify-content: center; margin-bottom: 4px; }
.empty-title { font-size: 17px; font-weight: 600; color: var(--text-1); }
.empty-sub   { font-size: 14px; color: var(--text-2); margin-top: 4px; text-align: center; }

/* ════════════════════════════════════════════════════════════
   Data Table
════════════════════════════════════════════════════════════ */
.data-table { width: 100%; border-collapse: collapse; min-width: 820px; }
.data-table thead tr { background: #fafafa; }
.data-table th {
  padding: 10px 24px; text-align: left;
  font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase;
  color: var(--text-2); border-bottom: 1px solid var(--border); white-space: nowrap;
}
.data-table th.text-right { text-align: right; }
.table-row { border-bottom: 1px solid #f3f4f6; transition: background var(--ease); }
.table-row:last-child { border-bottom: none; }
.table-row:hover { background: #fafbff; }

/* Meter avatar */
.meter-avatar {
  width: 38px; height: 38px; border-radius: var(--r-md);
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.meter-avatar--electricity { background: #fffbeb; color: #92400e; }
.meter-avatar--water       { background: #e0f2fe; color: #075985; }
.meter-avatar--gas         { background: var(--orange-lt); color: #9a3412; }
.meter-avatar--solar       { background: var(--yellow-lt); color: #854d0e; }
.meter-avatar--chilled_water { background: var(--cyan-lt); color: #164e63; }
.meter-avatar--internet    { background: var(--accent-lt); color: var(--accent); }
.meter-avatar--default     { background: #f1f5f9; color: #475569; }

.row-code  { font-size: 13.5px; font-weight: 700; color: var(--text-1); font-family: 'DM Mono', ui-monospace, monospace; }
.row-title { font-size: 13.5px; font-weight: 600; color: var(--text-1); }
.row-sub   { font-size: 12px; color: var(--text-3); margin-top: 2px; }
.row-reading { font-size: 15px; font-weight: 700; color: var(--text-1); letter-spacing: -.01em; font-family: 'DM Mono', ui-monospace, monospace; }

/* Utility badge */
.utility-badge {
  display: inline-flex; align-items: center;
  padding: 4px 10px; border-radius: var(--r-sm);
  font-size: 12px; font-weight: 600;
}
.utility-badge--electricity { background: #fffbeb; color: #92400e; }
.utility-badge--water       { background: #e0f2fe; color: #075985; }
.utility-badge--gas         { background: var(--orange-lt); color: #9a3412; }
.utility-badge--solar       { background: var(--yellow-lt); color: #854d0e; }
.utility-badge--chilled_water { background: var(--cyan-lt); color: #164e63; }
.utility-badge--steam       { background: var(--purple-lt); color: #5b21b6; }
.utility-badge--internet    { background: var(--accent-lt); color: var(--accent); }

/* Meter type badge */
.meter-type-badge { display: inline-flex; padding: 3px 8px; border-radius: 99px; font-size: 11px; font-weight: 600; }
.meter-type-badge--analog  { background: #f1f5f9; color: #475569; }
.meter-type-badge--digital { background: var(--blue-lt); color: var(--blue); }
.meter-type-badge--smart   { background: var(--accent-lt); color: var(--accent); }

/* Smart badge */
.smart-badge {
  display: inline-flex; align-items: center; gap: 4px;
  padding: 2px 7px; border-radius: 99px;
  font-size: 10.5px; font-weight: 600;
  background: var(--emerald-lt); color: var(--emerald);
}

/* Status badge */
.status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 11px; border-radius: 99px; font-size: 12px; font-weight: 600; white-space: nowrap; }
.status-dot   { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }

.status-badge--active        { background: var(--emerald-lt); color: #065f46; }
.status-badge--active        .status-dot { background: var(--emerald); }
.status-badge--faulty        { background: var(--red-lt);     color: #991b1b; }
.status-badge--faulty        .status-dot { background: var(--red); }
.status-badge--maintenance   { background: var(--amber-lt);   color: #92400e; }
.status-badge--maintenance   .status-dot { background: var(--amber); }
.status-badge--decommissioned { background: #f1f5f9; color: #475569; }
.status-badge--decommissioned .status-dot { background: #9ca3af; }
.status-badge--default        { background: #f1f5f9; color: #475569; }
.status-badge--default        .status-dot { background: #9ca3af; }

/* Action buttons */
.action-btn {
  width: 32px; height: 32px; border-radius: var(--r-sm);
  border: 1px solid var(--border); background: transparent;
  display: inline-flex; align-items: center; justify-content: center;
  color: var(--text-2); cursor: pointer; text-decoration: none;
  transition: background var(--ease), color var(--ease), border-color var(--ease), transform var(--ease);
}
.action-btn:hover { background: #f3f4f6; color: var(--text-1); transform: translateY(-1px); }
.action-btn--edit:hover { background: var(--accent-lt); border-color: #c7d2fe; color: var(--accent); }

/* ════════════════════════════════════════════════════════════
   Pagination
════════════════════════════════════════════════════════════ */
.pagination-bar {
  display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;
  padding: 14px 22px; border-top: 1px solid var(--border); background: #fafafa;
}
.pagination-bar__info { font-size: 13px; color: var(--text-3); }
.pagination-bar__info strong { color: var(--text-2); font-weight: 600; }

.pagination-controls { display: flex; align-items: center; gap: 6px; }
.page-btn {
  width: 32px; height: 32px; border-radius: var(--r-md);
  border: 1px solid var(--border); background: var(--surface);
  display: flex; align-items: center; justify-content: center;
  color: var(--text-2); cursor: pointer;
  transition: background var(--ease), color var(--ease);
}
.page-btn:hover:not(:disabled) { background: var(--accent-lt); color: var(--accent); border-color: #c7d2fe; }
.page-btn:disabled { opacity: .4; cursor: not-allowed; }

.page-pills { display: flex; gap: 4px; }
.page-pill {
  min-width: 32px; height: 32px; border-radius: var(--r-md);
  border: 1px solid var(--border); background: var(--surface);
  font-size: 13px; font-weight: 500; color: var(--text-2);
  cursor: pointer; display: flex; align-items: center; justify-content: center; padding: 0 8px;
  transition: all var(--ease);
}
.page-pill:hover:not(.page-pill--active):not(.page-pill--ellipsis) { background: #f3f4f6; color: var(--text-1); }
.page-pill--active { background: var(--accent); border-color: var(--accent); color: #fff; font-weight: 700; }
.page-pill--ellipsis { cursor: default; border: none; background: transparent; }

/* ════════════════════════════════════════════════════════════
   Responsive
════════════════════════════════════════════════════════════ */
@media (max-width: 768px) {
  .toolbar { flex-direction: column; align-items: stretch; }
  .toolbar-filters { margin-left: 0; flex-direction: column; align-items: stretch; }
  .search-wrap { max-width: none; }
  .utility-filter-chips { flex-wrap: wrap; }
  .utility-chip__label { display: inline; }
  .pagination-bar { flex-direction: column; align-items: flex-start; }
}
</style>