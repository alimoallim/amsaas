<template>
  <div
    class="auth-shell flex min-h-[100dvh]"
    :data-theme="themeStore.resolved"
  >
    <!-- Brand panel (desktop) -->
    <aside
      class="relative hidden w-[min(400px,36vw)] shrink-0 flex-col justify-between overflow-hidden border-r border-slate-800/50 bg-slate-900 px-8 py-10 xl:px-10 lg:flex"
      aria-hidden="true"
    >
      <div
        class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top_left,rgba(79,70,229,0.18),transparent_55%),radial-gradient(ellipse_at_bottom_right,rgba(14,165,233,0.12),transparent_50%)]"
      />
      <div
        class="pointer-events-none absolute inset-0 opacity-[0.04]"
        style="background-image: linear-gradient(rgba(255,255,255,.6) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.6) 1px, transparent 1px); background-size: 28px 28px;"
      />

      <div class="relative z-10">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-500/20 text-indigo-300 ring-1 ring-indigo-400/30">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
              <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
          </div>
          <div>
            <p class="text-sm font-bold tracking-wide text-white">AMSAAS</p>
            <p class="text-[11px] text-slate-400">Property Platform</p>
          </div>
        </div>

        <div class="mt-14">
          <p
            v-if="eyebrow"
            class="inline-flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wider text-indigo-300/80"
          >
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.5)]" />
            {{ eyebrow }}
          </p>
          <h1 class="mt-4 text-2xl font-semibold leading-tight tracking-tight text-white xl:text-3xl">
            <slot name="headline">{{ headline }}</slot>
          </h1>
          <p class="mt-4 text-sm leading-relaxed text-slate-400">
            <slot name="description">{{ description }}</slot>
          </p>
        </div>
      </div>

      <div v-if="features.length" class="relative z-10 space-y-4">
        <div
          v-for="feature in features"
          :key="feature.title"
          class="flex items-start gap-3"
        >
          <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white/5 text-slate-300 ring-1 ring-white/10">
            <span v-html="feature.icon" />
          </div>
          <div>
            <p class="text-sm font-medium text-slate-200">{{ feature.title }}</p>
            <p class="mt-0.5 text-xs leading-relaxed text-slate-500">{{ feature.sub }}</p>
          </div>
        </div>
      </div>

      <div class="relative z-10 flex flex-wrap items-center gap-2 text-[11px] font-medium text-slate-500">
        <span>Multi-tenant SaaS</span>
        <span class="text-slate-700">·</span>
        <span>API-first</span>
        <span class="text-slate-700">·</span>
        <span>Enterprise ready</span>
      </div>
    </aside>

    <!-- Form panel -->
    <main class="flex min-h-[100dvh] min-w-0 flex-1 flex-col overflow-y-auto bg-slate-50 dark:bg-[var(--erp-bg)]">
      <div class="flex shrink-0 items-center justify-between px-4 py-3 sm:px-6 sm:py-4 lg:px-8">
        <div class="flex min-w-0 items-center gap-2.5 lg:hidden">
          <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-600 text-white">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
              <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
          </div>
          <span class="truncate text-sm font-bold tracking-wide text-slate-900 dark:text-slate-100">AMSAAS</span>
        </div>
        <div class="ml-auto shrink-0">
          <ThemeToggle />
        </div>
      </div>

      <div class="auth-shell-main flex flex-1 flex-col items-stretch justify-start px-4 pb-8 pt-1 sm:items-center sm:justify-center sm:px-6 sm:pb-10 lg:px-8">
        <div class="w-full min-w-0" :class="wide ? 'max-w-2xl' : 'max-w-md'">
          <slot />
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import ThemeToggle from '@/components/common/ThemeToggle.vue'
import { useThemeStore } from '@/stores/theme'

defineProps({
  eyebrow: { type: String, default: '' },
  headline: { type: String, default: '' },
  description: { type: String, default: '' },
  features: { type: Array, default: () => [] },
  wide: { type: Boolean, default: false },
})

const themeStore = useThemeStore()
</script>
