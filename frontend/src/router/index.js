import {
  createRouter,
  createWebHistory,
} from 'vue-router'

import {
  useAuthStore,
} from '@/stores/auth'

/*
|--------------------------------------------------------------------------
| Layouts
|--------------------------------------------------------------------------
*/

import DashboardLayout
  from '@/layouts/DashboardLayout.vue'

/*
|--------------------------------------------------------------------------
| Root / Auth
|--------------------------------------------------------------------------
*/

import AppRoot
  from '@/Pages/AppRoot.vue'

import Login
  from '@/Pages/Auth/Login.vue'

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

import DashboardHome
  from '@/Pages/Dashboard/DashboardHome.vue'

/*
|--------------------------------------------------------------------------
| Onboarding
|--------------------------------------------------------------------------
*/

import CompanyOnboarding
  from '@/Pages/Onboarding/CompanyOnboarding.vue'

import BuildingOnboarding
  from '@/Pages/Onboarding/BuildingOnboarding.vue'

/*
|--------------------------------------------------------------------------
| Buildings
|--------------------------------------------------------------------------
*/

import BuildingsIndex
  from '@/Pages/Buildings/BuildingsIndex.vue'

import BuildingCreate
  from '@/Pages/Buildings/BuildingCreate.vue'

import BuildingShow
  from '@/Pages/Buildings/BuildingShow.vue'

import BuildingEdit
  from '@/Pages/Buildings/BuildingEdit.vue'

/*
|--------------------------------------------------------------------------
| Apartments
|--------------------------------------------------------------------------
*/


import ApartmentsIndex
  from '@/Pages/Apartments/ApartmentsIndex.vue'

import ApartmentCreate
  from '@/Pages/Apartments/ApartmentCreate.vue'

import ApartmentShow
  from '@/Pages/Apartments/ApartmentShow.vue'

import ApartmentEdit
  from '@/Pages/Apartments/ApartmentEdit.vue'

/*
|--------------------------------------------------------------------------
| Tenants
|--------------------------------------------------------------------------
*/


import TenantsIndex
  from '@/Pages/Tenants/TenantIndex.vue'

import TenantCreate
  from '@/Pages/Tenants/TenantCreate.vue'

import TenantEdit
  from '@/Pages/Tenants/TenantEdit.vue'

/*
|--------------------------------------------------------------------------
| Rental Agreements
|--------------------------------------------------------------------------
*/

import RentalAgreementIndex
  from '@/Pages/rentalAgreements/RentalAgreementIndex.vue'

import RentalAgreementCreate
  from '@/Pages/rentalAgreements/RentalAgreementCreate.vue'
import RentalAgreementShow
  from '@/Pages/rentalAgreements/RentalAgreementShow.vue'
import RentalAgreementEdit
  from '@/Pages/rentalAgreements/RentalAgreementEdit.vue'
  //meter registraiton
  import MeterIndex
  from '@/Pages/meters/MeterIndex.vue'
import MeterCreate
  from '@/Pages/meters/MeterCreate.vue'
import MeterShow
  from '@/Pages/meters/MeterShow.vue'
import MeterEdit
  from '@/Pages/meters/MeterEdit.vue'

 


/*
|--------------------------------------------------------------------------
| Meter Readings
|--------------------------------------------------------------------------
*/

import MeterReadingIndex
  from '@/Pages/MeterReading/MeterReadingIndex.vue'

import MeterReadingCreate
  from '@/Pages/MeterReading/MeterReadingCreate.vue'

import MeterReadingShow
  from '@/Pages/MeterReading/MeterReadingShow.vue'

import MeterReadingEdit
  from '@/Pages/MeterReading/MeterReadingEdit.vue'

/*
|--------------------------------------------------------------------------
| Financial Modules
|--------------------------------------------------------------------------
*/

import InvoicesIndex
  from '@/Pages/Invoices/InvoicesIndex.vue'

import PaymentsIndex
  from '@/Pages/Payments/PaymentsIndex.vue'

/*
|--------------------------------------------------------------------------
| Reports
|--------------------------------------------------------------------------
*/

