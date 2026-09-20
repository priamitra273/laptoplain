<script setup lang="ts">
interface Props {
    title: string;
    icon?: string;
    description?: string;
    size?: 'compact' | 'normal';
}

withDefaults(defineProps<Props>(), { size: 'normal' });
</script>

<template>
    <div class="flex flex-col items-center gap-2 text-center" :class="size === 'compact' ? 'py-6' : 'py-10'">
        <UIcon v-if="icon" :name="icon" class="size-8 text-muted" />

        <p class="text-sm font-medium text-highlighted">{{ title }}</p>

        <p v-if="description" class="max-w-md text-xs leading-relaxed text-muted">{{ description }}</p>
    </div>
</template>
