<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <div class="margin-0 relative h-screen overflow-hidden bg-surface-100">
        <div class="grid h-full sm:grid-cols-3 lg:grid-cols-2">
            <div class="hidden overflow-hidden bg-gradient-to-br from-[#536976] to-[#292E49] sm:block">
                <img
                    src="https://images.unsplash.com/photo-1488229297570-58520851e868?q=80&w=1469&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D"
                    alt=""
                    srcset=""
                    class="h-full w-full object-cover"
                />
            </div>

            <div class="col-span-2 flex items-center justify-center lg:col-span-1">
                <div>
                    <div class="w-full text-center">
                        <Head title="Log in" />

                        <form class="relative w-[29rem] px-12 md:p-0" @submit.prevent="submit">
                            <div class="col-span-9 mb-8 text-left">
                                <h2 class="mb-1 font-serif text-3xl text-surface-700 dark:text-surface-900">Welcome Back</h2>
                                <span class="text-surface-500 dark:text-surface-300">Enter your email and password to access your account</span>
                            </div>

                            <div class="grid grid-cols-12 gap-4">
                                <div class="col-span-12" v-if="status">
                                    <div class="mb-4 text-center text-sm font-medium text-green-600">
                                        {{ status }}
                                    </div>
                                </div>

                                <div class="col-span-12 text-left">
                                    <Label class="mb-1 text-surface-400 dark:text-surface-400">Email address</Label>
                                    <div class="mt-1">
                                        <Input
                                            id="email"
                                            v-model="form.email"
                                            type="email"
                                            placeholder="Email"
                                            class="w-full"
                                            required
                                            autofocus
                                            :tabindex="1"
                                            autocomplete="email"
                                        />
                                        <InputError :message="form.errors.email" class="mt-1 inline-block text-sm text-red-600" />
                                    </div>
                                </div>

                                <div class="col-span-12 text-left">
                                    <Label class="mb-1 text-surface-400 dark:text-surface-400">Password</Label>
                                    <div class="mt-1">
                                        <Input
                                            id="password"
                                            v-model="form.password"
                                            type="password"
                                            placeholder="Password"
                                            class="w-full"
                                            required
                                            :tabindex="2"
                                            autocomplete="current-password"
                                        />
                                        <InputError :message="form.errors.password" class="mt-1 inline-block text-sm text-red-600" />
                                    </div>
                                </div>

                                <div class="col-span-12 flex justify-between">
                                    <div class="flex items-center gap-2">
                                        <Checkbox id="remember" v-model:checked="form.remember" name="remember" :tabindex="3" />
                                        <Label for="remember" class="text-surface-500">Remember me</Label>
                                    </div>

                                    <div>
                                        <TextLink
                                            v-if="canResetPassword"
                                            :href="route('password.request')"
                                            class="flex justify-center text-sm text-gray-300"
                                            :tabindex="5"
                                        >
                                            Forgot Password?
                                        </TextLink>
                                    </div>
                                </div>

                                <div class="col-span-12">
                                    <Button type="submit" class="mt-4 w-full" :tabindex="4" :disabled="form.processing">
                                        <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                                        Log in
                                    </Button>
                                </div>

                                <div class="col-span-12 text-center text-sm text-muted-foreground">
                                    Don't have an account?
                                    <TextLink :href="route('register')" :tabindex="6">Sign up</TextLink>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
