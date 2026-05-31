<template>
 
    <div class="ra-root">

      <!-- Fonts + Global Keyframes -->
      <component :is="'style'">
        @import url('https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap');
        .ra-root, .ra-root * { box-sizing: border-box; font-family: 'DM Sans', sans-serif; }
        .ra-display { font-family: 'Syne', sans-serif; }

        @keyframes ra-fadeUp  { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:translateY(0); } }
        @keyframes ra-spin    { to { transform:rotate(360deg); } }
        @keyframes ra-pulse   { 0%,100%{opacity:1;} 50%{opacity:.35;} }
        @keyframes ra-modalIn { from{opacity:0;transform:scale(.95) translateY(14px);} to{opacity:1;transform:scale(1) translateY(0);} }
        @keyframes ra-slideDown { from{opacity:0;transform:translateY(-6px);} to{opacity:1;transform:translateY(0);} }
        @keyframes ra-progressFill { from{width:0;} to{width:var(--target-width);} }

        /* ── Row Actions Container ── */
.ra-row-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 8px;
}

/* ── Base Action Button (View & Edit) ── */
.ra-action-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 6px;
  border-radius: 8px;
  border: none;
  background: var(--c-bg); /* Soft background */
  color: var(--c-muted);   /* Visible grey icon */
  cursor: pointer;
  transition: all 0.15s ease;
  text-decoration: none;
}

.ra-action-btn:hover {
  background: var(--c-border);
  color: var(--c-accent); /* Highlights to purple on hover */
}

/* ── Terminate Button (Warning / Amber) ── */
.ra-action-btn--warn {
  color: var(--c-amber);
  background: var(--c-amber-bg);
}

.ra-action-btn--warn:hover {
  background: var(--c-amber);
  color: #fff;
}

/* ── Delete Button (Danger / Red) ── */
.ra-action-btn--danger {
  color: var(--c-red);
  background: var(--c-red-bg);
}

