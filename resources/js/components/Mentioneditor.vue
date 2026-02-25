<script setup lang="ts">
import Avatar from 'primevue/avatar';
import Tag from 'primevue/tag';
import Quill from 'quill';
import { Mention, MentionBlot } from 'quill-mention';
import 'quill-mention/dist/quill.mention.css';
import 'quill/dist/quill.snow.css';
import { createApp, h, onMounted, onUnmounted, ref, watch } from 'vue';

if (!Quill.imports['blots/mention']) {
    Quill.register({ 'blots/mention': MentionBlot, 'modules/mention': Mention });
}

const SEVERITIES = ['primary', 'secondary', 'success', 'info', 'warn', 'danger', 'contrast'] as const;
type Severity = (typeof SEVERITIES)[number];

const severityCache = new Map<number | string, Severity>();

function getSeverityForId(id: number | string): Severity {
    if (!severityCache.has(id)) {
        severityCache.set(id, SEVERITIES[Math.floor(Math.random() * SEVERITIES.length)]);
    }
    return severityCache.get(id)!;
}

const props = defineProps<{
    modelValue: string;
    projectMembers?: { id: number | string; name: string; avatar?: string }[];
    height?: string;
    placeholder?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const editorRef = ref<HTMLElement | null>(null);
let quillInstance: Quill | null = null;
let isUpdatingFromProp = false;

// ✅ Track user IDs yang sudah di-mention
const mentionedIds = ref<Set<number | string>>(new Set());

function syncMentionedIds() {
    if (!quillInstance) return;
    const mentions = quillInstance.root.querySelectorAll('.mention');
    const ids = new Set<number | string>();
    mentions.forEach((el) => {
        const id = el.getAttribute('data-id');
        if (id !== null) ids.add(id);
    });
    mentionedIds.value = ids;
}

onMounted(() => {
    if (!editorRef.value) return;

    quillInstance = new Quill(editorRef.value, {
        theme: 'snow',
        placeholder: props.placeholder ?? 'Write something... Use @ to mention someone',
        modules: {
            toolbar: [['bold', 'italic', 'underline']],
            mention: {
                allowedChars: /^[A-Za-z\sÅÄÖåäö\u00C0-\u024F]*$/,
                mentionDenotationChars: ['@'],
                showDenotationChar: true,
                defaultMenuOrientation: 'bottom',
                renderItem(item: { id: number | string; value: string; avatar?: string }) {
                    const div = document.createElement('div');
                    div.classList.add('mention-item-inner');

                    const initials = item.value
                        .split(' ')
                        .map((w: string) => w[0])
                        .join('')
                        .toUpperCase()
                        .slice(0, 2);

                    const iconContainer = document.createElement('span');

                    if (item.avatar) {
                        const app = createApp({
                            render() {
                                return h(Avatar, {
                                    image: item.avatar,
                                    shape: 'circle',
                                    size: 'small',
                                    style: 'border-radius: 9999px;',
                                });
                            },
                        });
                        app.mount(iconContainer);
                    } else {
                        const app = createApp({
                            render() {
                                return h(Tag, {
                                    value: initials,
                                    severity: getSeverityForId(item.id),
                                    rounded: true,
                                    style: 'font-size: 0.6rem; font-weight: 700; cursor: default; width: 26px; height: 26px; display: inline-flex; align-items: center; justify-content: center; padding: 0; border-radius: 9999px;',
                                });
                            },
                        });
                        app.mount(iconContainer);
                    }

                    const name = document.createElement('span');
                    name.innerText = item.value;
                    name.classList.add('mention-member-name');

                    div.appendChild(iconContainer);
                    div.appendChild(name);

                    return div;
                },
                source(searchTerm: string, renderList: Function) {
                    const members = (props.projectMembers ?? []).map((m) => ({
                        id: m.id,
                        value: m.name,
                        avatar: m.avatar,
                    }));

                    // ✅ Filter user yang sudah di-mention
                    const available = members.filter((m) => !mentionedIds.value.has(String(m.id)));

                    if (searchTerm.length === 0) {
                        renderList(available, searchTerm);
                    } else {
                        const matches = available.filter((m) => m.value.toLowerCase().includes(searchTerm.toLowerCase()));
                        renderList(matches, searchTerm);
                    }
                },
            },
        },
    });

    if (props.modelValue) {
        isUpdatingFromProp = true;
        quillInstance.clipboard.dangerouslyPasteHTML(props.modelValue);
        isUpdatingFromProp = false;
        syncMentionedIds(); // ✅ Sync saat load awal
    }

    quillInstance.on('text-change', () => {
        if (isUpdatingFromProp) return;
        syncMentionedIds(); // ✅ Sync setiap ada perubahan (termasuk hapus mention)
        const html = quillInstance!.root.innerHTML;
        const isEmpty = html === '<p><br></p>' || html === '<p></p>' || !html;
        emit('update:modelValue', isEmpty ? '' : html);
    });
});

onUnmounted(() => {
    quillInstance = null;
});

watch(
    () => props.modelValue,
    (val) => {
        if (!quillInstance) return;
        const currentHtml = quillInstance.root.innerHTML;
        const incoming = val ?? '';
        const isEmpty = incoming === '' || incoming === '<p><br></p>';

        if (isEmpty && (currentHtml === '<p><br></p>' || currentHtml === '')) return;
        if (currentHtml === incoming) return;

        isUpdatingFromProp = true;
        const selection = quillInstance.getSelection();
        quillInstance.clipboard.dangerouslyPasteHTML(incoming);
        if (selection) {
            try {
                quillInstance.setSelection(selection);
            } catch {}
        }
        isUpdatingFromProp = false;
        syncMentionedIds(); // ✅ Sync setelah update dari luar
    },
);
</script>

<template>
    <div class="mention-editor-wrapper">
        <div ref="editorRef" :style="{ minHeight: height ?? '120px' }"></div>
    </div>
</template>

<style>
/* ===== Quill Base Override ===== */
.mention-editor-wrapper .ql-container {
    font-size: 0.875rem;
    border-bottom-left-radius: 0.5rem;
    border-bottom-right-radius: 0.5rem;
    border-color: #e5e7eb;
}

.dark .mention-editor-wrapper .ql-container {
    border-color: #374151;
    background-color: #1f2937;
    color: #f3f4f6;
}

.mention-editor-wrapper .ql-toolbar {
    border-top-left-radius: 0.5rem;
    border-top-right-radius: 0.5rem;
    border-color: #e5e7eb;
    background-color: #f9fafb;
}

.dark .mention-editor-wrapper .ql-toolbar {
    border-color: #374151;
    background-color: #111827;
}

.dark .mention-editor-wrapper .ql-toolbar .ql-stroke {
    stroke: #9ca3af;
}

.dark .mention-editor-wrapper .ql-toolbar .ql-fill {
    fill: #9ca3af;
}

.dark .mention-editor-wrapper .ql-toolbar button:hover .ql-stroke {
    stroke: #f3f4f6;
}

.dark .mention-editor-wrapper .ql-editor.ql-blank::before {
    color: #6b7280;
}

/* ===== Mention Dropdown ===== */
.ql-mention-list-container {
    z-index: 9999;
    min-width: 200px;
    max-width: 280px;
    border-radius: 0.625rem;
    border: 1px solid #e5e7eb;
    background-color: #ffffff;
    box-shadow:
        0 10px 25px -5px rgba(0, 0, 0, 0.1),
        0 4px 10px -5px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

.dark .ql-mention-list-container {
    border-color: #374151;
    background-color: #1f2937;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4);
}

.ql-mention-list {
    list-style: none;
    margin: 0;
    padding: 0.25rem;
    max-height: 200px;
    overflow-y: auto;
}

.ql-mention-list-item {
    cursor: pointer;
    border-radius: 9999px;
    padding: 0.25rem 0.5rem;
    transition: background-color 0.15s ease;
}

.ql-mention-list-item.selected,
.ql-mention-list-item:hover {
    background-color: #eff6ff;
}

.dark .ql-mention-list-item.selected,
.dark .ql-mention-list-item:hover {
    background-color: rgba(59, 130, 246, 0.15);
}

/* ===== Mention Item Inner ===== */
.mention-item-inner {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.mention-member-name {
    font-size: 0.8125rem;
    font-weight: 500;
    color: #111827;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.dark .mention-member-name {
    color: #f3f4f6;
}

/* ===== Mention Chip (in editor) ===== */
.mention {
    display: inline-flex !important;
    align-items: center;
    width: fit-content !important;
    max-width: fit-content !important;
    min-width: 0 !important;
    border-radius: 9999px;
    background-color: #dbeafe;
    color: #1d4ed8;
    padding: 0 0.35rem;
    font-weight: 600;
    font-size: 0.8125rem;
    line-height: 1.4;
    cursor: default;
    user-select: all;
    white-space: nowrap;
    word-break: keep-all;
    overflow-wrap: normal;
    vertical-align: middle;
    box-sizing: border-box;
}

.dark .mention {
    background-color: rgba(59, 130, 246, 0.2);
    color: #93c5fd;
}
</style>
