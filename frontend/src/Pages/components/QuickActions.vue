<template>
  <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-6 gap-4">
    <router-link
      v-for="action in actions"
      :key="action.to"
      :to="action.to"
      class="group flex flex-col items-center justify-center gap-3 rounded-2xl bg-white border border-slate-200 p-6 shadow-sm transition-all hover:border-indigo-300 hover:shadow-md dark:bg-slate-900 dark:border-slate-800 dark:hover:border-indigo-500"
    >
      <div class="rounded-full bg-indigo-50 p-3 text-indigo-600 transition-colors group-hover:bg-indigo-600 group-hover:text-white dark:bg-indigo-500/10 dark:group-hover:bg-indigo-600">
        <component :is="action.icon" class="h-6 w-6" />
      </div>
      
      <span class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ action.label }}</span>
    </router-link>
  </div>
</template>

<script setup>
import { defineComponent } from 'vue';
// Example using Heroicons (Install via: npm install @heroicons/vue)
import { 
  UserPlusIcon, DocumentPlusIcon, CurrencyDollarIcon, 
  ReceiptPercentIcon, CpuChipIcon, BuildingOffice2Icon 
} from '@heroicons/vue/24/outline';

const actions = [
  { label: 'New Tenant', to: '/tenants/create', icon: UserPlusIcon },
  { label: 'New Lease', to: '/leases/create', icon: DocumentPlusIcon },
  { label: 'Record Payment', to: '/payments/create', icon: CurrencyDollarIcon },
  { label: 'Create Invoice', to: '/invoices/create', icon: ReceiptPercentIcon },
  { label: 'Register Meter', to: '/meters/create', icon: CpuChipIcon },
  { label: 'Add Building', to: '/buildings/create', icon: BuildingOffice2Icon },
];
</script>