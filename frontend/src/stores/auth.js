import { defineStore }
from 'pinia'

import api
from '@/services/api'

export const useAuthStore =
defineStore('auth', {

  state: () => ({

    user: null,

    token:
      localStorage.getItem(
        'token'
      ),

    loading: false,
  }),

  getters: {

    isAuthenticated:
      (state) => !!state.token,

    company:
      (state) =>
        state.user?.company || null,

    role:
      (state) =>
        state.user?.role || null,
  },

  actions: {

    /*
    |--------------------------------------------------------------------------
    | Set Authentication
    |--------------------------------------------------------------------------
    */

    setAuth(data) {

      this.token =
        data.token

      this.user =
        data.user

      localStorage.setItem(

        'token',

        data.token
      )
    },

    /*
    |--------------------------------------------------------------------------
    | Fetch Current User
    |--------------------------------------------------------------------------
    */

    async fetchUser() {

      if (!this.token) {

        return
      }

      this.loading = true

      try {

        const response =
          await api.get('/me')

        this.user =
          response.data.user
      }
      catch (error) {

        this.logout()
      }
      finally {

        this.loading = false
      }
    },

    /*
    |--------------------------------------------------------------------------
    | Login
    |--------------------------------------------------------------------------
    */

    async login(credentials) {

      const response =
        await api.post(

          '/login',

          credentials
        )

      this.setAuth(
        response.data
      )

      return response
    },

    /*
    |--------------------------------------------------------------------------
    | Logout
    |--------------------------------------------------------------------------
    */

    async logout() {

      try {

        await api.post(
          '/logout'
        )
      }
      catch (error) {

        console.warn(error)
      }

      this.user = null

      this.token = null

      localStorage.removeItem(
        'token'
      )
    },
  },
})