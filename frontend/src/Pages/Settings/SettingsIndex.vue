<template>
  <div class="erp-page">
    <ErpPanel>
      <PageHeader
        eyebrow="System"
        title="Settings"
        description="Company preferences and platform configuration."
      />
    </ErpPanel>

    <ErpPanel title="Appearance" subtitle="Choose how AMSAAS looks on your device.">
      <div class="grid gap-3 sm:grid-cols-3">
        <button
          v-for="option in themeOptions"
          :key="option.value"
          type="button"
          class="appearance-option"
          :class="{ 'appearance-option--active': themeStore.mode === option.value }"
          @click="themeStore.setMode(option.value)"
        >
          <span class="appearance-option__icon" v-html="option.icon" />
          <span class="appearance-option__label">{{ option.label }}</span>
          <span class="appearance-option__hint">{{ option.hint }}</span>
        </button>
      </div>

      <p class="mt-4 text-xs text-slate-500 dark:text-slate-400">
        Current theme:
        <span class="font-medium text-slate-700 dark:text-slate-300">{{ themeStore.label }}</span>
      </p>
    </ErpPanel>

    <ErpPanel>
      <EmptyState
        title="More settings coming soon"
        description="Company profile and billing defaults will be configured here."
      />
    </ErpPanel>
  </div>
</template>

<script setup>
import { PageHeader, ErpPanel, EmptyState } from '@/components/erp'
import { useThemeStore } from '@/stores/theme'

const themeStore = useThemeStore()

const themeOptions = [
  {
    value: 'light',
    label: 'Light',
    hint: 'Bright surfaces',
    icon: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/></svg>`,
  },
  {
    value: 'dark',
    label: 'Dark',
    hint: 'Reduced glare',
    icon: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>`,
  },
  {
    value: 'system',
    label: 'System',
    hint: 'Match OS setting',
    icon: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>`,
  },
]
</script>

<style scoped>
.appearance-option {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.35rem;
  padding: 1rem;
  border-radius: 0.75rem;
  border: 1.5px solid var(--erp-border, #e2e8f0);
  background: var(--erp-surface, #fff);
  text-align: left;
  transition: border-color 0.15s ease, background 0.15s ease, box-shadow 0.15s ease;
}

.appearance-option:hover {
  border-color: var(--erp-accent, #4f46e5);
  background: var(--erp-surface-muted, #f8fafc);
}

.appearance-option--active {
  border-color: var(--erp-accent, #4f46e5);
  background: var(--erp-accent-soft, #eef2ff);
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
}

.appearance-option__icon {
  display: flex;
  color: var(--erp-text-muted, #64748b);
}

.appearance-option--active .appearance-option__icon {
  color: var(--erp-accent, #4f46e5);
}

.appearance-option__label {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--erp-text, #0f172a);
}

.appearance-option__hint {
  font-size: 0.75rem;
  color: var(--erp-text-muted, #64748b);
}
</style>
