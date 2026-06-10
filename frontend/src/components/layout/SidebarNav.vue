<template>
  <nav class="sidebar-nav" aria-label="Primary">
    <div class="nav-section">
      <template v-for="entry in navigation" :key="entry.id">
        <!-- Direct link -->
        <RouterLink
          v-if="entry.type === 'link'"
          :to="entry.to"
          class="nav-item"
          :class="{ 'nav-item--active': isActive(entry.to) }"
          :title="collapsed ? entry.label : undefined"
          @click="onNavigate"
        >
          <span class="nav-item-icon" v-html="entry.icon" />
          <Transition name="label-fade">
            <span v-if="!collapsed" class="nav-item-label">{{ entry.label }}</span>
          </Transition>
          <span v-if="collapsed" class="nav-tooltip">{{ entry.label }}</span>
        </RouterLink>

        <!-- Collapsible group -->
        <div
          v-else
          class="nav-group"
          :class="{
            'nav-group--open': isGroupOpen(entry.id),
            'nav-group--active': isGroupActive(entry),
            'nav-group--rail': collapsed,
          }"
          @mouseenter="collapsed && setRailHover(entry.id)"
          @mouseleave="collapsed && clearRailHover()"
        >
          <button
            type="button"
            class="nav-group-trigger"
            :ref="(el) => setGroupRef(entry.id, el)"
            :aria-expanded="isGroupOpen(entry.id)"
            :title="collapsed ? entry.label : undefined"
            @click="toggleGroup(entry.id)"
          >
            <span class="nav-item-icon" v-html="entry.icon" />
            <Transition name="label-fade">
              <span v-if="!collapsed" class="nav-item-label">{{ entry.label }}</span>
            </Transition>
            <Transition name="label-fade">
              <span v-if="!collapsed" class="nav-group-count">{{ entry.items.length }}</span>
            </Transition>
            <Transition name="label-fade">
              <svg
                v-if="!collapsed"
                class="nav-group-chevron"
                width="14"
                height="14"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2.5"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
              >
                <polyline points="6 9 12 15 18 9" />
              </svg>
            </Transition>
            <span v-if="collapsed" class="nav-tooltip">{{ entry.label }}</span>
          </button>

          <!-- Expanded sub-items (full sidebar) -->
          <div
            v-if="!collapsed"
            class="nav-subitems"
            :class="{ 'nav-subitems--open': isGroupOpen(entry.id) }"
          >
            <div class="nav-subitems-inner">
              <template v-for="item in entry.items" :key="item.to">
                <span
                  v-if="item.disabled"
                  class="nav-subitem nav-subitem--disabled"
                  role="presentation"
                >
                  <span class="nav-subitem-icon" v-html="item.icon" />
                  <span class="nav-subitem-label">{{ item.label }}</span>
                  <span v-if="item.badge" class="nav-badge nav-badge--muted">{{ item.badge }}</span>
                </span>

                <RouterLink
                  v-else
                  :to="item.to"
                  class="nav-subitem"
                  :class="{ 'nav-subitem--active': isActive(item.to) }"
                  @click="onNavigate"
                >
                  <span class="nav-subitem-icon" v-html="item.icon" />
                  <span class="nav-subitem-label">{{ item.label }}</span>
                  <span v-if="item.badge" class="nav-badge">{{ item.badge }}</span>
                </RouterLink>
              </template>
            </div>
          </div>

        </div>
      </template>
    </div>

    <div v-if="footerNavigation.length" class="nav-section nav-section--footer">
      <div class="nav-footer-divider" role="separator" />
      <template v-for="entry in footerNavigation" :key="entry.id">
        <RouterLink
          v-if="entry.type === 'link'"
          :to="entry.to"
          class="nav-item"
          :class="{ 'nav-item--active': isActive(entry.to) }"
          :title="collapsed ? entry.label : undefined"
          @click="onNavigate"
        >
          <span class="nav-item-icon" v-html="entry.icon" />
          <Transition name="label-fade">
            <span v-if="!collapsed" class="nav-item-label">{{ entry.label }}</span>
          </Transition>
          <span v-if="collapsed" class="nav-tooltip">{{ entry.label }}</span>
        </RouterLink>
      </template>
    </div>

    <Teleport to="body">
      <Transition name="flyout">
        <div
          v-if="collapsed && railFlyout"
          class="nav-flyout"
          :style="railFlyoutStyle"
          role="menu"
          @mouseenter="keepRailFlyout"
          @mouseleave="clearRailFlyout"
        >
          <p class="nav-flyout-title">{{ railFlyout.label }}</p>
          <RouterLink
            v-for="item in railFlyout.items"
            :key="item.to"
            :to="item.to"
            class="nav-flyout-item"
            :class="{ 'nav-flyout-item--active': isActive(item.to) }"
            role="menuitem"
            @click="onNavigate"
          >
            <span class="nav-flyout-icon" v-html="item.icon" />
            {{ item.label }}
          </RouterLink>
        </div>
      </Transition>
    </Teleport>
  </nav>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import {
  sidebarNavigation,
  sidebarFooterNavigation,
} from '@/config/sidebarNav'

