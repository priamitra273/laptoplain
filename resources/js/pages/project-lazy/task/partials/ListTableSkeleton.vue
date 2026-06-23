<script setup lang="ts">
defineProps<{ rows?: number }>();
</script>

<template>
    <div class="flex animate-pulse flex-col gap-4">
        <!-- Toolbar -->
        <div class="flex items-center justify-between">
            <div class="h-6 w-24 rounded bg-surface-200 dark:bg-surface-700"></div>
            <div class="h-9 w-28 rounded bg-surface-200 dark:bg-surface-700"></div>
        </div>

        <!-- Filters -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            <div class="h-10 rounded bg-surface-200 dark:bg-surface-700"></div>
            <div class="h-10 rounded bg-surface-200 dark:bg-surface-700"></div>
            <div class="h-10 rounded bg-surface-200 dark:bg-surface-700"></div>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-lg border border-surface-200 dark:border-surface-700">
            <div class="flex items-center gap-3 bg-surface-50 px-4 py-3 dark:bg-surface-800">
                <div class="h-4 w-4 rounded bg-surface-200 dark:bg-surface-700"></div>
                <div class="h-4 flex-1 rounded bg-surface-200 dark:bg-surface-700"></div>
                <div class="h-4 w-24 rounded bg-surface-200 dark:bg-surface-700"></div>
                <div class="h-4 w-24 rounded bg-surface-200 dark:bg-surface-700"></div>
                <div class="h-4 w-28 rounded bg-surface-200 dark:bg-surface-700"></div>
            </div>
            <div
                v-for="row in rows ?? 6"
                :key="row"
                class="flex items-center gap-3 border-t border-surface-100 px-4 py-3 dark:border-surface-700"
            >
                <div class="h-4 w-4 rounded bg-surface-200 dark:bg-surface-700"></div>
                <div class="h-4 flex-1 rounded bg-surface-200 dark:bg-surface-700"></div>
                <div class="h-5 w-20 rounded bg-surface-200 dark:bg-surface-700"></div>
                <div class="h-5 w-20 rounded bg-surface-200 dark:bg-surface-700"></div>
                <div class="h-2 w-28 rounded bg-surface-200 dark:bg-surface-700"></div>
            </div>
        </div>
    </div>
</template>
