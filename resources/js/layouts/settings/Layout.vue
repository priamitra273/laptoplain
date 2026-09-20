<script setup lang="ts">
const navItems = [
    { title: 'Profile', routeName: 'profile.edit' },
    { title: 'Password', routeName: 'password.edit' },
    { title: 'Appearance', routeName: 'appearance' },
];
</script>

<template>
    <div>
        <Heading title="Settings" description="Manage your profile and account settings" />

        <div class="flex flex-col gap-6 lg:flex-row lg:gap-10">
            <nav class="flex shrink-0 flex-col gap-1 lg:w-44">
                <UButton
                    v-for="item in navItems"
                    :key="item.routeName"
                    :to="route(item.routeName)"
                    :label="item.title"
                    color="neutral"
                    :variant="route().current(item.routeName) ? 'subtle' : 'ghost'"
                    class="shrink-0 justify-start"
                    block
                />
            </nav>

            <div class="max-w-2xl flex-1">
                <slot />
            </div>
        </div>
    </div>
</template>
