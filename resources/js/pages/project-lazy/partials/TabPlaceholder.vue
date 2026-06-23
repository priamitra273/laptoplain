<script setup lang="ts">
defineProps<{
    icon: string;
    title: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex flex-col items-center justify-center gap-3 py-16 text-center">
        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-surface-100 dark:bg-surface-800">
            <i :class="icon" class="text-2xl text-surface-400 dark:text-surface-500"></i>
        </div>
        <h3 class="text-lg font-semibold text-surface-700 dark:text-surface-200">{{ title }}</h3>
        <p class="max-w-md text-sm text-surface-500 dark:text-surface-400">
            {{ description ?? 'This tab is being migrated to the lazy detail. Coming soon.' }}
        </p>
    </div>
</template>
