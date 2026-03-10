<script setup>
import { useLayout } from '@/composables/useLayouts';
import { Link, router, usePage } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import { computed, ref } from 'vue';

const { layoutState, layoutConfig, onMenuProfileToggle, isHorizontal, isSlim } = useLayout();
const menuClass = computed(() => (isHorizontal.value ? 'overlay' : null));
const page = usePage();
const user = page.props.auth.user;
const previewImage = ref(user.avatar_url || null);
const avatarLabel = computed(() => user.name?.charAt(0).toUpperCase() || 'U');

async function logout() {
    router.post(route('logout'));
}

function navigateTo(route) {
    router.push(route);
    toggleMenu();
}

function toggleMenu() {
    const menu = document.querySelector('.menu-transition');

    if (layoutState.menuProfileActive) {
        menu.style.maxHeight = '0';
        menu.style.opacity = '0';
        if (isHorizontal.value) {
            menu.style.transform = 'scaleY(0.8)';
        }
    } else {
        menu.style.maxHeight = menu.scrollHeight + 'px';
        menu.style.opacity = '1';
        if (isHorizontal.value) {
            menu.style.transform = 'scaleY(1)';
        }
    }
    onMenuProfileToggle();
}

const iconClass = computed(() => {
    const profilePositionStart = layoutConfig.menuProfilePosition === 'start';

    return {
        'pi-angle-up':
            (layoutState.menuProfileActive && (profilePositionStart || isHorizontal.value)) ||
            (!layoutState.menuProfileActive && !profilePositionStart && !isHorizontal.value),
        'pi-angle-down':
            (!layoutState.menuProfileActive && profilePositionStart) ||
            (layoutState.menuProfileActive && !profilePositionStart) ||
            isHorizontal.value,
    };
});

function tooltipValue(tooltipText) {
    return isSlim.value ? tooltipText : null;
}
</script>

<template>
    <div class="layout-menu-profile">
        <button v-tooltip="{ value: tooltipValue('Profile') }" class="rounded-none" @click="toggleMenu()">
            <Avatar v-if="previewImage" size="large" :image="previewImage" shape="circle" class="border-2 border-neutral-200 dark:border-neutral-700" />
            <Avatar v-else :label="avatarLabel" size="large" shape="circle" class="bg-primary text-white" />
            <span>
                <strong>{{ user.name }}</strong>
                <!-- <small>Webmaster</small> -->
            </span>
            <i class="layout-menu-profile-toggler pi pi-fw" :class="iconClass"></i>
        </button>

        <ul :class="['menu-transition', menuClass]" style="max-height: 0; opacity: 0">
            <li v-tooltip="{ value: tooltipValue('Settings') }">
                <button>
                    <Link href="/settings">
                        <i class="pi pi-cog pi-fw"></i>
                        <span class="ml-2">Settings</span>
                    </Link>
                </button>
            </li>
            <li v-tooltip="{ value: tooltipValue('Logout') }">
                <button @click="logout">
                    <i class="pi pi-power-off pi-fw"></i>
                    <span>Logout</span>
                </button>
            </li>
        </ul>
    </div>
</template>

<style scoped>
.menu-transition {
    transition:
        max-height 400ms cubic-bezier(0.86, 0, 0.07, 1),
        opacity 400ms cubic-bezier(0.86, 0, 0.07, 1);
}
.menu-transition.overlay {
    transition:
        opacity 100ms linear,
        transform 120ms cubic-bezier(0, 0, 0.2, 1);
}
</style>
