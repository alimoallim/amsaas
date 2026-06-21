import BuildingFormContent from '@/components/forms/BuildingFormContent.vue'
import ApartmentFormContent from '@/components/forms/ApartmentFormContent.vue'
import TenantFormContent from '@/components/forms/TenantFormContent.vue'
import ChargeTypeFormContent from '@/components/forms/ChargeTypeFormContent.vue'
import MeterFormContent from '@/components/forms/MeterFormContent.vue'
import MeterReadingFormContent from '@/components/forms/MeterReadingFormContent.vue'
import AccountFormContent from '@/components/forms/AccountFormContent.vue'
import BuyerFormContent from '@/components/forms/BuyerFormContent.vue'

/** @type {Record<string, import('vue').Component>} */
export const TRANSACTIONAL_FORM_COMPONENTS = {
  building: BuildingFormContent,
  apartment: ApartmentFormContent,
  tenant: TenantFormContent,
  'charge-type': ChargeTypeFormContent,
  meter: MeterFormContent,
  'meter-reading': MeterReadingFormContent,
  account: AccountFormContent,
  buyer: BuyerFormContent,
}

/** @type {Record<string, object>} */
export const TRANSACTIONAL_FORM_META = {
  building: {
    listRouteName: 'Buildings',
    showRouteName: 'BuildingShow',
    moduleLabel: 'Buildings',
    createTitle: 'Add building',
    editTitle: 'Edit building',
    createDescription: 'Register a property in your portfolio.',
    editDescription: 'Update building details and operating settings.',
    primaryLabelCreate: 'Create building',
    primaryLabelEdit: 'Save changes',
    eyebrow: 'Property',
  },
  apartment: {
    listRouteName: 'Apartments',
    showRouteName: 'ApartmentShow',
    moduleLabel: 'Apartments',
    createTitle: 'Add unit',
    editTitle: 'Edit unit',
    createDescription: 'Register a rentable or sale unit under a building.',
    editDescription: 'Update unit layout, inventory, and listing settings.',
    primaryLabelCreate: 'Create unit',
    primaryLabelEdit: 'Save changes',
    eyebrow: 'Property',
  },
  tenant: {
    listRouteName: 'Tenants',
    showRouteName: 'TenantShow',
    moduleLabel: 'Tenants',
    createTitle: 'Add tenant',
    editTitle: 'Edit tenant',
    createDescription: 'Register an individual or company lessee.',
    editDescription: 'Update tenant contact and status details.',
    primaryLabelCreate: 'Create tenant',
    primaryLabelEdit: 'Save changes',
    eyebrow: 'Operations',
  },
  'charge-type': {
    listRouteName: 'ChargeTypes',
    moduleLabel: 'Charge types',
    createTitle: 'Add charge type',
    editTitle: 'Edit charge type',
    createDescription: 'Define billing category, GL mapping, and calculation rules.',
    editDescription: 'Update charge type configuration.',
    primaryLabelCreate: 'Create charge type',
    primaryLabelEdit: 'Save changes',
    eyebrow: 'Finance',
  },
  meter: {
    listRouteName: 'Meters',
    showRouteName: 'MeterShow',
    moduleLabel: 'Meters',
    createTitle: 'Register meter',
    editTitle: 'Edit meter',
    createDescription: 'Assign a utility meter to a building or unit.',
    editDescription: 'Update meter assignment and operational details.',
    primaryLabelCreate: 'Register meter',
    primaryLabelEdit: 'Save changes',
    eyebrow: 'Utilities',
  },
  'meter-reading': {
    listRouteName: 'MeterReadings',
    showRouteName: 'MeterReadingShow',
    moduleLabel: 'Meter readings',
    createTitle: 'Capture reading',
    editTitle: 'Edit reading',
    createDescription: 'Record utility consumption for billing.',
    editDescription: 'Correct reading values before approval.',
    primaryLabelCreate: 'Save reading',
    primaryLabelEdit: 'Save changes',
    eyebrow: 'Utilities',
  },
  account: {
    listRouteName: 'Accounts',
    moduleLabel: 'Chart of accounts',
    createTitle: 'Add account',
    editTitle: 'Edit account',
    createDescription: 'Extend your company chart of accounts.',
    editDescription: 'Update account name and status.',
    primaryLabelCreate: 'Create account',
    primaryLabelEdit: 'Save changes',
    eyebrow: 'Finance',
  },
  buyer: {
    listRouteName: 'Buyers',
    moduleLabel: 'Buyers',
    createTitle: 'Add buyer',
    editTitle: 'Edit buyer',
    createDescription: 'Register a property buyer for sale contracts.',
    editDescription: 'Update buyer contact details.',
    primaryLabelCreate: 'Create buyer',
    primaryLabelEdit: 'Save changes',
    eyebrow: 'Sales',
  },
}
