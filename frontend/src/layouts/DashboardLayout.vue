<template>
  <div class="shell" :data-theme="themeStore.resolved" :class="{ 'shell--sidebar-open': mobileOpen }">

    <!-- Skip link -->
    <a href="#main" class="skip-link">Skip to content</a>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- Mobile backdrop -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <Transition name="backdrop">
      <div v-if="mobileOpen" class="mobile-backdrop" @click="closeMobile" />
    </Transition>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- Sidebar -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <aside
      class="sidebar"
      :class="{
        'sidebar--rail': collapsed,
        'sidebar--mobile-open': mobileOpen
      }"
      id="sidebar"
      aria-label="Main navigation"
    >

      <!-- Brand -->
      <div class="sidebar-brand">
        <RouterLink to="/dashboard" class="brand-link" aria-label="AMSAAS Dashboard">
          <div class="brand-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
              <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
          </div>
          <Transition name="label-fade">
            <div v-if="!collapsed" class="brand-text">
              <span class="brand-name">AMSAAS</span>
              <span class="brand-tag">Property Platform</span>
            </div>
          </Transition>
        </RouterLink>

        <!-- Desktop collapse toggle -->
        <button
          class="collapse-btn"
          @click="toggleCollapse"
          :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
          :title="collapsed ? 'Expand' : 'Collapse'"
        >
          <svg
            width="14" height="14" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2.5"
            stroke-linecap="round" stroke-linejoin="round"
            class="collapse-icon"
            :class="{ 'collapse-icon--flipped': collapsed }"
          >
            <polyline points="15 18 9 12 15 6"/>
          </svg>
        </button>

        <!-- Mobile close -->
        <button class="mobile-close-btn" @click="closeMobile" aria-label="Close menu">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
      </div>

      <SidebarNav :collapsed="collapsed" @navigate="closeMobile" />

      <!-- Footer / user -->
      <div class="sidebar-footer" ref="userMenuRef">
        <div
          class="user-card"
          @click="userMenuOpen = !userMenuOpen"
          role="button"
          :aria-expanded="userMenuOpen"
          aria-haspopup="true"
          :title="collapsed ? (user?.name || 'Admin User') : undefined"
        >
          <div class="user-avatar">{{ userInitials }}</div>
          <Transition name="label-fade">
            <div v-if="!collapsed" class="user-info">
              <span class="user-name">{{ user?.name || 'Admin User' }}</span>
              <span class="user-role">{{ user?.role || 'Administrator' }}</span>
            </div>
          </Transition>
          <Transition name="label-fade">
            <svg v-if="!collapsed" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="user-chevron" :class="{ 'user-chevron--up': userMenuOpen }">
              <polyline points="6 9 12 15 18 9"/>
            </svg>
          </Transition>
          <span v-if="collapsed" class="nav-tooltip">{{ user?.name || 'Admin User' }}</span>
        </div>

        <Transition name="menu-pop">
          <div v-if="userMenuOpen" class="user-dropdown">
            <div class="dropdown-header">
              <p class="dh-name">{{ user?.name || 'Admin User' }}</p>
              <p class="dh-email">{{ user?.email || 'admin@amsaas.com' }}</p>
            </div>
            <button class="dropdown-item" @click="goSettings">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
              Settings
            </button>
            <button class="dropdown-item dropdown-item--danger" @click="doLogout">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
              Sign out
            </button>
          </div>
        </Transition>
      </div>

    </aside>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- Main shell -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <div class="main-shell" :class="{ 'main-shell--rail': collapsed }">

      <!-- Topbar -->
      <header class="topbar">

        <!-- Mobile hamburger -->
        <button class="hamburger" @click="openMobile" aria-label="Open menu" aria-controls="sidebar">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="6" x2="21" y2="6"/>
            <line x1="3" y1="12" x2="21" y2="12"/>
            <line x1="3" y1="18" x2="21" y2="18"/>
          </svg>
        </button>

        <!-- Breadcrumb / title -->
        <div class="topbar-title">
          <h1 class="page-heading">{{ pageTitle }}</h1>
          <nav class="breadcrumb" aria-label="Breadcrumb">
            <span v-for="(crumb, i) in breadcrumbs" :key="crumb.to" class="crumb">
              <RouterLink v-if="i < breadcrumbs.length - 1" :to="crumb.to" class="crumb-link">{{ crumb.label }}</RouterLink>
              <span v-else class="crumb-current" aria-current="page">{{ crumb.label }}</span>
              <span v-if="i < breadcrumbs.length - 1" class="crumb-sep" aria-hidden="true">/</span>
            </span>
          </nav>
        </div>

        <!-- Right controls -->
        <div class="topbar-right">

          <!-- Search -->
          <button class="search-trigger" @click="cmdOpen = true" aria-label="Open command palette">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <span class="search-label">Search…</span>
            <kbd class="search-kbd">⌘K</kbd>
          </button>

          <div class="topbar-sep" />

          <!-- Period indicator -->
          <div class="period-chip" :class="{ 'period-chip--warning': periodEnding }">
            <span class="period-pulse" />
            <span>{{ period }}</span>
          </div>

          <ThemeToggle compact class="topbar-theme-toggle" />

          <!-- Notifications -->
          <button class="icon-btn icon-btn--notif" @click="notifOpen = !notifOpen" :aria-label="`Notifications${unread > 0 ? `, ${unread} unread` : ''}`">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
            <span v-if="unread > 0" class="notif-badge">{{ unread > 9 ? '9+' : unread }}</span>
          </button>

          <div class="topbar-sep" />

          <!-- Topbar user avatar -->
          <div class="topbar-user-wrap" ref="topbarUserRef">
            <button
              class="topbar-user"
              @click="topbarMenuOpen = !topbarMenuOpen"
              :aria-expanded="topbarMenuOpen"
              aria-haspopup="true"
              aria-label="User menu"
            >
              <div class="topbar-avatar">{{ userInitials }}</div>
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="user-chevron" :class="{ 'user-chevron--up': topbarMenuOpen }">
                <polyline points="6 9 12 15 18 9"/>
              </svg>
            </button>

            <Transition name="menu-pop">
              <div v-if="topbarMenuOpen" class="topbar-dropdown">
                <div class="dropdown-header">
                  <p class="dh-name">{{ user?.name || 'Admin User' }}</p>
                  <p class="dh-email">{{ user?.email || 'admin@amsaas.com' }}</p>
                </div>
                <button class="dropdown-item" @click="goSettings">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                  Settings
                </button>
                <button class="dropdown-item dropdown-item--danger" @click="doLogout">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                  Sign out
                </button>
              </div>
            </Transition>
          </div>

        </div>
      </header>

      <!-- Page content -->
     <main
  id="main"
  class="
    page-content
    bg-slate-50
    font-sans
    antialiased
    text-slate-800
    text-slate-900
    transition-colors
    duration-300

    dark:bg-slate-950
    dark:text-slate-100
  "
