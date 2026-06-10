<template>

  <div
    class="toast-stack fixed top-5 right-5 z-[100] w-[min(100%,20rem)] space-y-3 sm:w-auto"
  >

    <transition-group
      name="toast"
      tag="div"
    >

      <div
        v-for="toast in toastStore.toasts"
        :key="toast.id"
        :class="toastClass(toast.type)"
        class="w-full rounded-2xl border p-4 shadow-2xl backdrop-blur-xl sm:min-w-[18rem]"
      >

        <div
          class="flex items-start justify-between gap-3"
        >

          <div>

            <p
              class="font-semibold"
            >
              {{ toast.message }}
            </p>

          </div>

          <button
            @click="toastStore.remove(toast.id)"
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
  useToastStore
} from '@/stores/toast'

const toastStore =
  useToastStore()

const toastClass = (type) => {
  switch (type) {
    case 'success':
      return 'bg-green-50 border-green-200 text-green-800 dark:bg-green-950/50 dark:border-green-800/50 dark:text-green-300'
    case 'error':
      return 'bg-red-50 border-red-200 text-red-800 dark:bg-red-950/50 dark:border-red-800/50 dark:text-red-300'
    case 'warning':
      return 'bg-amber-50 border-amber-200 text-amber-800 dark:bg-amber-950/50 dark:border-amber-800/50 dark:text-amber-300'
    default:
      return 'bg-white border-slate-200 text-slate-800 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200'
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