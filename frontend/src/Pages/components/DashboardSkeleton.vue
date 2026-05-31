<template>
    <div
        class="
            animate-pulse
            space-y-6
        "
    >
        <!-- Header -->

        <div
            class="
                flex
                flex-col
                lg:flex-row
                lg:items-center
                lg:justify-between
                gap-4
            "
        >
            <div>
                <div
                    class="
                        h-8
                        w-64
                        rounded-lg
                        bg-slate-200
                        dark:bg-slate-800
                    "
                />

                <div
                    class="
                        mt-3
                        h-4
                        w-40
                        rounded-lg
                        bg-slate-200
                        dark:bg-slate-800
                    "
                />
            </div>

            <div
                class="
                    h-10
                    w-32
                    rounded-xl
                    bg-slate-200
                    dark:bg-slate-800
                "
            />
        </div>

        <!-- Alerts -->

        <div
            class="
                rounded-2xl
                border
                border-slate-200
                dark:border-slate-800
                bg-white
                dark:bg-slate-900
                p-6
            "
        >
            <div
                class="
                    h-5
                    w-48
                    rounded-lg
                    bg-slate-200
                    dark:bg-slate-800
                    mb-4
                "
            />

            <div
                v-for="i in 3"
                :key="i"
                class="
                    h-12
                    rounded-xl
                    bg-slate-200
                    dark:bg-slate-800
                    mb-3
                "
            />
        </div>

        <!-- Executive KPI Cards -->

        <div
            class="
                grid
                grid-cols-1
                sm:grid-cols-2
                xl:grid-cols-6
                gap-5
            "
        >
            <div
                v-for="i in 6"
                :key="'kpi-' + i"
                class="
                    rounded-2xl
                    border
                    border-slate-200
                    dark:border-slate-800
                    bg-white
                    dark:bg-slate-900
                    p-5
                "
            >
                <div
                    class="
                        h-4
                        w-20
                        rounded
                        bg-slate-200
                        dark:bg-slate-800
                    "
                />

                <div
                    class="
                        mt-4
                        h-8
                        w-24
                        rounded
                        bg-slate-200
                        dark:bg-slate-800
                    "
                />
            </div>
        </div>

        <!-- Financial KPI Cards -->

        <div
            class="
                grid
                grid-cols-1
                md:grid-cols-2
                xl:grid-cols-5
                gap-5
            "
        >
            <div
                v-for="i in 5"
                :key="'financial-' + i"
                class="
                    rounded-2xl
                    border
                    border-slate-200
                    dark:border-slate-800
                    bg-white
                    dark:bg-slate-900
                    p-5
                "
            >
                <div
                    class="
                        h-4
                        w-24
                        rounded
                        bg-slate-200
                        dark:bg-slate-800
                    "
                />

                <div
                    class="
                        mt-4
                        h-8
                        w-32
                        rounded
                        bg-slate-200
                        dark:bg-slate-800
                    "
                />
            </div>
        </div>

        <!-- Chart Placeholder -->

        <div
            class="
                rounded-2xl
                border
                border-slate-200
                dark:border-slate-800
                bg-white
                dark:bg-slate-900
                p-6
            "
        >
            <div
                class="
                    h-5
                    w-40
                    rounded
                    bg-slate-200
                    dark:bg-slate-800
                    mb-6
                "
            />

            <div
                class="
                    h-80
                    rounded-xl
                    bg-slate-200
                    dark:bg-slate-800
                "
            />
        </div>

        <!-- Quick Actions -->

        <div
            class="
                grid
                grid-cols-2
                md:grid-cols-3
                xl:grid-cols-6
                gap-4
            "
        >
            <div
                v-for="i in 6"
                :key="'action-' + i"
                class="
                    h-14
                    rounded-xl
                    bg-slate-200
                    dark:bg-slate-800
                "
            />
        </div>
    </div>
</template>

<script setup>
// Dashboard Loading Skeleton
</script>