>

  <RouterView />

</main>

      <!-- Toast stack -->
      <div class="toast-stack" aria-live="polite" aria-label="Notifications">
        <slot name="toasts" />
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- Command palette slot -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <slot name="command-palette" :open="cmdOpen" :close="() => cmdOpen = false" />
    </Teleport>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
//import { useRoute, useRouter } from 'vue-router'
import {
  RouterView,
  useRoute,
  useRouter
} from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useThemeStore } from '@/stores/theme'
import ThemeToggle from '@/components/common/ThemeToggle.vue'
import SidebarNav from '@/components/layout/SidebarNav.vue'

/* ─── Props ─────────────────────────────────────────────────── */
const props = defineProps({
  user: { type: Object, default: null },
  unread: { type: Number, default: 0 },
  notifications: { type: Array, default: () => [] },
})

const emit = defineEmits(['logout', 'mark-read'])

/* ─── Router ─────────────────────────────────────────────────── */
const route  = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const themeStore = useThemeStore()

/* ─── State ──────────────────────────────────────────────────── */
const collapsed     = ref(false)
const mobileOpen    = ref(false)
const userMenuOpen  = ref(false)
const topbarMenuOpen = ref(false)
const notifOpen     = ref(false)
const cmdOpen       = ref(false)

const userMenuRef    = ref(null)
const topbarUserRef  = ref(null)

