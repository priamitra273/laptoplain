import type { ButtonProps, PopoverProps, TreeItem } from '@nuxt/ui';

/**
 * One node of the `options` tree. Extends `TreeItem` so the whole tree can be handed to
 * `UTree` as-is — `icon`, `disabled` and the per-item `ui` overrides all come from there.
 */
export interface TreeSelectNode extends TreeItem {
    /** Unique id. Required — selection and expansion state are keyed by this. */
    key: string;
    children?: TreeSelectNode[];
}

export type TreeSelectionMode = 'single' | 'multiple' | 'checkbox';

/** One search target: a property name (dot-path supported) or a getter. */
export type TreeFilterField = string | ((node: TreeSelectNode) => unknown);

export interface TreeSelectProps {
    /** A single key in `single` mode, an array of keys otherwise. */
    modelValue?: string | string[];
    options?: TreeSelectNode[];
    selectionMode?: TreeSelectionMode;
    placeholder?: string;
    emptyMessage?: string;
    /** Shows a search box above the tree. */
    filter?: boolean;
    filterBy?: TreeFilterField | TreeFilterField[];
    /** `lenient` keeps the descendants of a matching node; `strict` filters them too. */
    filterMode?: 'lenient' | 'strict';
    filterPlaceholder?: string;
    /** Locale used by `toLocaleLowerCase` when comparing. */
    filterLocale?: string;
    showClear?: boolean;
    loading?: boolean;
    disabled?: boolean;
    size?: ButtonProps['size'];
    color?: ButtonProps['color'];
    variant?: ButtonProps['variant'];
    trailingIcon?: string;
    clearIcon?: string;
    /** Forwarded to `UPopover` (align, side, sideOffset, …). */
    content?: PopoverProps['content'] & { class?: unknown };
    /** Read by `useFormField` to wire into `UFormField`. */
    id?: string;
    name?: string;
    highlight?: boolean;
    class?: unknown;
}
