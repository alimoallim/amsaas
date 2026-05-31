import { defineStore }
from 'pinia'

import {
  ref,
  computed
} from 'vue'

export const useNotificationStore =
defineStore(
  'notifications',
  () => {

    /*
    |--------------------------------------------------------------------------
    | State
    |--------------------------------------------------------------------------
    */

    const notifications =
      ref([])

    const loading =
      ref(false)

    /*
    |--------------------------------------------------------------------------
    | Getters
    |--------------------------------------------------------------------------
    */

    const unreadCount =
      computed(() => {

        return notifications.value
          .filter(
            notification => !notification.read
          )
          .length
      })

    /*
    |--------------------------------------------------------------------------
    | Fetch Notifications
    |--------------------------------------------------------------------------
    */

    const fetchNotifications =
      async () => {

        try {

          loading.value = true

          /*
          |--------------------------------------------------------------------------
          | TEMP MOCK DATA
          |--------------------------------------------------------------------------
          |
          | Replace later with:
          |
          | const response =
          | await axios.get('/api/v1/notifications')
          |
          */

          notifications.value = [

            {
              id: 1,
              title: 'New Apartment Added',
              message: 'Apartment A-101 created successfully',
              read: false,
              created_at: '2 min ago',
            },

            {
              id: 2,
              title: 'Invoice Generated',
              message: 'Monthly invoices generated',
              read: false,
              created_at: '10 min ago',
            },

            {
              id: 3,
              title: 'Payment Received',
              message: 'Tenant payment confirmed',
              read: true,
              created_at: '1 hour ago',
            },
          ]

        }
        catch (error) {

          console.error(error)
        }
        finally {

          loading.value = false
        }
      }

    /*
    |--------------------------------------------------------------------------
    | Mark All Read
    |--------------------------------------------------------------------------
    */

    const markAsRead = () => {

      notifications.value =
        notifications.value.map(
          notification => ({

            ...notification,

            read: true,
          })
        )
    }

    /*
    |--------------------------------------------------------------------------
    | Add Notification
    |--------------------------------------------------------------------------
    */

    const addNotification = (
      notification
    ) => {

      notifications.value.unshift({

        id: Date.now(),

        read: false,

        created_at: 'now',

        ...notification,
      })
    }

    /*
    |--------------------------------------------------------------------------
    | Remove Notification
    |--------------------------------------------------------------------------
    */

    const removeNotification = (
      id
    ) => {

      notifications.value =
        notifications.value.filter(
          notification =>
            notification.id !== id
        )
    }

    return {

      notifications,

      loading,

      unreadCount,

      fetchNotifications,

      markAsRead,

      addNotification,

      removeNotification,
    }
  }
)