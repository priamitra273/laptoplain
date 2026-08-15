// Gaya ui-thing (shadcn, tema violet) untuk seluruh komponen Nuxt UI.
//
// Warna dan radius sudah ditangani token CSS di src/assets/main.css.
// Di sini hanya yang tidak bisa dicapai token: tinggi tetap, shadow, dan
// focus ring 3px. Nuxt UI sudah memakai `outline-3` untuk focus — yang beda
// hanya opacity (/25 → /50), jadi sebagian besar override cuma satu class.
//
// File ini sengaja ada di src/ supaya Tailwind ikut memindainya; class dari
// template literal (`outline-${color}/50`) tetap perlu @source inline di main.css.

// `neutral` sengaja dikecualikan: Nuxt UI memakai token `inverted`/`elevated`, bukan `bg-neutral`
const COLORS = ['primary', 'secondary', 'success', 'info', 'warning', 'error'] as const;

// `outline-style` tidak bisa ditransisikan, jadi tanpa `outline-0` ring akan lompat dari
// none ke solid. Dengan ring terpasang permanen selebar 0, `focus-visible:outline-3`
// bawaan Nuxt UI tinggal menumbuhkan lebarnya.
//
// Daftar transisi ditulis penuh karena tailwind-merge menganggapnya segrup dengan
// `transition-colors` bawaan — jadi ini mengganti, bukan menambah.
const ring = 'outline-0 transition-[color,background-color,outline-color,outline-width,box-shadow]';

/** focus ring shadcn (`ring-ring/50`) untuk komponen tanpa slot warna per-slot */
const focusRing = () => [
    ...COLORS.map((color) => ({ color, class: `${ring} outline-${color}/50` })),
    { color: 'neutral', class: `${ring} outline-inverted/50` },
];

/** idem, untuk komponen yang variant `color`-nya berupa object per slot */
const focusRingOn = (slot: string) =>
    Object.fromEntries([
        ...COLORS.map((color) => [color, { [slot]: `${ring} outline-${color}/50` }]),
        ['neutral', { [slot]: `${ring} outline-inverted/50` }],
    ]);

/** permukaan floating shadcn: rounded-md + border + shadow-md di atas --card */
const panel = 'bg-card rounded-md shadow-md';

// shadcn-flavored button: fixed heights, subtle shadow, /90 hover, stronger focus ring
const button = {
    slots: {
        base: `${ring} justify-center whitespace-nowrap disabled:opacity-50 aria-disabled:opacity-50 disabled:pointer-events-none aria-disabled:pointer-events-none active:translate-y-px`,
    },
    variants: {
        // shadcn menahan text-sm dari sm sampai lg — cuma tinggi dan padding yang berubah
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
            { color, variant: 'outline', class: `shadow-xs outline-${color}/50` },
            { color, variant: 'subtle', class: `shadow-xs outline-${color}/50` },
            { color, variant: 'soft', class: `outline-${color}/50` },
            { color, variant: 'ghost', class: `outline-${color}/50` },
            { color, variant: 'link', class: `outline-${color}/50` },
        ]),
        {
            color: 'neutral',
            variant: 'solid',
            class: 'shadow-xs hover:bg-inverted/90 active:bg-inverted/90 outline-inverted/50',
        },
        { color: 'neutral', variant: 'outline', class: 'shadow-xs outline-inverted/50' },
        { color: 'neutral', variant: 'subtle', class: 'shadow-xs outline-inverted/50' },
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

// Kontrol form shadcn: tinggi tetap sejajar button, shadow-xs pada varian berbingkai.
const fieldSizes = {
    xs: { base: 'h-7 py-0 px-2.5 text-xs' },
    sm: { base: 'h-8 py-0 px-3 text-sm' },
    md: { base: 'h-9 py-0 px-3 text-sm' },
    lg: { base: 'h-10 py-0 px-3.5 text-sm' },
    xl: { base: 'h-11 py-0 px-4 text-base' },
};

/** item menu shadcn: `px-2 py-1.5 gap-2`; Nuxt UI defaultnya `p-1.5 gap-1.5` */
const menuSizes = {
    xs: { item: 'px-1.5 py-1 gap-1.5', label: 'px-1.5 py-1' },
    sm: { item: 'px-2 py-1 gap-2', label: 'px-2 py-1' },
    md: { item: 'px-2 py-1.5 gap-2', label: 'px-2 py-1.5' },
    lg: { item: 'px-2.5 py-2 gap-2', label: 'px-2.5 py-2' },
    xl: { item: 'px-3 py-2 gap-2.5', label: 'px-3 py-2' },
};

type Slots = Record<string, string>;
const mergeSizes = (a: Record<string, Slots>, b: Record<string, Slots>) =>
    Object.fromEntries(Object.keys(a).map((size) => [size, { ...a[size], ...b[size] }]));

