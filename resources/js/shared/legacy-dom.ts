/** Typed DOM helpers for legacy scripts (admin + client bundles). */

export function $id<T extends HTMLElement = HTMLElement>(id: string): T | null {
  return document.getElementById(id) as T | null;
}

export function $<T extends Element = Element>(selector: string, root: ParentNode = document): T | null {
  return root.querySelector(selector) as T | null;
}

export function $all<T extends Element = Element>(selector: string, root: ParentNode = document): T[] {
  return Array.from(root.querySelectorAll(selector)) as T[];
}

export function must<T extends Element>(el: T | null, _label?: string): T {
  if (!el) {
    throw new Error('Missing required DOM element');
  }
  return el;
}

export function asInput(el: Element | null): HTMLInputElement | null {
  return el instanceof HTMLInputElement ? el : null;
}

export function asForm(el: Element | null): HTMLFormElement | null {
  return el instanceof HTMLFormElement ? el : null;
}

export function asSelect(el: Element | null): HTMLSelectElement | null {
  return el instanceof HTMLSelectElement ? el : null;
}

export function asButton(el: Element | null): HTMLButtonElement | null {
  return el instanceof HTMLButtonElement ? el : null;
}

export function eventTargetEl(event: Event): Element | null {
  return event.target instanceof Element ? event.target : null;
}

export type Cf4Theme = 'dark' | 'light';

export interface AjaxPaginationOptions {
  pushState?: boolean;
  scroll?: boolean;
}
