<template>
  <WorklistLayout
    eyebrow="Accounting"
    title="General ledger"
    :count="ledger?.rows?.length"
    description="Per-account journal history with opening balance, running balance, and period totals."
  >
    <template #actions>
      <ErpButton
        variant="ghost"
        size="sm"
        :disabled="!filters.accountId"
        :loading="exporting"
        @click="onExport"
      >
        Export CSV
      </ErpButton>
      <ErpButton
        variant="ghost"
        size="sm"
        :disabled="!filters.accountId"
        :loading="loading"
        @click="loadLedger"
      >
        Refresh
      </ErpButton>
    </template>

    <template #filters>
      <AlertBanner v-if="pageError" class="mb-0" :message="pageError" @dismiss="pageError = ''" />
      <SmartFilterBar :chips="chips" @clear-all="onClearAll" @remove-chip="removeChip">
        <FormField label="Account" required span="2">
          <select v-model="filters.accountId" class="erp-select" @change="loadLedger">
            <option value="">Select account…</option>
            <option v-for="account in accounts" :key="account.id" :value="account.id">
              {{ account.code }} — {{ account.name }}
            </option>
          </select>
        </FormField>
        <FormField label="From">
          <ErpDateInput v-model="filters.from" @change="loadLedger" />
        </FormField>
        <FormField label="To">
          <ErpDateInput v-model="filters.to" @change="loadLedger" />
        </FormField>
      </SmartFilterBar>
    </template>

    <template #kpis>
      <KpiStrip v-if="ledger?.summary" class="mb-0">
        <KpiCard label="Opening balance" :value="formatMoney(ledger.summary.opening_balance)" />
        <KpiCard label="Period debits" :value="formatMoney(ledger.summary.period_debits)" />
        <KpiCard label="Period credits" :value="formatMoney(ledger.summary.period_credits)" />
        <KpiCard
          label="Closing balance"
          :value="formatMoney(ledger.summary.closing_balance)"
          variant="accent"
        />
      </KpiStrip>
    </template>

    <template #table>
      <DataTable
        :columns="columns"
        :rows="ledger?.rows || []"
        :loading="loading"
        empty-title="No ledger activity"
        :empty-description="emptyDescription"
      >
        <template #cell-entry_date="{ row }">
          <span class="text-sm text-slate-700 dark:text-slate-300">{{ row.entry_date || '—' }}</span>
        </template>

        <template #cell-entry_number="{ row }">
          <code class="text-xs text-slate-600 dark:text-slate-400">{{ row.entry_number || '—' }}</code>
        </template>

        <template #cell-debit_amount="{ row }">
          <span class="font-mono text-sm">{{ row.debit_amount > 0 ? formatMoney(row.debit_amount) : '—' }}</span>
        </template>

        <template #cell-credit_amount="{ row }">
          <span class="font-mono text-sm">{{ row.credit_amount > 0 ? formatMoney(row.credit_amount) : '—' }}</span>
        </template>

        <template #cell-running_balance="{ row }">
          <span class="font-mono text-sm font-medium text-slate-900 dark:text-slate-100">
            {{ formatMoney(row.running_balance) }}
          </span>
        </template>
      </DataTable>
    </template>
  </WorklistLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAccounts } from '@/composables/useAccounts'
import { useGeneralLedger } from '@/composables/useGeneralLedger'
import { useSmartFilters } from '@/composables/useSmartFilters'
import {
  WorklistLayout,
  ErpButton,
  DataTable,
  SmartFilterBar,
  FormField,
  ErpDateInput,
  AlertBanner,
  KpiStrip,
  KpiCard,
} from '@/components/erp'

const route = useRoute()
const router = useRouter()
const pageError = ref('')
const exporting = ref(false)

const { items: accounts, fetchList: fetchAccounts } = useAccounts()
const {
  ledger,
  loading,
  filters,
  defaultPeriod,
  fetchLedger,
  exportCsv,
  formatMoney,
} = useGeneralLedger()

const { chips, clearAll, removeChip, bindRoute } = useSmartFilters({
  defaults: { accountId: '', from: '', to: '' },
  labels: {
    accountId: {
      label: 'Account',
      format: (id) => {
        const account = accounts.value.find((a) => a.id === id)
        return account ? `${account.code} ${account.name}` : id
      },
    },
    from: { label: 'From' },
    to: { label: 'To' },
  },
})

const columns = [
  { key: 'entry_date', label: 'Date' },
  { key: 'entry_number', label: 'Entry #', mono: true, hideBelow: 'md' },
  { key: 'description', label: 'Description', emphasis: true, truncate: true },
  { key: 'debit_amount', label: 'Debit', shortLabel: 'DR', align: 'right', mono: true },
  { key: 'credit_amount', label: 'Credit', shortLabel: 'CR', align: 'right', mono: true },
  { key: 'running_balance', label: 'Balance', shortLabel: 'Bal.', align: 'right', mono: true },
]

const emptyDescription = computed(() =>
  filters.accountId
    ? 'No journal lines in this period.'
    : 'Select an account and date range to view ledger activity.',
)

async function loadLedger() {
  if (!filters.accountId) {
    ledger.value = null
    return
  }
  pageError.value = ''
  try {
    await fetchLedger(filters.accountId, {
      from: filters.from,
      to: filters.to,
    })
    syncRoute()
  } catch (e) {
    pageError.value = e.response?.data?.message || 'Could not load general ledger.'
  }
}

async function onExport() {
  if (!filters.accountId) return
  exporting.value = true
  try {
    await exportCsv(filters.accountId, {
      from: filters.from,
      to: filters.to,
    })
  } catch (e) {
    pageError.value = e.response?.data?.message || 'Could not export ledger.'
  } finally {
    exporting.value = false
  }
}

function syncRoute() {
  router.replace({
    query: {
      account_id: filters.accountId || undefined,
      from: filters.from || undefined,
      to: filters.to || undefined,
    },
  })
}

function applyRouteQuery() {
  const period = defaultPeriod()
  filters.accountId = (route.query.account_id || '').toString()
  filters.from = (route.query.from || period.from).toString()
  filters.to = (route.query.to || period.to).toString()
}

function onClearAll() {
  const period = defaultPeriod()
  clearAll()
  filters.accountId = ''
  filters.from = period.from
  filters.to = period.to
  ledger.value = null
  syncRoute()
}

onMounted(async () => {
  const period = defaultPeriod()
  filters.from = period.from
  filters.to = period.to
  bindRoute(route, router, { debounceMs: 0 })
  applyRouteQuery()

  try {
    await fetchAccounts(1)
    if (filters.accountId) {
      await loadLedger()
    }
  } catch (e) {
    pageError.value = e.response?.data?.message || 'Could not load accounts.'
  }
})
</script>
