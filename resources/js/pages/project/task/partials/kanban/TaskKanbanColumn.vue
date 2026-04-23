<script setup lang="ts">
import { SeverityClasses } from '@/lib/severity';

interface Props {
    statusId: string;
    statusName: string;
    tasksCount: number;
    meta: SeverityClasses;
    collapsed?: boolean;
    canAct?: boolean;
}

interface Emits {
    toggleCollapse: [statusId: string];
    startQuickAdd: [statusId: string];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();
</script>

<template>
    <div
        class="flex shrink-0 flex-col rounded-xl border transition-all duration-200"
        :class="[meta.colBg, meta.colBorder, collapsed ? 'w-12' : 'w-[280px]']"
    >
        <!-- Column header -->
        <div class="flex items-center gap-2 px-3 py-2.5">
            <template v-if="!collapsed">
                <span class="h-2.5 w-2.5 shrink-0 rounded-full" :class="meta.dot" />
                <span class="flex-1 truncate text-xs font-semibold uppercase tracking-wider" :class="meta.headerText">
                    {{ statusName }}
                </span>
                <span
                    class="min-w-[20px] rounded-full bg-surface-200/80 px-1.5 py-0.5 text-center text-xs font-bold text-surface-600 dark:bg-surface-700 dark:text-surface-300"
                >
                    {{ tasksCount }}
                </span>
                <button
                    v-if="canAct"
                    @click="emit('startQuickAdd', statusId)"
                    class="flex h-5 w-5 items-center justify-center rounded text-surface-400 hover:bg-surface-200 hover:text-surface-700 dark:hover:bg-surface-700"
                    title="Quick add"
                >
                    <i class="pi pi-plus text-xs" />
                </button>
                <button
                    @click="emit('toggleCollapse', statusId)"
                    class="flex h-5 w-5 items-center justify-center rounded text-surface-400 hover:bg-surface-200 hover:text-surface-700 dark:hover:bg-surface-700"
                    title="Collapse"
                >
                    <i class="pi pi-chevron-left text-xs" />
                </button>
            </template>
            <template v-else>
                <div class="flex w-full flex-col items-center gap-2 py-1">
                    <button
                        @click="emit('toggleCollapse', statusId)"
                        class="flex h-5 w-5 items-center justify-center rounded text-surface-400 hover:bg-surface-200"
                        title="Expand"
                    >
                        <i class="pi pi-chevron-right text-xs" />
                    </button>
                    <div
                        class="whitespace-nowrap text-xs font-semibold uppercase tracking-widest"
                        :class="meta.headerText"
                        style="writing-mode: vertical-rl; transform: rotate(180deg)"
                    >
                        {{ statusName }}
                    </div>
                    <span class="rounded-full bg-surface-200/80 px-1 py-0.5 text-center text-xs font-bold text-surface-600 dark:bg-surface-700">
                        {{ tasksCount }}
                    </span>
                </div>
            </template>
        </div>

        <div v-show="!collapsed" class="flex flex-col px-2 pb-2">
            <slot />

            <slot name="empty" />

            <slot name="footer">
                <button
                    v-if="canAct"
                    class="mt-2 flex w-full items-center gap-1.5 rounded-lg px-2 py-1.5 text-xs text-surface-400 transition hover:bg-surface-200/60 hover:text-surface-600 dark:hover:bg-surface-700/60"
                    @click="emit('startQuickAdd', statusId)"
                >
                    <i class="pi pi-plus text-xs" /> Create issue
                </button>
            </slot>
        </div>
    </div>
</template>
