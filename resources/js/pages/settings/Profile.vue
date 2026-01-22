<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem, type SharedData, type User } from '@/types';

import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import FileUpload from 'primevue/fileupload';
import InlineMessage from 'primevue/inlinemessage';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: '/settings/profile',
    },
];

const page = usePage<SharedData>();
const user = page.props.auth.user as User;

const form = useForm({
    name: user.name,
    email: user.email,
    avatar: null as File | null,
});

const previewImage = ref<string | null>(user.avatar_url || null);

const onFileSelect = (event: any) => {
    const file = event.files[0];
    if (file) {
        form.avatar = file;

        // Create preview
        const reader = new FileReader();
        reader.onload = (e) => {
            previewImage.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const removeImage = () => {
    form.avatar = null;
    previewImage.value = user.avatar_url || null;
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        _method: 'PATCH',
    })).post(route('profile.update'), {
        preserveScroll: true,
        forceFormData: true,
    });
};

const avatarLabel = computed(() => {
    return user.name?.charAt(0).toUpperCase() || 'U';
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Profile settings" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall title="Profile information" description="Update your name, email address, and profile picture" />

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Avatar Upload Section -->
                    <div class="grid gap-3">
                        <label class="text-sm font-medium text-neutral-700 dark:text-neutral-200"> Profile Picture </label>

                        <div class="flex items-center gap-4">
                            <Avatar
                                v-if="previewImage"
                                :image="previewImage"
                                size="xlarge"
                                shape="circle"
                                class="border-2 border-neutral-200 dark:border-neutral-700"
                            />
                            <Avatar v-else :label="avatarLabel" size="xlarge" shape="circle" class="bg-primary text-white" />

                            <div class="flex gap-2">
                                <FileUpload
                                    mode="basic"
                                    accept="image/*"
                                    :maxFileSize="2000000"
                                    @select="onFileSelect"
                                    :auto="false"
                                    chooseLabel="Choose Image"
                                    class="p-button-sm"
                                />

                                <Button v-if="form.avatar" type="button" severity="secondary" size="small" @click="removeImage" label="Remove" />
                            </div>
                        </div>

                        <small class="text-neutral-500 dark:text-neutral-400"> JPG, PNG or GIF. Max size 2MB. </small>

                        <InlineMessage v-if="form.errors.avatar" severity="error">
                            {{ form.errors.avatar }}
                        </InlineMessage>
                    </div>

                    <!-- Name Field -->
                    <div class="grid gap-2">
                        <label for="name" class="text-sm font-medium text-neutral-700 dark:text-neutral-200"> Name </label>
                        <InputText id="name" v-model="form.name" placeholder="Full name" :invalid="!!form.errors.name" class="w-full" />
                        <InlineMessage v-if="form.errors.name" severity="error">
                            {{ form.errors.name }}
                        </InlineMessage>
                    </div>

                    <!-- Email Field -->
                    <div class="grid gap-2">
                        <label for="email" class="text-sm font-medium text-neutral-700 dark:text-neutral-200"> Email address </label>
                        <InputText
                            id="email"
                            v-model="form.email"
                            type="email"
                            placeholder="Email address"
                            :invalid="!!form.errors.email"
                            class="w-full"
                        />
                        <InlineMessage v-if="form.errors.email" severity="error">
                            {{ form.errors.email }}
                        </InlineMessage>
                    </div>

                    <!-- Email Verification Warning -->
                    <Message v-if="mustVerifyEmail && !user.email_verified_at" severity="warn" :closable="false">
                        Your email address is unverified.
                        <Link
                            :href="route('verification.send')"
                            method="post"
                            as="button"
                            class="ml-1 font-medium underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:!decoration-current"
                        >
                            Click here to resend the verification email.
                        </Link>
                    </Message>

                    <!-- Verification Success Message -->
                    <Message v-if="status === 'verification-link-sent'" severity="success" :closable="false">
                        A new verification link has been sent to your email address.
                    </Message>

                    <!-- Submit Button -->
                    <div class="flex items-center gap-4">
                        <Button type="submit" :loading="form.processing" label="Save Changes" />

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <span v-if="form.recentlySuccessful" class="text-sm text-green-600 dark:text-green-400"> Saved. </span>
                        </Transition>
                    </div>
                </form>
            </div>

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>
