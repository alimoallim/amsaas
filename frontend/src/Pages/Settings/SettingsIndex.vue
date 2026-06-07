<template>
  <div class="erp-page">
    <ErpPanel>
      <PageHeader
        eyebrow="System"
        title="Settings"
        description="Company preferences and platform configuration."
      />
      <EmptyState
        title="Settings"
        description="Company profile and billing defaults will be configured here."
      />
    </ErpPanel>
  </div>
</template>

<script setup>
import { PageHeader, ErpPanel, EmptyState } from '@/components/erp'
</script>
