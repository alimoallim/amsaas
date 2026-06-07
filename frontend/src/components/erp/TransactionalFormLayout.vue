<template>
  <div class="erp-page">
    <Breadcrumbs v-if="breadcrumbs?.length" :items="breadcrumbs" />

    <ErpPanel>
      <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 pb-4">
        <PageHeader :title="title" :description="description" :eyebrow="eyebrow" />
        <StatusBadge v-if="state" :status="state" />
      </div>

      <AlertBanner
        v-if="error"
        class="mt-4"
        :message="error"
        variant="error"
        @dismiss="$emit('dismiss-error')"
      />

      <form class="mt-6" @submit.prevent="$emit('primary')">
        <slot />

        <footer
          class="erp-transactional-footer sticky bottom-0 z-10 -mx-5 -mb-5 mt-8 flex flex-col-reverse gap-2 border-t border-slate-200 bg-white/95 px-5 py-4 backdrop-blur sm:flex-row sm:items-center sm:justify-between"
        >
          <div class="flex flex-col-reverse gap-2 sm:flex-row">
            <ErpButton variant="secondary" type="button" @click="$emit('cancel')">
              Cancel
            </ErpButton>
            <ErpButton
              v-if="showSaveDraft"
              variant="ghost"
              type="button"
              :loading="savingDraft"
              :disabled="savingDraft || saving"
              @click="$emit('save-draft')"
            >
              Save draft
            </ErpButton>
          </div>
          <ErpButton
            variant="primary"
            native-type="submit"
            :loading="saving"
            :disabled="saving || savingDraft"
          >
            {{ primaryLabel }}
          </ErpButton>
        </footer>
      </form>
    </ErpPanel>
  </div>
</template>

<script setup>
import Breadcrumbs from './Breadcrumbs.vue'
import ErpPanel from './ErpPanel.vue'
import PageHeader from './PageHeader.vue'
import StatusBadge from './StatusBadge.vue'
import AlertBanner from './AlertBanner.vue'
import ErpButton from './ErpButton.vue'

defineProps({
  breadcrumbs: { type: Array, default: () => [] },
  title: { type: String, required: true },
  description: { type: String, default: '' },
  eyebrow: { type: String, default: '' },
  /** draft | active — Fiori transactional state */
  state: { type: String, default: 'draft' },
  error: { type: String, default: '' },
  primaryLabel: { type: String, default: 'Save' },
  showSaveDraft: { type: Boolean, default: true },
  saving: { type: Boolean, default: false },
  savingDraft: { type: Boolean, default: false },
})

defineEmits(['cancel', 'save-draft', 'primary', 'dismiss-error'])
</script>
