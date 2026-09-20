<script setup lang="ts">
defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex flex-col justify-between gap-3 md:flex-row md:items-center">
        <div class="flex flex-col gap-1">
            <slot name="title">
                <h1 v-if="title" class="text-xl font-semibold text-highlighted">{{ title }}</h1>
            </slot>

            <slot name="description">
                <p v-if="description" class="text-sm text-muted">{{ description }}</p>
            </slot>
        </div>

        <div v-if="$slots.default" class="flex flex-wrap items-center gap-2">
            <slot />
        </div>
    </div>
</template>
