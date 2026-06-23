<script setup lang="ts">
withDefaults(defineProps<{ columns?: number; cardsPerColumn?: number }>(), {
    columns: 4,
    cardsPerColumn: 3,
});
</script>

<template>
    <div class="flex gap-4 overflow-hidden" aria-hidden="true">
        <div
            v-for="col in columns"
            :key="col"
            class="flex w-[280px] shrink-0 flex-col gap-3 rounded-xl border border-surface-200 bg-surface-50/60 p-3 dark:border-surface-700 dark:bg-surface-800/40"
        >
            <!-- Column header -->
            <div class="flex items-center gap-2">
                <span class="h-2.5 w-2.5 shrink-0 animate-pulse rounded-full bg-surface-300 dark:bg-surface-600" />
                <span class="h-3 flex-1 animate-pulse rounded bg-surface-200 dark:bg-surface-700" />
                <span class="h-5 w-6 animate-pulse rounded-full bg-surface-200 dark:bg-surface-700" />
            </div>

            <!-- Card placeholders -->
            <div
                v-for="card in cardsPerColumn"
                :key="card"
                class="flex flex-col gap-2 rounded-lg border border-surface-200 bg-white p-3 dark:border-surface-700 dark:bg-surface-800"
            >
                <span class="h-3 w-3/4 animate-pulse rounded bg-surface-200 dark:bg-surface-700" />
                <span class="h-3 w-1/2 animate-pulse rounded bg-surface-200 dark:bg-surface-700" />
                <div class="mt-1 flex items-center justify-between">
                    <span class="h-5 w-16 animate-pulse rounded-full bg-surface-200 dark:bg-surface-700" />
                    <span class="h-6 w-6 animate-pulse rounded-full bg-surface-200 dark:bg-surface-700" />
                </div>
            </div>
        </div>
    </div>
</template>
