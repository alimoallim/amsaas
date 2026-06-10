<template>
  <ErpPanel :no-padding="true">
    <div class="erp-table-scroll overflow-x-auto">
      <table
        class="divide-y divide-slate-200 text-left text-sm dark:divide-slate-700"
        :class="tableMinWidth"
      >
        <thead class="bg-slate-50/90 dark:bg-slate-800/80">
          <tr>
            <th v-if="selectable" scope="col" class="w-10 px-4 py-3 sm:px-5">
              <span class="sr-only">Select</span>
            </th>
            <th
              v-for="col in columns"
              :key="col.key"
              scope="col"
              class="px-3 py-3 text-[10px] font-semibold uppercase tracking-wide text-slate-600 sm:px-5 sm:text-xs dark:text-slate-400"
              :class="[
                col.align === 'right' ? 'text-right' : 'text-left',
                col.type === 'actions' ? 'w-12' : '',
                columnVisibilityClass(col),
              ]"
            >
              {{ col.shortLabel || col.label }}
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 bg-white dark:divide-slate-800 dark:bg-slate-900">
          <tr v-if="loading">
            <td :colspan="colSpan">
              <TableSkeleton :rows="skeletonRows" />
            </td>
          </tr>
          <tr v-else-if="!rows?.length">
            <td :colspan="colSpan">
              <EmptyState :title="emptyTitle" :description="emptyDescription">
                <template v-if="$slots.emptyAction" #action>
                  <slot name="emptyAction" />
                </template>
              </EmptyState>
            </td>
          </tr>
          <tr
            v-else
            v-for="(row, index) in rows"
            :key="rowKey ? row[rowKey] : index"
            class="erp-table-row-hover cursor-pointer"
            :class="{ 'bg-indigo-50/60 dark:bg-indigo-950/40': isRowSelected(row) }"
            @click="onRowClick(row, $event)"
          >
            <td v-if="selectable" class="px-4 py-3.5 sm:px-5" @click.stop>
              <input
                type="checkbox"
                class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800"
                :checked="isRowSelected(row)"
                :aria-label="`Select row ${row[rowKey]}`"
                @change="toggleRow(row)"
              />
            </td>
            <td
              v-for="col in columns"
              :key="col.key"
              class="erp-table-cell px-3 py-3 text-slate-700 sm:px-5 sm:py-3.5 dark:text-slate-300"
              :class="[...cellClasses(col), columnVisibilityClass(col)]"
            >
              <slot :name="`cell-${col.key}`" :row="row" :value="row[col.key]">
                {{ row[col.key] }}
              </slot>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <PaginationBar
      v-if="meta"
      :meta="meta"
      :loading="loading"
      @page-change="$emit('page-change', $event)"
    />
  </ErpPanel>
</template>

<script setup>
import { computed } from 'vue'
import ErpPanel from './ErpPanel.vue'
import EmptyState from './EmptyState.vue'
import TableSkeleton from './TableSkeleton.vue'
import PaginationBar from './PaginationBar.vue'

const props = defineProps({
  columns: { type: Array, required: true },
  rows: { type: Array, default: () => [] },
  rowKey: { type: String, default: 'id' },
  loading: { type: Boolean, default: false },
  meta: { type: Object, default: null },
  emptyTitle: { type: String, default: 'No records found' },
  emptyDescription: { type: String, default: 'Try adjusting filters or create a new record.' },
  skeletonRows: { type: Number, default: 5 },
  selectable: { type: Boolean, default: false },
  /** Single selection id when selectable (legacy) */
  selectedId: { type: [String, Number], default: null },
  /** Multi-select ids when selectable + multiSelect */
  selectedIds: { type: Array, default: () => [] },
  multiSelect: { type: Boolean, default: false },
})

const emit = defineEmits(['page-change', 'row-click', 'update:selectedId', 'update:selectedIds'])

const colSpan = computed(() => props.columns.length + (props.selectable ? 1 : 0))

const tableMinWidth = computed(() => {
  const count = props.columns.length
  if (count >= 7) return 'min-w-[52rem] w-full'
  if (count >= 5) return 'min-w-[40rem] w-full'
  if (count >= 4) return 'min-w-[32rem] w-full'
  return 'min-w-full w-full'
})

const columnVisibilityClass = (col) => {
  const map = {
    sm: 'hidden sm:table-cell',
    md: 'hidden md:table-cell',
    lg: 'hidden lg:table-cell',
    xl: 'hidden xl:table-cell',
  }
  return col.hideBelow ? map[col.hideBelow] || '' : ''
}

function isRowSelected(row) {
  const id = row[props.rowKey]
  if (props.multiSelect) {
    return props.selectedIds.includes(id)
  }
  return props.selectedId != null && id === props.selectedId
}

function onRowClick(row, event) {
  if (event.target.closest('a, button, input, select, textarea')) return
  emit('row-click', row)
}

function toggleRow(row) {
  const id = row[props.rowKey]
  if (props.multiSelect) {
    const next = isRowSelected(row)
      ? props.selectedIds.filter((x) => x !== id)
      : [...props.selectedIds, id]
    emit('update:selectedIds', next)
    return
  }
  emit('update:selectedId', isRowSelected(row) ? null : id)
  emit('row-click', row)
}

function cellClasses(col) {
  return [
    col.align === 'right' ? 'text-right' : 'text-left',
    col.mono ? 'font-mono text-xs' : '',
    col.emphasis ? 'font-medium text-slate-900 dark:text-slate-100' : '',
    col.wrap ? 'whitespace-normal' : 'whitespace-nowrap',
    col.truncate ? 'max-w-[12rem] truncate' : '',
    col.type === 'actions' ? 'w-12 min-w-[3rem]' : '',
    col.class,
  ]
}
</script>
