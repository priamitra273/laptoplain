<script setup lang="ts">
withDefaults(
    defineProps<{
        title: string;
        as?: 'p' | 'span';
    }>(),
    { as: 'p' },
);
</script>

<template>
    <component :is="as" class="text-xs font-semibold tracking-[0.06em] text-muted uppercase">
        {{ title }}
    </component>
</template>
