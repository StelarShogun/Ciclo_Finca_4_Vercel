import type { VNode } from 'vue';
import type { AppConfig } from '@nuxt/schema';
import theme from '#build/ui/dashboard-search';
import type { ButtonProps, ModalProps, CommandPaletteProps, CommandPaletteSlots, CommandPaletteGroup, CommandPaletteItem, LinkPropsKeys } from '../types';
import type { ComponentConfig } from '../types/tv';
type DashboardSearch = ComponentConfig<typeof theme, AppConfig, 'dashboardSearch'>;
export interface DashboardSearchProps<T extends CommandPaletteItem = CommandPaletteItem> extends Pick<ModalProps, 'title' | 'description' | 'overlay' | 'transition' | 'content' | 'dismissible' | 'fullscreen' | 'modal' | 'portal'>, Pick<CommandPaletteProps<CommandPaletteGroup<T>, T>, 'icon' | 'placeholder' | 'autofocus' | 'loading' | 'loadingIcon' | 'closeIcon' | 'groups' | 'fuse'> {
    /**
     * @defaultValue 'md'
     */
    size?: DashboardSearch['variants']['size'];
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
    /**
     * Delay (in milliseconds) before the search term is passed to Fuse (debounced).
     * Useful for large datasets where running fuzzy search on every keystroke is the bottleneck — the input stays responsive while Fuse only re-runs after typing settles.
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
    ui?: DashboardSearch['slots'] & CommandPaletteProps<CommandPaletteGroup<CommandPaletteItem>, CommandPaletteItem>['ui'];
}
export type DashboardSearchSlots = CommandPaletteSlots<CommandPaletteItem> & {
    content?(props: {
        close: () => void;
    }): VNode[];
};
declare const _default: typeof __VLS_export;
export default _default;
declare const __VLS_export: __VLS_WithSlots<import("vue").DefineComponent<DashboardSearchProps<CommandPaletteItem> & {
    open?: boolean;
    searchTerm?: string;
}, {
    commandPaletteRef: Readonly<import("vue").ShallowRef<{} | null, {} | null>>;
}, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {
    "update:open": (value: boolean) => any;
    "update:searchTerm": (value: string) => any;
}, string, import("vue").PublicProps, Readonly<DashboardSearchProps<CommandPaletteItem> & {
    open?: boolean;
    searchTerm?: string;
}> & Readonly<{
    "onUpdate:open"?: ((value: boolean) => any) | undefined;
    "onUpdate:searchTerm"?: ((value: string) => any) | undefined;
}>, {
    close: boolean | Omit<ButtonProps, LinkPropsKeys>;
    colorMode: boolean;
    searchDelay: number;
    fullscreen: boolean;
    shortcut: string;
}, {}, {}, {}, string, import("vue").ComponentProvideOptions, false, {}, any>, DashboardSearchSlots>;
type __VLS_WithSlots<T, S> = T & {
    new (): {
        $slots: S;
    };
};
