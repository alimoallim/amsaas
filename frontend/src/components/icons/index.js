import * as HeroIcons
from '@heroicons/vue/24/outline'

/*
|--------------------------------------------------------------------------
| Export ALL Heroicons
|--------------------------------------------------------------------------
*/

export default HeroIcons

export * from '@heroicons/vue/24/outline'

/*
|--------------------------------------------------------------------------
| Compatibility Aliases
|--------------------------------------------------------------------------
*/

/* Navigation */

export const MenuIcon =
  HeroIcons.Bars3Icon

export const CloseIcon =
  HeroIcons.XMarkIcon

export const XIcon =
  HeroIcons.XMarkIcon

export const ChevronIcon =
  HeroIcons.ChevronDownIcon

export const MoreVerticalIcon =
  HeroIcons.EllipsisVerticalIcon

/* Search */

export const SearchIcon =
  HeroIcons.MagnifyingGlassIcon

  

/* Buildings */

export const BuildingIcon =
  HeroIcons.BuildingOfficeIcon

/* Settings */

export const SettingsIcon =
  HeroIcons.Cog6ToothIcon

/* Help */

export const HelpIcon =
  HeroIcons.QuestionMarkCircleIcon

/* Auth */

export const LogoutIcon =
  HeroIcons.ArrowRightOnRectangleIcon

/* Notifications */

export const NotificationIcon =
  HeroIcons.BellIcon

/* Dashboard */

export const DashboardIcon =
  HeroIcons.Squares2X2Icon

/* Users */

export const UserGroupIcon =
  HeroIcons.UsersIcon

/* Documents */



export const InvoiceIcon =
  HeroIcons.DocumentTextIcon

/* Payments */

export const PaymentIcon =
  HeroIcons.CreditCardIcon

/* Reports */

export const ReportIcon =
  HeroIcons.ChartBarIcon

/*
|--------------------------------------------------------------------------
| Custom Components
|--------------------------------------------------------------------------
*/

export {
  default as Logo
} from './Logo.vue'