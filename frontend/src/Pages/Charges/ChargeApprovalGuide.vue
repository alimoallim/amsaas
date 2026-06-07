<template>
  <div class="erp-page space-y-5">
    <ErpPanel>
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Billing pipeline · Step 3</p>
          <h1 class="mt-1 text-xl font-semibold text-slate-900">Approve utility charges</h1>
          <p class="mt-1 max-w-2xl text-sm text-slate-600">
            Charges are created when meter readings are approved. Only <strong>approved</strong> charges
            are included when you compile monthly invoices.
          </p>
        </div>
        <div class="flex flex-wrap gap-2">
          <ErpButton variant="ghost" size="sm" :to="{ name: 'MeterReadings' }">Meter readings</ErpButton>
          <ErpButton
            variant="primary"
            size="sm"
            :disabled="companySummary.pending === 0"
            :to="{ name: 'Invoices' }"
          >
            Compile invoices
          </ErpButton>
        </div>
      </div>

      <ol class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <li
          v-for="(step, index) in steps"
          :key="step.key"
          class="rounded-lg border border-slate-200 bg-slate-50/80 p-4"
        >
          <span
            class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white"
          >
            {{ index + 1 }}
          </span>
          <p class="mt-2 text-sm font-semibold text-slate-900">{{ step.title }}</p>
          <p class="mt-1 text-xs text-slate-600">{{ step.description }}</p>
          <ErpButton
            v-if="step.to"
            variant="ghost"
            size="sm"
            class="mt-3"
            :to="step.to"
          >
            {{ step.action }}
          </ErpButton>
        </li>
      </ol>

      <div class="mt-6 grid gap-3 sm:grid-cols-3">
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
          <p class="text-xs font-medium uppercase text-amber-800">Pending approval</p>
          <p class="mt-1 text-2xl font-bold tabular-nums text-amber-900">{{ companySummary.pending }}</p>
        </div>
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3">
          <p class="text-xs font-medium uppercase text-emerald-800">Ready to invoice</p>
          <p class="mt-1 text-2xl font-bold tabular-nums text-emerald-900">{{ companySummary.approved_ready }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
          <p class="text-xs font-medium uppercase text-slate-500">Already invoiced</p>
          <p class="mt-1 text-2xl font-bold tabular-nums text-slate-800">{{ companySummary.invoiced }}</p>
        </div>
      </div>

      <AlertBanner
        v-if="companySummary.pending > 0"
        variant="warning"
        class="mt-4"
        :dismissible="false"
        :message="`${companySummary.pending} charge(s) must be approved before monthly invoice consolidation.`"
      />
      <AlertBanner
        v-else-if="companySummary.approved_ready > 0"
        variant="success"
        class="mt-4"
        :dismissible="false"
        message="All utility charges are approved. Proceed to Billing operations to compile invoices."
      />
    </ErpPanel>

    <ChargeApprovalTable />
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { ErpButton, ErpPanel, AlertBanner } from '@/components/erp'
import { useCharges } from '@/composables/useCharges'
import ChargeApprovalTable from './ChargeApprovalTable.vue'

const { companySummary, fetchCompanySummary } = useCharges()

const steps = [
  {
    key: 'readings',
    title: 'Capture readings',
    description: 'Enter meter indexes for each unit or tenant meter.',
    action: 'Open readings',
    to: { name: 'MeterReadings' },
  },
  {
    key: 'approve-readings',
    title: 'Approve readings',
    description: 'Verified readings spawn utility charges (pending).',
    action: 'Review readings',
    to: { name: 'MeterReadings' },
  },
  {
    key: 'approve-charges',
    title: 'Approve charges',
    description: 'Confirm amounts before invoicing (this screen).',
    action: null,
    to: null,
  },
  {
    key: 'compile',
    title: 'Compile invoices',
    description: 'Run monthly close to create draft invoices.',
    action: 'Billing ops',
    to: { name: 'Invoices' },
  },
]

onMounted(() => fetchCompanySummary())
</script>
