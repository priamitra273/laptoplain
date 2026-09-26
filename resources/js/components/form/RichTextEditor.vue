<script setup lang="ts">
import Quill from 'quill';
import { Mention, MentionBlot } from 'quill-mention';
import 'quill-mention/dist/quill.mention.css';
import 'quill/dist/quill.snow.css';
import { getInitials } from '@/lib/utils';
import { onMounted, onUnmounted, ref, watch } from 'vue';

if (!Quill.imports['blots/mention']) {
    Quill.register({ 'blots/mention': MentionBlot, 'modules/mention': Mention });
}

interface MentionMember {
    id: string;
    name: string;
    avatar_url?: string | null;
}

interface Props {
    modelValue: string;
    placeholder?: string;
    minHeight?: string;
    mention?: boolean;
    members?: MentionMember[];
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Write something...',
    minHeight: '160px',
    mention: false,
    members: () => [],
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const editorRef = ref<HTMLElement | null>(null);
let quill: Quill | null = null;
let isUpdatingFromProp = false;

const isEmptyHtml = (html: string) => !html || html === '<p><br></p>' || html === '<p></p>';

const renderMentionItem = (item: { id: string; value: string; avatar?: string | null }) => {
    const row = document.createElement('div');
    row.className = 'mention-row';
    const badge = document.createElement('span');
    badge.className = 'mention-row-avatar';
    if (item.avatar) {
        const image = document.createElement('img');
        image.src = item.avatar;
        image.alt = item.value;
        badge.appendChild(image);
    } else {
        badge.textContent = getInitials(item.value);
    }
    const name = document.createElement('span');
    name.className = 'mention-row-name';
    name.textContent = item.value;
    row.append(badge, name);
    return row;
};

onMounted(() => {
    if (!editorRef.value) return;

    quill = new Quill(editorRef.value, {
        theme: 'snow',
        placeholder: props.placeholder,
        modules: {
            toolbar: [['bold', 'italic', 'underline']],
            ...(props.mention
                ? {
                      mention: {
                          allowedChars: /^[A-Za-z0-9._\-\s]*$/,
                          mentionDenotationChars: ['@'],
                          showDenotationChar: true,
                          defaultMenuOrientation: 'bottom',
                          renderItem: renderMentionItem,
                          source(searchTerm: string, renderList: (items: unknown[], term: string) => void) {
                              const items = props.members.map((member) => ({
                                  id: member.id,
                                  value: member.name,
                                  avatar: member.avatar_url ?? undefined,
                              }));
                              renderList(
                                  searchTerm.length ? items.filter((item) => item.value.toLowerCase().includes(searchTerm.toLowerCase())) : items,
                                  searchTerm,
                              );
                          },
                      },
                  }
                : {}),
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
    <div :class="mention ? 'mention-editor' : 'rich-text-editor'">
        <div ref="editorRef" :style="{ minHeight }" />
    </div>
</template>

<style>
.rich-text-editor .ql-toolbar.ql-snow {
    border-color: var(--ui-border);
    border-top-left-radius: var(--ui-radius);
    border-top-right-radius: var(--ui-radius);
    background: var(--ui-bg-elevated);
}

.mention-editor .ql-toolbar.ql-snow,
.mention-editor .ql-container.ql-snow {
    border-color: var(--ui-border);
}
.mention-editor .ql-toolbar.ql-snow {
    border-top-left-radius: 0.5rem;
    border-top-right-radius: 0.5rem;
}
.mention-editor .ql-container.ql-snow {
    border-top: 0;
    font-family: inherit;
    font-size: 0.875rem;
}
.mention-editor .ql-snow .ql-picker {
    color: var(--ui-text-muted);
}
.ql-mention-list-container {
    z-index: 60;
    min-width: 200px;
    max-width: 280px;
    overflow: hidden;
    border: 1px solid var(--ui-border);
    border-radius: 0.625rem;
    background-color: var(--ui-bg);
    box-shadow: 0 10px 25px -5px rgb(0 0 0 / 0.15);
}
.ql-mention-list {
    margin: 0;
    padding: 0.25rem;
    max-height: 210px;
    overflow-y: auto;
    list-style: none;
}
.ql-mention-list-item {
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    cursor: pointer;
}
.ql-mention-list-item.selected,
.ql-mention-list-item:hover {
    background-color: var(--ui-bg-elevated);
}
.mention-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.mention-row-avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    width: 1.5rem;
    height: 1.5rem;
    border-radius: 9999px;
    background-color: var(--ui-bg-accented);
    color: var(--ui-text-muted);
    font-size: 0.625rem;
    font-weight: 700;
}
.mention-row-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.mention-row-name {
    overflow: hidden;
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--ui-text-highlighted);
    text-overflow: ellipsis;
    white-space: nowrap;
}
.mention {
    display: inline-flex;
    align-items: center;
    width: fit-content;
    padding: 0 0.35rem;
    border-radius: 9999px;
    background-color: var(--ui-bg-accented);
    color: var(--ui-text-highlighted);
    font-size: 0.8125rem;
    font-weight: 600;
    line-height: 1.4;
    white-space: nowrap;
    vertical-align: middle;
}

.rich-text-editor .ql-container.ql-snow {
    border-color: var(--ui-border);
    border-bottom-right-radius: var(--ui-radius);
    border-bottom-left-radius: var(--ui-radius);
    font-family: inherit;
    font-size: 0.875rem;
    background: var(--ui-bg);
    color: var(--ui-text);
}

.rich-text-editor .ql-editor.ql-blank::before,
.mention-editor .ql-editor.ql-blank::before {
    color: var(--ui-text-dimmed);
    font-style: normal;
}

.rich-text-editor .ql-snow .ql-stroke,
.mention-editor .ql-snow .ql-stroke {
    stroke: var(--ui-text-muted);
}

.rich-text-editor .ql-snow .ql-fill,
.mention-editor .ql-snow .ql-fill {
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
