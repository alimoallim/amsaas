<template>
  <div class="erp-page py-12 text-center text-sm text-slate-500">Opening form…</div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

onMounted(() => {
  const listRouteName = route.meta.listRouteName
  const formMode = route.meta.formMode
  if (!listRouteName || !formMode) {
    router.replace({ name: 'Dashboard' })
    return
  }
  const query = { form: formMode }
  const idParam = route.meta.formIdParam
  if (formMode === 'edit' && idParam && route.params[idParam]) {
    query.id = route.params[idParam]
  }
  router.replace({ name: listRouteName, query })
})
</script>
