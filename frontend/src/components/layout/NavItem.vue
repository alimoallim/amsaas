<template>

  <router-link
    :to="to"
    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition"
    :class="
      active
        ? 'bg-blue-600/20 text-blue-300'
        : 'text-slate-400 hover:bg-white/5 hover:text-slate-200'
    "
  >

    <slot />

    <span>
      {{ label }}
    </span>

  </router-link>

</template>

<script setup>

defineProps({

  to: {
    type: [String, Object],
    required: true,
  },

  label: {
    type: String,
    required: true,
  },

  active: {
    type: Boolean,
    default: false,
  },
})

</script>