<template>

  <div
    class="flex items-center gap-2 text-sm text-slate-500"
  >

    <span>
      Dashboard
    </span>

    <span>/</span>

    <span class="text-slate-800 font-medium">
      {{ current }}
    </span>

  </div>

</template>

<script setup>

defineProps({

  current: {
    type: String,
    default: 'Overview',
  },
})

</script>