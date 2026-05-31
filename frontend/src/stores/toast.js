import { defineStore } from 'pinia'

import { ref } from 'vue'

export const useToastStore = defineStore(
  'toast',
  () => {

    const toasts = ref([])

    const show = (
      message,
      type = 'success'
    ) => {

      const id = Date.now()

      toasts.value.push({

        id,
        message,
        type,
      })

      setTimeout(() => {

        remove(id)

      }, 3500)
    }

    const remove = (id) => {

      toasts.value =
        toasts.value.filter(
          toast => toast.id !== id
        )
    }

    return {

      toasts,

      show,

      remove,
    }
  }
)