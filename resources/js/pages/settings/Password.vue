<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: (errors) => {
            if (errors.password) {
                form.reset('password', 'password_confirmation');
            }

            if (errors.current_password) {
                form.reset('current_password');
            }
        },
    });
};
</script>

<template>
    <AppLayout title="Password settings">
        <Head title="Password settings" />

        <SettingsLayout>
            <div class="flex flex-col gap-6">
                <HeadingSmall title="Update password" description="Ensure your account is using a long, random password to stay secure" />

                <form class="flex flex-col gap-6" @submit.prevent="updatePassword">
                    <div class="flex flex-col gap-2">
                        <Label value="Current password" required />
                        <UInput v-model="form.current_password" type="password" placeholder="Current password" autocomplete="current-password" />
                        <InputError v-if="form.errors.current_password" :message="form.errors.current_password" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label value="New password" required />
                        <UInput v-model="form.password" type="password" placeholder="New password" autocomplete="new-password" />
                        <InputError v-if="form.errors.password" :message="form.errors.password" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label value="Confirm password" required />
                        <UInput v-model="form.password_confirmation" type="password" placeholder="Confirm password" autocomplete="new-password" />
                        <InputError v-if="form.errors.password_confirmation" :message="form.errors.password_confirmation" />
                    </div>

                    <div class="flex items-center gap-4">
                        <UButton type="submit" label="Save password" :loading="form.processing" :disabled="form.processing" />
                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <span v-if="form.recentlySuccessful" class="text-sm text-success">Saved.</span>
                        </Transition>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
