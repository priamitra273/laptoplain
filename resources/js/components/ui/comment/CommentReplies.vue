<script setup lang="ts">
import { watchDebounced } from '@vueuse/core';
import { computed, ref } from 'vue';
import CommentItem from './CommentItem.vue';
import { ReplyProps } from './type';

const props = defineProps<ReplyProps>();

const visible = ref<string | null>(null);
const isRender = ref(false);

const iconClass = computed(() => {
    return visible.value === '0' ? 'pi pi-chevron-up' : 'pi pi-chevron-down';
});

const toggle = () => {
    visible.value = visible.value === '0' ? null : '0';
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
    <div v-if="comment.replies?.length && currentLevel < 1">
        <Accordion v-model:value="visible">
            <AccordionPanel value="0" class="!border-0">
                <AccordionContent pt:content:class="!px-0 !py-4 space-y-3">
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
                </AccordionContent>
            </AccordionPanel>
        </Accordion>

        <Button
            :label="`${comment.replies.length} Replies`"
            :icon="iconClass"
            icon-pos="right"
            severity="secondary"
            text
            rounded
            size="small"
            @click="toggle"
        />
    </div>
</template>
