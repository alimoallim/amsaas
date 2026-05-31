<template>
    <div
        class="
            flex
            flex-col
            lg:flex-row
            lg:items-center
            lg:justify-between
            mb-8
        "
    >
        <div>
            <h1
                class="
                    text-3xl
                    font-bold
                    text-slate-900
                    dark:text-white
                "
            >
                Dashboard
            </h1>

            <p
                class="
                    mt-2
                    text-slate-500
                    dark:text-slate-400
                "
            >
                Welcome back,
                {{ user.name }}
            </p>
        </div>

        <div
            class="
                mt-4
                lg:mt-0
                text-sm
                text-slate-500
            "
        >
            {{ currentDate }}
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

defineProps({
    user: {
        type: Object,
        default: () => ({})
    }
})

const currentDate = computed(() =>
    new Date().toLocaleDateString()
)
</script>