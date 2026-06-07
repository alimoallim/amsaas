<template>
  <div ref="rootRef" class="erp-date-input">
    <div class="relative">
      <input
        :id="inputId"
        type="text"
        readonly
        :value="displayText"
        :placeholder="placeholder"
        :disabled="disabled"
        :aria-expanded="open"
        aria-haspopup="dialog"
        class="erp-input erp-date-input__trigger cursor-pointer pr-10"
        :class="inputClass"
        @click="onTriggerClick"
        @keydown.enter.prevent="onTriggerClick"
        @keydown.space.prevent="onTriggerClick"
      />
      <button
        type="button"
        class="erp-date-input__icon-btn"
        :disabled="disabled"
        tabindex="-1"
        aria-label="Open calendar"
        @click="onTriggerClick"
      >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
          />
        </svg>
      </button>
      <button
        v-if="clearable && modelValue && !disabled"
        type="button"
        class="erp-date-input__clear-btn"
        aria-label="Clear date"
        @click.stop="clear"
      >
        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <Teleport to="body">
      <div
        v-if="open"
        ref="panelRef"
        class="erp-date-picker"
        role="dialog"
        aria-label="Choose date"
        :style="panelStyle"
      >
        <div class="erp-date-picker__header">
          <button type="button" class="erp-date-picker__nav" aria-label="Previous month" @click="prevMonth">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <p class="erp-date-picker__title">{{ monthLabel }}</p>
          <button type="button" class="erp-date-picker__nav" aria-label="Next month" @click="nextMonth">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>

        <div class="erp-date-picker__weekdays">
          <span v-for="d in weekdays" :key="d" class="erp-date-picker__weekday">{{ d }}</span>
        </div>

        <div class="erp-date-picker__grid">
          <button
            v-for="cell in calendarCells"
            :key="cell.key"
            type="button"
            class="erp-date-picker__day"
            :class="{
              'erp-date-picker__day--muted': !cell.inMonth,
              'erp-date-picker__day--selected': cell.iso === modelValue,
              'erp-date-picker__day--today': cell.isToday,
              'erp-date-picker__day--disabled': cell.disabled,
            }"
            :disabled="cell.disabled"
            @click="selectDate(cell.iso)"
          >
            {{ cell.day }}
          </button>
        </div>

        <div class="erp-date-picker__footer">
          <button type="button" class="erp-date-picker__action" @click="selectToday">Today</button>
          <button type="button" class="erp-date-picker__action erp-date-picker__action--primary" @click="close">
            Done
          </button>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, ref, watch, nextTick, useId, onMounted, onUnmounted } from 'vue'
import { useDismissablePanel } from '@/composables/useDismissablePanel'

const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: 'Select date' },
  disabled: { type: Boolean, default: false },
  clearable: { type: Boolean, default: true },
  min: { type: String, default: '' },
  max: { type: String, default: '' },
  /** Extra classes on the trigger input (e.g. legacy field-date) */
  inputClass: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue'])

const inputId = useId()
const open = ref(false)
const rootRef = ref(null)
const panelRef = ref(null)
const panelStyle = ref({})
const viewYear = ref(new Date().getFullYear())
const viewMonth = ref(new Date().getMonth())

const weekdays = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa']

const displayText = computed(() => formatDisplay(props.modelValue))

const monthLabel = computed(() => {
  const d = new Date(viewYear.value, viewMonth.value, 1)
  return d.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })
})

