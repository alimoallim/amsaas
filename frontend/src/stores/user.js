import { defineStore }
from 'pinia'

import {
  ref,
  computed
} from 'vue'

import axios from 'axios'

export const useUserStore =
defineStore(
  'user',
  () => {

    /*
    |--------------------------------------------------------------------------
    | State
    |--------------------------------------------------------------------------
    */

    const user =
      ref(null)

    const loading =
      ref(false)

    const authenticated =
      ref(false)

    /*
    |--------------------------------------------------------------------------
    | Getters
    |--------------------------------------------------------------------------
    */

    const initials =
      computed(() => {

        if (!user.value?.name) {

          return 'AU'
        }

        return user.value.name
          .split(' ')
          .map(word => word[0])
          .join('')
          .substring(0, 2)
          .toUpperCase()
      })

    /*
    |--------------------------------------------------------------------------
    | Actions
    |--------------------------------------------------------------------------
    */

    const setUser = (
      userData
    ) => {

      user.value = userData

      authenticated.value = true
    }

    /*
    |--------------------------------------------------------------------------
    | Fetch Current User
    |--------------------------------------------------------------------------
    */

    const fetchCurrentUser =
      async () => {

        try {

          loading.value = true

          /*
          |--------------------------------------------------------------------------
          | TEMP MOCK USER
          |--------------------------------------------------------------------------
          |
          | Replace later with:
          |
          | const response =
          | await axios.get('/api/user')
          |
          */

          const mockUser = {

            id: 1,

            name: 'Admin User',

            email:
              'admin@example.com',

            role:
              'Administrator',
          }

          user.value =
            mockUser

          authenticated.value =
            true

        }
        catch (error) {

          console.error(
            error
          )

          authenticated.value =
            false
        }
        finally {

          loading.value =
            false
        }
      }

    /*
    |--------------------------------------------------------------------------
    | Logout
    |--------------------------------------------------------------------------
    */

    const logout = () => {

      user.value = null

      authenticated.value =
        false

      localStorage.removeItem(
        'token'
      )
    }

    return {

      user,

      loading,

      authenticated,

      initials,

      setUser,

      fetchCurrentUser,

      logout,
    }
  }
)