const fieldCompound = [{ variant: ['outline', 'subtle'], class: 'shadow-xs' }, ...focusRing()];

const field = {
    variants: { size: fieldSizes },
    compoundVariants: fieldCompound,
};

/** field + panel bebas isi, untuk InputDate/InputTime */
const fieldWithPanel = { ...field, slots: { content: panel } };

/** field + panel berisi daftar item, untuk Select/SelectMenu/InputMenu */
const fieldWithMenu = {
    ...field,
    slots: { content: panel },
    variants: { size: mergeSizes(fieldSizes, menuSizes) },
};

export default {
    button,

    // ————— form —————
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
    // shadcn textarea tumbuh dari min-h-16, bukan tinggi tetap
    textarea: {
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
        // shadcn thumb datar; Nuxt UI defaultnya shadow-lg
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
    commandPalette: { slots: { root: 'bg-card' }, variants: { size: menuSizes } },
    tooltip: {
        slots: { content: 'bg-card rounded-md shadow-md h-auto px-3 py-1.5 text-xs' },
    },
    modal: {
        slots: {
            content: 'bg-card divide-y-0 p-6 gap-4',
            header: 'flex-col items-stretch p-0 sm:px-0 min-h-0 gap-2 text-center sm:text-left',
            body: 'p-0 sm:p-0',
            footer: 'flex-col-reverse items-stretch sm:flex-row sm:items-center sm:justify-end p-0 sm:px-0 gap-2',
            title: 'text-lg leading-none tracking-tight',
            description: 'mt-0',
        },
        variants: {
            overlay: { true: { overlay: 'bg-default/50 backdrop-blur-sm' } },
            fullscreen: { false: { content: 'max-w-100 rounded-xl shadow-lg' } },
        },
    },
    // Sheet: kebalikan Dialog — container cuma gap-4, padding p-4 menempel di tiap bagian.
    // Semua `sm:*` di sini menetralkan `sm:px-6`/`sm:p-6` bawaan Nuxt UI; ui-thing tetap 4.
    slideover: {
        slots: {
            // slideover tidak punya variant `overlay` (cuma prop boolean untuk render/tidak),
            // jadi warnanya harus di slot — kalau ditaruh di variants tidak pernah kepakai
            overlay: 'bg-default/50 backdrop-blur-sm',
            content: 'bg-card divide-y-0 gap-4',
            header: 'flex-col items-stretch min-h-0 gap-1.5 p-4 sm:px-4',
            body: 'px-4 py-0 sm:px-4 sm:py-0',
            // Sheet menumpuk tombol vertikal penuh; tidak jadi baris di sm seperti Dialog
            footer: 'flex-col items-stretch mt-auto gap-2 p-4 sm:px-4',
            description: 'mt-0',
        },
        // ui-thing `sm:max-w-sm` (24rem); Nuxt UI defaultnya max-w-md (28rem)
        variants: { side: { left: { content: 'max-w-sm' }, right: { content: 'max-w-sm' } } },
    },
    // Drawer: seperti Sheet, tapi tanpa gap sama sekali dan overlay lebih tipis
    drawer: {
        slots: {
            content: 'bg-card',
            container: 'gap-0 p-0',
            header: 'min-h-0 gap-1.5 p-4',
            body: 'p-0',
            footer: 'mt-auto gap-2 p-4',
            overlay: 'bg-default/40 backdrop-blur',
        },
    },
    toast: { slots: { root: 'bg-card rounded-lg' } },

    // ————— tampilan —————
    card: {
        // shadcn card: py-6 di root + gap-6 antar bagian, anaknya cuma px-6
        slots: {
            root: 'rounded-xl shadow-sm flex flex-col gap-6 py-6',
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
        // skala size tidak sejajar: Nuxt UI md (text-xs) = ui-thing sm, dan seterusnya
        slots: { base: 'w-fit shrink-0 justify-center whitespace-nowrap' },
        variants: {
            size: {
                md: { base: 'rounded-md px-2 py-0.5' },
                lg: { base: 'rounded-md px-2.5 py-[3px]' },
                xl: { base: 'rounded-md px-2.5 py-1' },
            },
        },
    },
    alert: { slots: { root: 'gap-3' } },
    empty: { slots: { root: 'rounded-lg gap-6 p-6 md:p-12' } },
    accordion: { slots: { trigger: 'py-4', body: 'pb-4' } },
    tabs: {
        // shadcn TabsList: rounded-md, padding tipis, trigger rounded-sm
        variants: {
            variant: {
                pill: { list: 'rounded-md p-0.5', indicator: 'rounded-sm', trigger: 'rounded-sm' },
            },
        },
    },
    table: {
        slots: {
            th: 'h-12 px-3 py-0 font-medium text-muted',
            td: 'p-3 text-default',
            tr: 'hover:bg-elevated/50',
        },
    },
    link: { base: 'outline-primary/50' },
};
