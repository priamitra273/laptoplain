<script setup lang="ts">
import { getInitials } from '@/lib/utils';
import { computed } from 'vue';
import type { UserPreview, WorkloadSummary } from './types';

const props = defineProps<{
    summary: WorkloadSummary;
}>();

const hasOverloaded = computed(() => props.summary.busy > 0);

const toAvatars = (users: UserPreview[]) => users.map((user) => ({ id: user.id, text: getInitials(user.name), alt: user.name }));

const overloadedAvatars = computed(() => toAvatars(props.summary.overloaded_preview));

const userAvatars = computed(() => toAvatars(props.summary.users_preview));
</script>

<template>
    <div class="grid gap-3 lg:grid-cols-[1.6fr_1fr]">
        <div
            class="flex items-center justify-between gap-4 rounded-lg p-4 ring"
            :class="hasOverloaded ? 'bg-error/5 ring-error/20' : 'bg-success/5 ring-success/20'"
        >
            <div class="flex min-w-0 flex-col gap-2">
                <span
                    class="flex size-7 shrink-0 items-center justify-center rounded-md bg-default ring"
                    :class="hasOverloaded ? 'ring-error/25' : 'ring-success/25'"
                >
                    <UIcon
                        :name="hasOverloaded ? 'i-lucide-triangle-alert' : 'i-lucide-circle-check'"
                        class="size-3.5"
                        :class="hasOverloaded ? 'text-error' : 'text-success'"
                    />
                </span>

                <p class="flex items-baseline gap-1.5">
                    <span class="text-3xl leading-none font-semibold tabular-nums" :class="hasOverloaded ? 'text-error' : 'text-success'">
                        {{ summary.busy }}
                    </span>
                    <span class="truncate text-sm text-toned">of {{ summary.total_users }} users Overloaded</span>
                </p>
            </div>

            <UAvatarGroup v-if="overloadedAvatars.length" :max="5" size="md" class="shrink-0">
                <UAvatar v-for="avatar in overloadedAvatars" :key="avatar.id" :text="avatar.text" :alt="avatar.alt" :title="avatar.alt" />
            </UAvatarGroup>
        </div>

        <div class="flex items-center justify-between gap-4 rounded-lg p-4 ring ring-default">
            <div class="flex min-w-0 flex-col gap-2">
                <span class="flex size-7 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
                    <UIcon name="i-lucide-users" class="size-3.5" />
                </span>

                <p class="flex items-baseline gap-1.5">
                    <span class="text-3xl leading-none font-semibold tabular-nums">{{ summary.total_users }}</span>
                    <span class="truncate text-sm text-toned">users</span>
                </p>
            </div>

            <UAvatarGroup v-if="userAvatars.length" :max="5" size="md" class="shrink-0">
                <UAvatar v-for="avatar in userAvatars" :key="avatar.id" :text="avatar.text" :alt="avatar.alt" :title="avatar.alt" />
            </UAvatarGroup>
        </div>
    </div>
</template>
