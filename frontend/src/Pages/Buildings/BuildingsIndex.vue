<template>
    <div class="bi-root">

      <!-- ═══════════════════════════════════════════════════════ -->
      <!-- Google Fonts -->
      <!-- ═══════════════════════════════════════════════════════ -->
      <component :is="'style'">
        @import url('https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap');
        .bi-root { font-family: 'DM Sans', sans-serif; }
        .bi-display { font-family: 'Syne', sans-serif; }

        .bi-root * { box-sizing: border-box; }

        /* Stat card shimmer */
        @keyframes bi-shimmer {
          0%   { background-position: -200% center; }
          100% { background-position:  200% center; }
        }
        @keyframes bi-fadeUp {
          from { opacity: 0; transform: translateY(14px); }
          to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes bi-spin {
          to { transform: rotate(360deg); }
        }
        @keyframes bi-modalIn {
          from { opacity: 0; transform: scale(.96) translateY(12px); }
          to   { opacity: 1; transform: scale(1)  translateY(0); }
        }
        @keyframes bi-backdropIn {
          from { opacity: 0; }
          to   { opacity: 1; }
        }

        .bi-row-enter { animation: bi-fadeUp .22s ease both; }

        .bi-stat-shine {
          background: linear-gradient(105deg,
            transparent 40%,
            rgba(255,255,255,.55) 50%,
            transparent 60%
          );
          background-size: 200% 100%;
          animation: bi-shimmer 2.4s linear infinite;
        }

        .bi-table tbody tr {
          transition: background .15s;
        }
        .bi-table tbody tr:hover {
          background: #f8f7ff;
        }
        .bi-table tbody tr:hover .bi-row-actions {
          opacity: 1;
        }
        .bi-row-actions {
          opacity: 0;
          transition: opacity .18s;
        }
        @media (max-width: 768px) {
          .bi-row-actions { opacity: 1; }
        }

        /* Scrollbar */
        .bi-table-wrap::-webkit-scrollbar { height: 6px; width: 6px; }
        .bi-table-wrap::-webkit-scrollbar-track { background: transparent; }
        .bi-table-wrap::-webkit-scrollbar-thumb { background: #ddd8f5; border-radius: 99px; }

        /* Select arrow */
        .bi-select {
          appearance: none;
          background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%239690C4'%3E%3Cpath fill-rule='evenodd' d='M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z'/%3E%3C/svg%3E");
          background-repeat: no-repeat;
          background-position: right 12px center;
          background-size: 16px;
          padding-right: 36px !important;
        }
      </component>

      <!-- ═══════════════════════════════════════════════════════ -->
      <!-- Page Header -->
      <!-- ═══════════════════════════════════════════════════════ -->
      <div class="bi-header">

        <div class="bi-header-left">
          <div class="bi-eyebrow">Property Infrastructure</div>
          <h1 class="bi-title bi-display">Buildings</h1>
          <p class="bi-subtitle">Manage multi-country properties, floors &amp; operations</p>
        </div>

        <div class="bi-header-actions">
          <button @click="fetchBuildings" class="bi-btn-ghost" title="Refresh">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="23 4 23 10 17 10"/>
              <polyline points="1 20 1 14 7 14"/>
              <path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/>
            </svg>
            Refresh
          </button>

          <router-link :to="{ name: 'BuildingCreate' }" class="bi-btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Add Building
          </router-link>
        </div>

      </div>

      <!-- ═══════════════════════════════════════════════════════ -->
      <!-- Stats Row -->
      <!-- ═══════════════════════════════════════════════════════ -->
      <div class="bi-stats-grid">

        <!-- Total -->
        <div class="bi-stat bi-stat--total">
          <div class="bi-stat-inner">
            <div class="bi-stat-icon">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
              </svg>
            </div>
            <div>
              <p class="bi-stat-label">Total Buildings</p>
              <p class="bi-stat-value bi-display">{{ meta.total || buildings.length }}</p>
            </div>
          </div>
          <div class="bi-stat-shine bi-stat-bar"></div>
        </div>

        <!-- Active -->
        <div class="bi-stat bi-stat--active">
          <div class="bi-stat-inner">
            <div class="bi-stat-icon bi-stat-icon--active">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
              </svg>
            </div>
            <div>
              <p class="bi-stat-label">Active</p>
              <p class="bi-stat-value bi-stat-value--active bi-display">{{ activeBuildings }}</p>
            </div>
          </div>
          <div class="bi-stat-bar bi-stat-bar--active"></div>
        </div>

        <!-- Inactive -->
        <div class="bi-stat bi-stat--inactive">
          <div class="bi-stat-inner">
            <div class="bi-stat-icon bi-stat-icon--inactive">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <circle cx="12" cy="12" r="10"/>
                <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
              </svg>
            </div>
            <div>
              <p class="bi-stat-label">Inactive</p>
              <p class="bi-stat-value bi-stat-value--inactive bi-display">{{ inactiveBuildings }}</p>
            </div>
          </div>
          <div class="bi-stat-bar bi-stat-bar--inactive"></div>
        </div>

        <!-- Floors -->
        <div class="bi-stat bi-stat--floors">
          <div class="bi-stat-inner">
            <div class="bi-stat-icon bi-stat-icon--floors">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <polyline points="3 9 12 2 21 9"/>
                <path d="M9 22V12h6v10"/><path d="M3 9v13h18V9"/>
              </svg>
            </div>
            <div>
              <p class="bi-stat-label">Total Floors</p>
              <p class="bi-stat-value bi-display">{{ totalFloors }}</p>
            </div>
          </div>
          <div class="bi-stat-bar bi-stat-bar--floors"></div>
        </div>

      </div>

      <!-- ═══════════════════════════════════════════════════════ -->
      <!-- Main Panel -->
      <!-- ═══════════════════════════════════════════════════════ -->
      <div class="bi-panel">

        <!-- Toolbar -->
        <div class="bi-toolbar">

          <div class="bi-search-wrap">
            <svg class="bi-search-ico" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input
              v-model="filters.search"
              @input="debouncedSearch"
              type="text"
              placeholder="Search by name, city, code…"
              class="bi-search-input"
            />
            <transition name="bi-clear">
              <button v-if="filters.search" @click="filters.search = ''; fetchBuildings()" class="bi-search-clear">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </transition>
          </div>

          <div class="bi-filters-right">

            <div class="bi-filter-pill" :class="{ 'bi-filter-pill--active': filters.status === 'active' }" @click="setStatus('active')">
              <span class="bi-dot bi-dot--green"></span> Active
            </div>
            <div class="bi-filter-pill" :class="{ 'bi-filter-pill--active': filters.status === 'inactive' }" @click="setStatus('inactive')">
              <span class="bi-dot bi-dot--amber"></span> Inactive
            </div>
            <div v-if="filters.status" class="bi-filter-pill bi-filter-pill--clear" @click="setStatus('')">
              All <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </div>

          </div>

        </div>

        <!-- Loading -->
        <div v-if="loading" class="bi-loading-state">
          <div class="bi-spinner"></div>
          <p class="bi-loading-text">Loading buildings…</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="buildings.length === 0" class="bi-empty-state">
          <div class="bi-empty-icon">
            <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="3 9 12 2 21 9"/><path d="M9 22V12h6v10"/>
              <path d="M3 9v13h18V9"/>
            </svg>
          </div>
          <h3 class="bi-empty-title bi-display">No buildings found</h3>
          <p class="bi-empty-body">{{ filters.search || filters.status ? 'Try adjusting your filters.' : 'Register your first property to get started.' }}</p>
          <router-link v-if="!filters.search && !filters.status" :to="{ name: 'BuildingCreate' }" class="bi-btn-primary">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add First Building
          </router-link>
        </div>

        <!-- Table -->
        <div v-else class="bi-table-wrap">
          <table class="bi-table">

            <thead>
              <tr>
                <th class="bi-th">Building</th>
                <th class="bi-th">Code</th>
                <th class="bi-th">Floors</th>
                <th class="bi-th">Currency</th>
                <th class="bi-th">Status</th>
                <th class="bi-th bi-th--right">Actions</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="(building, i) in buildings"
                :key="building.id"
                class="bi-row"
                :style="{ animationDelay: i * 0.04 + 's' }"
              >

                <!-- Building Name -->
                <td class="bi-td">
                  <div class="bi-building-cell">
                    <div class="bi-avatar">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 9 12 2 21 9"/>
                        <path d="M9 22V12h6v10"/><path d="M3 9v13h18V9"/>
                      </svg>
                    </div>
                    <div>
                      <p class="bi-building-name">{{ building.name }}</p>
                      <p class="bi-building-loc">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        {{ [building.city, building.country].filter(Boolean).join(', ') || 'Location not set' }}
                      </p>
                    </div>
                  </div>
                </td>

                <!-- Code -->
                <td class="bi-td">
                  <span class="bi-badge bi-badge--code">{{ building.code || '—' }}</span>
                </td>

                <!-- Floors -->
                <td class="bi-td">
                  <div class="bi-floors-cell">
                    <span class="bi-floors-num">{{ building.total_floors || 0 }}</span>
                    <span class="bi-floors-label">floors</span>
                  </div>
                </td>

                <!-- Currency -->
                <td class="bi-td">
                  <div>
                    <span class="bi-badge bi-badge--currency">{{ building.operating_currency || 'USD' }}</span>
                    <p class="bi-tz">{{ building.timezone || '—' }}</p>
                  </div>
                </td>

                <!-- Status -->
                <td class="bi-td">
                  <span :class="['bi-status', building.is_active ? 'bi-status--active' : 'bi-status--inactive']">
                    <span class="bi-status-dot"></span>
                    {{ building.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>

                <!-- Actions -->
                <td class="bi-td bi-td--right">
                  <div class="bi-actions bi-row-actions">
                    <button @click="openViewModal(building)" class="bi-action-btn" title="View details">
                      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                    <router-link :to="{ name: 'BuildingEdit', params: { id: building.id } }" class="bi-action-btn" title="Edit building">
                      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </router-link>
                    <button @click="deleteBuilding(building)" class="bi-action-btn bi-action-btn--danger" title="Delete building">
                      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                    </button>
                  </div>
                </td>

              </tr>
            </tbody>

          </table>
        </div>

        <!-- Table Footer / Count -->
        <div v-if="!loading && buildings.length > 0" class="bi-table-footer">
          <span>Showing <strong>{{ buildings.length }}</strong> building{{ buildings.length !== 1 ? 's' : '' }}</span>
        </div>

      </div>

      <!-- ═══════════════════════════════════════════════════════ -->
      <!-- View Modal -->
      <!-- ═══════════════════════════════════════════════════════ -->
      <Teleport to="body">
        <Transition name="bi-modal">
          <div v-if="showViewModal" class="bi-backdrop" @click.self="closeViewModal">

            <div class="bi-modal">

              <!-- Modal Header -->
              <div class="bi-modal-header">
                <div class="bi-modal-avatar">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><polyline points="3 9 12 2 21 9"/><path d="M9 22V12h6v10"/><path d="M3 9v13h18V9"/></svg>
                </div>
                <div class="bi-modal-title-block">
                  <h2 class="bi-modal-title bi-display">{{ selectedBuilding?.name }}</h2>
                  <p class="bi-modal-sub">Building Overview</p>
                </div>
                <button @click="closeViewModal" class="bi-modal-close">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
              </div>

              <!-- Status Banner -->
              <div :class="['bi-modal-banner', selectedBuilding?.is_active ? 'bi-modal-banner--active' : 'bi-modal-banner--inactive']">
                <span class="bi-status-dot"></span>
                {{ selectedBuilding?.is_active ? 'Active Building' : 'Inactive Building' }}
              </div>

              <!-- Info Grid -->
              <div class="bi-modal-grid">

                <div class="bi-info-card">
                  <p class="bi-info-label">Building Code</p>
                  <p class="bi-info-value">{{ selectedBuilding?.code || '—' }}</p>
                </div>

                <div class="bi-info-card">
                  <p class="bi-info-label">Operating Currency</p>
                  <p class="bi-info-value">{{ selectedBuilding?.operating_currency || '—' }}</p>
                </div>

                <div class="bi-info-card">
                  <p class="bi-info-label">Total Floors</p>
                  <p class="bi-info-value">{{ selectedBuilding?.total_floors ?? '—' }}</p>
                </div>

                <div class="bi-info-card">
                  <p class="bi-info-label">Total Units</p>
                  <p class="bi-info-value">{{ selectedBuilding?.total_units ?? '—' }}</p>
                </div>

                <div class="bi-info-card">
                  <p class="bi-info-label">Country</p>
                  <p class="bi-info-value">{{ selectedBuilding?.country || '—' }}</p>
                </div>

                <div class="bi-info-card">
                  <p class="bi-info-label">City</p>
                  <p class="bi-info-value">{{ selectedBuilding?.city || '—' }}</p>
                </div>

                <div class="bi-info-card bi-info-card--full">
                  <p class="bi-info-label">Timezone</p>
                  <p class="bi-info-value">{{ selectedBuilding?.timezone || '—' }}</p>
                </div>

              </div>

              <!-- Description -->
              <div class="bi-modal-desc-wrap">
                <p class="bi-info-label">Description</p>
                <p class="bi-modal-desc">{{ selectedBuilding?.description || 'No description provided for this building.' }}</p>
              </div>

              <!-- Footer Actions -->
              <div class="bi-modal-footer">
                <button @click="closeViewModal" class="bi-btn-ghost">Close</button>
                <router-link
                  :to="{ name: 'BuildingEdit', params: { id: selectedBuilding?.id } }"
                  class="bi-btn-primary"
                  @click="closeViewModal"
                >
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  Edit Building
                </router-link>
              </div>

            </div>

          </div>
        </Transition>
      </Teleport>

    </div>

    <!-- ═══════════════════════════════════════════════════════ -->
    <!-- Scoped Styles -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <component :is="'style'" scoped>

      /* ── Root ─────────────────────────────────── */
      .bi-root {
        --bi-bg:       #f4f3fb;
        --bi-surface:  #ffffff;
        --bi-border:   #e8e5f5;
        --bi-border-2: #d4cff0;
        --bi-text:     #1a1730;
        --bi-muted:    #7b748f;
        --bi-accent:   #5b4ce8;
        --bi-accent-h: #4a3dd6;
        --bi-accent-bg:#f0eeff;
        --bi-green:    #15b97a;
        --bi-green-bg: #edfaf4;
        --bi-amber:    #e07d20;
        --bi-amber-bg: #fff7ed;
        --bi-red:      #e0334c;
        --bi-red-bg:   #fff1f3;
        --bi-blue:     #2a7de1;
        --bi-blue-bg:  #eef5ff;
        --bi-shadow-sm: 0 1px 3px rgba(40,30,90,.07), 0 1px 2px rgba(40,30,90,.04);
        --bi-shadow-md: 0 4px 16px rgba(40,30,90,.1), 0 1px 4px rgba(40,30,90,.06);
        --bi-shadow-lg: 0 20px 60px rgba(40,30,90,.18), 0 4px 16px rgba(40,30,90,.08);
        min-height: 100%;
        padding: 28px;
        background: var(--bi-bg);
        color: var(--bi-text);
      }

      /* ── Header ───────────────────────────────── */
      .bi-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 28px;
        flex-wrap: wrap;
      }
      .bi-eyebrow {
        font-size: 11px;
        font-weight: 600;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: var(--bi-accent);
        margin-bottom: 6px;
      }
      .bi-title {
        font-size: 32px;
        font-weight: 800;
        letter-spacing: -.02em;
        line-height: 1;
        color: var(--bi-text);
        margin: 0;
      }
      .bi-subtitle {
        font-size: 14px;
        color: var(--bi-muted);
        margin-top: 6px;
        font-weight: 400;
      }
      .bi-header-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
      }

      /* ── Buttons ──────────────────────────────── */
      .bi-btn-ghost {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 10px 18px;
        border-radius: 12px;
        border: 1.5px solid var(--bi-border-2);
        background: var(--bi-surface);
        color: var(--bi-text);
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: border-color .15s, background .15s, color .15s;
        font-family: 'DM Sans', sans-serif;
        text-decoration: none;
        white-space: nowrap;
      }
      .bi-btn-ghost:hover {
        border-color: var(--bi-accent);
        background: var(--bi-accent-bg);
        color: var(--bi-accent);
      }
      .bi-btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 10px 20px;
        border-radius: 12px;
        border: none;
        background: var(--bi-accent);
        color: #fff;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: background .15s, box-shadow .15s, transform .1s;
        box-shadow: 0 4px 14px rgba(91,76,232,.35);
        text-decoration: none;
        white-space: nowrap;
        font-family: 'DM Sans', sans-serif;
      }
      .bi-btn-primary:hover {
        background: var(--bi-accent-h);
        box-shadow: 0 6px 20px rgba(91,76,232,.45);
        transform: translateY(-1px);
      }
      .bi-btn-primary:active { transform: translateY(0); }

      /* ── Stats ────────────────────────────────── */
      .bi-stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
        margin-bottom: 24px;
      }
      @media (max-width: 1024px) { .bi-stats-grid { grid-template-columns: repeat(2, 1fr); } }
      @media (max-width: 560px)  { .bi-stats-grid { grid-template-columns: 1fr; } }

      .bi-stat {
        position: relative;
        background: var(--bi-surface);
        border: 1.5px solid var(--bi-border);
        border-radius: 20px;
        padding: 20px;
        overflow: hidden;
        box-shadow: var(--bi-shadow-sm);
        animation: bi-fadeUp .3s ease both;
      }
      .bi-stat:nth-child(2) { animation-delay: .06s; }
      .bi-stat:nth-child(3) { animation-delay: .12s; }
      .bi-stat:nth-child(4) { animation-delay: .18s; }

      .bi-stat-inner { display: flex; align-items: center; gap: 14px; }

      .bi-stat-icon {
        width: 44px; height: 44px;
        border-radius: 12px;
        background: var(--bi-accent-bg);
        color: var(--bi-accent);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
      }
      .bi-stat-icon--active   { background: var(--bi-green-bg); color: var(--bi-green); }
      .bi-stat-icon--inactive { background: var(--bi-amber-bg); color: var(--bi-amber); }
      .bi-stat-icon--floors   { background: var(--bi-blue-bg);  color: var(--bi-blue);  }

      .bi-stat-label { font-size: 12px; font-weight: 500; color: var(--bi-muted); margin-bottom: 4px; }
      .bi-stat-value { font-size: 30px; font-weight: 700; letter-spacing: -.03em; color: var(--bi-text); line-height: 1; }
      .bi-stat-value--active   { color: var(--bi-green); }
      .bi-stat-value--inactive { color: var(--bi-amber); }

      .bi-stat-bar {
        position: absolute;
        bottom: 0; left: 0; right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--bi-accent), #9b8df7);
      }
      .bi-stat-bar--active   { background: linear-gradient(90deg, var(--bi-green), #3deaa0); }
      .bi-stat-bar--inactive { background: linear-gradient(90deg, var(--bi-amber), #f5c06a); }
      .bi-stat-bar--floors   { background: linear-gradient(90deg, var(--bi-blue), #60aaff); }

      /* ── Panel ────────────────────────────────── */
      .bi-panel {
        background: var(--bi-surface);
        border: 1.5px solid var(--bi-border);
        border-radius: 24px;
        overflow: hidden;
        box-shadow: var(--bi-shadow-sm);
        display: flex;
        flex-direction: column;
        animation: bi-fadeUp .35s .22s ease both;
      }

      /* ── Toolbar ──────────────────────────────── */
      .bi-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 16px 20px;
        border-bottom: 1.5px solid var(--bi-border);
        flex-wrap: wrap;
      }

      .bi-search-wrap {
        position: relative;
        flex: 1;
        max-width: 360px;
        min-width: 200px;
      }
      .bi-search-ico {
        position: absolute;
        left: 13px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--bi-muted);
        pointer-events: none;
      }
      .bi-search-input {
        width: 100%;
        padding: 10px 38px 10px 38px;
        border-radius: 12px;
        border: 1.5px solid var(--bi-border);
        background: var(--bi-bg);
        font-size: 14px;
        color: var(--bi-text);
        outline: none;
        transition: border-color .15s, box-shadow .15s;
        font-family: 'DM Sans', sans-serif;
      }
      .bi-search-input::placeholder { color: var(--bi-muted); }
      .bi-search-input:focus {
        border-color: var(--bi-accent);
        box-shadow: 0 0 0 3px rgba(91,76,232,.12);
        background: #fff;
      }
      .bi-search-clear {
        position: absolute;
        right: 10px; top: 50%;
        transform: translateY(-50%);
        background: none; border: none;
        color: var(--bi-muted); cursor: pointer;
        padding: 4px;
        border-radius: 6px;
        display: flex; align-items: center; justify-content: center;
      }
      .bi-search-clear:hover { color: var(--bi-text); background: var(--bi-border); }

      .bi-filters-right {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
      }

      .bi-filter-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 14px;
        border-radius: 999px;
        border: 1.5px solid var(--bi-border);
        font-size: 13px;
        font-weight: 500;
        color: var(--bi-muted);
        cursor: pointer;
        transition: all .15s;
        background: var(--bi-surface);
        user-select: none;
      }
      .bi-filter-pill:hover { border-color: var(--bi-border-2); color: var(--bi-text); }
      .bi-filter-pill--active {
        border-color: var(--bi-accent);
        background: var(--bi-accent-bg);
        color: var(--bi-accent);
      }
      .bi-filter-pill--clear {
        border-color: transparent;
        background: transparent;
        color: var(--bi-muted);
        font-size: 12px;
      }
      .bi-filter-pill--clear:hover { color: var(--bi-red); }

      .bi-dot {
        width: 7px; height: 7px;
        border-radius: 50%;
        display: inline-block;
      }
      .bi-dot--green   { background: var(--bi-green); }
      .bi-dot--amber   { background: var(--bi-amber); }

      /* ── Loading ──────────────────────────────── */
      .bi-loading-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 80px 20px;
        gap: 16px;
      }
      .bi-spinner {
        width: 40px; height: 40px;
        border: 3px solid var(--bi-border);
        border-top-color: var(--bi-accent);
        border-radius: 50%;
        animation: bi-spin .8s linear infinite;
      }
      .bi-loading-text { font-size: 14px; color: var(--bi-muted); }

      /* ── Empty State ──────────────────────────── */
      .bi-empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 80px 20px;
        text-align: center;
        gap: 10px;
      }
      .bi-empty-icon {
        width: 80px; height: 80px;
        border-radius: 24px;
        background: var(--bi-accent-bg);
        color: var(--bi-accent);
        display: flex; align-items: center; justify-content: center;
        margin-bottom: 8px;
      }
      .bi-empty-title { font-size: 20px; font-weight: 700; color: var(--bi-text); margin: 0; }
      .bi-empty-body  { font-size: 14px; color: var(--bi-muted); margin-bottom: 8px; }

      /* ── Table ────────────────────────────────── */
      .bi-table-wrap { overflow-x: auto; flex: 1; }
      .bi-table { width: 100%; border-collapse: collapse; }

      .bi-th {
        padding: 12px 20px;
        text-align: left;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--bi-muted);
        border-bottom: 1.5px solid var(--bi-border);
        background: #faf9ff;
        white-space: nowrap;
      }
      .bi-th--right { text-align: right; }

      .bi-td {
        padding: 14px 20px;
        border-bottom: 1px solid var(--bi-border);
        vertical-align: middle;
      }
      .bi-td--right { text-align: right; }

      .bi-row { animation: bi-fadeUp .2s ease both; }
      .bi-row:last-child .bi-td { border-bottom: none; }

      /* Building Cell */
      .bi-building-cell { display: flex; align-items: center; gap: 12px; }
      .bi-avatar {
        width: 42px; height: 42px;
        border-radius: 14px;
        background: var(--bi-accent-bg);
        color: var(--bi-accent);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        border: 1.5px solid var(--bi-border);
      }
      .bi-building-name { font-size: 14px; font-weight: 600; color: var(--bi-text); white-space: nowrap; }
      .bi-building-loc  {
        font-size: 12px; color: var(--bi-muted);
        display: flex; align-items: center; gap: 3px;
        margin-top: 2px;
      }

      /* Badges */
      .bi-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: .02em;
      }
      .bi-badge--code     { background: #f1f0fb; color: #5046b8; border: 1px solid #e0dcf7; }
      .bi-badge--currency { background: var(--bi-blue-bg); color: var(--bi-blue); border: 1px solid #d4e8ff; }

      .bi-tz { font-size: 11px; color: var(--bi-muted); margin-top: 4px; }

      /* Floors */
      .bi-floors-cell { display: flex; align-items: baseline; gap: 4px; }
      .bi-floors-num   { font-size: 16px; font-weight: 700; color: var(--bi-text); }
      .bi-floors-label { font-size: 11px; color: var(--bi-muted); }

      /* Status */
      .bi-status {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 5px 12px;
        border-radius: 99px;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
      }
      .bi-status--active   { background: var(--bi-green-bg); color: var(--bi-green); }
      .bi-status--inactive { background: var(--bi-amber-bg); color: var(--bi-amber); }

      .bi-status-dot {
        width: 7px; height: 7px;
        border-radius: 50%;
        background: currentColor;
        display: inline-block;
        animation: bi-pulse 2s infinite;
      }
      .bi-status--inactive .bi-status-dot { animation: none; }

      @keyframes bi-pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: .4; }
      }

      /* Action Buttons */
      .bi-actions { display: flex; align-items: center; justify-content: flex-end; gap: 6px; }

      .bi-action-btn {
        width: 34px; height: 34px;
        border-radius: 10px;
        border: 1.5px solid var(--bi-border);
        background: var(--bi-surface);
        color: var(--bi-muted);
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
        transition: all .15s;
        text-decoration: none;
        flex-shrink: 0;
      }
      .bi-action-btn:hover { border-color: var(--bi-accent); color: var(--bi-accent); background: var(--bi-accent-bg); }
      .bi-action-btn--danger:hover { border-color: var(--bi-red); color: var(--bi-red); background: var(--bi-red-bg); }

      /* Table Footer */
      .bi-table-footer {
        padding: 12px 20px;
        border-top: 1.5px solid var(--bi-border);
        font-size: 12px;
        color: var(--bi-muted);
        background: #faf9ff;
      }
      .bi-table-footer strong { color: var(--bi-text); }

      /* ── Modal ────────────────────────────────── */
      .bi-backdrop {
        position: fixed;
        inset: 0;
        z-index: 1000;
        background: rgba(20, 16, 48, .55);
        backdrop-filter: blur(6px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        animation: bi-backdropIn .2s ease;
      }

      .bi-modal {
        width: 100%;
        max-width: 640px;
        background: var(--bi-surface);
        border-radius: 28px;
        overflow: hidden;
        box-shadow: var(--bi-shadow-lg);
        animation: bi-modalIn .25s cubic-bezier(.34,1.3,.64,1) both;
        max-height: 90vh;
        overflow-y: auto;
      }

      .bi-modal-header {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 22px 22px 20px;
        border-bottom: 1.5px solid var(--bi-border);
      }
      .bi-modal-avatar {
        width: 52px; height: 52px;
        border-radius: 18px;
        background: var(--bi-accent-bg);
        color: var(--bi-accent);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        border: 1.5px solid var(--bi-border);
      }
      .bi-modal-title-block { flex: 1; min-width: 0; }
      .bi-modal-title { font-size: 22px; font-weight: 800; letter-spacing: -.02em; color: var(--bi-text); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
      .bi-modal-sub   { font-size: 12px; color: var(--bi-muted); margin-top: 3px; }

      .bi-modal-close {
        width: 36px; height: 36px;
        border-radius: 10px;
        border: 1.5px solid var(--bi-border);
        background: none;
        color: var(--bi-muted);
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
        transition: all .15s;
        flex-shrink: 0;
      }
      .bi-modal-close:hover { background: var(--bi-red-bg); color: var(--bi-red); border-color: var(--bi-red); }

      .bi-modal-banner {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 22px;
        font-size: 13px;
        font-weight: 600;
      }
      .bi-modal-banner--active   { background: var(--bi-green-bg); color: var(--bi-green); }
      .bi-modal-banner--inactive { background: var(--bi-amber-bg); color: var(--bi-amber); }

      .bi-modal-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1px;
        background: var(--bi-border);
        margin: 0;
        border-bottom: 1.5px solid var(--bi-border);
      }
      .bi-info-card {
        background: var(--bi-surface);
        padding: 16px 22px;
      }
      .bi-info-card--full { grid-column: 1 / -1; }
      .bi-info-label { font-size: 11px; font-weight: 600; letter-spacing: .07em; text-transform: uppercase; color: var(--bi-muted); margin-bottom: 6px; }
      .bi-info-value { font-size: 16px; font-weight: 600; color: var(--bi-text); }

      .bi-modal-desc-wrap { padding: 18px 22px; }
      .bi-modal-desc {
        font-size: 14px;
        color: var(--bi-muted);
        line-height: 1.7;
        background: var(--bi-bg);
        border-radius: 14px;
        padding: 14px 16px;
        margin-top: 8px;
        border: 1px solid var(--bi-border);
      }

      .bi-modal-footer {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        padding: 16px 22px;
        border-top: 1.5px solid var(--bi-border);
        background: #faf9ff;
      }

      /* ── Transitions ──────────────────────────── */
      .bi-modal-enter-active, .bi-modal-leave-active { transition: opacity .2s ease; }
      .bi-modal-enter-from, .bi-modal-leave-to { opacity: 0; }

      .bi-clear-enter-active, .bi-clear-leave-active { transition: opacity .15s; }
      .bi-clear-enter-from, .bi-clear-leave-to { opacity: 0; }

    </component>

 
</template>

<script setup>

import {
  ref,
  reactive,
  computed,
  onMounted
} from 'vue'

import api
from '@/services/api'

import DashboardLayout
from '@/layouts/DashboardLayout.vue'

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const selectedBuilding = ref(null)
const showViewModal    = ref(false)

const openViewModal = (building) => {
  selectedBuilding.value = building
  showViewModal.value    = true
}

const closeViewModal = () => {
  showViewModal.value    = false
  selectedBuilding.value = null
}

const loading   = ref(false)
const buildings = ref([])
const meta      = ref({})

const filters = reactive({
  search: '',
  status: '',
})

/*
|--------------------------------------------------------------------------
| Filter Helpers
|--------------------------------------------------------------------------
*/

const setStatus = (val) => {
  filters.status = filters.status === val ? '' : val
  fetchBuildings()
}

/*
|--------------------------------------------------------------------------
| Search Debounce
|--------------------------------------------------------------------------
*/

let searchTimeout = null

const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => fetchBuildings(), 400)
}

