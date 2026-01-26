<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

// Components
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';

const passwordInput = ref<HTMLInputElement | null>(null);
const visible = ref(false);

const form = useForm({
    password: '',
});

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    visible.value = false;
    form.clearErrors();
    form.reset();
};
</script>

<template>
    <div class="space-y-6">
        <HeadingSmall title="Delete account" description="Delete your account and all of its resources" />
        <div class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
            <div class="relative space-y-0.5 text-red-600 dark:text-red-100">
                <p class="font-medium">Warning</p>
                <p class="text-sm">Please proceed with caution, this cannot be undone.</p>
            </div>

            <Button label="Delete account" severity="danger" @click="visible = true" />

            <Dialog
                v-model:visible="visible"
                modal
                header="Are you sure you want to delete your account?"
                :style="{ width: '32rem' }"
                @hide="closeModal"
            >
                <template #default>
                    <p class="mb-6 text-sm text-gray-600 dark:text-gray-400">
                        Once your account is deleted, all of its resources and data will also be permanently deleted. Please enter your password to
                        confirm you would like to permanently delete your account.
                    </p>

                    <form @submit.prevent="deleteUser">
                        <div class="grid gap-2">
                            <label for="password" class="sr-only">Password</label>
                            <InputText
                                id="password"
                                v-model="form.password"
                                type="password"
                                placeholder="Password"
                                ref="passwordInput"
                                class="w-full"
                            />
                            <InputError :message="form.errors.password" />
                        </div>
                    </form>
                </template>

                <template #footer>
                    <div class="flex justify-end gap-2">
                        <Button label="Cancel" severity="secondary" @click="closeModal" />
                        <Button label="Delete account" severity="danger" @click="deleteUser" :disabled="form.processing" :loading="form.processing" />
                    </div>
                </template>
            </Dialog>
        </div>
    </div>
</template>
