<script setup lang="ts">
import { getInitials } from '@/lib/utils';
import { Deferred, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { DashboardStats, Member } from './types';

const props = defineProps<{
    stats: DashboardStats;
    members?: Member[];
}>();

const page = usePage();

// The signed-in user leads the stack and wears the accent so "this one is me" reads at a glance.
const memberAvatars = computed(() => {
    const currentUserId = page.props.auth?.user?.id;

    return (props.members ?? [])
        .map((member) => ({
            id: member.id,
            src: member.avatar_url && !member.avatar_url.includes('default-avatar') ? member.avatar_url : undefined,
            text: getInitials(member.name),
            alt: member.name,
            isCurrentUser: member.id === currentUserId,
        }))
        .sort((a, b) => Number(b.isCurrentUser) - Number(a.isCurrentUser));
});
</script>

<template>
    <section class="grid gap-3 md:grid-cols-3">
        <div class="flex items-center gap-4 rounded-lg p-4 ring ring-default">
            <div class="flex min-w-0 flex-1 flex-col gap-2.5">
                <div class="flex items-center gap-2">
                    <span class="flex size-7 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
                        <UIcon name="i-lucide-folder-kanban" class="size-3.5" />
                    </span>
                    <span class="truncate text-sm text-toned">My projects</span>
                </div>

                <div class="flex flex-col gap-0.5">
                    <p class="flex items-baseline gap-1.5">
                        <span class="text-3xl leading-none font-semibold tabular-nums">{{ stats.projects.total }}</span>
                        <span class="text-sm text-toned">projects</span>
                    </p>
                    <span class="text-xs text-muted">Every project you belong to</span>
                </div>
            </div>

            <ProgressRing :value="stats.projects.progress" label="Progress" />
        </div>

        <div class="flex items-center gap-4 rounded-lg p-4 ring ring-default">
            <div class="flex min-w-0 flex-1 flex-col gap-2.5">
                <div class="flex items-center gap-2">
                    <span class="flex size-7 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
                        <UIcon name="i-lucide-square-check-big" class="size-3.5" />
                    </span>
                    <span class="truncate text-sm text-toned">My tasks</span>
                </div>

                <div class="flex flex-col gap-0.5">
                    <p class="flex items-baseline gap-1.5">
                        <span class="text-3xl leading-none font-semibold tabular-nums">{{ stats.tasks.total }}</span>
                        <span class="text-sm text-toned">tasks</span>
                    </p>
                    <span class="text-xs text-muted">Everything you created or were assigned</span>
                </div>
            </div>

            <ProgressRing :value="stats.tasks.progress" label="Progress" />
        </div>

        <div class="flex items-center gap-4 rounded-lg p-4 ring ring-default">
            <div class="flex min-w-0 flex-1 flex-col gap-2.5">
                <div class="flex items-center gap-2">
                    <span class="flex size-7 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
                        <UIcon name="i-lucide-users" class="size-3.5" />
                    </span>
                    <span class="truncate text-sm text-toned">Teammates</span>
                </div>

                <Deferred data="members">
                    <template #fallback>
                        <div class="flex flex-col gap-1.5">
                            <USkeleton class="h-8 w-20" />
                            <USkeleton class="h-4 w-28" />
                        </div>
                    </template>

                    <div class="flex flex-col gap-0.5">
                        <p class="flex items-baseline gap-1.5">
                            <span class="text-3xl leading-none font-semibold tabular-nums">{{ memberAvatars.length }}</span>
                            <span class="text-sm text-toned">members</span>
                        </p>
                        <span class="text-xs text-muted">Across your {{ stats.projects.total }} projects</span>
                    </div>
                </Deferred>
            </div>

            <Deferred data="members">
                <template #fallback>
                    <USkeleton class="size-15 shrink-0 rounded-full" />
                </template>

                <div class="flex shrink-0 flex-col items-center gap-1.5">
                    <div class="flex h-15 items-center">
                        <UAvatarGroup v-if="memberAvatars.length" :max="5" size="md">
                            <UAvatar
                                v-for="avatar in memberAvatars"
                                :key="avatar.id"
                                :src="avatar.src"
                                :text="avatar.text"
                                :alt="avatar.alt"
                                :title="avatar.alt"
                                :class="avatar.isCurrentUser && !avatar.src ? 'bg-primary text-inverted' : undefined"
                            />
                        </UAvatarGroup>
                    </div>
                    <span class="text-xs text-muted">Your team</span>
                </div>
            </Deferred>
        </div>
    </section>
</template>