.ra-action-btn--danger:hover {
  background: var(--c-red);
  color: #fff;
}

        .ra-table-wrap::-webkit-scrollbar { height:5px; }
        .ra-table-wrap::-webkit-scrollbar-track { background:transparent; }
        .ra-table-wrap::-webkit-scrollbar-thumb { background:#ddd8f5; border-radius:99px; }

        .ra-stat:nth-child(1) { animation-delay:.00s; }
        .ra-stat:nth-child(2) { animation-delay:.06s; }
        .ra-stat:nth-child(3) { animation-delay:.12s; }
        .ra-stat:nth-child(4) { animation-delay:.18s; }

        .ra-confirm-enter-active, .ra-confirm-leave-active { transition: all .2s ease; }
        .ra-confirm-enter-from, .ra-confirm-leave-to { opacity:0; transform:scale(.96) translateY(10px); }
        .ra-fade-enter-active, .ra-fade-leave-active { transition:opacity .18s; }
        .ra-fade-enter-from, .ra-fade-leave-to { opacity:0; }
        .ra-clear-enter-active, .ra-clear-leave-active { transition:opacity .15s; }
        .ra-clear-enter-from, .ra-clear-leave-to { opacity:0; }
      </component>

      <!-- ════════════════════════════════════════════
           PAGE HEADER
      ════════════════════════════════════════════ -->
      <div class="ra-header">
        <div>
          <div class="ra-eyebrow">Lease Management</div>
          <h1 class="ra-title ra-display">Rental Agreements</h1>
          <p class="ra-subtitle">Manage lease lifecycle, occupancy, tenants, and contract operations</p>
        </div>
        <div class="ra-header-actions">
          <button @click="loadAgreements" class="ra-btn-ghost" title="Refresh">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
              <polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/>
              <path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/>
            </svg>
            Refresh
          </button>
          <router-link to="/rental-agreements/create" class="ra-btn-primary">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Create Agreement
          </router-link>
        </div>
      </div>

      <!-- ════════════════════════════════════════════
           KPI STATS
      ════════════════════════════════════════════ -->
      <div class="ra-stats-grid">

        <div class="ra-stat">
          <div class="ra-stat-inner">
            <div class="ra-stat-icon ra-stat-icon--purple">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            </div>
            <div>
              <p class="ra-stat-label">Total Agreements</p>
              <p class="ra-stat-value ra-display">{{ stats.total }}</p>
            </div>
          </div>
          <div class="ra-stat-progress">
            <div class="ra-stat-progress-fill ra-stat-progress-fill--purple" :style="{ width: '100%' }"></div>
          </div>
        </div>

        <div class="ra-stat">
          <div class="ra-stat-inner">
            <div class="ra-stat-icon ra-stat-icon--green">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div>
              <p class="ra-stat-label">Active</p>
              <p class="ra-stat-value ra-stat-value--green ra-display">{{ stats.active }}</p>
            </div>
          </div>
          <div class="ra-stat-progress">
            <div class="ra-stat-progress-fill ra-stat-progress-fill--green" :style="{ width: stats.total ? (stats.active / stats.total * 100) + '%' : '0%' }"></div>
          </div>
        </div>

        <div class="ra-stat">
          <div class="ra-stat-inner">
            <div class="ra-stat-icon ra-stat-icon--amber">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
            </div>
            <div>
              <p class="ra-stat-label">Draft</p>
              <p class="ra-stat-value ra-stat-value--amber ra-display">{{ stats.draft }}</p>
            </div>
          </div>
          <div class="ra-stat-progress">
            <div class="ra-stat-progress-fill ra-stat-progress-fill--amber" :style="{ width: stats.total ? (stats.draft / stats.total * 100) + '%' : '0%' }"></div>
          </div>
        </div>

        <div class="ra-stat ra-stat--revenue">
          <div class="ra-stat-inner">
            <div class="ra-stat-icon ra-stat-icon--blue">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
            </div>
            <div>
              <p class="ra-stat-label">Monthly Revenue</p>
              <p class="ra-stat-value ra-stat-value--blue ra-display">${{ stats.monthlyRevenue }}</p>
            </div>
          </div>
          <div class="ra-stat-progress">
            <div class="ra-stat-progress-fill ra-stat-progress-fill--blue" style="width:72%"></div>
          </div>
        </div>

      </div>

      <!-- ════════════════════════════════════════════
           MAIN PANEL
      ════════════════════════════════════════════ -->
      <div class="ra-panel">

        <!-- ── Toolbar ── -->
        <div class="ra-toolbar">

          <!-- Search -->
          <div class="ra-search-wrap">
            <svg class="ra-search-ico" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input
              v-model="filters.search"
              type="text"
              placeholder="Search agreement, tenant, building, unit…"
              class="ra-search-input"
            />
            <Transition name="ra-clear">
              <button v-if="filters.search" @click="filters.search = ''" class="ra-search-clear">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </Transition>
          </div>

          <!-- Status Filter Pills -->
          <div class="ra-filter-pills">
            <button
              v-for="s in statusOptions"
              :key="s.value"
              @click="toggleStatus(s.value)"
              :class="['ra-pill', filters.status === s.value && `ra-pill--${s.color}`]"
            >
              <span :class="['ra-pill-dot', `ra-pill-dot--${s.color}`]"></span>
              {{ s.label }}
              <span v-if="statusCount(s.value)" class="ra-pill-count">{{ statusCount(s.value) }}</span>
            </button>
          </div>

          <!-- Building Search -->
          <div class="ra-building-wrap">
            <svg class="ra-building-ico" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 9 12 2 21 9"/><path d="M9 22V12h6v10"/><path d="M3 9v13h18V9"/></svg>
            <input
              v-model="filters.building"
              type="text"
              placeholder="Filter building…"
              class="ra-building-input"
            />
          </div>

          <!-- Reset -->
          <button
            v-if="filters.search || filters.status || filters.building"
            @click="resetFilters"
            class="ra-reset-btn"
          >
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            Clear
          </button>

          <!-- Result Count -->
          <div class="ra-result-count">
            <span class="ra-result-num">{{ filteredAgreements.length }}</span>
            <span class="ra-result-label">results</span>
          </div>

        </div>

        <!-- ── Loading ── -->
        <div v-if="loading" class="ra-loading">
          <div class="ra-spinner"></div>
          <p class="ra-loading-text">Loading agreements…</p>
        </div>

        <!-- ── Empty ── -->
        <div v-else-if="filteredAgreements.length === 0" class="ra-empty">
          <div class="ra-empty-icon">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/></svg>
          </div>
          <h3 class="ra-empty-title ra-display">No agreements found</h3>
          <p class="ra-empty-body">{{ filters.search || filters.status || filters.building ? 'Adjust filters to see more results.' : 'Create your first rental agreement to get started.' }}</p>
          <div style="display:flex;gap:10px;margin-top:10px;">
            <button v-if="filters.search || filters.status || filters.building" @click="resetFilters" class="ra-btn-ghost">Clear Filters</button>
            <router-link v-else to="/rental-agreements/create" class="ra-btn-primary">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Create Agreement
            </router-link>
          </div>
        </div>

        <!-- ── Table ── -->
        <div v-else class="ra-table-wrap">
          <table class="ra-table">

            <thead>
              <tr>
                <th class="ra-th">Agreement</th>
                <th class="ra-th">Tenant</th>
                <th class="ra-th">Property</th>
                <th class="ra-th">Rent</th>
                <th class="ra-th">Duration</th>
                <th class="ra-th">Status</th>
                <th class="ra-th ra-th--right">Actions</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="(agreement, i) in filteredAgreements"
                :key="agreement.id"
                class="ra-row"
                :style="{ animationDelay: i * 0.035 + 's' }"
              >

                <!-- Agreement -->
                <td class="ra-td">
                  <div class="ra-agreement-cell">
                    <div :class="['ra-agreement-icon', `ra-agreement-icon--${agreement.agreement_type}`]">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    </div>
                    <div>
                      <p class="ra-agreement-num">{{ agreement.agreement_number }}</p>
                      <span :class="['ra-type-badge', `ra-type-badge--${agreement.agreement_type}`]">{{ agreement.agreement_type }}</span>
                    </div>
                  </div>
                </td>

                <!-- Tenant -->
                <td class="ra-td">
                  <div class="ra-tenant-cell">
                    <div class="ra-tenant-avatar" :style="{ background: avatarGradient(agreement.tenant_name) }">
                      {{ initials(agreement.tenant_name) }}
                    </div>
                    <div>
                      <p class="ra-tenant-name">{{ agreement.tenant_name }}</p>
                      <p class="ra-tenant-phone">{{ agreement.tenant_phone }}</p>
                    </div>
                  </div>
                </td>

                <!-- Property -->
                <td class="ra-td">
                  <div class="ra-property-cell">
                    <p class="ra-building-name">{{ agreement.building_name }}</p>
                    <div class="ra-unit-row">
                      <span class="ra-unit-badge">U{{ agreement.unit_number }}</span>
                      <span class="ra-floor-badge">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><polyline points="3 9 12 2 21 9"/><path d="M9 22V12h6v10"/><path d="M3 9v13h18V9"/></svg>
                        Fl. {{ agreement.floor }}
                      </span>
                    </div>
                  </div>
                </td>

                <!-- Rent -->
                <td class="ra-td">
                  <div class="ra-rent-cell">
                    <p class="ra-rent-amount">${{ Number(agreement.monthly_rent).toLocaleString() }}</p>
                    <p class="ra-rent-cycle">/ month</p>
                  </div>
                </td>

                <!-- Duration -->
                <td class="ra-td">
                  <div class="ra-duration-cell">
                    <div class="ra-date-row">
                      <span class="ra-date-label">From</span>
                      <span class="ra-date-val">{{ agreement.start_date }}</span>
                    </div>
                    <div class="ra-date-row">
                      <span class="ra-date-label">To</span>
                      <span class="ra-date-val">{{ agreement.end_date }}</span>
                    </div>
                    <!-- Days remaining bar for active -->
                    <div v-if="agreement.status === 'active' && agreement.daysRemaining !== null" class="ra-days-bar-wrap">
                      <div class="ra-days-bar-track">
                        <div class="ra-days-bar-fill" :style="{ width: agreement.progressPct + '%', background: agreement.progressColor }"></div>
                      </div>
                      <span class="ra-days-remaining" :style="{ color: agreement.progressColor }">{{ agreement.daysRemaining }}d left</span>
                    </div>
                  </div>
                </td>

                <!-- Status -->
                <td class="ra-td">
                  <span :class="['ra-status', `ra-status--${agreement.status}`]">
                    <span class="ra-status-dot" :class="agreement.status === 'active' && 'ra-status-dot--pulse'"></span>
                    {{ agreement.status_label }}
                  </span>
                </td>

                <!-- Actions -->
                <td class="ra-td ra-td--right">
                  <div class="ra-actions ra-row-actions">

                    <!-- View -->
                    <router-link
                      :to="`/rental-agreements/${agreement.id}`"
                      class="ra-action-btn"
                      title="View details"
                    >
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </router-link>

                    <!-- Edit -->
                    <router-link
                      :to="`/rental-agreements/${agreement.id}/edit`"
                      class="ra-action-btn"
                      title="Edit agreement"
                    >
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </router-link>

                    <!-- Terminate (active only) -->
                    <button
                      v-if="agreement.status === 'active'"
                      @click="promptTerminate(agreement)"
                      class="ra-action-btn ra-action-btn--warn"
                      title="Terminate agreement"
                    >
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                    </button>

                    <!-- Delete (draft only) -->
                    <button
                      v-if="agreement.status === 'draft'"
                      @click="promptDelete(agreement)"
                      class="ra-action-btn ra-action-btn--danger"
                      title="Delete draft"
                    >
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                    </button>

                  </div>
                </td>

              </tr>
            </tbody>

          </table>

          <!-- Table Footer -->
          <div class="ra-table-footer">
            <span>Showing <strong>{{ filteredAgreements.length }}</strong> of <strong>{{ agreements.length }}</strong> agreements</span>
            <span v-if="stats.active" class="ra-footer-revenue">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
              Active revenue: ${{ stats.monthlyRevenue }}/mo
            </span>
          </div>
        </div>

      </div>

      <!-- ════════════════════════════════════════════
           CONFIRM MODAL (Terminate / Delete)
      ════════════════════════════════════════════ -->
      <Teleport to="body">
        <Transition name="ra-fade">
          <div v-if="confirmModal.show" class="ra-backdrop" @click.self="closeConfirm">
            <Transition name="ra-confirm" appear>
              <div v-if="confirmModal.show" class="ra-modal">

                <!-- Icon -->
                <div :class="['ra-modal-icon', confirmModal.type === 'terminate' ? 'ra-modal-icon--warn' : 'ra-modal-icon--danger']">
                  <svg v-if="confirmModal.type === 'terminate'" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                  <svg v-else width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                </div>

                <!-- Text -->
                <h3 class="ra-modal-title ra-display">
                  {{ confirmModal.type === 'terminate' ? 'Terminate Agreement?' : 'Delete Draft?' }}
                </h3>
                <p class="ra-modal-body">
                  <template v-if="confirmModal.type === 'terminate'">
                    You're about to terminate agreement
                    <strong>{{ confirmModal.agreement?.agreement_number }}</strong>
                    for tenant <strong>{{ confirmModal.agreement?.tenant_name }}</strong>.
                    This action cannot be undone.
                  </template>
                  <template v-else>
                    Permanently delete draft
                    <strong>{{ confirmModal.agreement?.agreement_number }}</strong>?
                    All unsaved information will be lost.
                  </template>
                </p>

                <!-- Agreement Quick Info -->
                <div class="ra-modal-info-card">
                  <div class="ra-modal-info-row">
                    <span class="ra-modal-info-label">Building</span>
                    <span class="ra-modal-info-val">{{ confirmModal.agreement?.building_name }}</span>
                  </div>
                  <div class="ra-modal-info-row">
                    <span class="ra-modal-info-label">Unit</span>
                    <span class="ra-modal-info-val">{{ confirmModal.agreement?.unit_number }}</span>
                  </div>
                  <div class="ra-modal-info-row">
                    <span class="ra-modal-info-label">Rent</span>
                    <span class="ra-modal-info-val">${{ Number(confirmModal.agreement?.monthly_rent || 0).toLocaleString() }}/mo</span>
                  </div>
                </div>

                <!-- Actions -->
                <div class="ra-modal-actions">
                  <button @click="closeConfirm" class="ra-btn-ghost" :disabled="confirmModal.loading">Cancel</button>
                  <button
                    @click="executeConfirm"
                    :disabled="confirmModal.loading"
                    :class="['ra-modal-confirm-btn', confirmModal.type === 'terminate' ? 'ra-modal-confirm-btn--warn' : 'ra-modal-confirm-btn--danger']"
                  >
                    <span v-if="confirmModal.loading" class="ra-mini-spinner"></span>
                    <svg v-else-if="confirmModal.type === 'terminate'" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                    <svg v-else width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg>
                    {{ confirmModal.loading ? 'Processing…' : (confirmModal.type === 'terminate' ? 'Yes, Terminate' : 'Yes, Delete') }}
                  </button>
                </div>

              </div>
            </Transition>
          </div>
        </Transition>
      </Teleport>

      <!-- ════════════════════════════════════════════
           TOAST
      ════════════════════════════════════════════ -->
      <Teleport to="body">
        <Transition name="ra-fade">
          <div v-if="toast.show" :class="['ra-toast', `ra-toast--${toast.type}`]">
            <svg v-if="toast.type === 'success'" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            {{ toast.message }}
          </div>
        </Transition>
      </Teleport>

    </div>

    <!-- ════════════════════════════════════════════
         SCOPED STYLES
    ════════════════════════════════════════════ -->
    <component :is="'style'" scoped>
      .ra-root {
        --c-bg:        #f3f2f9;
        --c-surface:   #ffffff;
        --c-border:    #e5e2f5;
        --c-border2:   #cfc9ef;
        --c-text:      #18152e;
        --c-muted:     #7870a0;
        --c-accent:    #5b4ce8;
        --c-accent-h:  #4a3dd6;
        --c-accent-bg: #f0eeff;
        --c-green:     #12b374;
        --c-green-bg:  #edfaf4;
        --c-amber:     #d97706;
        --c-amber-bg:  #fffbeb;
        --c-red:       #dc2626;
        --c-red-bg:    #fff1f2;
        --c-blue:      #2563eb;
        --c-blue-bg:   #eff6ff;
        --c-shadow-sm: 0 1px 3px rgba(30,20,80,.07), 0 1px 2px rgba(30,20,80,.04);
        --c-shadow-md: 0 4px 16px rgba(30,20,80,.10);
        --c-shadow-lg: 0 20px 60px rgba(30,20,80,.18), 0 4px 16px rgba(30,20,80,.08);
        min-height: 100%;
        padding: 28px;
        background: var(--c-bg);
        color: var(--c-text);
        animation: ra-fadeUp .25s ease both;
      }

      /* ── Header ── */
      .ra-header { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; margin-bottom:24px; flex-wrap:wrap; }
      .ra-eyebrow { font-size:11px; font-weight:600; letter-spacing:.12em; text-transform:uppercase; color:var(--c-accent); margin-bottom:5px; }
      .ra-title   { font-size:32px; font-weight:800; letter-spacing:-.02em; color:var(--c-text); margin:0; line-height:1; }
      .ra-subtitle { font-size:13.5px; color:var(--c-muted); margin-top:5px; }
      .ra-header-actions { display:flex; align-items:center; gap:10px; flex-shrink:0; }

      /* ── Buttons ── */
      .ra-btn-ghost {
        display:inline-flex; align-items:center; gap:7px; padding:10px 18px;
        border-radius:12px; border:1.5px solid var(--c-border2); background:var(--c-surface);
        color:var(--c-text); font-size:14px; font-weight:500; cursor:pointer;
        transition:all .15s; font-family:'DM Sans',sans-serif; text-decoration:none; white-space:nowrap;
      }
      .ra-btn-ghost:hover { border-color:var(--c-accent); background:var(--c-accent-bg); color:var(--c-accent); }
      .ra-btn-ghost:disabled { opacity:.5; cursor:not-allowed; }

      .ra-btn-primary {
        display:inline-flex; align-items:center; gap:7px; padding:10px 20px;
        border-radius:12px; border:none; background:var(--c-accent); color:#fff;
        font-size:14px; font-weight:600; cursor:pointer; transition:all .15s;
        box-shadow:0 4px 14px rgba(91,76,232,.35); text-decoration:none; white-space:nowrap;
        font-family:'DM Sans',sans-serif;
      }
      .ra-btn-primary:hover { background:var(--c-accent-h); transform:translateY(-1px); box-shadow:0 6px 20px rgba(91,76,232,.45); }
      .ra-btn-primary:active { transform:translateY(0); }

      /* ── Stats ── */
      .ra-stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:20px; }
      @media (max-width:1024px) { .ra-stats-grid { grid-template-columns:repeat(2,1fr); } }
      @media (max-width:560px)  { .ra-stats-grid { grid-template-columns:1fr; } }

      .ra-stat {
        background:var(--c-surface); border:1.5px solid var(--c-border);
        border-radius:20px; padding:20px; overflow:hidden; box-shadow:var(--c-shadow-sm);
        animation:ra-fadeUp .28s ease both; position:relative;
      }
      .ra-stat--revenue { background:linear-gradient(135deg,#f8f7ff 60%,#eef0ff); }
      .ra-stat-inner { display:flex; align-items:center; gap:14px; margin-bottom:14px; }
      .ra-stat-icon {
        width:44px; height:44px; border-radius:12px;
        display:flex; align-items:center; justify-content:center; flex-shrink:0;
      }
      .ra-stat-icon--purple { background:var(--c-accent-bg); color:var(--c-accent); }
      .ra-stat-icon--green  { background:var(--c-green-bg);  color:var(--c-green); }
      .ra-stat-icon--amber  { background:var(--c-amber-bg);  color:var(--c-amber); }
      .ra-stat-icon--blue   { background:var(--c-blue-bg);   color:var(--c-blue); }
      .ra-stat-label { font-size:12px; font-weight:500; color:var(--c-muted); margin-bottom:4px; }
      .ra-stat-value { font-size:30px; font-weight:700; letter-spacing:-.03em; color:var(--c-text); line-height:1; }
      .ra-stat-value--green { color:var(--c-green); }
      .ra-stat-value--amber { color:var(--c-amber); }
      .ra-stat-value--blue  { color:var(--c-blue); }

      .ra-stat-progress { height:4px; background:var(--c-border); border-radius:99px; overflow:hidden; }
      .ra-stat-progress-fill { height:100%; border-radius:99px; transition:width .8s cubic-bezier(.4,0,.2,1); }
      .ra-stat-progress-fill--purple { background:linear-gradient(90deg,var(--c-accent),#9b8df7); }
      .ra-stat-progress-fill--green  { background:linear-gradient(90deg,var(--c-green),#3deaa0); }
      .ra-stat-progress-fill--amber  { background:linear-gradient(90deg,var(--c-amber),#fbbf24); }
      .ra-stat-progress-fill--blue   { background:linear-gradient(90deg,var(--c-blue),#60a5fa); }

      /* ── Panel ── */
      .ra-panel {
        background:var(--c-surface); border:1.5px solid var(--c-border);
        border-radius:24px; overflow:hidden; box-shadow:var(--c-shadow-sm);
        display:flex; flex-direction:column;
        animation:ra-fadeUp .3s .22s ease both;
      }

      /* ── Toolbar ── */
      .ra-toolbar {
        display:flex; align-items:center; gap:10px; padding:14px 18px;
        border-bottom:1.5px solid var(--c-border); flex-wrap:wrap;
      }
      .ra-search-wrap { position:relative; flex:1; min-width:200px; max-width:340px; }
      .ra-search-ico { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--c-muted); pointer-events:none; }
      .ra-search-input {
        width:100%; padding:9px 34px 9px 36px; border-radius:12px;
        border:1.5px solid var(--c-border); background:var(--c-bg);
        font-size:13.5px; color:var(--c-text); outline:none; transition:all .15s;
        font-family:'DM Sans',sans-serif;
      }
      .ra-search-input::placeholder { color:var(--c-muted); }
      .ra-search-input:focus { border-color:var(--c-accent); box-shadow:0 0 0 3px rgba(91,76,232,.1); background:#fff; }
      .ra-search-clear {
        position:absolute; right:9px; top:50%; transform:translateY(-50%);
        background:none; border:none; color:var(--c-muted); cursor:pointer; padding:3px;
        border-radius:6px; display:flex;
      }
      .ra-search-clear:hover { color:var(--c-text); }

      /* Filter Pills */
      .ra-filter-pills { display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
      .ra-pill {
        display:inline-flex; align-items:center; gap:6px; padding:6px 12px;
        border-radius:99px; border:1.5px solid var(--c-border); background:var(--c-surface);
        font-size:12.5px; font-weight:500; color:var(--c-muted);
        cursor:pointer; transition:all .15s; user-select:none; font-family:'DM Sans',sans-serif;
      }
      .ra-pill:hover { border-color:var(--c-border2); color:var(--c-text); }
      .ra-pill--green  { border-color:var(--c-green);  background:var(--c-green-bg);  color:var(--c-green); }
      .ra-pill--amber  { border-color:var(--c-amber);  background:var(--c-amber-bg);  color:var(--c-amber); }
      .ra-pill--red    { border-color:var(--c-red);    background:var(--c-red-bg);    color:var(--c-red); }
      .ra-pill--slate  { border-color:var(--c-border2); background:var(--c-bg);        color:var(--c-text); }
      .ra-pill-dot { width:7px; height:7px; border-radius:50%; display:inline-block; }
      .ra-pill-dot--green  { background:var(--c-green); }
      .ra-pill-dot--amber  { background:var(--c-amber); }
      .ra-pill-dot--red    { background:var(--c-red); }
      .ra-pill-dot--slate  { background:var(--c-muted); }
      .ra-pill-count {
        font-size:10px; font-weight:700; padding:1px 5px; border-radius:99px;
        background:rgba(0,0,0,.08); min-width:18px; text-align:center;
      }

      /* Building filter */
      .ra-building-wrap { position:relative; }
      .ra-building-ico { position:absolute; left:10px; top:50%; transform:translateY(-50%); color:var(--c-muted); pointer-events:none; }
      .ra-building-input {
        padding:8px 12px 8px 28px; border-radius:12px;
        border:1.5px solid var(--c-border); background:var(--c-surface);
        font-size:13px; color:var(--c-text); outline:none; width:160px;
        transition:all .15s; font-family:'DM Sans',sans-serif;
      }
      .ra-building-input::placeholder { color:var(--c-muted); }
      .ra-building-input:focus { border-color:var(--c-accent); box-shadow:0 0 0 3px rgba(91,76,232,.1); }

      .ra-reset-btn {
        display:inline-flex; align-items:center; gap:5px; padding:6px 12px;
        border-radius:99px; border:none; background:none;
        font-size:12px; font-weight:500; color:var(--c-muted);
        cursor:pointer; transition:color .15s; font-family:'DM Sans',sans-serif;
      }
      .ra-reset-btn:hover { color:var(--c-red); }

      .ra-result-count { margin-left:auto; display:flex; align-items:baseline; gap:4px; }
      .ra-result-num   { font-size:20px; font-weight:700; color:var(--c-text); font-family:'Syne',sans-serif; }
      .ra-result-label { font-size:12px; color:var(--c-muted); }

      /* ── Loading / Empty ── */
      .ra-loading { display:flex; flex-direction:column; align-items:center; justify-content:center; padding:80px; gap:14px; }
      .ra-spinner { width:38px; height:38px; border:3px solid var(--c-border); border-top-color:var(--c-accent); border-radius:50%; animation:ra-spin .8s linear infinite; }
      .ra-loading-text { font-size:14px; color:var(--c-muted); }

      .ra-empty { display:flex; flex-direction:column; align-items:center; justify-content:center; padding:80px 20px; text-align:center; gap:10px; }
      .ra-empty-icon { width:78px; height:78px; border-radius:22px; background:var(--c-accent-bg); color:var(--c-accent); display:flex; align-items:center; justify-content:center; margin-bottom:6px; border:1.5px solid var(--c-border); }
      .ra-empty-title { font-size:20px; font-weight:700; color:var(--c-text); margin:0; }
      .ra-empty-body  { font-size:14px; color:var(--c-muted); max-width:340px; }

      /* ── Table ── */
      .ra-table-wrap { overflow-x:auto; }
      .ra-table { width:100%; border-collapse:collapse; min-width:1100px; }

      .ra-th {
        padding:11px 18px; text-align:left; font-size:11px; font-weight:600;
        letter-spacing:.08em; text-transform:uppercase; color:var(--c-muted);
        border-bottom:1.5px solid var(--c-border); background:#faf9ff; white-space:nowrap;
      }
      .ra-th--right { text-align:right; }

      .ra-td { padding:13px 18px; border-bottom:1px solid var(--c-border); vertical-align:middle; }
      .ra-td--right { text-align:right; }
      .ra-row { animation:ra-fadeUp .2s ease both; }
      .ra-row:last-child .ra-td { border-bottom:none; }

      /* Agreement cell */
      .ra-agreement-cell { display:flex; align-items:center; gap:10px; }
      .ra-agreement-icon {
        width:36px; height:36px; border-radius:11px; flex-shrink:0;
        display:flex; align-items:center; justify-content:center;
        border:1.5px solid var(--c-border);
      }
      .ra-agreement-icon--rental      { background:var(--c-accent-bg); color:var(--c-accent); }
      .ra-agreement-icon--commercial  { background:var(--c-blue-bg);   color:var(--c-blue); }
      .ra-agreement-icon--residential { background:var(--c-green-bg);  color:var(--c-green); }
      .ra-agreement-num  { font-size:13.5px; font-weight:700; color:var(--c-text); white-space:nowrap; }
      .ra-type-badge {
        display:inline-block; font-size:10.5px; font-weight:600;
        padding:2px 8px; border-radius:6px; margin-top:3px; text-transform:capitalize;
      }
      .ra-type-badge--rental      { background:var(--c-accent-bg); color:var(--c-accent); }
      .ra-type-badge--commercial  { background:var(--c-blue-bg);   color:var(--c-blue); }
      .ra-type-badge--residential { background:var(--c-green-bg);  color:var(--c-green); }

      /* Tenant cell */
      .ra-tenant-cell { display:flex; align-items:center; gap:10px; }
      .ra-tenant-avatar {
        width:36px; height:36px; border-radius:12px; flex-shrink:0;
        display:flex; align-items:center; justify-content:center;
        font-size:12px; font-weight:700; color:#fff; font-family:'Syne',sans-serif;
      }
      .ra-tenant-name  { font-size:13.5px; font-weight:600; color:var(--c-text); white-space:nowrap; }
      .ra-tenant-phone { font-size:11.5px; color:var(--c-muted); margin-top:2px; }

      /* Property cell */
      .ra-property-cell { }
      .ra-building-name { font-size:13.5px; font-weight:600; color:var(--c-text); }
      .ra-unit-row { display:flex; align-items:center; gap:6px; margin-top:4px; }
      .ra-unit-badge {
        display:inline-flex; align-items:center; padding:2px 8px;
        border-radius:6px; font-size:11px; font-weight:700;
        background:var(--c-accent-bg); color:var(--c-accent);
        border:1px solid #ddd8f5;
      }
      .ra-floor-badge {
        display:inline-flex; align-items:center; gap:3px; padding:2px 8px;
        border-radius:6px; font-size:11px; font-weight:500;
        background:var(--c-bg); color:var(--c-muted); border:1px solid var(--c-border);
      }

      /* Rent cell */
      .ra-rent-cell { }
      .ra-rent-amount { font-size:15px; font-weight:700; color:var(--c-text); letter-spacing:-.01em; }
      .ra-rent-cycle  { font-size:11.5px; color:var(--c-muted); margin-top:2px; }

      /* Duration cell */
      .ra-duration-cell { }
      .ra-date-row { display:flex; align-items:center; gap:8px; }
      .ra-date-row + .ra-date-row { margin-top:3px; }
      .ra-date-label { font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:.06em; color:var(--c-muted); width:26px; flex-shrink:0; }
      .ra-date-val   { font-size:12.5px; font-weight:500; color:var(--c-text); }
      .ra-days-bar-wrap { display:flex; align-items:center; gap:7px; margin-top:7px; }
      .ra-days-bar-track { flex:1; height:4px; background:var(--c-border); border-radius:99px; overflow:hidden; }
      .ra-days-bar-fill  { height:100%; border-radius:99px; transition:width .6s ease; }
      .ra-days-remaining { font-size:10.5px; font-weight:700; white-space:nowrap; }

      /* Status */
      .ra-status {
        display:inline-flex; align-items:center; gap:6px;
        padding:4px 10px; border-radius:99px; font-size:12px; font-weight:600;
        white-space:nowrap; text-transform:capitalize;
      }
      .ra-status--active      { background:var(--c-green-bg); color:var(--c-green); }
      .ra-status--draft       { background:var(--c-amber-bg); color:var(--c-amber); }
      .ra-status--terminated  { background:var(--c-red-bg);   color:var(--c-red); }
      .ra-status--expired     { background:var(--c-bg);       color:var(--c-muted); }
      .ra-status-dot { width:7px; height:7px; border-radius:50%; background:currentColor; display:inline-block; }
      .ra-status-dot--pulse { animation:ra-pulse 2s infinite; }

      /* Action buttons */
      .ra-actions { display:flex; align-items:center; justify-content:flex-end; gap:5px; }
      .ra-action-btn {
        width:32px; height:32px; border-radius:9px;
        border:1.5px solid var(--c-border); background:var(--c-surface);
        color:var(--c-muted); display:flex; align-items:center; justify-content:center;
        cursor:pointer; transition:all .15s; text-decoration:none; flex-shrink:0;
      }
      .ra-action-btn:hover { border-color:var(--c-accent); color:var(--c-accent); background:var(--c-accent-bg); }
      .ra-action-btn--warn:hover   { border-color:var(--c-amber); color:var(--c-amber); background:var(--c-amber-bg); }
      .ra-action-btn--danger:hover { border-color:var(--c-red);   color:var(--c-red);   background:var(--c-red-bg); }

      /* Table Footer */
      .ra-table-footer {
        padding:11px 18px; border-top:1.5px solid var(--c-border);
        font-size:12px; color:var(--c-muted); background:#faf9ff;
        display:flex; align-items:center; justify-content:space-between;
      }
      .ra-table-footer strong { color:var(--c-text); }
      .ra-footer-revenue {
        display:flex; align-items:center; gap:5px;
        color:var(--c-blue); font-weight:600; font-size:12px;
      }

      /* ── Confirm Modal ── */
      .ra-backdrop {
        position:fixed; inset:0; z-index:1000;
        background:rgba(20,16,48,.55); backdrop-filter:blur(6px);
        display:flex; align-items:center; justify-content:center; padding:20px;
      }
      .ra-modal {
        width:100%; max-width:440px; background:var(--c-surface);
        border-radius:28px; padding:32px; box-shadow:var(--c-shadow-lg);
        display:flex; flex-direction:column; align-items:center; text-align:center; gap:14px;
      }
      .ra-modal-icon {
        width:68px; height:68px; border-radius:22px;
        display:flex; align-items:center; justify-content:center;
        border:2px solid transparent;
      }
      .ra-modal-icon--warn   { background:var(--c-amber-bg); color:var(--c-amber); border-color:#fde68a; }
      .ra-modal-icon--danger { background:var(--c-red-bg);   color:var(--c-red);   border-color:#fecaca; }
      .ra-modal-title { font-size:22px; font-weight:800; color:var(--c-text); margin:0; letter-spacing:-.02em; }
      .ra-modal-body  { font-size:14px; color:var(--c-muted); line-height:1.6; max-width:340px; }
      .ra-modal-info-card {
        width:100%; background:var(--c-bg); border:1.5px solid var(--c-border);
        border-radius:16px; padding:14px 16px; text-align:left;
      }
      .ra-modal-info-row { display:flex; justify-content:space-between; align-items:center; padding:4px 0; }
      .ra-modal-info-row + .ra-modal-info-row { border-top:1px solid var(--c-border); }
      .ra-modal-info-label { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.07em; color:var(--c-muted); }
      .ra-modal-info-val   { font-size:13px; font-weight:600; color:var(--c-text); }
      .ra-modal-actions { display:flex; gap:10px; margin-top:4px; }
      .ra-modal-confirm-btn {
        display:inline-flex; align-items:center; gap:7px; padding:11px 22px;
        border-radius:12px; border:none; font-size:14px; font-weight:700;
        cursor:pointer; transition:all .15s; font-family:'Syne',sans-serif;
      }
      .ra-modal-confirm-btn--warn   { background:var(--c-amber); color:#fff; box-shadow:0 4px 14px rgba(217,119,6,.35); }
      .ra-modal-confirm-btn--warn:hover  { background:#b45309; }
      .ra-modal-confirm-btn--danger { background:var(--c-red); color:#fff; box-shadow:0 4px 14px rgba(220,38,38,.35); }
      .ra-modal-confirm-btn--danger:hover { background:#b91c1c; }
      .ra-modal-confirm-btn:disabled { opacity:.6; cursor:not-allowed; }

      .ra-mini-spinner {
        width:15px; height:15px; border:2px solid rgba(255,255,255,.3);
        border-top-color:#fff; border-radius:50%; animation:ra-spin .7s linear infinite;
        display:inline-block;
      }

      /* ── Toast ── */
      .ra-toast {
        position:fixed; bottom:28px; right:28px; z-index:2000;
        display:flex; align-items:center; gap:10px; padding:13px 18px;
        border-radius:14px; font-size:14px; font-weight:500;
        box-shadow:var(--c-shadow-lg); min-width:240px;
        font-family:'DM Sans',sans-serif;
      }
      .ra-toast--success { background:var(--c-green); color:#fff; }
      .ra-toast--error   { background:var(--c-red);   color:#fff; }
    </component>

  
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../../services/api'
import DashboardLayout from '../../layouts/DashboardLayout.vue'

/*──────── State ────────*/
const loading    = ref(false)
const agreements = ref([])
const filters    = ref({ search: '', status: '', building: '' })

const stats = ref({ total: 0, active: 0, draft: 0, monthlyRevenue: 0 })

/*──────── Status options for pills ────────*/
const statusOptions = [
  { value: 'active',     label: 'Active',     color: 'green' },
  { value: 'draft',      label: 'Draft',      color: 'amber' },
  { value: 'terminated', label: 'Terminated', color: 'red'   },
  { value: 'expired',    label: 'Expired',    color: 'slate' },
]
const toggleStatus = (val) => {
  filters.value.status = filters.value.status === val ? '' : val
}
const statusCount = (val) => agreements.value.filter(a => a.status === val).length

/*──────── Avatar helpers ────────*/
const GRADIENTS = [
  'linear-gradient(135deg,#5b4ce8,#7c6cf5)',
  'linear-gradient(135deg,#12b374,#0ea572)',
  'linear-gradient(135deg,#2563eb,#3b82f6)',
  'linear-gradient(135deg,#d97706,#f59e0b)',
  'linear-gradient(135deg,#dc2626,#ef4444)',
  'linear-gradient(135deg,#7e22ce,#a855f7)',
]
const initials = (name) => {
  if (!name || name === 'N/A') return '?'
  return name.split(' ').slice(0,2).map(w => w[0]).join('').toUpperCase()
}
const avatarGradient = (name) => {
  if (!name) return GRADIENTS[0]
  let h = 0
  for (let i = 0; i < name.length; i++) h = name.charCodeAt(i) + ((h << 5) - h)
  return GRADIENTS[Math.abs(h) % GRADIENTS.length]
}

/*──────── Duration / progress helpers ────────*/
const parseDuration = (startStr, endStr) => {
  if (!startStr || !endStr || startStr === 'N/A' || endStr === 'N/A') return { daysRemaining: null, progressPct: 0, progressColor: '#12b374' }
  const now   = Date.now()
  const start = new Date(startStr).getTime()
  const end   = new Date(endStr).getTime()
  if (isNaN(start) || isNaN(end)) return { daysRemaining: null, progressPct: 0, progressColor: '#12b374' }
  const total   = end - start
  const elapsed = now - start
  const remain  = Math.max(0, Math.ceil((end - now) / 86400000))
  const pct     = total > 0 ? Math.min(100, (elapsed / total) * 100) : 0
  // color: green → amber → red
  const color = pct < 60 ? '#12b374' : pct < 85 ? '#d97706' : '#dc2626'
  return { daysRemaining: remain, progressPct: pct, progressColor: color }
}

/*──────── Load & map agreements ────────*/
const loadAgreements = async () => {
  loading.value = true
  try {
    const response = await api.get('/rental-agreements')
    agreements.value = (response.data.data || []).map(item => {
      const start = item.dates?.start_date || 'N/A'
      const end   = item.dates?.end_date   || 'N/A'
      const status = item.status?.value || 'draft'
      const { daysRemaining, progressPct, progressColor } = parseDuration(start, end)
      return {
        id:               item.id,
        agreement_number: item.agreement_number || 'N/A',
        agreement_type:   item.agreement_type   || 'rental',
        tenant_name:      item.tenant?.display_name || 'N/A',
        tenant_phone:     item.tenant?.phone        || 'N/A',
        building_name:    item.apartment?.building?.name || 'N/A',
        unit_number:      item.apartment?.unit_number    || 'N/A',
        floor:            item.apartment?.floor          || 'N/A',
        monthly_rent:     item.financials?.monthly_rent  || 0,
        start_date:       start,
        end_date:         end,
        status,
        status_label:     item.status?.label || 'Draft',
        daysRemaining:    status === 'active' ? daysRemaining : null,
        progressPct:      status === 'active' ? progressPct   : 0,
        progressColor:    status === 'active' ? progressColor : '#12b374',
      }
    })
    calculateStats()
  } catch (err) {
    console.error('Failed to load agreements:', err)
    showToast('Failed to load agreements', 'error')
  } finally {
    loading.value = false
  }
}

/*──────── Stats ────────*/
const calculateStats = () => {
  stats.value.total  = agreements.value.length
  stats.value.active = agreements.value.filter(a => a.status === 'active').length
  stats.value.draft  = agreements.value.filter(a => a.status === 'draft').length
  stats.value.monthlyRevenue = agreements.value
    .filter(a => a.status === 'active')
    .reduce((sum, a) => sum + Number(a.monthly_rent || 0), 0)
    .toLocaleString()
}

/*──────── Computed filter ────────*/
const filteredAgreements = computed(() => {
  const q    = filters.value.search.toLowerCase()
  const stat = filters.value.status
  const bldg = filters.value.building.toLowerCase()
  return agreements.value.filter(a => {
    const matchSearch = !q || [a.agreement_number, a.tenant_name, a.building_name, a.unit_number]
      .some(v => v?.toLowerCase().includes(q))
    const matchStatus   = !stat || a.status === stat
    const matchBuilding = !bldg || a.building_name?.toLowerCase().includes(bldg)
    return matchSearch && matchStatus && matchBuilding
  })
})

const resetFilters = () => { filters.value = { search: '', status: '', building: '' } }

/*──────── Confirm Modal ────────*/
const confirmModal = ref({ show: false, type: '', agreement: null, loading: false })

const promptTerminate = (agreement) => {
  confirmModal.value = { show: true, type: 'terminate', agreement, loading: false }
}
const promptDelete = (agreement) => {
  confirmModal.value = { show: true, type: 'delete', agreement, loading: false }
}
const closeConfirm = () => {
  if (!confirmModal.value.loading) confirmModal.value.show = false
}

const executeConfirm = async () => {
  confirmModal.value.loading = true
  const { type, agreement } = confirmModal.value
  try {
    if (type === 'terminate') {
      await api.post(`/rental-agreements/${agreement.id}/terminate`)
      showToast(`Agreement ${agreement.agreement_number} terminated`, 'success')
    } else {
      await api.delete(`/rental-agreements/${agreement.id}`)
      showToast(`Draft ${agreement.agreement_number} deleted`, 'success')
    }
    confirmModal.value.show = false
    await loadAgreements()
  } catch (err) {
    console.error(err)
    showToast(`Failed to ${type} agreement`, 'error')
  } finally {
    confirmModal.value.loading = false
  }
}

/*──────── Toast ────────*/
const toast = ref({ show: false, message: '', type: 'success' })
let toastTimer = null
const showToast = (message, type = 'success') => {
  clearTimeout(toastTimer)
  toast.value = { show: true, message, type }
  toastTimer  = setTimeout(() => { toast.value.show = false }, 3500)
}

/*──────── Lifecycle ────────*/
onMounted(() => loadAgreements())
</script>