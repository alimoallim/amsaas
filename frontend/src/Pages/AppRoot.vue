<template>

  <div
    class="min-h-screen flex items-center justify-center bg-slate-50"
  >

    <div class="text-center">

      <div
        class="w-16 h-16 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin mx-auto"
      ></div>

      <p
        class="mt-6 text-slate-600 font-medium"
      >
        Loading AMSAAS...
      </p>

    </div>

  </div>

</template>

<script setup>

import {
  onMounted,
} from 'vue'

import {
  useRouter,
} from 'vue-router'

import api
from '@/services/api'

const router =
  useRouter()

const bootstrap =
async () => {

  try {

    /*
    |--------------------------------------------------------------------------
    | Existing Session?
    |--------------------------------------------------------------------------
    */

    const token =
      localStorage.getItem(
        'token'
      )

    if (token) {

      try {

        await api.get('/me')

        return router.replace(
          '/dashboard'
        )
      }
      catch (e) {

        localStorage.removeItem(
          'token'
        )
      }
    }

    /*
    |--------------------------------------------------------------------------
    | Bootstrap Status
    |--------------------------------------------------------------------------
    */

    const response =
      await api.get(
        '/system/bootstrap-status'
      )

    if (
      response.data.has_company
    ) {

      router.replace(
        '/login'
      )
    }
    else {

      router.replace(
        '/onboarding/company'
      )
    }

  }
  catch (error) {

    console.error(error)
  }
}

onMounted(() => {

  bootstrap()
})

</script>