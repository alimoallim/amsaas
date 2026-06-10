<template>
  <section
    class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:shadow-black/20"
    :class="{ 'p-0': noPadding, 'p-4 sm:p-5': !noPadding }"
  >
    <header
      v-if="title || $slots.header"
      class="border-b border-slate-100 px-4 py-3 sm:px-5 dark:border-slate-700"
      :class="{ 'mb-0': noPadding }"
    >
      <slot name="header">
        <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ title }}</h2>
        <p v-if="subtitle" class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ subtitle }}</p>
      </slot>
    </header>
    <div :class="bodyClass">
      <slot />
    </div>
    <footer v-if="$slots.footer" class="border-t border-slate-100 bg-slate-50/80 px-4 py-3 sm:px-5 dark:border-slate-700 dark:bg-slate-800/50">
      <slot name="footer" />
    </footer>
  </section>
</template>

<script setup>
defineProps({
  title: { type: String, default: '' },
  subtitle: { type: String, default: '' },
  noPadding: { type: Boolean, default: false },
  bodyClass: { type: String, default: '' },
})
</script>
