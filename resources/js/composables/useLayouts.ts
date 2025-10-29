import { useStorage } from '@vueuse/core';
import { computed, reactive, Ref, ref, watch } from 'vue';

interface LayoutConfig {
    preset: string;
    primary: string;
    surface?: string|null;
    darkTheme: boolean;
    menuMode: string;
    menuTheme: string;
    topbarTheme: string;
    menuProfilePosition: string;
}

interface LayoutState {
    staticMenuDesktopInactive: boolean;
    overlayMenuActive: boolean;
    configSidebarVisible: boolean;
    staticMenuMobileActive: boolean;
    menuHoverActive: boolean;
    rightMenuActive: boolean;
    sidebarActive: boolean;
    anchored: boolean;
    activeMenuItem?: string|null,
    overlaySubmenuActive: boolean;
    menuProfileActive: boolean;
}

const layoutConfigFromStorage = useStorage<LayoutConfig>('layout-config', {
    preset: 'Aura',
    primary: 'indigo',
    surface: 'slate',
    darkTheme: false,
    menuMode: 'horizontal',
    menuTheme: 'light',
    topbarTheme: 'light',
    menuProfilePosition: 'end'
})

const layoutConfig = reactive<LayoutConfig>({...layoutConfigFromStorage.value});

const layoutState = reactive<LayoutState>({
    staticMenuDesktopInactive: false,
    overlayMenuActive: false,
    configSidebarVisible: false,
    staticMenuMobileActive: false,
    menuHoverActive: false,
    rightMenuActive: false,
    sidebarActive: false,
    anchored: false,
    activeMenuItem: null,
    overlaySubmenuActive: false,
    menuProfileActive: false
});

const outsideClickListener = ref<((event: MouseEvent) => void) | null>(null);

export function useLayout() {
    const setPrimary = (value: string) => {
        layoutConfig.primary = value;
    };

    const setSurface = (value?: string) => {
        layoutConfig.surface = value;
    };

    const setPreset = (value: string) => {
        layoutConfig.preset = value;
    };

    const setMenuMode = (mode: string) => {
        layoutConfig.menuMode = mode;
        layoutConfigFromStorage.value.menuMode = mode

        if (mode === 'static') {
            layoutState.staticMenuDesktopInactive = false;
        }
    };

    const showConfigSidebar = () => {
        layoutState.configSidebarVisible = true;
    };

    const showSidebar = () => {
        layoutState.rightMenuActive = true;
    };

    const setTopbarTheme = (value: string) => {
        layoutConfig.topbarTheme = value;
    };

    const setProfilePosition = (value: string) => {
        layoutConfig.menuProfilePosition = value;
    };

    const onMenuProfileToggle = () => {
        layoutState.menuProfileActive = !layoutState.menuProfileActive;
    };

    const toggleDarkMode = () => {
        if (!document.startViewTransition) {
            executeDarkModeToggle();

            return;
        }

        document.startViewTransition(() => executeDarkModeToggle(event));
    };

    const executeDarkModeToggle = () => {
        layoutConfig.darkTheme = !layoutConfig.darkTheme;
        layoutConfig.menuTheme = isDarkTheme.value ? 'dark' : 'light';
        layoutConfigFromStorage.value.menuTheme = isDarkTheme.value ? 'dark' : 'light';

        document.documentElement.classList.toggle('app-dark');
    };

    const setActiveMenuItem = (item: Ref<string> | string): void => {
        layoutState.activeMenuItem = typeof item === 'string' ? item : item.value;
    };

    const setMenuStates = (value: boolean) => {
        layoutState.overlaySubmenuActive = value;
        layoutState.menuHoverActive = value;
    };

    const setStaticMenuMobile = () => {
        layoutState.staticMenuMobileActive = !layoutState.staticMenuMobileActive;
    };

    const watchSidebarActive = () => {
        watch(isSidebarActive, (newVal) => {
            if (newVal) {
                bindOutsideClickListener();
            } else {
                unbindOutsideClickListener();
            }
        });
    };

    const onMenuToggle = () => {
        if (layoutConfig.menuMode === 'overlay') {
            layoutState.overlayMenuActive = !layoutState.overlayMenuActive;
        }

        if (window.innerWidth > 991) {
            layoutState.staticMenuDesktopInactive = !layoutState.staticMenuDesktopInactive;
        } else {
            layoutState.staticMenuMobileActive = !layoutState.staticMenuMobileActive;
        }
    };

    const onProfileSidebarToggle = () => {
        layoutState.rightMenuActive = !layoutState.rightMenuActive;
    };

    const onConfigSidebarToggle = () => {
        if (isSidebarActive.value) {
            resetMenu();
            unbindOutsideClickListener();
        }

        layoutState.configSidebarVisible = !layoutState.configSidebarVisible;
    };

    const onSidebarToggle = (value: boolean) => {
        layoutState.sidebarActive = value;
    };

    const onAnchorToggle = () => {
        layoutState.anchored = !layoutState.anchored;
    };

    const bindOutsideClickListener = () => {
        if (!outsideClickListener.value) {
            outsideClickListener.value = (event) => {
                if (isOutsideClicked(event)) {
                    resetMenu();
                }
            };
            document.addEventListener('click', outsideClickListener.value);
        }
    };

    const unbindOutsideClickListener = () => {
        if (outsideClickListener.value) {
            document.removeEventListener('click', outsideClickListener.value);
            outsideClickListener.value = null;
        }
    };

    const isOutsideClicked = (event: MouseEvent) : boolean => {
        const sidebarEl = document.querySelector('.layout-sidebar') as HTMLElement | null;
        const topbarButtonEl = document.querySelector('.layout-menu-button') as HTMLElement | null;

        return !(sidebarEl?.isSameNode(event.target as Node) || 
                sidebarEl?.contains(event.target as Node) || 
                topbarButtonEl?.isSameNode(event.target as Node) || 
                topbarButtonEl?.contains(event.target as Node));
    };

    const resetMenu = () => {
        layoutState.overlayMenuActive = false;
        layoutState.overlaySubmenuActive = false;
        layoutState.staticMenuMobileActive = false;
        layoutState.menuHoverActive = false;
        layoutState.configSidebarVisible = false;
    };

    const isSidebarActive = computed(() => layoutState.overlayMenuActive || layoutState.staticMenuMobileActive || layoutState.overlaySubmenuActive);

    const isDesktop = computed(() => window.innerWidth > 991);

    const isSlim = computed(() => layoutConfig.menuMode === 'slim');
    const isSlimPlus = computed(() => layoutConfig.menuMode === 'slim-plus');
    const isHorizontal = computed(() => layoutConfig.menuMode === 'horizontal');

    const isDarkTheme = computed(() => layoutConfig.darkTheme);
    const getPrimary = computed(() => layoutConfig.primary);
    const getSurface = computed(() => layoutConfig.surface);

    return {
        layoutConfig,
        layoutState,
        getPrimary,
        getSurface,
        isDarkTheme,
        setPrimary,
        setSurface,
        setPreset,
        setMenuMode,
        setTopbarTheme,
        setProfilePosition,
        onMenuProfileToggle,
        toggleDarkMode,
        onMenuToggle,
        onProfileSidebarToggle,
        setMenuStates,
        setStaticMenuMobile,
        watchSidebarActive,
        isSidebarActive,
        setActiveMenuItem,
        onConfigSidebarToggle,
        onSidebarToggle,
        onAnchorToggle,
        isSlim,
        isSlimPlus,
        isHorizontal,
        isDesktop,
        showConfigSidebar,
        showSidebar,
        unbindOutsideClickListener
    };
}
