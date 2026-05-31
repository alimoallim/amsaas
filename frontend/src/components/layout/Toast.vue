<template>

  <div
    class="fixed top-5 right-5 z-[100] space-y-3"
  >

    <transition-group
      name="toast"
      tag="div"
    >

      <div
        v-for="toast in toasts"
        :key="toast.id"
        :class="toastClass(toast.type)"
        class="min-w-[320px] rounded-2xl shadow-2xl border p-4 backdrop-blur-xl"
      >

        <div
          class="flex items-start justify-between gap-3"
        >

          <p
            class="font-medium"
          >
            {{ toast.message }}
          </p>

          <button
            @click="remove(toast.id)"
            class="opacity-60 hover:opacity-100"
          >
            ✕
          </button>

        </div>

      </div>

    </transition-group>

  </div>

</template>

<script setup>

import {
  storeToRefs
} from 'pinia'

import {
  useToastStore
} from '@/stores/toast'

const toastStore =
  useToastStore()

const {
  toasts
} = storeToRefs(
  toastStore
)

const {
  remove
} = toastStore

const toastClass = (type) => {

  switch (type) {

    case 'success':
      return 'bg-green-50 border-green-200 text-green-800'

    case 'error':
      return 'bg-red-50 border-red-200 text-red-800'

    case 'warning':
      return 'bg-amber-50 border-amber-200 text-amber-800'

    default:
      return 'bg-white border-slate-200 text-slate-800'
  }
}

</script>

<style scoped>

.toast-enter-active,
.toast-leave-active {

  transition: all 0.25s ease;
}

.toast-enter-from,
.toast-leave-to {

  opacity: 0;

  transform: translateY(-10px);
}

</style>