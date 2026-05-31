<template>
  
    <div class="mr-root">

      <!-- Fonts + Animations -->
      <component :is="'style'">
        @import url('https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap');
        .mr-root, .mr-root * { box-sizing: border-box; font-family: 'DM Sans', sans-serif; }
        .mr-display { font-family: 'Syne', sans-serif; }

        @keyframes mr-fadeUp   { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:translateY(0); } }
        @keyframes mr-spin     { to { transform:rotate(360deg); } }
        @keyframes mr-pulse    { 0%,100%{opacity:1;} 50%{opacity:.3;} }
        @keyframes mr-modalIn  { from{opacity:0;transform:scale(.95) translateY(12px);} to{opacity:1;transform:scale(1) translateY(0);} }
        @keyframes mr-backdropIn { from{opacity:0;} to{opacity:1;} }
        @keyframes mr-barFill  { from{width:0;} to{width:100%;} }
        @keyframes mr-toast    { 0%{opacity:0;transform:translateY(14px);} 15%,85%{opacity:1;transform:translateY(0);} 100%{opacity:0;transform:translateY(14px);} }

        .mr-stat:nth-child(1) { animation-delay:.00s; }
        .mr-stat:nth-child(2) { animation-delay:.06s; }
        .mr-stat:nth-child(3) { animation-delay:.12s; }
        .mr-stat:nth-child(4) { animation-delay:.18s; }

        .mr-timeline-item:not(:last-child)::before {
          content:'';
          position:absolute;
          left:5px; top:18px;
          width:2px; bottom:-20px;
          background: var(--c-border);
        }
      </component>

      <!-- ════════════════ LOADING ════════════════ -->
      <div v-if="loading" class="mr-loading">
        <div class="mr-spinner"></div>
        <p class="mr-loading-text">Loading meter reading…</p>
      </div>

      <!-- ════════════════ CONTENT ════════════════ -->
      <div v-else-if="reading" class="mr-content">

        <!-- ── PAGE HEADER ── -->
        <div class="mr-header">
          <div class="mr-header-left">

            <RouterLink to="/meter-readings" class="mr-back-link">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round"><polyline points="15 18 9 12 15 6"/></svg>
              Meter Readings
            </RouterLink>

            <div class="mr-eyebrow">Reading Detail</div>

            <div class="mr-title-row">
              <h1 class="mr-title mr-display">{{ reading.meter.meter_number }}</h1>
              <span :class="['mr-status-badge', `mr-status-badge--${reading.status.value}`]">
                <span class="mr-status-dot" :class="reading.status.value === 'approved' && 'mr-status-dot--pulse'"></span>
                {{ reading.status.label }}
              </span>
              <span class="mr-utility-badge">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                {{ reading.meter.utility_type.label }}
              </span>
            </div>

            <p class="mr-subtitle">Operational utility reading review and lifecycle management workspace</p>

            <!-- Location strip -->
            <div class="mr-location-strip">
              <div class="mr-location-item">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 9 12 2 21 9"/><path d="M9 22V12h6v10"/><path d="M3 9v13h18V9"/></svg>
                <span class="mr-location-label">Building</span>
                <span class="mr-location-val">{{ reading.building?.name || 'N/A' }}</span>
              </div>
              <span class="mr-location-sep">·</span>
              <div class="mr-location-item">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
                <span class="mr-location-label">Unit</span>
                <span class="mr-location-val">{{ reading.apartment?.unit_number || 'N/A' }}</span>
              </div>
              <span class="mr-location-sep">·</span>
              <div class="mr-location-item">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <span class="mr-location-label">Date</span>
                <span class="mr-location-val">{{ reading.reading.reading_date }}</span>
              </div>
            </div>

          </div>

          <!-- Header Actions -->
          <div class="mr-header-actions">
            <RouterLink to="/meter-readings" class="mr-btn-ghost">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><polyline points="15 18 9 12 15 6"/></svg>
              Back
            </RouterLink>

            <button
              v-if="reading.controls.can_approve"
              @click="approveReading"
              :disabled="approving"
              class="mr-btn-approve"
            >
              <span v-if="approving" class="mr-mini-spinner"></span>
              <svg v-else width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><polyline points="20 6 9 17 4 12"/></svg>
              {{ approving ? 'Approving…' : 'Approve Reading' }}
            </button>

            <button
              @click="showRejectModal = true"
              class="mr-btn-reject"
            >
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
              Reject
            </button>
          </div>
        </div>

        <!-- ── KPI CARDS ── -->
        <div class="mr-stats-grid">

          <div class="mr-stat">
            <div class="mr-stat-inner">
              <div class="mr-stat-icon mr-stat-icon--slate">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
              </div>
              <div>
                <p class="mr-stat-label">Previous Reading</p>
                <p class="mr-stat-value mr-display">{{ reading.reading.previous_reading }}</p>
              </div>
            </div>
            <p class="mr-stat-unit">{{ reading.meter.measurement_unit }}</p>
          </div>

          <div class="mr-stat">
            <div class="mr-stat-inner">
              <div class="mr-stat-icon mr-stat-icon--blue">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              </div>
              <div>
                <p class="mr-stat-label">Current Reading</p>
                <p class="mr-stat-value mr-stat-value--blue mr-display">{{ reading.reading.current_reading }}</p>
              </div>
            </div>
            <p class="mr-stat-unit">{{ reading.meter.measurement_unit }}</p>
          </div>

          <div class="mr-stat">
            <div class="mr-stat-inner">
              <div class="mr-stat-icon mr-stat-icon--green">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
              </div>
              <div>
                <p class="mr-stat-label">Consumption</p>
                <p class="mr-stat-value mr-stat-value--green mr-display">{{ reading.reading.consumption }}</p>
              </div>
            </div>
            <p class="mr-stat-unit">{{ reading.meter.measurement_unit }}</p>
          </div>

          <div class="mr-stat" :class="Number(variance) > 20 ? 'mr-stat--warn' : Number(variance) < 0 ? 'mr-stat--danger' : ''">
            <div class="mr-stat-inner">
              <div :class="['mr-stat-icon', Number(variance) > 20 ? 'mr-stat-icon--amber' : Number(variance) < 0 ? 'mr-stat-icon--red' : 'mr-stat-icon--purple']">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
              </div>
              <div>
                <p class="mr-stat-label">Variance</p>
                <p :class="['mr-stat-value mr-display', Number(variance) > 20 ? 'mr-stat-value--amber' : Number(variance) < 0 ? 'mr-stat-value--red' : '']">{{ variance }}%</p>
              </div>
            </div>
            <p class="mr-stat-unit">vs previous period</p>
          </div>

        </div>

        <!-- ── MAIN GRID ── -->
        <div class="mr-main-grid">

          <!-- ═══ LEFT COLUMN ═══ -->
          <div class="mr-left-col">

            <!-- Reading Information -->
            <div class="mr-card">
              <div class="mr-card-header">
                <div class="mr-card-icon mr-card-icon--purple">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                </div>
                <div>
                  <h2 class="mr-card-title mr-display">Reading Information</h2>
                  <p class="mr-card-subtitle">Capture metadata and operational context</p>
                </div>
                <span class="mr-reading-type-badge">{{ reading.reading_type.label }}</span>
              </div>

              <div class="mr-info-grid">
                <div class="mr-info-field">
                  <p class="mr-info-label">Reading Source</p>
                  <p class="mr-info-value">{{ reading.reading_source.label }}</p>
                </div>
                <div class="mr-info-field">
                  <p class="mr-info-label">Reader</p>
                  <p class="mr-info-value">{{ reading.reader?.name || 'N/A' }}</p>
                </div>
                <div class="mr-info-field">
                  <p class="mr-info-label">Reading Date</p>
                  <p class="mr-info-value">{{ reading.reading.reading_date }}</p>
                </div>
                <div class="mr-info-field">
                  <p class="mr-info-label">Reading Type</p>
                  <p class="mr-info-value">{{ reading.reading_type.label }}</p>
                </div>
                <div class="mr-info-field">
                  <p class="mr-info-label">Created At</p>
                  <p class="mr-info-value">{{ reading.audit.created_at }}</p>
                </div>
                <div class="mr-info-field">
                  <p class="mr-info-label">Last Updated</p>
                  <p class="mr-info-value">{{ reading.audit.updated_at }}</p>
                </div>
              </div>

              <!-- Consumption visual bar -->
              <div class="mr-consumption-bar-wrap">
                <div class="mr-consumption-bar-header">
                  <span class="mr-consumption-bar-label">Consumption Gauge</span>
                  <span class="mr-consumption-bar-val">{{ reading.reading.consumption }} {{ reading.meter.measurement_unit }}</span>
                </div>
                <div class="mr-consumption-track">
                  <div
                    class="mr-consumption-fill"
                    :style="{
                      width: Math.min(100, (Number(reading.reading.consumption) / Math.max(Number(reading.reading.current_reading), 1)) * 100) + '%',
                      background: Number(variance) > 20 ? 'linear-gradient(90deg,#d97706,#f59e0b)' : 'linear-gradient(90deg,#12b374,#3deaa0)'
                    }"
                  ></div>
                </div>
              </div>
            </div>

            <!-- Operational Notes -->
            <div class="mr-card">
              <div class="mr-card-header">
                <div class="mr-card-icon mr-card-icon--slate">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                </div>
                <div>
                  <h2 class="mr-card-title mr-display">Operational Notes</h2>
                  <p class="mr-card-subtitle">Internal remarks and field observations</p>
                </div>
              </div>
              <div class="mr-notes-body">
                {{ reading.notes || 'No operational notes available for this reading.' }}
              </div>
            </div>

            <!-- Evidence & Attachments -->
            <div class="mr-card">
              <div class="mr-card-header">
                <div class="mr-card-icon mr-card-icon--blue">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/></svg>
                </div>
                <div>
                  <h2 class="mr-card-title mr-display">Evidence &amp; Attachments</h2>
                  <p class="mr-card-subtitle">Supporting documentation and meter photos</p>
                </div>
              </div>

              <a
                v-if="reading.attachment_path"
                :href="reading.attachment_path"
                target="_blank"
                class="mr-attachment-link"
              >
                <div class="mr-attachment-icon">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <div>
                  <p class="mr-attachment-name">View Attachment</p>
                  <p class="mr-attachment-hint">Opens in new tab</p>
                </div>
                <svg class="mr-attachment-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
              </a>

              <div v-else class="mr-no-attachment">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/></svg>
                <p>No evidence attachment uploaded</p>
              </div>
            </div>

          </div>

          <!-- ═══ RIGHT COLUMN ═══ -->
          <div class="mr-right-col">

            <!-- Anomaly Intelligence -->
            <div class="mr-card">
              <div class="mr-card-header">
                <div :class="['mr-card-icon', reading.anomaly.detected ? 'mr-card-icon--red' : 'mr-card-icon--green']">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </div>
                <div>
                  <h2 class="mr-card-title mr-display">Anomaly Intelligence</h2>
                  <p class="mr-card-subtitle">AI-powered anomaly detection</p>
                </div>
              </div>

              <!-- Anomaly Detected -->
              <div v-if="reading.anomaly.detected" class="mr-anomaly-detected">
                <div class="mr-anomaly-header">
                  <span class="mr-anomaly-pulse"></span>
                  <strong>Anomaly Detected</strong>
                </div>
                <p class="mr-anomaly-reason">{{ reading.anomaly.reason }}</p>
                <div class="mr-anomaly-footer">
                  <span class="mr-severity-label">Severity</span>
                  <span :class="['mr-severity-badge', `mr-severity-badge--${(reading.anomaly.severity || 'low').toLowerCase()}`]">
                    {{ reading.anomaly.severity }}
                  </span>
                </div>
              </div>

              <!-- All Clear -->
              <div v-else class="mr-anomaly-clear">
                <div class="mr-anomaly-clear-icon">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <div>
                  <p class="mr-anomaly-clear-title">No Anomalies Detected</p>
                  <p class="mr-anomaly-clear-body">Reading passed all operational validation checks</p>
                </div>
              </div>
            </div>

            <!-- Meter Intelligence -->
            <div class="mr-card">
              <div class="mr-card-header">
                <div class="mr-card-icon mr-card-icon--purple">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 010 14.14M4.93 4.93a10 10 0 000 14.14"/><path d="M15.54 8.46a5 5 0 010 7.07M8.46 8.46a5 5 0 000 7.07"/></svg>
                </div>
                <div>
                  <h2 class="mr-card-title mr-display">Meter Intelligence</h2>
                  <p class="mr-card-subtitle">Hardware and operational metadata</p>
                </div>
              </div>

              <div class="mr-meter-fields">

                <div class="mr-meter-field">
                  <div class="mr-meter-field-icon">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                  </div>
                  <div>
                    <p class="mr-info-label">Serial Number</p>
                    <p class="mr-info-value">{{ reading.meter.serial_number || 'N/A' }}</p>
                  </div>
                </div>

                <div class="mr-meter-field">
                  <div class="mr-meter-field-icon">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                  </div>
                  <div>
                    <p class="mr-info-label">Utility Type</p>
                    <p class="mr-info-value">{{ reading.meter.utility_type.label }}</p>
                  </div>
                </div>

                <div class="mr-meter-field">
                  <div class="mr-meter-field-icon">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                  </div>
                  <div>
                    <p class="mr-info-label">Measurement Unit</p>
                    <p class="mr-info-value">{{ reading.meter.measurement_unit }}</p>
                  </div>
                </div>

                <div class="mr-meter-field">
                  <div class="mr-meter-field-icon">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                  </div>
                  <div>
                    <p class="mr-info-label">Meter Status</p>
                    <span class="mr-meter-status-badge">{{ reading.meter.status }}</span>
                  </div>
                </div>

              </div>
            </div>

            <!-- Approval Timeline -->
            <div class="mr-card">
              <div class="mr-card-header">
                <div class="mr-card-icon mr-card-icon--amber">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div>
                  <h2 class="mr-card-title mr-display">Approval Timeline</h2>
                  <p class="mr-card-subtitle">Lifecycle audit trail</p>
                </div>
              </div>

              <div class="mr-timeline">

                <!-- Captured -->
                <div class="mr-timeline-item">
                  <div class="mr-timeline-dot mr-timeline-dot--blue"></div>
                  <div class="mr-timeline-body">
                    <p class="mr-timeline-title">Reading Captured</p>
                    <p class="mr-timeline-meta">{{ reading.audit.created_at }}</p>
                    <p class="mr-timeline-meta">By {{ reading.reader?.name || 'System' }}</p>
                  </div>
                </div>

                <!-- Approved -->
                <div v-if="reading.approval?.approved_at" class="mr-timeline-item">
                  <div class="mr-timeline-dot mr-timeline-dot--green"></div>
                  <div class="mr-timeline-body">
                    <p class="mr-timeline-title">Reading Approved</p>
                    <p class="mr-timeline-meta">{{ reading.approval.approved_at }}</p>
                    <p class="mr-timeline-meta">By {{ reading.approval.approved_by?.name }}</p>
                  </div>
                </div>

                <!-- Pending -->
                <div v-if="reading.status.value === 'draft' || reading.status.value === 'verified'" class="mr-timeline-item mr-timeline-item--pending">
                  <div class="mr-timeline-dot mr-timeline-dot--muted"></div>
                  <div class="mr-timeline-body">
                    <p class="mr-timeline-title mr-timeline-title--muted">Awaiting Approval</p>
                    <p class="mr-timeline-meta">Not yet processed</p>
                  </div>
                </div>

                <!-- Rejected -->
                <div v-if="reading.status.value === 'rejected'" class="mr-timeline-item">
                  <div class="mr-timeline-dot mr-timeline-dot--red"></div>
                  <div class="mr-timeline-body">
                    <p class="mr-timeline-title">Reading Rejected</p>
                    <p class="mr-timeline-meta">{{ reading.audit.updated_at }}</p>
                  </div>
                </div>

              </div>
            </div>

          </div>
        </div>
      </div>

      <!-- ════════════════ REJECT MODAL ════════════════ -->
      <Teleport to="body">
        <Transition name="mr-backdrop-fade">
          <div v-if="showRejectModal" class="mr-backdrop" @click.self="showRejectModal = false">
            <div class="mr-modal">

              <!-- Modal Header -->
              <div class="mr-modal-header">
                <div class="mr-modal-icon">
                  <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                </div>
                <div>
                  <h2 class="mr-modal-title mr-display">Reject Reading</h2>
                  <p class="mr-modal-subtitle">Provide a reason for audit and workflow traceability</p>
                </div>
                <button @click="showRejectModal = false" class="mr-modal-close">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
              </div>

              <!-- Reading Quick Info -->
              <div class="mr-modal-info-strip">
                <div class="mr-modal-info-item">
                  <span class="mr-modal-info-label">Meter</span>
                  <span class="mr-modal-info-val">{{ reading?.meter.meter_number }}</span>
                </div>
                <div class="mr-modal-info-item">
                  <span class="mr-modal-info-label">Unit</span>
                  <span class="mr-modal-info-val">{{ reading?.apartment?.unit_number || 'N/A' }}</span>
                </div>
                <div class="mr-modal-info-item">
                  <span class="mr-modal-info-label">Reading</span>
                  <span class="mr-modal-info-val">{{ reading?.reading.current_reading }} {{ reading?.meter.measurement_unit }}</span>
                </div>
              </div>

              <!-- Reason Textarea -->
              <div class="mr-modal-body">
                <label class="mr-textarea-label">
                  Rejection Reason <span class="mr-req">*</span>
                </label>
                <textarea
                  v-model="rejectionReason"
                  rows="5"
                  class="mr-textarea"
                  placeholder="Describe the reason for rejection in detail — this will be logged in the audit trail and communicated to the field team…"
                ></textarea>
                <p class="mr-textarea-hint">Minimum 10 characters required for audit compliance</p>
              </div>

              <!-- Footer -->
              <div class="mr-modal-footer">
                <button @click="showRejectModal = false" class="mr-btn-ghost">Cancel</button>
                <button
                  @click="rejectReading"
                  :disabled="rejecting || rejectionReason.trim().length < 10"
                  class="mr-btn-reject-confirm"
                >
                  <span v-if="rejecting" class="mr-mini-spinner"></span>
                  <svg v-else width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                  {{ rejecting ? 'Rejecting…' : 'Confirm Rejection' }}
                </button>
              </div>

            </div>
          </div>
        </Transition>
      </Teleport>

      <!-- ════════════════ TOAST ════════════════ -->
      <Teleport to="body">
        <Transition name="mr-toast-fade">
          <div v-if="toast.show" :class="['mr-toast', `mr-toast--${toast.type}`]">
            <svg v-if="toast.type === 'success'" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            {{ toast.message }}
          </div>
        </Transition>
      </Teleport>

    </div>

    <!-- ═══════════════════ STYLES ═══════════════════ -->
    <component :is="'style'" scoped>
      .mr-root {
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
        --c-shadow-lg: 0 20px 60px rgba(30,20,80,.18), 0 4px 16px rgba(30,20,80,.08);
        min-height: 100%;
        padding: 28px;
        background: var(--c-bg);
        color: var(--c-text);
        animation: mr-fadeUp .25s ease both;
      }

      /* ── Loading ── */
      .mr-loading { display:flex; flex-direction:column; align-items:center; justify-content:center; min-height:60vh; gap:16px; }
      .mr-spinner { width:42px; height:42px; border:3px solid var(--c-border); border-top-color:var(--c-accent); border-radius:50%; animation:mr-spin .8s linear infinite; }
      .mr-loading-text { font-size:14px; color:var(--c-muted); }

      /* ── Header ── */
      .mr-header { display:flex; align-items:flex-start; justify-content:space-between; gap:20px; margin-bottom:24px; flex-wrap:wrap; }
      .mr-header-left { flex:1; min-width:0; }

      .mr-back-link {
        display:inline-flex; align-items:center; gap:5px; font-size:12.5px;
        color:var(--c-muted); text-decoration:none; margin-bottom:10px; transition:color .15s;
      }
      .mr-back-link:hover { color:var(--c-accent); }

      .mr-eyebrow { font-size:11px; font-weight:600; letter-spacing:.12em; text-transform:uppercase; color:var(--c-accent); margin-bottom:6px; }
      .mr-title-row { display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:6px; }
      .mr-title { font-size:30px; font-weight:800; letter-spacing:-.02em; color:var(--c-text); margin:0; line-height:1; }
      .mr-subtitle { font-size:13px; color:var(--c-muted); margin-bottom:14px; }

      /* Status badges */
      .mr-status-badge {
        display:inline-flex; align-items:center; gap:7px;
        padding:5px 14px; border-radius:99px; font-size:12px; font-weight:700; white-space:nowrap;
      }
      .mr-status-badge--approved  { background:var(--c-green-bg); color:var(--c-green); }
      .mr-status-badge--verified  { background:var(--c-blue-bg);  color:var(--c-blue); }
      .mr-status-badge--draft     { background:var(--c-amber-bg); color:var(--c-amber); }
      .mr-status-badge--rejected  { background:var(--c-red-bg);   color:var(--c-red); }
      .mr-status-dot { width:7px; height:7px; border-radius:50%; background:currentColor; display:inline-block; }
      .mr-status-dot--pulse { animation:mr-pulse 2s infinite; }

      .mr-utility-badge {
        display:inline-flex; align-items:center; gap:5px;
        padding:4px 12px; border-radius:99px;
        background:var(--c-accent-bg); color:var(--c-accent);
        font-size:12px; font-weight:600; border:1px solid #ddd8f5;
      }

      /* Location strip */
      .mr-location-strip { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
      .mr-location-item { display:flex; align-items:center; gap:5px; }
      .mr-location-label { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.07em; color:var(--c-muted); }
      .mr-location-val   { font-size:13px; font-weight:600; color:var(--c-text); }
      .mr-location-sep   { color:var(--c-border2); font-size:16px; }

      /* Header actions */
      .mr-header-actions { display:flex; align-items:center; gap:10px; flex-shrink:0; flex-wrap:wrap; }

      /* Buttons */
      .mr-btn-ghost {
        display:inline-flex; align-items:center; gap:7px; padding:10px 18px;
        border-radius:12px; border:1.5px solid var(--c-border2); background:var(--c-surface);
        color:var(--c-text); font-size:14px; font-weight:500; cursor:pointer;
        transition:all .15s; font-family:'DM Sans',sans-serif; text-decoration:none; white-space:nowrap;
      }
      .mr-btn-ghost:hover { border-color:var(--c-accent); background:var(--c-accent-bg); color:var(--c-accent); }
      .mr-btn-ghost:disabled { opacity:.5; cursor:not-allowed; }

      .mr-btn-approve {
        display:inline-flex; align-items:center; gap:7px; padding:10px 20px;
        border-radius:12px; border:none; background:var(--c-green); color:#fff;
        font-size:14px; font-weight:600; cursor:pointer; transition:all .15s;
        box-shadow:0 4px 14px rgba(18,179,116,.35); font-family:'DM Sans',sans-serif; white-space:nowrap;
      }
      .mr-btn-approve:hover:not(:disabled) { background:#0ea572; transform:translateY(-1px); box-shadow:0 6px 20px rgba(18,179,116,.45); }
      .mr-btn-approve:disabled { opacity:.6; cursor:not-allowed; }

      .mr-btn-reject {
        display:inline-flex; align-items:center; gap:7px; padding:10px 18px;
        border-radius:12px; border:1.5px solid #fecaca; background:var(--c-red-bg);
        color:var(--c-red); font-size:14px; font-weight:600; cursor:pointer;
        transition:all .15s; font-family:'DM Sans',sans-serif; white-space:nowrap;
      }
      .mr-btn-reject:hover { background:var(--c-red); color:#fff; border-color:var(--c-red); }

      .mr-mini-spinner {
        width:15px; height:15px; border:2px solid rgba(255,255,255,.3);
        border-top-color:#fff; border-radius:50%; animation:mr-spin .7s linear infinite; display:inline-block;
      }

      /* ── Stats ── */
      .mr-stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:20px; }
      @media (max-width:1100px) { .mr-stats-grid { grid-template-columns:repeat(2,1fr); } }
      @media (max-width:600px)  { .mr-stats-grid { grid-template-columns:1fr; } }

      .mr-stat {
        background:var(--c-surface); border:1.5px solid var(--c-border);
        border-radius:20px; padding:20px; box-shadow:var(--c-shadow-sm);
        animation:mr-fadeUp .28s ease both; transition:border-color .2s;
      }
      .mr-stat--warn   { border-color:#fde68a; background:#fffdf0; }
      .mr-stat--danger { border-color:#fecaca; background:var(--c-red-bg); }
      .mr-stat-inner { display:flex; align-items:center; gap:14px; margin-bottom:8px; }
      .mr-stat-icon {
        width:44px; height:44px; border-radius:12px;
        display:flex; align-items:center; justify-content:center; flex-shrink:0;
      }
      .mr-stat-icon--slate  { background:#f1f0fb; color:var(--c-muted); }
      .mr-stat-icon--blue   { background:var(--c-blue-bg);  color:var(--c-blue); }
      .mr-stat-icon--green  { background:var(--c-green-bg); color:var(--c-green); }
      .mr-stat-icon--purple { background:var(--c-accent-bg);color:var(--c-accent); }
      .mr-stat-icon--amber  { background:var(--c-amber-bg); color:var(--c-amber); }
      .mr-stat-icon--red    { background:var(--c-red-bg);   color:var(--c-red); }
      .mr-stat-label { font-size:12px; font-weight:500; color:var(--c-muted); margin-bottom:4px; }
      .mr-stat-value { font-size:30px; font-weight:700; letter-spacing:-.03em; color:var(--c-text); line-height:1; }
      .mr-stat-value--blue  { color:var(--c-blue); }
      .mr-stat-value--green { color:var(--c-green); }
      .mr-stat-value--amber { color:var(--c-amber); }
      .mr-stat-value--red   { color:var(--c-red); }
      .mr-stat-unit { font-size:11px; color:var(--c-muted); font-weight:500; }

      /* ── Main grid ── */
      .mr-main-grid { display:grid; grid-template-columns:1fr 340px; gap:18px; align-items:start; }
      @media (max-width:1100px) { .mr-main-grid { grid-template-columns:1fr; } }
      .mr-left-col  { display:flex; flex-direction:column; gap:18px; }
      .mr-right-col { display:flex; flex-direction:column; gap:18px; }

      /* ── Card ── */
      .mr-card {
        background:var(--c-surface); border:1.5px solid var(--c-border);
        border-radius:22px; overflow:hidden; box-shadow:var(--c-shadow-sm);
        animation:mr-fadeUp .28s ease both;
      }
      .mr-card-header {
        display:flex; align-items:center; gap:13px; padding:18px 22px;
        border-bottom:1.5px solid var(--c-border); background:#faf9ff;
      }
      .mr-card-icon {
        width:42px; height:42px; border-radius:13px; flex-shrink:0;
        display:flex; align-items:center; justify-content:center; border:1.5px solid var(--c-border);
      }
      .mr-card-icon--purple { background:var(--c-accent-bg); color:var(--c-accent); }
      .mr-card-icon--blue   { background:var(--c-blue-bg);  color:var(--c-blue); }
      .mr-card-icon--green  { background:var(--c-green-bg); color:var(--c-green); }
      .mr-card-icon--amber  { background:var(--c-amber-bg); color:var(--c-amber); }
      .mr-card-icon--red    { background:var(--c-red-bg);   color:var(--c-red); }
      .mr-card-icon--slate  { background:#f1f0fb; color:var(--c-muted); }
      .mr-card-title    { font-size:17px; font-weight:700; color:var(--c-text); margin:0; letter-spacing:-.01em; }
      .mr-card-subtitle { font-size:12px; color:var(--c-muted); margin-top:2px; }

      .mr-reading-type-badge {
        margin-left:auto; flex-shrink:0;
        padding:4px 12px; border-radius:99px;
        background:var(--c-accent-bg); color:var(--c-accent);
        font-size:11px; font-weight:600; border:1px solid #ddd8f5; text-transform:capitalize;
      }

      /* Info grid */
      .mr-info-grid { display:grid; grid-template-columns:1fr 1fr; gap:1px; background:var(--c-border); }
      .mr-info-field { background:var(--c-surface); padding:14px 22px; }
      .mr-info-label { font-size:10.5px; font-weight:600; text-transform:uppercase; letter-spacing:.08em; color:var(--c-muted); margin-bottom:5px; }
      .mr-info-value { font-size:14px; font-weight:600; color:var(--c-text); }

      /* Consumption bar */
      .mr-consumption-bar-wrap { padding:16px 22px; border-top:1px solid var(--c-border); }
      .mr-consumption-bar-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; }
      .mr-consumption-bar-label { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.07em; color:var(--c-muted); }
      .mr-consumption-bar-val   { font-size:12px; font-weight:700; color:var(--c-text); }
      .mr-consumption-track { height:7px; background:var(--c-border); border-radius:99px; overflow:hidden; }
      .mr-consumption-fill  { height:100%; border-radius:99px; transition:width .8s cubic-bezier(.4,0,.2,1); }

      /* Notes */
      .mr-notes-body {
        margin:18px 22px; padding:16px; border-radius:14px;
        background:var(--c-bg); border:1px solid var(--c-border);
        font-size:14px; color:var(--c-muted); line-height:1.75; min-height:80px;
      }

      /* Attachment */
      .mr-attachment-link {
        display:flex; align-items:center; gap:14px; margin:16px 22px; padding:14px 18px;
        border-radius:16px; border:1.5px solid #c7d2fe; background:var(--c-blue-bg);
        text-decoration:none; transition:all .15s;
      }
      .mr-attachment-link:hover { border-color:var(--c-blue); background:#e0ebff; }
      .mr-attachment-icon {
        width:42px; height:42px; border-radius:12px; background:#dbeafe; color:var(--c-blue);
        display:flex; align-items:center; justify-content:center; flex-shrink:0;
      }
      .mr-attachment-name { font-size:14px; font-weight:600; color:var(--c-blue); }
      .mr-attachment-hint { font-size:11.5px; color:var(--c-muted); margin-top:2px; }
      .mr-attachment-arrow { margin-left:auto; color:var(--c-blue); flex-shrink:0; }

      .mr-no-attachment {
        display:flex; flex-direction:column; align-items:center; gap:10px;
        margin:16px 22px; padding:28px; border-radius:16px;
        border:1.5px dashed var(--c-border); color:var(--c-muted);
        font-size:13px; text-align:center;
      }

      /* Anomaly */
      .mr-anomaly-detected {
        margin:0 22px 18px; padding:16px; border-radius:16px;
        border:1.5px solid #fecaca; background:var(--c-red-bg);
      }
      .mr-anomaly-header { display:flex; align-items:center; gap:9px; margin-bottom:10px; font-size:13.5px; font-weight:700; color:var(--c-red); }
      .mr-anomaly-pulse {
        width:10px; height:10px; border-radius:50%; background:var(--c-red); flex-shrink:0;
        animation:mr-pulse 1.5s infinite;
      }
      .mr-anomaly-reason { font-size:13px; color:#b91c1c; line-height:1.6; margin-bottom:12px; }
      .mr-anomaly-footer { display:flex; align-items:center; gap:8px; }
      .mr-severity-label { font-size:10.5px; font-weight:600; text-transform:uppercase; letter-spacing:.07em; color:var(--c-muted); }
      .mr-severity-badge {
        display:inline-block; padding:3px 10px; border-radius:8px;
        font-size:11px; font-weight:700; text-transform:capitalize;
      }
      .mr-severity-badge--high   { background:#fee2e2; color:var(--c-red); }
      .mr-severity-badge--medium { background:var(--c-amber-bg); color:var(--c-amber); }
      .mr-severity-badge--low    { background:var(--c-green-bg); color:var(--c-green); }

      .mr-anomaly-clear {
        display:flex; align-items:flex-start; gap:13px;
        margin:0 22px 18px; padding:16px; border-radius:16px;
        border:1.5px solid #a7f3d0; background:var(--c-green-bg);
      }
      .mr-anomaly-clear-icon {
        width:38px; height:38px; border-radius:12px; background:#bbf7d0; color:var(--c-green);
        display:flex; align-items:center; justify-content:center; flex-shrink:0;
      }
      .mr-anomaly-clear-title { font-size:13.5px; font-weight:700; color:var(--c-green); margin-bottom:3px; }
      .mr-anomaly-clear-body  { font-size:12px; color:#059669; line-height:1.5; }

      /* Meter fields */
      .mr-meter-fields { padding:4px 0 8px; }
      .mr-meter-field {
        display:flex; align-items:flex-start; gap:12px;
        padding:12px 22px; border-bottom:1px solid var(--c-border);
      }
      .mr-meter-field:last-child { border-bottom:none; }
      .mr-meter-field-icon {
        width:28px; height:28px; border-radius:9px; background:var(--c-bg);
        color:var(--c-muted); display:flex; align-items:center; justify-content:center;
        flex-shrink:0; margin-top:2px; border:1px solid var(--c-border);
      }
      .mr-meter-status-badge {
        display:inline-block; padding:2px 10px; border-radius:8px;
        background:var(--c-bg); color:var(--c-text); font-size:12px; font-weight:600;
        border:1px solid var(--c-border); text-transform:capitalize; margin-top:4px;
      }

      /* Timeline */
      .mr-timeline { padding:6px 22px 16px; display:flex; flex-direction:column; gap:20px; }
      .mr-timeline-item { display:flex; gap:14px; position:relative; }
      .mr-timeline-item--pending { opacity:.5; }
      .mr-timeline-dot {
        width:12px; height:12px; border-radius:50%; flex-shrink:0; margin-top:3px;
        border:2px solid var(--c-surface); box-shadow:0 0 0 2px var(--c-border); position:relative; z-index:1;
      }
      .mr-timeline-dot--blue   { background:var(--c-blue); box-shadow:0 0 0 2px #bfdbfe; }
      .mr-timeline-dot--green  { background:var(--c-green); box-shadow:0 0 0 2px #a7f3d0; }
      .mr-timeline-dot--red    { background:var(--c-red);  box-shadow:0 0 0 2px #fecaca; }
      .mr-timeline-dot--muted  { background:var(--c-border2); }
      .mr-timeline-body { flex:1; padding-bottom:4px; }
      .mr-timeline-title { font-size:13px; font-weight:600; color:var(--c-text); }
      .mr-timeline-title--muted { color:var(--c-muted); }
      .mr-timeline-meta { font-size:11.5px; color:var(--c-muted); margin-top:2px; }

      /* ── Modal ── */
      .mr-backdrop {
        position:fixed; inset:0; z-index:1000; background:rgba(20,16,48,.55);
        backdrop-filter:blur(7px); display:flex; align-items:center; justify-content:center; padding:20px;
        animation:mr-backdropIn .2s ease;
      }
      .mr-modal {
        width:100%; max-width:520px; background:var(--c-surface); border-radius:28px;
        overflow:hidden; box-shadow:var(--c-shadow-lg); animation:mr-modalIn .25s cubic-bezier(.34,1.3,.64,1) both;
      }
      .mr-modal-header {
        display:flex; align-items:center; gap:13px; padding:20px 22px;
        border-bottom:1.5px solid var(--c-border); background:#fff8f8;
      }
      .mr-modal-icon {
        width:48px; height:48px; border-radius:16px; flex-shrink:0;
        background:var(--c-red-bg); color:var(--c-red); border:1.5px solid #fecaca;
        display:flex; align-items:center; justify-content:center;
      }
      .mr-modal-title    { font-size:20px; font-weight:800; color:var(--c-text); margin:0; letter-spacing:-.02em; }
      .mr-modal-subtitle { font-size:12px; color:var(--c-muted); margin-top:2px; }
      .mr-modal-close {
        margin-left:auto; width:34px; height:34px; border-radius:10px;
        border:1.5px solid var(--c-border); background:none; color:var(--c-muted);
        display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all .15s;
      }
      .mr-modal-close:hover { background:var(--c-red-bg); color:var(--c-red); border-color:var(--c-red); }

      .mr-modal-info-strip {
        display:flex; gap:1px; background:var(--c-border);
        border-bottom:1.5px solid var(--c-border);
      }
      .mr-modal-info-item { flex:1; background:var(--c-surface); padding:12px 16px; }
      .mr-modal-info-label { font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:.08em; color:var(--c-muted); margin-bottom:4px; }
      .mr-modal-info-val   { font-size:13px; font-weight:700; color:var(--c-text); }

      .mr-modal-body { padding:18px 22px; }
      .mr-textarea-label { display:block; font-size:12px; font-weight:600; color:var(--c-text); margin-bottom:8px; letter-spacing:.01em; }
      .mr-req { color:var(--c-red); }
      .mr-textarea {
        width:100%; padding:12px 14px; border-radius:14px;
        border:1.5px solid var(--c-border); background:var(--c-bg);
        font-size:14px; color:var(--c-text); outline:none; resize:vertical;
        transition:all .15s; font-family:'DM Sans',sans-serif; min-height:120px;
      }
      .mr-textarea::placeholder { color:var(--c-muted); font-weight:300; }
      .mr-textarea:focus { border-color:var(--c-red); box-shadow:0 0 0 3px rgba(220,38,38,.1); background:#fff; }
      .mr-textarea-hint { font-size:11px; color:var(--c-muted); margin-top:6px; }

      .mr-modal-footer {
        display:flex; align-items:center; justify-content:flex-end; gap:10px;
        padding:16px 22px; border-top:1.5px solid var(--c-border); background:#faf9ff;
      }
      .mr-btn-reject-confirm {
        display:inline-flex; align-items:center; gap:7px; padding:11px 22px;
        border-radius:12px; border:none; background:var(--c-red); color:#fff;
        font-size:14px; font-weight:700; cursor:pointer; transition:all .15s;
        box-shadow:0 4px 14px rgba(220,38,38,.35); font-family:'Syne',sans-serif;
      }
      .mr-btn-reject-confirm:hover:not(:disabled) { background:#b91c1c; transform:translateY(-1px); }
      .mr-btn-reject-confirm:disabled { opacity:.45; cursor:not-allowed; transform:none; }

      /* ── Toast ── */
      .mr-toast {
        position:fixed; bottom:28px; right:28px; z-index:2000;
        display:flex; align-items:center; gap:10px; padding:13px 18px;
        border-radius:14px; font-size:14px; font-weight:500;
        box-shadow:var(--c-shadow-lg); min-width:240px;
        font-family:'DM Sans',sans-serif;
      }
      .mr-toast--success { background:var(--c-green); color:#fff; }
      .mr-toast--error   { background:var(--c-red);   color:#fff; }

      /* Transitions */
      .mr-backdrop-fade-enter-active, .mr-backdrop-fade-leave-active { transition:opacity .2s ease; }
      .mr-backdrop-fade-enter-from, .mr-backdrop-fade-leave-to { opacity:0; }
      .mr-toast-fade-enter-active, .mr-toast-fade-leave-active { transition:all .25s ease; }
      .mr-toast-fade-enter-from, .mr-toast-fade-leave-to { opacity:0; transform:translateY(10px); }
    </component>

  
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import api from '@/services/api'
import DashboardLayout from '@/layouts/DashboardLayout.vue'

const route  = useRoute()
const router = useRouter()

/*──────── State ────────*/
const loading         = ref(false)
const approving       = ref(false)
const rejecting       = ref(false)
const reading         = ref(null)
const rejectionReason = ref('')
const showRejectModal = ref(false)

/*──────── Toast ────────*/
const toast = ref({ show: false, message: '', type: 'success' })
let toastTimer = null
const showToast = (message, type = 'success') => {
  clearTimeout(toastTimer)
  toast.value = { show: true, message, type }
  toastTimer  = setTimeout(() => { toast.value.show = false }, 3500)
}

/*──────── Fetch ────────*/
const fetchReading = async () => {
  try {
    loading.value = true
    const response = await api.get(`/meter-readings/${route.params.id}`)
    reading.value  = response.data.data
  } catch (err) {
    console.error('Failed to fetch reading:', err)
    showToast('Failed to load reading', 'error')
  } finally {
    loading.value = false
  }
}

/*──────── Approve ────────*/
const approveReading = async () => {
  try {
    approving.value = true
    await api.post(`/meter-readings/${route.params.id}/approve`)
    await fetchReading()
    showToast('Reading approved successfully', 'success')
  } catch (err) {
    console.error('Approval failed:', err)
    showToast('Approval failed. Please try again.', 'error')
  } finally {
    approving.value = false
  }
}

/*──────── Reject ────────*/
const rejectReading = async () => {
  try {
    rejecting.value = true
    await api.post(`/meter-readings/${route.params.id}/reject`, {
      reason: rejectionReason.value,
    })
    showRejectModal.value = false
    rejectionReason.value = ''
    await fetchReading()
    showToast('Reading rejected and logged', 'success')
  } catch (err) {
    console.error('Rejection failed:', err)
    showToast('Rejection failed. Please try again.', 'error')
  } finally {
    rejecting.value = false
  }
}

/*──────── Variance ────────*/
const variance = computed(() => {
  if (!reading.value) return 0
  const prev = Number(reading.value.reading.previous_reading)
  const curr = Number(reading.value.reading.current_reading)
  if (prev === 0) return 0
  return (((curr - prev) / prev) * 100).toFixed(2)
})

/*──────── Lifecycle ────────*/
onMounted(() => fetchReading())
</script>