<template>
    <div
        class="
            grid
            grid-cols-1
            md:grid-cols-2
            xl:grid-cols-6
            gap-5
            mb-8
        "
    >
        <div
            v-for="card in cards"
            :key="card.label"
            class="
                rounded-2xl
                bg-white
                dark:bg-slate-900
                border
                border-slate-200
                dark:border-slate-800
                p-5
                shadow-sm
            "
        >
            <p
                class="
                    text-sm
                    text-slate-500
                "
            >
                {{ card.label }}
            </p>

            <h3
                class="
                    mt-3
                    text-3xl
                    font-bold
                    text-slate-900
                    dark:text-white
                "
            >
                {{ card.value }}
            </h3>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    portfolio: Object
})

const cards = computed(() => [

    {
        label: 'Buildings',
        value:
            props.portfolio
                ?.buildings || 0
    },

    {
        label: 'Apartments',
        value:
            props.portfolio
                ?.apartments || 0
    },

    {
        label: 'Occupied',
        value:
            props.portfolio
                ?.occupied || 0
    },

    {
        label: 'Vacant',
        value:
            props.portfolio
                ?.vacant || 0
    },

    {
        label: 'Tenants',
        value:
            props.portfolio
                ?.tenants || 0
    },

    {
        label: 'Leases',
        value:
            props.portfolio
                ?.leases || 0
    }
])
</script>