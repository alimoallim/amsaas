<template>
    <div
        class="
            mb-8
            rounded-2xl
            bg-white
            dark:bg-slate-900
            border
            border-slate-200
            dark:border-slate-800
            p-6
        "
    >
        <h2
            class="
                text-lg
                font-semibold
                mb-4
                text-slate-900
                dark:text-white
            "
        >
            Operational Alerts
        </h2>

        <div
            class="
                space-y-3
            "
        >
            <div
                v-for="alert in alerts"
                :key="alert.id"
                class="
                    p-4
                    rounded-xl
                    bg-red-50
                    dark:bg-red-500/10
                "
            >
                {{ alert.message }}
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    alerts: {
        type: Array,
        default: () => []
    }
})
</script>