<template>
  <button
    type="button"
    class="theme-toggle"
    :class="{ 'theme-toggle--compact': compact }"
    :aria-label="ariaLabel"
    :title="ariaLabel"
    @click="themeStore.toggle()"
  >
    <svg
      v-if="themeStore.isDark"
      width="15"
      height="15"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="2"
      stroke-linecap="round"
      stroke-linejoin="round"
      aria-hidden="true"
    >
      <circle cx="12" cy="12" r="5" />
      <line x1="12" y1="1" x2="12" y2="3" />
      <line x1="12" y1="21" x2="12" y2="23" />
      <line x1="4.22" y1="4.22" x2="5.64" y2="5.64" />
      <line x1="18.36" y1="18.36" x2="19.78" y2="19.78" />
      <line x1="1" y1="12" x2="3" y2="12" />
      <line x1="21" y1="12" x2="23" y2="12" />
      <line x1="4.22" y1="19.78" x2="5.64" y2="18.36" />
      <line x1="18.36" y1="5.64" x2="19.78" y2="4.22" />
    </svg>
    <svg
      v-else
      width="15"
      height="15"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="2"
      stroke-linecap="round"
      stroke-linejoin="round"
      aria-hidden="true"
    >
      <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
    </svg>
    <span v-if="!compact" class="theme-toggle__label">{{ themeStore.label }}</span>
  </button>
</template>

<script setup>
import { computed } from 'vue'
import { useThemeStore } from '@/stores/theme'

defineProps({
  compact: { type: Boolean, default: false },
})

const themeStore = useThemeStore()

const ariaLabel = computed(() =>
  themeStore.isDark ? 'Switch to light mode' : 'Switch to dark mode',
)
</script>

<style scoped>
.theme-toggle {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  border-radius: 0.5rem;
  border: 1px solid var(--erp-border, #e2e8f0);
  background: var(--erp-surface, #fff);
  color: var(--erp-text-muted, #64748b);
  padding: 0.45rem 0.65rem;
  font-size: 0.75rem;
  font-weight: 500;
  transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.theme-toggle:hover {
  background: var(--erp-surface-muted, #f8fafc);
  color: var(--erp-text, #0f172a);
}

.theme-toggle--compact {
  padding: 0.45rem;
}

.theme-toggle__label {
  text-transform: capitalize;
}
</style>
