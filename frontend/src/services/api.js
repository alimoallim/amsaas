import axios from 'axios'

const api = axios.create({

  baseURL:
    import.meta.env
      .VITE_API_BASE_URL
      || '/api/v1',

  withCredentials: true,

  headers: {

    'X-Requested-With':
      'XMLHttpRequest',

    Accept:
      'application/json',
  },
})

/*
|--------------------------------------------------------------------------
| Request Interceptor
|--------------------------------------------------------------------------
|
| Automatically attach bearer token
|
*/

api.interceptors.request.use(

  (config) => {

    const token =
      localStorage.getItem(
        'token'
      )

    if (token) {

      config.headers.Authorization =
        `Bearer ${token}`
    }

    return config
  },

  (error) => {

    return Promise.reject(
      error
    )
  }
)

/*
|--------------------------------------------------------------------------
| Response Interceptor
|--------------------------------------------------------------------------
*/

api.interceptors.response.use(

  (response) => response,

  (error) => {

    /*
    |--------------------------------------------------------------------------
    | Unauthorized
    |--------------------------------------------------------------------------
    */

    if (
      error.response?.status === 401
    ) {

      console.error(
        'Unauthorized'
      )

      /*
      |--------------------------------------------------------------------------
      | Clear invalid session
      |--------------------------------------------------------------------------
      */

      localStorage.removeItem(
        'token'
      )

      /*
      |--------------------------------------------------------------------------
      | Redirect login
      |--------------------------------------------------------------------------
      */

      if (
        window.location.pathname
        !== '/login'
      ) {

        window.location.href =
          '/login'
      }
    }

    return Promise.reject(
      error
    )
  }
)
api.interceptors.request.use(

  (config) => {

    const token =
      localStorage.getItem('token')

    console.log('TOKEN:', token)

    if (token) {

      config.headers.Authorization =
        `Bearer ${token}`
    }

    console.log(
      'AUTH HEADER:',
      config.headers.Authorization
    )

    return config
  }
)

export default api