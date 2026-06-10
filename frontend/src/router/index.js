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

import BuildingShow
  from '@/Pages/Buildings/BuildingShow.vue'

/*
|--------------------------------------------------------------------------
| Apartments
|--------------------------------------------------------------------------
*/


import ApartmentsIndex
  from '@/Pages/Apartments/ApartmentsIndex.vue'

import ApartmentShow
  from '@/Pages/Apartments/ApartmentShow.vue'

/*
|--------------------------------------------------------------------------
| Tenants
|--------------------------------------------------------------------------
*/


import TenantsIndex
  from '@/Pages/Tenants/TenantIndex.vue'
import TenantBilling
  from '@/Pages/Tenants/TenantBilling.vue'

/*
|--------------------------------------------------------------------------
| Sales
|--------------------------------------------------------------------------
*/

import BuyerIndex
  from '@/Pages/Sales/BuyerIndex.vue'

import InventoryIndex
  from '@/Pages/Sales/InventoryIndex.vue'

import SaleReservationsIndex
  from '@/Pages/Sales/SaleReservationsIndex.vue'

import SaleAgreementIndex
  from '@/Pages/Sales/SaleAgreementIndex.vue'

import SaleAgreementShow
  from '@/Pages/Sales/SaleAgreementShow.vue'

/*
|--------------------------------------------------------------------------
| Rental Agreements
|--------------------------------------------------------------------------
*/

import RentalAgreementIndex
  from '@/Pages/rentalAgreements/RentalAgreementIndex.vue'

import RentalAgreementShow
  from '@/Pages/rentalAgreements/RentalAgreementShow.vue'
  //meter registraiton
  import MeterIndex
  from '@/Pages/meters/MeterIndex.vue'
import MeterShow
  from '@/Pages/meters/MeterShow.vue'

 


/*
|--------------------------------------------------------------------------
| Meter Readings
|--------------------------------------------------------------------------
*/

import MeterReadingIndex
  from '@/Pages/MeterReading/MeterReadingIndex.vue'

import MeterReadingBulkEntry
  from '@/Pages/MeterReading/MeterReadingBulkEntry.vue'

import MeterReadingApprovalQueue
  from '@/Pages/MeterReading/MeterReadingApprovalQueue.vue'

import MeterReadingShow
  from '@/Pages/MeterReading/MeterReadingShow.vue'

/*
|--------------------------------------------------------------------------
| Financial Modules
|--------------------------------------------------------------------------
*/

import InvoicesIndex
  from '@/Pages/Invoices/InvoicesIndex.vue'
import MonthlyInvoicesWorklist
  from '@/Pages/Invoices/MonthlyInvoicesWorklist.vue'
import InvoiceShow
  from '@/Pages/Invoices/InvoiceShow.vue'
import InvoiceCreate
  from '@/Pages/Invoices/InvoiceCreate.vue'

import PaymentsIndex
  from '@/Pages/Payments/PaymentsIndex.vue'
import PaymentShow
  from '@/Pages/Payments/PaymentShow.vue'

import ChargeApprovalGuide
  from '@/Pages/Charges/ChargeApprovalGuide.vue'

import ChargeTypeIndex
  from '@/Pages/ChargeTypes/ChargeTypeIndex.vue'
import AccountIndex
  from '@/Pages/Accounting/AccountIndex.vue'
import GeneralLedgerIndex
  from '@/Pages/Accounting/GeneralLedgerIndex.vue'
import TrialBalanceIndex
  from '@/Pages/Accounting/TrialBalanceIndex.vue'
import IncomeStatementIndex
  from '@/Pages/Accounting/IncomeStatementIndex.vue'
import BalanceSheetIndex
  from '@/Pages/Accounting/BalanceSheetIndex.vue'
import FinancialAuditIndex
  from '@/Pages/Accounting/FinancialAuditIndex.vue'
import ChargeModelIndex
  from '@/Pages/ChargeModels/ChargeModelIndex.vue'
import ChargeModelShow
  from '@/Pages/ChargeModels/ChargeModelShow.vue'
import ChargeIndex
  from '@/Pages/Charges/ChargeIndex.vue'

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

