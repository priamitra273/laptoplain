<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <div class="overflow-hidden margin-0 relative h-screen bg-surface-100">
        <div class="grid sm:grid-cols-3 lg:grid-cols-2 h-full">
            <div class="hidden sm:block bg-gradient-to-br from-[#536976] to-[#292E49] overflow-hidden">
                <img src="https://images.unsplash.com/photo-1488229297570-58520851e868?q=80&w=1469&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" alt="" srcset="" class="w-full h-full object-cover" />
            </div>

            <div class="col-span-2 lg:col-span-1 flex items-center justify-center">
                <div>
                    <div class="w-full text-center">
                        <Head title="Register" />
                        
                        <form class="px-12 md:p-0 w-[29rem] relative" @submit.prevent="submit">
                            <div class="col-span-9 text-left mb-8">
                                <h2 class="mb-1 text-3xl font-serif text-surface-700 dark:text-surface-900">Create Account</h2>
                                <span class="text-surface-500 dark:text-surface-300">Enter your details below to create your account</span>
                            </div>

                            <div class="grid grid-cols-12 gap-4">
                                <div class="col-span-12 text-left">
                                    <Label class="text-surface-400 dark:text-surface-400 mb-1">Full name</Label>
                                    <div class="mt-1">
                                        <Input 
                                            id="name"
                                            v-model="form.name" 
                                            type="text" 
                                            placeholder="Full name"
                                            class="w-full" 
                                            required
                                            autofocus
                                            :tabindex="1"
                                            autocomplete="name"
                                        />
                                        <InputError :message="form.errors.name" class="mt-1 inline-block text-red-600 text-sm" />
                                    </div>
                                </div>

                                <div class="col-span-12 text-left">
                                    <Label class="text-surface-400 dark:text-surface-400 mb-1">Email address</Label>
                                    <div class="mt-1">
                                        <Input 
                                            id="email"
                                            v-model="form.email" 
                                            type="email" 
                                            placeholder="email@example.com" 
                                            class="w-full" 
                                            required
                                            :tabindex="2"
                                            autocomplete="email"
                                        />
                                        <InputError :message="form.errors.email" class="mt-1 inline-block text-red-600 text-sm" />
                                    </div>
                                </div>

                                <div class="col-span-12 text-left">
                                    <Label class="text-surface-400 dark:text-surface-400 mb-1">Password</Label>
                                    <div class="mt-1">
                                        <Input 
                                            id="password"
                                            v-model="form.password" 
                                            type="password" 
                                            placeholder="Password" 
                                            class="w-full"
                                            required
                                            :tabindex="3"
                                            autocomplete="new-password"
                                        />
                                        <InputError :message="form.errors.password" class="mt-1 inline-block text-red-600 text-sm" />
                                    </div>
                                </div>

                                <div class="col-span-12 text-left">
                                    <Label class="text-surface-400 dark:text-surface-400 mb-1">Confirm password</Label>
                                    <div class="mt-1">
                                        <Input 
                                            id="password_confirmation"
                                            v-model="form.password_confirmation" 
                                            type="password" 
                                            placeholder="Confirm password" 
                                            class="w-full"
                                            required
                                            :tabindex="4"
                                            autocomplete="new-password"
                                        />
                                        <InputError :message="form.errors.password_confirmation" class="mt-1 inline-block text-red-600 text-sm" />
                                    </div>
                                </div>

                                <div class="col-span-12">
                                    <Button 
                                        type="submit" 
                                        class="w-full mt-4" 
                                        :tabindex="5" 
                                        :disabled="form.processing"
                                    >
                                        <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                                        Create account
                                    </Button>
                                </div>

                                <div class="col-span-12 text-center text-sm text-muted-foreground">
                                    Already have an account?
                                    <TextLink :href="route('login')" :tabindex="6">Log in</TextLink>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
