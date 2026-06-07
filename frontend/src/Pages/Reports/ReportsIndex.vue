<template>
  <div class="erp-page">
    <ErpPanel>
      <PageHeader
        eyebrow="Finance"
        title="Reports"
        description="Financial and operational reports — coming in a later phase."
      />
      <EmptyState
        title="Reports module"
        description="Export packs and scheduled reports will appear here."
      />
    </ErpPanel>
  </div>
</template>

<script setup>
import { PageHeader, ErpPanel, EmptyState } from '@/components/erp'
</script>
