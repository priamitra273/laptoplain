<script setup lang="ts">
import { watchDebounced } from '@vueuse/core';
import { computed, ref } from 'vue';
import CommentItem from './CommentItem.vue';
import { ReplyProps } from './type';

const props = defineProps<ReplyProps>();

const visible = ref<string | null>(null);
const isRender = ref(false);

const isOpen = computed(() => visible.value === '0');

const iconClass = computed(() => (isOpen.value ? 'pi pi-chevron-up' : 'pi pi-chevron-down'));

const toggleLabel = computed(() => (isOpen.value ? 'Sembunyikan balasan' : `Lihat ${props.comment.replies?.length ?? 0} balasan`));

const toggle = () => {
    visible.value = isOpen.value ? null : '0';
    isRender.value = true;
};

watchDebounced(
    visible,
    () => {
        if (visible.value === null) {
            isRender.value = false;
        }
    },
    { debounce: 1500 },
);
</script>

<template>
    <div v-if="comment.replies?.length && currentLevel < 1" class="space-y-1">
        <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-xs font-semibold text-primary-600 transition-colors hover:bg-primary-50 dark:text-primary-400 dark:hover:bg-primary-500/10"
            @click="toggle"
        >
            <i :class="iconClass" class="text-[10px]" />
            {{ toggleLabel }}
        </button>

        <Accordion v-model:value="visible">
            <AccordionPanel value="0" class="!border-0">
                <AccordionContent pt:content:class="!p-0">
                    <div class="space-y-4 border-l border-surface-200 pl-4 dark:border-surface-700">
                        <CommentItem
                            v-if="isRender"
                            v-for="reply in props.comment.replies"
                            :key="reply.id"
                            :comment="reply"
                            :taskId="props.taskId"
                            :level="currentLevel + 1"
                            :currentUserId="props.currentUserId"
                            :projectMembers="props.projectMembers"
                            :showReply="false"
                        />
                    </div>
                </AccordionContent>
            </AccordionPanel>
        </Accordion>
    </div>
</template>