import ReportsIndex
  from '@/Pages/Reports/ReportsIndex.vue'

/*
|--------------------------------------------------------------------------
| Settings
|--------------------------------------------------------------------------
*/

import SettingsIndex
  from '@/Pages/Settings/SettingsIndex.vue'

/*
|--------------------------------------------------------------------------
| Routes
|--------------------------------------------------------------------------
*/

const routes = [

  /*
  |--------------------------------------------------------------------------
  | Public Routes
  |--------------------------------------------------------------------------
  */

  {
    path: '/',

    name: 'AppRoot',

    component: AppRoot,
  },

  {
    path: '/login',

    name: 'Login',

    component: Login,
  },

  /*
  |--------------------------------------------------------------------------
  | Onboarding
  |--------------------------------------------------------------------------
  */

  {
    path: '/onboarding/company',

    name: 'CompanyOnboarding',

    component: CompanyOnboarding,
  },

  {
    path: '/onboarding/building/:company_id',

    name: 'BuildingOnboarding',

    component: BuildingOnboarding,

    props: true,
  },

  /*
  |--------------------------------------------------------------------------
  | Protected ERP Routes
  |--------------------------------------------------------------------------
  */

  {
    path: '/',

    component: DashboardLayout,

    meta: {
      requiresAuth: true,
    },

    children: [

      /*
      |--------------------------------------------------------------------------
      | Dashboard
      |--------------------------------------------------------------------------
      */

      {
        path: 'dashboard',

        name: 'Dashboard',

        component: DashboardHome,

        meta: {
          title: 'Dashboard',
        },
      },

      /*
      |--------------------------------------------------------------------------
      | Buildings
      |--------------------------------------------------------------------------
      */

      {
        path: 'buildings',

        name: 'Buildings',

        component: BuildingsIndex,

        meta: {
          title: 'Buildings',
        },
      },

      {
        path: 'buildings/create',

        name: 'BuildingCreate',

        component: BuildingCreate,

        meta: {
          title: 'Create Building',
        },
      },

      {
        path: 'buildings/:id',

        name: 'BuildingShow',

        component: BuildingShow,

        props: true,

        meta: {
          title: 'Building Details',
        },
      },

      {
        path: 'buildings/:id/edit',

        name: 'BuildingEdit',

        component: BuildingEdit,

        props: true,

        meta: {
          title: 'Edit Building',
        },
      },

      /*
      |--------------------------------------------------------------------------
      | Apartments
      |--------------------------------------------------------------------------
      */

      {
        path: 'apartments',

        name: 'Apartments',

        component: ApartmentsIndex,

        meta: {
          title: 'Apartments',
        },
      },

      {
        path: 'apartments/create',

        name: 'ApartmentCreate',

        component: ApartmentCreate,

        meta: {
          title: 'Create Apartment',
        },
      },

      {
        path: 'apartments/:id',

        name: 'ApartmentShow',

        component: ApartmentShow,

        props: true,

        meta: {
          title: 'Apartment Details',
        },
      },

      {
        path: 'apartments/:id/edit',

        name: 'ApartmentEdit',

        component: ApartmentEdit,

        props: true,

        meta: {
          title: 'Edit Apartment',
        },
      },

      /*
      |--------------------------------------------------------------------------
      | Tenants
      |--------------------------------------------------------------------------
      */

      {
        path: 'tenants',

        name: 'Tenants',

        component: TenantsIndex,

        meta: {
          title: 'Tenants',
        },
      },

      {
        path: 'tenants/create',

        name: 'TenantCreate',

        component: TenantCreate,

        meta: {
          title: 'Create Tenant',
        },
      },

      {
        path: 'tenants/:id/edit',

        name: 'TenantEdit',

        component: TenantEdit,

        meta: {
          title: 'Edit Tenant',
        },
      },

      /*
      |--------------------------------------------------------------------------
      | Rental Agreements
      |--------------------------------------------------------------------------
      */

      {
        path: 'rental-agreements',

        name: 'RentalAgreementIndex',

        component: RentalAgreementIndex,

        meta: {
          title: 'Rental Agreements',
        },
      },

      {
        path: 'rental-agreements/create',

        name: 'RentalAgreementCreate',

        component: RentalAgreementCreate,

        meta: {
          title: 'Create Rental Agreement',
        },
      },

      {
        path: 'rental-agreements/:id',

        name: 'RentalAgreementShow',

        component: RentalAgreementShow,

        meta: {
          title: 'Rental Agreement Details',
        },
      },

      {
        path: 'rental-agreements/:id/edit',

        name: 'RentalAgreementEdit',

        component: RentalAgreementEdit,

        meta: {
          title: 'Edit Rental Agreement',
        },
      },

      //Meter management
      {
        path: 'meters',

        name: 'Meters',

        component: MeterIndex,

        meta: {

          title: 'Meter Registry',
        },
      },

      {
        path: 'meters/create',

        name: 'MeterCreate',

        component: MeterCreate,

        meta: {

          title: 'Register Meter',
        },
      },

      {
        path: 'meters/:id',

        name: 'MeterShow',

        component: MeterShow,

        meta: {

          title: 'Meter Details',
        },
      },

      {
        path: 'meters/:id/edit',

        name: 'MeterEdit',

        component: MeterEdit,

        meta: {

          title: 'Edit Meter',
        },
      },
    

/*
      |--------------------------------------------------------------------------
      | Meter Readings
      |--------------------------------------------------------------------------
      */

      {
        path: 'meter-readings',

        name: 'MeterReadings',

        component: MeterReadingIndex,

        meta: {
          title: 'Meter Readings',
        },
      },

      {
        path: 'meter-readings/create',

        name: 'MeterReadingCreate',

        component: MeterReadingCreate,

        meta: {
          title: 'Capture Meter Reading',
        },
      },

      {
        path: 'meter-readings/:id',

        name: 'MeterReadingShow',

        component: MeterReadingShow,

        meta: {
          title: 'Meter Reading Details',
        },
      },

      {
        path: 'meter-readings/:id/edit',

        name: 'MeterReadingEdit',

        component: MeterReadingEdit,

        meta: {
          title: 'Edit Meter Reading',
        },
      },

      /*
      |--------------------------------------------------------------------------
      | Financials
      |--------------------------------------------------------------------------
      */

      {
        path: 'invoices',

        name: 'Invoices',

        component: InvoicesIndex,

        meta: {
          title: 'Invoices',
        },
      },

      {
        path: 'payments',

        name: 'Payments',

        component: PaymentsIndex,

        meta: {
          title: 'Payments',
        },
      },

      /*
      |--------------------------------------------------------------------------
      | Reports
      |--------------------------------------------------------------------------
      */

      {
        path: 'reports',

        name: 'Reports',

        component: ReportsIndex,

        meta: {
          title: 'Reports',
        },
      },

      /*
      |--------------------------------------------------------------------------
      | Settings
      |--------------------------------------------------------------------------
      */

      {
        path: 'settings',

        name: 'Settings',

        component: SettingsIndex,

        meta: {
          title: 'Settings',
        },
      },
    ],
  },
]

/*
|--------------------------------------------------------------------------
| Router
|--------------------------------------------------------------------------
*/

const router =
  createRouter({

    history:
      createWebHistory(),

    routes,
  })

/*
|--------------------------------------------------------------------------
| Route Protection
|--------------------------------------------------------------------------
*/

router.beforeEach(

  async (to) => {

    const authStore =
      useAuthStore()

    const token =
      localStorage.getItem(
        'token'
      )

    /*
    |--------------------------------------------------------------------------
    | Protected Routes
    |--------------------------------------------------------------------------
    */

    if (
      to.meta.requiresAuth
    ) {

      if (!token) {

        return '/login'
      }

      if (!authStore.user) {

        try {

          await authStore.fetchUser()
        }

        catch (error) {

          localStorage.removeItem(
            'token'
          )

          return '/login'
        }
      }
    }

    return true
  }
)

export default router