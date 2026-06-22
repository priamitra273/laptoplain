<script setup lang="ts">
import Panel from 'primevue/panel';

interface Props {
    title?: string;
    icon?: string;
    bodyClass?: string;
    toggleable?: boolean;
    collapsed?: boolean;
}

withDefaults(defineProps<Props>(), {
    toggleable: false,
    collapsed: false,
});
</script>

<template>
    <Panel
        :toggleable="toggleable"
        :collapsed="collapsed"
        :pt="{
            root: 'overflow-hidden !rounded-xl !border !border-surface-200 !bg-surface-0 !shadow-sm dark:!border-surface-700 dark:!bg-surface-900',
            header: '!items-center !gap-3 !border-b !border-surface-200 !px-4 !py-3 dark:!border-surface-700',
            headerActions: '!gap-2',
            content: bodyClass ?? '!p-4',
        }"
    >
        <template #header>
            <slot name="header">
                <div class="flex min-w-0 items-center gap-2">
                    <i v-if="icon" :class="icon" class="text-sm text-surface-400 dark:text-surface-500" />
                    <h2 class="truncate text-sm font-semibold text-surface-900 dark:text-surface-50">{{ title }}</h2>
                </div>
            </slot>
        </template>

        <template #icons>
            <slot name="actions" />
        </template>

        <slot />
    </Panel>
</template>