/* ─── Computed ───────────────────────────────────────────────── */
const userInitials = computed(() => {
  if (!props.user?.name) return 'AU'
  return props.user.name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
})

const routeTitles = {
  dashboard: 'Dashboard', buildings: 'Buildings', apartments: 'Apartments',
  tenants: 'Tenants', invoices: 'Billing close', payments: 'Payments',
  'invoices/monthly': 'Monthly invoices',
  invoicecreate: 'Create invoice',
  invoiceshow: 'Invoice detail',
  accounts: 'Chart of Accounts',
  'general-ledger': 'General Ledger', generalledger: 'General Ledger',
  'trial-balance': 'Trial Balance', trialbalance: 'Trial Balance',
  'income-statement': 'Income Statement', incomestatement: 'Income Statement',
  'balance-sheet': 'Balance Sheet', balancesheet: 'Balance Sheet',
  'financial-audit': 'Financial Audit', financialaudit: 'Financial Audit',
  chargemodels: 'Charge Models', 'charge-models': 'Charge Models',
  charges: 'Utility Charges',
  'charge-types': 'Charge Types', chargetypes: 'Charge Types',
  reports: 'Reports', settings: 'Settings',
  sales: 'Sales', 'sales/inventory': 'Property inventory', 'sales/reservations': 'Reservations',
  'sales/contracts': 'Sale contracts', 'sales/buyers': 'Buyers',
}

const pageTitle = computed(() => {
  const name = (route.name || route.path.split('/').filter(Boolean)[0] || 'dashboard').toString().toLowerCase()
  return routeTitles[name] || name.charAt(0).toUpperCase() + name.slice(1)
})

const breadcrumbs = computed(() => {
  const segs = route.path.split('/').filter(Boolean)
  const crumbs = [{ label: 'Home', to: '/dashboard' }]
  let path = ''
  for (const s of segs) {
    path += `/${s}`
    crumbs.push({ label: s.charAt(0).toUpperCase() + s.slice(1), to: path })
  }
  return crumbs
})

const period = computed(() => new Date().toLocaleDateString('en-US', { month: 'short', year: 'numeric' }))
const periodEnding = computed(() => {
  const now = new Date()
  return now.getDate() > new Date(now.getFullYear(), now.getMonth() + 1, 0).getDate() - 5
})

/* ─── Methods ────────────────────────────────────────────────── */
const toggleCollapse = () => {
  collapsed.value = !collapsed.value
  localStorage.setItem('sb-collapsed', collapsed.value)
}

const openMobile  = () => { mobileOpen.value = true;  document.body.style.overflow = 'hidden' }
const closeMobile = () => { mobileOpen.value = false; document.body.style.overflow = '' }

const goSettings = () => { router.push('/settings'); userMenuOpen.value = false; topbarMenuOpen.value = false }
const doLogout = async () => {
  await authStore.logout()
  emit('logout')
  router.push('/login')
}

/* ─── Click outside dropdowns ────────────────────────────────── */
const handleClickOutside = (e) => {
  if (userMenuRef.value  && !userMenuRef.value.contains(e.target))  userMenuOpen.value   = false
  if (topbarUserRef.value && !topbarUserRef.value.contains(e.target)) topbarMenuOpen.value = false
}

/* ─── Keyboard ───────────────────────────────────────────────── */
const handleKey = (e) => {
  if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); cmdOpen.value = true }
  if (e.key === 'Escape') {
    cmdOpen.value = false; notifOpen.value = false
    userMenuOpen.value = false; topbarMenuOpen.value = false
    if (mobileOpen.value) closeMobile()
  }
}

/* ─── Lifecycle ──────────────────────────────────────────────── */
onMounted(() => {
  const sc = localStorage.getItem('sb-collapsed')
  if (sc !== null) collapsed.value = sc === 'true'

  window.addEventListener('keydown', handleKey)
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.body.style.overflow = ''
  window.removeEventListener('keydown', handleKey)
  document.removeEventListener('click', handleClickOutside)
})