const props = defineProps({
  collapsed: { type: Boolean, default: false },
})

const emit = defineEmits(['navigate'])

const route = useRoute()
const navigation = sidebarNavigation
const footerNavigation = sidebarFooterNavigation

const STORAGE_KEY = 'sb-expanded-groups'
const expandedGroups = ref(new Set())
const railFlyout = ref(null)
const railFlyoutStyle = ref({})
const groupRefs = ref({})
let railCloseTimer = null

function loadExpanded() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (raw) {
      const ids = JSON.parse(raw)
      if (Array.isArray(ids)) {
        expandedGroups.value = new Set(ids)
      }
    }
  } catch {
    expandedGroups.value = new Set()
  }
}

function saveExpanded() {
  localStorage.setItem(STORAGE_KEY, JSON.stringify([...expandedGroups.value]))
}

function isActive(path) {
  return path === '/dashboard' ? route.path === '/dashboard' : route.path.startsWith(path)
}

function isGroupActive(group) {
  return group.items?.some((item) => isActive(item.to))
}

function isGroupOpen(id) {
  return expandedGroups.value.has(id)
}

function setGroupRef(id, el) {
  if (el) groupRefs.value[id] = el
}

function positionRailFlyout(id) {
  const entry = navigation.find((e) => e.id === id && e.type === 'group')
  const el = groupRefs.value[id]
  if (!entry || !el) return

  const rect = el.getBoundingClientRect()
  railFlyout.value = entry
  railFlyoutStyle.value = {
    position: 'fixed',
    top: `${Math.max(8, rect.top)}px`,
    left: `${rect.right + 6}px`,
    zIndex: 200,
  }
}

function clearRailFlyout() {
  railCloseTimer = setTimeout(() => {
    railFlyout.value = null
  }, 120)
}

function keepRailFlyout() {
  if (railCloseTimer) {
    clearTimeout(railCloseTimer)
    railCloseTimer = null
  }
}

function toggleGroup(id) {
  if (props.collapsed) {
    if (railFlyout.value?.id === id) {
      railFlyout.value = null
    } else {
      positionRailFlyout(id)
    }
    return
  }
  const next = new Set(expandedGroups.value)
  if (next.has(id)) {
    next.delete(id)
  } else {
    next.add(id)
  }
  expandedGroups.value = next
  saveExpanded()
}

function setRailHover(id) {
  positionRailFlyout(id)
}

function clearRailHover() {
  clearRailFlyout()
}

function ensureActiveGroupExpanded() {
  const next = new Set(expandedGroups.value)
  for (const entry of navigation) {
    if (entry.type === 'group' && isGroupActive(entry)) {
      next.add(entry.id)
    }
  }
  expandedGroups.value = next
}

function onNavigate() {
  emit('navigate')
  railFlyout.value = null
}

watch(() => route.path, () => {
  ensureActiveGroupExpanded()
  railFlyout.value = null
})

watch(() => props.collapsed, (isCollapsed) => {
  if (!isCollapsed) railFlyout.value = null
})

onMounted(() => {
  loadExpanded()
  ensureActiveGroupExpanded()
})

onUnmounted(() => {
  if (railCloseTimer) clearTimeout(railCloseTimer)
})
</script>

<style scoped>
.sidebar-nav {
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 10px 8px;
  display: flex;
  flex-direction: column;
  gap: 4px;
  scrollbar-width: thin;
  scrollbar-color: rgba(255, 255, 255, 0.07) transparent;
}

.sidebar-nav::-webkit-scrollbar { width: 4px; }
.sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.08); border-radius: 4px; }

