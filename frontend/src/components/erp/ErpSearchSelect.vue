<template>
  <div ref="rootRef" class="erp-search-select" :class="{ 'erp-search-select--open': open }">
    <div class="relative">
      <input
        :id="inputId"
        type="text"
        :value="triggerText"
        :placeholder="placeholder"
        :disabled="disabled"
        :aria-expanded="open"
        aria-haspopup="listbox"
        autocomplete="off"
        class="erp-input erp-search-select__trigger cursor-pointer pr-9"
        :class="[inputClass, { 'erp-input--error': hasError }]"
        readonly
        @click="openPanel"
        @keydown.enter.prevent="openPanel"
        @keydown.space.prevent="openPanel"
        @keydown.down.prevent="openPanel"
      />
      <button
        type="button"
        class="erp-search-select__chevron"
        :disabled="disabled"
        tabindex="-1"
        aria-label="Open list"
        @click="openPanel"
      >
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="6 9 12 15 18 9" />
        </svg>
      </button>
      <button
        v-if="clearable && modelValue != null && modelValue !== '' && !disabled"
        type="button"
        class="erp-search-select__clear"
        aria-label="Clear selection"
        @click.stop="clear"
      >
        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <Teleport to="body">
      <div
        v-if="open"
        ref="panelRef"
        class="erp-search-select__panel"
        role="listbox"
        :style="panelStyle"
      >
        <div class="border-b border-slate-100 p-2">
          <input
            ref="searchRef"
            v-model="query"
            type="search"
            class="erp-input py-1.5 text-sm"
            :placeholder="searchPlaceholder"
            autocomplete="off"
            @input="onQueryInput"
            @keydown.escape.prevent="close"
          />
        </div>
        <div class="max-h-56 overflow-y-auto py-1">
          <p v-if="loading" class="px-3 py-4 text-center text-xs text-slate-500">Loading…</p>
          <p v-else-if="!filteredOptions.length" class="px-3 py-4 text-center text-xs text-slate-500">
            {{ emptyText }}
          </p>
          <button
            v-for="opt in filteredOptions"
            :key="opt.value"
            type="button"
            role="option"
            class="erp-search-select__option"
            :class="{ 'erp-search-select__option--active': String(opt.value) === String(modelValue) }"
            :aria-selected="String(opt.value) === String(modelValue)"
            @click="select(opt)"
          >
            <span class="erp-search-select__option-label">{{ opt.label }}</span>
            <span v-if="opt.hint" class="erp-search-select__option-hint">{{ opt.hint }}</span>
          </button>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, ref, watch, nextTick, onBeforeUnmount } from 'vue'
import { useDismissablePanel } from '@/composables/useDismissablePanel'

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  options: { type: Array, default: () => [] },
  placeholder: { type: String, default: 'Select…' },
  searchPlaceholder: { type: String, default: 'Search…' },
  disabled: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  clearable: { type: Boolean, default: true },
  emptyText: { type: String, default: 'No matches' },
  inputClass: { type: String, default: '' },
  hasError: { type: Boolean, default: false },
  /** When true, parent handles filtering via @search; options list is used as-is */
  remote: { type: Boolean, default: false },
  inputId: { type: String, default: undefined },
})

const emit = defineEmits(['update:modelValue', 'search'])

const open = ref(false)
const query = ref('')
const rootRef = ref(null)
const panelRef = ref(null)
const searchRef = ref(null)
const panelStyle = ref({})

const selectedOption = computed(() =>
  props.options.find((o) => String(o.value) === String(props.modelValue))
)

const triggerText = computed(() => {
  if (open.value && !props.remote) return query.value
  if (selectedOption.value) return selectedOption.value.label
  return ''
})

const filteredOptions = computed(() => {
  if (props.remote) return props.options
  const q = query.value.trim().toLowerCase()
  if (!q) return props.options
  return props.options.filter((o) => {
    const hay = `${o.label} ${o.hint || ''}`.toLowerCase()
    return hay.includes(q)
  })
})

function positionPanel() {
  const el = rootRef.value
  if (!el) return
  const rect = el.getBoundingClientRect()
  const width = Math.max(rect.width, 240)
  let top = rect.bottom + 4
  const maxHeight = 280
  if (top + maxHeight > window.innerHeight - 8) {
    top = Math.max(8, rect.top - maxHeight - 4)
  }
  panelStyle.value = {
    position: 'fixed',
    top: `${top}px`,
    left: `${rect.left}px`,
    width: `${width}px`,
    zIndex: 240,
  }
}

function openPanel() {
  if (props.disabled) return
  open.value = true
  query.value = ''
  nextTick(() => {
    positionPanel()
    searchRef.value?.focus()
  })
}

function close() {
  open.value = false
  query.value = ''
}

function select(opt) {
  emit('update:modelValue', opt.value)
  close()
}

function clear() {
  emit('update:modelValue', '')
  close()
}

let searchDebounce = null
function onQueryInput() {
  if (!props.remote) return
  clearTimeout(searchDebounce)
  searchDebounce = setTimeout(() => emit('search', query.value.trim()), 280)
}

watch(open, (isOpen) => {
  if (isOpen) nextTick(positionPanel)
})

useDismissablePanel(open, rootRef, panelRef, close)

onBeforeUnmount(() => {
  open.value = false
  clearTimeout(searchDebounce)
})
</script>
