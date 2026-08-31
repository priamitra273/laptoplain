type Slots = Record<string, string>;

const COLORS = ['primary', 'secondary', 'success', 'info', 'warning', 'error'] as const;

const ring = 'outline-0 transition-[color,background-color,outline-color,outline-width,box-shadow]';

const focusRing = () => [
    ...COLORS.map((color) => ({ color, class: `${ring} outline-${color}/50` })),
    { color: 'neutral', class: `${ring} outline-inverted/50` },
];

const focusRingOn = (slot: string) =>
    Object.fromEntries([
        ...COLORS.map((color) => [color, { [slot]: `${ring} outline-${color}/50` }]),
        ['neutral', { [slot]: `${ring} outline-inverted/50` }],
    ]);

const panel = 'bg-card rounded-md shadow-lg';

const overlay = 'bg-black/40 backdrop-blur-sm';

const softBadge = (color: string) =>
    `bg-${color}-50 text-${color}-700 dark:bg-${color}-400/10 dark:text-${color}-300`;

const button = {
    slots: {
        base: `${ring} justify-center whitespace-nowrap disabled:opacity-50 aria-disabled:opacity-50 disabled:pointer-events-none aria-disabled:pointer-events-none active:translate-y-px`,
    },
    variants: {
        size: {
            xs: { base: 'h-7 py-0 px-2.5 text-xs gap-1' },
            sm: { base: 'h-8 py-0 px-3 text-sm gap-1.5' },
            md: { base: 'h-9 py-0 px-4 text-sm gap-2' },
            lg: { base: 'h-10 py-0 px-6 text-sm gap-2' },
            xl: { base: 'h-11 py-0 px-8 text-base gap-2' },
        },
    },
    compoundVariants: [
        ...COLORS.flatMap((color) => [
            {
                color,
                variant: 'solid',
                class: `shadow-xs hover:bg-${color}/90 active:bg-${color}/90 outline-${color}/50`,
            },
            {
                color,
                variant: 'outline',
                class: `shadow-xs outline-${color}/50`,
            },
            {
                color,
                variant: 'subtle',
                class: `shadow-xs outline-${color}/50`,
            },
            { color, variant: 'soft', class: `outline-${color}/50` },
            { color, variant: 'ghost', class: `outline-${color}/50` },
            { color, variant: 'link', class: `outline-${color}/50` },
        ]),
        {
            color: 'neutral',
            variant: 'solid',
            class: 'shadow-xs hover:bg-inverted/90 active:bg-inverted/90 outline-inverted/50',
        },
        {
            color: 'neutral',
            variant: 'outline',
            class: 'shadow-xs outline-inverted/50',
        },
        {
            color: 'neutral',
            variant: 'subtle',
            class: 'shadow-xs outline-inverted/50',
        },
        { color: 'neutral', variant: 'soft', class: 'outline-inverted/50' },
        { color: 'neutral', variant: 'ghost', class: 'outline-inverted/50' },
        { color: 'neutral', variant: 'link', class: 'outline-inverted/50' },
        { size: 'xs', square: true, class: 'size-7 p-0' },
        { size: 'sm', square: true, class: 'size-8 p-0' },
        { size: 'md', square: true, class: 'size-9 p-0' },
        { size: 'lg', square: true, class: 'size-10 p-0' },
        { size: 'xl', square: true, class: 'size-11 p-0' },
    ],
};

const fieldSizes = {
    xs: { base: 'h-7 py-0 px-2.5 text-xs' },
    sm: { base: 'h-8 py-0 px-3 text-sm' },
    md: { base: 'h-9 py-0 px-3 text-sm' },
    lg: { base: 'h-10 py-0 px-3.5 text-sm' },
    xl: { base: 'h-11 py-0 px-4 text-base' },
};

const menuSizes = {
    xs: { item: 'px-1.5 py-1 gap-1.5', label: 'px-1.5 py-1' },
    sm: { item: 'px-2 py-1 gap-2', label: 'px-2 py-1' },
    md: { item: 'px-2 py-1.5 gap-2', label: 'px-2 py-1.5' },
    lg: { item: 'px-2.5 py-2 gap-2', label: 'px-2.5 py-2' },
    xl: { item: 'px-3 py-2 gap-2.5', label: 'px-3 py-2' },
};

const mergeSizes = (a: Record<string, Slots>, b: Record<string, Slots>) =>
    Object.fromEntries(Object.keys(a).map((size) => [size, { ...a[size], ...b[size] }]));

