<script setup lang="ts">
withDefaults(
    defineProps<{
        disabled?: boolean;
    }>(),
    { disabled: false },
);

const emit = defineEmits<{
    click: [];
}>();
</script>

<template>
    <UButton
        label="Clear filters"
        icon="i-lucide-filter-x"
        color="neutral"
        variant="ghost"
        size="sm"
        class="rounded-full"
        :disabled="disabled"
        @click="emit('click')"
    />
</template>
