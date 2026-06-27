import type { VNode } from 'vue';
import type { ContentNavigationItem } from '@nuxt/content';
import type { AppConfig } from '@nuxt/schema';
import type { UseFuseOptions } from '@vueuse/integrations/useFuse';
import theme from '#build/ui/content/content-search';
import type { ButtonProps, LinkProps, ModalProps, CommandPaletteProps, CommandPaletteSlots, CommandPaletteGroup, CommandPaletteItem, IconProps, LinkPropsKeys } from '../../types';
import type { ComponentConfig } from '../../types/tv';
type ContentSearch = ComponentConfig<typeof theme, AppConfig, 'contentSearch'>;
export interface ContentSearchLink extends Omit<LinkProps, 'custom'> {
    label?: string;
    description?: string;
    /**
     * @IconifyIcon
     */
    icon?: IconProps['name'];
    children?: ContentSearchLink[];
}
export interface ContentSearchFile {
    id: string;
    title: string;
    titles: string[];
    level: number;
    content: string;
}
export interface ContentSearchItem extends Omit<LinkProps, 'custom'>, CommandPaletteItem {
    level?: number;
    /**
     * @IconifyIcon
     */
    icon?: IconProps['name'];
}
export interface ContentSearchProps<T extends ContentSearchLink = ContentSearchLink> extends Pick<ModalProps, 'title' | 'description' | 'overlay' | 'transition' | 'content' | 'dismissible' | 'fullscreen' | 'modal' | 'portal'>, Pick<CommandPaletteProps<CommandPaletteGroup<ContentSearchItem>, ContentSearchItem>, 'icon' | 'placeholder' | 'autofocus' | 'loading' | 'loadingIcon' | 'closeIcon' | 'groups'> {
    /**
     * @defaultValue 'md'
     */
    size?: ContentSearch['variants']['size'];
    /**
     * Display a close button in the input (useful when inside a Modal for example).
     * `{ size: 'md', color: 'neutral', variant: 'ghost' }`{lang="ts-type"}
     * @emits 'update:open'
     * @defaultValue true
     */
    close?: boolean | Omit<ButtonProps, LinkPropsKeys>;
    /**
     * Keyboard shortcut to open the search (used by [`defineShortcuts`](https://ui.nuxt.com/docs/composables/define-shortcuts))
     * @defaultValue 'meta_k'
     */
    shortcut?: string;
    /** Links group displayed as the first group in the command palette. */
    links?: T[];
    navigation?: ContentNavigationItem[];
    files?: ContentSearchFile[];
    /**
     * Options for [useFuse](https://vueuse.org/integrations/useFuse) passed to the [CommandPalette](https://ui.nuxt.com/docs/components/command-palette).
     * @defaultValue {
        fuseOptions: {
          ignoreLocation: true,
          includeMatches: true,
          threshold: 0.1,
          keys: ['label', 'suffix']
        },
        resultLimit: 12,
        matchAllWhenSearchEmpty: true
      }
     */
    fuse?: UseFuseOptions<T>;
    /**
     * Delay (in milliseconds) before the search term is passed to Fuse (debounced).
     * Useful for large doc sets where running fuzzy search on every keystroke is the bottleneck — the input stays responsive while Fuse only re-runs after typing settles.
     * Set to `0` to disable.
     * @defaultValue 100
     */
    searchDelay?: number;
    /**
     * When `true`, the theme command will be added to the groups.
     * @defaultValue true
     */
    colorMode?: boolean;
    class?: any;
    ui?: ContentSearch['slots'] & CommandPaletteProps<CommandPaletteGroup<ContentSearchItem>, ContentSearchItem>['ui'];
}
export type ContentSearchSlots = CommandPaletteSlots<ContentSearchItem> & {
    content?(props: {
        close: () => void;
    }): VNode[];
};
declare const _default: typeof __VLS_export;
export default _default;
declare const __VLS_export: <T extends ContentSearchLink>(__VLS_props: NonNullable<Awaited<typeof __VLS_setup>>["props"], __VLS_ctx?: __VLS_PrettifyLocal<Pick<NonNullable<Awaited<typeof __VLS_setup>>, "attrs" | "emit" | "slots">>, __VLS_exposed?: NonNullable<Awaited<typeof __VLS_setup>>["expose"], __VLS_setup?: Promise<{
    props: import("vue").PublicProps & __VLS_PrettifyLocal<(ContentSearchProps<T> & {
        searchTerm?: string;
    }) & {
        "onUpdate:searchTerm"?: ((value: string) => any) | undefined;
    }> & (typeof globalThis extends {
        __VLS_PROPS_FALLBACK: infer P;
    } ? P : {});
    expose: (exposed: import("vue").ShallowUnwrapRef<{
        commandPaletteRef: Readonly<import("vue").ShallowRef<{} | null, {} | null>>;
    }>) => void;
    attrs: any;
    slots: ContentSearchSlots;
    emit: (event: "update:searchTerm", value: string) => void;
}>) => import("vue").VNode & {
    __ctx?: Awaited<typeof __VLS_setup>;
};
type __VLS_PrettifyLocal<T> = (T extends any ? {
    [K in keyof T]: T[K];
} : {
    [K in keyof T as K]: T[K];
}) & {};
