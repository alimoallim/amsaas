<template>
  <div class="erp-page">
    <ErpPanel>
      <PageHeader :title="title" :description="description" :eyebrow="eyebrow">
        <template #actions>
          <slot name="headerActions">
            <ErpButton v-if="backTo" variant="secondary" :to="backTo">Back</ErpButton>
          </slot>
        </template>
      </PageHeader>
    </ErpPanel>

    <AlertBanner
      v-if="error"
      :message="error"
      variant="error"
      @dismiss="$emit('dismiss-error')"
    />

    <form @submit.prevent="$emit('submit')">
      <ErpPanel :no-padding="true" body-class="">
        <slot />
        <footer
          v-if="showFooter"
          class="flex flex-col-reverse gap-2 border-t border-slate-100 bg-slate-50/80 px-5 py-4 sm:flex-row sm:justify-end"
        >
          <ErpButton v-if="backTo" variant="secondary" :to="backTo">Cancel</ErpButton>
          <ErpButton
            variant="primary"
            native-type="submit"
            :loading="saving"
            :disabled="saving"
          >
            {{ submitLabel }}
          </ErpButton>
        </footer>
      </ErpPanel>
    </form>
  </div>
</template>

<script setup>
import ErpPanel from './ErpPanel.vue'
import PageHeader from './PageHeader.vue'
import ErpButton from './ErpButton.vue'
import AlertBanner from './AlertBanner.vue'

defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '' },
  eyebrow: { type: String, default: '' },
  backTo: { type: [String, Object], default: null },
  error: { type: String, default: '' },
  saving: { type: Boolean, default: false },
  submitLabel: { type: String, default: 'Save' },
  showFooter: { type: Boolean, default: true },
})

defineEmits(['submit', 'dismiss-error'])
</script>
