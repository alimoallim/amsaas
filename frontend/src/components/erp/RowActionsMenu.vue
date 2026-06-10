<template>
  <div ref="rootRef" class="relative inline-flex justify-end" @click.stop>
    <button
      type="button"
      class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50 hover:text-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-slate-100"
      :aria-expanded="open"
      aria-haspopup="menu"
      aria-label="Row actions"
      @click="toggle"
    >
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
        <circle cx="12" cy="5" r="1.5" />
        <circle cx="12" cy="12" r="1.5" />
        <circle cx="12" cy="19" r="1.5" />
      </svg>
    </button>

    <Teleport to="body">
      <div
        v-if="open && visibleActions.length"
        ref="menuRef"
        class="erp-row-actions-menu z-[230] min-w-[10rem] rounded-lg border border-slate-200 bg-white py-1 shadow-lg dark:border-slate-600 dark:bg-slate-900"
        :style="menuStyle"
        role="menu"
      >
        <template v-for="item in visibleActions" :key="item.key">
          <RouterLink
            v-if="item.to"
            :to="item.to"
            class="erp-row-actions-menu__item block w-full text-left text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800"
            :class="itemClass(item)"
            role="menuitem"
            @click="close"
          >
            {{ item.label }}
          </RouterLink>
          <button
            v-else
            type="button"
            class="erp-row-actions-menu__item w-full text-left text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800"
            :class="itemClass(item)"
            :disabled="item.disabled"
            role="menuitem"
            @click="onItemClick(item)"
          >
            {{ item.label }}
          </button>
        </template>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, ref, nextTick, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { useDismissablePanel } from '@/composables/useDismissablePanel'

const props = defineProps({
  /** @type {Array<{ key: string, label: string, to?: object, onClick?: Function, variant?: string, disabled?: boolean, hidden?: boolean }>} */
  actions: { type: Array, default: () => [] },
})

const emit = defineEmits(['action'])

const open = ref(false)
const rootRef = ref(null)
const menuRef = ref(null)
const menuStyle = ref({})

const visibleActions = computed(() =>
  props.actions.filter((a) => a && !a.hidden && a.label)
)

function itemClass(item) {
  return {
    'text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40': item.variant === 'danger',
    'text-indigo-700 hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-indigo-950/40': item.variant === 'primary',
    'text-emerald-700 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-950/40': item.variant === 'success',
    'text-amber-700 hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-950/40': item.variant === 'warning',
    'opacity-40 cursor-not-allowed': item.disabled,
  }
}

function toggle() {
  if (!visibleActions.value.length) return
  open.value = !open.value
  if (open.value) nextTick(positionMenu)
}

function close() {
  open.value = false
}

function positionMenu() {
  const el = rootRef.value
  if (!el) return
  const rect = el.getBoundingClientRect()
  const menuWidth = 176
  let left = rect.right - menuWidth
  if (left < 8) left = 8
  let top = rect.bottom + 4
  const estimatedHeight = visibleActions.value.length * 36 + 8
  if (top + estimatedHeight > window.innerHeight - 8) {
    top = rect.top - estimatedHeight - 4
  }
  menuStyle.value = {
    position: 'fixed',
    top: `${Math.max(8, top)}px`,
    left: `${left}px`,
    minWidth: `${menuWidth}px`,
  }
}

function onItemClick(item) {
  if (item.disabled) return
  item.onClick?.()
  emit('action', item.key, item)
  close()
}

useDismissablePanel(open, rootRef, menuRef, close)

watch(open, (isOpen) => {
  if (isOpen) nextTick(positionMenu)
})
</script>

<style scoped>
.erp-row-actions-menu__item {
  display: block;
  width: 100%;
  padding: 0.5rem 0.75rem;
  font-size: 0.8125rem;
  font-weight: 500;
  transition: background-color 0.15s;
}
</style>
