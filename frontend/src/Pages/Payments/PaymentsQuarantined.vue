<template>
  <div class="mx-auto max-w-2xl rounded-xl border border-slate-200 bg-slate-50 p-8 text-slate-800">
    <h2 class="text-xl font-semibold text-slate-900">Payments — coming in Phase 3</h2>
    <p class="mt-3 text-sm leading-relaxed text-slate-600">
      Payment recording, allocations, and reconciliation APIs are not enabled yet. Use
      <strong>Invoices</strong> for billing operations until the payments module ships.
    </p>
    <router-link
      :to="{ name: 'Invoices' }"
      class="mt-6 inline-flex rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
    >
      Go to Billing Operations
    </router-link>
  </div>
</template>

<script setup>
</script>
