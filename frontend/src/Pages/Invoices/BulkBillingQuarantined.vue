<template>
  <div class="mx-auto max-w-2xl rounded-xl border border-amber-200 bg-amber-50 p-8 text-slate-800 shadow-sm">
    <h2 class="text-xl font-semibold text-amber-900">Bulk billing — not available yet</h2>
    <p class="mt-3 text-sm leading-relaxed text-amber-950/90">
      Batch invoice generation UI is disabled until Sprint 1 picks a single invoice model and exposes a
      secured <code class="rounded bg-amber-100 px-1">/api/v1/...</code> bulk endpoint. Use
      <strong>Billing Operations</strong> for monthly generation in the meantime.
    </p>
    <p class="mt-2 text-xs text-amber-800/80">
      Ref: <span class="font-mono">DEBT_REGISTER C7</span> · Sprint 1 in
      <span class="font-mono">docs/refactoring/EXECUTION_PLAN.md</span>
    </p>
    <router-link
      :to="{ name: 'Invoices' }"
      class="mt-6 inline-flex rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
    >
      Back to Billing Operations
    </router-link>
  </div>
</template>

<script setup>
</script>