import FormRouteRedirect
  from '@/components/forms/FormRouteRedirect.vue'

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

      {
        path: '',

        redirect: {
          name: 'Dashboard',
        },
      },

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

        component: FormRouteRedirect,

        meta: {
          title: 'Create Building',
          listRouteName: 'Buildings',
          formMode: 'create',
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

        component: FormRouteRedirect,

        props: true,

        meta: {
          title: 'Edit Building',
          listRouteName: 'Buildings',
          formMode: 'edit',
          formIdParam: 'id',
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

        component: FormRouteRedirect,

        meta: {
          title: 'Create Apartment',
          listRouteName: 'Apartments',
          formMode: 'create',
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

        component: FormRouteRedirect,

        props: true,

        meta: {
          title: 'Edit Apartment',
          listRouteName: 'Apartments',
          formMode: 'edit',
          formIdParam: 'id',
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

        component: FormRouteRedirect,

        meta: {
          title: 'Create Tenant',
          listRouteName: 'Tenants',
          formMode: 'create',
        },
      },

      {
        path: 'tenants/:id/edit',

        name: 'TenantEdit',

        component: FormRouteRedirect,

        props: true,

        meta: {
          title: 'Edit Tenant',
          listRouteName: 'Tenants',
          formMode: 'edit',
          formIdParam: 'id',
        },
      },

      {
        path: 'tenants/:id/billing',

        name: 'TenantBilling',

        component: TenantBilling,

        props: true,

        meta: {
          title: 'Tenant billing',
        },
      },

      /*
      |--------------------------------------------------------------------------
      | Sales
      |--------------------------------------------------------------------------
      */

      {
        path: 'sales/inventory',
        name: 'SalesInventory',
        component: InventoryIndex,
        meta: { title: 'Sales inventory' },
      },

      {
        path: 'sales/reservations',
        name: 'SaleReservations',
        component: SaleReservationsIndex,
        meta: { title: 'Reservations' },
      },

      {
        path: 'sales/contracts',
        name: 'SaleAgreements',
        component: SaleAgreementIndex,
        meta: { title: 'Sale contracts' },
      },

      {
        path: 'sales/contracts/:id',
        name: 'SaleAgreementShow',
        component: SaleAgreementShow,
        props: true,
        meta: { title: 'Sale contract' },
      },

      {
        path: 'sales/buyers',
        name: 'Buyers',
        component: BuyerIndex,
        meta: { title: 'Buyers' },
      },

      {
        path: 'sales/buyers/create',
        name: 'BuyerCreate',
        component: FormRouteRedirect,
        meta: {
          title: 'Create buyer',
          listRouteName: 'Buyers',
          formMode: 'create',
        },
      },

      {
        path: 'sales/buyers/:id/edit',
        name: 'BuyerEdit',
        component: FormRouteRedirect,
        props: true,
        meta: {
          title: 'Edit buyer',
          listRouteName: 'Buyers',
          formMode: 'edit',
          formIdParam: 'id',
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

        component: FormRouteRedirect,

        meta: {
          title: 'Create Rental Agreement',
          listRouteName: 'RentalAgreementIndex',
          formMode: 'create',
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

        component: FormRouteRedirect,

        props: true,

        meta: {
          title: 'Edit Rental Agreement',
          listRouteName: 'RentalAgreementIndex',
          formMode: 'edit',
          formIdParam: 'id',
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

        component: FormRouteRedirect,

        meta: {
          title: 'Register Meter',
          listRouteName: 'Meters',
          formMode: 'create',
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

        component: FormRouteRedirect,

        props: true,

        meta: {
          title: 'Edit Meter',
          listRouteName: 'Meters',
          formMode: 'edit',
          formIdParam: 'id',
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
        path: 'meter-readings/bulk-entry',

        name: 'MeterReadingBulkEntry',

        component: MeterReadingBulkEntry,

        meta: {
          title: 'Bulk Meter Readings',
        },
      },

      {
        path: 'meter-readings/queue',

        name: 'MeterReadingApprovalQueue',

        component: MeterReadingApprovalQueue,

        meta: {
          title: 'Reading Approval Queue',
        },
      },

      {
        path: 'meter-readings/create',

        name: 'MeterReadingCreate',

        component: FormRouteRedirect,

        meta: {
          title: 'Capture Meter Reading',
          listRouteName: 'MeterReadings',
          formMode: 'create',
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

        component: FormRouteRedirect,

        props: true,

        meta: {
          title: 'Edit Meter Reading',
          listRouteName: 'MeterReadings',
          formMode: 'edit',
          formIdParam: 'id',
        },
      },

      /*
      |--------------------------------------------------------------------------
      | Financials
      |--------------------------------------------------------------------------
      */

      {
        path: 'general-ledger',

        name: 'GeneralLedger',

        component: GeneralLedgerIndex,

        meta: {
          title: 'General Ledger',
        },
      },

      {
        path: 'trial-balance',

        name: 'TrialBalance',

        component: TrialBalanceIndex,

        meta: {
          title: 'Trial Balance',
        },
      },

      {
        path: 'income-statement',

        name: 'IncomeStatement',

        component: IncomeStatementIndex,

        meta: {
          title: 'Income Statement',
        },
      },

      {
        path: 'balance-sheet',

        name: 'BalanceSheet',

        component: BalanceSheetIndex,

        meta: {
          title: 'Balance Sheet',
        },
      },

      {
        path: 'financial-audit',

        name: 'FinancialAudit',

        component: FinancialAuditIndex,

        meta: {
          title: 'Financial Audit Log',
        },
      },

      {
        path: 'accounts',

        name: 'Accounts',

        component: AccountIndex,

        meta: {
          title: 'Chart of Accounts',
        },
      },
      {
        path: 'accounts/create',

        name: 'AccountCreate',

        component: FormRouteRedirect,

        meta: {
          title: 'Create Account',
          listRouteName: 'Accounts',
          formMode: 'create',
        },
      },
      {
        path: 'accounts/:id/edit',

        name: 'AccountEdit',

        component: FormRouteRedirect,

        props: true,

        meta: {
          title: 'Edit Account',
          listRouteName: 'Accounts',
          formMode: 'edit',
          formIdParam: 'id',
        },
      },

      {
        path: 'charge-types',

        name: 'ChargeTypes',

        component: ChargeTypeIndex,

        meta: {
          title: 'Charge Types',
        },
      },
      {
        path: 'charge-types/create',

        name: 'ChargeTypeCreate',

        component: FormRouteRedirect,

        meta: {
          title: 'Create Charge Type',
          listRouteName: 'ChargeTypes',
          formMode: 'create',
        },
      },
      {
        path: 'charge-types/:id/edit',

        name: 'ChargeTypeEdit',

        component: FormRouteRedirect,

        props: true,

        meta: {
          title: 'Edit Charge Type',
          listRouteName: 'ChargeTypes',
          formMode: 'edit',
          formIdParam: 'id',
        },
      },

      {
        path: 'charge-models',

        name: 'ChargeModels',

        component: ChargeModelIndex,

        meta: {
          title: 'Charge Models',
        },
      },
      {
        path: 'charge-models/create',

        name: 'ChargeModelCreate',

        component: FormRouteRedirect,

        meta: {
          title: 'Create Charge Model',
          listRouteName: 'ChargeModels',
          formMode: 'create',
        },
      },
      {
        path: 'charge-models/:id',

        name: 'ChargeModelShow',

        component: ChargeModelShow,

        meta: {
          title: 'Charge Model Details',
        },
      },
      {
        path: 'charge-models/:id/edit',

        name: 'ChargeModelEdit',

        component: FormRouteRedirect,

        props: true,

        meta: {
          title: 'Edit Charge Model',
          listRouteName: 'ChargeModels',
          formMode: 'edit',
          formIdParam: 'id',
        },
      },

      {
        path: 'charges',

        name: 'Charges',

        component: ChargeIndex,

        meta: {
          title: 'Utility Charges',
        },
      },

      {
        path: 'charges/approve',

        name: 'ChargeApproval',

        component: ChargeApprovalGuide,

        meta: {
          title: 'Approve Utility Charges',
        },
      },

      {
        path: 'invoices',

        name: 'Invoices',

        component: InvoicesIndex,

        meta: {
          title: 'Billing close',
        },
      },

      {
        path: 'invoices/monthly',

        name: 'MonthlyInvoices',

        component: MonthlyInvoicesWorklist,

        meta: {
          title: 'Monthly invoices',
        },
      },

      {
        path: 'invoices/create',

        name: 'InvoiceCreate',

        component: InvoiceCreate,

        meta: {
          title: 'Create invoice',
        },
      },

      {
        path: 'invoices/:id',

        name: 'InvoiceShow',

        component: InvoiceShow,

        meta: {
          title: 'Invoice detail',
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

      {
        path: 'payments/:id',

        name: 'PaymentShow',

        component: PaymentShow,

        meta: {
          title: 'Payment receipt',
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