const calendarCells = computed(() => {
  const first = new Date(viewYear.value, viewMonth.value, 1)
  const startPad = first.getDay()
  const daysInMonth = new Date(viewYear.value, viewMonth.value + 1, 0).getDate()
  const prevMonthDays = new Date(viewYear.value, viewMonth.value, 0).getDate()
  const cells = []
  const todayIso = toIso(new Date())

  for (let i = startPad - 1; i >= 0; i -= 1) {
    const day = prevMonthDays - i
    const date = new Date(viewYear.value, viewMonth.value - 1, day)
    cells.push(makeCell(date, false, todayIso))
  }

  for (let day = 1; day <= daysInMonth; day += 1) {
    const date = new Date(viewYear.value, viewMonth.value, day)
    cells.push(makeCell(date, true, todayIso))
  }

  let nextDay = 1
  while (cells.length < 42) {
    const date = new Date(viewYear.value, viewMonth.value + 1, nextDay)
    cells.push(makeCell(date, false, todayIso))
    nextDay += 1
  }

  return cells
})

function makeCell(date, inMonth, todayIso) {
  const iso = toIso(date)
  return {
    key: `${iso}-${inMonth}`,
    day: date.getDate(),
    iso,
    inMonth,
    isToday: iso === todayIso,
    disabled: isDisabled(iso),
  }
}

function isDisabled(iso) {
  if (!iso) return true
  if (props.min && iso < props.min) return true
  if (props.max && iso > props.max) return true
  return false
}

function toIso(date) {
  const y = date.getFullYear()
  const m = String(date.getMonth() + 1).padStart(2, '0')
  const d = String(date.getDate()).padStart(2, '0')
  return `${y}-${m}-${d}`
}

function parseIso(value) {
  if (!value || !/^\d{4}-\d{2}-\d{2}$/.test(value)) return null
  const [y, m, d] = value.split('-').map(Number)
  return new Date(y, m - 1, d)
}

function formatDisplay(iso) {
  const date = parseIso(iso)
  if (!date) return ''
  return date.toLocaleDateString('en-US', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}

function syncViewFromValue() {
  const date = parseIso(props.modelValue) || new Date()
  viewYear.value = date.getFullYear()
  viewMonth.value = date.getMonth()
}

function onTriggerClick() {
  if (props.disabled) return
  syncViewFromValue()
  open.value = !open.value
  if (open.value) nextTick(positionPanel)
}

function positionPanel() {
  const el = rootRef.value
  if (!el) return
  const rect = el.getBoundingClientRect()
  const panelWidth = 288
  const panelHeight = 340
  let left = rect.left
  let top = rect.bottom + 6

  if (left + panelWidth > window.innerWidth - 12) {
    left = window.innerWidth - panelWidth - 12
  }
  if (top + panelHeight > window.innerHeight - 12) {
    top = rect.top - panelHeight - 6
  }

  panelStyle.value = {
    position: 'fixed',
    top: `${Math.max(8, top)}px`,
    left: `${Math.max(8, left)}px`,
    width: `${panelWidth}px`,
    zIndex: 250,
  }
}

function selectDate(iso) {
  if (isDisabled(iso)) return
  emit('update:modelValue', iso)
  close()
}

function selectToday() {
  const iso = toIso(new Date())
  if (!isDisabled(iso)) {
    emit('update:modelValue', iso)
    close()
  }
}

function clear() {
  emit('update:modelValue', '')
}

function close() {
  open.value = false
}

function prevMonth() {
  if (viewMonth.value === 0) {
    viewMonth.value = 11
    viewYear.value -= 1
  } else {
    viewMonth.value -= 1
  }
}

function nextMonth() {
  if (viewMonth.value === 11) {
    viewMonth.value = 0
    viewYear.value += 1
  } else {
    viewMonth.value += 1
  }
}

useDismissablePanel(open, rootRef, panelRef, close)

watch(
  () => props.modelValue,
  () => {
    if (open.value) syncViewFromValue()
  }
)

watch(open, (isOpen) => {
  if (isOpen) {
    syncViewFromValue()
    nextTick(positionPanel)
  }
})

function onKeydown(e) {
  if (e.key === 'Escape' && open.value) close()
}

onMounted(() => window.addEventListener('keydown', onKeydown))
onUnmounted(() => window.removeEventListener('keydown', onKeydown))
</script>
