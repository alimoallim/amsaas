<template>

  <div
    class="absolute right-0 top-14 w-80 bg-white border border-slate-200 rounded-2xl shadow-2xl overflow-hidden"
  >

    <div
      class="px-4 py-3 border-b border-slate-100"
    >

      <h3
        class="font-semibold text-slate-800"
      >
        Notifications
      </h3>

    </div>

    <div
      class="max-h-96 overflow-y-auto"
    >

      <div
        v-if="!notifications.length"
        class="p-6 text-center text-sm text-slate-500"
      >
        No notifications yet.
      </div>

      <div
        v-for="item in notifications"
        :key="item.id"
        class="px-4 py-3 border-b border-slate-100 hover:bg-slate-50 transition"
      >

        <p
          class="font-medium text-slate-800"
        >
          {{ item.title }}
        </p>

        <p
          class="text-sm text-slate-500 mt-1"
        >
          {{ item.message }}
        </p>

      </div>

    </div>

  </div>

</template>

<script setup>

import {
  storeToRefs
} from 'pinia'

import {
  useNotificationStore
} from '@/stores/notifications'

const notificationStore =
  useNotificationStore()

const {
  notifications
} = storeToRefs(
  notificationStore
)

</script>