/*
|--------------------------------------------------------------------------
| Fetch Buildings
|--------------------------------------------------------------------------
*/

const fetchBuildings = async () => {
  try {
    loading.value = true

    const response = await api.get('/buildings', {
      params: {
        search: filters.search,
        status: filters.status,
      }
    })

    buildings.value = response.data.data || []
    meta.value      = response.data.meta || {}
  }
  catch (error) {
    console.error(error)
  }
  finally {
    loading.value = false
  }
}

/*
|--------------------------------------------------------------------------
| Computed Stats
|--------------------------------------------------------------------------
*/

const activeBuildings = computed(() =>
  buildings.value.filter(b => b.is_active).length
)

const inactiveBuildings = computed(() =>
  buildings.value.filter(b => !b.is_active).length
)

const totalFloors = computed(() =>
  buildings.value.reduce((total, b) => total + Number(b.total_floors || 0), 0)
)

/*
|--------------------------------------------------------------------------
| Delete Building
|--------------------------------------------------------------------------
*/

const deleteBuilding = async (building) => {
  const confirmed = confirm(`Delete "${building.name}"? This action cannot be undone.`)
  if (!confirmed) return

  try {
    await api.delete(`/buildings/${building.id}`)
    fetchBuildings()
  }
  catch (error) {
    console.error(error)
    alert('Failed to delete building. Please try again.')
  }
}

/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/

onMounted(() => {
  fetchBuildings()
})

</script>