<template>

  <RouterView />

  <AppToast />
  <ConfirmModal />

</template>

<script setup>

import AppToast from '@/components/ui/AppToast.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'

</script>