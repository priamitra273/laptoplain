<script setup lang="ts">
import { ref, watch } from 'vue';

interface Props {
    value: string;
    disabled?: boolean;
    inputSize?: 'sm' | 'md' | 'lg';
    inputClass?: string;
}

const props = withDefaults(defineProps<Props>(), { inputSize: 'sm' });

const emit = defineEmits<{ save: [value: string] }>();

const editing = ref(false);
const draft = ref('');

const startEditing = () => {
    if (props.disabled) {
        return;
    }

    draft.value = props.value;
    editing.value = true;
};

const commit = () => {
    if (!editing.value || props.disabled) {
        return;
    }

    editing.value = false;

    const next = draft.value.trim();

    if (next && next !== props.value.trim()) {
        emit('save', next);
    }
};

const cancel = () => {
    editing.value = false;
};

watch(
    () => props.disabled,
    (disabled) => {
        if (disabled) {
            cancel();
        }
    },
);

const onEnter = (event: KeyboardEvent) => {
    if (event.isComposing) {
        return;
    }

    commit();
};
</script>

<template>
    <UInput
        v-if="editing"
        v-model="draft"
        :size="inputSize"
        :class="inputClass"
        :disabled="disabled"
        autofocus
        @blur="commit"
        @keydown.enter="onEnter"
        @keydown.escape="cancel"
    />

    <slot v-else :start-editing="startEditing" />
</template>
