<script setup lang="ts">
import { useConfirmDialog } from '@/composables/useConfirmDialog';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { getInitials } from '@/lib/utils';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AvatarCropperDialog from './AvatarCropperDialog.vue';
import DeleteAccountDialog from './DeleteAccountDialog.vue';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const page = usePage();
const user = computed(() => page.props.auth.user);

const form = useForm({
    name: user.value.name,
    email: user.value.email,
    avatar: null as File | null,
});

const previewUrl = ref<string | null>(user.value.avatar_url ?? null);
const avatarInitials = computed(() => getInitials(user.value.name));

const overlay = useOverlay();
const confirm = useConfirmDialog();

const pickedFile = ref<File | null>(null);

const onFilePicked = async (file: File | null | undefined) => {
    pickedFile.value = null;
    if (!file) return;

    const src = URL.createObjectURL(file);
    const modal = overlay.create(AvatarCropperDialog, { destroyOnClose: true, props: { src } });
    const cropped = await modal.open();
    URL.revokeObjectURL(src);

    if (cropped) {
        form.avatar = cropped;
        previewUrl.value = URL.createObjectURL(cropped);
    }
};

const removePickedAvatar = () => {
    form.avatar = null;
    previewUrl.value = user.value.avatar_url ?? null;
};

const deletingAvatar = ref(false);

const deleteSavedAvatar = async () => {
    const confirmed = await confirm({
        title: 'Delete profile picture',
        description: 'Your avatar will be replaced with your initials. This cannot be undone.',
    });

    if (!confirmed) return;

    deletingAvatar.value = true;

    router.delete(route('profile.avatar.destroy'), {
        preserveScroll: true,
        onSuccess: () => {
            previewUrl.value = null;
        },
        onFinish: () => {
            deletingAvatar.value = false;
        },
    });
};

const submit = () => {
    form.transform((data) => ({ ...data, _method: 'PATCH' })).post(route('profile.update'), {
        preserveScroll: true,
        forceFormData: true,
    });
};

const openDeleteAccount = () => {
    overlay.create(DeleteAccountDialog, { destroyOnClose: true }).open();
};
</script>

<template>
    <AppLayout title="Profile settings">
        <Head title="Profile settings" />

        <SettingsLayout>
            <div class="flex flex-col gap-8">
                <div class="flex flex-col gap-6">
                    <HeadingSmall title="Profile information" description="Update your name, email address, and profile picture" />

                    <form class="flex flex-col gap-6" @submit.prevent="submit">
                        <div class="flex flex-col gap-2">
                            <Label value="Profile picture" />

                            <div class="flex items-center gap-4">
                                <UAvatar :src="previewUrl ?? undefined" :text="avatarInitials" :alt="user.name" size="3xl" />

                                <div class="flex items-center gap-2">
                                    <UFileUpload
                                        :model-value="pickedFile"
                                        accept="image/*"
                                        variant="button"
                                        label="Choose image"
                                        icon="i-lucide-upload"
                                        color="neutral"
                                        @update:model-value="onFilePicked"
                                    />

                                    <UButton
                                        v-if="form.avatar"
                                        label="Remove"
                                        color="neutral"
                                        variant="ghost"
                                        size="sm"
                                        @click="removePickedAvatar"
                                    />
                                    <UButton
                                        v-else-if="user.avatar_url"
                                        label="Delete avatar"
                                        color="error"
                                        variant="ghost"
                                        size="sm"
                                        :loading="deletingAvatar"
                                        @click="deleteSavedAvatar"
                                    />
                                </div>
                            </div>

                            <p class="text-xs text-muted">JPG, PNG or GIF. Max size 2MB.</p>
                            <InputError v-if="form.errors.avatar" :message="form.errors.avatar" />
                        </div>

                        <div class="flex flex-col gap-2">
                            <Label value="Name" required />
                            <UInput v-model="form.name" placeholder="Full name" />
                            <InputError v-if="form.errors.name" :message="form.errors.name" />
                        </div>

                        <div class="flex flex-col gap-2">
                            <Label value="Email address" required />
                            <UInput v-model="form.email" type="email" placeholder="Email address" />
                            <InputError v-if="form.errors.email" :message="form.errors.email" />
                        </div>

                        <UAlert
                            v-if="mustVerifyEmail && !user.email_verified_at"
                            color="warning"
                            variant="subtle"
                            title="Your email address is unverified."
                        >
                            <template #description>
                                <Link :href="route('verification.send')" method="post" as="button" class="font-medium underline">
                                    Click here to resend the verification email.
                                </Link>
                            </template>
                        </UAlert>

                        <UAlert
                            v-if="status === 'verification-link-sent'"
                            color="success"
                            variant="subtle"
                            description="A new verification link has been sent to your email address."
                        />

                        <div class="flex items-center gap-4">
                            <UButton type="submit" label="Save changes" :loading="form.processing" :disabled="form.processing" />
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

                <div class="flex flex-col gap-4 rounded-lg border border-error/20 bg-error/5 p-4">
                    <HeadingSmall title="Delete account" description="Delete your account and all of its resources" />
                    <p class="text-sm text-error">Please proceed with caution, this cannot be undone.</p>
                    <UButton label="Delete account" color="error" class="self-start" @click="openDeleteAccount" />
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
