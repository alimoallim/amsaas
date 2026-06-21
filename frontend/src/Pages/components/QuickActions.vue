<template>
  <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-3">
    <router-link
      v-for="action in actions"
      :key="action.to"
      :to="action.to"
      class="group flex min-w-0 items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 transition hover:border-indigo-300 hover:bg-indigo-50/50 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-indigo-500 dark:hover:bg-indigo-950/30"
    >
      <div
        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 transition group-hover:bg-indigo-600 group-hover:text-white dark:bg-indigo-500/10 dark:group-hover:bg-indigo-600"
      >
        <component :is="action.icon" class="h-5 w-5" />
      </div>
      <div class="min-w-0">
        <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">
          {{ action.label }}
        </p>
        <p class="truncate text-xs text-slate-500 dark:text-slate-400">
          {{ action.hint }}
        </p>
      </div>
    </router-link>
  </div>
</template>

<script setup>
import {
  UserPlusIcon,
  DocumentPlusIcon,
  CurrencyDollarIcon,
  ReceiptPercentIcon,
  CpuChipIcon,
  BuildingOffice2Icon,
} from '@heroicons/vue/24/outline'

const actions = [
  { label: 'New tenant', hint: 'Register an occupant', to: '/tenants/create', icon: UserPlusIcon },
  { label: 'New lease', hint: 'Create rental agreement', to: '/rental-agreements/create', icon: DocumentPlusIcon },
  { label: 'Billing ops', hint: 'Invoices and close', to: '/invoices', icon: CurrencyDollarIcon },
  { label: 'Create invoice', hint: 'Manual billing entry', to: '/invoices/create', icon: ReceiptPercentIcon },
  { label: 'Register meter', hint: 'Add utility meter', to: '/meters/create', icon: CpuChipIcon },
  { label: 'Add building', hint: 'Expand portfolio', to: '/buildings/create', icon: BuildingOffice2Icon },
]
</script>
