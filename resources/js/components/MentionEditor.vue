<script setup lang="ts">
import Quill from 'quill';
import { Mention, MentionBlot } from 'quill-mention';
import 'quill-mention/dist/quill.mention.css';
import 'quill/dist/quill.snow.css';
import { onMounted, onUnmounted, ref, watch } from 'vue';

// Quill mendaftarkan modul secara global; penjagaan ini mencegah pendaftaran ganda
// saat komponen dipasang lebih dari sekali dalam satu sesi.
if (!Quill.imports['blots/mention']) {
    Quill.register({ 'blots/mention': MentionBlot, 'modules/mention': Mention });
}

interface MentionMember {
    id: string;
    name: string;
    avatar_url?: string | null;
}

const props = withDefaults(
    defineProps<{
        modelValue: string;
        members?: MentionMember[];
        placeholder?: string;
        minHeight?: string;
    }>(),
    {
        members: () => [],
        placeholder: 'Write a comment... use @ to mention someone',
        minHeight: '150px',
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const editorRef = ref<HTMLElement | null>(null);
let quill: Quill | null = null;
let updatingFromProp = false;

const isEmptyHtml = (html: string) => !html || html === '<p><br></p>' || html === '<p></p>';

const initials = (name: string) =>
    name
        .split(' ')
        .map((word) => word[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);

/**
 * Backend membaca mention dari atribut `data-id` pada HTML komentar
 * (lihat StoreCommentRequest::mentionedUserIds), jadi id yang dipasang di sini
 * harus id ter-encode yang sama dengan yang dikirim server.
 */
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
        badge.textContent = initials(item.value);
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

                    if (!searchTerm.length) {
                        renderList(items, searchTerm);

                        return;
                    }

                    renderList(
                        items.filter((item) => item.value.toLowerCase().includes(searchTerm.toLowerCase())),
                        searchTerm,
                    );
                },
            },
        },
    });

    if (props.modelValue) {
        updatingFromProp = true;
        quill.clipboard.dangerouslyPasteHTML(props.modelValue);
        updatingFromProp = false;
    }

    quill.on('text-change', () => {
        if (updatingFromProp || !quill) return;

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
        const current = quill.root.innerHTML;

        if (current === incoming) return;
        if (isEmptyHtml(incoming) && isEmptyHtml(current)) return;

        updatingFromProp = true;
        quill.clipboard.dangerouslyPasteHTML(incoming);
        updatingFromProp = false;
    },
);
</script>

<template>
    <div class="mention-editor">
        <div ref="editorRef" :style="{ minHeight: props.minHeight }" />
    </div>
</template>

<style>
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

.mention-editor .ql-editor.ql-blank::before {
    color: var(--ui-text-dimmed);
    font-style: normal;
}

.mention-editor .ql-snow .ql-stroke {
    stroke: var(--ui-text-muted);
}

.mention-editor .ql-snow .ql-fill {
    fill: var(--ui-text-muted);
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
</style>
