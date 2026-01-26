<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AppearanceTabs from '@/components/AppearanceTabs.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';

import Card from 'primevue/card';
import Divider from 'primevue/divider';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Appearance settings',
        href: '/settings/appearance',
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Appearance settings" />

        <SettingsLayout>
            <Card>
                <template #content>
                    <div class="space-y-6">
                        <div>
                            <h2 class="mb-2 text-xl font-semibold">Appearance settings</h2>
                            <p class="text-surface-600 dark:text-surface-400">Update your account's appearance settings</p>
                        </div>

                        <Divider />

                        <AppearanceTabs />
                    </div>
                </template>
            </Card>
        </SettingsLayout>
    </AppLayout>
</template>
