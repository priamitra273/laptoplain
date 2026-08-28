<script setup lang="ts">
const hints = [
    {
        title: 'What lands here',
        body: 'Your overdue tasks, anything due within the next seven days, and work that is currently blocked.',
    },
    {
        title: 'Only what is yours',
        body: 'Other people\u2019s projects never show up on this dashboard, super admins included. Use the Projects page to browse everything.',
    },
    {
        title: 'After an invite',
        body: 'The moment a PM adds you to a project, your summary and task list fill in on this page.',
    },
];
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex flex-col items-center gap-2 rounded-lg border border-dashed border-default bg-elevated/30 px-8 py-12 text-center">
            <span class="mb-2 flex size-10 items-center justify-center rounded-lg bg-primary/10 ring ring-primary/20">
                <UIcon name="i-lucide-folder-open" class="size-5 text-primary" />
            </span>

            <h2 class="text-base font-semibold text-highlighted">Nothing in your workspace yet</h2>

            <p class="max-w-md text-sm leading-relaxed text-muted">
                This dashboard only shows projects you are a member of and tasks you created or were assigned. Start a project, or ask a PM to add you
                to one that is already running.
            </p>

            <div class="mt-3 flex flex-wrap justify-center gap-2">
                <UButton :to="route('project.index')" label="Browse projects" icon="i-lucide-briefcase" />
                <UButton :to="route('task.index')" label="Browse tasks" icon="i-lucide-square-check-big" color="neutral" variant="outline" />
            </div>
        </div>

        <div class="grid gap-2.5 md:grid-cols-3">
            <div v-for="hint in hints" :key="hint.title" class="flex flex-col gap-1.5 rounded-lg p-4 ring ring-default">
                <h3 class="text-sm font-semibold text-highlighted">{{ hint.title }}</h3>
                <p class="text-xs leading-relaxed text-muted">{{ hint.body }}</p>
            </div>
        </div>
    </div>
</template>