.nav-section {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.nav-section--footer {
  margin-top: auto;
  padding-top: 4px;
}

.nav-footer-divider {
  height: 1px;
  background: var(--sb-border, rgba(255, 255, 255, 0.07));
  margin: 4px 6px 8px;
}

/* ── Top-level link / group trigger ─────────────────────── */
.nav-item,
.nav-group-trigger {
  display: flex;
  align-items: center;
  gap: 9px;
  width: 100%;
  padding: 7px 9px;
  border-radius: var(--r-md, 8px);
  color: var(--sb-text, #8b949e);
  text-decoration: none;
  font-size: 13px;
  font-weight: 500;
  position: relative;
  white-space: nowrap;
  border: none;
  background: transparent;
  cursor: pointer;
  text-align: left;
  transition: background 0.12s, color 0.12s;
}

.nav-item:hover,
.nav-group-trigger:hover {
  background: rgba(255, 255, 255, 0.05);
  color: var(--sb-text-hov, #e6edf3);
}

.nav-item--active,
.nav-group--active > .nav-group-trigger {
  background: var(--sb-active-bg, rgba(88, 166, 255, 0.1)) !important;
  color: var(--sb-active-tx, #58a6ff) !important;
}

.nav-item--active::before {
  content: '';
  position: absolute;
  left: 0;
  top: 20%;
  bottom: 20%;
  width: 2.5px;
  border-radius: 0 2px 2px 0;
  background: var(--sb-active-bar, #58a6ff);
  margin-left: -9px;
}

.nav-item-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  width: 16px;
}

.nav-item-label {
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
}

.nav-group-count {
  font-size: 10px;
  font-weight: 600;
  color: var(--sb-grp, #3d444d);
  background: rgba(255, 255, 255, 0.04);
  border-radius: 99px;
  padding: 0 6px;
  line-height: 1.6;
}

.nav-group-chevron {
  flex-shrink: 0;
  color: var(--sb-grp, #3d444d);
  transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.nav-group--open .nav-group-chevron {
  transform: rotate(180deg);
}

/* ── Collapsible sub-items ──────────────────────────────── */
.nav-group {
  position: relative;
}

.nav-subitems {
  display: grid;
  grid-template-rows: 0fr;
  transition: grid-template-rows 0.22s cubic-bezier(0.4, 0, 0.2, 1);
}

.nav-subitems--open {
  grid-template-rows: 1fr;
}

.nav-subitems-inner {
  overflow: hidden;
  padding-left: 8px;
  margin-top: 1px;
}

.nav-subitem {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 5px 9px 5px 18px;
  border-radius: var(--r-md, 8px);
  color: var(--sb-text, #8b949e);
  text-decoration: none;
  font-size: 12.5px;
  font-weight: 500;
  position: relative;
  transition: background 0.12s, color 0.12s;
}

.nav-subitem::before {
  content: '';
  position: absolute;
  left: 9px;
  top: 50%;
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: var(--sb-grp, #3d444d);
  transform: translateY(-50%);
  transition: background 0.12s;
}

.nav-subitem:hover {
  background: rgba(255, 255, 255, 0.04);
  color: var(--sb-text-hov, #e6edf3);
}

.nav-subitem--active {
  color: var(--sb-active-tx, #58a6ff) !important;
  background: rgba(88, 166, 255, 0.06) !important;
}

.nav-subitem--active::before {
  background: var(--sb-active-bar, #58a6ff);
}

.nav-subitem--disabled {
  opacity: 0.5;
  cursor: not-allowed;
  pointer-events: none;
}

.nav-subitem-icon {
  display: none;
}

.nav-subitem-label {
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
}

.nav-badge {
  font-size: 9px;
  font-weight: 700;
  background: rgba(88, 166, 255, 0.18);
  color: var(--sb-active-tx, #58a6ff);
  border-radius: 99px;
  padding: 1px 6px;
}

.nav-badge--muted {
  background: rgba(255, 255, 255, 0.06);
  color: var(--sb-text, #8b949e);
}

/* ── Rail flyout ──────────────────────────────────────────── */
.nav-flyout {
  min-width: 196px;
  background: var(--sb-label-bg, #1c2128);
  border: 1px solid var(--sb-border, rgba(255, 255, 255, 0.1));
  border-radius: var(--r-lg, 12px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
  padding: 6px;
  z-index: 120;
}

.nav-flyout-title {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--sb-grp, #3d444d);
  padding: 6px 10px 4px;
  margin: 0;
}

.nav-flyout-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 7px 10px;
  border-radius: var(--r-md, 8px);
  color: var(--sb-text-hov, #e6edf3);
  text-decoration: none;
  font-size: 12.5px;
  font-weight: 500;
  transition: background 0.1s;
}

.nav-flyout-item:hover {
  background: rgba(255, 255, 255, 0.06);
}

.nav-flyout-item--active {
  background: var(--sb-active-bg, rgba(88, 166, 255, 0.1));
  color: var(--sb-active-tx, #58a6ff);
}

.nav-flyout-icon {
  display: flex;
  width: 14px;
  flex-shrink: 0;
}

/* ── Tooltip (rail mode) ──────────────────────────────────── */
.nav-tooltip {
  position: absolute;
  left: calc(var(--sb-rail, 68px) - 2px);
  top: 50%;
  transform: translateY(-50%);
  background: var(--sb-label-bg, #1c2128);
  color: var(--sb-text-hov, #e6edf3);
  font-size: 12px;
  font-weight: 600;
  padding: 5px 12px;
  border-radius: var(--r-md, 8px);
  border: 1px solid var(--sb-border, rgba(255, 255, 255, 0.1));
  white-space: nowrap;
  pointer-events: none;
  opacity: 0;
  transition: opacity 0.12s;
  z-index: 100;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
}

:global(.sidebar--rail) .nav-item:hover .nav-tooltip,
:global(.sidebar--rail) .nav-group-trigger:hover .nav-tooltip {
  opacity: 1;
}

/* ── Transitions ──────────────────────────────────────────── */
.label-fade-enter-active { transition: opacity 0.15s ease, transform 0.15s ease; }
.label-fade-leave-active { transition: opacity 0.1s ease; position: absolute; }
.label-fade-enter-from { opacity: 0; transform: translateX(-6px); }
.label-fade-leave-to { opacity: 0; }

.flyout-enter-active { transition: opacity 0.12s ease, transform 0.12s cubic-bezier(0.34, 1.2, 0.64, 1); }
.flyout-leave-active { transition: opacity 0.08s ease, transform 0.08s ease; }
.flyout-enter-from,
.flyout-leave-to { opacity: 0; transform: translateX(-4px); }
</style>
