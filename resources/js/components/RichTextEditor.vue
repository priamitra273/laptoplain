<script setup lang="ts">
import Quill from 'quill';
import 'quill/dist/quill.snow.css';
import { onMounted, onUnmounted, ref, watch } from 'vue';

interface Props {
    modelValue: string;
    placeholder?: string;
    height?: string;
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Write something...',
    height: '160px',
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const editorRef = ref<HTMLElement | null>(null);
let quill: Quill | null = null;
let isUpdatingFromProp = false;

const isEmptyHtml = (html: string) => !html || html === '<p><br></p>' || html === '<p></p>';

onMounted(() => {
    if (!editorRef.value) return;

    quill = new Quill(editorRef.value, {
        theme: 'snow',
        placeholder: props.placeholder,
        modules: {
            toolbar: [['bold', 'italic', 'underline']],
        },
    });

    if (props.modelValue) {
        isUpdatingFromProp = true;
        quill.clipboard.dangerouslyPasteHTML(props.modelValue);
        isUpdatingFromProp = false;
    }

    quill.on('text-change', () => {
        if (isUpdatingFromProp || !quill) return;
        const html = quill.root.innerHTML;
        emit('update:modelValue', isEmptyHtml(html) ? '' : html);
    });
});

onUnmounted(() => {
    quill = null;
});

watch(
    () => props.modelValue,
    (value) => {
        if (!quill) return;

        const incoming = value ?? '';
        const currentHtml = quill.root.innerHTML;

        if (isEmptyHtml(incoming) && isEmptyHtml(currentHtml)) return;
        if (currentHtml === incoming) return;

        isUpdatingFromProp = true;
        quill.clipboard.dangerouslyPasteHTML(incoming);
        isUpdatingFromProp = false;
    },
);
</script>

<template>
    <div class="rich-text-editor" :style="{ '--rte-height': height }">
        <div ref="editorRef" />
    </div>
</template>

<style>
.rich-text-editor .ql-toolbar.ql-snow {
    border-color: var(--ui-border);
    border-top-left-radius: var(--ui-radius);
    border-top-right-radius: var(--ui-radius);
    background: var(--ui-bg-elevated);
}

.rich-text-editor .ql-container.ql-snow {
    border-color: var(--ui-border);
    border-bottom-right-radius: var(--ui-radius);
    border-bottom-left-radius: var(--ui-radius);
    min-height: var(--rte-height);
    font-family: inherit;
    font-size: 0.875rem;
    background: var(--ui-bg);
    color: var(--ui-text);
}

.rich-text-editor .ql-editor.ql-blank::before {
    color: var(--ui-text-dimmed);
    font-style: normal;
}

.rich-text-editor .ql-snow .ql-stroke {
    stroke: var(--ui-text-muted);
}

.rich-text-editor .ql-snow .ql-fill {
    fill: var(--ui-text-muted);
}

.rich-text-editor .ql-snow.ql-toolbar button:hover .ql-stroke,
.rich-text-editor .ql-snow.ql-toolbar button.ql-active .ql-stroke {
    stroke: var(--ui-color-primary-500);
}

.rich-text-editor .ql-snow.ql-toolbar button:hover .ql-fill,
.rich-text-editor .ql-snow.ql-toolbar button.ql-active .ql-fill {
    fill: var(--ui-color-primary-500);
}

.rich-text-editor .ql-snow.ql-toolbar button.ql-active {
    color: var(--ui-color-primary-500);
}
</style>
