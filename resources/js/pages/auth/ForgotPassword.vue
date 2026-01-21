<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import bgImage from '@/images/Bg.jpg';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <div class="margin-0 relative h-screen overflow-hidden bg-surface-100">
        <div class="grid h-full sm:grid-cols-3 lg:grid-cols-2">
            <div class="hidden overflow-hidden bg-gradient-to-br from-[#536976] to-[#292E49] sm:block">
                <img :src="bgImage" alt="Background" srcset="" class="h-full w-full object-cover" />
            </div>

            <div class="col-span-2 flex items-center justify-center lg:col-span-1">
                <div>
                    <div class="w-full text-center">
                        <Head title="Forgot password" />

                        <div class="relative w-[29rem] px-12 md:p-0">
                            <div class="col-span-9 mb-8 text-left">
                                <h2 class="mb-1 font-serif text-3xl text-surface-700 dark:text-surface-900">Forgot Password</h2>
                                <span class="text-surface-500 dark:text-surface-300">No worries, we'll send you instructions for reset!</span>
                            </div>

                            <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
                                {{ status }}
                            </div>

                            <form @submit.prevent="submit">
                                <div class="grid grid-cols-12 gap-8">
                                    <div class="col-span-12 text-left">
                                        <Label class="mb-1 text-surface-400 dark:text-surface-400">Email address</Label>
                                        <div class="mt-1">
                                            <Input
                                                id="email"
                                                v-model="form.email"
                                                type="email"
                                                name="email"
                                                placeholder="Enter your email address"
                                                class="w-full"
                                                autocomplete="off"
                                                autofocus
                                            />
                                            <InputError :message="form.errors.email" class="mt-1 inline-block text-sm text-red-600" />
                                        </div>
                                    </div>

                                    <div class="col-span-12">
                                        <Button type="submit" class="w-full" :disabled="form.processing" :loading="form.processing">
                                            <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                                            Reset Password
                                        </Button>

                                        <div class="mt-4 text-center text-sm text-muted-foreground">
                                            <span>Or, return to </span>
                                            <TextLink :href="route('login')">log in</TextLink>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
