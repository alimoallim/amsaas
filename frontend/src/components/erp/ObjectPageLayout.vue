<template>
  <div class="erp-page">
    <Breadcrumbs v-if="breadcrumbs?.length" :items="breadcrumbs" />

    <ErpPanel>
      <ObjectPageHeader
        :title="title"
        :subtitle="subtitle"
        :status="status"
        :status-label="statusLabel"
        :attributes="attributes"
      >
        <template v-if="$slots.actions" #actions>
          <slot name="actions" />
        </template>
      </ObjectPageHeader>

      <nav
        v-if="tabs?.length"
        class="erp-tabs -mx-1 mt-2 flex gap-1 overflow-x-auto border-b border-slate-200 dark:border-slate-700"
        aria-label="Object sections"
      >
        <button
          v-for="tab in tabs"
          :key="tab.id"
          type="button"
          class="erp-tab whitespace-nowrap px-4 py-2.5 text-sm font-medium transition"
          :class="
            activeTab === tab.id
              ? 'border-b-2 border-indigo-600 text-indigo-700 dark:border-indigo-400 dark:text-indigo-300'
              : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100'
          "
          :aria-selected="activeTab === tab.id"
          role="tab"
          @click="activeTab = tab.id"
        >
          {{ tab.label }}
        </button>
      </nav>

      <div class="erp-tab-panels mt-6">
        <div
          v-for="tab in tabs"
          :key="tab.id"
          v-show="!tabs.length || activeTab === tab.id"
          class="erp-tab-panel"
          role="tabpanel"
        >
          <slot :name="tab.id" />
        </div>
        <div v-if="!tabs?.length" class="erp-tab-panel">
          <slot />
        </div>
      </div>
    </ErpPanel>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import Breadcrumbs from './Breadcrumbs.vue'
import ErpPanel from './ErpPanel.vue'
import ObjectPageHeader from './ObjectPageHeader.vue'

const props = defineProps({
  breadcrumbs: { type: Array, default: () => [] },
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  status: { type: String, default: '' },
  statusLabel: { type: String, default: '' },
  attributes: { type: Array, default: () => [] },
  /** @type {{ id: string, label: string }[]} */
  tabs: { type: Array, default: () => [] },
  initialTab: { type: String, default: '' },
})

const activeTab = ref(props.initialTab || props.tabs[0]?.id || '')

watch(
  () => props.tabs,
  (t) => {
    if (t?.length && !t.some((tab) => tab.id === activeTab.value)) {
      activeTab.value = t[0].id
    }
  },
  { immediate: true }
)
</script>
