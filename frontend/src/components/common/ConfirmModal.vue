<template>
  <Teleport to="body">
    <Transition name="erp-fade">
      <div
        v-if="state.open"
        class="fixed inset-0 z-[200] flex items-end justify-center p-0 sm:items-center sm:p-4"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="titleId"
      >
        <div class="absolute inset-0 bg-slate-900/55 backdrop-blur-[2px]" @click="cancel" />

        <div class="relative w-full max-w-md rounded-t-2xl border border-slate-200 bg-white p-6 shadow-2xl sm:rounded-xl">
          <h2 :id="titleId" class="text-lg font-semibold text-slate-900">
            {{ state.title }}
          </h2>
          <p class="mt-2 text-sm leading-relaxed text-slate-600">
            {{ state.message }}
          </p>

          <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button
              type="button"
              class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
              @click="cancel"
            >
              {{ state.cancelLabel }}
            </button>
            <button
              type="button"
              class="rounded-lg px-4 py-2 text-sm font-medium text-white shadow-sm"
              :class="state.variant === 'danger'
                ? 'bg-red-600 hover:bg-red-700'
                : 'bg-indigo-600 hover:bg-indigo-700'"
              @click="confirm"
            >
              {{ state.confirmLabel }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { useId } from 'vue'
import { useConfirmState, resolveConfirm } from '@/composables/useConfirm'

const { state } = useConfirmState()
const titleId = useId()

function confirm() {
  resolveConfirm(true)
}

function cancel() {
  resolveConfirm(false)
}
</script>
