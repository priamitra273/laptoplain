<script setup lang="ts">
import Icon from '@/components/Icon.vue';

interface Props {
    label: string;
    icon: string;
}

const props = defineProps<Props>();
</script>

<template>
    <div class="grid grid-cols-3 gap-4">
        <div class="flex items-center gap-3 text-surface-500">
            <Icon :name="icon" class="size-4" />
            <span>{{ label }}</span>
        </div>

        <div class="col-span-2">
            <slot></slot>
        </div>
    </div>
</template>