watch(() => route.path, () => {
  closeMobile()
  userMenuOpen.value  = false
  topbarMenuOpen.value = false
  notifOpen.value     = false
})
</script>

<style scoped>
/* ═══════════════════════════════════════════════════════════
   Tokens — Light Mode
═══════════════════════════════════════════════════════════ */
.shell {
  /* Sidebar */
  --sb-w:        256px;
  --sb-rail:     68px;
  --sb-bg:       #0d1117;
  --sb-border:   rgba(255,255,255,.07);
  --sb-text:     #8b949e;
  --sb-text-hov: #e6edf3;
  --sb-active-bg: rgba(88,166,255,.10);
  --sb-active-tx: #58a6ff;
  --sb-active-bar: #58a6ff;
  --sb-label-bg: #1c2128;
  --sb-grp:      #3d444d;

  /* App chrome */
  --topbar-h:    58px;
  --bg:          #f6f8fa;
  --surface:     #ffffff;
  --surface-rgb: 255,255,255;
  --border:      #d0d7de;
  --border-soft: #eaeef2;
  --text:        #1f2328;
  --muted:       #656d76;
  --subtle:      #9198a1;
  --accent:      #0969da;
  --accent-lt:   #ddf4ff;

  /* Shadows */
  --sh-xs: 0 1px 2px rgba(31,35,40,.06);
  --sh-sm: 0 1px 3px rgba(31,35,40,.08), 0 1px 2px rgba(31,35,40,.04);
  --sh-md: 0 4px 8px rgba(31,35,40,.10), 0 1px 3px rgba(31,35,40,.06);
  --sh-lg: 0 12px 28px rgba(31,35,40,.14), 0 2px 6px rgba(31,35,40,.08);
  --sh-sidebar: 2px 0 24px rgba(0,0,0,.28);

  /* Radii */
  --r-sm: 5px;
  --r-md: 8px;
  --r-lg: 12px;

  /* Timing */
  --ease: cubic-bezier(.4,0,.2,1);
  --t:    200ms;

  font-family: 'DM Sans', ui-sans-serif, system-ui, sans-serif;
  display: flex;
  min-height: 100vh;
  background: var(--bg);
  color: var(--text);
  overflow: hidden;
}

/* ── Dark theme overrides ─────────────────────────────────── */
.shell[data-theme="dark"] {
  --sb-bg:       #010409;
  --bg:          #0d1117;
  --surface:     #161b22;
  --surface-rgb: 22,27,34;
  --border:      #30363d;
  --border-soft: #21262d;
  --text:        #e6edf3;
  --muted:       #8b949e;
  --subtle:      #6e7681;
  --accent:      #58a6ff;
  --accent-lt:   #121d2f;
  --sh-sidebar: 2px 0 32px rgba(0,0,0,.5);
}

