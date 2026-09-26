<script setup lang="ts">
import { ref } from 'vue';

interface Props {
    icon: string;
    contentId: string;
    active?: boolean;
}

defineProps<Props>();

const expanded = ref(true);
</script>

<template>
    <UCard as="section" class="min-w-0 overflow-hidden shadow-xs" :class="active ? 'border-primary/30' : 'border-default'" :ui="{ body: 'p-0' }">
        <div class="h-0.5" :class="active ? 'bg-primary' : 'bg-muted'" />

        <div class="flex flex-wrap items-center gap-3 p-4 sm:flex-nowrap sm:px-5">
            <UButton
                type="button"
                color="neutral"
                variant="ghost"
                class="-m-1 flex min-w-0 flex-1 items-center gap-3 rounded-lg p-1 text-left"
                :aria-expanded="expanded"
                :aria-controls="contentId"
                @click="expanded = !expanded"
            >
                <UIcon name="i-lucide-chevron-right" class="size-4 shrink-0 text-dimmed transition-transform" :class="{ 'rotate-90': expanded }" />

                <span
                    class="flex size-9 shrink-0 items-center justify-center rounded-lg"
                    :class="active ? 'bg-primary/10 text-primary' : 'bg-elevated text-muted'"
                >
                    <UIcon :name="icon" class="size-4" />
                </span>

                <span class="flex min-w-0 flex-1 flex-col gap-1.5">
                    <slot name="header" />
                </span>
            </UButton>

            <div class="flex shrink-0 flex-wrap items-center gap-2">
                <slot name="actions" />
            </div>
        </div>

        <div v-show="expanded" :id="contentId" class="border-t border-default">
            <slot />
        </div>
    </UCard>
</template>
