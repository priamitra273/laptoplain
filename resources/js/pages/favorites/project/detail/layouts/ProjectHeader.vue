<script setup lang="ts">
import UserAvatarGroup from '@/components/UserAvatarGroup.vue';
import InlineTextEdit from '@/components/InlineTextEdit.vue';
import { router } from '@inertiajs/vue3';
import type { ShellMember, ShellProject } from '../types';

interface Props {
    project: ShellProject;
    members: ShellMember[];
    canEdit: boolean;
    isMember: boolean;
}

interface Emits {
    (e: 'update', value: string, field: string): void;
}

defineProps<Props>();
const emit = defineEmits<Emits>();


</script>

<template>
    <div class="flex flex-wrap items-center gap-x-3 gap-y-2 border-b border-default pb-4">
        <UButton
            icon="i-lucide-arrow-left"
            color="neutral"
            variant="outline"
            aria-label="Back to projects"
            @click="router.visit(route('project.index'))"
        />

        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-elevated text-xl">
            {{ project.emoji }}
        </span>

        <div class="min-w-0 flex-1">
            <InlineTextEdit
                :value="project.title"
                :disabled="!canEdit"
                input-size="lg"
                input-class="max-w-md"
                @save="(value) => emit('update', value, 'title')"
            >
                <template #default="{ startEditing }">
                    <div
                        class="flex items-center gap-2"
                        :class="canEdit ? '-mx-2 -my-1 cursor-pointer rounded px-2 py-1 hover:bg-elevated' : ''"
                        :role="canEdit ? 'button' : undefined"
                        :tabindex="canEdit ? 0 : undefined"
                        @click="startEditing"
                        @keydown.enter.prevent="startEditing"
                        @keydown.space.prevent="startEditing"
                    >
                        <h1 class="min-w-0 truncate text-xl font-bold text-highlighted">{{ project.title }}</h1>
                        <UBadge v-if="project.project_no" color="neutral" variant="outline" size="sm" class="font-mono">{{ project.project_no }}</UBadge>
                    </div>
                </template>
            </InlineTextEdit>

            <p class="mt-0.5 flex items-center gap-1.5 text-xs">
                <span v-if="isMember" class="flex items-center gap-1 text-success">
                    <UIcon name="i-lucide-check-circle" class="size-3.5" /> Member
                </span>
                <span v-else class="flex items-center gap-1 text-muted">
                    <UIcon name="i-lucide-eye" class="size-3.5" /> Viewer
                </span>
            </p>
        </div>

        <UserAvatarGroup :users="members.map((member) => member.user)" :max="5" size="md" class="ms-auto" />
    </div>
</template>