/* ═══════════════════════════════════════════════════════════
   Skip link
═══════════════════════════════════════════════════════════ */
.skip-link {
  position: absolute;
  top: -100px;
  left: 16px;
  z-index: 9999;
  background: var(--accent);
  color: #fff;
  padding: 8px 16px;
  border-radius: var(--r-md);
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
  transition: top .15s var(--ease);
}
.skip-link:focus { top: 16px; outline: 2px solid #fff; outline-offset: 2px; }

.topbar-theme-toggle {
  border-color: var(--border);
  background: var(--surface);
  color: var(--muted);
  padding: 0.45rem;
}

.topbar-theme-toggle:hover {
  background: var(--border-soft);
  color: var(--text);
}

/* ═══════════════════════════════════════════════════════════
   Mobile backdrop
═══════════════════════════════════════════════════════════ */
.mobile-backdrop {
  position: fixed;
  inset: 0;
  z-index: 38;
  background: rgba(1,4,9,.65);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
}

/* ═══════════════════════════════════════════════════════════
   Sidebar
═══════════════════════════════════════════════════════════ */
.sidebar {
  position: fixed;
  top: 0; left: 0; bottom: 0;
  z-index: 40;
  width: var(--sb-w);
  background: var(--sb-bg);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: var(--sh-sidebar);
  border-right: 1px solid var(--sb-border);
  transition: width var(--t) var(--ease);
  will-change: width;
}

.sidebar--rail {
  width: var(--sb-rail);
}

/* Mobile: hidden off-screen by default */
@media (max-width: 1023px) {
  .sidebar {
    transform: translateX(-100%);
    width: var(--sb-w);             /* always full width on mobile */
    transition: transform .26s cubic-bezier(.32,0,.67,0);
  }
  .sidebar--mobile-open {
    transform: translateX(0);
    transition: transform .3s cubic-bezier(.33,1,.68,1);
  }
}

/* ─ Brand ─────────────────────────────────────────────────── */
.sidebar-brand {
  display: flex;
  align-items: center;
  height: var(--topbar-h);
  padding: 0 14px;
  border-bottom: 1px solid var(--sb-border);
  flex-shrink: 0;
  gap: 10px;
  position: relative;
}

.brand-link {
  display: flex;
  align-items: center;
  gap: 10px;
  text-decoration: none;
  flex: 1;
  min-width: 0;
}

.brand-icon {
  width: 34px; height: 34px;
  border-radius: var(--r-md);
  background: linear-gradient(135deg, #1f6feb 0%, #58a6ff 100%);
  display: flex; align-items: center; justify-content: center;
  color: #fff;
  flex-shrink: 0;
  box-shadow: 0 0 0 1px rgba(88,166,255,.25), 0 2px 8px rgba(88,166,255,.2);
  transition: transform .15s var(--ease);
}
.brand-link:hover .brand-icon { transform: scale(1.04); }

.brand-text {
  display: flex; flex-direction: column;
  overflow: hidden;
}
.brand-name {
  font-size: 13px; font-weight: 800;
  letter-spacing: .06em; color: #e6edf3;
  white-space: nowrap;
}
.brand-tag {
  font-size: 10px; color: var(--sb-text);
  white-space: nowrap; margin-top: 1px;
}

.collapse-btn {
  width: 26px; height: 26px;
  display: flex; align-items: center; justify-content: center;
  border-radius: var(--r-sm);
  background: transparent;
  border: 1px solid var(--sb-border);
  color: var(--sb-text);
  cursor: pointer;
  flex-shrink: 0;
  transition: background .12s, color .12s, border-color .12s;
}
.collapse-btn:hover { background: rgba(255,255,255,.07); color: var(--sb-text-hov); border-color: rgba(255,255,255,.12); }

.collapse-icon { transition: transform var(--t) var(--ease); }
.collapse-icon--flipped { transform: rotate(180deg); }

/* Only show collapse-btn on desktop */
.mobile-close-btn { display: none; }
@media (max-width: 1023px) {
  .collapse-btn    { display: none; }
  .mobile-close-btn {
    display: flex; align-items: center; justify-content: center;
    width: 30px; height: 30px;
    border-radius: var(--r-md);
    background: rgba(255,255,255,.07);
    border: 1px solid var(--sb-border);
    color: var(--sb-text-hov);
    cursor: pointer;
    flex-shrink: 0;
    transition: background .12s;
  }
  .mobile-close-btn:hover { background: rgba(255,255,255,.12); }
}

/* ─ Footer ────────────────────────────────────────────────── */
.sidebar-footer {
  border-top: 1px solid var(--sb-border);
  padding: 12px 10px;
  flex-shrink: 0;
  position: relative;
}

.user-card {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 10px;
  border-radius: var(--r-md);
  cursor: pointer;
  transition: background .12s;
  position: relative;
}
.user-card:hover { background: rgba(255,255,255,.05); }

.user-avatar {
  width: 34px; height: 34px;
  border-radius: 50%;
  background: linear-gradient(135deg, #1f6feb, #58a6ff);
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 700; color: #fff;
  flex-shrink: 0;
  box-shadow: 0 0 0 1.5px rgba(88,166,255,.3);
}

.user-info { flex: 1; min-width: 0; display: flex; flex-direction: column; }
.user-name {
  font-size: 13px; font-weight: 600; color: var(--sb-text-hov);
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.user-role { font-size: 11px; color: var(--sb-text); white-space: nowrap; }

.user-chevron { color: var(--sb-text); transition: transform var(--t) var(--ease); }
.user-chevron--up { transform: rotate(180deg); }

/* User dropdown from sidebar */
.user-dropdown {
  position: absolute;
  bottom: calc(100% + 4px);
  left: 10px; right: 10px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--r-lg);
  box-shadow: var(--sh-lg);
  overflow: hidden;
  z-index: 50;
}

/* ═══════════════════════════════════════════════════════════
   Main shell
═══════════════════════════════════════════════════════════ */
.main-shell {
  flex: 1;
  display: flex;
  flex-direction: column;
  margin-left: var(--sb-w);
  min-width: 0;
  height: 100vh;
  overflow: hidden;
  transition: margin-left var(--t) var(--ease);
}
.main-shell--rail { margin-left: var(--sb-rail); }

@media (max-width: 1023px) {
  .main-shell,
  .main-shell--rail { margin-left: 0; }
}

/* ─ Topbar ────────────────────────────────────────────────── */
.topbar {
  position: sticky; top: 0; z-index: 30;
  height: var(--topbar-h);
  flex-shrink: 0;
  background: rgba(var(--surface-rgb), .92);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  border-bottom: 1px solid var(--border-soft);
  display: flex;
  align-items: center;
  padding: 0 20px;
  gap: 12px;
}

.hamburger {
  display: none;
  width: 34px; height: 34px;
  border-radius: var(--r-md);
  border: 1px solid var(--border);
  background: transparent;
  color: var(--muted);
  cursor: pointer;
  align-items: center; justify-content: center;
  flex-shrink: 0;
  transition: background .12s, color .12s;
}
.hamburger:hover { background: var(--bg); color: var(--text); }
@media (max-width: 1023px) { .hamburger { display: flex; } }

.topbar-title { flex: 1; min-width: 0; }

.page-heading {
  font-size: 16px; font-weight: 700;
  letter-spacing: -.02em; color: var(--text);
  margin: 0; line-height: 1.2;
}

.breadcrumb {
  display: flex; align-items: center; gap: 4px;
  margin-top: 1px;
  flex-wrap: nowrap; overflow: hidden;
}
.crumb { display: flex; align-items: center; gap: 4px; font-size: 11.5px; }
.crumb-link { color: var(--muted); text-decoration: none; transition: color .12s; }
.crumb-link:hover { color: var(--text); }
.crumb-sep { color: var(--subtle); }
.crumb-current { color: var(--subtle); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.topbar-right {
  display: flex; align-items: center;
  gap: 6px; flex-shrink: 0;
}

.topbar-sep { width: 1px; height: 22px; background: var(--border); margin: 0 2px; }
@media (max-width: 640px) { .topbar-sep { display: none; } }

/* Search trigger */
.search-trigger {
  display: flex; align-items: center; gap: 8px;
  height: 34px; padding: 0 12px;
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: var(--r-md);
  color: var(--subtle);
  cursor: pointer;
  font-size: 13px;
  transition: border-color .12s, box-shadow .12s, background .12s;
}
.search-trigger:hover { border-color: var(--accent); background: var(--surface); color: var(--muted); }
.search-label { flex: 1; text-align: left; }
.search-kbd {
  font-size: 10px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 4px;
  padding: 1px 5px;
  color: var(--subtle);
  font-family: ui-monospace, monospace;
}
@media (max-width: 767px) { .search-trigger { display: none; } }

/* Period chip */
.period-chip {
  display: flex; align-items: center; gap: 6px;
  height: 30px; padding: 0 11px;
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: 99px;
  font-size: 12px; font-weight: 500; color: var(--muted);
}
.period-pulse {
  width: 7px; height: 7px; border-radius: 50%;
  background: #3fb950;
  box-shadow: 0 0 0 2px rgba(63,185,80,.2);
}
.period-chip--warning .period-pulse { background: #d29922; box-shadow: 0 0 0 2px rgba(210,153,34,.2); }
@media (max-width: 900px) { .period-chip { display: none; } }

/* Icon buttons */
.icon-btn {
  position: relative;
  width: 34px; height: 34px;
  display: flex; align-items: center; justify-content: center;
  border-radius: var(--r-md);
  border: 1px solid var(--border);
  background: var(--surface);
  color: var(--muted);
  cursor: pointer;
  flex-shrink: 0;
  transition: background .12s, color .12s, border-color .12s, transform .1s;
}
.icon-btn:hover { background: var(--bg); color: var(--text); transform: translateY(-1px); }

.notif-badge {
  position: absolute;
  top: -4px; right: -4px;
  min-width: 17px; height: 17px;
  border-radius: 99px;
  background: #f85149;
  color: #fff;
  font-size: 9.5px; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  padding: 0 3px;
  border: 2px solid var(--surface);
}

/* Topbar user */
.topbar-user-wrap { position: relative; }
.topbar-user {
  display: flex; align-items: center; gap: 7px;
  padding: 4px 10px 4px 4px;
  border-radius: 99px;
  border: 1px solid var(--border);
  background: var(--surface);
  cursor: pointer;
  transition: background .12s;
}
.topbar-user:hover { background: var(--bg); }

.topbar-avatar {
  width: 28px; height: 28px;
  border-radius: 50%;
  background: linear-gradient(135deg, #1f6feb, #58a6ff);
  display: flex; align-items: center; justify-content: center;
  font-size: 11px; font-weight: 700; color: #fff;
}

/* Topbar dropdown */
.topbar-dropdown {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  width: 200px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--r-lg);
  box-shadow: var(--sh-lg);
  overflow: hidden;
  z-index: 50;
}

/* Shared dropdown parts */
.dropdown-header {
  padding: 12px 16px 10px;
  border-bottom: 1px solid var(--border-soft);
}
.dh-name  { font-size: 13px; font-weight: 600; color: var(--text); margin: 0; }
.dh-email { font-size: 11.5px; color: var(--subtle); margin: 2px 0 0; }

.dropdown-item {
  display: flex; align-items: center; gap: 10px;
  width: 100%; padding: 10px 16px;
  background: transparent;
  border: none;
  font-size: 13px; font-weight: 500;
  color: var(--text);
  cursor: pointer;
  text-align: left;
  transition: background .1s;
}
.dropdown-item:hover { background: var(--bg); }
.dropdown-item--danger {
  color: #f85149;
  border-top: 1px solid var(--border-soft);
}
.dropdown-item--danger:hover { background: rgba(248,81,73,.06); }

/* ─ Page content ──────────────────────────────────────────── */
.page-content {
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 24px;
  min-width: 0;
  outline: none;
}
@media (max-width: 767px) {
  .page-content { padding: 16px; }
  .topbar { padding: 0 12px; gap: 8px; }
  .page-heading { font-size: 15px; }
}

@media (max-width: 480px) {
  .breadcrumb { display: none; }
  .topbar-user { padding-right: 6px; }
}

/* Toast stack */
.toast-stack {
  position: fixed;
  bottom: 20px; right: 20px;
  z-index: 200;
  display: flex; flex-direction: column; gap: 10px;
}

/* ═══════════════════════════════════════════════════════════
   Transitions
═══════════════════════════════════════════════════════════ */
/* Backdrop */
.backdrop-enter-active { transition: opacity .2s ease; }
.backdrop-leave-active { transition: opacity .18s ease; }
.backdrop-enter-from, .backdrop-leave-to { opacity: 0; }

/* Label fade (sidebar labels on collapse) */
.label-fade-enter-active { transition: opacity .15s ease, transform .15s ease; }
.label-fade-leave-active { transition: opacity .1s ease; position: absolute; }
.label-fade-enter-from   { opacity: 0; transform: translateX(-6px); }
.label-fade-leave-to     { opacity: 0; }

/* Dropdown pop */
.menu-pop-enter-active { transition: opacity .15s ease, transform .15s cubic-bezier(.34,1.3,.64,1); }
.menu-pop-leave-active { transition: opacity .1s ease, transform .1s ease; }
.menu-pop-enter-from   { opacity: 0; transform: translateY(6px) scale(.97); }
.menu-pop-leave-to     { opacity: 0; transform: translateY(4px) scale(.98); }
</style>