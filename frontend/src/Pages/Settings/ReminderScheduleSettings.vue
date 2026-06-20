<template>
  <div class="max-w-2xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-6">Reminder Schedule Settings</h1>

    <div v-if="loading" class="text-gray-500">Loading...</div>

    <form v-else @submit.prevent="save" class="space-y-6">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Days Before Due Date</label>
        <p class="text-xs text-gray-500 mb-2">Send reminders this many days before the invoice due date (comma-separated)</p>
        <input
          v-model="daysBefore"
          type="text"
          class="border rounded px-3 py-2 w-full"
          placeholder="e.g. 7,3,1"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Days After Due Date</label>
        <p class="text-xs text-gray-500 mb-2">Send reminders this many days after the invoice due date (comma-separated)</p>
        <input
          v-model="daysAfter"
          type="text"
          class="border rounded px-3 py-2 w-full"
          placeholder="e.g. 1,7,14,30"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Channels</label>
        <div class="flex gap-4">
          <label class="flex items-center gap-2">
            <input v-model="channels" type="checkbox" value="email" />
            Email
          </label>
          <label class="flex items-center gap-2">
            <input v-model="channels" type="checkbox" value="sms" />
            SMS
          </label>
        </div>
      </div>

      <div>
        <label class="flex items-center gap-2">
          <input v-model="isActive" type="checkbox" />
          <span class="text-sm font-medium text-gray-700">Enable automated reminders</span>
        </label>
      </div>

      <div v-if="error" class="text-red-600 text-sm">{{ error }}</div>
      <div v-if="success" class="text-green-600 text-sm">Settings saved successfully.</div>

      <button
        type="submit"
        :disabled="saving"
        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 disabled:opacity-50"
      >
        {{ saving ? 'Saving...' : 'Save Settings' }}
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const loading = ref(true)
const saving = ref(false)
const error = ref('')
const success = ref(false)

const daysBefore = ref('')
const daysAfter = ref('1,7,14,30')
const channels = ref(['email'])
const isActive = ref(true)

function parseInts(str) {
  return str
    .split(',')
    .map(s => parseInt(s.trim(), 10))
    .filter(n => !isNaN(n) && n >= 0)
}

onMounted(async () => {
  try {
    const { data } = await axios.get('/api/v1/settings/reminder-schedule')
    const s = data.data ?? {}
    daysBefore.value = (s.days_before_due ?? []).join(',')
    daysAfter.value = (s.days_after_due ?? [1, 7, 14, 30]).join(',')
    channels.value = s.channels ?? ['email']
    isActive.value = s.is_active ?? true
  } catch (e) {
    error.value = 'Failed to load settings.'
  } finally {
    loading.value = false
  }
})

async function save() {
  saving.value = true
  error.value = ''
  success.value = false
  try {
    await axios.put('/api/v1/settings/reminder-schedule', {
      days_before_due: parseInts(daysBefore.value),
      days_after_due: parseInts(daysAfter.value),
      channels: channels.value,
      is_active: isActive.value,
    })
    success.value = true
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Failed to save settings.'
  } finally {
    saving.value = false
  }
}
</script>