const fieldCompound = [{ class: 'shadow-xs' }, ...focusRing()];

const placeholderSlots = {
    base: 'placeholder:text-muted',
    placeholder: 'text-muted',
    segment: 'data-placeholder:text-muted',
    tagsInput: 'placeholder:text-muted',
};

const field = {
    slots: placeholderSlots,
    variants: { size: fieldSizes },
    compoundVariants: fieldCompound,
};

const fieldWithPanel = { ...field, slots: { ...placeholderSlots, content: panel } };

const fieldWithMenu = {
    ...field,
    slots: { ...placeholderSlots, content: panel },
    variants: { size: mergeSizes(fieldSizes, menuSizes) },
};

export default {
    button,
    input: field,
    inputNumber: field,
    inputTags: field,
    inputDate: fieldWithPanel,
    inputTime: fieldWithPanel,
    select: fieldWithMenu,
    selectMenu: fieldWithMenu,
    inputMenu: fieldWithMenu,
    listbox: { variants: { size: menuSizes } },
    pinInput: {
        variants: {
            size: {
                xs: { base: 'size-7 text-xs' },
                sm: { base: 'size-8 text-xs' },
                md: { base: 'size-9 text-sm' },
                lg: { base: 'size-10 text-sm' },
                xl: { base: 'size-11 text-base' },
            },
        },
        compoundVariants: [{ variant: ['outline', 'subtle'], class: 'shadow-xs' }, ...focusRing()],
    },
    textarea: {
        slots: { base: 'placeholder:text-muted' },
        variants: {
            size: {
                xs: { base: 'min-h-14 px-2 py-1.5 text-xs' },
                sm: { base: 'min-h-15 px-2.5 py-1.5 text-xs' },
                md: { base: 'min-h-16 px-3 py-2 text-sm' },
                lg: { base: 'min-h-18 px-3.5 py-2 text-sm' },
                xl: { base: 'min-h-20 px-4 py-2.5 text-base' },
            },
        },
        compoundVariants: [{ variant: ['outline', 'subtle'], class: 'shadow-xs' }, ...focusRing()],
    },
    checkbox: {
        slots: { base: 'shadow-xs' },
        variants: { color: focusRingOn('base') },
    },
    checkboxGroup: {
        slots: { base: 'shadow-xs' },
        variants: { color: focusRingOn('base') },
    },
    radioGroup: {
        slots: { base: 'shadow-xs' },
        variants: { color: focusRingOn('base') },
    },
    switch: {
        slots: { base: 'shadow-xs', thumb: 'shadow-sm' },
        variants: { color: focusRingOn('base') },
    },
    slider: {
        slots: { thumb: 'shadow-sm' },
        variants: { color: focusRingOn('thumb') },
    },
    fileUpload: {
        compoundVariants: focusRing(),
    },
    popover: { slots: { content: `${panel} p-4` } },
    dropdownMenu: { slots: { content: panel }, variants: { size: menuSizes } },
    contextMenu: { slots: { content: panel }, variants: { size: menuSizes } },
    commandPalette: {
        slots: { root: 'bg-card' },
        variants: { size: menuSizes },
    },
    tooltip: {
        slots: {
            content: 'bg-card rounded-md shadow-md h-auto px-3 py-1.5 text-xs',
        },
    },
    modal: {
        slots: {
            content: 'bg-card divide-y-0 gap-4',
            header: 'flex-col items-stretch p-0 sm:px-0 min-h-0 gap-2 text-center sm:text-left',
            body: 'p-0 sm:p-0',
            footer: 'flex-col-reverse items-stretch sm:flex-row sm:items-center sm:justify-end p-0 sm:px-0 gap-2',
            title: 'text-lg leading-none tracking-tight mb-3',
            description: 'mt-0',
        },
        variants: {
            overlay: { true: { overlay } },
            fullscreen: {
                false: { content: 'max-w-100 rounded-xl p-6 shadow-xl' },
            },
        },
    },
    slideover: {
        slots: {
            overlay,
            content: 'bg-card divide-y-0 gap-4',
            header: 'flex-col items-stretch min-h-0 gap-1.5 p-4 sm:px-4',
            body: 'px-4 py-0 sm:px-4 sm:py-0',
            footer: 'flex-col items-stretch mt-auto gap-2 p-4 sm:px-4',
            description: 'mt-0',
        },
        variants: {
            side: {
                left: { content: 'max-w-sm' },
                right: { content: 'max-w-sm' },
            },
        },
    },
    drawer: {
        slots: {
            content: 'bg-card',
            container: 'gap-0 p-0',
            header: 'min-h-0 gap-1.5 p-4',
            body: 'p-0',
            footer: 'mt-auto gap-2 p-4',
            overlay,
        },
    },
    toast: { slots: { root: 'bg-card rounded-lg' } },

    card: {
        slots: {
            root: 'rounded-lg shadow-sm flex flex-col gap-4 py-6',
            header: 'px-6 py-0 gap-2',
            body: 'px-6 py-0',
            footer: 'px-6 py-0',
            title: 'leading-none font-semibold',
            description: 'text-sm text-muted',
        },
        variants: {
            variant: {
                outline: { root: 'bg-card divide-y-0' },
                subtle: { root: 'divide-y-0' },
                soft: { root: 'divide-y-0' },
            },
        },
    },
    badge: {
        slots: { base: 'w-fit shrink-0 justify-center whitespace-nowrap' },
        variants: {
            size: {
                md: { base: 'rounded-sm px-2 py-0.5' },
                lg: { base: 'rounded-sm px-2.5 py-[3px]' },
                xl: { base: 'rounded-md px-2.5 py-1' },
            },
        },
        compoundVariants: [
            ...COLORS.flatMap((color) => [
                { color, variant: 'soft', class: softBadge(color) },
                {
                    color,
                    variant: 'subtle',
                    class: `${softBadge(color)} ring ring-inset ring-${color}-600/15 dark:ring-${color}-400/20`,
                },
            ]),
            { color: 'neutral', variant: 'soft', class: 'bg-elevated text-toned' },
            { color: 'neutral', variant: 'subtle', class: 'bg-elevated text-toned ring ring-inset ring-accented' },
        ],
        defaultVariants: { variant: 'soft' },
    },
    alert: { slots: { root: 'gap-3' } },
    empty: { slots: { root: 'rounded-lg gap-6 p-6 md:p-12' } },
    accordion: { slots: { trigger: 'py-4', body: 'pb-4' } },
    tabs: {
        variants: {
            variant: {
                pill: {
                    list: 'rounded-md p-0.5',
                    indicator: 'rounded-sm',
                    trigger: 'rounded-sm',
                },
            },
        },
    },
    table: {
        slots: {
            root: 'isolate',
            th: 'h-10 px-3 py-0 text-xs font-medium text-muted',
            td: 'p-3 text-default',
            tr: 'hover:bg-elevated/50 transition-colors',
        },
    },
    link: { base: 'outline-primary/50' },

    navigationMenu: {
        slots: {
            link: 'gap-2 px-2',
            linkLeadingIcon: 'size-4',
            linkTrailingIcon: 'size-4',
            childLinkIcon: 'size-4',
            label: 'px-2 text-xs font-medium text-muted',
        },
        compoundVariants: [
            {
                variant: 'pill',
                active: true,
                class: { link: 'before:shadow-xs before:bg-primary-50 dark:before:bg-primary-950/50' },
            },
            {
                orientation: 'vertical',
                collapsed: true,
                class: {
                    list: 'flex flex-col gap-1',
                    // trigger tetap tersorot selama popover-nya terbuka; tanpa ini ia
                    // kehilangan hover begitu kursor pindah ke panel dan tampak lepas
                    link: 'justify-center size-9 p-0 mx-auto data-[state=open]:text-highlighted data-[state=open]:before:bg-elevated',
                    linkLeadingIcon: 'size-5',
                    content: `${panel} p-1`,
                    childLabel: `${menuSizes.md.label} font-semibold`,
                    childLink: `${menuSizes.md.item} items-center rounded-sm`,
                },
            },
        ],
        defaultVariants: { color: 'neutral' },
    },
    dashboardSidebar: {
        slots: {
            root: 'group/sidebar p-2',
            header: 'gap-2 px-2 group-data-[collapsed=true]/sidebar:px-0',
            body: 'gap-2 px-2 py-2 group-data-[collapsed=true]/sidebar:px-0',
            footer: 'px-2 py-2 group-data-[collapsed=true]/sidebar:px-0',
        },
        variants: {
            side: { left: { root: 'border-e-0' } },
        },
    },
};
