const STORAGE_KEY = 'cf4-theme';
const TRANSITION_MS = 650;
const TOGGLE_ANIM_MS = 560;

function getSystemTheme() {
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

function getCurrentTheme() {
    try {
        return localStorage.getItem(STORAGE_KEY) || getSystemTheme();
    } catch {
        return getSystemTheme();
    }
}

function beginThemeTransition() {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    const root = document.documentElement;
    root.classList.add('cf4-theme-transition');
    window.clearTimeout(beginThemeTransition._timer);
    beginThemeTransition._timer = window.setTimeout(() => {
        root.classList.remove('cf4-theme-transition');
    }, TRANSITION_MS);
}

function syncToggleButtons(theme, animate = false) {
    const isDark = theme === 'dark';

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.setAttribute('aria-pressed', String(isDark));
        button.setAttribute(
            'aria-label',
            isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'
        );
        button.setAttribute('title', isDark ? 'Modo claro' : 'Modo oscuro');

        const labelText = isDark ? 'Modo claro' : 'Modo oscuro';
        button.querySelectorAll('.theme-toggle-btn__sidebar-label').forEach((el) => {
            el.textContent = labelText;
        });
        document.querySelectorAll('[data-theme-toggle-label]').forEach((el) => {
            el.textContent = labelText;
        });

        if (animate) {
            button.classList.add('is-toggling');
            window.setTimeout(() => button.classList.remove('is-toggling'), TOGGLE_ANIM_MS);
        }
    });
}

function applyTheme(theme, { animate = false } = {}) {
    const normalizedTheme = theme === 'dark' ? 'dark' : 'light';

    if (animate) {
        beginThemeTransition();
    }

    document.documentElement.dataset.theme = normalizedTheme;
    document.documentElement.style.colorScheme = normalizedTheme;

    const themeColor = document.querySelector('#cf4-theme-color');
    if (themeColor) {
        themeColor.setAttribute('content', normalizedTheme === 'dark' ? '#051F20' : '#DAF1DE');
    }

    syncToggleButtons(normalizedTheme, animate);
}

function toggleTheme() {
    const current = document.documentElement.dataset.theme || getCurrentTheme();
    const next = current === 'dark' ? 'light' : 'dark';

    const apply = () => {
        try {
            localStorage.setItem(STORAGE_KEY, next);
        } catch {
            // Si localStorage falla, igual aplicamos el tema en sesión.
        }

        applyTheme(next, { animate: true });
    };

    if (typeof document.startViewTransition === 'function') {
        document.startViewTransition(apply);
        return;
    }

    apply();
}

document.addEventListener('DOMContentLoaded', () => {
    applyTheme(getCurrentTheme());

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.stopPropagation();
            toggleTheme();
        });
    });

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        try {
            if (!localStorage.getItem(STORAGE_KEY)) {
                applyTheme(getSystemTheme());
            }
        } catch {
            applyTheme(getSystemTheme());
        }
    });
});
