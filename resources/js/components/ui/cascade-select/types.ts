import type { ButtonProps, DropdownMenuProps } from '@nuxt/ui';

/** Satu option mentah dari pemanggil — bentuknya bebas, dibaca lewat accessor. */
export type CascadeOption = Record<string, unknown>;

/** Cara membaca satu field option: nama properti (boleh dot-path) atau getter. */
export type CascadeOptionAccessor<T = unknown> = string | ((option: CascadeOption) => T);

/** Satu simpul hasil normalisasi `options`. `children === null` menandai leaf. */
export interface CascadeNode {
    /** Option aslinya, apa adanya. */
    option: CascadeOption;
    label: string;
    /** Hanya leaf yang punya nilai; grup selalu `undefined`. */
    value: unknown;
    disabled: boolean;
    /** Kedalaman 0-based. */
    level: number;
    /** Path indeks (mis. `"0.2.1"`) — pembanding simpul terpilih. */
    key: string;
    children: CascadeNode[] | null;
}

/** `T` adalah tipe nilai leaf — hasil `optionValue`, atau option itu sendiri tanpa itu. */
export interface CascadeSelectProps<T = unknown> {
    modelValue?: T;
    /** Daftar option bertingkat. */
    options?: CascadeOption[];
    /** Label leaf. Tanpa ini option-nya sendiri yang dipakai. */
    optionLabel?: CascadeOptionAccessor;
    /** Nilai leaf. Tanpa ini option-nya sendiri yang jadi nilai. */
    optionValue?: CascadeOptionAccessor;
    /** Penanda option yang dimatikan. */
    optionDisabled?: CascadeOptionAccessor<boolean>;
    /** Label grup. Tanpa ini `optionLabel` yang dipakai. */
    optionGroupLabel?: CascadeOptionAccessor;
    /**
     * Sumber anak sebuah grup:
     *
     * - `string[]` — satu properti per level, mis. `['kota', 'kecamatan']`
     * - `string` — properti yang sama di tiap level, rekursif selama isinya array
     * - fungsi — dipanggil `(option)`, hasilnya dipakai sebagai anak
     */
    optionGroupChildren?: string | string[] | ((option: CascadeOption) => unknown);
    placeholder?: string;
    /** Teks saat `options` kosong. */
    emptyMessage?: string;
    /** Menampilkan tombol untuk mengosongkan nilai. */
    showClear?: boolean;
    loading?: boolean;
    disabled?: boolean;
    size?: ButtonProps['size'];
    color?: ButtonProps['color'];
    variant?: ButtonProps['variant'];
    trailingIcon?: string;
    clearIcon?: string;
    /**
     * Diteruskan ke `UDropdownMenu` (align, side, sideOffset, …). `class` di sini hanya
     * kena panel akar — `ui.content` milik UDropdownMenu ikut ke semua submenu.
     */
    content?: DropdownMenuProps['content'] & { class?: unknown };
    /** Dibaca `useFormField` untuk menyambung ke `UFormField`. */
    id?: string;
    name?: string;
    highlight?: boolean;
    class?: unknown;
}
