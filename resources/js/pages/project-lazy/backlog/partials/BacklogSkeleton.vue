<script setup lang="ts">
defineProps<{ sections?: number; rows?: number }>();
</script>

<template>
    <div class="flex animate-pulse flex-col gap-3">
        <div v-for="section in sections ?? 2" :key="section" class="overflow-hidden rounded-lg border border-surface-200 dark:border-surface-700">
            <!-- Header -->
            <div class="flex items-center gap-2 bg-surface-50 px-3 py-2 dark:bg-surface-800">
                <div class="h-4 w-4 rounded bg-surface-200 dark:bg-surface-700"></div>
                <div class="h-4 w-40 rounded bg-surface-200 dark:bg-surface-700"></div>
                <div class="ml-auto h-4 w-16 rounded bg-surface-200 dark:bg-surface-700"></div>
            </div>
            <!-- Rows -->
            <div v-for="row in rows ?? 3" :key="row" class="flex items-center gap-3 border-t border-surface-100 px-4 py-3 dark:border-surface-700">
                <div class="h-4 w-4 rounded bg-surface-200 dark:bg-surface-700"></div>
                <div class="h-4 flex-1 rounded bg-surface-200 dark:bg-surface-700"></div>
                <div class="h-5 w-20 rounded bg-surface-200 dark:bg-surface-700"></div>
                <div class="h-5 w-16 rounded bg-surface-200 dark:bg-surface-700"></div>
                <div class="h-6 w-6 rounded-full bg-surface-200 dark:bg-surface-700"></div>
            </div>
        </div>
    </div>
</template>
