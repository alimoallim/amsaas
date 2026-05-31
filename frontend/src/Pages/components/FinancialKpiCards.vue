<template>
    <div
        class="
            grid
            grid-cols-1
            md:grid-cols-2
            xl:grid-cols-5
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
                    text-2xl
                    font-bold
                    text-emerald-600
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
    financials: Object
})

const cards = computed(() => [

    {
        label: 'Rent Revenue',
        value:
            props.financials
                ?.rent_revenue || '$0'
    },

    {
        label: 'Utility Revenue',
        value:
            props.financials
                ?.utility_revenue || '$0'
    },

    {
        label: 'Collected',
        value:
            props.financials
                ?.collected || '$0'
    },

    {
        label: 'Outstanding',
        value:
            props.financials
                ?.outstanding || '$0'
    },

    {
        label: 'Collection Rate',
        value:
            props.financials
                ?.collection_rate || '0%'
    }
])
</script>