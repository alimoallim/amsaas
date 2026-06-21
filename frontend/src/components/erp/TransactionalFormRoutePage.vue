<template>
  <TransactionalFormLayout
    :eyebrow="config.eyebrow"
    :breadcrumbs="breadcrumbs"
    :title="title"
    :description="description"
    :state="state"
    :primary-label="primaryLabel"
    :show-save-draft="false"
    :saving="saving"
    :error="serverError"
    @primary="onSubmit"
    @cancel="onCancel"
    @dismiss-error="serverError = ''"
  >
    <div @input.capture="markDirty" @change.capture="markDirty">
      <component
        :is="formComponent"
        ref="formRef"
        :entity-id="entityId"
        @saved="onSaved"
        @edit-existing="onEditExisting"
      />
    </div>
  </TransactionalFormLayout>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import TransactionalFormLayout from './TransactionalFormLayout.vue'
import { useTransactionalEntityPage } from '@/composables/useTransactionalEntityPage'
import {
  TRANSACTIONAL_FORM_COMPONENTS,
  TRANSACTIONAL_FORM_META,
} from '@/config/transactionalFormRegistry'

const route = useRoute()
const router = useRouter()

const registryKey = computed(() => route.meta.transactionalForm)
const config = computed(() => TRANSACTIONAL_FORM_META[registryKey.value] || {})
const formComponent = computed(() => TRANSACTIONAL_FORM_COMPONENTS[registryKey.value])

const {
  formRef,
  saving,
  serverError,
  entityId,
  breadcrumbs,
  title,
  description,
  primaryLabel,
  state,
  onSubmit,
  onCancel,
  onSaved,
  markDirty,
} = useTransactionalEntityPage({
  registryKey,
})

function onEditExisting(id) {
  if (registryKey.value === 'meter-reading' && id) {
    router.push({ name: 'MeterReadingEdit', params: { id: String(id) } })
  }
}
</script>
