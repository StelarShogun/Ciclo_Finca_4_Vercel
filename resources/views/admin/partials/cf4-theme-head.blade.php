<meta name="color-scheme" content="light dark">
<meta name="theme-color" content="#051F20" id="cf4-theme-color">

<script>
    (() => {
        const STORAGE_KEY = 'cf4-theme';

        try {
            const savedTheme = localStorage.getItem(STORAGE_KEY);
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = savedTheme || (prefersDark ? 'dark' : 'light');

            document.documentElement.dataset.theme = theme;
            document.documentElement.style.colorScheme = theme;

            const themeColor = document.querySelector('#cf4-theme-color');
            if (themeColor) {
                themeColor.setAttribute('content', theme === 'dark' ? '#051F20' : '#DAF1DE');
            }
        } catch (error) {
            document.documentElement.dataset.theme = 'light';
            document.documentElement.style.colorScheme = 'light';
        }
    })();
</script>
